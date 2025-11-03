<?php

namespace App\Services\Integration;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Helpers\CurrencyHelper;
use App\Helpers\SaleModuleHelper;
use App\Helpers\ItemHelper;
use App\Helpers\TaxHelper;
use App\Helpers\ConstantHelper;

use App\Models\ErpSaleOrder;
use App\Models\ErpSoItem;
use App\Models\ErpSoItemAttribute;
use App\Models\ErpSoItemDelivery;
use App\Models\ErpSaleOrderTed;
use App\Models\Customer;
use App\Models\ERP\ErpConsignee;
use App\Models\ERP\ErpExternalIntegration;
use App\Models\ErpPickupSchedule;
use App\Models\Item;
use App\Models\ErpStore;
use App\Models\ErpTripPlanDetail;
use App\Models\ErpTripPlanHeader;
use App\Services\Common\DocumentLockService;


class SaleOrderService
{
    protected DocumentLockService $lockService;

    public function __construct(DocumentLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * Main method to create sale order
     */
    public function create($request, $user)
    {
        try {
            [$user, $erpIntegration, $store, $customer, $currencyExchange] = $this->validateAndSetup($request, $user);

            DB::beginTransaction();

            $erpSaleOrder = $this->createSaleOrder($user, $erpIntegration, $store, $customer, $currencyExchange, $request);
            $saleOrder = $erpSaleOrder['erpSaleOrders'];
            $erpTripPlan = $erpSaleOrder['erpTripPlanHeader'];
            $saleOrderCount = $erpSaleOrder['count'];

            DB::commit();

            return ['status' => true, 'message' => "{$saleOrderCount} Sale Order(s) created successfully", 'data' => []];

        } catch (Exception $ex) {
            DB::rollBack();
            return ['status' => false, 'message' => $ex->getMessage(), 'errors' => $ex];
        }
    }

    /**
     * Main method to update sale order
     */
    public function update($request, $user)
    {
        try {
            [$user, $erpIntegration, $store, $customer, $currencyExchange, $erpSaleOrder] = $this->validateAndSetupForUpdate($request, $user);

            DB::beginTransaction();

            $erpTripPlan = ErpTripPlanHeader::where('book_id', $erpIntegration->trip_book_id)
                ->where('document_number', $request->trip_number)
                ->first();

            $this->updateItems($erpSaleOrder, $erpTripPlan, $request->delivery_skus, $customer, $erpIntegration, $store, $request);

            $this->finalizeSaleOrder($erpSaleOrder, $request);

            DB::commit();

            return ['status' => true, 'message' => 'Sale Order updated successfully', 'data' => $erpSaleOrder];

        } catch (Exception $ex) {
            DB::rollBack();
            // dd($ex);
            return ['status' => false, 'message' => $ex->getMessage()];
        }
    }

    /**
     * Validate request and prepare setup entities
     */
    private function validateAndSetup($request, $user)
    {
        $erpIntegration = ErpExternalIntegration::with([
                'soBook:id,book_code', 
                'tripBook:id,book_code',
                'pickupScheduleBook:id,book_code',
            ])
            ->whereGroupId($request->user()->group_id)
            ->whereCompanyId($request->user()->company_id)
            ->whereOrganizationId($request->organization_id)
            ->whereStoreId($request->store_id)
            ->first();

        if (!$erpIntegration) throw new Exception('Integration mapping not found.');
        if (!$erpIntegration->trip_book_id) throw new Exception('Trip book id not found.');
        if (!$erpIntegration->so_book_id) throw new Exception('SO book id not found.');
        if (!$erpIntegration->pickup_schedule_book_id) throw new Exception('Pickup Schedule book id not found.');

        $store = ErpStore::with('address')->find($request->store_id);

        if (!$store || !$store->address) throw new Exception('Store or store address not assigned.');
        $customer = Customer::find($erpIntegration->customer_id);
        if (!$customer) throw new Exception('No customer found.');
        if (!$customer->paymentTerm) throw new Exception("Payment term not found for customer");

        $currencyExchange = CurrencyHelper::getCurrencyExchangeRates($customer->currency_id, $request->trip_date);
        if (!$currencyExchange['status']) throw new Exception($currencyExchange['message']);

        return [$user, $erpIntegration, $store, $customer, $currencyExchange];
    }

    /**
     * Validate request and prepare setup entities
     */
    private function validateAndSetupForUpdate($request, $user)
    {
        $erpIntegration = ErpExternalIntegration::with(['soBook:id,book_code', 'tripBook:id,book_code'])
            ->whereGroupId($request->user()->group_id)
            ->whereCompanyId($request->user()->company_id)
            ->whereOrganizationId($request->organization_id)
            ->whereStoreId($request->store_id)
            ->first();

        if (!$erpIntegration) throw new Exception('Integration mapping not found.');
        if (!$erpIntegration->trip_book_id) throw new Exception('Trip book id not found.');
        if (!$erpIntegration->so_book_id) throw new Exception('SO book id not found.');

        $store = ErpStore::with('address')->find($request->store_id);

        if (!$store || !$store->address) throw new Exception('Store or store address not assigned.');
        $customer = Customer::find($erpIntegration->customer_id);
        if (!$customer) throw new Exception('No customer found.');
        if (!$customer->paymentTerm) throw new Exception("Payment term not found for customer");

        $currencyExchange = CurrencyHelper::getCurrencyExchangeRates($customer->currency_id, $request->trip_date);
        if (!$currencyExchange['status']) throw new Exception($currencyExchange['message']);

        $erpSaleOrder = ErpSaleOrder::where('trip_number',$request->trip_number)
            ->where('ref_order_number', $request->ref_order_number)
            ->first();
        if (!$erpSaleOrder) throw new Exception(ConstantHelper::TRIP_NUMBER_NOT_FOUND);

        return [$user, $erpIntegration, $store, $customer, $currencyExchange, $erpSaleOrder];
    }

    /**
     * Create Sale Order record
     */
    private function createSaleOrder($user, $erpIntegration, $store, $customer, $currencyExchange, $request)
    {
        $tripData = [
            'organization_id'    => $user?->organization_id,
            'group_id'           => $user?->group_id,
            'company_id'         => $user?->company_id,
            'book_id'            => $erpIntegration->trip_book_id,
            'book_code'          => $erpIntegration->tripBook?->book_code,
            'document_type'      => ConstantHelper::SO_SERVICE_ALIAS,
            'document_number'    => $request->trip_number,
            'document_date'      => $request->trip_date,
            'order_type'         => SaleModuleHelper::ORDER_TYPE_DEFAULT,
            'store_id'           => $store->id,
            'store_code'         => $store->store_name,
            'customer_id'        => $customer->id,
            'customer_code'      => $customer->customer_code,
            'customer_email'     => $customer->email,
            'customer_phone_no'  => $customer->mobile,
            'customer_gstin'     => $customer->compliances?->gstin_no,
            'currency_id'        => $customer->currency_id,
            'currency_code'      => $customer->currency?->short_name,
            'payment_term_id'    => $customer->payment_terms_id,
            'payment_term_code'  => $customer->paymentTerm?->name,
            'credit_days'        => $customer->credit_days ?? 0,
            'document_status'    => ConstantHelper::APPROVAL_NOT_REQUIRED,
            'remarks'            => 'Order pushed through API',
            'data_source_type'   => 'API', // WEB or API
            'org_currency_id'       => $currencyExchange['data']['org_currency_id'],
            'org_currency_code'     => $currencyExchange['data']['org_currency_code'],
            'org_currency_exg_rate' => $currencyExchange['data']['org_currency_exg_rate'],
            'comp_currency_id'      => $currencyExchange['data']['comp_currency_id'],
            'comp_currency_code'    => $currencyExchange['data']['comp_currency_code'],
            'comp_currency_exg_rate'=> $currencyExchange['data']['comp_currency_exg_rate'],
            'group_currency_id'     => $currencyExchange['data']['group_currency_id'],
            'group_currency_code'   => $currencyExchange['data']['group_currency_code'],
            'group_currency_exg_rate'=> $currencyExchange['data']['group_currency_exg_rate'],
        ];

        // Create Trip Plan
        $erpTripPlan = ErpTripPlanHeader::create($tripData);

        // Create Pickup Schedule
        $this->createPickupSchedule($tripData, $erpIntegration, $erpTripPlan->id);

        $erpSaleOrders = [];
        $orders = $request->orders;
        foreach($orders as $order){

            // Generate Document Number
            $numberPatternData = Helper::generateDocumentNumberNew($erpIntegration->so_book_id, $request->trip_date, null, null);
            if (!isset($numberPatternData) || !$numberPatternData['document_number']) {
                throw new Exception("Unable to generate document number for order number {$order['ref_order_number']}.");
            }

            $documentNumber = $numberPatternData['document_number'];

            $saleOrder = null;

            // Validate duplicate document number
            $erpSaleOrder = ErpSaleOrder::where('book_id', $erpIntegration->so_book_id)
                        ->where('document_number', $documentNumber)
                        ->exists();

            if ($erpSaleOrder){
                throw new Exception(ConstantHelper::DUPLICATE_DOCUMENT_NUMBER);
            } 

            // Define a unique lock key for this document number
            $lockKey = "org_{$user?->organization_id}_book_{$erpIntegration->so_book_id}_doc_{$documentNumber}";
            $lockResult = $this->lockService->lockDocumentNumber($lockKey);
            
            if(!$lockResult['success']) {
                throw new Exception($lockResult['message']);
            }

            // Create Sale Order
            $saleOrder = ErpSaleOrder::create(array_merge(
                $tripData,
                [
                    'book_id' => $erpIntegration->so_book_id,
                    'book_code' => $erpIntegration->soBook?->book_code,
                    'document_number' => $documentNumber,
                    'doc_number_type' => $numberPatternData['type'],
                    'doc_reset_pattern' => $numberPatternData['reset_pattern'],
                    'doc_prefix' => $numberPatternData['prefix'],
                    'doc_suffix' => $numberPatternData['suffix'],
                    'doc_no' => $numberPatternData['doc_no'],
                    'ref_order_number' => $order['ref_order_number'],
                    'trip_number' => $request->trip_number,
                    'trip_id' => $erpTripPlan->id,
                    'consignee_id' => $order['consignee_id'],
                ]
            ));

            $erpSaleOrders[] = $saleOrder;

            if (!$saleOrder) {
                throw new Exception("Sale order creation failed for order number {$order['ref_order_number']}");
            }
            
            // Run insertion safely after lock check
            $this->assignAddresses($saleOrder, $store, $order['shipping_address'] ?? []);
            $this->processItems($saleOrder, $erpTripPlan, $order, $customer, $erpIntegration, $store);
            $this->finalizeSaleOrder($saleOrder, $request);
        }

        return [
            'erpSaleOrders' => $erpSaleOrders,
            'erpTripPlanHeader' => $erpTripPlan,
            'count'  => count($erpSaleOrders)
        ];
    }

    /**
     * Create pickup schedule record
     */
    private function createPickupSchedule($tripData, $erpIntegration, $erpTripPlanId){
         // Generate Document Number
        $numberPatternData = Helper::generateDocumentNumberNew($erpIntegration->pickup_schedule_book_id, $tripData['document_date'], null, null);
        if (!isset($numberPatternData) || !$numberPatternData['document_number']) {
            throw new Exception("Unable to generate document number for pickup schedule.");
        }

        $documentNumber = $numberPatternData['document_number'];

        $erpPickupSchedules = ErpPickupSchedule::create(array_merge(
                $tripData,
                [
                    'book_id' => $erpIntegration->pickup_schedule_book_id,
                    'book_code' => $erpIntegration->pickupScheduleBook?->book_code,
                    'trip_id' => $erpTripPlanId,
                    'trip_no' => $tripData['document_number'],
                    'document_number' => $documentNumber,
                    'doc_number_type' => $numberPatternData['type'],
                    'doc_reset_pattern' => $numberPatternData['reset_pattern'],
                    'doc_prefix' => $numberPatternData['prefix'],
                    'doc_suffix' => $numberPatternData['suffix'],
                    'doc_no' => $numberPatternData['doc_no'],
                    'remark' => "Order pushed through API",
                ]
            ));

        if(!$erpPickupSchedules) throw new Exception("Unable to create pickup schedule record.");
    }

    /**
     * Assign Billing, Shipping, and Location addresses
     */
    private function assignAddresses($saleOrder, $store, $shipping)
    {
        $billingAddress  = self::createAddress([$saleOrder, 'billing_address_details'], $shipping, 'billing');
        $shippingAddress = self::createAddress([$saleOrder, 'shipping_address_details'], $shipping, 'shipping');
        self::createAddress([$saleOrder, 'location_address_details'], $store->address, 'location');

        $saleOrder->update([
            'billing_address'  => $billingAddress?->id,
            'shipping_address' => $shippingAddress?->id,
        ]);
    }

    /**
     * Manage Consignee Billing & Shipping address
     */
    private function manageConsigneeAddress($consigneeId, $consigneeAddress)
    {
        $consignee = ErpConsignee::find($consigneeId);
        if (!$consignee) throw new Exception("Consignee not found.");

        return $consignee->addresses()->updateOrCreate(
            [
                'addressable_id'    => $consignee->id,
                'addressable_type'  => ErpConsignee::class,
                'address'           => $consigneeAddress['address'] ?? null,
                'country_id'        => $consigneeAddress['country_id'] ?? null,
                'state_id'          => $consigneeAddress['state_id'] ?? null,
                'city_id'           => $consigneeAddress['city_id'] ?? null,
                'pincode'           => $consigneeAddress['pincode'] ?? null,
            ],
            [
                'type'              => 'shipping',
                'is_shipping'       => 1,
                'line_1'            => $consigneeAddress['address'] ?? null,
            ]
        );
    }


    /**
     *  Handle items (validation, tax, attributes, deliveries)
     * Process SKUs for a Sale Order and create related records.
     *
     * @param  SaleOrder              $saleOrder
     * @param  ErpTripPlanHeader      $erpTripPlan
     * @param  array                  $skus
     * @param  Customer               $customer
     * @param  ErpExternalIntegration $erpIntegration
     * @param  ErpStore               $store
     * @throws Exception              $ex
     */
    private function processItems($saleOrder, $erpTripPlan, $order, $customer, $erpIntegration, $store)
    {
        $skus = $order['order_items'];
        // Ensure SKUs exist
        if (empty($skus)) {
            throw new Exception("SKU not found.");
        }

        $totalItemValue = 0;
        $totalTax       = 0;

        foreach ($skus as $sku) {
            // Fetch Item record
            $item = Item::find($sku['item_id']);
            if (!$item) {
                throw new Exception("No item found with item code: {$sku['item_code']}");
            }

            // Validate BOM existence
            $bom = ItemHelper::checkItemBomExists($item->id, []);
            if (empty($bom['bom_id']) && $bom['status'] !== 'bom_not_required') {
                throw new Exception("No BOM found with item code: {$sku['item_code']}");
            }

            // Calculate base values
            $qty   = (float) $sku['item_qty'];
            $rate  = (float) $sku['item_rate'];
            $value = $qty * $rate;
            $totalItemValue += $value;

            // Calculate applicable taxes (only if tax rules apply)
            $taxDetails = [];
            if (SaleModuleHelper::checkTaxApplicability($customer->id, $erpIntegration->so_book_id)) {
                $taxDetails = TaxHelper::calculateTax(
                    $item->hsn_id,
                    $rate,
                    $store->address?->country_id,
                    $store->address?->state_id,
                    $saleOrder->shipping_address_details?->country_id,
                    $saleOrder->shipping_address_details?->state_id,
                    'sale'
                );
            }

            // Manage Consignee & Consignee address
            $consigneeAddress = $this->manageConsigneeAddress($order['consignee_id'], $order['shipping_address'] ?? []);

            // Build common item data for reuse
            $itemsData = [
                'sale_order_id'             => $saleOrder->id,
                'consignee_id'              => $order['consignee_id'],
                'sale_type'                 => $order['sale_type'],
                'shipping_addressable_id'   => $consigneeAddress->id,
                'bom_id'                    => $bom['bom_id'],
                'item_id'                   => $item->id,
                'item_code'                 => $item->item_code,
                'item_name'                 => $item->item_name,
                'hsn_id'                    => $item->hsn_id,
                'hsn_code'                  => $item->hsn?->code,
                'uom_id'                    => $item->uom_id,
                'uom_code'                  => $item->uom?->name,
                'order_qty'                 => $qty,
                'inventory_uom_qty'         => $qty,
                'inventory_uom_id'          => $item->uom?->id,
                'inventory_uom_code'        => $item->uom?->name,
                'rate'                      => $rate,
                'delivery_date'             => $saleOrder->document_date,
                // 'tax_amount'                => $itemTax,
                // 'total_item_amount'         => $value + $itemTax,
                'remarks'                   => 'Order pushed by API',
            ];

            // Create Sale Order Item
            $soItem = ErpSoItem::create($itemsData);

            // Apply taxes and accumulate total tax
            $itemTax = $this->applyTaxes($saleOrder, $soItem, $value, $taxDetails, $totalTax);

            // Update tax amount
            $soItem->update([
                'tax_amount' => $itemTax,
                'total_item_amount' => $value + $itemTax
            ]);
            
            // Attach item attributes if provided
            $itemAttributes = $sku['item_attributes'] ?? [];
            foreach ($itemAttributes as $attribute) {
                ErpSoItemAttribute::updateOrCreate(
                    [
                        'sale_order_id'     => $saleOrder->id,
                        'so_item_id'        => $soItem->id,
                        'item_attribute_id' => $attribute['id'],
                    ],
                    [
                        'item_code'       => $soItem->item_code,
                        'attribute_name'  => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                        'attr_name'       => $attribute['attribute_name_id'],
                        'attr_value'      => $attribute['attribute_value_id'],
                    ]
                );
            }

            // Ensure delivery details exist for this item
            $soItemDelivery = ErpSoItemDelivery::updateOrCreate(
                [
                    'sale_order_id' => $saleOrder->id,
                    'so_item_id'    => $soItem->id,
                ],
                [
                    'qty'           => $qty,
                    'invoice_qty'   => 0,
                    'ledger_id'     => null,
                    'delivery_date' => $saleOrder->document_date,
                ]
            );

            //Prepare data for Trip Plan Details & remove sale_order_id
            unset($itemsData['sale_order_id']);
            $tripPlanData = array_merge(
                $itemsData,
                [
                    'order_id'              => $saleOrder->id,
                    'order_item_id'         => $soItem->id,
                    'trip_header_id'        => $erpTripPlan->id,
                    'total_amount'          => $soItem->total_item_amount ?? 0,
                    'order_item_delivery_id'=> $soItemDelivery->id,
                    'attributes' => $soItem->item_attributes_array() ?? null,
                    'planned_qty'=> $qty,
                ]
            );
            
            // Create Trip Plan Detail record
            $tripPlanDetail = ErpTripPlanDetail::create($tripPlanData);
        }

        // Update Sale Order totals
        $saleOrder->update([
            'total_item_value' => $totalItemValue,
            'total_tax_value'  => $totalTax,
            'total_amount'     => $totalItemValue + $totalTax,
        ]);
    }

    /**
     * Update items (validation, tax, attributes, deliveries)
     * Process SKUs for a Sale Order and update/add or delete related records.
     *
     * @param  SaleOrder              $saleOrder
     * @param  ErpTripPlanHeader      $erpTripPlan
     * @param  array                  $skus
     * @param  Customer               $customer
     * @param  ErpExternalIntegration $erpIntegration
     * @param  ErpStore               $store
     * @throws Exception              $ex
     */
    private function updateItems($saleOrder, $erpTripPlan, $skus, $customer, $erpIntegration, $store, $request)
    {
        // Ensure SKUs exist
        if (empty($skus)) {
            throw new Exception("SKU not found.");
        }

        $totalTax       = 0;
        $totalItemValue = 0;

        foreach ($skus as $sku) {
            $action = $sku['action_type'];

            // Fetch Item record
            $item = Item::find($sku['item_id']);
            if (!$item) {
                throw new Exception("No item found with item code: {$sku['item_code']}");
            }

            // Calculate base values
            $qty   = (float) $sku['item_qty'];
            $rate  = (float) $sku['item_rate'];
            $value = $qty * $rate;

            // For delete, we only need to remove
            if ($action === 'delete') {
                $this->handleDeleteItem($saleOrder, $erpTripPlan, $item);
                continue;
            }


            $totalItemValue += $value;

            // Validate BOM existence
            $bom = ItemHelper::checkItemBomExists($item->id, []);
            if (empty($bom['bom_id']) && $bom['status'] !== 'bom_not_required') {
                throw new Exception("No BOM found with item code: {$sku['item_code']}");
            }

            // Calculate applicable taxes (only if tax rules apply)
            $taxDetails = [];
            if (SaleModuleHelper::checkTaxApplicability($customer->id, $erpIntegration->so_book_id)) {
                $taxDetails = TaxHelper::calculateTax(
                    $item->hsn_id,
                    $rate,
                    $store->address?->country_id,
                    $store->address?->state_id,
                    $saleOrder->shipping_address_details?->country_id,
                    $saleOrder->shipping_address_details?->state_id,
                    'sale'
                );
            }
            
            // Manage Consignee & Consignee address
            $consigneeId = $request->consignee_id ?? NULL;
            if($consigneeId && !empty($request->shipping_address)){
                $consigneeAddress = $request->shipping_address ?? [];
                $consigneeAddress = $this->manageConsigneeAddress($consigneeId, $consigneeAddress);
            }

            // Build common item data for reuse
            $itemsData = [
                'sale_order_id'             => $saleOrder->id,
                'consignee_id'              => $consigneeId,
                'sale_type'                 => $request->sale_type,
                'shipping_addressable_id'   => @$consigneeAddress->id,
                'bom_id'                    => $bom['bom_id'],
                'item_id'                   => $item->id,
                'item_code'                 => $item->item_code,
                'item_name'                 => $item->item_name,
                'hsn_id'                    => $item->hsn_id,
                'hsn_code'                  => $item->hsn?->code,
                'uom_id'                    => $item->uom_id,
                'uom_code'                  => $item->uom?->name,
                'order_qty'                 => $qty,
                'inventory_uom_qty'         => $qty,
                'inventory_uom_id'          => $item->uom?->id,
                'inventory_uom_code'        => $item->uom?->name,
                'rate'                      => $rate,
                'delivery_date'             => $saleOrder->document_date,
                'remarks'                   => 'Order pushed by API',
            ];

            // Handle ErpSoItem Add or Update
            $soItem = ErpSoItem::where('sale_order_id', $saleOrder->id)
                    ->when($consigneeId, function($q) use($consigneeId){
                        $q->where('consignee_id', $consigneeId);
                    })
                    ->where('item_id', $item->id)
                    ->first();

           if ($action === 'add') {
                if ($soItem) throw new Exception("SO Item {$item->item_name} already exists.");
                $soItem = ErpSoItem::create($itemsData);
            } elseif ($action === 'update') {
                if (!$soItem) throw new Exception("SO Item {$item->item_name} not found.");

                // Subtract old values before updating
                $oldItemValue = (float) $soItem->order_qty * (float) $soItem->rate;
                $oldTaxAmount = (float) $soItem->tax_amount;
                $oldTotalAmount = (float) $soItem->total_item_amount;

                $saleOrder->decrement('total_item_value', $oldItemValue);
                $saleOrder->decrement('total_tax_value', $oldTaxAmount);
                $saleOrder->decrement('total_amount', $oldTotalAmount);
                
                $soItem = ErpSoItem::updateOrCreate([
                    'sale_order_id' => $saleOrder->id,
                    // 'consignee_id'  => $request->consignee_id,
                    'item_id'       => $item->id,
                ], $itemsData);
            }


            // Apply taxes and accumulate total tax
            $itemTax = $this->applyTaxes($saleOrder, $soItem, $value, $taxDetails, $totalTax);

            // Update tax amount
            $soItem->update([
                'tax_amount' => $itemTax,
                'total_item_amount' => $value + $itemTax
            ]);

            // Attach item attributes if provided
            foreach ($sku['item_attributes'] ?? [] as $attribute) {
                ErpSoItemAttribute::updateOrCreate(
                    [
                        'sale_order_id'     => $saleOrder->id,
                        'so_item_id'        => $soItem->id,
                        'item_attribute_id' => $attribute['id'],
                    ],
                    [
                        'item_code'       => $soItem->item_code,
                        'attribute_name'  => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                        'attr_name'       => $attribute['attribute_name_id'],
                        'attr_value'      => $attribute['attribute_value_id'],
                    ]
                );
            }

            // Ensure delivery details exist for this item
            $soItemDelivery = ErpSoItemDelivery::updateOrCreate(
                [
                    'sale_order_id' => $saleOrder->id,
                    'so_item_id'    => $soItem->id,
                ],
                [
                    'qty'           => $qty,
                    'invoice_qty'   => 0,
                    'ledger_id'     => null,
                    'delivery_date' => $saleOrder->document_date,
                ]
            );

            //Prepare data for Trip Plan Details & remove sale_order_id
            unset($itemsData['sale_order_id']);
            $tripPlanData = array_merge(
                $itemsData,
                [
                    'order_id'              => $saleOrder->id,
                    'order_item_id'         => $soItem->id,
                    'trip_header_id'        => $erpTripPlan->id,
                    'total_amount'          => $soItem->total_item_amount,
                    'order_item_delivery_id'=> $soItemDelivery->id,
                    'attributes' => json_encode($soItem->item_attributes_array()) ?? null,
                    'planned_qty'=> $qty,
                ]
            );

            // Create Trip Plan Detail record
            ErpTripPlanDetail::updateOrCreate(
                [
                    'order_id'      => $saleOrder->id,
                    'order_item_id' => $soItem->id,
                    'trip_header_id' => $erpTripPlan->id
                ],
                $tripPlanData
            );
        }

        // Update Sale Order totals
        $saleOrder->increment('total_item_value', $totalItemValue);
        $saleOrder->increment('total_tax_value', $totalTax);
        $saleOrder->increment('total_amount', $totalItemValue + $totalTax);
    }

    private function handleDeleteItem($saleOrder, $erpTripPlan, $item)
    {
        $soItem = ErpSoItem::where([
            'sale_order_id' => $saleOrder->id,
            'item_id'       => $item->id,
        ])->first();

        if(!$soItem) {
            return false;
        }

        ErpTripPlanDetail::where('order_id', $saleOrder->id)
            ->where('order_item_id', $soItem->id)
            ->where('trip_header_id', $erpTripPlan->id)
            ->delete();

        $soItem->custom_bom_details()->delete();
        $soItem->teds()->delete();
        $soItem->item_deliveries()->delete();
        $soItem->attributes()->delete();  
        
        // Use actual values from database, not from request
        $itemValue = (float) $soItem->order_qty * (float) $soItem->rate;
        $taxAmount = (float) $soItem->tax_amount;
        $totalItemAmount = (float) $soItem->total_item_amount;

        // Update Sale Order totals
        $saleOrder->decrement('total_item_value', $itemValue);
        $saleOrder->decrement('total_tax_value', $taxAmount);
        $saleOrder->decrement('total_amount', $totalItemAmount);

        // Delete SO Item
        $soItem->delete();   

    }


    /**
     * Apply tax calculations & persist TED records
     */
    private function applyTaxes($saleOrder, $soItem, $value, $taxDetails, &$totalTax)
    {
        $itemTax = 0;
        $totalDiscount = $soItem->item_discount_amount + $soItem->header_discount_amount;
        $assessmentAmount = $value - $totalDiscount;
        foreach ($taxDetails as $tax) {

            $taxAmount = ($tax['tax_percentage'] / 100 * $value);
            $itemTax  += $taxAmount;
            $totalTax += $tax['applicability_type'] === "collection" ? $taxAmount : -$taxAmount;

            ErpSaleOrderTed::updateOrCreate(
                [
                    'sale_order_id' => $saleOrder->id,
                    'so_item_id' => $soItem->id,
                    'ted_id' => $tax['id']
                ],
                [
                    'ted_name' => $tax['tax_type'],
                    'ted_percentage' => $tax['tax_percentage'],
                    'ted_amount' => $taxAmount,
                    'assessment_amount' => $assessmentAmount,
                ]
            );
        }

        return $itemTax;
    }

    /**
     * Finalize sale order with approval and payment terms
     */
    private function finalizeSaleOrder($saleOrder, $request)
    {
        $approval = Helper::approveDocument(
            $saleOrder->book_id,
            $saleOrder->id,
            $saleOrder->revision_number ?? 0,
            $saleOrder->remarks,
            $request->file('attachment'),
            $saleOrder->approval_level ?? 0,
            'approve',
            $saleOrder->total_amount,
            get_class($saleOrder)
        );

        $saleOrder->update([
            'document_status' => $approval['approvalStatus'] ?? $saleOrder->document_status,
        ]);

        SaleModuleHelper::updateOrCreateSoPaymentTerms(
            $saleOrder->id,
            $saleOrder->payment_term_id,
            $saleOrder->credit_days
        );
    }

    /**
     * Generic address creation
     */
    public static function createAddress($relation, $entity, $type)
    {
        $entity = (object) $entity;
        return $relation()->create([
            'address'     => $entity->address,
            'country_id'  => $entity->country_id,
            'state_id'    => $entity->state_id,
            'city_id'     => $entity->city_id,
            'type'        => $type,
            'pincode'     => $entity->pincode,
            'phone'       => $entity->phone ?? null,
            'fax_number'  => $entity->fax_number  ?? null,
        ]);
    }
}

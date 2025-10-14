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
use App\Models\Item;
use App\Models\ErpStore;
use App\Models\ErpTripPlanDetail;
use App\Models\ErpTripPlanHeader;

class SaleOrderService
{
    /**
     * Main method to create sale order
     */
    public function create($request, $user)
    {
        try {
            [$user, $erpIntegration, $store, $customer, $currencyExchange, $erpSaleOrder] = $this->validateAndSetup($request, $user);

            if ($erpSaleOrder) throw new Exception(ConstantHelper::DUPLICATE_DOCUMENT_NUMBER);

            DB::beginTransaction();

            $erpSaleOrder = $this->createSaleOrder($user, $erpIntegration, $store, $customer, $currencyExchange, $request);

            $saleOrder = $erpSaleOrder['erpSaleOrder'];
            $erpTripPlan = $erpSaleOrder['erpTripPlanHeader'];

            $this->assignAddresses($saleOrder, $store, $customer);

            $this->processItems($saleOrder, $erpTripPlan, $request->delivery_skus, $customer, $erpIntegration, $store);
            $this->finalizeSaleOrder($saleOrder, $request);

            DB::commit();

            return ['status' => true, 'message' => 'Sale Order created successfully', 'data' => $saleOrder];

        } catch (Exception $ex) {
            DB::rollBack();
            return ['status' => false, 'message' => $ex->getMessage()];
        }
    }

    /**
     * Main method to update sale order
     */
    public function update($request, $user)
    {
        try {
            [$user, $erpIntegration, $store, $customer, $currencyExchange, $erpSaleOrder] = $this->validateAndSetup($request, $user);
            if (!$erpSaleOrder) throw new Exception(ConstantHelper::DOCUMENT_NUMBER_NOT_FOUND);

            DB::beginTransaction();

            $erpTripPlan = ErpTripPlanHeader::where('book_id', $erpIntegration->trip_book_id)
                ->where('document_number', $request->document_number)
                ->first();

            $this->updateItems($erpSaleOrder, $erpTripPlan, $request->delivery_skus, $customer, $erpIntegration, $store);

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
        $erpIntegration = ErpExternalIntegration::with(['soBook:id,book_code', 'tripBook:id,book_code'])
            ->whereGroupId($request->user()->group_id)
            ->whereCompanyId($request->user()->company_id)
            ->whereOrganizationId($request->organization_id)
            ->whereStoreId($request->store_id)
            ->first();

        if (!$erpIntegration) throw new Exception('Integration mapping not found.');

        $store = ErpStore::with('address')->find($request->store_id);

        if (!$store || !$store->address) throw new Exception('Store address not assigned.');

        $customer = Customer::find($erpIntegration->customer_id);
        if (!$customer) throw new Exception('No customer found.');


        $currencyExchange = CurrencyHelper::getCurrencyExchangeRates($customer->currency_id, $request->document_date);
        if (!$currencyExchange['status']) throw new Exception($currencyExchange['message']);

        $erpSaleOrder = ErpSaleOrder::with('customer')
            ->where('book_id', $erpIntegration->so_book_id)
            ->where('document_number', $request->document_number)
            ->first();

        return [$user, $erpIntegration, $store, $customer, $currencyExchange, $erpSaleOrder];
    }

    /**
     * Create Sale Order record
     */
    private function createSaleOrder($user, $erpIntegration, $store, $customer, $currencyExchange, $request)
    {
        $orderData = [
            'organization_id'    => $user?->organization_id,
            'group_id'           => $user?->group_id,
            'company_id'         => $user?->company_id,
            'book_id'            => $erpIntegration->so_book_id,
            'book_code'          => $erpIntegration->soBook?->book_code,
            'document_type'      => ConstantHelper::SO_SERVICE_ALIAS,
            'document_number'    => $request->document_number,
            'document_date'      => $request->document_date,
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
            'payment_term_code'  => $customer->paymentTerm?->alias,
            'credit_days'        => $customer->credit_days ?? 0,
            'document_status'    => ConstantHelper::APPROVAL_NOT_REQUIRED,
            'remarks'            => 'Order pushed through API',
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

        $erpSaleOrder = ErpSaleOrder::create($orderData);

        $erpTripPlanHeader = ErpTripPlanHeader::create(array_merge(
            $orderData,
            [
                'book_id'   => $erpIntegration->trip_book_id,
                'book_code' => $erpIntegration->tripBook?->book_code,
            ]
        ));

        return [
            'erpSaleOrder' => $erpSaleOrder,
            'erpTripPlanHeader' => $erpTripPlanHeader,
        ];
    }

    /**
     * Assign Billing, Shipping, and Location addresses
     */
    private function assignAddresses($saleOrder, $store, $customer)
    {
        $addresses = $customer->addresses()
            ->whereIn('type', ['billing', 'shipping', 'both'])
            ->get();

        $billing  = $addresses->firstWhere(fn($a) => in_array($a->type, ['billing', 'both']));
        $shipping = $addresses->firstWhere(fn($a) => in_array($a->type, ['shipping', 'both']));

        if (!$billing || !$shipping) throw new Exception("Customer's billing or shipping address not found.");

        $billingAddress  = self::createAddress([$saleOrder, 'billing_address_details'], $billing, 'billing');
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
    private function processItems($saleOrder, $erpTripPlan, $skus, $customer, $erpIntegration, $store)
    {
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
            if (empty($bom['bom_id'])) {
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

            // Apply taxes and accumulate total tax
            $itemTax = $this->applyTaxes($saleOrder, $item, $value, $taxDetails, $totalTax);

            // Manage Consignee & Consignee address
            $consigneeAddress = $this->manageConsigneeAddress($sku['consignee_id'], $sku['consignee_address'] ?? []);

            // Build common item data for reuse
            $itemsData = [
                'sale_order_id'             => $saleOrder->id,
                'consignee_id'              => $sku['consignee_id'],
                'sale_type'                 => $sku['sale_type'],
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
                'tax_amount'                => $itemTax,
                'total_item_amount'         => $value + $itemTax,
                'remarks'                   => 'Order pushed by API',
            ];

            // Create Sale Order Item
            $soItem = ErpSoItem::create($itemsData);

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
                ]
            );

            // Create Trip Plan Detail record
            ErpTripPlanDetail::create($tripPlanData);
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
    private function updateItems($saleOrder, $erpTripPlan, $skus, $customer, $erpIntegration, $store)
    {
        // Ensure SKUs exist
        if (empty($skus)) {
            throw new Exception("SKU not found.");
        }

        $totalItemValue = 0;
        $totalTax       = 0;

        foreach ($skus as $sku) {
            $action = $sku['action_type'];

            // Fetch Item record
            $item = Item::find($sku['item_id']);
            if (!$item) {
                throw new Exception("No item found with item code: {$sku['item_code']}");
            }

            // For delete, we only need to remove
            if ($action === 'delete') {
                $this->handleDeleteItem($saleOrder, $erpTripPlan, $item);
                continue;
            }

            // Calculate base values
            $qty   = (float) $sku['item_qty'];
            $rate  = (float) $sku['item_rate'];
            $value = $qty * $rate;
            $totalItemValue += $value;

            // Validate BOM existence
            $bom = ItemHelper::checkItemBomExists($item->id, []);
            if (empty($bom['bom_id'])) {
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

            // Apply taxes and accumulate total tax
            $itemTax = $this->applyTaxes($saleOrder, $item, $value, $taxDetails, $totalTax);

            // Manage Consignee & Consignee address
            $consigneeAddress = $this->manageConsigneeAddress($sku['consignee_id'], $sku['consignee_address'] ?? []);

            // Build common item data for reuse
            $itemsData = [
                'sale_order_id'             => $saleOrder->id,
                'consignee_id'              => $sku['consignee_id'],
                'sale_type'                 => $sku['sale_type'],
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
                'tax_amount'                => $itemTax,
                'total_item_amount'         => $value + $itemTax,
                'remarks'                   => 'Order pushed by API',
            ];

            // Handle ErpSoItem Add or Update
            if ($action === 'add') {
                $soItem = ErpSoItem::create($itemsData);
            } elseif ($action === 'update') {
                $soItem = ErpSoItem::updateOrCreate(
                    [
                        'sale_order_id' => $saleOrder->id,
                        'consignee_id'  => $sku['consignee_id'],
                        'item_id'       => $item->id,
                    ],
                    $itemsData
                );
            }

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
                ]
            );

            // Create Trip Plan Detail record
            ErpTripPlanDetail::updateOrCreate(
                [
                    'order_id'      => $saleOrder->id,
                    'order_item_id' => $soItem->id,
                    'trip_header_id' => $erpTripPlan->id,
                ],
                $tripPlanData
            );
        }

        // Update Sale Order totals
        $saleOrder->update([
            'total_item_value' => $totalItemValue,
            'total_tax_value'  => $totalTax,
            'total_amount'     => $totalItemValue + $totalTax,
        ]);
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
        $soItem->delete();

    }


    /**
     * Apply tax calculations & persist TED records
     */
    private function applyTaxes($saleOrder, $item, $value, $taxDetails, &$totalTax)
    {
        $itemTax = 0;
        foreach ($taxDetails as $tax) {

            $taxAmount = ($tax['tax_percentage'] / 100 * $value);
            $itemTax  += $taxAmount;
            $totalTax += $tax['applicability_type'] === "collection" ? $taxAmount : -$taxAmount;

            ErpSaleOrderTed::updateOrCreate(
                [
                    'sale_order_id' => $saleOrder->id,
                    'so_item_id' => $item->id,
                    'ted_id' => $tax['id']
                ],
                [
                    'ted_name' => $tax['tax_type'],
                    'ted_percentage' => $tax['tax_percentage'],
                    'ted_amount' => $taxAmount
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
        return $relation()->create([
            'address'     => $entity->address,
            'country_id'  => $entity->country_id,
            'state_id'    => $entity->state_id,
            'city_id'     => $entity->city_id,
            'type'        => $type,
            'pincode'     => $entity->pincode,
            'phone'       => $entity->phone,
            'fax_number'  => $entity->fax_number,
        ]);
    }
}

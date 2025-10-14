<?php

namespace App\Services\Integration;

use App\Helpers\CommonHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Helpers\CurrencyHelper;
use App\Helpers\TaxHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\Inventory\StockReservation;
use App\Helpers\InventoryHelper;
use App\Helpers\ItemHelper;
use App\Lib\Services\WHM\DispatchJob;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\ERP\ErpExternalIntegration;
use App\Models\ERP\StockStoreMapping;
use App\Models\ErpInvoiceItem;
use App\Models\ErpInvoiceItemAttribute;
use App\Models\ErpInvoiceItemLocation;
use App\Models\ErpSaleInvoice;
use App\Models\ErpSaleInvoiceTed;
use App\Models\Item;
use App\Models\ErpStore;

class DeliveryNoteService
{
    /**
     * Main method to create sale order
     */
    public function create($request, $user)
    {
        try {
            [$user, $erpIntegration, $store, $stockType, $customer, $currencyExchange, $saleInvoice, $config] = $this->validateAndSetup($request, $user);
            
            if ($saleInvoice) throw new Exception(ConstantHelper::DUPLICATE_DOCUMENT_NUMBER);

            DB::beginTransaction();

            $saleInvoice = $this->createSaleInvoice($user, $erpIntegration, $store, $stockType->sub_store_id, $customer, $currencyExchange, $request);

            $this->assignAddresses($saleInvoice, $store, $customer);

            $this->processItems($saleInvoice, $request->items, $store, $stockType->sub_store_id);
            $this->finalizeSaleInvoice($saleInvoice, $request);
            $this->maintainStockLedger($saleInvoice, $config);
            $this->createDipatchJob($saleInvoice->id, $config);

            DB::commit();

            return ['status' => true, 'message' => 'Delivery note created successfully.', 'data' => $saleInvoice];

        } catch (Exception $ex) {
            DB::rollBack();
            return ['status' => false, 'message' => $ex->getMessage()];
        }
    }

    /**
     * Validate request and prepare setup entities
     */
    private function validateAndSetup($request, $user)
    {
        $erpIntegration = ErpExternalIntegration::with(['dnoteBook:id,book_code'])
            ->whereGroupId($request->user()->group_id)
            ->whereCompanyId($request->user()->company_id)
            ->whereOrganizationId($request->organization_id)
            ->whereStoreId($request->store_id)
            ->first();

        if (!$erpIntegration) throw new Exception('Integration mapping not found.');

        if (!$erpIntegration->dnote_book_id) throw new Exception('Book not found.');

        $store = ErpStore::with('address')->find($request->store_id);

        if (!$store || !$store->address) throw new Exception('Store address not assigned.');

        $stockType = StockStoreMapping::where('stock_type',$request->stock_type)
                ->where('is_primary','1')
                ->first();

        if (!$stockType) throw new Exception("Sub store not mapped with $request->stock_type.");

        $customer = Customer::find($erpIntegration->customer_id);
        if (!$customer) throw new Exception('No customer found.');


        $currencyExchange = CurrencyHelper::getCurrencyExchangeRates($customer->currency_id, $request->document_date);
        if (!$currencyExchange['status']) throw new Exception($currencyExchange['message']);

        $saleInvoice = ErpSaleInvoice::with('customer')
            ->where('book_id', $erpIntegration->dnote_book_id)
            ->where('document_number', $request->document_number)
            ->first();

        $config = Configuration::where('type','organization')
            ->where('type_id', $request->organization_id)
            ->where('config_key', CommonHelper::ENFORCE_UIC_SCANNING)
            ->first();

        return [$user, $erpIntegration, $store, $stockType, $customer, $currencyExchange, $saleInvoice, $config];
    }

    /**
     * Create Sale Invoice record
     */
    private function createSaleInvoice($user, $erpIntegration, $store, $subStoreId, $customer, $currencyExchange, $request)
    {
        $numberPatternData = Helper::generateDocumentNumberNew($erpIntegration->dnote_book_id, $request->document_date);
        if (!isset($numberPatternData)) {
            throw new Exception("Invalid Book.");
        }

        $invoiceData = [
            'organization_id'       => $user?->organization_id,
            'group_id'              => $user?->group_id,
            'company_id'            => $user?->company_id,
            'book_id'               => $erpIntegration->dnote_book_id,
            'book_code'             => $erpIntegration->dnoteBook?->book_code,
            'document_type'         => ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS,
            'document_number'       => $request->document_number,
            'doc_no'                => $numberPatternData['doc_no'],
            'document_date'         => $request->document_date ? date('Y-m-d', strtotime($request->document_date)) : NULL,
            'store_id'              => $store->id,
            'store_code'            => $store->store_name,
            'sub_store_id'          => $subStoreId,
            'customer_id'           => $customer->id,
            'customer_code'         => $customer->customer_code,
            'customer_email'        => $customer->email,
            'customer_phone_no'     => $customer->mobile,
            'customer_gstin'        => $customer->compliances?->gstin_no,
            'currency_id'           => $customer->currency_id,
            'currency_code'         => $customer->currency?->short_name,
            'payment_term_id'       => $customer->payment_terms_id,
            'payment_term_code'     => $customer->paymentTerm?->alias,
            'credit_days'           => $customer->credit_days ?? 0,
            'document_status'       => ConstantHelper::APPROVAL_NOT_REQUIRED,
            'remarks'               => 'Dnote pushed through API',
            'org_currency_id'       => $currencyExchange['data']['org_currency_id'],
            'org_currency_code'     => $currencyExchange['data']['org_currency_code'],
            'org_currency_exg_rate' => $currencyExchange['data']['org_currency_exg_rate'],
            'comp_currency_id'      => $currencyExchange['data']['comp_currency_id'],
            'comp_currency_code'    => $currencyExchange['data']['comp_currency_code'],
            'comp_currency_exg_rate'=> $currencyExchange['data']['comp_currency_exg_rate'],
            'group_currency_id'     => $currencyExchange['data']['group_currency_id'],
            'group_currency_code'   => $currencyExchange['data']['group_currency_code'],
            'group_currency_exg_rate'=> $currencyExchange['data']['group_currency_exg_rate'],
            'transportation_mode'  => $request['transport_detail']['transport_mode'] ?? null,
            'transporter_name'     => $request['transport_detail']['transporter_name'] ?? null,
            'vehicle_no'           => $request['transport_detail']['vehicle_number'] ?? null,
        ];

        $erpSaleInvoice = ErpSaleInvoice::create($invoiceData);

        return $erpSaleInvoice;
    }

    /**
     * Assign Billing, Shipping, and Location addresses
     */
    private function assignAddresses($saleInvoice, $store, $customer)
    {
        $addresses = $customer->addresses()
            ->whereIn('type', ['billing', 'shipping', 'both'])
            ->get();

        $billing  = $addresses->firstWhere(fn($a) => in_array($a->type, ['billing', 'both']));
        $shipping = $addresses->firstWhere(fn($a) => in_array($a->type, ['shipping', 'both']));

        if (!$billing || !$shipping) throw new Exception("Customer's billing or shipping address not found.");

        $billingAddress  = self::createAddress([$saleInvoice, 'billing_address_details'], $billing, 'billing');
        $shippingAddress = self::createAddress([$saleInvoice, 'shipping_address_details'], $shipping, 'shipping');
        self::createAddress([$saleInvoice, 'location_address_details'], $store->address, 'location');


        $saleInvoice->update([
            'billing_address'  => $billingAddress?->id,
            'shipping_address' => $shippingAddress?->id,
        ]);
    }


    /**
     *  Handle items (validation, tax, attributes)
     * Process SKUs for a Sale Invoice and create related records.
     *
     * @param  SaleInvoice            $saleInvoice
     * @param  array                  $skus
     * @param  ErpStore               $store
     * @throws Exception              $ex
     */
    private function processItems($saleInvoice, $skus, $store, $subStoreId)
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

            // Calculate base values
            $qty   = (float) $sku['item_qty'];
            $rate  = (float) $sku['item_rate'];
            $value = $qty * $rate;
            $totalItemValue += $value;

            // Calculate applicable taxes (only if tax rules apply)
            $taxDetails = TaxHelper::calculateTax(
                $item->hsn_id,
                $rate,
                $store->address?->country_id,
                $store->address?->state_id,
                $saleInvoice->billing_address_details?->country_id,
                $saleInvoice->billing_address_details?->state_id,
                'sale'
            );

            // Build common item data for reuse
            $itemsData = [
                'sale_invoice_id'           => $saleInvoice->id,
                'store_id'                  => $store->id,
                'sub_store_id'              => $subStoreId,
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
                'remarks'                   => 'Order pushed by API',
            ];

            // Create Sale Invoice Item
            $invoiceItem = ErpInvoiceItem::create($itemsData);

            // Apply taxes and accumulate total tax
            $itemTax = $this->applyTaxes($saleInvoice, $invoiceItem, $value, $taxDetails, $totalTax);
            $invoiceItem->tax_amount = $itemTax;
            $invoiceItem->total_item_amount = $value + $itemTax; 
            $invoiceItem->save();

            // Attach item attributes if provided
            foreach ($sku['item_attributes'] ?? [] as $attribute) {
                ErpInvoiceItemAttribute::updateOrCreate(
                    [
                        'sale_invoice_id'     => $saleInvoice->id,
                        'invoice_item_id'        => $invoiceItem->id,
                        'item_attribute_id' => $attribute['id'],
                    ],
                    [
                        'item_code'       => $invoiceItem->item_code,
                        'attribute_name'  => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                        'attr_name'       => $attribute['attribute_name_id'],
                        'attr_value'      => $attribute['attribute_value_id'],
                    ]
                );
            }
           
        }

        // Update Sale Order totals
        $saleInvoice->update([
            'total_item_value' => $totalItemValue,
            'total_tax_value'  => $totalTax,
            'total_amount'     => $totalItemValue + $totalTax,
        ]);
    }

    /**
     * Apply tax calculations & persist TED records
     */
    private function applyTaxes($saleInvoice, $item, $value, $taxDetails, &$totalTax)
    {
        $itemTax = 0;
        foreach ($taxDetails as $tax) {

            $taxAmount = ($tax['tax_percentage'] / 100 * $value);
            $itemTax  += $taxAmount;
            $totalTax += $tax['applicability_type'] === "collection" ? $taxAmount : -$taxAmount;

            ErpSaleInvoiceTed::updateOrCreate(
                [
                    'sale_invoice_id' => $saleInvoice->id,
                    'invoice_item_id' => $item->id,
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
     * Finalize sale invoice with approval and payment terms
     */
    private function finalizeSaleInvoice($saleInvoice, $request)
    {
        $approval = Helper::approveDocument(
            $saleInvoice->book_id,
            $saleInvoice->id,
            $saleInvoice->revision_number ?? 0,
            $saleInvoice->remarks,
            NULL,
            $saleInvoice->approval_level ?? 0,
            'approve',
            $saleInvoice->total_amount,
            get_class($saleInvoice)
        );

        $saleInvoice->update([
            'document_status' => $approval['approvalStatus'] ?? $saleInvoice->document_status,
        ]);
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

     /**
     * Maintain stock ledger
     */
    private static function maintainStockLedger($saleInvoice, $config)
    {
        $detailIds = $saleInvoice->items->pluck('id')->toArray();

        if ($config && strtolower($config->config_value) === 'yes'){
            $stockReservation = StockReservation::stockReservation($saleInvoice->document_type, $saleInvoice->id, $saleInvoice->items);
            if ($stockReservation['status'] == 'error') {
                throw new Exception($stockReservation['message']);
            }
            return "";
        }

        $issueRecords = InventoryHelper::settlementOfInventoryAndStock($saleInvoice->id, $detailIds, $saleInvoice->document_type, $saleInvoice->document_status, 'issue');
        if($issueRecords['status'] == 'error'){
            throw new Exception($issueRecords['message']);
        }

        return null;
    }

    /**
     * Maintain stock ledger
     */
    private static function createDipatchJob($saleInvoiceId, $config)
    {
        if($config && strtolower($config->config_value) === 'yes'){
            (new DispatchJob)->createJob($saleInvoiceId, ErpSaleInvoice::class);
        }
 
    }
}

<?php
namespace App\Helpers\ReManufacturing\RepairOrder;

use App\Helpers\ConstantHelper;
use App\Helpers\ServiceParametersHelper;
use App\Models\Book;
use App\Models\ErpRgrItem;
use App\Models\Item;
use App\Models\ErpRgr;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;
use App\Helpers\Helper as MainHelper;
use App\Lib\Services\WHM\RepairOrderJob;
use App\Models\ErpRgrStoreMapping;
use App\Models\ErpRepairOrder;
use App\Models\ErpRepItem;
use App\Models\ErpRepItemAttribute;
use App\Models\ItemAttribute;
use App\Models\AttributeGroup;
use App\Models\Attribute;
use App\Models\Organization;
use Carbon\Carbon;
use App\Helpers\Common\OrganizationHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\InventoryHelper;
use Illuminate\Validation\ValidationException;
use App\Helpers\ReManufacturing\RepairOrder\Constants as RepConstant;

//Repair Order General Helper
class Helper
{
    //Ok To Receive Items from RGR Item
    public static function generateRepFromRgrItem(ErpItemUniqueCode $rgrItemUniqueCode, string $repairOrderType, $authUser, $createJob = false) : array
    {
        //RGR Item Segregation
        $rgrSegregation = $rgrItemUniqueCode -> segregation;
        if (!$rgrSegregation) {
            return [
                'status' => 'error',
                'message' => 'RGR Item segregation not found'
            ];
        }

        $job = ErpWhmJob::where('id', $rgrItemUniqueCode->job_id)
            ->where('morphable_type', ErpRgr::class)
            ->first();

        if (!$job) {
            return [
                'status' => 'error',
                'message' => 'Job not found'
            ];
        }

        $rgrHeader = $job->morphable;

        //Check RGR Header
        if (!$rgrHeader) {
            return [
                'status' => 'error',
                'message' => 'RGR Header reference not found'
            ];
        }
        //Check RGR Book Param to get Ok To Receive Book
        $okToReceiveParam = ServiceParametersHelper::getBookLevelParameterValue($repairOrderType, $rgrHeader -> book_id);
        if ($okToReceiveParam['status'] == false || count($okToReceiveParam['data']) == 0) {
            return [
                'status' => 'error',
                'message' => 'Ok to receive Book Param not specified'
            ];
        }
        //Get the Repair Order books
        $okToReceiveParamValue = $okToReceiveParam['data'][0];
        $okToReceiveRepBook = Book::find($okToReceiveParamValue);
        if (!$okToReceiveRepBook) {
            return [
                'status' => 'error',
                'message' => 'Repair Order Book for Ok to receive items not found'
            ];
        }
        //Setup Header Details
        //Setup Book Details
        $documentDate = $rgrHeader -> document_date;
        $documentNoDetails = MainHelper::generateDocumentNumberNew($okToReceiveRepBook -> id, $documentDate);
        if (!($documentNoDetails)) {
            return [
                'status' => 'error',
                'message' => 'Series numbering pattern not specified'
            ];
        }
        //Any other error from Doc generation
        if ($documentNoDetails['error']) {
            return [
                'status' => 'error',
                'message' => $documentNoDetails['error']
            ];
        }
        //Check if the series has manual number pattern
        if ($documentNoDetails['type'] !== ConstantHelper::DOC_NO_TYPE_AUTO) {
            return [
                'status' => 'error',
                'message' => 'Series numbering pattern should be set to Auto'
            ];
        }
        //Get Auth details
        $organization = Organization::find($authUser -> organization_id);
        if (!$organization) {
            return [
                'status' => 'error',
                'message' => 'Organization Not Found'
            ];
        }
        $groupId = $organization -> group_id;
        $companyId = $organization -> company_id;
        

        // Get RGR Item from morphable_id
        $rgrItem = ErpRepItem::find($rgrItemUniqueCode->morphable_id);
        
        $categoryId = $rgrItem->category_id ?? $rgrItemUniqueCode->item->subcategory_id ?? null;

        $qcSubStoreId = null;
        $qcSubStoreName = null;

        if ($rgrHeader->store_id && $categoryId) {
            $storeMapping = ErpRgrStoreMapping::where('store_id', $rgrHeader->store_id)
                ->where('category_id', $categoryId)
                ->first();

            if (!$storeMapping || !$storeMapping->qc_sub_store_id) {
                return [
                    'status' => 'error',
                    'message' => 'QC Sub Store is not mapped for the selected Store and Category. Please set up the mapping first.'
                ];
            }

            $qcSubStoreId = $storeMapping->qc_sub_store_id;
            $qcSubStoreName = $storeMapping->qcSubStore?->name; 
        }

        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $currencyExchangeData = CurrencyHelper::getCurrencyExchangeRates($organization ->currency_id, $documentDate);
        if ($currencyExchangeData['status'] == false) {
            return [
                'status'  => 'error',
                'message' => $currencyExchangeData['message'] ?? 'Currency exchange rate data not found'
            ];
        }
        //Insert data into Main Header Table
        $repairOrder = ErpRepairOrder::create([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'organization_id' => $organization -> id,
            'book_id' => $okToReceiveRepBook -> id,
            'book_code' => $okToReceiveRepBook -> book_code,
            'store_id' => $rgrHeader -> store_id,
            'store_name' => $rgrHeader -> store_name,
            'rgr_sub_store_id' => $rgrItemUniqueCode -> sub_store_id,
            'qc_sub_store_id' => $qcSubStoreId,  
            'vendor_id' => null,
            'type' => null,
            'defect_status' => $rgrSegregation ->defect_severity,
            'doc_number_type' => $documentNoDetails['type'],
            'doc_reset_pattern' => $documentNoDetails['reset_pattern'],
            'doc_prefix' => $documentNoDetails['prefix'],
            'doc_suffix' => $documentNoDetails['suffix'],
            'doc_no' => $documentNoDetails['doc_no'],
            'document_number' => $documentNoDetails['document_number'],
            'document_date' => $documentDate,
            'revision_number' => 0,
            'revision_date' => null,
            'document_status' => 'pending',
            'approval_level' => 1,
            'rgr_id' => $rgrHeader -> id,
                // currency columns
            'org_currency_id' => $currencyExchangeData['data']['org_currency_id'],
            'org_currency_code' => $currencyExchangeData['data']['org_currency_code'],
            'org_currency_exg_rate' => $currencyExchangeData['data']['org_currency_exg_rate'],
            'comp_currency_id' => $currencyExchangeData['data']['comp_currency_id'],
            'comp_currency_code' => $currencyExchangeData['data']['comp_currency_code'],
            'comp_currency_exg_rate' => $currencyExchangeData['data']['comp_currency_exg_rate'],
            'group_currency_id' => $currencyExchangeData['data']['group_currency_id'],
            'group_currency_code' => $currencyExchangeData['data']['group_currency_code'],
            'group_currency_exg_rate' => $currencyExchangeData['data']['group_currency_exg_rate'],
            'remarks' => null,
        ]);
        //Now Item Details

        $unitId=$rgrItemUniqueCode ->item-> uom_id;
        $unitName=$rgrItemUniqueCode ->item->uom->name;

        $repItem = ErpRepItem::create([
            'repair_order_id' => $repairOrder -> id,
            'rgr_item_id' => $rgrItemUniqueCode -> morphable_id,
            'rgr_job_detail_id' => $rgrSegregation -> job_item_id,
            'rgr_item_segregation_id' => $rgrSegregation -> id,
            'item_id' => $rgrItemUniqueCode -> item_id,
            'item_code' => $rgrItemUniqueCode -> item_code,
            'item_name' => $rgrItemUniqueCode -> item_name,
            'item_uid' => $rgrItemUniqueCode -> item_uid,
            'uom_id' => $unitId,
            'uom_code' => $unitName,
            'qty' => $rgrItemUniqueCode -> qty,
            'inventory_uom_id' => $unitId,
            'inventory_uom_code' => $unitName,
            'inventory_uom_qty' => $rgrItemUniqueCode -> qty,
            'service_item_id' => null,
            'service_item_code' => null,
            'service_item_name' => null,
            'rgr_sub_store_id' => $rgrItemUniqueCode -> sub_store_id,
            'rgr_sub_store_name' => $rgrItemUniqueCode ?-> subStore ?-> name,
            'qc_sub_store_id' => $qcSubStoreId,    
            'qc_sub_store_name' => $qcSubStoreName,
            'rejuvenate_item_id' => null,
            'rejuvenate_item_code' => null,
            'rejuvenate_item_name' => null,
            'rejuvenate_item_attributes' => null,
            'repair_remarks' => null,
        ]);

       // Validate and Attach Attributes
        $rawAttributes = is_string($rgrItemUniqueCode->item_attributes) 
                            ? json_decode($rgrItemUniqueCode->item_attributes, true) 
                            : $rgrItemUniqueCode->item_attributes;

        $validatedAttributes = self::validateItemAttributes($rawAttributes ?? [], $rgrItemUniqueCode->item_id, true);
       
        foreach ($validatedAttributes as $attr) {
            ErpRepItemAttribute::create([
                'repair_order_id' => $repairOrder->id,
                'rep_item_id' => $repItem->id,
                'item_attribute_id' => $attr['item_attribute_id'] ?? 0,
                'item_code' => $rgrItemUniqueCode->item_code,
                'attribute_name' => $attr['attribute_name'],
                'attr_name' => $attr['attr_name'],
                'attribute_value' => $attr['attribute_value'],
                'attr_value' => $attr['attr_value'],
            ]);
        }

        $repItemIds = $repItem->pluck('id')->toArray();

        $issueRecords = InventoryHelper::settlementOfInventoryAndStock($repairOrder->id, $repItemIds, RepConstant::SERVICE_ALIAS, $repairOrder->document_status, 'receipt');

        if($issueRecords['status'] == 'error'){
            return [
                'status' => 'error',
                'message' => $issueRecords['message'],
            ];
        }
        //If Job is not required return success
        if (!$createJob) {
            return [
                'status' => 'success',
                'message' => 'Repair Order generated successfully'
            ];
        }
        //Create Job
        $repJob = new RepairOrderJob();
        $repJob -> createJob($repairOrder->id,'App\Models\ErpRepairOrder');

        return [
            'status' => 'success',
            'message' => 'Repair Order generated successfully'
        ];
    }

   public static function validateItemAttributes(array $attributes, int $itemId, bool $returnItemAttributeId = false): array
    {
        $item = Item::where('id', $itemId)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->firstOrFail();

        $itemAttributes = ItemAttribute::where('item_id', $itemId)->get();
        $attributeGroups = AttributeGroup::pluck('id', 'name'); 
        $allAttributes = Attribute::all()->groupBy('attribute_group_id');

        $formatted = [];

        foreach ($attributes as $attr) {
            $groupName = $attr['attribute_name'] ?? '';
            $value     = $attr['attribute_value'] ?? null;

            if (!$groupName || $value === null) continue;

            if (!isset($attributeGroups[$groupName])) {
                throw ValidationException::withMessages([
                    'attribute_group' => ["Attribute group '{$groupName}' does not exist."]
                ]);
            }
            $groupId = $attributeGroups[$groupName];

            $matchingAttr = ($allAttributes[$groupId] ?? collect())
                                ->first(fn($a) => $a->value === $value);

            if (!$matchingAttr) {
                throw ValidationException::withMessages([
                    'item_attribute' => ["Invalid value '{$value}' for attribute group '{$groupName}'."]
                ]);
            }

            // Find the matching ItemAttribute for this item
            $itemAttribute = $itemAttributes
                ->first(fn($ia) => 
                    $ia->attribute_group_id == $groupId &&
                    (
                        (is_array($ia->attribute_id) && in_array($matchingAttr->id, $ia->attribute_id)) ||
                        $ia->attribute_id == $matchingAttr->id ||
                        (is_string($ia->attribute_id) && in_array($matchingAttr->id, json_decode($ia->attribute_id, true) ?? []))
                    )
                );

            if (!$itemAttribute) {
                throw ValidationException::withMessages([
                    'item_attribute' => ["Item does not have this attribute '{$value}' in group '{$groupName}'."]
                ]);
            }

            $formattedItem = [
                'attribute_name'  => $groupName,
                'attr_name'       => $groupId,
                'attribute_value' => $matchingAttr->value,
                'attr_value'      => $matchingAttr->id,
            ];

            if ($returnItemAttributeId) {
                $formattedItem['item_attribute_id'] = $itemAttribute->id;
            }

            $formatted[] = $formattedItem;
        }

        return $formatted;
    }

}
<?php

namespace App\Helpers\ReManufacturing\RCA;

use App\Helpers\ConstantHelper;
use App\Helpers\ServiceParametersHelper;
use App\Models\Book;
use App\Models\ErpRgr;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;
use App\Helpers\Helper as MainHelper;
use App\Lib\Services\WHM\RepairOrderJob;
use App\Models\ErpRcaHeader;
use App\Models\ErpPickupSchedule;
use App\Models\ErpRcaItem;
use App\Models\ErpRcaItemAttribute;
use App\Models\Item;
use App\Models\Organization;
use Illuminate\Support\Collection;
use App\Helpers\ReManufacturing\RepairOrder\Helper as RepHelper;

class MultiItemHelper
{
    /**
     * Generate RCA for multiple items at once (Missing & Extra Items)
     */
    public static function generateRcaFromRgrItems(Collection $rgrItemUniqueCodes, string $bookParam, $authUser, $createJob = false): array
    {
        if ($rgrItemUniqueCodes->isEmpty()) {
            return ['status' => 'error', 'message' => 'No items provided for RCA generation'];
        }

        $firstItem = $rgrItemUniqueCodes->first();
        $job = ErpWhmJob::where('id', $firstItem->job_id)
            ->where('morphable_type', ErpRgr::class)
            ->first();

        if (!$job) {
            return ['status' => 'error', 'message' => 'Job not found'];
        }

        $rgrHeader = $job->morphable;
        if (!$rgrHeader) {
            return ['status' => 'error', 'message' => 'RGR Header reference not found'];
        }

        $rcaParam = ServiceParametersHelper::getBookLevelParameterValue($bookParam, $rgrHeader->book_id);
        if (!$rcaParam['status'] || count($rcaParam['data']) == 0) {
            return ['status' => 'error', 'message' => 'RCA Book Param not specified'];
        }

        $rcaBook = Book::find($rcaParam['data'][0]);
        if (!$rcaBook) {
            return ['status' => 'error', 'message' => 'RCA Book not found'];
        }

        $documentDate = $rgrHeader->document_date;
        $documentNoDetails = MainHelper::generateDocumentNumberNew($rcaBook->id, $documentDate);
        if (!$documentNoDetails || !empty($documentNoDetails['error'])) {
            return ['status' => 'error', 'message' => $documentNoDetails['error'] ?? 'Series numbering pattern not specified'];
        }
        if ($documentNoDetails['type'] !== ConstantHelper::DOC_NO_TYPE_AUTO) {
            return ['status' => 'error', 'message' => 'Series numbering pattern should be set to Auto'];
        }

        $organization = Organization::find($authUser->organization_id);
        if (!$organization) {
            return ['status' => 'error', 'message' => 'Organization Not Found'];
        }

        $groupId = $organization->group_id;
        $companyId = $organization->company_id;

        $pickupSchedule = $rgrHeader->pickup_schdule_id ? ErpPickupSchedule::find($rgrHeader->pickup_schdule_id) : null;

        // Create RCA Header (single for all items)
        $rcaHeader = ErpRcaHeader::create([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'organization_id' => $organization->id,
            'book_id' => $rcaBook->id,
            'book_code' => $rcaBook->book_code,
            'store_id' => $rgrHeader->store_id,
            'store_name' => $rgrHeader->store_name,
            'rgr_id' => $rgrHeader->id,
            'discrepancy_type' => 'Missing & Extra Items',
            'unloading_date' => $pickupSchedule->document_date ?? null,
            'trip_no' => $rgrHeader->trip_no ?? null,
            'vehicle_no' => $rgrHeader->vehicle_no ?? null,
            'champ_name' => $rgrHeader->champ_name ?? null,
            'items_count' => $rgrItemUniqueCodes->count(),
            'doc_number_type' => $documentNoDetails['type'],
            'doc_reset_pattern' => $documentNoDetails['reset_pattern'],
            'doc_prefix' => $documentNoDetails['prefix'],
            'doc_suffix' => $documentNoDetails['suffix'],
            'doc_no' => $documentNoDetails['doc_no'],
            'document_number' => $documentNoDetails['document_number'],
            'document_date' => $documentDate,
            'fur_id' => $pickupSchedule->fur_id ?? null,
            'document_status' => ConstantHelper::APPROVAL_NOT_REQUIRED,
            'revision_number' => 0,
            'revision_date' => null,
            'approval_level' => 1,
            'remarks' => null,
            'created_by' => $authUser->id,
        ]);

        // Create RCA Items & Attributes for each unique code
        foreach ($rgrItemUniqueCodes as $rgrItemUniqueCode) {
            $unitId = $rgrItemUniqueCode->item->uom_id;
            $unitName = $rgrItemUniqueCode->item->uom->name;

            $rcaItem = ErpRcaItem::create([
                'rca_header_id' => $rcaHeader->id,
                'rgr_job_detail_id' => $rgrItemUniqueCode->segregation?->job_item_id ?? null,
                'rgr_item_segregation_id' => $rgrItemUniqueCode->segregation?->id ?? null,
                'item_id' => $rgrItemUniqueCode->item_id,
                'item_code' => $rgrItemUniqueCode->item_code,
                'item_name' => $rgrItemUniqueCode->item_name,
                'item_uid' => $rgrItemUniqueCode->item_uid,
                'uom_id' => $unitId,
                'uom_code' => $unitName,
                'inventory_uom_id' => $unitId,
                'inventory_uom_code' => $unitName,
                'inventory_uom_qty' => $rgrItemUniqueCode->qty,
                'scheduled_qty' => $rgrItemUniqueCode->qty,
                'missing_qty' => 0,
            ]);

            $attributes = is_string($rgrItemUniqueCode->item_attributes) 
                ? json_decode($rgrItemUniqueCode->item_attributes, true) 
                : $rgrItemUniqueCode->item_attributes;

            $validatedAttributes = RepHelper::validateItemAttributes($attributes, $rgrItemUniqueCode->item_id, true);

            foreach ($validatedAttributes as $attr) {
                ErpRcaItemAttribute::create([
                    'rca_header_id' => $rcaHeader->id,
                    'rca_item_id' => $rcaItem->id,
                    'item_attribute_id' => $attr['item_attribute_id'] ?? 0,
                    'item_code' => $rgrItemUniqueCode->item_code,
                    'attribute_name' => $attr['attribute_name'] ?? null,
                    'attr_name' => $attr['attr_name'] ?? null,
                    'attribute_value' => $attr['attribute_value'] ?? null,
                    'attr_value' => $attr['attr_value'] ?? null,
                ]);
            }
        }

        // Auto-approve RCA
        $approveDocument = MainHelper::approveDocument(
            $rcaHeader->book_id,
            $rcaHeader->id,
            $rcaHeader->revision_number ?? 0,
            $rcaHeader->remarks,
            null,
            $rcaHeader->approval_level,
            'submit',
            0,
            get_class($rcaHeader)
        );
        if ($approveDocument['message']) {
            DB::rollBack();
            return response()->json([
                'message' => $approveDocument['message'],
                'error' => "",
            ], 422);
        }
        
        $rcaHeader->document_status = !empty($approveDocument['approvalStatus']) 
            ? $approveDocument['approvalStatus'] 
            : ConstantHelper::DRAFT;
        $rcaHeader->save();

        return ['status' => 'success', 'message' => 'RCA generated successfully'];
    }
}


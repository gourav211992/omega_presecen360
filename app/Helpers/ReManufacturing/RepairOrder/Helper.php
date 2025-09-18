<?php
namespace App\Helpers\ReManufacturing\RepairOrder;

use App\Helpers\ConstantHelper;
use App\Helpers\ServiceParametersHelper;
use App\Models\Book;
use App\Models\ErpRgrItem;
use App\Models\WHM\ErpItemUniqueCode;
use App\Helpers\Helper as MainHelper;
use App\Lib\Services\WHM\RepairOrderJob;
use App\Models\ErpRepairOrder;
use App\Models\ErpRepItem;
use App\Models\ErpRepItemAttribute;
use App\Models\Organization;
use Carbon\Carbon;

//Repair Order General Helper
class Helper
{
    //Ok To Receive Items from RGR Item
    public static function generateRepFromRgrItem(ErpItemUniqueCode $rgrItemUniqueCode, string $repairOrderType, $authUser, $createJob = false) : array
    {
        $rgrItem = ErpRgrItem::with('rgr')->find($rgrItemUniqueCode -> morphable_id);
        //RGR Item reference not found
        if (!$rgrItem) {
            return [
                'status' => 'error',
                'message' => 'RGR Item reference not found'
            ];
        }
        //RGR Item Segregation
        $rgrSegregation = $rgrItem -> segregation;
        if (!$rgrSegregation) {
            return [
                'status' => 'error',
                'message' => 'RGR Item segregation not found'
            ];
        }
        $rgrHeader = $rgrItem -> rgr;
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
        
        //Insert data into Main Header Table
        $repairOrder = ErpRepairOrder::create([
            'group_id' => $groupId,
            'company_id' => $companyId,
            'organization_id' => $organization -> id,
            'book_id' => $okToReceiveRepBook -> id,
            'book_code' => $okToReceiveRepBook -> book_code,
            'store_id' => $rgrHeader -> store_id,
            'store_name' => $rgrHeader -> store_name,
            'rgr_sub_store_id' => $rgrItem -> sub_store_id,
            'qc_sub_store_id' => $rgrItem -> sub_store_id,
            'vendor_id' => null,
            'type' => null,
            'defect_status' => $rgrSegregation -> defect_severity,
            'doc_number_type' => $documentNoDetails['type'],
            'doc_reset_pattern' => $documentNoDetails['reset_pattern'],
            'doc_prefix' => $documentNoDetails['prefix'],
            'doc_suffix' => $documentNoDetails['suffix'],
            'doc_no' => $documentNoDetails['doc_no'],
            'document_date' => $documentDate,
            'revision_number' => 0,
            'revision_date' => null,
            'document_status' => 'pending',
            'revision_number' => 0,
            'revision_date' => null,
            'approval_level' => 1,
            'rgr_id' => $rgrHeader -> id,
            'remarks' => null,
        ]);
        //Now Item Details
        $repItem = ErpRepItem::create([
            'repair_order_id' => $repairOrder -> id,
            'rgr_item_id' => $rgrItem -> id,
            'rgr_job_detail_id' => $rgrSegregation -> job_item_id,
            'item_id' => $rgrItem -> item_id,
            'item_code' => $rgrItem -> item_code,
            'item_name' => $rgrItem -> item_name,
            'item_uid' => $rgrItemUniqueCode -> item_uid,
            'uom_id' => $rgrItem -> uom_id,
            'uom_code' => $rgrItem -> uom_name,
            'qty' => $rgrItemUniqueCode -> qty,
            'inventory_uom_id' => $rgrItem -> inventory_uom_id,
            'inventory_uom_code' => $rgrItem -> inventory_uom_code,
            'inventory_uom_qty' => $rgrItemUniqueCode -> qty,
            'service_item_id' => null,
            'service_item_code' => null,
            'service_item_name' => null,
            'rgr_sub_store_id' => $rgrItem -> sub_store_id, //NEED TO DISCUSS
            'rgr_sub_store_name' => $rgrItem ?-> subStore ?-> name,//NEED TO DISCUSS
            'qc_sub_store_id' => $rgrItem -> sub_store_id,//NEED TO DISCUSS
            'qc_sub_store_name' => $rgrItem -> subStore ?-> name,//NEED TO DISCUSS
            'rejuvenate_item_id' => null,
            'rejuvenate_item_code' => null,
            'rejuvenate_item_name' => null,
            'rejuvenate_item_attributes' => null,
            'repair_remarks' => null,
        ]);
        //Now Rep Item Attributes
        foreach ($rgrItem -> attributes as $rgrItemAttribute) {
            ErpRepItemAttribute::create([
                'repair_order_id' => $repairOrder -> id,
                'rep_item_id' => $repItem -> id,
                'item_attribute_id' => $rgrItemAttribute -> item_attribute_id,
                'item_code' => $rgrItemAttribute -> rgrItem -> item_code,
                'attribute_name' => $rgrItemAttribute -> attribute_name,
                'attr_name' => $rgrItemAttribute -> attr_name,
                'attribute_value' => $rgrItemAttribute -> attribute_value,
                'attr_value' => $rgrItemAttribute -> attr_value,
            ]);
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
}

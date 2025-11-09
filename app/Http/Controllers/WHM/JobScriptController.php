<?php

namespace App\Http\Controllers\WHM;

use App\Exceptions\ApiGenericException;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use App\Models\ErpMaterialIssueHeader;
use App\Models\InspectionDetail;
use App\Models\InspectionHeader;
use App\Models\MrnHeader;
use App\Models\StockLedger;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use DB;

class JobScriptController extends Controller
{
    protected $referenceNo;  
    protected $referenceHeader; 
    protected $storeId; 
    protected $subStoreId; 
    protected $subStoreType; 

    public function createJob(Request $request){
        \DB::beginTransaction();
        try {
            $stockLedgers = StockLedger:: where('organization_id',39)
                ->where('group_id',16)
                ->where('company_id',17)
                ->where('transaction_type','receipt')
                ->whereIn('book_type',['mrn','mi'])
                ->where('putaway_pending_qty','<=',0)
                ->whereIn('document_status', ConstantHelper::DOCUMENT_STATUS_APPROVED)
                ->whereNull('utilized_id')
                ->get();

            foreach($stockLedgers as $stockLedger){
                $job = ErpWhmJob::where('morphable_id',$stockLedger->document_header_id)->where('trns_type',$stockLedger->book_type)->exists();
                if($job){
                    continue;
                }

                if($stockLedger->book_type == 'mrn'){
                    $header = MrnHeader::find($stockLedger->document_header_id);

                    // Create Inspection Job
                    $hasInspectionItems = $header->items()->where('is_inspection', 1)->exists();
                    if($hasInspectionItems){
                        $inspection = InspectionHeader::where('mrn_header_id',$stockLedger->document_header_id)->first();
                        if(!$inspection){
                            continue;
                        }
                        
                        $mainSubStore = $inspection->erpSubStore;
                        $rejectedSubStore = $inspection->rejectedSubStore;
                        $targets = [];
                        if ($mainSubStore) {
                            $targets[] = 'main_store';
                        }
                        if ($mainSubStore && $rejectedSubStore) {
                            $targets[] = 'rejected_store';
                        }

                        if (!empty($targets)) {
                            foreach ($targets as $target) {
                                $this->createPutawayJob($inspection->id, \App\Models\InspectionHeader::class, CommonHelper::PUTAWAY, $target);
                            }
                        }
                    }else{
                        $this->createPutawayJob($header->id, \App\Models\MrnHeader::class, CommonHelper::PUTAWAY);
                    }

                }

                if($stockLedger->book_type == 'mi'){
                    $header = ErpMaterialIssueHeader::find($stockLedger->document_header_id);
                    $this->createPutawayJob($header->id,ErpMaterialIssueHeader::class,CommonHelper::PUTAWAY);
                }

            }
        \DB::commit();
            return [
                'message' => 'Job created successfully!'
            ];
        } catch (\Exception $e) {
            \DB::rollback();
            throw new ApiGenericException($e->getMessage());
        }

    }

    private function createPutawayJob($id, $namespace, $jobType = null, $subStoreType = null)
    {
        // Step 1: Get Header
        $header = app($namespace)::findOrFail($id);
        $referenceType = null;
        $referenceId = null;
        $this->referenceNo = null;
        $this->referenceHeader = $header;
        $this->storeId = isset($header->store_id) ? $header->store_id : null;
        $this->subStoreId = isset($header->sub_store_id) ? $header->sub_store_id : null;
        $this->subStoreType = $subStoreType;

        // ✅ Conditionally skip MRN headers with no is_inspection = 0
        if ($namespace === \App\Models\MrnHeader::class) {
            $hasInspectionItems = $header->items()->where('is_inspection', 0)->exists();
            if (!$hasInspectionItems) {
                return; // ⛔ No job creation
            }
        }

        if ($namespace === \App\Models\InspectionHeader::class) {
            $referenceType = ConstantHelper::INSPECTION_SERVICE_ALIAS;
            $referenceId = $header->id;
            $this->referenceNo = $header->book_code.'-'.$header->doc_no;

            // Get batch data
            $batch = $header->batches()->get();
            $acceptedQty = $batch->sum('accepted_inv_uom_qty') ?? 0;
            $rejectedQty = $batch->sum('rejected_inv_uom_qty') ?? 0;

            if($this->subStoreType === 'rejected_store'){
                if($rejectedQty <= 0){
                    return; // ⛔ No job creation
                }
                $this->subStoreId = isset($header->rejected_sub_store_id) ? $header->rejected_sub_store_id : null;
            } else{
                if($acceptedQty <= 0){
                    return; // ⛔ No job creation
                }
                $this->subStoreId = isset($header->sub_store_id) ? $header->sub_store_id : null;
            }
            
            $namespace = \App\Models\MrnHeader::class;
            $id = $header->mrn_header_id;
            $header = app($namespace)::findOrFail($id);
        }

        if ($namespace === \App\Models\ErpMaterialIssueHeader::class) {
            $this->storeId = isset($header->to_store_id) ? $header->to_store_id : null;
            $this->subStoreId = isset($header->to_sub_store_id) ? $header->to_sub_store_id : null;
        }

        $type = $jobType ?? CommonHelper::getJobType($namespace);
        $trnstype = CommonHelper::getJobTransactionType($namespace);

        // Step 2: Get or Create Job (prevents duplicate job on edit)
        $job = ErpWhmJob::firstOrCreate(
            [
                'morphable_type' => $namespace,
                'morphable_id' => $header->id,
                'type' => $type,
                'store_id' => $this->storeId,
                'sub_store_id' => $this->subStoreId
            ],
            [
                'organization_id' => $header->organization_id,
                'group_id' => $header->group_id,
                'company_id' => $header->company_id,
                'status' => CommonHelper::CLOSED,
                'trns_type' => $trnstype,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $this->referenceNo,
            ]
        );

        // Step 3: Fetch Details with attributes
        if (!method_exists($header, 'items')) {
            throw new \Exception("Model does not have 'items' relationship defined.");
        }

        $detailsQuery = $header->items()->with('attributes');
        
        if ($namespace === \App\Models\MrnHeader::class) {
            $detailsQuery->where('is_inspection', 0);
        }

        if($referenceType == ConstantHelper::INSPECTION_SERVICE_ALIAS){
            $detailsQuery = InspectionDetail::where('header_id',$referenceId)->with('attributes');
        }
        
        $details = $detailsQuery->get();

        // Step 3: Loop through each detail and create unique item codes
        foreach ($details as $detail) {
            $detalNamespace = get_class($detail);
            $this->generateUniqueQRCodes($header, $job, $detalNamespace, $detail, $type, $trnstype);
        }

    }

    private function generateUniqueQRCodes($header, $job, $namespace, $detail, $type, $trnstype)
    {
        $qty = intval($detail->inventory_uom_qty);
        $attributes = $this->getAttributes($detail);

        if (method_exists($detail, 'batches')) {
            $batchData = $detail->batches()
                ->where('header_id',$this->referenceHeader->id)
                ->where('detail_id',$detail->id)
                ->where('item_id',$detail->item_id)
                ->get();
    
            if ($batchData->count() > 0) {
                foreach($batchData as $batch){
                    // Get qty
                    $qty = isset($batch->accepted_inv_uom_qty) && $batch->accepted_inv_uom_qty ? $batch->accepted_inv_uom_qty : intval($batch->inventory_uom_qty);
                    if($this->subStoreType === 'rejected_store'){
                        $qty = isset($batch->rejected_inv_uom_qty) && $batch->rejected_inv_uom_qty ? $batch->rejected_inv_uom_qty : 0;
                    }
    
                    $this->createUniqueCode($header, $job, $namespace, $detail, $attributes, $type, $trnstype, $batch,$qty);
                }
    
                return; // Exit here so fresh creation logic is not executed for MRN
            }
        }

        
        $this->createUniqueCode($header, $job, $namespace, $detail, $attributes, $type, $trnstype, NULL, $qty);

    }

    private function createUniqueCode($header, $job, $namespace, $detail, $attributes, $type, $trnstype, $batch, $qty){
        $referenceType = null;
        $referenceDetailId = null;

        $morphableType = $namespace;
        $morphableId = $detail->id;

        if ($namespace === \App\Models\InspectionDetail::class){
            $morphableType = \App\Models\MrnDetail::class;
            $morphableId = $detail->mrn_detail_id;
            $referenceType = ConstantHelper::INSPECTION_SERVICE_ALIAS;
            $referenceDetailId = $detail->id;
        }

        $storageUomCount = intval(optional($detail->item)->storage_uom_count);
        $totalPacket = $storageUomCount > 0 ? $storageUomCount : 1;

        // Create Unique Code
        $records = [];

        for ($packet = 1; $packet <= $totalPacket; $packet++) {
            for ($i = 0; $i < $qty; $i++) {
                $itemUid = $totalPacket > 1 ? $this->generateUniqueUid(). '-' . $packet : $this->generateUniqueUid();
                $packetNo = $totalPacket > 1 ? $packet : $totalPacket;
                $records[] = [
                    'uid' => $this->generateUniqueUid(),
                    'job_id' => $job->id,
                    'organization_id' => $job->organization_id,
                    'group_id' => $job->group_id,
                    'company_id' => $job->company_id,
                    'morphable_type' => $morphableType,
                    'morphable_id' => $morphableId,
                    'job_type' => $type,
                    'trns_type' => $trnstype,
                    'doc_type' => CommonHelper::RECEIPT,
                    'doc_no' => $header->document_number ?? null,
                    'doc_date' => $header->document_date ?? null,
                    'book_id' => $header->book_id ?? null,
                    'store_id' => $this->storeId,
                    'sub_store_id' => $this->subStoreId,
                    'book_code' => $header->book_code ?? null,
                    'item_attributes' => json_encode($attributes),
                    'item_id' => $detail->item_id,
                    'item_name' => $detail->item->item_name,
                    'item_code' => $detail->item_code,
                    'vendor_id' => $header->vendor_id,
                    'item_uid' => $itemUid,
                    'packet_no' => $packetNo,
                    'total_packets' => $totalPacket,
                    'type' => 'qr',
                    'qty' => 1,
                    'status' => CommonHelper::SCANNED,
                    'storage_point_id' => 230,
                    'batch_id' => $batch ? $batch->id : NULL,
                    'batch_number' => $batch ? $batch->batch_number : NULL,
                    'manufacturing_year' => $batch ? ($batch->manufacturing_year == 0 ? NULL : $batch->manufacturing_year) : NULL,
                    'expiry_date' => $batch ? ($batch->expiry_date ? date('Y-m-d',strtotime($batch->expiry_date)) : NULL) : NULL,
                    'reference_type' => $referenceType,
                    'reference_detail_id' => $referenceDetailId,
                    'reference_no' => $this->referenceNo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($records, 500) as $chunk) {
            ErpItemUniqueCode::insert($chunk);
        }
    }

    private function getAttributes($detail){
        $attributeJsonArray = [];
        if(isset($detail->attributes) && !empty($detail->attributes)){
            foreach($detail->attributes as $key1 => $attribute) {
                $attributeJsonArray[] = [
                    "attr_name" => (string)$attribute->attr_name,
                    "attribute_name" => (string)@$attribute->attributeName->name,
                    "attr_value" => (string)@$attribute->attr_value,
                    "attribute_value" => (string)@$attribute->attributeValue->value,
                ];
            }
        }

        return $attributeJsonArray;
    }

    public function generateUniqueUid($length = 15)
    {
        $raw = str_replace('-', '', Str::uuid()); // 15-character hex
        $uid = strtoupper(substr($raw, 0, $length)); // Alphanumeric only, uppercase
        return $uid;
    }
}

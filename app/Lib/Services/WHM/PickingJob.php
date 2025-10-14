<?php

namespace App\Lib\Services\WHM;

use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use App\Models\ErpMiItem;
use App\Models\ErpPlItem;
use App\Models\ErpPsvItem;
use App\Models\ErpTripPlanHeader;
use App\Models\StockLedgerReservation;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PickingJob
{
    protected $referenceNo;  
    protected $referenceHeader; 
    protected $storeId; 
    protected $subStoreId; 

    public function createJob($id, $namespace, $jobType = null, $subStoreType = null)
    {
        // Step 1: Get Header
        $header = app($namespace)::findOrFail($id);

        $type = $jobType ?? CommonHelper::getJobType($namespace);
        $trnstype = CommonHelper::getJobTransactionType($namespace);
        $subStoreId = isset($header->main_sub_store_id) ? $header->main_sub_store_id : NULL;

        if ($namespace === \App\Models\ErpPsvHeader::class) {
            $type = 'picking';
            $hasPsvItems = $header->items()->where('adjusted_qty','<', 0)->exists();
            if (!$hasPsvItems) {
                return; // ⛔ No job creation
            }

            $subStoreId = isset($header->sub_store_id) ? $header->sub_store_id : NULL;
        }

        // Step 2: Get or Create Job (prevents duplicate job on edit)
        $job = (new WhmJob())->createJob($header, $namespace, $type, $trnstype, $header->store_id, $subStoreId, NULL, NULL, NULL);
    }
    
    private function generateUniqueUid($length = 15)
    {
        $raw = str_replace('-', '', Str::uuid()); // 15-character hex
        $uid = strtoupper(substr($raw, 0, $length)); // Alphanumeric only, uppercase
        return $uid;
    }

    public function scanQRCodes($detail, $header, $job, $packetIds, $storagePointId, $userId, $jobType, $trnstype)
    {
        $attributes = $detail->attributes;

        $packets = ErpItemUniqueCode::whereIn('item_uid', $packetIds)
            // ->where('storage_point_id',$storagePointId)
            ->when($storagePointId, function ($query) use ($storagePointId) {
                $query->where('storage_point_id', $storagePointId);
            })
            ->whereNull('utilized_id')
            ->whereIn('trns_type', $trnstype)
            ->where('status', CommonHelper::SCANNED)
            ->get();

        $morphableType = get_class($detail);
        $trip = ErpTripPlanHeader::find($header->trip_id);
         
        foreach ($packets as $code) {
            $newRecord = ErpItemUniqueCode::create([
                'uid' => $this->generateUniqueUid(),
                'job_id' => $job->id,
                'organization_id' => $job->organization_id,
                'group_id' => $job->group_id,
                'company_id' => $job->company_id,
                'morphable_type' => $morphableType,
                'morphable_id' => $detail->id,
                'job_type' => $jobType,
                'trns_type' => $job->trns_type,
                'doc_type' => CommonHelper::ISSUE,
                'doc_no' => $header->document_number ?? null,
                'doc_date' => $header->document_date ?? null,
                'book_id' => $header->book_id ?? null,
                'store_id' => $job->store_id,
                'sub_store_id' => $job->sub_store_id,
                'book_code' => $header->book_code ?? null,
                'item_attributes' => json_encode($code->item_attributes),
                'item_id' => $code->item_id,
                'item_name' => $code->item_name,
                'item_code' => $code->item_code,
                'vendor_id' => $code->vendor_id,
                'item_uid' => $code->item_uid, 
                'packet_no' => $code->packet_no,
                'total_packets' => $code->total_packets,
                'batch_id' => $code->batch_id,
                'batch_number' => $code->batch_number,
                'manufacturing_year' => $code->manufacturing_year,
                'expiry_date' => $code->expiry_date,
                'serial_no' => $code->serial_no,
                'type' => 'qr',
                'qty' => 1,
                'status' => CommonHelper::SCANNED,
                'trip_id' => isset($trip->id) ? $trip->id : NULL,
                'trip_no' => isset($trip->document_number) ? $trip->document_number : NULL,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $code->utilized_id = $newRecord->uid;
            $code->save();
        }
    }

    public function generateQRCodes($subStoreId, $job, $storeId = null)
    {
        $chunkSize = 100;

        $job->itemUniqueCodes()
            ->where('status', CommonHelper::SCANNED)
            ->where('doc_type', CommonHelper::ISSUE)
            ->whereNull('utilized_id')
            ->chunk($chunkSize, function ($packets) use ($subStoreId, $storeId) {

                if ($packets->isEmpty()) {
                    return;
                }

                $now = now();
                $insertData = [];
                $updateData = [];

                foreach ($packets as $packet) {
                    $newUid = $this->generateUniqueUid();

                    // Insert new QR copy
                    $insertData[] = [
                        'uid' => $newUid,
                        'job_id' => $packet->job_id,
                        'organization_id' => $packet->organization_id,
                        'group_id' => $packet->group_id,
                        'company_id' => $packet->company_id,
                        'morphable_type' => $packet->morphable_type,
                        'morphable_id' => $packet->morphable_id,
                        'job_type' => $packet->job_type,
                        'trns_type' => ConstantHelper::PL_SERVICE_ALIAS,
                        'doc_type' => CommonHelper::RECEIPT,
                        'doc_no' => $packet->doc_no ?? null,
                        'doc_date' => $packet->doc_date ?? null,
                        'book_id' => $packet->book_id ?? null,
                        'store_id' => $storeId ?? $packet->store_id,
                        'sub_store_id' => $subStoreId ?? null,
                        'book_code' => $packet->book_code ?? null,
                        'item_attributes' => json_encode($packet->item_attributes),
                        'item_id' => $packet->item_id,
                        'item_name' => $packet->item_name,
                        'item_code' => $packet->item_code,
                        'vendor_id' => $packet->vendor_id,
                        'batch_id' => $packet->batch_id,
                        'batch_number' => $packet->batch_number,
                        'manufacturing_year' => $packet->manufacturing_year,
                        'expiry_date' => $packet->expiry_date,
                        'serial_no' => $packet->serial_no,
                        'item_uid' => $packet->item_uid,
                        'trip_id' => $packet->trip_id,
                        'trip_no' => $packet->trip_no,
                        'storage_point_id' => null,
                        'packet_no' => $packet->packet_no,
                        'total_packets' => $packet->total_packets,
                        'type' => 'qr',
                        'qty' => 1,
                        'status' => CommonHelper::SCANNED,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'action_by' => $packet->action_by,
                        'action_at' => $now
                    ];

                    // Store original UID and new UID for update
                    $updateData[$packet->uid] = $newUid;
                }

                // Insert all new QR records
                \DB::table('erp_item_unique_codes')->insert($insertData);

                // Update original packets' utilized_id by UID
                foreach ($updateData as $originalUid => $newUid) {
                    \DB::table('erp_item_unique_codes')
                        ->where('uid', $originalUid)
                        ->update(['utilized_id' => $newUid]);
                }
                
            });
    }


    // public function generateQRCodes($subStoreId,$job,$storeId = null)
    // {
    //     $chunkSize = 100;

    //     // Query to get packets to process, chunked for memory efficiency
    //     $job->itemUniqueCodes()
    //         ->where('status', CommonHelper::SCANNED)
    //         ->where('doc_type', CommonHelper::ISSUE)
    //         ->whereNull('utilized_id')
    //         ->chunk($chunkSize, function ($packets) use ($subStoreId, $storeId) {

    //         if ($packets->isEmpty()) {
    //             // No packets in this chunk, nothing to do
    //             return;
    //         }

    //         foreach ($packets as $packet) {
    //             $newRecord = ErpItemUniqueCode::create([
    //                 'uid' => $this->generateUniqueUid(),
    //                 'job_id' => $packet->job_id,
    //                 'organization_id' => $packet->organization_id,
    //                 'group_id' => $packet->group_id,
    //                 'company_id' => $packet->company_id,
    //                 'morphable_type' => $packet->morphable_type,
    //                 'morphable_id' => $packet->morphable_id,
    //                 'job_type' => $packet->job_type,
    //                 'trns_type' => ConstantHelper::PL_SERVICE_ALIAS,
    //                 'doc_type' => CommonHelper::RECEIPT,
    //                 'doc_no' => $packet->doc_no ?? null,
    //                 'doc_date' => $packet->doc_date ?? null,
    //                 'book_id' => $packet->book_id ?? null,
    //                 'store_id' => $storeId ? $storeId : ($packet->store_id ?? null),
    //                 'sub_store_id' => $subStoreId ?? null,
    //                 'book_code' => $packet->book_code ?? null,
    //                 'item_attributes' => json_encode($packet->item_attributes),
    //                 'item_id' => $packet->item_id,
    //                 'item_name' => $packet->item_name,
    //                 'item_code' => $packet->item_code,
    //                 'vendor_id' => $packet->vendor_id,
    //                 'batch_id' => $packet->batch_id,
    //                 'batch_number' => $packet->batch_number,
    //                 'manufacturing_year' => $packet->manufacturing_year,
    //                 'expiry_date' => $packet->expiry_date,
    //                 'serial_no' => $packet->serial_no,
    //                 'item_uid' => $packet->item_uid, 
    //                 'trip_id' => $packet->trip_id, 
    //                 'trip_no' => $packet->trip_no, 
    //                 'storage_point_id' => Null, 
    //                 'packet_no' => $packet->packet_no,
    //                 'total_packets' => $packet->total_packets,
    //                 'type' => 'qr',
    //                 'qty' => 1,
    //                 'status' => CommonHelper::SCANNED,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //                 'action_by' => $packet->action_by,
    //                 'action_at' => now()
    //             ]);

    //             $packet->utilized_id = $newRecord->uid;
    //             $packet->save();
    //         }
    //     });

    // }

    public function reservedStock($job, $issueDetailId)
    {
        $reservedStock = StockLedgerReservation::where('issue_book_type', $job->trns_type)
            ->where('issue_header_id', $job->morphable_id)
            ->where('issue_detail_id', $issueDetailId);

        $transType = $reservedStock->pluck('receipt_book_type')->toArray();
        $mrnIds = $reservedStock->pluck('receipt_detail_id')->toArray();

        return [
            'transType' => $transType, 
            'mrnIds' => $mrnIds
        ];
    }

    public function getScannedPackets($jobId, $packetIds, $plItemId)
    {
        $packets = ErpItemUniqueCode::where('job_id', $jobId)
            ->when($packetIds, function($q) use($packetIds){
                $q->whereIn('item_uid', $packetIds);
            })
            ->where('status', CommonHelper::SCANNED)
            ->where('job_type', CommonHelper::PICKING)
            ->where('morphable_id', $plItemId)
            ->get();

        return [
            'data' => $packets
        ];
    }

    public function getPlItemDetail($trnsType, $plItemId)
    {
        if($trnsType == ConstantHelper::MATERIAL_ISSUE_SERVICE_ALIAS_NAME ){
            $detail = ErpMiItem::find($plItemId);
        }elseif($trnsType == ConstantHelper::PSV_SERVICE_ALIAS ){
            $detail = ErpPsvItem::find($plItemId);
        }else{
            $detail = ErpPlItem::find($plItemId);
        }

        if(!$detail){
            throw ValidationException::withMessages([
                'pl_item_id' => ['Item not found.'],
            ]);
        }

        return $detail;
    }

    public function getMrnPackets($packetIds, $storagePointId, $itemId, $mrnIds, $transType)
    {
        $packets = ErpItemUniqueCode::whereIn('item_uid', $packetIds)
            ->when($storagePointId, fn($q) => $q->where('storage_point_id', $storagePointId))
            ->where('item_id', $itemId)
            ->whereIn('morphable_id', $mrnIds)
            ->whereIn('trns_type', $transType)
            ->pluck('item_uid')
            ->toArray();
            
        return $packets;
    }

    public function getJob($jobId)
    {
        $job = ErpWhmJob::where('id',$jobId)
            ->where('type',CommonHelper::PICKING)
            ->first();

        if(!$job){
            throw ValidationException::withMessages([
                'job_id' => ['Job not found.'],
            ]);
        }

        return $job;
    }
}
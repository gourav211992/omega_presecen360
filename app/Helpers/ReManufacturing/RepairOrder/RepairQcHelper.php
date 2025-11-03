<?php
namespace App\Helpers\ReManufacturing\RepairOrder;

use App\Models\ErpRepairOrder;
use App\Models\ErpRepItem;
use App\Models\WHM\ErpWhmJob;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\ErpRgrItemSegregation;
use App\Models\ErpRepItemDefectLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\ConstantHelper;
use App\Lib\Services\WHM\WhmJob;


class RepairQcHelper
{

 public static function handleApproval($docId, $newDocStatus, $user = null)
    {
        try {
            $repairOrder = ErpRepairOrder::with('items')->findOrFail($docId);
         
            if ($repairOrder->type === 'repair') {

                if ($newDocStatus === ConstantHelper::REJECTED) {

                    foreach ($repairOrder->items ?? [] as $item) {

                        ErpRepItemDefectLog::create([
                            'repair_order_id' => $repairOrder->id,
                            'rep_item_id' => $item->id,
                            'rejuvenate_item_id' => $item->rejuvenate_item_id,
                            'rejuvenate_item_code' => $item->rejuvenate_item_code,
                            'rejuvenate_item_name' => $item->rejuvenate_item_name,
                            'rejuvenate_item_attributes' => $item->rejuvenate_item_attributes,
                            'repair_remarks' => $item->repair_remarks,
                            'created_by' => $user->auth_user_id ?? null,
                        ]);

                        // Null the fields
                        $item->rejuvenate_item_id = null;
                        $item->rejuvenate_item_code = null;
                        $item->rejuvenate_item_name = null;
                        $item->rejuvenate_item_attributes = null;
                        $item->repair_remarks = null;
                        $item->save();

                        $uniqueItem = ErpItemUniqueCode::where('morphable_id', $item->id)
                            ->where('morphable_type', ErpRepItem::class)
                            ->first();

                        $job = ErpWhmJob::find($uniqueItem->job_id);

                        $uniqueItem->status = ConstantHelper::PENDING;
                        $uniqueItem->save();

                        $job->status = 'pending';
                        $job->save();
                    }

                    $repairOrder->type = null;
                    $repairOrder->save();

                } elseif (in_array($newDocStatus, [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])) {
                    if (!empty($repairOrder->items)) {
                        self::generateRepairQc($repairOrder->id);
                    }
                }
            }

            // ---------- Send to Vendor type ----------
         if ($repairOrder->type === 'send_to_vendor' && $newDocStatus === ConstantHelper::REJECTED) {

            foreach ($repairOrder->items ?? [] as $item) {

                ErpRepItemDefectLog::create([
                    'repair_order_id'       => $repairOrder->id,
                    'rep_item_id'           => $item->id,
                    'rejuvenate_item_id'    => $item->rejuvenate_item_id,
                    'rejuvenate_item_code'  => $item->rejuvenate_item_code,
                    'rejuvenate_item_name'  => $item->rejuvenate_item_name,
                    'rejuvenate_item_attributes' => $item->rejuvenate_item_attributes,
                    'service_item_id'       => $item->service_item_id,
                    'service_item_code'     => $item->service_item_code,
                    'service_item_name'     => $item->service_item_name,
                    'vendor_id'             => $repairOrder->vendor_id,
                    'repair_remarks'        => $item->repair_remarks,
                    'created_by'            => $user->auth_user_id ?? null,
                ]);

                $item->rejuvenate_item_id = null;
                $item->rejuvenate_item_code = null;
                $item->rejuvenate_item_name = null;
                $item->rejuvenate_item_attributes = null;

                $item->service_item_id = null;
                $item->service_item_code = null;
                $item->service_item_name = null;

                $item->repair_remarks = null;
                $item->save();

                $uniqueItem = ErpItemUniqueCode::where('morphable_id', $item->id)
                    ->where('morphable_type', ErpRepItem::class)
                    ->first();

                $uniqueItem->status = ConstantHelper::PENDING;
                $uniqueItem->save();
            }

            $repairOrder->type = null;
            $repairOrder->vendor_id = null;
            $repairOrder->save();
        }

           // ---------- Scrap type ----------
         if ($repairOrder->type === 'scrap' && $newDocStatus === ConstantHelper::REJECTED) {

            foreach ($repairOrder->items ?? [] as $item) {

                ErpRepItemDefectLog::create([
                    'repair_order_id' => $repairOrder->id,
                    'rep_item_id'     => $item->id,
                    'repair_remarks'  => $item->repair_remarks,
                    'created_by'      => $user->auth_user_id ?? null,
                ]);

                $item->repair_remarks = null;
                $item->save();

                $uniqueItem = ErpItemUniqueCode::where('morphable_id', $item->id)
                    ->where('morphable_type', ErpRepItem::class)
                    ->first();

                $uniqueItem->status = ConstantHelper::PENDING;
                $uniqueItem->save();
            }

            $repairOrder->type = null;
            $repairOrder->save();
         }
           // ---------- Change Defect Severity ----------
            if ($repairOrder->type === 'change_defect_severity') {

                foreach ($repairOrder->items ?? [] as $item) {
                    $uniqueItem = ErpItemUniqueCode::where('morphable_id', $item->id)
                        ->where('morphable_type', ErpRepItem::class)
                        ->first();
                    $segregation = ErpRgrItemSegregation::find($item->rgr_item_segregation_id);
                    $lastLog = ErpRepItemDefectLog::where('rep_item_id', $item->id)->latest()->first();
                    if ($segregation && $lastLog && $newDocStatus === ConstantHelper::REJECTED) {
                        $segregation->update([
                            'defect_severity' => $lastLog->defect_severity,
                            'defect_type' => $lastLog->defect_type,
                            'damage_nature' => $lastLog->damage_nature,
                            'remarks' => $lastLog->remarks,
                        ]);

                        $item->repair_remarks = null;
                        $item->save();

                        $repairOrder->type = null;
                    }

                    if (in_array($newDocStatus, [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED,ConstantHelper::REJECTED])) {
                        $uniqueItem->status = ConstantHelper::PENDING;
                        $uniqueItem->save();
                    } 
                }
               
               // ---------- Update Repair Order status AFTER loop ----------
                if (in_array($newDocStatus, [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])) {
                    $newDocStatus = ConstantHelper::PENDING;
                     $repairOrder->type = null;
                } 
               
                $repairOrder->save();
               
            }

            return [
                'approvalStatus'=> $newDocStatus,
                'status' => 'success',
                'message' => 'Repair Order approval handled successfully.',
            ];

        } catch (\Throwable $e) {
            Log::error('Repair QC Approval Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'approvalStatus'=> $newDocStatus,
                'status'  => 'error',
                'message' => 'Something went wrong while approving Repair Order.',
            ];
        }
    }


    /**
     * Generate Repair QC Job for a given Repair Order
     */
    public static function generateRepairQc($repairOrderId)
    {

        $repairOrder = ErpRepairOrder::with(['items.uniqueCodes'])->find($repairOrderId);

        foreach ($repairOrder->items as $repItem) {
            foreach ($repItem->uniqueCodes as $uniqueCode) {

                if ($uniqueCode->job_id) {
                    $whmJob = ErpWhmJob::find($uniqueCode->job_id);
                    if ($whmJob) {
                        // Close existing job
                        $whmJob->status = 'closed';
                        $whmJob->save();

                        // Replicate job for Repair QC
                        $newJob = $whmJob->replicate(['id', 'job_closed_at']);
                        $newJob->type = 'repair-qc';
                        $newJob->status = 'pending';
                        $newJob->job_closed_at = null;
                        $newJob->save();

                        // Replicate unique codes for new job
                        foreach ($whmJob->itemUniqueCodes as $code) {
                            $newUnique = $code->replicate(['id', 'action_by', 'action_at']);
                            $newUnique->uid = (new WhmJob())->generateUniqueUid();
                            $newUnique->job_id = $newJob->id;
                            $newUnique->job_type = 'repair-qc';
                            $newUnique->status = 'pending';
                            $newUnique->save();

                            // Copy media
                            foreach ($code->media as $media) {
                                $newUnique->media()->create([
                                    'uuid' => (string) Str::uuid(),
                                    'model_name' => $media->model_name,
                                    'collection_name' => $media->collection_name,
                                    'name' => $media->name,
                                    'file_name' => $media->file_name,
                                    'mime_type' => $media->mime_type,
                                    'disk' => $media->disk,
                                    'size' => $media->size,
                                    'manipulations' => $media->manipulations,
                                    'custom_properties' => $media->custom_properties,
                                    'generated_conversions' => $media->generated_conversions,
                                    'responsive_images' => $media->responsive_images,
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}

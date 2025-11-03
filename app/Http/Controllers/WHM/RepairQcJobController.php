<?php

namespace App\Http\Controllers\WHM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WHM\ErpWhmJob;
use App\Models\ErpRepairOrder;
use App\Models\ErpRepItem;
use App\Models\ErpItemUniqueCode;
use App\Models\ErpStore;
use App\Models\ErpRgr;
use App\Models\ErpRgrItemSegregation;
use App\Helpers\CommonHelper;
use App\Helpers\Helper;
use App\Exceptions\ApiGenericException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Helpers\ConstantHelper;

class RepairQcJobController extends Controller
{
   public function getRepairQc(Request $request, $store_id)
    {
        if (!is_numeric($store_id)) {
            throw ValidationException::withMessages(['store_id' => ['Invalid store ID.']]);
        }

        $storeExists = ErpStore::where('id', $store_id)->exists();
        if (!$storeExists) {
            throw ValidationException::withMessages(['store_id' => ['Store does not exist.']]);
        }

        try {
            $search = $request->get('search');
            $defectStatus = $request->get('defect_status');
            $subStoreId = $request->get('sub_store_id');

            $repairOrderQuery = ErpRepairOrder::where('store_id', $store_id);

            if ($subStoreId) {
                $repairOrderQuery->where('qc_sub_store_id', $subStoreId);
            }

            if ($search) {
                $repairOrderQuery->where(function ($q) use ($search) {
                    $q->where('book_code', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('store_name', 'like', "%{$search}%");
                });
            }

            if ($defectStatus && strtolower($defectStatus) !== 'all') {
                $repairOrderQuery->where('defect_status', $defectStatus);
            }

            $repairOrders = $repairOrderQuery->orderBy('id', 'desc')
                ->paginate(CommonHelper::PAGE_LENGTH_10);

            $result = $repairOrders->map(function ($repairOrder) {

                $job = ErpWhmJob::where('morphable_type', ErpRepairOrder::class)
                    ->where('morphable_id', $repairOrder->id)
                    ->where('type', 'repair-qc')
                    ->latest()
                    ->first();

                $item = null;
                if ($job && $job->itemUniqueCodes->isNotEmpty()) {
                    $unique = $job->itemUniqueCodes->first(); 
                    $attributes = [];
                    if ($unique->item_attributes) {
                        $attrs = is_string($unique->item_attributes)
                            ? json_decode($unique->item_attributes, true) ?? []
                            : (array) $unique->item_attributes;

                        $attributes = array_map(fn($attr) => [
                            'attribute_name'  => $attr['attribute_name'] ?? null,
                            'attribute_value' => $attr['attribute_value'] ?? null,
                        ], $attrs);
                    }

                    $item = [
                        'id'         => $unique->id,
                        'item_id'    => $unique->item_id,
                        'uid'        => $unique->uid,
                        'item_uid'   => $unique->item_uid ?? "",
                        'item_code'  => $unique->item_code,
                        'item_name'  => $unique->item_name,
                        'attributes' => $attributes,
                        'status'     => $unique->status,
                    ];
                }

                return [
                    'id'            => $job?->id,
                    'document_no'   => ($repairOrder->book_code ?? '') . '-' . ($repairOrder->document_number ?? ''),
                    'store_name'    => $repairOrder->store_name ?? "",
                    'defect_status' => $repairOrder->defect_status ?? "",
                    'total_items'   => $job ? $job->itemUniqueCodes->count() : 0,
                    'item'          => $item,
                    'job' => $job ? [
                        'total_packets' => $job->itemUniqueCodes->count(),
                        'job_status'    => $job->status ?? "",
                        'created_at'    => $job->created_at?->format('Y-m-d') ?? "",
                    ] : null,
                ];
            });

            return [
                'message' => $result->isEmpty() ? 'No repair QC jobs found.' : 'Repair QC jobs retrieved successfully.',
                'data' => [
                    'records' => $result,
                    'pagination' => [
                        'current_page' => $repairOrders->currentPage(),
                        'last_page'    => $repairOrders->lastPage(),
                        'per_page'     => $repairOrders->perPage(),
                        'total'        => $repairOrders->total(),
                        'from'         => $repairOrders->firstItem(),
                        'to'           => $repairOrders->lastItem(),
                    ]
                ]
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
   }
   public function getRepairQcJobDetails($job_id)
    {
        if (!is_numeric($job_id)) {
            throw ValidationException::withMessages(['job_id' => ['Invalid job ID.']]);
        }

        try {

            $job = ErpWhmJob::where('id', $job_id)
                ->where('type', 'repair-qc')
                ->with('morphable', 'itemUniqueCodes.media')
                ->first();

            if (!$job || !$job->morphable) {
                throw ValidationException::withMessages(['job_id' => ['Repair QC job not found.']]);
            }

            $repairOrder = $job->morphable;

            $rgr = null;
            if ($repairOrder->rgr_id) {
                $rgr = ErpRgr::select('id', 'book_code', 'document_number', 'trip_no', 'vehicle_no')
                    ->find($repairOrder->rgr_id);
            }

            $uniqueCode = $job->itemUniqueCodes->first();
            $item = null;

            if ($uniqueCode) {
                $attributes = [];
               
                if ($uniqueCode->item_attributes) {
                    $attrs = is_string($uniqueCode->item_attributes)
                        ? json_decode($uniqueCode->item_attributes, true) ?? []
                        : (array) $uniqueCode->item_attributes;

                    $attributes = array_map(fn($attr) => [
                        'attribute_name'  => $attr['attribute_name'] ?? null,
                        'attribute_value' => $attr['attribute_value'] ?? null,
                    ], $attrs);
                }

                $itemImages = $uniqueCode->media->map(fn($media) => asset('storage/' . $media->file_name))->toArray();

                $repItem = ErpRepItem::where('repair_order_id', $repairOrder->id)->first();

                $defectDetails = (object)[];
                if ($repItem) {
                    $segregation = ErpRgrItemSegregation::where('rgr_id', $repairOrder->rgr_id)
                        ->where('job_item_id', $repItem->rgr_job_detail_id)
                        ->with('media')
                        ->first();

                    if ($segregation) {
                        $defectImages = $segregation->media->map(fn($media) => asset('storage/' . $media->file_name))->toArray();

                        $defectDetails = [
                            'defect_severity'     => $segregation->defect_severity,
                            'defect_type'         => $segregation->defect_type,
                            'damage_nature'       => $segregation->damage_nature,
                            'remarks'             => $segregation->remarks,
                            'new_item_id'         => $segregation->new_item_id,
                            'new_item_code'       => $segregation->new_item_code,
                            'new_item_name'       => $segregation->new_item_name,
                            'new_item_attributes' => $segregation->new_item_attributes ? json_decode($segregation->new_item_attributes, true) : null,
                            'defect_images'       => $defectImages,
                        ];
                    }
                }

                $item = [
                    'id'              => $uniqueCode->id,
                    'item_id'         => $uniqueCode->item_id,
                    'item_code'       => $uniqueCode->item_code,
                    'item_name'       => $uniqueCode->item_name,
                    'attributes'      => $attributes,
                    'uid'             => $uniqueCode->uid,
                    'item_uid'        => $uniqueCode->item_uid ?? "",
                    'repair_remark'   => $repItem->repair_remarks ?? null,
                    'status'          => $uniqueCode->status,
                    'defect_detail'   => $defectDetails,
                    'item_image_urls' => $itemImages,
                ];
            }

            $data = [
                'repair_order_id' => $repairOrder->id,
                'repair_doc_no'   => trim(($repairOrder->book_code ?? '') . '-' . ($repairOrder->document_number ?? '')),
                'posted_by'       => $repairOrder->createdBy?->name,
                'total_items'     => $job->itemUniqueCodes->count(),
                'job_status'      => $job->status,
                'rgr_id'          => $rgr?->id,
                'trip_no'         => $rgr?->trip_no,
                'vehicle_no'      => $rgr?->vehicle_no,
                'item'            => $item,
            ];

            return [
                'message' => 'Repair QC job details retrieved successfully.',
                'data'    => $data
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }
    public function getQcAction()
    {
        return response()->json([
            'message' => 'Data retrieved successfully.',
            'data' => ConstantHelper::QC_ACTION
        ], 200);
    }


  public function closeRepairQcJob(Request $request)
    {
        $validated = $request->validate([
            'job_id'  => 'required|numeric',
            'action'  => 'required|string|in:approve,reject,approve_with_deviation',
            'remarks' => 'required|string',
        ]);

        $job_id = $validated['job_id'];

        DB::beginTransaction();
        try {
            $job = ErpWhmJob::where('id', $job_id)
                ->where('type', 'repair-qc')
                ->with('itemUniqueCodes.morphable.repairOrder') 
                ->first();

            if (!$job) {
                throw ValidationException::withMessages(['job_id' => ['Repair QC job not found.']]);
            }

            if ($job->status === 'closed') {
                throw ValidationException::withMessages(['job' => ['Job already closed.']]);
            }

            $action  = $validated['action'];
            $remarks = $validated['remarks'];

            // ---------- Update job status ----------
            $job->status = match ($action) {
                'approve'                => 'closed',
                'reject'                 => 'rejected',
                'approve_with_deviation' => 'deviation',
            };
            $job->job_closed_at = now();
            $job->save();

            foreach ($job->itemUniqueCodes as $uniqueItem) {
                $uniqueItem->status = 'scanned';
                $uniqueItem->save();

                $repItem = $uniqueItem->morphable;
                if ($repItem && $repItem instanceof ErpRepItem) {
                    $repItem->repair_qc_remarks = $remarks;
                    $repItem->save();

                    $repairOrder = $repItem->repairOrder;

                    if ($action === 'reject' && $repairOrder) {
                        // ---------- Reject logic ----------
                        $repairOrder->type = null;
                        $repairOrder->document_status = 'pending';
                        $repairOrder->save();

                        foreach ($repairOrder->items ?? [] as $item) {
                            $item->rejuvenate_item_id = null;
                            $item->rejuvenate_item_code = null;
                            $item->rejuvenate_item_name = null;
                            $item->rejuvenate_item_attributes = null;
                            $item->repair_remarks = null;
                            $item->save();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Repair QC job closed successfully.',
                'job_id'  => $job->id,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
    }

}

<?php

namespace App\Http\Controllers\WHM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ErpRepairOrder;
use App\Models\ErpStore;
use App\Models\ErpRepItem;
use App\Models\ErpRgrItemSegregation;
use App\Models\ErpRepItemDefectLog;
use App\Models\ErpRgr;
use App\Models\Vendor;
use App\Models\WHM\ErpWhmJob;
use App\Models\WHM\ErpItemUniqueCode;
use App\Helpers\CommonHelper;
use App\Helpers\ConstantHelper;
use Illuminate\Validation\ValidationException;
use App\Lib\Services\WHM\WhmJob;
use App\Helpers\Helper;
use App\Models\Item;
use App\Models\ErpRepMedia;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiGenericException;
use Illuminate\Support\Str;

class RepairOrderJobController extends Controller
{

    public function getDefectStatusCounts(Request $request, $store_id)
    {
        if (!is_numeric($store_id)) {
            throw ValidationException::withMessages(['store_id' => ['Invalid store_id provided.']]);
        }

        $storeExists = ErpStore::where('id', $store_id)->exists();
        if (!$storeExists) {
            throw ValidationException::withMessages(['store_id' => ['Store does not exist.']]);
        }

        try {
            $counts = ErpRepairOrder::where('store_id', $store_id)
                ->selectRaw('LOWER(defect_status) as defect_status, COUNT(*) as total')
                ->groupByRaw('LOWER(defect_status)')
                ->pluck('total', 'defect_status');

            $result = [
                'minor' => $counts['minor'] ?? 0,
                'major' => $counts['major'] ?? 0,
                'scrap' => $counts['scrap'] ?? 0,
            ];

            return ['message' => 'Defect status counts retrieved successfully.', 'data' => $result];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }
    public function getRepairOrder(Request $request, $store_id)
    {
            if (!is_numeric($store_id)) {
                throw ValidationException::withMessages(['store_id' => ['Invalid store_id provided.']]);
            }

            $storeExists = ErpStore::where('id', $store_id)->exists();
            if (!$storeExists) {
                throw ValidationException::withMessages(['store_id' => ['Store does not exist.']]);
            }

            try {
                $search = $request->get('search');
                $defectStatus = $request->get('defect_status');
                $subStoreId = $request->get('sub_store_id');

                $repairOrders = ErpRepairOrder::with(['job.itemUniqueCodes','store','company','group','organization'])
                    ->where('store_id', $store_id)
                    ->when($subStoreId, function ($query) use ($subStoreId) {
                        $query->where('rgr_sub_store_id', $subStoreId);
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('book_code', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%")
                            ->orWhere('store_name', 'like', "%{$search}%");
                        });
                    })
                    ->when($defectStatus && strtolower($defectStatus) !== 'all', function ($query) use ($defectStatus) {
                        $query->where('defect_status', $defectStatus);
                    })
                    ->orderBy('id','desc')
                    ->paginate(CommonHelper::PAGE_LENGTH_10);

                // Transform data
                $result = $repairOrders->map(function ($order) {
                    $job = $order->job;

                    $items = [];
                    if ($job) {
                        $uniqueItemsGrouped = $job->itemUniqueCodes->groupBy('item_id');
                        foreach ($uniqueItemsGrouped as $itemId => $groupedItems) {
                            $firstItem = $groupedItems->first();
                            $attributes = [];

                            foreach ($groupedItems as $uniqueItem) {
                                if ($uniqueItem->item_attributes) {
                                    $attrs = is_string($uniqueItem->item_attributes) 
                                        ? json_decode($uniqueItem->item_attributes, true) ?? [] 
                                        : $uniqueItem->item_attributes;
                                    $attributes = array_merge($attributes, $attrs);
                                }
                            }

                            $items[] = [
                                'unique_item_id'    => $firstItem->id,
                                'item_id'    => $firstItem->item_id,
                                'uid'        => $firstItem->uid,
                                'item_uid'   => $firstItem->item_uid ?? "",
                                'item_code'  => $firstItem->item_code,
                                'item_name'  => $firstItem->item_name,
                                'attributes' => $attributes,
                            ];
                        }
                    }

                    return [
                        'id'            => $job?->id,
                        'document_no'   => ($order->book_code ?? '') . '-' . ($order->document_number ?? ''),
                        'store_name' => $order->store->store_name ?? "",
                        'defect_status' => $order->defect_status ?? "", 
                        'total_items'   => count($items),
                        'items'         => $items,
                        'job' => $job ? [
                            'total_packets' => $job->itemUniqueCodes->count(),
                            'job_status'    => $job->status ?? "",
                            'created_at'    => $job->created_at?->format('Y-m-d') ?? "",
                        ] : null,
                    ];
                });

                return [
                    'message' => $result->isEmpty() ? 'No records found.' : 'Data retrieved successfully.',
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

    public function getRepairOrderDetails($job_id)
    {
        if (!is_numeric($job_id)) {
            throw ValidationException::withMessages(['job_id' => ['Invalid job_id provided.']]);
        }

        try {
            $job = ErpWhmJob::where('id', $job_id)
                ->where('morphable_type', ErpRepairOrder::class)
                ->with('morphable', 'itemUniqueCodes')
                ->first();

            if (!$job || !$job->morphable) {
                throw ValidationException::withMessages(['job_id' => ['No Repair Order found for this job.']]);
            }

            if ($job->status === 'closed') {
                throw ValidationException::withMessages(['job_id' => ['This job is closed.']]);
            }

            $repairOrder = $job->morphable;

            $rgr = null;
            if ($repairOrder->rgr_id) {
                $rgr = ErpRgr::select('id', 'book_code', 'document_number', 'trip_no', 'vehicle_no')
                    ->find($repairOrder->rgr_id);
            }

            $scannedItems = ErpItemUniqueCode::where('job_id', $job_id)
                ->where('status', 'scanned')
                ->orderBy('updated_at', 'desc')
                ->paginate(CommonHelper::PAGE_LENGTH_10);

            $formattedScannedItems = $scannedItems->map(function ($uniqueCode) use ($repairOrder) {
                $attributes = [];

                if ($uniqueCode->item_attributes) {
                    if (is_string($uniqueCode->item_attributes)) {
                        $attributes = json_decode($uniqueCode->item_attributes, true) ?? [];
                    } elseif (is_array($uniqueCode->item_attributes)) {
                        $attributes = $uniqueCode->item_attributes;
                    }
                }

                $repItem = ErpRepItem::where('repair_order_id', $repairOrder->id)
                 ->first();

                $defectDetails = [];
                if ($repItem) {
                    $segregation = ErpRgrItemSegregation::where('rgr_id', $repairOrder->rgr_id)
                        ->where('rgr_item_id', $repItem->rgr_item_id)
                        ->where('job_item_id', $repItem->rgr_job_detail_id)
                        ->first();
    
                    if ($segregation) {
                        $defectDetails = [
                            'segregation_id' => $segregation->id,               
                            'defect_severity' => $segregation->defect_severity,
                            'defect_type'     => $segregation->defect_type,
                            'damage_nature'   => $segregation->damage_nature,
                            'remarks'         => $segregation->remarks,
                        ];
                    }
                }

                $item_image_urls = [];
                foreach ($uniqueCode->media as $media) {  
                     $item_image_urls[] = asset('storage/' . $media->file_name);
                }

                return [
                    'id'            => $uniqueCode->id ?? "",
                    'item_id'       => $uniqueCode->item_id ?? "",
                    'item_code'     => $uniqueCode->item_code ?? "",
                    'item_name'     => $uniqueCode->item_name ?? "",
                    'attributes'    => $attributes,
                    'uid'           => $uniqueCode->uid ?? "",
                    'item_uid'      => $uniqueCode->item_uid ?? "",
                    'status'        => $uniqueCode->status ?? "",
                    'defect_detail' => $defectDetails ?? [],
                    'item_image_urls' => $item_image_urls,
                ];
            });

            $data = [
                'repair_order_id'  => $repairOrder->id,
                'repair_doc_no'    => ($repairOrder->book_code ?? '') . '-' . ($repairOrder->document_number ?? ''),
                'total_items'      => $job->itemUniqueCodes->count(),
                'scanned_items'    => $formattedScannedItems,
                'scanned_count'    => $scannedItems->total(),
                'rgr_id'           => $rgr?->id,
                'rgr_doc_no'       => $rgr ? ($rgr->book_code . '-' . $rgr->document_number) : null,
                'trip_no'          => $rgr?->trip_no,
                'vehicle_no'       => $rgr?->vehicle_no,
            ];

            $responseData = [
                'repair_order' => $data,
                'pagination' => [
                    'current_page' => $scannedItems->currentPage(),
                    'last_page'    => $scannedItems->lastPage(),
                    'per_page'     => $scannedItems->perPage(),
                    'total'        => $scannedItems->total(),
                    'from'         => $scannedItems->firstItem(),
                    'to'           => $scannedItems->lastItem(),
                ]
            ];

            return [
                'message' => 'Data retrieved successfully.',
                'data' => $responseData
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

   public function getServiceItems(Request $request)
    {
        try {
            $searchTerm = $request->query('search');

            $query = Item::where('status', ConstantHelper::ACTIVE)
                        ->where('type', 'Service');

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('item_code', 'like', '%' . $searchTerm . '%')
                    ->orWhere('item_name', 'like', '%' . $searchTerm . '%');
                });
            }

            $items = $query->orderBy('id', 'desc')
                        ->select('id','item_code','item_name')
                        ->limit(CommonHelper::PAGE_LENGTH_10)
                        ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => ['No Service items found.']]);
            }

            $records = $items->map(fn($item) => [
                'id'        => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
            ]);

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'records' => $records,
                    'total'   => $records->count(),
                ]
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function getRepairAction()
    {
        return response()->json([
            'message' => 'Data retrieved successfully.',
            'data' => ConstantHelper::REPAIR_ACTION 
        ], 200);
    }

   public function getVendors(Request $request)
    {
        try {
            $searchTerm = $request->query('search');

            $query = Vendor::where('status', ConstantHelper::ACTIVE);

            if ($searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('vendor_code', 'like', '%' . $searchTerm . '%')
                    ->orWhere('company_name', 'like', '%' . $searchTerm . '%');
                });
            }

            $vendors = $query->orderBy('id', 'desc')
                            ->select('id','vendor_code','company_name')
                            ->limit(CommonHelper::PAGE_LENGTH_10)
                            ->get();

            if ($vendors->isEmpty()) {
                throw ValidationException::withMessages(['vendors' => ['No vendors found.']]);
            }

            $records = $vendors->map(fn($vendor) => [
                'id'           => $vendor->id,
                'vendor_code'  => $vendor->vendor_code,
                'vendor_name'  => $vendor->company_name,
            ]);

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'records' => $records,
                    'total'   => $records->count(),
                ]
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

 public function repairAction(Request $request)
{
    DB::beginTransaction();

    try {
        $title = $request->input('title');
        $remark = $request->input('remark');

        $validated = $request->validate([
            'title' => 'required|string|in:scrap,repair,send_to_vendor,change_defect_severity',
            'remark' => 'nullable|string',
            'unique_item_id' => 'required', 
            'rejuvenate_item_id' => 'nullable|exists:erp_items,id',
            'rejuvenate_item_attributes' => 'nullable|array',
            'vendor_id' => 'nullable|exists:erp_vendors,id',
            'service_item_id' => 'nullable|exists:erp_items,id',
            'files' => 'nullable|array|max:5', 
            'files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048', 
            'defect_files' => 'nullable|array|max:5', 
            'defect_files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048', 
        ]);

        $uniqueItemIds = $request->input('unique_item_id');
        if (!is_array($uniqueItemIds)) {
            $uniqueItemIds = [$uniqueItemIds];
        }

        $processedJobIds = []; 

        foreach ($uniqueItemIds as $uniqueItemId) {

            $uniqueItem = ErpItemUniqueCode::where('id', $uniqueItemId)
                ->where('morphable_type', ErpRepItem::class)
                ->first();

            if (!$uniqueItem) {
                throw ValidationException::withMessages([
                    'unique_item_id' => ["Unique item ID {$uniqueItemId} not found."]
                ]);
            }

            if ($uniqueItem->status === 'scanned') {
                throw ValidationException::withMessages([
                    'unique_item_id' => ["Item ID {$uniqueItemId} has already been scanned."]
                ]);
            }

            $repItem = $uniqueItem->morphable;
            if (!$repItem || !$repItem instanceof ErpRepItem) {
                throw ValidationException::withMessages([
                    'unique_item_id' => ["Unique item ID {$uniqueItemId} not linked to a valid repair job."]
                ]);
            }

            $repairOrder = $repItem->repairOrder;
            if (!$repairOrder) {
                throw ValidationException::withMessages([
                    'repair_order' => ["Repair order not found for item ID {$uniqueItemId}."]
                ]);
            }


            $repairOrder->type = $title;
            if ($title === 'send_to_vendor' && $request->vendor_id) {
                $repairOrder->vendor_id = $request->vendor_id;
            }
            $repairOrder->save();

            $repItem->repair_remarks = $remark ?? $repItem->repair_remarks;

            if (in_array($title, ['repair', 'send_to_vendor']) && $request->rejuvenate_item_id) {
                $rejuItem = Item::find($request->rejuvenate_item_id);
                if ($rejuItem) {
                    $repItem->rejuvenate_item_id = $rejuItem->id;
                    $repItem->rejuvenate_item_code = $rejuItem->item_code;
                    $repItem->rejuvenate_item_name = $rejuItem->item_name;
                    $repItem->rejuvenate_item_attributes = json_encode($request->rejuvenate_item_attributes ?? []);
                }
            }

            if ($title === 'send_to_vendor' && $request->service_item_id) {
                $serviceItem = Item::find($request->service_item_id);
                if ($serviceItem) {
                    $repItem->service_item_id = $serviceItem->id;
                    $repItem->service_item_code = $serviceItem->item_code;
                    $repItem->service_item_name = $serviceItem->item_name;
                }
            }

            $uniqueItem->status = 'scanned';
            $uniqueItem->save();

            if ($request->hasFile('files')) {
                $uniqueItem->uploadDocuments($request->file('files'), 'images');
            }

            if ($title === 'change_defect_severity') {
                $defectLog = ErpRepItemDefectLog::create([
                    'repair_order_id' => $repairOrder->id,
                    'rep_item_id' => $repItem->id,
                    'defect_severity' => $request->defect_severity,
                    'defect_type' => $request->defect_type,
                    'damage_nature' => $request->damage_nature,
                    'remarks' => $remark,
                ]);

                if ($request->hasFile('defect_files')) {
                    $defectLog->uploadDocuments($request->file('defect_files'), 'defect_images');
                }
            }

            $repItem->save();

            if ($uniqueItem->job_id && $title !== 'change_defect_severity') {
                $whmJob = ErpWhmJob::find($uniqueItem->job_id);
                if ($whmJob) {
                    $whmJob->status = 'closed';
                    $whmJob->save();

                    if ($title === 'repair' && !$request->has('rejuvenate_item_id') && !in_array($whmJob->id, $processedJobIds)) {
                        $newJob = $whmJob->replicate(['id', 'job_closed_at']);
                        $newJob->type = 'repair-qc';
                        $newJob->status = 'pending';
                        $newJob->job_closed_at = null;
                        $newJob->save();

                        foreach ($whmJob->itemUniqueCodes as $uniqueCode) {
                            $newUnique = $uniqueCode->replicate(['id', 'action_by', 'action_at']);
                            $newUnique->uid = (new WhmJob())->generateUniqueUid();
                            $newUnique->job_id = $newJob->id;
                            $newUnique->job_type = 'repair-qc';
                            $newUnique->status = 'pending';
                            $newUnique->save();

                            foreach ($uniqueCode->media as $media) {
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

                        $repairOrder->document_status = 'approval_not_required';
                        $repairOrder->save();

                        $processedJobIds[] = $whmJob->id; 
                    }
                }
            }
        }

        DB::commit();

        return [
            'message' => 'Action processed successfully.'
        ];

    } catch (\Throwable $e) {
        DB::rollBack();
        throw new ApiGenericException($e->getMessage());
    }
}

}


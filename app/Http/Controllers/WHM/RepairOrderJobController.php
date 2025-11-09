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
use App\Helpers\ReManufacturing\RepairOrder\RepairQcHelper;
use App\Helpers\ReManufacturing\RepairOrder\Helper as RepHelper;

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
        $subStoreId = $request->input('sub_store_id');
        try {
            $counts = ErpRepairOrder::where('store_id', $store_id)
            ->when($subStoreId, fn($q) => $q->where('rgr_sub_store_id', $subStoreId))
            ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])
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
                ->whereIn('document_status', [
                    ConstantHelper::PENDING,
                    ConstantHelper::REJECTED
                ])
                ->when($subStoreId, fn($q) => $q->where('rgr_sub_store_id', $subStoreId))
                ->when($search, function ($query) use ($search) {
                    $query->where(fn($q) => 
                        $q->where('book_code', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('store_name', 'like', "%{$search}%")
                    );
                })
                ->when($defectStatus && strtolower($defectStatus) !== 'all', fn($q) => $q->where('defect_status', $defectStatus))
                ->whereHas('job', function ($q) {
                    $q->where('status', '!=', ConstantHelper::CLOSED)  
                    ->where('morphable_type', ErpRepairOrder::class); 
                })
                ->orderBy('id','desc')
                ->paginate(CommonHelper::PAGE_LENGTH_10);

                $result = $repairOrders->map(function ($order) {
                $job = $order->job;

                $itemObject = null;
                if ($job) {
                    $firstItem = $job->itemUniqueCodes->first();
                    if ($firstItem) {
                      $attributes = [];
                      if ($firstItem->item_attributes) {
                            $attrs = is_string($firstItem->item_attributes)
                                ? json_decode($firstItem->item_attributes, true) ?? []
                                : (array) $firstItem->item_attributes;

                            $attributes = array_map(fn($attr) => [
                                'attribute_name'  => $attr['attribute_name'] ?? null,
                                'attribute_value' => $attr['attribute_value'] ?? null,
                            ], $attrs);
                        }

                        $itemObject = [
                            'unique_item_id' => $firstItem->id,
                            'item_id'       => $firstItem->item_id,
                            'uid'           => $firstItem->uid,
                            'item_uid'      => $firstItem->item_uid ?? "",
                            'item_code'     => $firstItem->item_code,
                            'item_name'     => $firstItem->item_name,
                            'attributes'    => $attributes,
                        ];
                    }
                }

                return [
                    'id'            => $job?->id,
                    'document_no'   => ($order->book_code ?? '') . '-' . ($order->document_number ?? ''),
                    'store_name'    => $order->store->store_name ?? "",
                    'defect_status' => $order->defect_status ?? "", 
                    'total_items'   => $job?->itemUniqueCodes->count() ?? 0,
                    'item'          => $itemObject,
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
                ->with('morphable', 'itemUniqueCodes.media')
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

            $uniqueCode = ErpItemUniqueCode::where('job_id', $job_id)
                ->orderBy('updated_at', 'desc')
                ->first();

            $itemObject = null;
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

                $repItem = ErpRepItem::where('repair_order_id', $repairOrder->id)
                 ->where('item_id', $uniqueCode->item_id)
                 ->first();

                $defectDetails = (object)[];
                if ($repItem) {
                    $segregation = ErpRgrItemSegregation::where('rgr_id', $repairOrder->rgr_id)
                        ->where('job_item_id', $repItem->rgr_job_detail_id)
                        ->first();

                    if ($segregation) {
                        $defectDetails = [
                            'segregation_id' => $segregation->id,
                            'defect_severity' => $segregation->defect_severity,
                            'defect_type' => $segregation->defect_type,
                            'damage_nature' => $segregation->damage_nature,
                            'remarks' => $segregation->remarks,
                        ];
                    }
                }

                $item_image_urls = [];
                foreach ($uniqueCode->media as $media) {
                    $item_image_urls[] = asset('storage/' . $media->file_name);
                }

                $itemObject = [
                    'id' => $uniqueCode->id ?? "",
                    'item_id' => $uniqueCode->item_id ?? "",
                    'item_code' => $uniqueCode->item_code ?? "",
                    'item_name' => $uniqueCode->item_name ?? "",
                    'attributes' => $attributes,
                    'uid' => $uniqueCode->uid ?? "",
                    'item_uid' => $uniqueCode->item_uid ?? "",
                    'status' => $uniqueCode->status ?? "",
                    'defect_detail' => $defectDetails ,
                    'item_image_urls' => $item_image_urls,
                ];
            }

            $scanned_count = ErpItemUniqueCode::where('job_id', $job_id)
                ->where('status', 'scanned')
                ->count();

            $data = [
                'job_id' => $job->id,
                'repair_order_id' => $repairOrder->id,
                'repair_doc_no' => ($repairOrder->book_code ?? '') . '-' . ($repairOrder->document_number ?? ''),
                'total_items' => $job->itemUniqueCodes->count(),
                'repair_item' => $itemObject, 
                'scanned_count' => $scanned_count,
                'rgr_id' => $rgr?->id,
                'rgr_doc_no' => $rgr ? ($rgr->book_code . '-' . $rgr->document_number) : null,
                'trip_no' => $rgr?->trip_no,
                'vehicle_no' => $rgr?->vehicle_no,
            ];

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'repair_order' => $data
                ]
            ];

        } catch (\Exception $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function getRepairOrderDetailsByItemUid($item_uid)
    {
        if (!$item_uid) {
            throw ValidationException::withMessages(['item_uid' => ['Invalid item_uid provided.']]);
        }

        try {
            $uniqueCode = ErpItemUniqueCode::where('item_uid', $item_uid)
                ->where('morphable_type', ErpRepItem::class)
                 ->with('media', 'job') 
                ->first();

            if (!$uniqueCode || !$uniqueCode->job) {
                throw ValidationException::withMessages(['item_uid' => ['No job found for this item.']]);
            }

            $job = $uniqueCode->job;

            if ($job->morphable_type !== ErpRepairOrder::class || !$job->morphable) {
                throw ValidationException::withMessages(['item_uid' => ['Related job is not a Repair Order.']]);
            }

            if ($job->status === 'closed') {
                throw ValidationException::withMessages(['item_uid' => ['This job is closed.']]);
            }

            $repairOrder = $job->morphable;

            $rgr = null;
            if ($repairOrder->rgr_id) {
                $rgr = ErpRgr::select('id', 'book_code', 'document_number', 'trip_no', 'vehicle_no')
                    ->find($repairOrder->rgr_id);
            }

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

            $repItem = ErpRepItem::where('repair_order_id', $repairOrder->id)
                ->where('item_id', $uniqueCode->item_id)
                ->first();

            $defectDetails = (object)[];
            if ($repItem) {
                $segregation = ErpRgrItemSegregation::where('rgr_id', $repairOrder->rgr_id)
                    ->where('job_item_id', $repItem->rgr_job_detail_id)
                    ->first();

                if ($segregation) {
                    $defectDetails = [
                        'segregation_id' => $segregation->id,
                        'defect_severity' => $segregation->defect_severity,
                        'defect_type' => $segregation->defect_type,
                        'damage_nature' => $segregation->damage_nature,
                        'remarks' => $segregation->remarks,
                    ];
                }
            }

            $item_image_urls = [];
            foreach ($uniqueCode->media as $media) {
                $item_image_urls[] = asset('storage/' . $media->file_name);
            }

            $itemObject = [
                'id' => $uniqueCode->id ?? "",
                'item_id' => $uniqueCode->item_id ?? "",
                'item_code' => $uniqueCode->item_code ?? "",
                'item_name' => $uniqueCode->item_name ?? "",
                'attributes' => $attributes,
                'uid' => $uniqueCode->uid ?? "",
                'item_uid' => $uniqueCode->item_uid ?? "",
                'status' => $uniqueCode->status ?? "",
                'defect_detail' => $defectDetails ,
                'item_image_urls' => $item_image_urls,
            ];

            $data = [
                'job_id' => $job->id,
                'repair_order_id' => $repairOrder->id,
                'repair_doc_no' => ($repairOrder->book_code ?? '') . '-' . ($repairOrder->document_number ?? ''),
                'repair_item' => $itemObject,
                'rgr_id' => $rgr?->id,
                'rgr_doc_no' => $rgr ? ($rgr->book_code . '-' . $rgr->document_number) : null,
                'trip_no' => $rgr?->trip_no,
                'vehicle_no' => $rgr?->vehicle_no,
            ];

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'repair_order' => $data
                ]
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

   public function scrapAction(Request $request)
    {
        DB::beginTransaction();
        try {
            $remark = $request->input('remark');

            $validated = $request->validate([
                'unique_item_id' => 'required|exists:erp_item_unique_codes,id',
                'remark' => 'nullable|string',
                'files' => 'nullable|array|max:5',
                'files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048',
            ]);

            $uniqueItemIds = $request->input('unique_item_id');
            if (!is_array($uniqueItemIds)) $uniqueItemIds = [$uniqueItemIds];

            foreach ($uniqueItemIds as $uniqueItemId) {
                $uniqueItem = ErpItemUniqueCode::where('id', $uniqueItemId)
                    ->where('morphable_type', ErpRepItem::class)
                    ->firstOrFail();

                if ($uniqueItem->status === 'scanned') {
                    throw ValidationException::withMessages(['unique_item_id' => ["Item already scanned"]]);
                }

                if ($uniqueItem->job_id) {
                    $job = ErpWhmJob::find($uniqueItem->job_id);
                    if ($job && $job->status === 'closed') {
                        throw ValidationException::withMessages(['unique_item_id' => ["Job already closed for this item"]]);
                    }
                }

                $repItem = $uniqueItem->morphable;
                $repairOrder = $repItem->repairOrder;

                $repairOrder->type = 'scrap';
                $repairOrder->save();

                $repItem->repair_remarks = $remark ?? $repItem->repair_remarks;
                $repItem->save();

                $uniqueItem->status = 'scanned';
                $uniqueItem->save();

                if ($request->hasFile('files')) {
                    $uniqueItem->uploadDocuments($request->file('files'), 'images');
                }

                // ---------- Approve Document ----------
                $approveDocument = Helper::approveDocument(
                    $repairOrder->book_id,
                    $repairOrder->id,
                    $repairOrder->revision_number ?? 0,
                    $repairOrder->remarks,
                    $request->file('files'),
                    $repairOrder->approval_level,
                    'submit', 
                    0,
                    get_class($repairOrder)
                );

                if ($approveDocument['message']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $approveDocument['message'],
                        'error' => "",
                    ], 422);
                }

                // ---------- Update document status ----------
                $repairOrder->document_status = $approveDocument['approvalStatus'] ?? ConstantHelper::DRAFT;
                $repairOrder->save();
            }

            DB::commit();
            return ['message' => 'Scrap action processed successfully.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
 }

  public function repairAction(Request $request)
  {
        DB::beginTransaction();
        try {
            $remark = $request->input('remark');
            $rejuvenate_item_id = $request->input('rejuvenate_item_id');
            $rejuvenate_item_attributes = $request->input('rejuvenate_item_attributes', []);

            $validated = $request->validate([
                'unique_item_id' => 'required|exists:erp_item_unique_codes,id',
                'remark' => 'nullable|string',
                'rejuvenate_item_id' => 'nullable|exists:erp_items,id',
                'rejuvenate_item_attributes' => 'nullable|array',
                'files' => 'nullable|array|max:5',
                'files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048',
            ]);

            $uniqueItemIds = is_array($request->input('unique_item_id'))
                ? $request->input('unique_item_id')
                : [$request->input('unique_item_id')];

            $processedJobIds = [];

            foreach ($uniqueItemIds as $uniqueItemId) {
                $uniqueItem = ErpItemUniqueCode::where('id', $uniqueItemId)
                    ->where('morphable_type', ErpRepItem::class)
                    ->firstOrFail();

                if ($uniqueItem->status === 'scanned') {
                    throw ValidationException::withMessages(['unique_item_id' => ["Item already scanned"]]);
                }

                if ($uniqueItem->job_id) {
                    $job = ErpWhmJob::find($uniqueItem->job_id);
                    if ($job && $job->status === 'closed') {
                        throw ValidationException::withMessages(['unique_item_id' => ["Job already closed for this item"]]);
                    }
                }

                $repItem = $uniqueItem->morphable;
                $repairOrder = $repItem->repairOrder;

                $repairOrder->type = 'repair';
                $repairOrder->save();

                // ---------- Remarks ----------
                $repItem->repair_remarks = $remark ?? $repItem->repair_remarks;

                // ---------- Handle Rejuvenate Item ----------
                if ($rejuvenate_item_id) {
                    
                    $rejuItem = Item::find($rejuvenate_item_id);
                    $validatedAttributes = [];
                    if (!empty($rejuvenate_item_attributes) && $rejuItem) {
                       $validatedArray = RepHelper::validateItemAttributes($rejuvenate_item_attributes, $rejuItem->id, false);
                       $validatedAttributes = !empty($validatedArray) ? json_encode($validatedArray, JSON_THROW_ON_ERROR) : null;
                    }
                    if ($rejuItem) {
                        $validatedArray = !empty($rejuvenate_item_attributes)
                            ? RepHelper::validateItemAttributes($rejuvenate_item_attributes, $rejuItem->id, false)
                            : null;

                        $repItem->rejuvenate_item_id = $rejuItem->id;
                        $repItem->rejuvenate_item_code = $rejuItem->item_code;
                        $repItem->rejuvenate_item_name = $rejuItem->item_name;
                        $repItem->rejuvenate_item_attributes = !empty($validatedArray)
                            ? json_encode($validatedArray, JSON_THROW_ON_ERROR)
                            : null;
                    }
                }

                $repItem->save();

                $uniqueItem->status = 'scanned';
                $uniqueItem->save();

                if ($request->hasFile('files')) {
                    $uniqueItem->uploadDocuments($request->file('files'), 'images');
                }

                // ---------- Approval Logic ----------
                if ($rejuvenate_item_id) {
                    $approveDocument = Helper::approveDocument(
                        $repairOrder->book_id,
                        $repairOrder->id,
                        $repairOrder->revision_number ?? 0,
                        $repairOrder->remarks,
                        $request->file('files'),
                        $repairOrder->approval_level,
                        'submit',
                        0,
                        get_class($repairOrder)
                    );
                    if ($approveDocument['message']) {
                        DB::rollBack();
                        return response()->json([
                            'message' => $approveDocument['message'],
                            'error' => "",
                        ], 422);
                    }
                    $repairOrder->document_status = $approveDocument['approvalStatus'] ?? ConstantHelper::DRAFT;
                    $repairOrder->save();
                } else {
                    if ($uniqueItem->job_id && !in_array($uniqueItem->job_id, $processedJobIds)) {
                        RepairQcHelper::generateRepairQc($repairOrder->id);
                        $processedJobIds[] = $uniqueItem->job_id;
                        $repairOrder->document_status = ConstantHelper::APPROVAL_NOT_REQUIRED;
                        $repairOrder->save();
                    }
                }

                // ---------- Close Job After Repair Action ----------
                if ($uniqueItem->job_id && !in_array($uniqueItem->job_id, $processedJobIds)) {
                    $whmJob = ErpWhmJob::find($uniqueItem->job_id);
                    if ($whmJob) {
                        $whmJob->status = 'closed';
                        $whmJob->job_closed_at = now(); 
                        $whmJob->save();
                    }
                    $processedJobIds[] = $uniqueItem->job_id;
                }
            }

            DB::commit();
            return ['message' => 'Repair action processed successfully.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
   }

  public function sendToVendorAction(Request $request)
    {
        DB::beginTransaction();
        try {
            $remark = $request->input('remark');
            $vendor_id = $request->input('vendor_id');
            $service_item_id = $request->input('service_item_id');
            $rejuvenate_item_id = $request->input('rejuvenate_item_id');
            $rejuvenate_item_attributes = $request->input('rejuvenate_item_attributes', []);

            $validated = $request->validate([
                'unique_item_id' => 'required|exists:erp_item_unique_codes,id',
                'remark' => 'nullable|string',
                'vendor_id' => 'required|exists:erp_vendors,id',
                'service_item_id' => 'required|exists:erp_items,id',
                'rejuvenate_item_id' => 'nullable|exists:erp_items,id',
                'rejuvenate_item_attributes' => 'nullable|array',
                'files' => 'nullable|array|max:5',
                'files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048',
            ]);

            $uniqueItemIds = is_array($request->input('unique_item_id'))
                ? $request->input('unique_item_id')
                : [$request->input('unique_item_id')];

            foreach ($uniqueItemIds as $uniqueItemId) {
                $uniqueItem = ErpItemUniqueCode::where('id', $uniqueItemId)
                    ->where('morphable_type', ErpRepItem::class)
                    ->firstOrFail();

                if ($uniqueItem->status === 'scanned') {
                    throw ValidationException::withMessages(['unique_item_id' => ["Item already scanned"]]);
                }

                if ($uniqueItem->job_id) {
                    $job = ErpWhmJob::find($uniqueItem->job_id);
                    if ($job && $job->status === 'closed') {
                        throw ValidationException::withMessages(['unique_item_id' => ["Job already closed for this item"]]);
                    }
                }

                $repItem = $uniqueItem->morphable;
                $repairOrder = $repItem->repairOrder;

                // ---------- Update Repair Order ----------
                $repairOrder->type = 'send_to_vendor';
                $repairOrder->vendor_id = $vendor_id;
                $repairOrder->save();

                $repItem->repair_remarks = $remark ?? $repItem->repair_remarks;

                // ---------- Handle Rejuvenate Item ----------
                if ($rejuvenate_item_id) {
                    $rejuItem = Item::find($rejuvenate_item_id);
                    $validatedAttributes = [];
                    if (!empty($rejuvenate_item_attributes) && $rejuItem) {
                         $validatedArray = RepHelper::validateItemAttributes($rejuvenate_item_attributes, $rejuItem->id, false);
                          $validatedAttributes = !empty($validatedArray) ? json_encode($validatedArray, JSON_THROW_ON_ERROR) : null;
                    }

                    if ($rejuItem) {
                        $validatedArray = !empty($rejuvenate_item_attributes)
                            ? RepHelper::validateItemAttributes($rejuvenate_item_attributes, $rejuItem->id, false)
                            : null;

                        $repItem->rejuvenate_item_id = $rejuItem->id;
                        $repItem->rejuvenate_item_code = $rejuItem->item_code;
                        $repItem->rejuvenate_item_name = $rejuItem->item_name;
                        $repItem->rejuvenate_item_attributes = !empty($validatedArray)
                            ? json_encode($validatedArray, JSON_THROW_ON_ERROR)
                            : null;
                    }
                }

                // ---------- Service Item ----------
                $serviceItem = Item::find($service_item_id);
                if ($serviceItem) {
                    $repItem->service_item_id = $serviceItem->id;
                    $repItem->service_item_code = $serviceItem->item_code;
                    $repItem->service_item_name = $serviceItem->item_name;
                }

                $repItem->save();

                // ---------- Update unique item ----------
                $uniqueItem->status = 'scanned';
                $uniqueItem->save();

                // ---------- Upload Files ----------
                if ($request->hasFile('files')) {
                    $uniqueItem->uploadDocuments($request->file('files'), 'images');
                }

                // ---------- Approve Document ----------
                $approveDocument = Helper::approveDocument(
                    $repairOrder->book_id,
                    $repairOrder->id,
                    $repairOrder->revision_number ?? 0,
                    $repairOrder->remarks,
                    $request->file('files'),
                    $repairOrder->approval_level,
                    'submit',
                    0,
                    get_class($repairOrder)
                );

                if ($approveDocument['message']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $approveDocument['message'],
                        'error' => "",
                    ], 422);
                }

                // ---------- Update document status from approval ----------
                $repairOrder->document_status = $approveDocument['approvalStatus'] ?? ConstantHelper::DRAFT;
                $repairOrder->save();
            }

            DB::commit();
            return ['message' => 'Send to vendor action processed successfully.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
 }

 public function changeDefectSeverityAction(Request $request)
    {
        DB::beginTransaction();
        try {
            $remark = $request->input('remark');
            $user = Helper::getAuthenticatedUser();

            $validated = $request->validate([
                'unique_item_id' => 'required|exists:erp_item_unique_codes,id',
                'remark' => 'nullable|string',
                'defect_severity' => 'required|string',
                'defect_type' => 'required|string',
                'damage_nature' => 'required|string',
                'defect_files' => 'nullable|array|max:5',
                'defect_files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048',
            ]);

            $uniqueItemIds = is_array($request->input('unique_item_id'))
                ? $request->input('unique_item_id')
                : [$request->input('unique_item_id')];

            foreach ($uniqueItemIds as $uniqueItemId) {
                $uniqueItem = ErpItemUniqueCode::where('id', $uniqueItemId)
                    ->where('morphable_type', ErpRepItem::class)
                    ->firstOrFail();

                if ($uniqueItem->status === 'scanned') {
                    throw ValidationException::withMessages([
                        'unique_item_id' => ["Item already scanned"]
                    ]);
                }
                
                if ($uniqueItem->job_id) {
                    $job = ErpWhmJob::find($uniqueItem->job_id);
                    if ($job && $job->status === 'closed') {
                        throw ValidationException::withMessages(['unique_item_id' => ["Job already closed for this item"]]);
                    }
                }

                $repItem = $uniqueItem->morphable;
                $repairOrder = $repItem->repairOrder;
                $repairOrder->type = 'change_defect_severity';
                $repairOrder->save();

                // ---------- Update Repair Remarks ----------
                $repItem->repair_remarks = $remark ?? $repItem->repair_remarks;
                $repItem->save();

                // ---------- Handle Segregation ----------
                $segregation = ErpRgrItemSegregation::where('rgr_id', $repairOrder->rgr_id)
                    ->where('job_item_id', $repItem->rgr_job_detail_id)
                    ->where('id', $repItem->rgr_item_segregation_id)
                    ->first();

                if ($segregation) {
                    // ---------- Save old segregation state to defect log ----------
                    $defectLog = ErpRepItemDefectLog::create([
                        'repair_order_id' => $repairOrder->id,
                        'rep_item_id' => $repItem->id,
                        'defect_severity' => $segregation->defect_severity,
                        'defect_type' => $segregation->defect_type,
                        'damage_nature' => $segregation->damage_nature,
                        'remarks' => $segregation->remarks ?? $remark,
                        'created_by' => $user->auth_user_id ?? null,
                    ]);

                    // ---------- Update segregation with new defect details ----------
                    $segregation->update([
                        'defect_severity' => $request->defect_severity,
                        'defect_type' => $request->defect_type,
                        'damage_nature' => $request->damage_nature,
                        'remarks' => $remark ?? $segregation->remarks,
                    ]);

                    // ---------- Upload defect files if present ----------
                    if ($request->hasFile('defect_files')) {
                        $defectLog->uploadDocuments($request->file('defect_files'), 'defect_images');
                    }
                }

                // ---------- Mark unique item as scanned ----------
                $uniqueItem->update(['status' => 'scanned']);

                // ---------- Call Approval Logic for each item ----------
               $approveDocument= Helper::approveDocument(
                    $repairOrder->book_id,
                    $repairOrder->id,
                    $repairOrder->revision_number ?? 0,
                    $repairOrder->repair_remarks,
                    $request->file('defect_files'),
                    $repairOrder->approval_level,
                    'submit',
                    0,
                    get_class($repairOrder)
                );
                if ($approveDocument['message']) {
                    DB::rollBack();
                    return response()->json([
                        'message' => $approveDocument['message'],
                        'error' => "",
                    ], 422);
                }
                $repairOrder->document_status = $approveDocument['approvalStatus'] ?? ConstantHelper::DRAFT;
                $repairOrder->save();
            }

            DB::commit();
            return ['message' => 'Defect severity changed and approval processed successfully.'];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
    }

}


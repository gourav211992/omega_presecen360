<?php

namespace App\Http\Controllers\WHM;

use App\Exceptions\ApiGenericException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\CommonHelper;
use App\Models\ErpStore;
use App\Models\ErpRgr;
use App\Models\WHM\ErpWhmJob;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\ErpRgrItem;
use App\Models\Item;
use App\Models\ItemAttribute;
use App\Models\ErpRgrStoreMapping;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\ErpRgrItemSegregation;
use App\Models\ErpRgrDefectType;
use App\Models\ErpPickupItem;
use App\Models\ErpRgrDefectTypeDetail;
use App\Helpers\ConstantHelper;
use App\Helpers\RGR\Constants as RGRConstants;
use App\Helpers\ReManufacturing\RepairOrder\Helper as RepHelper;
use App\Helpers\ReManufacturing\RCA\Helper as RCAHelper;
use App\Helpers\ReManufacturing\RCA\MultiItemHelper as MultiRCAHelper;
use App\Helpers\ServiceParametersHelper;
use Illuminate\Validation\ValidationException;
use App\Lib\Services\WHM\WhmJob;
use App\Helpers\Helper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class RgrJobController extends Controller
{

   public function getRgr(Request $request, $store_id)
    {
        try {
            if (!is_numeric($store_id)) {
                throw ValidationException::withMessages(['store_id' => ['Invalid store_id provided.']]);
            }

            $storeExists = ErpStore::where('id', $store_id)->exists();
            if (!$storeExists) {
                throw ValidationException::withMessages(['store_id' => ['Store does not exist.']]);
            }

            $search = $request->get('search'); 

            $rgrs = ErpRgr::with(['items', 'job.itemUniqueCodes'])
            ->whereHas('job', function ($query) use ($store_id) {
                $query->where('store_id', $store_id)
                    ->where('status', '!=', 'closed')
                    ->where('morphable_type', ErpRgr::class); 
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('book_code', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('trip_no', 'like', "%{$search}%")
                    ->orWhere('vehicle_no', 'like', "%{$search}%")
                    ->orWhere('store_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('id','desc')
            ->paginate(CommonHelper::PAGE_LENGTH_10);

            if ($rgrs->isEmpty()) {
                throw ValidationException::withMessages(['rgrs' => ['No records found.']]);
            }

            $result = $rgrs->map(function ($rgr) {
                $job = $rgr->job;

                return [
                    'id'          => $rgr?->job?->id,
                    'document_no' => ($rgr->book_code ?? '') . '-' . ($rgr->document_number ?? ''),
                    'trip_no'     => $rgr->trip_no ?? "",
                    'vehicle_no'  => $rgr->vehicle_no ?? "",
                    'store_name'  => $rgr->store_name ?? "",
                    'total_items' => $rgr->items->count(),
                    'job' => $job ? [
                        'total_packets' => $job->itemUniqueCodes->count(),
                        'job_status'    => $job->status ?? "",
                        'created_at'    => $job->created_at ? $job->created_at->format('Y-m-d') : "",
                    ] : [],
                ];
            });

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'records' => $result,
                    'pagination' => [
                        'current_page' => $rgrs->currentPage(),
                        'last_page'    => $rgrs->lastPage(),
                        'per_page'     => $rgrs->perPage(),
                        'total'        => $rgrs->total(),
                        'from'         => $rgrs->firstItem(),
                        'to'           => $rgrs->lastItem(),
                    ]
                ]
            ];

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

   public function getRgrDetails($job_id)
    {
        try {
            if (!is_numeric($job_id)) {
                throw ValidationException::withMessages(['job_id' => ['Invalid job_id provided.']]);
            }

            $job = ErpWhmJob::where('id', $job_id)
                ->where('morphable_type', ErpRgr::class)
                ->with('morphable', 'itemUniqueCodes')
                ->first();

            if (!$job || !$job->morphable) {
                throw ValidationException::withMessages(['job_id' => ['No RGR found for this job.']]);
            }

            if ($job->status === 'closed') {  
                throw ValidationException::withMessages(['job_id' => ['This job is closed.']]);
            }

            $rgr = $job->morphable;

            $scannedItems = ErpItemUniqueCode::where('job_id', $job_id)
                ->with('segregation')
                ->where('status', 'scanned')
                ->orderBy('updated_at', 'desc')
                ->paginate(CommonHelper::PAGE_LENGTH_10);

            $formattedScannedItems = $scannedItems->map(function ($uniqueCode) {
                $attributes = [];

                if ($uniqueCode->item_attributes) {
                    $attrs = is_string($uniqueCode->item_attributes)
                        ? json_decode($uniqueCode->item_attributes, true) ?? []
                        : $uniqueCode->item_attributes;

                    $attributes = array_map(fn($attr) => [
                        'attribute_name'  => $attr['attribute_name'] ?? null,
                        'attribute_value' => $attr['attribute_value'] ?? null
                    ], $attrs);
               }
                $rgrStatuses = $uniqueCode -> segregation ?-> segregation_status;

                return [
                    'id'          => $uniqueCode->id ?? "",
                    'item_id'     => $uniqueCode->item_id ?? "",
                    'item_code'   => $uniqueCode->item_code ?? "",
                    'item_name'   => $uniqueCode->item_name ?? "",
                    'attributes'  => $attributes,
                    'uid'         => $uniqueCode->uid ?? "",
                    'item_uid'    => $uniqueCode->item_uid ?? "",
                    'status'      => $uniqueCode->status ?? "",
                    'rgr_statuses'=> $rgrStatuses
                ];
            });

            $data = [
                'id'                 => $rgr?->job?->id,
                'document_no'        => ($rgr->book_code ?? '') . '-' . ($rgr->document_number ?? ''),
                'trip_no'            => $rgr->trip_no ?? "",
                'vehicle_no'         => $rgr->vehicle_no ?? "",
                'total_item'         => $job->itemUniqueCodes->count(),
                'scanned_items'      => $formattedScannedItems,
                'scanned_item_count' => $scannedItems->total(),
            ];

            $responseData = [
                'rgr' => $data,
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

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

   
  public function getDefectSeverity()
    {
        return response()->json([
            'message' => 'Data retrieved successfully.',
            'data'    => ConstantHelper::DEFECT_SEVERITY_LEVELS
        ], 200);
    }

   public function getDamageNatureOptions()
    {
        return response()->json([
            'message' => 'Data retrieved successfully.',
            'data'    => ConstantHelper::DAMAGE_NATURES
        ], 200);
    }

  public function getDefectTypes(string $severity, int $itemId)
    {
        try {
            $severity = ucfirst(strtolower($severity));

            $item = Item::find($itemId);
            if (!$item) {
                throw ValidationException::withMessages(['item_id' => ['The provided item ID does not exist.']]);
            }

            $subcategory_id = $item->subcategory_id;

            $defectType = ErpRgrDefectType::where('category_id', $subcategory_id)
                ->where('defect_severity', $severity)
                ->first();

            if (!$defectType) {
                $defectType = ErpRgrDefectType::whereNull('category_id')
                    ->where('defect_severity', $severity)
                    ->first();
            }

            if (!$defectType) {
                // throw ValidationException::withMessages(['defect_type' => ['No matching defect type found for this category and severity.']]);
                $reasons = [
                    [
                        'id' => 1,
                        'reason' => 'Component Missing'
                    ],
                    [
                        'id' => 2,
                        'reason' => 'Major Damage'
                    ],
                    [
                        'id' => 3,
                        'reason' => 'Full Hardware Missing'
                    ]
                ];
                return [
                    'message' => 'Successfully retrieved defect reasons.',
                    'data'    => $reasons,
                ];
            }

            $reasons = ErpRgrDefectTypeDetail::select('id', 'reason')
                ->where('header_id', $defectType->id)
                ->get();

            return [
                'message' => 'Successfully retrieved defect reasons.',
                'data'    => $reasons,
            ];

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function getItems(Request $request)
    {
        try {
            $searchTerm = $request->query('search');

            $query = Item::where('status', ConstantHelper::ACTIVE)
                        ->where('type', 'Goods');

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
                throw ValidationException::withMessages(['items' => ['No Goods items found.']]);
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

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

  public function getAttributesByItemId($itemId)
    {
        try {
            $item = Item::where('id', $itemId)
                        ->where('status', ConstantHelper::ACTIVE)
                        ->first();

            if (!$item) {
                throw ValidationException::withMessages(['item' => ['Item not found or not active.']]);
            }

            $itemAttributes = ItemAttribute::with('attributeGroup')
                ->where('item_id', $itemId)
                ->orderBy('id', 'asc')
                ->get();

            if ($itemAttributes->isEmpty()) {
                throw ValidationException::withMessages(['attributes' => ['No attributes found for this item.']]);
            }

            $attributesByGroup = [];

            foreach ($itemAttributes as $itemAttribute) {
                $attributeGroup = $itemAttribute->attributeGroup;
                if (!$attributeGroup) continue;

                $attributeGroupId = $attributeGroup->id;
                $attributeGroupName = $attributeGroup->name;
                $attributeIds = is_array($itemAttribute->attribute_id)
                    ? $itemAttribute->attribute_id
                    : json_decode($itemAttribute->attribute_id, true);

                $attributes = Attribute::whereIn('id', $attributeIds)
                    ->get(['id', 'value']);

                if (!isset($attributesByGroup[$attributeGroupId])) {
                    $attributesByGroup[$attributeGroupId] = [
                        'attr_name' => $attributeGroupId,
                        'attribute_name' => $attributeGroupName,
                        'options' => [],
                    ];
                }

                foreach ($attributes as $attribute) {
                    $attributesByGroup[$attributeGroupId]['options'][] = [
                        'attr_value' => $attribute->id,
                        'attribute_value' => $attribute->value,
                    ];
                }
            }

            $response = array_values($attributesByGroup);

            return [
                'message' => 'Data retrieved successfully.',
                'data' => $response,
            ];

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }


   public function scanItem($item_uid, $job_id)
    {
        try {
            $job = ErpWhmJob::where('id', $job_id)
                ->where('morphable_type', ErpRgr::class)
                ->with('morphable') 
                ->first();

            if (!$job || !$job->morphable) {
                throw ValidationException::withMessages([
                    'job' => ['Job or associated RGR not found.']
                ]);
            }

            $rgr = $job->morphable;

            $uniqueItem = ErpItemUniqueCode::where('item_uid', $item_uid)
                ->where('job_id', $job_id)
                ->first();

            if (!$uniqueItem) {
                throw ValidationException::withMessages([
                    'item' => ['Item not found.']
                ]);
            }

            if ($uniqueItem->status === 'scanned') {
                throw ValidationException::withMessages([
                    'item' => ['Item already scanned.']
                ]);
            }

            $attributes = [];
           if ($uniqueItem->item_attributes) {
                $attrs = is_string($uniqueItem->item_attributes) 
                    ? json_decode($uniqueItem->item_attributes, true) ?? [] 
                    : (array) $uniqueItem->item_attributes;

                $attributes = array_map(fn($attr) => [
                    'attribute_name'  => $attr['attribute_name'] ?? null,
                    'attribute_value' => $attr['attribute_value'] ?? null,
                ], $attrs);
            }

            $delivery_cancel = false;
            $replacement_item = false;

            $rgrItem = ErpRgrItem::find($uniqueItem->morphable_id);
            if ($rgrItem && $rgrItem->pickup_item_id) {
                $pickupItem = ErpPickupItem::find($rgrItem->pickup_item_id);
                if ($pickupItem) {
                    $delivery_cancel  = strtolower($pickupItem->delivery_cancelled ?? '') === 'yes';
                    $replacement_item = strtolower($pickupItem->replacement_item ?? '') === 'yes';
                }
            }

            return [
                'message' => 'Data retrieved successfully.',
                'data' => [
                    'id'               => $uniqueItem->id,
                    'job_id'           => $job_id, 
                    'item_id'          => $uniqueItem->item_id,
                    'item_code'        => $uniqueItem->item_code,
                    'item_name'        => $uniqueItem->item_name,
                    'item_uid'         => $uniqueItem->item_uid,
                    'uid'              => $uniqueItem->uid,
                    'status'           => $uniqueItem->status,
                    'attributes'       => $attributes,
                    'label_status'     => true,
                    'delivery_cancel'  => $delivery_cancel,
                    'packing_status'   => true,
                    'replacement_item' => $replacement_item,
                ],
            ];

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
  }
   public function createSegregation(Request $request)
    {
        DB::beginTransaction();
        try {

            $requestData = $request->input('request');

            if (is_string($requestData)) {
                $requestData = json_decode($requestData, true);
              
                if ($requestData === null) {
                    throw ValidationException::withMessages(['request' => ['Invalid JSON format']]);
                }
            }
            if (!$requestData) {
                throw ValidationException::withMessages(['request' => ['Invalid or empty request payload.']]);
            }

          $validator = Validator::make($requestData, [
            'segregation_id' => 'nullable|exists:erp_rgr_item_segregations,id',
            'unique_item_id' => 'nullable|exists:erp_item_unique_codes,id',
            
            'job_id' => [
                Rule::requiredIf(function () use ($requestData) {
                    return empty($requestData['segregation_id']) && empty($requestData['unique_item_id']);
                }),
                'exists:erp_whm_jobs,id',
            ],

            'item_id' => [
                Rule::requiredIf(function () use ($requestData) {
                    return !empty($requestData['job_id']);
                }),
                'exists:erp_items,id',
            ],

            'item_attributes' => 'nullable|array',
            'label_status' => 'nullable|boolean',
            'delivery_cancel' => 'nullable|boolean',
            'packing_status' => 'nullable|boolean',
            'defect_severity' => 'nullable',
            'defect_type' => 'nullable|string',
            'damage_nature' => 'nullable',
            'remarks' => 'nullable|string',
            'new_item_id' => 'nullable|exists:erp_items,id',
            'new_item_attributes' => 'nullable|array',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|mimes:png,jpeg,jpg,svg,webp|max:2048',

        ], [
            'segregation_id.exists' => 'The selected segregation_id is invalid.',
            'unique_item_id.exists' => 'The selected unique_item_id is invalid.',
            'job_id.required' => 'Job ID is required when unique_item_id are not present.',
            'job_id.exists' => 'The selected job_id is invalid.',
            'item_id.required' => 'Item ID is required when job_id is provided.',
            'item_id.exists' => 'The selected item_id is invalid.',
            'new_item_id.exists' => 'The selected new_item_id is invalid.',
            'files.*.mimes' => 'Each file must be a valid image of type png, jpeg, jpg, svg, or webp.',
            'files.*.max' => 'Each file must not exceed 2MB in size.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

          $validated = $validator->validated();

         if ($request->hasFile('files')) {
            $files = $request->file('files');

            if (!is_array($files)) {
                throw ValidationException::withMessages(['files' => ['Files must be an array.']]);
            }

            if (count($files) > 5) {
                throw ValidationException::withMessages(['files' => ['You can upload maximum 5 files.']]);
            }

            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    throw ValidationException::withMessages(["files.$index" => ['Uploaded file is not valid.']]);
                }

                $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml', 'image/webp'];
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    throw ValidationException::withMessages(["files.$index" => ['Each file must be a valid image type: png, jpeg, jpg, svg, webp.']]);
                }

                if ($file->getSize() > 2 * 1024 * 1024) { 
                    throw ValidationException::withMessages(["files.$index" => ['Each file must not exceed 2MB in size.']]);
                }
            }
           }

            if (!empty($requestData['segregation_id'])) {
                $segregation = ErpRgrItemSegregation::find($requestData['segregation_id']);
                $uniqueItem = ErpItemUniqueCode::find($segregation->job_item_id);
            }

            elseif (!empty($requestData['unique_item_id'])) {
                $uniqueItem = ErpItemUniqueCode::find($requestData['unique_item_id']);
                if (!$uniqueItem) {
                    throw ValidationException::withMessages(['unique_item_id' => ['Item not found.']]);
                }
                if ($uniqueItem->status === 'scanned') {
                    throw ValidationException::withMessages(['unique_item_id' => ['Item has already been scanned.']]);
                }
                $job = ErpWhmJob::find($uniqueItem->job_id);
                if (!$job) {
                    throw ValidationException::withMessages(['job' => ['Job not found for this unique item.']]);
                }
                 // ---------- New: Check if job is closed ----------
                if ($job->status === 'closed') {
                    throw ValidationException::withMessages(['job' => ['Job for this item is already closed.']]);
                }
                $segregation = ErpRgrItemSegregation::where('job_item_id', $uniqueItem->id)->first();
            }

            elseif (!empty($requestData['job_id'])) {
                $job = ErpWhmJob::where('id', $requestData['job_id'])
                    ->where('morphable_type', ErpRgr::class)
                    ->first();
                if (!$job) throw ValidationException::withMessages(['job' => ['Job not found for this item.']]);

                $item = Item::find($requestData['item_id']);
                if (!$item) throw ValidationException::withMessages(['item_id'=>['Item not found.']]);

                $subStoreId = null;
                $categoryId = $item->subcategory_id ?? null;

                if ($job->store_id && $categoryId) {
                    $storeMapping = ErpRgrStoreMapping::where('store_id', $job->store_id)
                        ->where('category_id', $categoryId)
                        ->first();
                      
                    $subStoreId = $storeMapping ? $storeMapping->sub_store_id : null;
                }

                $rgr = ErpRgr::find($job->morphable_id);
                if (!$rgr) throw ValidationException::withMessages(['job_id'=>['RGR not found for this job.']]);

                $itemAttributes = RepHelper::validateItemAttributes($requestData['item_attributes'], $item->id, false);
                $itemAttributesJson = !empty($itemAttributes) 
                    ? json_encode($itemAttributes, JSON_THROW_ON_ERROR) 
                    : null;
                  
                $uniqueItem = ErpItemUniqueCode::create([
                    'job_id' => $job->id,
                    'item_id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'item_attributes' => $itemAttributesJson,
                    'status' => 'pending',
                    'store_id' => $rgr->store_id,
                    'sub_store_id' => $subStoreId,
                    'book_id' => $rgr->book_id,
                    'book_code' => $rgr->book_code,
                    'group_id' => $rgr->group_id,
                    'company_id' => $rgr->company_id,
                    'organization_id' => $rgr->organization_id,
                    'doc_no' => $rgr->document_number,
                    'doc_date' => $rgr->document_date,
                    'trns_type' => $job->trns_type,
                    'job_type' => $job->type,
                    'doc_type' => 'receipt',
                    'type' => 'qr',
                    'uid' => (new WhmJob())->generateUniqueUid(),
                ]);

                $segregation = ErpRgrItemSegregation::where('job_item_id', $uniqueItem->id)->first();
            }
            else {
                throw ValidationException::withMessages(['job_id' => ['Job ID is required.']]);
            }

            $newItem = !empty($requestData['new_item_id']) ? Item::find($requestData['new_item_id']) : null;
            $newItemAttributesArray = !empty($requestData['new_item_attributes']) && $newItem
                ? RepHelper::validateItemAttributes($requestData['new_item_attributes'], $newItem->id, false)
                : null;
             
            $newItemAttributes = !empty($newItemAttributesArray) 
                ? json_encode($newItemAttributesArray, JSON_THROW_ON_ERROR) 
                : null;

            if (!$segregation) {
                $segregation = ErpRgrItemSegregation::create([
                    'rgr_id' => $job->morphable_id,
                    'rgr_item_id' => $uniqueItem->morphable_id,
                    'job_item_id' => $uniqueItem->id,
                    'item_id' => $uniqueItem->item_id,
                    'label_status' => $requestData['label_status'] ?? 0,
                    'delivery_cancel' => $requestData['delivery_cancel'] ?? 0,
                    'replacement_item' => $requestData['replacement_item'] ?? 0,
                    'packing_status' => $requestData['packing_status'] ?? 0,
                    'defect_severity' => $requestData['defect_severity'] ?? 'Minor',
                    'defect_type' => $requestData['defect_type'] ?? 'component_missing',
                    'damage_nature' => $requestData['damage_nature'] ?? 'No Damage',
                    'remarks' => $requestData['remarks'] ?? null,
                    'new_item_id' => $newItem?->id,
                    'new_item_code' => $newItem?->item_code,
                    'new_item_name' => $newItem?->item_name,
                    'new_item_attributes' => $newItemAttributes,
                ]);
               if ($request->hasFile('files')) {
                    $segregation->uploadDocuments($request->file('files'), 'images');
                }
                $message = 'Segregation created successfully.';
            } else {
                $segregation->update([
                    'label_status' => $requestData['label_status'] ?? $segregation->label_status,
                    'delivery_cancel' => $requestData['delivery_cancel'] ?? $segregation->delivery_cancel,
                    'replacement_item' => $requestData['replacement_item'] ?? $segregation->replacement_item, 
                    'packing_status' => $requestData['packing_status'] ?? $segregation->packing_status,
                    'defect_severity' => $requestData['defect_severity'] ?? $segregation->defect_severity,
                    'defect_type' => $requestData['defect_type'] ?? $segregation->defect_type,
                    'damage_nature' => $requestData['damage_nature'] ?? $segregation->damage_nature,
                    'remarks' => $requestData['remarks'] ?? $segregation->remarks,
                    'new_item_id' => $newItem?->id,
                    'new_item_code' => $newItem?->item_code,
                    'new_item_name' => $newItem?->item_name,
                    'new_item_attributes' => $newItemAttributes ?? $segregation->new_item_attributes,
                ]);
                $message = 'Segregation updated successfully.';
            }


            if (!empty($request->files) && is_array($request->files)) {
                $segregation->uploadDocuments($request->files, 'images');
            }

            $uniqueItem->status = 'scanned';
            $uniqueItem->save();

            DB::commit();

            return [
                'message' => $message,
                'data' => [
                    'segregation_id' => $segregation->id,
                     'unique_item' => [
                        'id' => $uniqueItem->id,
                        'item_code' => $uniqueItem->item_code,
                        'item_name' => $uniqueItem->item_name,
                        'uid' => $uniqueItem->uid,
                        'status' => $uniqueItem->status,
                        'item_attributes' => $uniqueItem->item_attributes,
                    ]
                ]
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function fetchManualItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'job_id'          => 'required|exists:erp_whm_jobs,id',  
                'item_id'         => 'required|exists:erp_items,id',
                'item_attributes' => 'nullable|array',
            ]);

            $item = Item::find($validated['item_id']);
            if (!$item) {
                throw ValidationException::withMessages(['item_id' => ['Item not found.']]);
            }

            $attributes = $validated['item_attributes'] ?? json_decode($item->item_attributes ?? '[]', true);

            return [
                'message' => 'Item data retrieved successfully.',
                'data' => [
                    'job_id'           => $validated['job_id'],  
                    'item_id'          => $item->id,
                    'item_code'        => $item->item_code,
                    'item_name'        => $item->item_name,
                    'attributes'       => $attributes,
                    'label_status'     => false,
                    'delivery_cancel'  => false,
                    'packing_status'   => false,
                    'replacement_item' => false,
                ],
            ];
        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

    public function getWrongItemDetails(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();

            $validated = $request->validate([
                'item_id'         => 'required|exists:erp_items,id',
                'item_code'       => 'required|string|max:50',
                'item_name'       => 'required|string|max:199',
                'item_attributes' => 'nullable|array',
            ]);

            $item = Item::find($validated['item_id']);
            if (!$item) {
                throw ValidationException::withMessages(['item_id' => ['Item not found.']]);
            }
            $attributes = $request -> item_attributes;

            DB::commit();

            return [
                'message' => 'item retrieved successfully.',
                'data' => [
                    'item_id'        => $item->id,
                    'item_code'      => $item->item_code,
                    'item_name'      => $item->item_name,
                    'attributes'     => $attributes,
                ],
            ];

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ApiGenericException($e->getMessage());
        }
    }

   public function getJobItemStatus($jobId)
    {
        try {
            $job = ErpWhmJob::with('itemUniqueCodes')->find($jobId);
            if (!$job) {
                return response()->json(['message' => 'Job not found', 'data' => []], 404);
            }

            $statusCounts = [
                'Total Packets'    => $job->itemUniqueCodes->count(),
                'Ok to Receive'    => 0,
                'Package Missing'  => 0,
                'Wrong Product'    => 0,
                'Delivery Cancel'  => 0,
                'Replacement Item' => 0,
                'Extra Item'       => 0,
                'Missing Item'     => 0,
                'Transit Damage'   => 0,
            ];

            foreach ($job->itemUniqueCodes as $item) {
                $seg = ErpRgrItemSegregation::where('job_item_id', $item->id)->first();
                if (!$seg) {
                    $statusCounts['Missing Item']++;
                    continue;
                }

                $flags = [
                    'Package Missing'  => !((bool)$seg->packing_status),
                    'Delivery Cancel'  => (bool)$seg->delivery_cancel,
                    'Replacement Item' => (bool)$seg->replacement_item,
                    'Transit Damage'   => $seg->damage_nature == ConstantHelper::DAMAGE_NATURE_TRANSIT_HANDLE_DAMAGE,
                    'Wrong Product'    => isset($seg->new_item_id),
                    'Extra Item'       => $seg->rgr_item_id ? false : true,
                ];

                $statusCounts['Ok to Receive'] += array_sum(array_map('intval', [
                    $flags['Package Missing'],
                    $flags['Delivery Cancel'],
                    $flags['Replacement Item'],
                    $flags['Transit Damage'],
                ]));

                foreach ($flags as $key => $val) {
                    if ($val) $statusCounts[$key]++;
                }
            }

            $data = array_map(fn($k, $v) => ['label' => $k, 'value' => $v], array_keys($statusCounts), $statusCounts);

            return response()->json(['message' => 'Data retrieved successfully.', 'data' => $data], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Something went wrong', 'error' => $e->getMessage()], 500);
        }
    }

    public function closeJob(Request $request)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'job_id' => 'required|numeric|integer',
            ]);

            $authUser = Helper::getAuthenticatedUser();
            $job = ErpWhmJob::where('morphable_type', ErpRgr::class)->with('itemUniqueCodes')->find($request -> job_id);
   
            if (!$job) {
                return response()->json([
                    'message' => 'Job not found',
                    'data' => []
                ], 404);
            }

            if ($job->status === 'closed') {
                return response()->json([
                    'message' => 'Job already closed.',
                    'data' => []
                ], 400);
            }

        $items = $job->itemUniqueCodes;

          $itemsForRca = collect();

            $missingItems = $items->filter(function($item) {
                return !ErpRgrItemSegregation::where('job_item_id', $item->id)->exists();
            });

            $extraItems = $items->filter(function($item) {
                return ErpRgrItemSegregation::where('job_item_id', $item->id)->exists() && is_null($item->morphable_id);
            });

            $itemsForRca = $missingItems->merge($extraItems);

            if ($itemsForRca->isNotEmpty()) {
                $status = MultiRCAHelper::generateRcaFromRgrItems(
                    $itemsForRca,
                    ServiceParametersHelper::RCA_MISSING_EXTRA_ITEMS_PARAM,
                    $authUser,
                    true
                );

                if ($status['status'] === 'error') {
                    DB::rollBack();
                    throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                }
            }

           foreach ($items as $item) {
                $segregation = ErpRgrItemSegregation::where('job_item_id', $item->id)->first();
                if (!$segregation) continue;

                $statuses = $segregation->segregation_status ?? [];

                // Pack Missing
                if ( in_array(RGRConstants::RGR_SEGREGATION_PACK_MISSING, $statuses)){
                    $status = RCAHelper::generateRcaFromRgrItem($item, RGRConstants::RGR_SEGREGATION_PACK_MISSING,ServiceParametersHelper::RCA_PACKAGE_MISSING_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                    $status = RepHelper::generateRepFromRgrItem($item, ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                }

                // Wrong Product

                if (in_array(RGRConstants::RGR_SEGREGATION_WRONG_PRODUCT, $statuses)){
                    $status = RCAHelper::generateRcaFromRgrItem($item, RGRConstants::RGR_SEGREGATION_WRONG_PRODUCT,ServiceParametersHelper::RCA_WRONG_PRODUCT_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                }

                // Delivery Cancel

                if (in_array(RGRConstants::RGR_SEGREGATION_DELIVERY_CANCEL, $statuses)) {
                    $status = RCAHelper::generateRcaFromRgrItem($item, RGRConstants::RGR_SEGREGATION_DELIVERY_CANCEL,ServiceParametersHelper::RCA_DELIVERY_CANCEL_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                    $status = RepHelper::generateRepFromRgrItem($item, ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                }

                // Replacement Item
                if (in_array(RGRConstants::RGR_SEGREGATION_REPLACEMENT_ITEM, $statuses)) {
                    $status = RCAHelper::generateRcaFromRgrItem( $item, RGRConstants::RGR_SEGREGATION_REPLACEMENT_ITEM, ServiceParametersHelper::RCA_REPLACEMENT_ITEM_PARAM,true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                    $status = RepHelper::generateRepFromRgrItem($item, ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                }

                // Transit Damage
                if (in_array(RGRConstants::RGR_SEGREGATION_TRANSIT_DAMAGE, $statuses)) {
                    $status = RCAHelper::generateRcaFromRgrItem($item, RGRConstants::RGR_SEGREGATION_TRANSIT_DAMAGE,ServiceParametersHelper::RCA_TRANSIT_DAMAGE_PARAM,$authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                    $status = RepHelper::generateRepFromRgrItem($item, ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
                }

               if (count(array_intersect(RGRConstants::RGR_STATUSES, $statuses)) == 0){
                   $status = RepHelper::generateRepFromRgrItem($item, ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM, $authUser, true);
                    if ($status['status'] === 'error') {
                        DB::rollBack();
                        throw ValidationException::withMessages(['job_id' => [$status['message']]]);
                    }
               }
                
            }

            $job->status='closed';
            $job->job_closed_at = now(); 
            $job->save();

            DB::commit();
            return response()->json([
                'message' => 'Job closed successfully.',
                'data' => []
                ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new ApiGenericException($e -> getMessage());
        }
    }

    
   public function getSegregationByUniqueItemId($uniqueItemId)
    {
        $uniqueItem = ErpItemUniqueCode::find($uniqueItemId);

        if (!$uniqueItem) {
            throw ValidationException::withMessages([
                'unique_item_id' => ['Unique item not found.']
            ]);
        }

        $segregation = ErpRgrItemSegregation::where('job_item_id', $uniqueItem->id)
            ->with('media') 
            ->first();

        if (!$segregation) {
            throw ValidationException::withMessages([
                'segregation' => ['No segregation found for this item.']
            ]);
        }

        $itemAttributes = [];
        if ($uniqueItem->item_attributes) {
            $attrs = is_string($uniqueItem->item_attributes)
                ? json_decode($uniqueItem->item_attributes, true) ?? []
                : (array) $uniqueItem->item_attributes;

            $itemAttributes = array_map(fn($attr) => [
                'attribute_name'  => $attr['attribute_name'] ?? null,
                'attribute_value' => $attr['attribute_value'] ?? null,
            ], $attrs);
        }

        $newItemAttributes = [];
        if ($segregation->new_item_attributes) {
            $attrs = is_string($segregation->new_item_attributes)
                ? json_decode($segregation->new_item_attributes, true) ?? []
                : (array) $segregation->new_item_attributes;

            $newItemAttributes = array_map(fn($attr) => [
                'attribute_name'  => $attr['attribute_name'] ?? null,
                'attribute_value' => $attr['attribute_value'] ?? null,
            ], $attrs);
        }

       $segImageUrls = $segregation->media
            ? $segregation->media->map(fn($media) => asset('storage/' . $media->file_name))->toArray()
            : [];

        $delivery_cancel = false;
        $replacement_item = false;
        $rgrItem = ErpRgrItem::find($uniqueItem->morphable_id);
        if ($rgrItem && $rgrItem->pickup_item_id) {
            $pickupItem = ErpPickupItem::find($rgrItem->pickup_item_id);
            if ($pickupItem) {
                $delivery_cancel  = strtolower($pickupItem->delivery_cancelled ?? '') === 'yes';
                $replacement_item = strtolower($pickupItem->replacement_item ?? '') === 'yes';
            }
        }

        return [
            'message' => 'Segregation details retrieved successfully',
            'data' => [
                'segregation_id'      => $segregation->id,
                'job_item_id'         => $segregation->job_item_id,
                'item_id'             => $segregation->item_id,
                'label_status'        => $segregation->label_status == 1 ? true : false,
                'delivery_cancel'     => $segregation->delivery_cancel == 1 ? true : false,
                'packing_status'      => $segregation->packing_status == 1 ? true : false,
                'replacement_item'    => $replacement_item, 
                'defect_severity'     => $segregation->defect_severity,
                'defect_type'         => $segregation->defect_type,
                'damage_nature'       => $segregation->damage_nature,
                'remarks'             => $segregation->remarks,
                'new_item_id'         => $segregation->new_item_id,
                'new_item_code'       => $segregation->new_item_code,
                'new_item_name'       => $segregation->new_item_name,
                'new_item_attributes' => $newItemAttributes,
                'seg_image_urls'      => $segImageUrls,

                'item' => [
                    'id'              => $uniqueItem->id,
                    'item_id'         => $uniqueItem->item_id,
                    'item_code'       => $uniqueItem->item_code,
                    'item_name'       => $uniqueItem->item_name,
                    'item_uid'        => $uniqueItem->item_uid,
                    'uid'             => $uniqueItem->uid,
                    'attributes'      => $itemAttributes,
                ],
            ]
        ];
  }

  public function deleteScannedItem($uniqueItemId)
    {
        if (!is_numeric($uniqueItemId)) {
            throw ValidationException::withMessages([
                'unique_item_id' => ['The provided ID is invalid.']
            ]);
        }

        try {
            $uniqueItem = ErpItemUniqueCode::find($uniqueItemId);

            if (!$uniqueItem) {
                throw ValidationException::withMessages([
                    'unique_item_id' => ['Unique item not found.']
                ]);
            }

            $segregation = ErpRgrItemSegregation::where('job_item_id', $uniqueItem->id)->first();
            if ($segregation) {
                $segregation->delete();
            }

            $uniqueItem->status = 'pending';
            $uniqueItem->save();

            return response()->json([
                'message' => 'Record deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {
            throw new ApiGenericException($e->getMessage());
        }
    }

}

<?php

namespace App\Http\Controllers\Plant;

use Illuminate\Http\Request;
use App\Models\DefectNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\DefectNotificationRequest;
use App\Models\Item;
use App\Models\ErpAttribute;
use App\Models\ItemAttribute;
use App\Helpers\Helper;
use App\Models\ErpEquipment;
use App\Models\Category;
use App\Models\ErpDefectType;
use App\Helpers\ConstantHelper;
use App\Models\DefectNotificationHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DefectNotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDefectNotificationsData($request);
        }

        // Fetch filter data for the view
        $parentURL = "plant_defect-noti";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        
        // Get series data
        $series = collect();
        if (count($servicesBooks['services']) > 0) {
            $firstService = $servicesBooks['services'][0];
            $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        }
        
        // Get equipment data
        $equipments = ErpEquipment::select('id', 'name')
            ->orderBy('name')
            ->get();
        
        // Get category data
        $categories = Category::select('id', 'name')
            ->where('type', 'Equipment')
            ->where('status', 'Active')
            ->orderBy('name')
            ->get();
        
        // Get organization data
        $user = Helper::getAuthenticatedUser();
        $organizationId = $user->organization_id;
        $mappings = Helper::access_org();

        return view('plant.defect-notification.index', compact('series', 'equipments', 'categories', 'mappings', 'organizationId'));
    }
    public function filter(Request $request)
    {
        $query = DefectNotification::query();

        // Apply filters only if values are provided
        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('defect_type_id')) {
            $query->where('defect_type_id', $request->defect_type_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('series')) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('book_code', $request->series);
            });
        }

        // Load related models for display
        $defects = $query->with(['equipment', 'defectType', 'book'])->get();

        return response()->json([
            'status' => true,
            'data' => $defects
        ]);
    }


    public function getDefectNotification($id)
    {
        $defectNotification = DefectNotification::with([
            'book', 
            'equipment.maintenanceDetails.maintenanceType', 
            'location', 
            'category', 
            'defectType',
        ])->findOrFail($id);

        // Get maintenance types using Eloquent relationships
        $maintenanceTypes = [];
        if ($defectNotification->equipment && $defectNotification->equipment->maintenanceDetails) {
            $maintenanceTypes = $defectNotification->equipment->maintenanceDetails
                ->map(function ($detail) {
                    return $detail->maintenanceType;
                })
                ->filter() 
                ->unique('id') 
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'name' => $type->name
                    ];
                })
                ->values(); 
        }

        // Get all checklists for this equipment (grouped by maintenance type)
        $checklistsByMaintenanceType = [];
        if ($defectNotification->equipment && $defectNotification->equipment->maintenanceDetails) {
            foreach ($defectNotification->equipment->maintenanceDetails as $detail) {
                if ($detail->maintenanceType && $detail->checklists->count() > 0) {
                    $checklistsByMaintenanceType[$detail->maintenance_type_id] = [
                        'maintenance_type_name' => $detail->maintenanceType->name,
                        'checklists' => $detail->checklists->map(function ($checklist) {
                            return [
                                'id' => $checklist->id,
                                'name' => $checklist->name,
                                'description' => $checklist->description,
                                'type' => $checklist->type,
                                'status' => $checklist->status
                            ];
                        })
                    ];
                }
            }
        }

        return response()->json([
            'status' => true,
            'data' => array_merge(
                $defectNotification->toArray(),
                ['reported_by' => auth()->user()->name ?? 'N/A']
            ),
            'maintenance_types' => $maintenanceTypes,
            'checklists_by_maintenance_type' => $checklistsByMaintenanceType
        ]);
    }



    /**
     * Get defect notifications data for DataTables Ajax
     */
    public function getDefectNotificationsData(Request $request)
    {
        $query = DefectNotification::with(['equipment', 'location', 'category', 'defectType', 'book'])
            ->select([
                'id', 'document_date', 'book_id', 'doc_no', 'equipment_id', 'category_id', 'location_id', 
                'defect_type_id', 'problem', 'priority', 'document_status', 'revision_number', 'organization_id','document_number'
            ]);

            // Apply date range filter
            if ($request->filled('date_range')) {
                $dateRange = $request->date_range;
                if (strpos($dateRange, ' to ') !== false) {
                    $dates = explode(' to ', $dateRange);
                    if (count($dates) === 2) {
                        $query->whereBetween('document_date', [trim($dates[0]), trim($dates[1])]);
                    }
                }
            }

            // Apply series filter
            if ($request->filled('series_filter')) {
                $query->whereHas('book', function($q) use ($request) {
                    $q->where('book_code', $request->series_filter);
                });
            }

            // Apply equipment filter
            if ($request->filled('equipment_filter')) {
                $query->where('equipment_id', $request->equipment_filter);
            }

            // Apply category filter
            if ($request->filled('category_filter')) {
                $query->where('category_id', $request->category_filter);
            }

            // Apply organization filter
            if ($request->filled('filter_organization')) {
                $organizationIds = $request->filter_organization;
                if (is_array($organizationIds)) {
                    $query->whereIn('organization_id', $organizationIds);
                } else {
                    $query->where('organization_id', $organizationIds);
                }
            }

            // Apply status filter
            if ($request->filled('status_filter')) {
                $query->where('document_status', $request->status_filter);
            }

            // Apply search filter
            if ($request->has('search') && $request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function($q) use ($searchValue) {
                    $q->where('problem', 'like', "%{$searchValue}%")
                    ->orWhere('priority', 'like', "%{$searchValue}%")
                    ->orWhere('document_status', 'like', "%{$searchValue}%")
                    ->orWhere('doc_no', 'like', "%{$searchValue}%")
                    ->orWhere('document_date', 'like', "%{$searchValue}%")
                    ->orWhereRaw("DATE_FORMAT(document_date, '%d-%m-%Y') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("DATE_FORMAT(document_date, '%d/%m/%Y') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("DATE_FORMAT(document_date, '%Y-%m-%d') LIKE ?", ["%{$searchValue}%"])
                    // Handle "Approved" search for approval_not_required status
                    ->orWhere(function($statusQuery) use ($searchValue) {
                        $lowerSearchValue = strtolower($searchValue);
                        $approvedText = 'approved';
                        
                        // Check if search term matches "approved" (partial or full)
                        if (strpos($approvedText, $lowerSearchValue) !== false || strpos($lowerSearchValue, $approvedText) !== false) {
                            $statusQuery->where('document_status', 'approval_not_required');
                        }
                    })
                    ->orWhereHas('book', function($book) use ($searchValue) {
                        $book->where('book_name', 'like', "%{$searchValue}%")
                             ->orWhere('book_code', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('equipment', function($eq) use ($searchValue) {
                        $eq->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('category', function($cat) use ($searchValue) {
                        $cat->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('defectType', function($dt) use ($searchValue) {
                        $dt->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('location', function($loc) use ($searchValue) {
                        $loc->where('store_name', 'like', "%{$searchValue}%");
                    });
                });
            }

           
        $totalRecords = DefectNotification::count();
        $filteredRecords = $query->count();

        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];
            
            $columns = ['id', 'document_date', 'book_id', 'doc_no', 'equipment_id', 'category_id', 'location_id', 'defect_type_id', 'problem', 'priority', 'document_status','document_number'];
            if (isset($columns[$orderColumn])) {
                $query->orderBy($columns[$orderColumn], $orderDirection);
            }
        } else {
            $query->orderBy('document_number', 'desc')->orderBy('document_date', 'desc');
        }

        if ($request->has('start') && $request->has('length')) {
            $query->skip($request->start)->take($request->length);
        }

        $defectNotifications = $query->get();

        

        $data = [];
        foreach ($defectNotifications as $index => $notification) {
            // Debug: Log what we're getting from database
           
            $statusClass = 'badge-light-secondary';
            if (isset(ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$notification->document_status ?? 'draft'])) {
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$notification->document_status ?? 'draft'];
            }

            $statusText = $notification->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED ? 'Approved' : ucfirst($notification->document_status ?? 'draft');
            
            $statusBadge = "<span class='badge rounded-pill {$statusClass} badgeborder-radius'>{$statusText}</span>";

            // Conditional routing based on document status
            $route = ($notification->document_status == 'draft' || $notification->document_status == 'rejected') 
                ? route('defect-notification.edit', $notification->id)
                : route('defect-notification.show', $notification->id);
            
            $actions = '
                <div class="d-flex align-items-center justify-content-end">
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="' . $route . '">
                                <i data-feather="edit" class="me-50"></i>
                                <span>View</span>
                            </a>
                        </div>
                    </div>
                </div>';
            $data[] = [
                'DT_RowIndex' => $request->start + $index + 1,
                'document_date' => $notification->document_date ? '<span style="white-space: nowrap;">' . \Carbon\Carbon::parse($notification->document_date)->format('d-m-Y') . '</span>' : '-',
                'series' => $notification->book?->book_name ?? '',
                'document_number' => ($notification->document_number ?: $notification->doc_no) ? '<span style="text-align: center; display: block;">' . ($notification->document_number ?: $notification->doc_no) . '</span>' : '<span style="text-align: center; display: block;">-</span>',
                'equipment' => $notification->equipment?->name ?? '',
                'category' => $notification->category?->name ?? '',
                'location' => $notification->location?->store_name ?? '',
                'defect_type' => $notification->defectType?->name ?? '',
                'problem' => $notification->problem ?? '-',
                'priority' => $notification->priority ?? '-',
                'status' => $statusBadge,
                'action' => $actions
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentURL = "plant_defect-noti";
        $series = [];
        $defectTypes = ErpDefectType::select('id', 'name')->where('status','Active')->get();
        $equipments = ErpEquipment::select('id', 'name')->get();
        $categories = Category::orderBy('id', 'desc')
             ->with('parent', 'subCategories')
             ->where('type', strtolower(ConstantHelper::EQUIPMENT))
             ->where('status','Active')
             ->where('type','Equipment')
             ->get();
      

        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
      
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
     
        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->get();
            
        foreach ($items as $item) {
            $itemId = $item->id;

            if (isset($itemId)) {
                $itemAttributes = ItemAttribute::where('item_id', $itemId)->get();
            } else {
                $itemAttributes = [];
            }
            
            $processedData = [];
            foreach ($itemAttributes as $key => $attribute) {
                $attribute_group_id = $attribute->attribute_group_id;
                $attribute->group_name = $attribute->group?->name;

                $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                    ->select('id', 'value')
                    ->where('status', 'active')
                    ->get();

                $attribute->values_data = $attributeValueData;
                $attribute = $attribute->only(['id', 'group_name', 'values_data', 'attribute_group_id']);

                array_push($processedData, [
                    'id' => $attribute['id'], 
                    'group_name' => $attribute['group_name'], 
                    'values_data' => $attributeValueData, 
                    'attribute_group_id' => $attribute['attribute_group_id']
                ]);
            }
            
            $processedData = collect($processedData);
            $item->attributes = $processedData;
        }
        
        $items = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'uom_name' => optional($item->uom)->name,
                'uom_id' => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
            ];
        });

        $locations = \App\Helpers\InventoryHelper::getAccessibleLocations();

        return view('plant.defect-notification.create', compact('series', 'items', 'locations','defectTypes','categories','equipments'));
    }

    /**
     * Get equipment by category for filtering
     */
    public function getEquipmentByCategory(Request $request)
    {
        $categoryId = $request->category_id;
        
        if (!$categoryId) {
            // Return all equipment if no category selected
            $equipments = ErpEquipment::select('id', 'name')
                ->orderBy('name')
                ->get();
        } else {
            // Filter equipment by category
            $equipments = ErpEquipment::select('id', 'name')
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->get();
        }
        
        return response()->json([
            'status' => 'success',
            'equipments' => $equipments
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DefectNotificationRequest $request)
    {
        try {
            DB::beginTransaction();
    
            $user = Helper::getAuthenticatedUser();
    
            $data = [
                'book_id'         => $request->book_id,
                'document_number' => $request->document_number,
                'document_date'   => $request->document_date,
                'equipment_id'    => $request->equipment_id,
                'created_by'      => $user->auth_user_id,
                'type'            => get_class($user),
                'organization_id' => $user->organization->id,
                'group_id'        => $user->organization->group_id,
                'company_id'      => $user->organization->company_id,
                'approval_level'  => 1,
                'revision_number' => 0,
                'document_status' => $request->document_status ?? ConstantHelper::DRAFT,
                'doc_no'          => $request->doc_no,
                'problem'        =>  $request->problem,
                'priority'        =>  $request->priority,
                'report_date_time' => $request->report_date_time,
                'location_id'     => $request->location_id,
                'category_id'     => $request->category_id,
                'defect_type_id'  => $request->defect_type_id,
            ];

            if($request->doc_no==''){
                $data['doc_no'] = $request->document_number ;
            }
    
            $defectNotification = DefectNotification::create($data);
    
            if (!$defectNotification || !$defectNotification->id) {
                throw new \Exception('Defect Notification could not be created or ID missing.');
            }
    
            if ($request->hasFile('attachment')) {
                $attachmentPaths = [];
                foreach ($request->file('attachment') as $index => $file) {
                    $fileName = 'defect_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('defect_notifications/documents', $fileName, 'public');
                    $attachmentPaths[] = $path;
                }
                $defectNotification->attachment = json_encode($attachmentPaths);
                $defectNotification->save();
            }
    
            if ($request->document_status === ConstantHelper::SUBMITTED) {
                $bookId        = $defectNotification->book_id;
                $docId         = $defectNotification->id;
                $remarks       = $request->remarks ?? '';
                $attachments   = '';
                $currentLevel  = $defectNotification->approval_level ?? 1;
                $revisionNo    = $defectNotification->revision_number ?? 0;
                $actionType    = 'submit';
                $modelName     = get_class($defectNotification);
                $totalValue    = 0;
    
                $approveDocument = Helper::approveDocument(
                    $bookId,
                    $docId,
                    $revisionNo,
                    $remarks,
                    $attachments,
                    $currentLevel,
                    $actionType,
                    $totalValue,
                    $modelName
                );
    
                $defectNotification->document_status = $approveDocument['approvalStatus'] ?? ConstantHelper::SUBMITTED;
                $defectNotification->save();
            }
    
            DB::commit();
    
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Defect Notification created successfully!',
                    'defect_notification_id' => $defectNotification->id,
                    'redirect_url' => route('defect-notification.index')
                ], 200);
            }
    
            return redirect()
                ->route('defect-notification.index')
                ->with('success', 'Defect Notification created successfully!');
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating defect notification: ' . $e->getMessage());
    
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'Something went wrong'
                ], 500);
            }
    
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create defect notification: ' . $e->getMessage());
        }
    }
    

    public function show(Request $request, string $id)
    {
       
        $defectNotification = DefectNotification::findOrFail($id);
       
        $currNumber = $request->has('revisionNumber');
        
        if ($currNumber && $defectNotification->revision_number != $request->revisionNumber) {
            $currNumber = $request->revisionNumber;
            $defectNotification = DefectNotificationHistory::where('source_id', $id)
                ->where('revision_number', $currNumber)->first();
        }

      
      

        $parentURL = "plant_defect-noti";
        $series = [];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

        $userType = Helper::userCheck();
        $revision_number = $defectNotification->revision_number;
      

        $buttons = Helper::actionButtonDisplay(
            $defectNotification->book_id,
            $defectNotification->document_status,
            $id,
            0,
            $defectNotification->approval_level,
            $defectNotification->created_by ?? 0,
            $userType['type'],
            $revision_number
        );

       
             
        $revNo = $request->has('revisionNumber') 
            ? intval($request->revisionNumber) 
            : $defectNotification->revision_number;
        
        // If a specific revision is requested, fetch that revision's data
        if ($request->has('revisionNumber') && $revNo != $defectNotification->revision_number) {
            $revisionDefectNotification = DefectNotification::where('doc_no', $defectNotification->doc_no)
                ->where('revision_number', $revNo)
                ->first();
            
            if ($revisionDefectNotification) {
                $defectNotification = $revisionDefectNotification;
            }
        }
            
        $approvalHistory = Helper::getApprovalHistory(
            $defectNotification->book_id, 
            $id, 
            $revNo, 
            0,
            $defectNotification->created_by
        );
        
        // Get all revision numbers for this document for the dropdown
        $revisionHistory = DefectNotification::where('doc_no', $defectNotification->doc_no)
            ->select('revision_number')
            ->orderBy('revision_number', 'desc')
            ->pluck('revision_number')
            ->toArray();
       

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$defectNotification->document_status] ?? '';
        
        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->get();
        foreach ($items as $item) {
            $itemId = $item->id;

            if (isset($itemId)) {
                $itemAttributes = ItemAttribute::where('item_id', $itemId)->get();
            } else {
                $itemAttributes = [];
            }
            $processedData = [];
            foreach ($itemAttributes as $key => $attribute) {
                $attributesArray = array();
                $attribute_group_id = $attribute->attribute_group_id;
                $attribute->group_name = $attribute->group?->name;

                $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)->select('id', 'value')->where('status', 'active')->get();

                $attribute->values_data = $attributeValueData;
                $attribute = $attribute->only(['id', 'group_name', 'values_data', 'attribute_group_id']);

                array_push($processedData, ['id' => $attribute['id'], 'group_name' => $attribute['group_name'], 'values_data' => $attributeValueData, 'attribute_group_id' => $attribute['attribute_group_id']]);
            }
            $processedData = collect($processedData);

            $item->attributes = $processedData;
        }
        $items = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'uom_name' => optional($item->uom)->name,
                'uom_id' => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
            ];
        });
        // Load categories, equipments, and defect types for the show view
        $categories = Category::orderBy('id', 'desc')
            ->with('parent', 'subCategories')
            ->where('type', strtolower(ConstantHelper::EQUIPMENT))
            ->get();
            
        $equipments = ErpEquipment::orderBy('id', 'desc')->get();
        $defectTypes = ErpDefectType::orderBy('id', 'desc')->get();
        $locations = \App\Helpers\InventoryHelper::getAccessibleLocations();

        return view('plant.defect-notification.show', compact(
            'series', 
            'items', 
            'defectNotification', 
            'buttons', 
            'docStatusClass', 
            'revision_number', 
            'currNumber', 
            'approvalHistory',
            'revisionHistory',
            'categories',
            'equipments', 
            'defectTypes',
            'locations'
        ));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $defectNotification = DefectNotification::findOrFail($id);

        $userType = Helper::userCheck();
        $buttons = Helper::actionButtonDisplay(
            $defectNotification->book_id,
            $defectNotification->document_status,
            $id,
            0,
            $defectNotification->approval_level,
            $defectNotification->created_by ?? 0,
            $userType['type'],
            $defectNotification->revision_number
        );
        
        if ($defectNotification->document_status === ConstantHelper::DRAFT || $defectNotification->document_status === ConstantHelper::SUBMITTED)
            $buttons['cancel'] = true;
        else
            $buttons['cancel'] = false;

        if ($defectNotification->document_status === ConstantHelper::POSTED)
            $buttons['amend'] = false;


        $defectTypes = ErpDefectType::select('id', 'name')->where('status','Active')->get();
        $equipments = ErpEquipment::select('id', 'name')->get();
        $categories = Category::orderBy('id', 'desc')
             ->with('parent', 'subCategories')
             ->where('type', strtolower(ConstantHelper::EQUIPMENT))
             ->where('status','Active')
             ->where('type','Equipment')
             ->get();
        
        $parentURL = "plant_defect-noti";
        $series = [];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_code' => $item->item_code,
                    'item_name' => $item->item_name,
                    'uom_name' => optional($item->uom)->name,
                    'uom_id' => optional($item->uom)->id,
                ];
            });

        $locations = \App\Helpers\InventoryHelper::getAccessibleLocations();


        $data = $defectNotification;
        $userType = Helper::userCheck();
        $revision_number = $data->revision_number;

        $buttons = Helper::actionButtonDisplay(
            $data->book_id,
            $data->document_status,
            $id,
            0,
            $data->approval_level,
            $data->created_by ?? 0,
            $userType['type'],
            $revision_number
        );

        $approvalHistory = Helper::getApprovalHistory(
            $data->book_id,
            $id,
            $revision_number,
            0,
            $data->created_by ?? 0
        );

        return view('plant.defect-notification.edit', compact('defectNotification', 'series','buttons','approvalHistory','items', 'locations', 'defectTypes', 'equipments', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       
        $defectNotification = DefectNotification::findOrFail($id);
        $rules = [
            'document_date' => 'required|date',
            'location_id' => 'required',
        ];


        if ($request->document_status !== 'draft') {
            $rules = array_merge($rules, [
                'equipment_id' => 'required',
                'category_id' => 'required',
                'defect_type_id' => 'required',
                'priority' => 'required|in:Low,Medium,High,Critical',
                'problem' => 'required|string',
                'report_date_time' => 'required|date',
            ]);
        }

        // Add file validation if files are uploaded
        if ($request->hasFile('attachment')) {
            $rules['attachment.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max per file
        }

        $request->validate($rules);

        DB::beginTransaction();

        try {
            if ($request->action_type == "amendment") {
                // Validate amendment remarks
                $request->validate([
                    'amend_remarks' => 'required|string|max:1000',
                    'amend_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240'
                ]);

                $revisionData = [
                    [
                        "model_type" => "header",
                        "model_name" => "DefectNotification",
                        "relation_column" => "",
                    ],
                ];
                
                // Create DefectNotificationHistory record before amendment
                $amendmentResult = Helper::documentAmendment($revisionData, $id);
                
                if (!$amendmentResult) {
                    throw new \Exception('Failed to create DefectNotificationHistory record during amendment');
                }
                
                $approveDocument=Helper::approveDocument(
                    $defectNotification->book_id,
                    $defectNotification->id,
                    $defectNotification->revision_number,
                    $request->amend_remarks,
                    $request->file('amend_attachment'),
                    $defectNotification->approval_level,
                    'amendment',
                    0,
                    get_class($defectNotification)
                );
               

                $defectNotification->revision_number = $defectNotification->revision_number + 1;
                $defectNotification->revision_date = now();
                $defectNotification->save();
                DB::commit();
            }
           
                $revisionNumber = $defectNotification->revision_number ?? 0;
                $actionType = 'submit';
                $remarks='';
                $attachments='';
                $currentLevel=$defectNotification->approval_level;
                $modelName=get_class($defectNotification);
                $totalValue = $defectNotification->grand_total_amount ?? 0;
                $approveDocument = Helper::approveDocument($defectNotification->book_id, $defectNotification->id, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                $document_status = $approveDocument['approvalStatus'] ?? $defectNotification->document_status;
                $defectNotification->document_status = $document_status;
                $data['document_status'] = $document_status;
                $defectNotification->save();
          
        
            // Handle file attachments
            $finalAttachments = [];
            
            // Get existing attachments that weren't deleted
            if ($request->has('existing_attachments') && !empty($request->existing_attachments)) {
                $existingAttachments = json_decode($request->existing_attachments, true);
                if (is_array($existingAttachments)) {
                    $finalAttachments = array_merge($finalAttachments, $existingAttachments);
                } else if (is_string($existingAttachments) && !empty($existingAttachments)) {
                    // Handle legacy single file format
                    $finalAttachments[] = $existingAttachments;
                }
            }
            
            // Add new uploaded files
            if ($request->hasFile('attachment')) {
                $newAttachmentPaths = [];
                foreach ($request->file('attachment') as $index => $file) {
                    $fileName = 'defect_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('defect_notifications/documents', $fileName, 'public');
                    $newAttachmentPaths[] = $path;
                }
                $finalAttachments = array_merge($finalAttachments, $newAttachmentPaths);
            }
            
            // Delete files that were removed
            if ($defectNotification->attachment) {
                $oldAttachments = json_decode($defectNotification->attachment, true);
                if (!is_array($oldAttachments)) {
                    $oldAttachments = [$defectNotification->attachment];
                }
                
                // Find files to delete (files that were in old but not in final)
                $filesToDelete = array_diff($oldAttachments, $finalAttachments);
                foreach ($filesToDelete as $fileToDelete) {
                    if (\Storage::disk('public')->exists($fileToDelete)) {
                        \Storage::disk('public')->delete($fileToDelete);
                    }
                }
            }
            
            // Update attachment field
            if (!empty($finalAttachments)) {
                $defectNotification->attachment = json_encode($finalAttachments);
            } else {
                $defectNotification->attachment = null;
            }

            $defectNotification->fill($request->except(['_token', '_method', 'upload_document', 'attachment','document_status']));        
            $defectNotification->save();
            $defectNotification = DefectNotification::find($id);
            
            DB::commit();
            
            return redirect()
                ->route('defect-notification.index')
                ->with('success', 'Defect Notification updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating defect notification: ' . $e->getMessage());
            
            return redirect()
                ->route('defect-notification.edit', $id)
                ->withInput()
                ->with('error', 'Failed to update defect notification: ' . $e->getMessage());
        }
    }



    /**
     * Handle amendment request
     */
    public function amendment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $defectNotification = DefectNotification::find($id);
            if (!$defectNotification) {
                return response()->json(['data' => [], 'message' => "Defect Notification not found.", 'status' => 404]);
            }

            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'DefectNotification', 'relation_column' => '']
            ];

            // $a = Helper::documentAmendment($revisionData, $id);
            // if ($a) {
            //     Helper::approveDocument($defectNotification->book_id, $defectNotification->id, $defectNotification->revision_number, 'Amendment', $request->file('attachment'), $defectNotification->approval_level, 'amendment');

            //     $defectNotification->revision_date = now();
            //     $defectNotification->save();
            // }

            DB::commit();
            return response()->json(['data' => [], 'message' => "Amendment processed successfully.", 'status' => 200]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Amendment error: ' . $e->getMessage());
            return response()->json(['data' => [], 'message' => "Amendment failed: " . $e->getMessage(), 'status' => 500]);
        }
    }

    /**
     * Handle approval/rejection of defect notification
     */
    public function approval(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:255',
            'attachment.*' => 'nullable|file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120', // 5MB max per file
        ]);
        DB::beginTransaction();
        try {
            $doc = DefectNotification::findOrFail($request->id);
            $bookId = $doc->book_id;
            $docId = $doc->id;
            $docValue = 0;
            $remarks = $request->remarks;
            $attachments = $request->file('attachment');
            $currentLevel = $doc->approval_level;
            $revisionNumber = $doc->revision_number ?? 0;
            $actionType = $request->action_type; // Approve or reject
            $modelName = get_class($doc);
            $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $docValue, $modelName);
            $doc->approval_level = $approveDocument['nextLevel'];
            $doc->document_status = $approveDocument['approvalStatus'];
            $doc->save();

            DB::commit();

            return response()->json([
                'message' => "Maintenance BOM {$actionType}d successfully!",
                'data' => $doc,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Error occurred while processing {$request->action_type}",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke defect notification
     */
    public function revoke(Request $request)
    {
        DB::beginTransaction();
        try {
            $defectNotification = DefectNotification::find($request->id);

            if (!$defectNotification) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Document found'
                ]);
            }

            // Strict validation: once amended, cannot be revoked
            if ($defectNotification->revision_number > 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'This document has already been amended and cannot be revoked.',
                ]);
            }

            $revoke = Helper::approveDocument(
                $defectNotification->book_id,
                $defectNotification->id,
                $defectNotification->revision_number,
                '',
                null,
                1,
                ConstantHelper::REVOKE,
                0,
                get_class($defectNotification)
            );

            if ($revoke['message']) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $revoke['message'],
                ]);
            }

            $defectNotification->document_status = $revoke['approvalStatus'];
            $defectNotification->save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Revoked successfully',
            ]);

        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $ex->getMessage()
            ]);
        }
    }

    /**
     * Cancel defect notification
     */
    public function cancel(Request $request)
    {
        DB::beginTransaction();
        try {
            $defectNotification = DefectNotification::find($request->id);
            if (isset($defectNotification)) {
                $cancel = Helper::approveDocument(
                    $defectNotification->book_id, 
                    $defectNotification->id, 
                    $defectNotification->revision_number, 
                    '', 
                    null, 
                    1, 
                    ConstantHelper::CANCEL, 
                    0, 
                    get_class($defectNotification)
                );
                
                if ($cancel['message']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $cancel['message'],
                    ]);
                } else {
                    $defectNotification->document_status = ConstantHelper::CANCEL;
                    $defectNotification->save();
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Canceled successfully',
                    ]);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Document found'
                ]);
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $ex->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $defectNotification = DefectNotification::findOrFail($id);
            
            if ($defectNotification->document_status !== 'draft') {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot delete submitted defect notifications.');
            }
            
            $defectNotification->delete();
            
            return redirect()
                ->route('defect-notification.index')
                ->with('success', 'Defect Notification deleted successfully!');
                
        } catch (\Exception $e) {
            Log::error('Error deleting defect notification: ' . $e->getMessage());
            
            return redirect()
                ->back()
                ->with('error', 'Failed to delete defect notification.');
        }
    }

    /**
     * Check if document number already exists
     */
    public function checkDocumentNumber(Request $request)
    {
        $documentNumber = $request->get('document_number');
        $currentId = $request->get('current_id'); // For edit mode to exclude current record

        $user = Helper::getAuthenticatedUser();
        $organizationId = $user->organization_id;
        
        $response = [
            'document_exists' => false,
            'message' => null
        ];
        
        // Check document number if provided
        if ($documentNumber) {
            // Check if the user-entered document number already exists within organization
            $documentExists = DefectNotification::where('document_number', $documentNumber)
                ->where('organization_id', $organizationId);
                
            // For edit mode, exclude current record
            if ($currentId) {
                $documentExists->where('id', '!=', $currentId);
            }
            
            $documentExists = $documentExists->exists();
            
            if ($documentExists) {
                $response['document_exists'] = true;
                $response['message'] = "Document number '{$documentNumber}' already exists. Please use a different document number.";
            }
        }
        
        return response()->json($response, 200);
    }
}

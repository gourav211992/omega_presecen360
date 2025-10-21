<?php

namespace App\Http\Controllers;

use Exception;
use DataTables;
use Carbon\Carbon;
use App\Http\Requests\ErpEquipmentRequest;
use App\Helpers\Helper;
use App\Helpers\InventoryHelper;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\ErpEquipMaintenanceChecklist;
use App\Models\ErpEquipMaintenanceChecklistHistory;
use App\Models\ErpEquipMaintenanceDetail;
use App\Models\ErpEquipMaintenanceDetailHistory;
use App\Models\ErpMaintenanceType;
use App\Models\ErpEquipment;
use App\Models\ErpEquipmentHistory;
use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistDetail;
use App\Models\Item;
use App\Models\ErpEquipSparepartDetail;
use App\Models\ErpEquipmentSparePart;
use App\Models\PlantMaintWo;
use App\Models\ErpItemAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ConstantHelper;
use App\Models\PlantMaintBom;
use App\Models\FixedAssetRegistration;

class ErpEquipmentController extends Controller
{
    public function index()
    {
        $user = Helper::getAuthenticatedUser();
        $organizationId = $user->organization_id;
        $equipmentCategories = Category::where('type', 'Equipment')->where('status', 'Active')->pluck('name', 'id') ?? collect();
        $maintenanceTypes = ErpMaintenanceType::where('status', 'Active')->pluck('name', 'id') ?? collect();
        $mappings = Helper::access_org();

        return view('equipment.index', compact('equipmentCategories', 'maintenanceTypes', 'mappings', 'organizationId'));
    }

    public function getData(Request $request)
    {
        $equipment = ErpEquipment::with([
            'organization:id,name',
            'location:id,store_name',
            'category:id,name',
            'maintenanceDetails.maintenanceType:id,name',
            'maintenanceDetails' => function ($query) {
                $query->with(['latestWorkOrder:id,equipment_id,maintenance_type_id,document_status,created_at,equipment_details'])
                    ->addSelect([
                        'latest_work_order_id' => PlantMaintWo::select('id')
                            ->whereColumn('erp_plant_maint_wo.equipment_id', 'erp_equip_maintenance_details.erp_equipment_id')
                            ->whereColumn('erp_plant_maint_wo.maintenance_type_id', 'erp_equip_maintenance_details.maintenance_type_id')
                            ->where('erp_plant_maint_wo.reference_type', 'equipment')
                            ->latest('created_at')
                            ->limit(1)
                    ]);
            }
        ]);

        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if (strpos($dateRange, ' to ') !== false) {
                $dates = explode(' to ', $dateRange);
                if (count($dates) == 2) {
                    $startDate = Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay();
                    $equipment->whereBetween('created_at', [$startDate, $endDate]);
                }
            }
        }

        if ($request->filled('equipment_category_filter')) {
            $equipment->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->equipment_category_filter);
            });
        }

        if ($request->filled('maintenance_type_filter')) {
            $equipment->whereHas('maintenanceDetails.maintenanceType', function ($q) use ($request) {
                $q->where('name', $request->maintenance_type_filter);
            });
        }

        if ($request->filled('status_filter')) {
            $statusFilter = $request->status_filter;
            if ($statusFilter === 'approved') {
                $equipment->where('document_status', ConstantHelper::APPROVAL_NOT_REQUIRED);
            } elseif ($statusFilter === 'submitted') {
                $equipment->where('document_status', ConstantHelper::SUBMITTED);
            } elseif ($statusFilter === 'rejected') {
                $equipment->where('document_status', ConstantHelper::REJECTED);
            } else {
                $equipment->where('document_status', $statusFilter);
            }
        }

        if ($request->filled('filter_organization')) {
            $organizationFilters = is_array($request->filter_organization)
                ? $request->filter_organization
                : [$request->filter_organization];
            $equipment->whereIn('organization_id', $organizationFilters);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = trim($request->search['value']);
            $equipment->where(function ($query) use ($searchValue) {
                $query->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('alias', 'like', "%{$searchValue}%")
                    ->orWhere('document_status', 'like', "%{$searchValue}%")
                    ->orWhereHas('organization', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('location', function ($q) use ($searchValue) {
                        $q->where('store_name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('category', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('maintenanceDetails.maintenanceType', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });

                if (preg_match('/approv/i', $searchValue)) {
                    $query->orWhereIn('document_status', ['approved', 'approval_not_required']);
                }
            });
        }

        $equipment->orderBy('created_at', 'desc');

        $data = collect();
        foreach ($equipment->get() as $equip) {
            if ($equip->maintenanceDetails && $equip->maintenanceDetails->isNotEmpty()) {
                foreach ($equip->maintenanceDetails as $maintenanceDetail) {
                    $data->push([
                        'id' => $maintenanceDetail->id,
                        'equipment_id' => $equip->id,
                        'equipment_name' => $equip->name,
                        'alias' => $equip->alias,
                        'organization' => optional($equip->organization)->name,
                        'location' => optional($equip->location)->store_name,
                        'category' => optional($equip->category)->name,
                        'created_at' => $equip->created_at ? Carbon::parse($equip->created_at)->format('d-m-Y') : '',
                        'maintenance_type' => optional($maintenanceDetail->maintenanceType)->name,
                        'checklists' => $this->getChecklistNames($maintenanceDetail),
                        'last_date' => $this->getLastDate($maintenanceDetail),
                        'due_date' => $this->getDueDate($maintenanceDetail),
                        'status' => $equip->status,
                        'document_status' => $equip->document_status,
                        'equipment' => $equip
                    ]);
                }
            } else {
                $data->push([
                    'id' => null,
                    'equipment_id' => $equip->id,
                    'equipment_name' => $equip->name,
                    'alias' => $equip->alias,
                    'organization' => optional($equip->organization)->name,
                    'location' => optional($equip->location)->store_name,
                    'category' => optional($equip->category)->name,
                    'created_at' => $equip->created_at ? Carbon::parse($equip->created_at)->format('d-m-Y') : '',
                    'maintenance_type' => '',
                    'checklists' => '',
                    'last_date' => '',
                    'due_date' => '',
                    'status' => $equip->status,
                    'document_status' => $equip->document_status,
                    'equipment' => $equip
                ]);
            }
        }

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('equipment', fn($row) => $row['equipment_name'])
            ->addColumn('organization', fn($row) => $row['organization'])
            ->addColumn('location', fn($row) => $row['location'])
            ->addColumn('alias', fn($row) => $row['alias'])
            ->addColumn('category', fn($row) => $row['category'])
            ->addColumn('created_at', fn($row) => $row['created_at'])
            ->addColumn('maintenance_type', fn($row) => $row['maintenance_type'])
            ->addColumn('checklists', fn($row) => $row['checklists'])
            ->addColumn('last_date', fn($row) => $row['last_date'])
            ->addColumn('due_date', fn($row) => $row['due_date'])
            ->addColumn('status', function ($row) {
                $status = in_array($row['document_status'], [ConstantHelper::REJECTED, ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED,ConstantHelper::DRAFT]) ? $row['document_status'] : 'inactive';
                if ($row['document_status'] != null) {
                    if ($row['status'] == 1 && in_array($row['document_status'], [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])) {
                        $btn = '<span class="badge rounded-pill badge-light-success badgeborder-radius">Active</span>';
                    } else if($row['status'] == 0 && in_array($row['document_status'], [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])) {
                        $btn = '<span class="badge rounded-pill badge-light-danger badgeborder-radius">InActive</span>';
                    } else {
                        $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$status ?? "draft"];
                        $btn = '<span class="badge rounded-pill ' . $statusClass . ' badgeborder-radius">' . ucfirst($row['document_status']) . '</span>';
                    }
                } else {
                    if ($row['status'] == 1) {
                        $btn = '<span class="badge rounded-pill badge-light-success badgeborder-radius">Active</span>';
                    } else {
                        $btn = '<span class="badge rounded-pill badge-light-danger badgeborder-radius">InActive</span>';
                    }
                }


                return $btn;
            })
            ->addColumn('action', function ($row) {
                $equipmentId = $row['equipment_id'];
                $editUrl = route('equipment.edit', $equipmentId); // example edit route
            
                // If no equipment, return blank
                if (!$equipmentId) {
                    return '';
                }
            
                // If status is draft or rejected → show Edit button
                if ($row['document_status'] === 'draft' || $row['document_status'] === 'rejected') {
                    return '
                        <div class="dropdown">
                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="' . $editUrl . '">
                                    <i data-feather="edit-3" class="me-50"></i>
                                    <span>Edit</span>
                                </a>
                            </div>
                        </div>
                    ';
                }
            
                // Otherwise → show View button
                return '
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="' . route('equipment.show', $equipmentId) . '">
                                <i data-feather="eye" class="me-50"></i>
                                <span>View</span>
                            </a>
                        </div>
                    </div>
                ';
            })
            
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    /**
     * Get checklist names from maintenance detail JSON column
     */
    private function getChecklistNames($maintenanceDetail)
    {
        if (!$maintenanceDetail || empty($maintenanceDetail->checklist_data)) {
            return '';
        }

        $checklistData = $maintenanceDetail->checklist_data;
        
        // If it's a string, decode it
        if (is_string($checklistData)) {
            $checklistData = json_decode($checklistData, true);
        }

        if (!is_array($checklistData)) {
            return '';
        }

        // Extract unique checklist names
        $checklistNames = collect($checklistData)
            ->pluck('main_checklist_name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        return implode(', ', $checklistNames);
    }

    public function create()
    {
        $parentURL = request()->segments()[0];
        $fixedAssetRegistration = FixedAssetRegistration::select('id', 'asset_name', 'asset_code')->get();
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);

        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }

        $organization = Helper::getAuthenticatedUser()->organization;
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        $dataTypes = ConstantHelper::DATA_TYPES;

        $user = Helper::getAuthenticatedUser();
        $userOrganizations = Helper::access_org();
        $userOrganizations = $userOrganizations->unique(function ($item) {
            return $item->organization->id;
        });
        $organizationId = Helper::getAuthenticatedUser()->organization_id;

        $locations = InventoryHelper::getAccessibleLocations();
        $maintenanceTypes = ErpMaintenanceType::where('status', 'Active')->get(['id', 'name']);
        $maintenanceBOM = PlantMaintBom::all(['id', 'bom_name as name']);
        $checklists = InspectionChecklist::where('type', 'maintenance')->get();
        $items = Item::get();
        $categories = Category::where('type', 'Equipment')->where('status', 'Active')->get();

        return view('equipment.create', compact(
            'maintenanceBOM',
            'series',
            'organizationId',
            'userOrganizations',
            'locations',
            'categories',
            'maintenanceTypes',
            'items',
            'checklists',
            'fixedAssetRegistration',
            'dataTypes'
        ));
    }

    public function store(ErpEquipmentRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = Helper::getAuthenticatedUser();
            $org = $user->organization;
            $parentUrl = ConstantHelper::EQPT;
            $services = Helper::getAccessibleServicesFromMenuAlias($parentUrl);

            $book_id = null;
            if ($services && $services['current_book']) {
                $book = $services['current_book'];
                $book_id = $book->id;
            }

           

            $equipment = ErpEquipment::create([
                'organization_id' => $request->organization_id,
                'group_id' => $org->group_id ?? null,
                'company_id' => $org->company_id ?? null,
                'category_id' => $request->category_id,
                'location_id' => $request->location_id,
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'final_remarks' => $request->final_remarks,
                'book_id' => $book_id,
                'document_status' => $request->status,
                'created_by' => $user->auth_user_id,
                'asset_code_id' => $request->asset_code_id,
                'status' => $request->has('active_status') ? 1 : 0,
                'model_name' => $request->model_name,
                'manufacturer_name' => $request->manufacturer_name,
                'yom' => $request->yom,
                'commission_date' => $request->commission_date,
                'purchase_cost' => $request->purchase_cost,
            ]);

            if ($equipment->document_status != ConstantHelper::DRAFT) {
                $doc = Helper::approveDocument(
                    $equipment->book_id,
                    $equipment->id,
                    0,
                    $request->remarks,
                    null,
                    1,
                    'submit',
                    0,
                    get_class($equipment)
                );

                $equipment->document_status = $doc['approvalStatus'] ?? $equipment->document_status;
                $equipment->save();
            }

            $documentPaths = [];
            if ($request->hasFile('upload_document')) {
                foreach ($request->file('upload_document') as $index => $file) {
                    $fileName = 'equipment_documents' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('maint_bom_documents', $fileName, 'public');
                    $documentPaths[] = $path;
                }
            }

            if (!empty($documentPaths)) {
                $equipment->upload_document = json_encode($documentPaths);
            }

            $equipment->save();

            if ($request->has('maintenance') && is_array($request->maintenance)) {
                foreach ($request->maintenance as $mRow) {
                    if (empty($mRow['type']) || empty($mRow['frequency'])) continue;

                    // Prepare checklist data for JSON storage
                    $checklistData = [];
                    if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                        foreach ($mRow['checklists'] as $check) {
                            if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) continue;

                            $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                            if (!$checklistDetail) continue;

                            $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                            $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                            $checklistData[] = [
                                'checklist_id' => $check['checklist_id'],
                                'checklist_detail_id' => $check['checklist_detail_id'],
                                'main_checklist_name' => $mainChecklistName,
                                'name' => $checklistDetail->name,
                                'description' => $checklistDetail->description,
                                'data_type' => $checklistDetail->data_type
                            ];
                        }
                    }
                   

                    $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                        'erp_equipment_id' => $equipment->id,
                        'maintenance_type_id' => $mRow['type'],
                        'frequency' => $mRow['frequency'],
                        'time' => $mRow['time'] ?? null,
                        'start_date' => $mRow['date'] ?? null,
                        'maintenance_bom_id' => $mRow['bom'] ?? null,
                        'checklist_data' => !empty($checklistData) ? $checklistData : null,
                        'created_by' => $user->auth_user_id,
                    ]);
                }
            }

            DB::commit();

            $message = $request->status == 'draft'
                ? 'Equipment saved as draft successfully'
                : 'Equipment submitted successfully';

            return redirect()->route('equipment.index')->with('success', $message);
        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /** Display the specified resource. */
    public function show(Request $r, $id)
    {
        
        $parentURL = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        

        $organization = Helper::getAuthenticatedUser()->organization;
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        $user = Helper::getAuthenticatedUser();
        $userOrganizations = Helper::access_org()->unique(fn($item) => $item->organization->id);
        
        if ($r->has('revisionNumber')) {
            $revNo = intval($r->revisionNumber);
            $equipment = ErpEquipmentHistory::with([
                'spareParts',
                'maintenanceDetails'
            ])  ->where('source_id',$id)
                ->where('revision_number', $revNo)->firstOrFail();  
            $equipmentId = $equipment->source_id;
            
        } else {
            $equipment = ErpEquipment::with([
                'spareParts',
                'maintenanceDetails'
            ])->where('id',$id)->first();
            
            if (!$equipment) {
                abort(404, 'Equipment not found');
            }
            
            $revNo = $equipment->revision_number;
            $equipmentId = $equipment->id;
        }
        
        $userType = Helper::userCheck();

        $buttons = Helper::actionButtonDisplayEquipment(
            $equipment->book_id,
            $equipment->document_status,
            $equipment->id,
            0,
            $equipment->approval_level,
            $equipment->created_by ?? 0,
            $userType['type'],
            $revNo
        );
       

      
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$equipment->document_status] ?? '';
        $locations = InventoryHelper::getAccessibleLocations();
        $maintenanceTypes = ErpMaintenanceType::where('status', 'Active')->get(['id', 'name']);
        $maintenanceBOM = PlantMaintBom::all(['id', 'bom_name as name']);
        $items = Item::get();
        $categories = Category::where('type', 'Equipment')->where('status', 'Active')->get();
        $approvalHistory = [];

        if (!empty($equipment->book_id)) {
            $approvalHistory = Helper::getApprovalHistory(
                $equipment->book_id,
                $equipment->id,
                $revNo,
                0,
                $equipment->created_by
            );
        }

        $checklists = InspectionChecklist::where('type', 'maintenance')->get();
        $fixedAssetRegistration = FixedAssetRegistration::select('id', 'asset_name', 'asset_code')->get();
        
        $checkListIds = [];
        $mainChecklistNames = [];

        // Use the same approach as getPopupChecklistData method
        if ($r->has('revisionNumber')) {
            // For revision history - use equipment relationship data that's already loaded
            $maintenanceDetails = $equipment->maintenanceDetails;
        } else {
            // For current data - use equipment relationship data that's already loaded  
            $maintenanceDetails = $equipment->maintenanceDetails;
        }
        
        foreach ($maintenanceDetails as $maintenanceDetail) {
            if (!empty($maintenanceDetail->checklist_data)) {
                // Since we have casting in the model, checklist_data will be automatically converted to array
                $checklistData = $maintenanceDetail->checklist_data;
                if (is_array($checklistData)) {
                    foreach ($checklistData as $checklistItem) {
                        if (!empty($checklistItem['checklist_detail_id'])) {
                            $checkListIds[] = $checklistItem['checklist_detail_id'];
                        }
                        if (!empty($checklistItem['main_checklist_name'])) {
                            $mainChecklistNames[] = $checklistItem['main_checklist_name'];
                        }
                    }
                }
            }
        }

        $mainChecklistNames = array_unique($mainChecklistNames);
        
        return view('equipment.show', compact(
            'equipment',
            'series',
            'userOrganizations',
            'locations',
            'categories',
            'maintenanceTypes',
            'maintenanceBOM',
            'approvalHistory',
            'buttons',
            'docStatusClass',
            'items',
            'checklists',
            'fixedAssetRegistration',
            'checkListIds',
            'mainChecklistNames'
        ));
    }


    private function getLastDate($maintenanceDetail)
    {
        try {
            // Find the last submitted work order for this specific equipment + maintenance type combination
            $lastWorkOrder = \App\Models\PlantMaintWo::where('equipment_id', $maintenanceDetail->erp_equipment_id)
                ->where('maintenance_type_id', $maintenanceDetail->maintenance_type_id)
                ->whereIn('document_status', ['submitted', 'approved', 'approval_not_required', 'closed'])
                ->orderBy('document_date', 'desc')
                ->first();

            if ($lastWorkOrder) {
                $details = json_decode($lastWorkOrder->equipment_details, true);
                if (is_array($details)) {
                    if (!empty($details['last_maintenance_date'])) {
                        return Carbon::parse($details['last_maintenance_date'])->format('d-m-Y');
                    } elseif (!empty($details['due_date'])) {
                        return Carbon::parse($details['due_date'])->format('d-m-Y');
                    }
                }
                return Carbon::parse($lastWorkOrder->document_date)->format('d-m-Y');
            }
            return '';
        } catch (\Exception $e) {
            \Log::error("Error getting last date for maintenance detail ID {$maintenanceDetail->id}: " . $e->getMessage());
            return '';
        }
    }

    private function getDueDate($maintenanceDetail)
    {
        try {
            // Find the last submitted work order for this specific equipment + maintenance type combination
            $lastWorkOrder = PlantMaintWo::where('equipment_id', $maintenanceDetail->erp_equipment_id)
                ->where('maintenance_type_id', $maintenanceDetail->maintenance_type_id)
                ->whereIn('document_status', ['submitted', 'approved', 'approval_not_required', 'closed'])
                ->orderBy('document_date', 'desc')
                ->first();

            $baseDate = null;
            
            if ($lastWorkOrder) {
                // Use the document date of the last work order as base date
                $baseDate = Carbon::parse($lastWorkOrder->document_date);
            } else {
                // If no previous work order, use start date from maintenance detail
                if ($maintenanceDetail->start_date) {
                    $baseDate = Carbon::parse($maintenanceDetail->start_date);
                } else {
                    return '';
                }
            }

            // Calculate next due date based on frequency
            if ($baseDate && $maintenanceDetail->frequency) {
                switch ($maintenanceDetail->frequency) {
                    case 'Daily':
                        return $baseDate->copy()->addDay()->format('d-m-Y');
                    case 'Weekly':
                        return $baseDate->copy()->addWeek()->format('d-m-Y');
                    case 'Monthly':
                        return $baseDate->copy()->addMonth()->format('d-m-Y');
                    case 'Quarterly':
                        return $baseDate->copy()->addMonths(3)->format('d-m-Y');
                    case 'Semi-Annually':
                        return $baseDate->copy()->addMonths(6)->format('d-m-Y');
                    case 'Annually':
                    case 'Yearly':
                        return $baseDate->copy()->addYear()->format('d-m-Y');
                }
            }

            return $maintenanceDetail->start_date
                ? Carbon::parse($maintenanceDetail->start_date)->format('d-m-Y')
                : '';

        } catch (\Exception $e) {
            \Log::error("Error calculating due date for maintenance detail ID {$maintenanceDetail->id}: " . $e->getMessage());
            return $maintenanceDetail->start_date
                ? Carbon::parse($maintenanceDetail->start_date)->format('d-m-Y')
                : '';
        }
    }

        /** Edit equipment */
        public function edit(Request $r, $id)
        {
           
            $parentURL = request()->segments()[0];
            $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
            if (count($servicesBooks['services']) == 0) {
                return redirect()->route('/');
            }
    
            $organization = Helper::getAuthenticatedUser()->organization;
            $firstService = $servicesBooks['services'][0];
            $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
            $user = Helper::getAuthenticatedUser();
          
            $userOrganizations = Helper::access_org()->unique(fn($item) => $item->organization->id);
            
            if ($r->has('revisionNumber')) {
                $revNo = intval($r->revisionNumber);
                $equipment = ErpEquipmentHistory::with([
                    'spareParts',
                    'maintenanceDetails.checklists'
                ])->where('source_id', $id)
                    ->where('revision_number', $revNo)->firstOrFail();
            } else {
                $equipment = ErpEquipment::with([
                    'spareParts',
                    'maintenanceDetails.checklists'
                ])->where('id',$id)->first();
               
                $revNo = $equipment->revision_number;
            }
          
            $userType = Helper::userCheck();
            $buttons = Helper::actionButtonDisplayEquipment(
                $equipment->book_id,
                $equipment->document_status,
                $equipment->id,
                0,
                $equipment->approval_level,
                $equipment->created_by ?? 0,
                $userType['type'],
                $revNo
            );
    
            $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$equipment->document_status] ?? '';
            $locations = InventoryHelper::getAccessibleLocations();
            $maintenanceTypes = ErpMaintenanceType::where('status', 'Active')->get(['id', 'name']);
            $maintenanceBOM = PlantMaintBom::all(['id', 'bom_name as name']);
            $items = Item::get();
            $categories = Category::where('type', 'Equipment')->where('status', 'Active')->get();
            $approvalHistory = [];
    
            if (!empty($equipment->book_id)) {
                $approvalHistory = Helper::getApprovalHistory(
                    $equipment->book_id,
                    $equipment->id,
                    $revNo,
                    0,
                    $equipment->created_by
                );
            }
    
            $checklists = InspectionChecklist::where('type', 'maintenance')->get();
            $fixedAssetRegistration = FixedAssetRegistration::select('id', 'asset_name', 'asset_code')->get();
            $maintenanceDetails = ErpEquipMaintenanceDetail::where('erp_equipment_id', $equipment->id)->value('id');
            $checkListData = ErpEquipMaintenanceChecklist::where('erp_equip_maintenance_id', $maintenanceDetails)
                ->select('id', 'checklist_detail')->get();
    
            $checkListIds = [];
            $mainChecklistNames = [];
            foreach ($checkListData as $checkListItem) {
                $checkListDetail = json_decode($checkListItem->checklist_detail);
                if (!empty($checkListDetail->checklist_detail_id)) {
                    $checkListIds[] = $checkListDetail->checklist_detail_id;
                }
                if (!empty($checkListDetail->main_checklist_name)) {
                    $mainChecklistNames[] = $checkListDetail->main_checklist_name;
                }
            }
    
            $mainChecklistNames = array_unique($mainChecklistNames);
    
            return view('equipment.edit', compact(
                'equipment',
                'series',
                'userOrganizations',
                'locations',
                'categories',
                'maintenanceTypes',
                'maintenanceBOM',
                'approvalHistory',
                'buttons',
                'docStatusClass',
                'items',
                'checklists',
                'fixedAssetRegistration',
                'checkListIds',
                'mainChecklistNames'
            ));
        }
    
    // // /** Update equipment */
    // public function update(Request $request, $id)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $user = Helper::getAuthenticatedUser();
    //         $equipment = ErpEquipment::findOrFail($id);
    //          if ($request->action_type == "amendment") {

    //             $revisionData = [
    //                 ['model_type' => 'header', 'model_name' => 'ErpEquipment', 'relation_column' => ''],
    //                 ['model_type' => 'detail', 'model_name' => 'ErpEquipMaintenanceDetail', 'relation_column' => 'erp_equipment_id'],
    //                 ['model_type' => 'detail', 'model_name' => 'ErpEquipMaintenanceChecklist', 'relation_column' => 'equipment_id'],
    //             ];     
    //             // Create DefectNotificationHistory record before amendment
    //             $amendmentResult = Helper::documentAmendment($revisionData, $id);
                
    //             if (!$amendmentResult) {
    //                 throw new \Exception('Failed to create DefectNotificationHistory record during amendment');
    //             }
            
    //             Helper::approveDocument(
    //                 $equipment->book_id,
    //                 $equipment->id,
    //                 $equipment->revision_number,
    //                 $request->amend_remarks,
    //                 $request->file('amend_attachment'),
    //                 $equipment->approval_level,
    //                 'amendment',
    //                 0,
    //                 get_class($equipment)
    //             );

    //         $equipment->revision_number = $equipment->revision_number + 1;
    //         $equipment->revision_date = now();
    //         $equipment->save();
    //         DB::commit();
    //     }
        
    //         $revisionNumber = $equipment->revision_number ?? 0;
    //         $actionType = 'submit';
    //         $remarks='';
    //         $attachments='';
    //         $currentLevel=$equipment->approval_level;
    //         $modelName=get_class($equipment);
    //         $totalValue = $equipment->grand_total_amount ?? 0;
    //         $approveDocument = Helper::approveDocument($equipment->book_id, $equipment->id, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
    //         $document_status = $approveDocument['approvalStatus'] ?? $equipment->document_status;
    //         $equipment->document_status = $document_status;
    //         $data['document_status'] = $document_status;
    //         $equipment->save();
        

            
            

    //     $updateData = [
    //         'category_id' => $request->category_id,
    //         'name' => $request->name,
    //         'alias' => $request->alias,
    //         'description' => $request->description,
    //         'final_remarks' => $request->final_remarks,
    //         'document_status' => $request->status,
    //         'status' => $request->has('active_status') ? 1 : 0,
    //         'model_name' => $request->model_name,
    //         'manufacturer_name' => $request->manufacturer_name,
    //         'yom' => $request->yom,
    //         'commission_date' => $request->commission_date,
    //         'purchase_cost' => $request->purchase_cost,
    //     ];

    //         $equipment->update($updateData);
    //         $finalDocuments = [];
    //         if ($equipment->upload_document) {
    //             $existingDocuments = json_decode($equipment->upload_document, true);
    //             if (!is_array($existingDocuments)) {
    //                 $existingDocuments = [$equipment->upload_document];
    //             }
    //             $finalDocuments = $existingDocuments;
    //         }

    //         if ($request->has('deleted_files') && !empty($request->deleted_files)) {
    //             $deletedFiles = json_decode($request->deleted_files, true);
    //             if (is_array($deletedFiles)) {
    //                 $finalDocuments = array_diff($finalDocuments, $deletedFiles);
    //             }
    //         }

    //         if ($request->hasFile('upload_document')) {
    //             $newDocumentPaths = [];
    //             foreach ($request->file('upload_document') as $index => $file) {
    //                 $fileName = 'equipment_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
    //                 $path = $file->storeAs('equipment_documents', $fileName, 'public');
    //                 $newDocumentPaths[] = $path;
    //             }
    //             $finalDocuments = array_merge($finalDocuments, $newDocumentPaths);
    //         }

    //         if ($request->has('deleted_files') && !empty($request->deleted_files)) {
    //             $deletedFiles = json_decode($request->deleted_files, true);
    //             if (is_array($deletedFiles)) {
    //                 foreach ($deletedFiles as $fileToDelete) {
    //                     if (\Storage::disk('public')->exists($fileToDelete)) {
    //                         \Storage::disk('public')->delete($fileToDelete);
    //                     }
    //                 }
    //             }
    //         }

    //         $equipment->upload_document = !empty($finalDocuments)
    //             ? json_encode($finalDocuments)
    //             : null;

    //         $equipment->save();

    //         // Smart update approach to preserve IDs for revision history
    //         $existingMaintenanceDetails = $equipment->maintenanceDetails()->get()->keyBy('maintenance_type_id');
    //         $submittedMaintenanceTypes = [];
            
    //         if ($request->has('maintenance') && is_array($request->maintenance)) {
    //             foreach ($request->maintenance as $mRow) {
    //                 if (empty($mRow['type']) || empty($mRow['frequency'])) continue;
                    
    //                 $submittedMaintenanceTypes[] = $mRow['type'];
                    
    //                 // Update existing or create new maintenance detail
    //                 $maintenance_detail_item = $existingMaintenanceDetails->get($mRow['type']);
                    
    //                 if ($maintenance_detail_item) {
    //                     // Update existing maintenance detail
    //                     $maintenance_detail_item->update([
    //                         'frequency' => $mRow['frequency'],
    //                         'start_date' => $mRow['date'] ?? null,
    //                         'maintenance_bom_id' => $mRow['bom'] ?? null,
    //                         'time' => $mRow['time'] ?? null,
    //                     ]);
                        
    //                     // Clear existing checklists for this maintenance detail
    //                     $maintenance_detail_item->checklists()->delete();
    //                 } else {
    //                     // Create new maintenance detail
    //                     $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
    //                         'erp_equipment_id' => $equipment->id,
    //                         'maintenance_type_id' => $mRow['type'],
    //                         'frequency' => $mRow['frequency'],
    //                         'start_date' => $mRow['date'] ?? null,
    //                         'maintenance_bom_id' => $mRow['bom'] ?? null,
    //                         'time' => $mRow['time'] ?? null,
    //                     ]);
    //                 }

    //                 if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
    //                     foreach ($mRow['checklists'] as $check) {
    //                         if (empty($check['checklist_id']) && empty($check['checklist_detail_id'])) continue;
    //                         $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
    //                         if (!$checklistDetail) continue;
    //                         $mainChecklist = InspectionChecklist::find($check['checklist_id']);
    //                         $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

    //                         ErpEquipMaintenanceChecklist::create([
    //                             'erp_equip_maintenance_id' => $maintenance_detail_item->id,
    //                             'equipment_id' => $equipment->id,
    //                             'name' => $mainChecklistName,
    //                             'description' => $checklistDetail->description ?? null,
    //                             'type' => $checklistDetail->data_type ?? null,
    //                             'created_by' => $user->auth_user_id,
    //                             'checklist_detail' => json_encode([
    //                                 'checklist_id' => $check['checklist_id'],
    //                                 'checklist_detail_id' => $check['checklist_detail_id'],
    //                                 'main_checklist_name' => $mainChecklistName,
    //                                 'name' => $checklistDetail->name,
    //                                 'description' => $checklistDetail->description,
    //                                 'data_type' => $checklistDetail->data_type
    //                             ]),
    //                         ]);
    //                     }
    //                 }
    //             }
                
    //             // Remove maintenance details that were not submitted
    //             $maintenanceDetailsToRemove = $existingMaintenanceDetails->reject(function ($detail) use ($submittedMaintenanceTypes) {
    //                 return in_array($detail->maintenance_type_id, $submittedMaintenanceTypes);
    //             });
                
    //             foreach ($maintenanceDetailsToRemove as $detailToRemove) {
    //                 $detailToRemove->checklists()->delete();
    //                 $detailToRemove->delete();
    //             }
    //         }

    //         // Smart update approach for spare parts to preserve IDs
    //         $existingSpareParts = $equipment->spareParts()->get();
    //         $submittedSparePartKeys = [];
            
    //         if ($request->has('spareparts') && is_array($request->spareparts)) {
    //             foreach ($request->spareparts as $index => $sRow) {
    //                 if (empty($sRow['item_code']) || empty($sRow['item_name'])) continue;
                    
    //                 $sparePartKey = $sRow['item_code'] . '_' . $sRow['item_name'];
    //                 $submittedSparePartKeys[] = $sparePartKey;
                    
    //                 // Check if spare part already exists
    //                 $existingSparePart = $existingSpareParts->where('item_code', $sRow['item_code'])
    //                                                       ->where('item_name', $sRow['item_name'])
    //                                                       ->first();
                    
    //                 if ($existingSparePart) {
    //                     // Update existing spare part
    //                     $existingSparePart->update([
    //                         'uom' => $sRow['uom'] ?? '',
    //                         'qty' => $sRow['qty'] ?? 0,
    //                     ]);
    //                 } else {
    //                     // Create new spare part
    //                     $equipment->spareParts()->create([
    //                         'item_code' => $sRow['item_code'],
    //                         'item_name' => $sRow['item_name'],
    //                         'uom' => $sRow['uom'] ?? '',
    //                         'qty' => $sRow['qty'] ?? 0,
    //                         'created_by' => $user->auth_user_id,
    //                     ]);
    //                 }
    //             }
                
    //             // Remove spare parts that were not submitted
    //             $sparePartsToRemove = $existingSpareParts->reject(function ($sparePart) use ($submittedSparePartKeys) {
    //                 $sparePartKey = $sparePart->item_code . '_' . $sparePart->item_name;
    //                 return in_array($sparePartKey, $submittedSparePartKeys);
    //             });
                
    //             foreach ($sparePartsToRemove as $sparePartToRemove) {
    //                 $sparePartToRemove->delete();
    //             }
    //         } else {
    //             // If no spare parts submitted, remove all existing ones
    //             $equipment->spareParts()->delete();
    //         }
            
    //         // ✅ Handle approval process for submitted documents (same as BOM pattern)
    //         if ($equipment->document_status == ConstantHelper::SUBMITTED && $request->action_type != "amendment") {
    //             $bookId = $equipment->book_id;
    //             $docId = $equipment->id;
    //             $revisionNumber = $equipment->revision_number ?? 0;
    //             $remarks = $equipment->final_remarks;
    //             $attachments = null; // No attachments in regular update
    //             $currentLevel = $equipment->approval_level ?? 1;
    //             $actionType = 'submit';
    //             $modelName = get_class($equipment);
    //             $totalValue = 0; // Equipment doesn't have monetary value
                
    //             $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
    //             $equipment->document_status = $approveDocument['approvalStatus'] ?? $equipment->document_status;
    //             $equipment->save();
    //         }

    //         DB::commit();

    //         if ($request->ajax() || $request->action_type == "amendment") {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Amendment Done Successfully',
    //                 'data' => $equipment
    //             ]);
    //         }

    //         $message = $request->status == 'draft'
    //             ? 'Equipment updated as draft successfully'
    //             : 'Equipment updated successfully';

    //         // Follow standard pattern: draft goes to show page, submitted goes to index page
            
    //         return redirect()->route('equipment.index')->with('success', $message);
            
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         if ($request->ajax() || $request->action_type == "amendment") {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => $e->getMessage()
    //             ], 500);
    //         }
    //         return back()->withErrors(['error' => $e->getMessage()])->withInput();
    //     }
    // }

    public function update(Request $request, $id)
    {
        
        DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();
            $equipment = ErpEquipment::findOrFail($id);
          
            // Handle amendment process
            if ($request->action_type == "amendment") {
                // Create history first, then handle amendment
                $revisionData = [
                    [
                        'model_type' => 'header',
                        'model_name' => 'ErpEquipment',
                        'relation_column' => '',
                    ],
                    [
                        'model_type' => 'detail',
                        'model_name' => 'ErpEquipMaintenanceDetail',
                        'relation_column' => 'erp_equipment_id',
                    ],
                ];
                
                $amendmentResult = Helper::documentAmendment($revisionData, $id);
                
                // Update revision number and save
                $equipment->revision_number = $equipment->revision_number + 1;
                $equipment->revision_date = now();
                $equipment->save();
                
                DB::commit();
                
            }

           
            

            $updateData = [
                'category_id' => $request->category_id,
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'final_remarks' => $request->final_remarks,
                'document_status' => $request->status,
                'status' => $request->has('active_status') ? 1 : 0,
                'model_name' => $request->model_name,
                'manufacturer_name' => $request->manufacturer_name,
                'yom' => $request->yom,
                'commission_date' => $request->commission_date,
                'purchase_cost' => $request->purchase_cost,
            ];

            $equipment->update($updateData);
            $finalDocuments = [];
            if ($equipment->upload_document) {
                $existingDocuments = json_decode($equipment->upload_document, true);
                if (!is_array($existingDocuments)) {
                    $existingDocuments = [$equipment->upload_document];
                }
                $finalDocuments = $existingDocuments;
            }

            if ($request->has('deleted_files') && !empty($request->deleted_files)) {
                $deletedFiles = json_decode($request->deleted_files, true);
                if (is_array($deletedFiles)) {
                    $finalDocuments = array_diff($finalDocuments, $deletedFiles);
                }
            }

            if ($request->hasFile('upload_document')) {
                $newDocumentPaths = [];
                foreach ($request->file('upload_document') as $index => $file) {
                    $fileName = 'equipment_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('equipment_documents', $fileName, 'public');
                    $newDocumentPaths[] = $path;
                }
                $finalDocuments = array_merge($finalDocuments, $newDocumentPaths);
            }

            if ($request->has('deleted_files') && !empty($request->deleted_files)) {
                $deletedFiles = json_decode($request->deleted_files, true);
                if (is_array($deletedFiles)) {
                    foreach ($deletedFiles as $fileToDelete) {
                        if (\Storage::disk('public')->exists($fileToDelete)) {
                            \Storage::disk('public')->delete($fileToDelete);
                        }
                    }
                }
            }

            $equipment->upload_document = !empty($finalDocuments)
                ? json_encode($finalDocuments)
                : null;

            $equipment->save();

            // Smart approach: Update existing maintenance details or create new ones
            $existingMaintenanceDetails = $equipment->maintenanceDetails()->get();
            $submittedMaintenanceIds = [];

            if ($request->has('maintenance') && is_array($request->maintenance)) {
                foreach ($request->maintenance as $index => $mRow) {
                    if (empty($mRow['type']) || empty($mRow['frequency'])) {
                        continue;
                    }
                    
                    // Check if this is an update (has ID) or new entry
                    if (!empty($mRow['id']) && is_numeric($mRow['id'])) {
                        // Update existing maintenance detail (preserves ID)
                        $maintenance_detail_item = $existingMaintenanceDetails->where('id', $mRow['id'])->first();
                        
                        if ($maintenance_detail_item) {
                            // Prepare checklist data for JSON storage
                            $checklistData = [];
                            if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                                foreach ($mRow['checklists'] as $check) {
                                    if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) continue;
                                    
                                    $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                                    if (!$checklistDetail) continue;
                                    
                                    $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                                    $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                                    $checklistData[] = [
                                        'checklist_id' => $check['checklist_id'],
                                        'checklist_detail_id' => $check['checklist_detail_id'],
                                        'main_checklist_name' => $mainChecklistName,
                                        'name' => $checklistDetail->name,
                                        'description' => $checklistDetail->description,
                                        'data_type' => $checklistDetail->data_type
                                    ];
                                }
                            }
                            
                            $maintenance_detail_item->update([
                                'maintenance_type_id' => $mRow['type'],
                                'frequency' => $mRow['frequency'],
                                'start_date' => $mRow['date'] ?? null,
                                'maintenance_bom_id' => $mRow['bom'] ?? null,
                                'time' => $mRow['time'] ?? null,
                                'checklist_data' => !empty($checklistData) ? $checklistData : null,
                            ]);
                            
                            // Clear existing checklists for this maintenance detail (if any exist)
                            $maintenance_detail_item->checklists()->delete();
                            $submittedMaintenanceIds[] = $maintenance_detail_item->id;
                        } else {
                            // Prepare checklist data for JSON storage
                            $checklistData = [];
                            if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                                foreach ($mRow['checklists'] as $check) {
                                    if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) continue;
                                    
                                    $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                                    if (!$checklistDetail) continue;
                                    
                                    $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                                    $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                                    $checklistData[] = [
                                        'checklist_id' => $check['checklist_id'],
                                        'checklist_detail_id' => $check['checklist_detail_id'],
                                        'main_checklist_name' => $mainChecklistName,
                                        'name' => $checklistDetail->name,
                                        'description' => $checklistDetail->description,
                                        'data_type' => $checklistDetail->data_type
                                    ];
                                }
                            }
                            
                            // Create new if ID not found
                            $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                                'erp_equipment_id' => $equipment->id,
                                'maintenance_type_id' => $mRow['type'],
                                'frequency' => $mRow['frequency'],
                                'start_date' => $mRow['date'] ?? null,
                                'maintenance_bom_id' => $mRow['bom'] ?? null,
                                'time' => $mRow['time'] ?? null,
                                'checklist_data' => !empty($checklistData) ? $checklistData : null,
                                'created_by' => $user->auth_user_id,
                            ]);
                            $submittedMaintenanceIds[] = $maintenance_detail_item->id;
                        }
                    } else {
                        // Prepare checklist data for JSON storage
                        $checklistData = [];
                        if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                            foreach ($mRow['checklists'] as $check) {
                                if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) continue;
                                
                                $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                                if (!$checklistDetail) continue;
                                
                                $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                                $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                                $checklistData[] = [
                                    'checklist_id' => $check['checklist_id'],
                                    'checklist_detail_id' => $check['checklist_detail_id'],
                                    'main_checklist_name' => $mainChecklistName,
                                    'name' => $checklistDetail->name,
                                    'description' => $checklistDetail->description,
                                    'data_type' => $checklistDetail->data_type
                                ];
                            }
                        }
                        
                        // Create new maintenance detail (for new entries)
                        $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                            'erp_equipment_id' => $equipment->id,
                            'maintenance_type_id' => $mRow['type'],
                            'frequency' => $mRow['frequency'],
                            'start_date' => $mRow['date'] ?? null,
                            'maintenance_bom_id' => $mRow['bom'] ?? null,
                            'time' => $mRow['time'] ?? null,
                            'checklist_data' => !empty($checklistData) ? $checklistData : null,
                            'created_by' => $user->auth_user_id,
                        ]);
                        $submittedMaintenanceIds[] = $maintenance_detail_item->id;
                    }

                    // Checklist data is now stored in the maintenance detail JSON column
                }
                
                // Remove maintenance details that were not submitted
                $maintenanceDetailsToRemove = $existingMaintenanceDetails->reject(function ($detail) use ($submittedMaintenanceIds) {
                    return in_array($detail->id, $submittedMaintenanceIds);
                });
                
                foreach ($maintenanceDetailsToRemove as $detailToRemove) {
                    $detailToRemove->checklists()->delete();
                    $detailToRemove->delete();
                }
            }

            // Handle spare parts - use delete+recreate for simplicity
            $equipment->spareParts()->delete();

            if ($request->has('spareparts') && is_array($request->spareparts)) {
                foreach ($request->spareparts as $sRow) {
                    if (empty($sRow['item_code']) || empty($sRow['item_name'])) continue;

                    $equipment->spareParts()->create([
                        'item_code' => $sRow['item_code'],
                        'item_name' => $sRow['item_name'],
                        'uom' => $sRow['uom'] ?? '',
                        'qty' => $sRow['qty'] ?? 0,
                        'created_by' => $user->auth_user_id,
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax() || $request->action_type == "amendment") {
                return response()->json([
                    'success' => true,
                    'message' => 'Amendment Done Successfully',
                    'data' => $equipment
                ]);
            }

            $message = $request->status == 'draft'
                ? 'Equipment updated as draft successfully'
                : 'Equipment updated successfully';

            // Follow standard pattern: draft goes to show page, submitted goes to index page
          
            return redirect()->route('equipment.index')->with('success', $message);
            
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->action_type == "amendment") {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }







    
        /** Document approval */
        public function documentApproval(Request $request)
        {
            $request->validate([
                'remarks' => 'nullable|string|max:255',
                'attachments' => 'nullable|file'
            ]);
    
            DB::beginTransaction();
    
            try {
                $doc = ErpEquipment::findOrFail($request->id);
                $approveDocument = Helper::approveDocument(
                    $doc->book_id,
                    $doc->id,
                    $doc->revision_number ?? 0,
                    $request->remarks,
                    $request->file('attachments') ?? null,
                    $doc->approval_level,
                    $request->action_type,
                    0,
                    get_class($doc)
                );
    
                $document_status = $approveDocument['approvalStatus'] ?? null;
                $doc->document_status = $document_status;
                $doc->status = in_array($document_status, [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED]) ? 1 : 0;
                $doc->save();
    
                DB::commit();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Document approved successfully',
                    'data' => $doc
                ], 200);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Document Approval Error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.',
                    'data' => [],
                ], 500);
            }
        }
    
        /** Delete equipment */
        public function destroy($id)
        {
            try {
                DB::beginTransaction();
                $equipment = ErpEquipment::findOrFail($id);
    
                $isUsed = PlantMaintWo::where('reference_type', 'equipment')
                    ->where('equipment_id', $equipment->id)
                    ->exists();
    
                if ($isUsed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Equipment is already in use. You cannot delete it.'
                    ], 400);
                }
    
                if ($equipment->document_status !== 'draft') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only draft equipment can be deleted.'
                    ], 400);
                }
    
                if ($equipment->maintenanceDetails) {
                    foreach ($equipment->maintenanceDetails as $detail) {
                        $detail->checklists()->delete();
                    }
                    $equipment->maintenanceDetails()->delete();
                }
    
                $equipment->spareParts()->delete();
                $equipment->delete();
    
                DB::commit();
    
                return response()->json([
                    'success' => true,
                    'message' => 'Equipment deleted successfully.'
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting equipment: ' . $e->getMessage()
                ], 500);
            }
        }
    
        /** Revoke document */
        public function revoke(Request $request)
        {
            DB::beginTransaction();
            try {
                $equipment = ErpEquipment::find($request->id);
                if (!$equipment) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'No Document found']);
                }
    
                // if ($equipment->revision_number > 0) {
                //     DB::rollBack();
                //     return response()->json([
                //         'status' => 'error',
                //         'message' => 'This document has already been amended and cannot be revoked.',
                //     ]);
                // }
    
                $revoke = Helper::approveDocument(
                    $equipment->book_id,
                    $equipment->id,
                    $equipment->revision_number,
                    '',
                    null,
                    1,
                    ConstantHelper::REVOKE,
                    0,
                    get_class($equipment)
                );
    
                if ($revoke['message']) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $revoke['message']]);
                }
    
                $equipment->document_status = $revoke['approvalStatus'];
                $equipment->save();
    
                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Revoked successfully']);
            } catch (Exception $ex) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $ex->getMessage()]);
            }
        }

        public function amendment(Request $request,$id)
        {
            DB::beginTransaction();
            try {
                $ErpEquipment = ErpEquipment::find($id);
                if (!$ErpEquipment) {
                    return response()->json(['success' => false, 'message' => "Equipment not found.", 'status' => 404]);
                }
    
                // Build revision data array based on existing records
                $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'ErpEquipment', 'relation_column' => ''],
                ];
                
                // Only include maintenance details if they exist
                if (ErpEquipMaintenanceDetail::where('erp_equipment_id', $id)->exists()) {
                    $revisionData[] = ['model_type' => 'detail', 'model_name' => 'ErpEquipMaintenanceDetail', 'relation_column' => 'erp_equipment_id'];
                    
                    // Note: Checklists are not included as sub_detail due to Helper function limitation
                    // The Helper::documentAmendment function queries sub_detail models incorrectly
                    // Checklist data is preserved through maintenance detail's checklist_detail JSON field
                }
                
                // Only include spare parts if they exist
                if (ErpEquipSparepartDetail::where('erp_equipment_id', $id)->exists()) {
                    $revisionData[] = ['model_type' => 'detail', 'model_name' => 'ErpEquipSparepartDetail', 'relation_column' => 'erp_equipment_id'];
                }
    
                $a = Helper::documentAmendment($revisionData, $id);
                if ($a) {
                    Helper::approveDocument($ErpEquipment->book_id, $ErpEquipment->id, $ErpEquipment->revision_number, 'Amendment', $request->file('attachment'), $ErpEquipment->approval_level, 'amendment');
    
                    // $PlantBom->document_status = ConstantHelper::DRAFT;
                    $ErpEquipment->revision_number = $ErpEquipment->revision_number + 1;
                    $ErpEquipment->revision_date = now();
                    $ErpEquipment->save();
                }
    
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Amendment done successfully',
                    'data' => $ErpEquipment
                ]);
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Amendment Submit Error: ' . $e->getMessage());
                return response()->json(['success' =>false, 'message' => "An unexpected error occurred. Please try again.", 'status' => 500]);
            }
        }

        /**
     * Get checklist details by checklist ID via AJAX
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChecklistDetails(Request $request)
    {
        // try {
            $checklistId = $request->checklist_id;
            
            if (!$checklistId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist ID is required',
                    'data' => []
                ], 400);
            }

            // Get checklist with its details
            $checkLIstName = InspectionChecklist::where('id', $checklistId)->value('name');
            $checklist = InspectionChecklistDetail::where('header_id', $checklistId)
                        ->get();
       

            if (!$checklist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Checklist not found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Checklist details fetched successfully',
                'data' => [
                    'checklist' => $checklist,
                    'details' => $checklist,
                    'checklist_name' => $checkLIstName
                ]
            ], 200);

    }

    /**
     * Search checklists via AJAX
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchChecklists(Request $request)
    {
        try {
            // Get all checklists (you can add search filters here if needed)
            $checklists = \App\Models\InspectionChecklist::select('id', 'name', 'description', 'type')
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'success' => true,
                'message' => 'Checklist data fetched successfully',
                'data' => $checklists
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching checklist data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPopupChecklistData(Request $request){
        
            $equipmentId = $request->equipment_id;
            $maintenanceTypeId = $request->id;
            $revisionNumber = $request->revision_number; // Get revision number from request
            
            // Use EXACTLY the same approach as show method
            if ($request->has('revision_number') && $request->revision_number != null) {
                $revNo = intval($revisionNumber);
                $equipment = ErpEquipmentHistory::with([
                    'spareParts',
                    'maintenanceDetails'
                ])->where('source_id', $equipmentId)
                    ->where('revision_number', $revNo)->first();
                    
                if (!$equipment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Equipment history not found for revision: ' . $revNo
                    ], 404);
                }
                
            } else {
                $equipment = ErpEquipment::with([
                    'spareParts',
                    'maintenanceDetails'
                ])->find($equipmentId);
                
                if (!$equipment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Equipment not found with ID: ' . $equipmentId
                    ], 404);
                }
            }
            
            // Now use the same approach as show method - use loaded relationships
            $maintenanceDetails = $equipment->maintenanceDetails;
            
            // Find the specific maintenance detail by ID
            $maintenanceDetail = $maintenanceDetails->where('id', $maintenanceTypeId)->first();
            
            $maintenanceChecklist = collect();
            
            if ($maintenanceDetail && !empty($maintenanceDetail->checklist_data)) {
                $checklistData = $maintenanceDetail->checklist_data;
                
                if (is_array($checklistData)) {
                    $maintenanceChecklist = collect($checklistData)->map(function($item) {
                        return (object) [
                            'id' => $item['checklist_detail_id'] ?? null,
                            'name' => $item['main_checklist_name'] ?? null,
                            'description' => $item['description'] ?? null,
                            'type' => $item['data_type'] ?? null,
                            'checklist_detail' => json_encode($item)
                        ];
                    });
                }
            }
            
            $inspectionChecklist = InspectionChecklist::where('type', 'maintenance')->select('id', 'name')->get();
            foreach($inspectionChecklist as $checklist){
                $checklist->details = InspectionChecklistDetail::where('header_id', $checklist->id)
                            ->get();
            }
           
            return response()->json([
                'success' => true,
                'message' => 'Checklists found successfully',
                'data' => $inspectionChecklist,
                'maintenanceChecklist' => $maintenanceChecklist
            ], 200);      
     
    }

}
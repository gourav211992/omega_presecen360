<?php

namespace App\Http\Controllers;

use Exception;
use DataTables;
use Carbon\Carbon;
use App\Http\Requests\ErpEquipmentRequest;
use App\Helpers\Helper;
use App\Helpers\InventoryHelper;
use App\Models\Category;
use App\Models\ErpEquipMaintenanceChecklist;
use App\Models\ErpEquipMaintenanceDetail;
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
            'maintenanceDetails.checklists:id,erp_equip_maintenance_id,name',
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
                        'checklists' => $maintenanceDetail->checklists ? $maintenanceDetail->checklists->pluck('name')->unique()->implode(', ') : '',
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
                $status = in_array($row['document_status'], [
                    ConstantHelper::REJECTED,
                    ConstantHelper::SUBMITTED,
                    ConstantHelper::PARTIALLY_APPROVED
                ]) ? $row['document_status'] : 'inactive';

                if (!is_null($row['document_status'])) {
                    if ($row['status'] == 1) {
                        $btn = '<span class="badge rounded-pill badge-light-success badgeborder-radius">Active</span>';
                    } else {
                        $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$status ?? 'draft'] ?? 'badge-light-secondary';
                        $btn = '<span class="badge rounded-pill ' . $statusClass . ' badgeborder-radius">' . ucfirst($status) . '</span>';
                    }
                } else {
                    if ($row['status'] == 1) {
                        $btn = '<span class="badge rounded-pill badge-light-success badgeborder-radius">Active</span>';
                    } else {
                        $btn = '<span class="badge rounded-pill badge-light-danger badgeborder-radius">Inactive</span>';
                    }
                }

                return $btn;
            })
            ->addColumn('action', function ($row) {
                $equipmentId = $row['equipment_id'];
                if (!$equipmentId) {
                    return '';
                }
                return '
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="' . route('equipment.show', $equipmentId) . '">
                                <i data-feather="eye" class="me-50"></i>
                                <span>View</span>
                            </a>
                        </div>
                    </div>';
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
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

                    $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                        'erp_equipment_id' => $equipment->id,
                        'maintenance_type_id' => $mRow['type'],
                        'frequency' => $mRow['frequency'],
                        'time' => $mRow['time'] ?? null,
                        'start_date' => $mRow['date'] ?? null,
                        'maintenance_bom_id' => $mRow['bom'] ?? null,
                        'created_by' => $user->auth_user_id,
                    ]);

                    if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                        foreach ($mRow['checklists'] as $check) {
                            if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) continue;

                            $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                            if (!$checklistDetail) continue;

                            $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                            $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                            ErpEquipMaintenanceChecklist::create([
                                'erp_equip_maintenance_id' => $maintenance_detail_item->id,
                                'equipment_id' => $equipment->id,
                                'name' => $mainChecklistName,
                                'description' => $checklistDetail->description ?? null,
                                'type' => $checklistDetail->data_type ?? null,
                                'created_by' => $user->auth_user_id,
                                'checklist_detail' => json_encode([
                                    'checklist_id' => $check['checklist_id'],
                                    'checklist_detail_id' => $check['checklist_detail_id'],
                                    'main_checklist_name' => $mainChecklistName,
                                    'name' => $checklistDetail->name,
                                    'description' => $checklistDetail->description,
                                    'data_type' => $checklistDetail->data_type
                                ]),
                            ]);
                        }
                    }
                }
            }

            if ($request->has('spareparts') && is_array($request->spareparts)) {
                foreach ($request->spareparts as $sRow) {
                    if (empty($sRow['item_code']) || empty($sRow['item_name'])) continue;

                    $attributes = [];
                    if (!empty($sRow['attributes'])) {
                        try {
                            $attributes = is_string($sRow['attributes'])
                                ? json_decode($sRow['attributes'], true) ?? []
                                : $sRow['attributes'];
                        } catch (Exception $e) {
                            $attributes = [];
                        }
                    }

                    $equipment->spareParts()->create([
                        'item_code' => $sRow['item_code'],
                        'item_name' => $sRow['item_name'],
                        'attributes' => json_encode($attributes),
                        'uom' => $sRow['uom'] ?? '',
                        'qty' => $sRow['qty'] ?? 0,
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
                'maintenanceDetails.checklists'
            ])->where('source_id', $id)
                ->where('revision_number', $revNo)->firstOrFail();  
            $equipmentId = $equipment->source_id;
        } else {
            $equipment = ErpEquipment::with([
                'spareParts',
                'maintenanceDetails.checklists'
            ])->findOrFail($id);
          
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
        $maintenanceDetails = ErpEquipMaintenanceDetail::where('erp_equipment_id', $equipmentId)->value('id');
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
        $latestWorkOrder = $maintenanceDetail->latestWorkOrder;
        if ($latestWorkOrder && in_array($latestWorkOrder->document_status, [
            ConstantHelper::SUBMITTED,
            ConstantHelper::DOCUMENT_STATUS_APPROVED,
            ConstantHelper::APPROVAL_NOT_REQUIRED,
            'closed'
        ])) {
            $details = json_decode($latestWorkOrder->equipment_details, true);
            if (is_array($details)) {
                if (!empty($details['last_maintenance_date'])) {
                    return Carbon::parse($details['last_maintenance_date'])->format('d-m-Y');
                } elseif (!empty($details['due_date'])) {
                    return Carbon::parse($details['due_date'])->format('d-m-Y');
                }
            }
            return Carbon::parse($latestWorkOrder->created_at)->format('d-m-Y');
        }
        return '';
    }

    private function getDueDate($maintenanceDetail)
    {
        $lastMaintDate = null;
        if ($maintenanceDetail->latestWorkOrder && in_array($maintenanceDetail->latestWorkOrder->document_status, [
            ConstantHelper::SUBMITTED,
            ConstantHelper::DOCUMENT_STATUS_APPROVED,
            ConstantHelper::APPROVAL_NOT_REQUIRED,
            'closed'
        ])) {
            $details = json_decode($maintenanceDetail->latestWorkOrder->equipment_details, true);
            if (is_array($details) && !empty($details['due_date'])) {
                $lastMaintDate = Carbon::parse($details['due_date']);
            }
        }
        if ($lastMaintDate) {
            switch ($maintenanceDetail->frequency) {
                case 'Daily':
                    return $lastMaintDate->copy()->addDay()->format('d-m-Y');
                case 'Weekly':
                    return $lastMaintDate->copy()->addWeek()->format('d-m-Y');
                case 'Monthly':
                    return $lastMaintDate->copy()->addMonth()->format('d-m-Y');
                case 'Quarterly':
                    return $lastMaintDate->copy()->addMonths(3)->format('d-m-Y');
                case 'Semi-Annually':
                    return $lastMaintDate->copy()->addMonths(6)->format('d-m-Y');
                case 'Annually':
                case 'Yearly':
                    return $lastMaintDate->copy()->addYear()->format('d-m-Y');
            }
        }
        return $maintenanceDetail->start_date
            ? Carbon::parse($maintenanceDetail->start_date)->format('d-m-Y')
            : '';
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
                ])->findOrFail($id);
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
    
        /** Update equipment */
        public function update(Request $request, $id)
        {
            DB::beginTransaction();
            try {
                $user = Helper::getAuthenticatedUser();
                $equipment = ErpEquipment::findOrFail($id);
              
                if ($request->action_type == "amendment") {
                
                    // Build revision data array based on existing records
                    $revisionData = [
                        ['model_type' => 'header', 'model_name' => 'ErpEquipment', 'relation_column' => ''],
                        ['model_type' => 'detail', 'model_name' => 'ErpEquipMaintenanceDetail', 'relation_column' => 'erp_equipment_id'],
                        ['model_type' => 'detail', 'model_name' => 'ErpEquipMaintenanceChecklist', 'relation_column' => 'equipment_id'],
                    ];
                    
                    Helper::documentAmendment($revisionData, $id);
                    
                    $equipment = ErpEquipment::findOrFail($id);
                    $dd = Helper::approveDocument(
                        $equipment->book_id,
                        $equipment->id,
                        $equipment->revision_number,
                        $request->amend_remarks,
                        $request->file('amend_attachment'),
                        $equipment->approval_level,
                        'amendment',
                        0,
                        get_class($equipment)
                    );


                    $equipment->revision_number = $equipment->revision_number + 1;
                    $equipment->revision_date = now();
                    $equipment->revision_date = now();
                    $equipment->save();
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
    
                $oldChecklistData = [];
                $equipment->maintenanceDetails()->each(function ($detail) use (&$oldChecklistData) {
                    $oldChecklistData[$detail->maintenance_type_id] = $detail->checklists()->get()->toArray();
                    $detail->checklists()->delete();
                });
                $equipment->maintenanceDetails()->delete();
    
                if ($request->has('maintenance') && is_array($request->maintenance)) {
                    foreach ($request->maintenance as $mRow) {
                        if (empty($mRow['type']) || empty($mRow['frequency'])) continue;
    
                        $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                            'erp_equipment_id' => $equipment->id,
                            'maintenance_type_id' => $mRow['type'],
                            'frequency' => $mRow['frequency'],
                            'start_date' => $mRow['date'] ?? null,
                            'maintenance_bom_id' => $mRow['bom'] ?? null,
                            'time' => $mRow['time'] ?? null,
                        ]);
    
                        if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                            foreach ($mRow['checklists'] as $check) {
                                if (empty($check['checklist_id']) && empty($check['checklist_detail_id'])) continue;
                                $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                                if (!$checklistDetail) continue;
                                $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                                $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';
    
                                ErpEquipMaintenanceChecklist::create([
                                    'erp_equip_maintenance_id' => $maintenance_detail_item->id,
                                    'equipment_id' => $equipment->id,
                                    'name' => $mainChecklistName,
                                    'description' => $checklistDetail->description ?? null,
                                    'type' => $checklistDetail->data_type ?? null,
                                    'created_by' => $user->auth_user_id,
                                    'checklist_detail' => json_encode([
                                        'checklist_id' => $check['checklist_id'],
                                        'checklist_detail_id' => $check['checklist_detail_id'],
                                        'main_checklist_name' => $mainChecklistName,
                                        'name' => $checklistDetail->name,
                                        'description' => $checklistDetail->description,
                                        'data_type' => $checklistDetail->data_type
                                    ]),
                                ]);
                            }
                        } else {
                            $maintenanceTypeId = $mRow['type'];
                            if (isset($oldChecklistData[$maintenanceTypeId])) {
                                foreach ($oldChecklistData[$maintenanceTypeId] as $oldCheck) {
                                    ErpEquipMaintenanceChecklist::create([
                                        'erp_equip_maintenance_id' => $maintenance_detail_item->id,
                                        'name' => $oldCheck['name'],
                                        'description' => $oldCheck['description'] ?? null,
                                        'type' => $oldCheck['type'] ?? null,
                                        'created_by' => $oldCheck['created_by'] ?? $user->auth_user_id,
                                        'checklist_detail' => $oldCheck['checklist_detail'] ?? null,
                                    ]);
                                }
                            }
                        }
                    }
                }
    
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
            $inspectionChecklist = InspectionChecklist::where('type', 'maintenance')->select('id', 'name')->get();
            foreach($inspectionChecklist as $checklist){
                $checklist->details = InspectionChecklistDetail::where('header_id', $checklist->id)
                            ->get();
            }
           
            $maintenanceChecklist = ErpEquipMaintenanceChecklist::where('erp_equip_maintenance_id', $maintenanceTypeId)->get();
           
           
            
            return response()->json([
                'success' => true,
                'message' => 'Checklists found successfully',
                'data' => $inspectionChecklist,
                'maintenanceChecklist' => $maintenanceChecklist
            ], 200);      
     
    }

}
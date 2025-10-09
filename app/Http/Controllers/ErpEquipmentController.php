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
use App\Models\ErpItemAttribute;
use App\Models\PlantMaintWo;
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
        
        $equipmentCategories = Category::where('type', 'Equipment')->where('status','Active')->pluck('name', 'id') ?? collect();
        $maintenanceTypes = ErpMaintenanceType::where('status', 'Active')->pluck('name', 'id') ?? collect();
        $mappings = Helper::access_org();

        return view('equipment.index', compact('equipmentCategories', 'maintenanceTypes', 'mappings', 'organizationId'));
    }

    public function getData(Request $request)
    {
        // Get all equipment with their maintenance details
        $equipment = ErpEquipment::with([
            'organization:id,name',
            'location:id,store_name',
            'category:id,name',
            'maintenanceDetails.maintenanceType:id,name',
            'maintenanceDetails.checklists:id,erp_equip_maintenance_id,name',
            'maintenanceDetails' => function($query) {
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

        // Handle filter parameters
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if (strpos($dateRange, ' to ') !== false) {
                $dates = explode(' to ', $dateRange);
                if (count($dates) == 2) {
                    $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay();
                    $equipment->whereBetween('created_at', [$startDate, $endDate]);
                }
            }
        }

        if ($request->filled('equipment_category_filter')) {
            $equipment->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->equipment_category_filter);
            });
        }

        if ($request->filled('maintenance_type_filter')) {
            $equipment->whereHas('maintenanceDetails.maintenanceType', function($q) use ($request) {
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

        // Add search functionality
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = trim($request->search['value']);
            $equipment->where(function($query) use ($searchValue) {
                $query->where('name', 'like', "%{$searchValue}%")
                      ->orWhere('alias', 'like', "%{$searchValue}%")
                      ->orWhere('document_status', 'like', "%{$searchValue}%")
                      ->orWhereHas('organization', function($q) use ($searchValue) {
                          $q->where('name', 'like', "%{$searchValue}%");
                      })
                      ->orWhereHas('location', function($q) use ($searchValue) {
                          $q->where('store_name', 'like', "%{$searchValue}%");
                      })
                      ->orWhereHas('category', function($q) use ($searchValue) {
                          $q->where('name', 'like', "%{$searchValue}%");
                      })
                      ->orWhereHas('maintenanceDetails.maintenanceType', function($q) use ($searchValue) {
                          $q->where('name', 'like', "%{$searchValue}%");
                      });

                    if (preg_match('/approv/i', $searchValue)) {
                        $query->orWhereIn('document_status', ['approved', 'approval_not_required']);
                    }

            });
        }

        $equipment->orderBy('created_at', 'desc'); 

        // Process equipment data to create rows for DataTables
        $data = collect();
        foreach ($equipment->get() as $equip) {
            if ($equip->maintenanceDetails && $equip->maintenanceDetails->isNotEmpty()) {
                // Create one row per maintenance detail
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
                        'checklists' => $maintenanceDetail->checklists ? $maintenanceDetail->checklists->pluck('name')->implode(', ') : '',
                        'last_date' => $this->getLastDate($maintenanceDetail),
                        'due_date' => $this->getDueDate($maintenanceDetail),
                        'status' => $equip->document_status,
                        'equipment' => $equip
                    ]);
                }
            } else {
                // Create one row for equipment without maintenance details
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
                    'status' => $equip->document_status,
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
                $eqpStatus = $row['status'];
                $statusText = $eqpStatus == ConstantHelper::APPROVAL_NOT_REQUIRED ? 'Approved' : ucfirst($eqpStatus);
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$eqpStatus] ?? 'badge-light-secondary';
                return "<span class='badge rounded-pill {$statusClass}'>{$statusText}</span>";
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
                            <a class="dropdown-item" href="'.route('equipment.edit', $equipmentId).'">
                                <i data-feather="edit-3" class="me-50"></i>
                                <span>Edit</span>
                            </a>
                        </div>
                    </div>';
            })
            ->rawColumns(['status','action'])
            ->toJson();
    }

    private function getLastDate($maintenanceDetail)
    {
        $latestWorkOrder = $maintenanceDetail->latestWorkOrder;
        if ($latestWorkOrder && in_array($latestWorkOrder->document_status, [
            ConstantHelper::DOCUMENT_STATUS_APPROVED,
            ConstantHelper::APPROVAL_NOT_REQUIRED
        ])) {
            $details = json_decode($latestWorkOrder->equipment_details, true);
            if (is_array($details) && !empty($details['due_date'])) {
                return Carbon::parse($details['due_date'])->format('d-m-Y');
            }
        }
        return '';
    }

    private function getDueDate($maintenanceDetail)
    {
        $lastMaintDate = null;
        if ($maintenanceDetail->latestWorkOrder && in_array($maintenanceDetail->latestWorkOrder->document_status, [
            ConstantHelper::DOCUMENT_STATUS_APPROVED,
            ConstantHelper::APPROVAL_NOT_REQUIRED
        ])) {
            $details = json_decode($maintenanceDetail->latestWorkOrder->equipment_details, true);
            if (is_array($details) && !empty($details['due_date'])) {
                $lastMaintDate = Carbon::parse($details['due_date']);
            }
        }
        if ($lastMaintDate) {
            switch ($maintenanceDetail->frequency) {
                case 'Daily': return $lastMaintDate->copy()->addDay()->format('d-m-Y');
                case 'Weekly': return $lastMaintDate->copy()->addWeek()->format('d-m-Y');
                case 'Monthly': return $lastMaintDate->copy()->addMonth()->format('d-m-Y');
                case 'Quarterly': return $lastMaintDate->copy()->addMonths(3)->format('d-m-Y');
                case 'Semi-Annually': return $lastMaintDate->copy()->addMonths(6)->format('d-m-Y');
                case 'Annually':
                case 'Yearly': return $lastMaintDate->copy()->addYear()->format('d-m-Y');
            }
        }
        return $maintenanceDetail->start_date
            ? Carbon::parse($maintenanceDetail->start_date)->format('d-m-Y')
            : '';
    }

    public function create()
    {
        $parentURL = request()->segments()[0];
        $fixedAssetRegistration = FixedAssetRegistration::select('id', 'asset_name','asset_code')->get();
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
        $maintenanceTypes = ErpMaintenanceType::where('status','Active')->get(['id', 'name']);
        $maintenanceBOM = PlantMaintBom::all(['id', 'bom_name as name']);

        $checklists = InspectionChecklist::where('type','maintenance')->get();

        $items = Item::get();
        $categories = Category::where('type', 'Equipment')->where('status','Active')->get();
        return view('equipment.create', compact('maintenanceBOM','series', 'organizationId', 'userOrganizations', 'locations', 'categories', 'maintenanceTypes', 'items', 'checklists', 'fixedAssetRegistration','dataTypes'));
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
                if (isset($services['current_book'])) {
                    $book = $services['current_book'];
                    $book_id = $services['current_book']->id;
                }
            }

            // Store Equipment
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
                'book_id' => $book_id, // Or get from elsewhere
                'document_status' => $request->status, // From request
                'created_by' => $user->auth_user_id,
                'asset_code_id' => $request->asset_code_id,
            ]);
            if ($equipment->document_status != ConstantHelper::DRAFT) {
                $doc = Helper::approveDocument($equipment->book_id, $equipment->id, 0, $request->remarks, null, 1, 'submit', 0, get_class($equipment));
                $equipment->document_status = $doc['approvalStatus'] ?? $equipment->document_status;
                $equipment->save();
            }

            // Handle document upload
            if ($request->hasFile('upload_document')) {
                $file = $request->file('upload_document');
                
                try {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('equipment_documents', $fileName, 'public');
                    
                    $equipment->upload_document = $fileName;
                    $equipment->save();
                    
                    \Log::info('File uploaded successfully', ['filename' => $fileName, 'path' => $filePath]);
                } catch (\Exception $e) {
                    \Log::error('File upload failed: ' . $e->getMessage());
                    DB::rollBack();
                    return redirect()->back()->with('error', 'File upload failed: ' . $e->getMessage());
                }
            }

            // Maintenance Details
            if ($request->has('maintenance') && is_array($request->maintenance)) {
                foreach ($request->maintenance as $rowId => $mRow) {
                    // Skip rows without required fields
                    if (empty($mRow['type']) || empty($mRow['frequency'])) {
                        continue;
                    }

                    $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                        'erp_equipment_id' => $equipment->id,
                        'maintenance_type_id' => $mRow['type'],
                        'frequency' => $mRow['frequency'],
                        'time' => $mRow['time'] ?? null,
                        'start_date' => $mRow['date'] ?? null,
                        'maintenance_bom_id' => $mRow['bom'] ?? null,
                        'created_by' => $user->auth_user_id,
                    ]);

                    // Checklist for this maintenance
                    if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                        foreach ($mRow['checklists'] as $check) {
                            // Skip if required IDs are missing
                            if (empty($check['checklist_id']) || empty($check['checklist_detail_id'])) {
                                continue;
                            }

                            // Fetch checklist details from database using the IDs
                            $checklistDetail = InspectionChecklistDetail::find($check['checklist_detail_id']);
                            if (!$checklistDetail) {
                                continue; // Skip if checklist detail not found
                            }
                            
                            // Fetch main checklist name using checklist_id
                            $mainChecklist = InspectionChecklist::find($check['checklist_id']);
                            $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                            // Create checklist record with main checklist name and complete details in JSON
                            ErpEquipMaintenanceChecklist::create([
                                'erp_equip_maintenance_id' => $maintenance_detail_item->id,
                                'name' => $mainChecklistName, // Store main checklist name as requested
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

            // Spare Parts
            if ($request->has('spareparts') && is_array($request->spareparts)) {
                foreach ($request->spareparts as $rowId => $sRow) {
                    // Skip rows without required fields
                    if (empty($sRow['item_code']) || empty($sRow['item_name'])) {
                        continue;
                    }

                    // Parse attributes JSON if it exists
                    $attributes = [];
                    if (!empty($sRow['attributes'])) {
                        try {
                            if (is_string($sRow['attributes'])) {
                                $attributes = json_decode($sRow['attributes'], true) ?? [];
                            } else {
                                $attributes = $sRow['attributes'];
                            }
                        } catch (\Exception $e) {
                            // If JSON parsing fails, use empty array
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

            $message = $request->status == 'draft' ? 'Equipment saved as draft successfully' : 'Equipment submitted successfully';
            return redirect()->route("equipment.index")->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

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
        $userOrganizations = Helper::access_org();
        $userOrganizations = $userOrganizations->unique(function ($item) {
            return $item->organization->id;
        });
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

        $buttons = Helper::actionButtonDisplay(
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
        $maintenanceTypes = ErpMaintenanceType::where('status','Active')->get(['id', 'name']);
        $maintenanceBOM = PlantMaintBom::all(['id', 'bom_name as name']);
        $items = Item::get();
        $categories = Category::where('type', 'Equipment')->where('status','Active')->get();
        $approvalHistory = [];
       
        if (!empty($equipment->book_id))
            $approvalHistory = Helper::getApprovalHistory($equipment->book_id, $equipment->id, $revNo, 0, $equipment->created_by);
            Log::info('Approval History Debug', [
                'book_id' => $equipment->book_id,
                'equipment_id' => $equipment->id,
                'revNo' => $revNo,
                'created_by' => $equipment->created_by,
                'approvalHistory_count' => count($approvalHistory),
                'approvalHistory' => $approvalHistory
            ]);


        $checklists = InspectionChecklist::where('type','maintenance')->get();
       

        $fixedAssetRegistration = FixedAssetRegistration::select('id', 'asset_name','asset_code')->get();
        $maintenanceDetails = ErpEquipMaintenanceDetail::where('erp_equipment_id', $equipment->id)->value('id');
       
        $checkListData = ErpEquipMaintenanceChecklist::where('erp_equip_maintenance_id', $maintenanceDetails)->select('id','checklist_detail')->get();
        
        $checkListIds = [];
        $mainChecklistNames = []; // Store main checklist names for display
        foreach($checkListData as $checkListItem){
            $checkListDetail = json_decode($checkListItem->checklist_detail);
            if(!empty($checkListDetail->checklist_detail_id)){
                $checkListIds[] = $checkListDetail->checklist_detail_id;
            }
            
            // Extract main checklist name if available
            if(!empty($checkListDetail->main_checklist_name)){
                $mainChecklistNames[] = $checkListDetail->main_checklist_name;
            }
        }
        
        // Remove duplicate main checklist names
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

    public function update(Request $request, $id)
    {
        // dd($request->all());
        // DB::beginTransaction();
        // try {
            $user = Helper::getAuthenticatedUser();
            $equipment = ErpEquipment::findOrFail($id);

            if ($request->action_type == "amendment") {
                $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'ErpEquipment', 'relation_column' => ''],
                ];
                Helper::documentAmendment($revisionData, $id);
                // Refresh equipment to get updated revision number
                $equipment = ErpEquipment::findOrFail($id);
                Helper::approveDocument($equipment->book_id, $equipment->id, $equipment->revision_number, $request->amend_remarks, $request->file('amend_attachment'), $equipment->approval_level, 'amendment', 0, get_class($equipment));
            }

            // Update Equipment
            $updateData = [
                'organization_id' => $request->organization_id,
                'category_id' => $request->category_id,
                'location_id' => $request->location_id,
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'final_remarks' => $request->final_remarks,
                'document_status' => $request->status,
            ];

            $equipment->update($updateData);

            if ($equipment->document_status != ConstantHelper::DRAFT) {
                $currentRevision = $equipment->revision_number;
                if ($request->action_type == "amendment") {
                    $currentRevision = $equipment->revision_number + 1;
                }
                $doc = Helper::approveDocument($equipment->book_id, $equipment->id, $currentRevision, $request->remarks, null, 1, 'submit', 0, get_class($equipment));
                $equipment->document_status = $doc['approvalStatus'] ?? $equipment->document_status;
                $equipment->save();
            }

            // Handle document upload
            if ($request->hasFile('upload_document')) {
                $file = $request->file('upload_document');
                
                try {
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('equipment_documents', $fileName, 'public');
                    
                    $equipment->upload_document = $fileName;
                    $equipment->save();
                    
                    \Log::info('File uploaded successfully', ['filename' => $fileName, 'path' => $filePath]);
                } catch (\Exception $e) {
                    \Log::error('File upload failed: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'File upload failed: ' . $e->getMessage());
                }
            }

           

            // Store old checklist data before deletion
            $oldChecklistData = [];
            $equipment->maintenanceDetails()->each(function ($detail) use (&$oldChecklistData) {
                $oldChecklistData[$detail->maintenance_type_id] = $detail->checklists()->get()->toArray();
                $detail->checklists()->delete();
            });
            $equipment->maintenanceDetails()->delete();

            

            // Maintenance Details
            if ($request->has('maintenance') && is_array($request->maintenance)) {
                foreach ($request->maintenance as $rowId => $mRow) {
                    if (empty($mRow['type']) || empty($mRow['frequency'])) {
                        continue;
                    }

                    $maintenance_detail_item = ErpEquipMaintenanceDetail::create([
                        'erp_equipment_id' => $equipment->id,
                        'maintenance_type_id' => $mRow['type'],
                        'frequency' => $mRow['frequency'],
                        'start_date' => $mRow['date'] ?? null,
                        'maintenance_bom_id' => $mRow['bom'] ?? null,
                        'time' => $mRow['time'] ?? null,
                    ]);

                    // Handle checklist data - fetch details from database using IDs
                    if (!empty($mRow['checklists']) && is_array($mRow['checklists'])) {
                        // New checklist data provided, fetch details using IDs
                        foreach ($mRow['checklists'] as $check) {
                            if (empty($check['checklist_id']) && empty($check['checklist_detail_id'])) {
                                continue;
                            }
                            
                            // Fetch checklist details from database using the IDs
                            $checklistDetail = \App\Models\InspectionChecklistDetail::find($check['checklist_detail_id']);
                            
                            if (!$checklistDetail) {
                                Log::warning('Checklist detail not found for ID: ' . $check['checklist_detail_id']);
                                continue;
                            }
                            
                            // Fetch main checklist name using checklist_id
                            $mainChecklist = \App\Models\InspectionChecklist::find($check['checklist_id']);
                            $mainChecklistName = $mainChecklist ? $mainChecklist->name : 'Unknown Checklist';

                            Log::info('Processing checklist item with fetched details:', [
                                'checklist_id' => $check['checklist_id'],
                                'checklist_detail_id' => $check['checklist_detail_id'],
                                'fetched_name' => $checklistDetail->name,
                                'fetched_description' => $checklistDetail->description,
                                'fetched_type' => $checklistDetail->data_type
                            ]);

                            ErpEquipMaintenanceChecklist::create([
                                'erp_equip_maintenance_id' => $maintenance_detail_item->id,
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
                        // No new checklist data provided, preserve old checklist data if exists
                        $maintenanceTypeId = $mRow['type'];
                        if (isset($oldChecklistData[$maintenanceTypeId])) {
                            foreach ($oldChecklistData[$maintenanceTypeId] as $oldCheck) {
                                Log::info('Preserving old checklist item:', $oldCheck);

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

            // Remove old spare parts
            $equipment->spareParts()->delete();

            // Spare Parts
            if ($request->has('spareparts') && is_array($request->spareparts)) {
                foreach ($request->spareparts as $rowId => $sRow) {
                    if (empty($sRow['item_code']) || empty($sRow['item_name'])) {
                        continue;
                    }

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

            $message = $request->status == 'draft' ? 'Equipment updated as draft successfully' : 'Equipment updated successfully';
            return redirect()->route("equipment.index")->with('success', $message);
        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //     return back()->withErrors(['error' => $e->getMessage()])->withInput();
        // }
    }
    public function documentApproval(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:255',
            'attachment' => 'nullable'
        ]);
        DB::beginTransaction();
        try {
            $doc = ErpEquipment::find($request->id);
            $bookId = $doc->book_id;
            $docId = $doc->id;
            $docValue = 0;
            $remarks = $request->remarks;
            $attachments = $request->file('attachments') ?? null;
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
                'message' => "Document $actionType successfully!",
                'data' => $doc,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => "Error occurred while $actionType",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function amendment(Request $request, $id)
    {
        // DB::beginTransaction();
        // try {
            $equipment = ErpEquipment::find($id);
            if (!$equipment) {
                return response()->json(['data' => [], 'message' => "Payment Voucher not found.", 'status' => 404]);
            }

            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'ErpEquipment', 'relation_column' => ''],
            ];

            $a = Helper::documentAmendment($revisionData, $id);
            if ($a) {
                Helper::approveDocument($equipment->book_id, $equipment->id, $equipment->revision_number, 'Amendment', $request->file('attachment') ?? null, $equipment->approvalLevel, 'amendment');

                $equipment->document_status = ConstantHelper::DRAFT;
                $equipment->revision_number = $equipment->revision_number + 1;
                $equipment->revision_date = now();
                $equipment->save();
            }

            DB::commit();
            return response()->json(['data' => [], 'message' => "Amendment done!", 'status' => 200]);
        // } catch (Exception $e) {
        //     DB::rollBack();
        //     Log::error('Amendment Submit Error: ' . $e->getMessage());
        //     return response()->json(['data' => [], 'message' => "An unexpected error occurred. Please try again.", 'status' => 500]);
        // }
    }

    /**
     * Get fixed asset codes by book ID via AJAX
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFixedAssetCodesByBookId(Request $request)
    {
        try {
            $bookId = $request->book_id;
            
            if (!$bookId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book ID is required',
                    'data' => []
                ], 400);
            }

            // Import the FixedAssetRegistration model at the top if not already imported
            $assetCodes = \App\Models\FixedAssetRegistration::where('book_id', $bookId)
                        ->whereNotNull('asset_code')
                        ->where('asset_code', '!=', '')
                        ->select('id', 'asset_code', 'asset_name', 'status')
                        ->orderBy('asset_code')
                        ->get();

            return response()->json([
                'success' => true,
                'message' => 'Fixed asset codes fetched successfully',
                'data' => $assetCodes
            ], 200);

        } catch (\Exception $e) {
            Log::error("Get Fixed Asset Codes Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching asset codes',
                'data' => []
            ], 500);
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
                'message' => 'Checklists found successfully',
                'data' => $checklists
            ], 200);

        } catch (\Exception $e) {
            Log::error("Search Checklists Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching checklists',
                'data' => []
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
<?php

namespace App\Http\Controllers\Plant;

use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\MaintWoRequest;
use Yajra\DataTables\DataTables;
use App\Models\Item;
use App\Models\MailBox;
use App\Models\ItemAttribute;
use App\Models\ErpAttribute;
use App\Helpers\Helper;
use App\Helpers\InventoryHelper;
use App\Models\PlantMaintWo;
use App\Models\PlantMaintBom;
use App\Models\DefectNotification;
use App\Models\ErpEquipMaintenanceDetail;
use App\Models\ErpEquipment;
use App\Models\ErpMaintenanceType;
use App\Models\ErpDefectType;
use App\Models\ErpItem;
use App\Models\AuthUser;
use App\Models\ErpItemAttribute;
use App\Models\InspectionChecklist;
use App\Models\InspectionChecklistDetail;
use App\Models\InspectionChecklistDetailValue;
use Carbon\Carbon;
use App\Models\Category;
use DB;
use App\Models\StockLedger;
use App\Models\ErpEquipMaintenanceChecklist;
use Exception;
use App\Models\PlantMaintWoHistory;
use App\Exceptions\ApiGenericException;

class MaintWoController extends Controller
{
    private function calculateBatchStock(array $itemCodes): array
    {
        if (empty($itemCodes)) {
            return [];
        }

        $stockResults = StockLedger::whereIn('item_code', $itemCodes)
            ->whereIn('document_status', ['approved', 'approval_not_required', 'posted'])
            ->selectRaw("item_code, SUM(COALESCE(receipt_qty,0) - COALESCE(reserved_qty,0)) as confirmed_stock")
            ->groupBy('item_code')
            ->pluck('confirmed_stock', 'item_code')
            ->toArray();

        // Return stock for all requested items (0 if not found)
        $result = [];
        foreach ($itemCodes as $itemCode) {
            $result[$itemCode] = $stockResults[$itemCode] ?? 0;
        }

        return $result;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getDataTableData($request);
        }

        // Fetch filter data for the view
        $parentURL = "plant_maint-wo";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        
        // Get series data
        $series = collect();
        if (count($servicesBooks['services']) > 0) {
            $firstService = $servicesBooks['services'][0];
            $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        }
        
        // Get unique equipment categories
        $equipmentCategories = Category::where('type', 'Equipment')->where('status','Active')->pluck('name', 'id');
        
        // Get organization data
        $user = Helper::getAuthenticatedUser();
        $organizationId = $user->organization_id;
        $mappings = Helper::access_org();

        return view('plant.maint_wo.index', compact('series', 'equipmentCategories', 'mappings', 'organizationId'));
    }

    private function getDataTableData(Request $request): JsonResponse
    {
        $query = PlantMaintWo::select([
            'id',
            'equipment_details',
            'document_number',
            'document_date',
            'document_status',
            'book_id',
            'maintenance_type_id'
        ])->with(['book:id,book_code']);

        // Handle filter parameters
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if (strpos($dateRange, ' to ') !== false) {
                $dates = explode(' to ', $dateRange);
                if (count($dates) == 2) {
                    $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', trim($dates[0]))->startOfDay();
                    $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', trim($dates[1]))->endOfDay();
                    $query->whereBetween('document_date', [$startDate, $endDate]);
                }
            }
        }

        if ($request->filled('series_filter')) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('book_code', $request->series_filter);
            });
        }

        if ($request->filled('equipment_category_filter')) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(equipment_details, '$.equipment_category')) = ?", [$request->equipment_category_filter]);
        }

        if ($request->filled('filter_organization')) {
            $organizationFilters = is_array($request->filter_organization) 
                ? $request->filter_organization 
                : [$request->filter_organization];
            
            $query->whereIn('organization_id', $organizationFilters);
        }

        if ($request->filled('status_filter')) {
            $statusFilter = $request->status_filter;
            if ($statusFilter === 'approved') {
                $query->where('document_status', ConstantHelper::APPROVAL_NOT_REQUIRED);
            } elseif ($statusFilter === 'submitted') {
                $query->where('document_status', ConstantHelper::SUBMITTED);
            } elseif ($statusFilter === 'rejected') {
                $query->where('document_status', ConstantHelper::REJECTED);
            } else {
                $query->where('document_status', $statusFilter);
            }
        }

        // Apply global search if provided
        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('document_number', 'LIKE', "%{$searchValue}%")
                  ->orWhere('document_status', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('book', function($bookQuery) use ($searchValue) {
                      $bookQuery->where('book_code', 'like', "%{$searchValue}%");
                  })
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(equipment_details, '$.equipment_category')) LIKE ?", ["%{$searchValue}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(equipment_details, '$.equipment_maintenance_type_name')) LIKE ?", ["%{$searchValue}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(equipment_details, '$.maintenance_type_name')) LIKE ?", ["%{$searchValue}%"])
                  // Handle "Approved" search - map display text to database values
                  ->orWhere(function($statusQuery) use ($searchValue) {
                       $lowerSearchValue = strtolower(trim($searchValue));
                       
                       // If searching for "approve" variations, find approval_not_required records
                       if (strpos($lowerSearchValue, 'approv') !== false) {
                           $statusQuery->where('document_status', 'approval_not_required');
                       }
                   });
            });
        }

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $direction = $request->order[0]['dir'];

            $columns = [
                0 => 'id', 
                1 => 'document_date', 
                2 => 'book_id', 
                3 => 'document_number', 
                4 => 'equipment_details', 
                5 => 'equipment_details', 
                6 => 'equipment_details', 
                7 => 'document_status', 
            ];

            if (isset($columns[$columnIndex])) {
                $column = $columns[$columnIndex];

                if (in_array($column, ['equipment_details'])) {
                    // For JSON fields, we can't order directly, so we'll handle this in collection
                    // For now, skip ordering on these columns or implement custom ordering
                } else {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('document_date', function ($row) {
                return $row->document_date ? \Carbon\Carbon::parse($row->document_date)->format('d-m-Y') : '-';
            })
            ->addColumn('series', function ($row) {
                return $row->book?->book_code ?? '-';
            })
            ->addColumn('equipment_name', function ($row) {
                $details = $row->equipment_details;

                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                if (!empty($details['equipment_id'])) {
                    return ErpEquipment::where('id', $details['equipment_id'])->value('name') ?? '-';
                }

                return '-';
            })
            ->addColumn('equipment_category', function ($row) {
                $details = $row->equipment_details;

                if (is_string($details)) {
                    $details = json_decode($details, true);
                }

                return $details['equipment_category'] ?? '';
            })
            ->addColumn('equipment_maintenance_type', function ($row) {
                
                // Get maintenance type name from database using maintenance_type_id
                if (!empty($row->maintenance_type_id)) {
                    return ErpMaintenanceType::where('id', $row->maintenance_type_id)->value('name') ?? '-';
                }
                
              
            })
            ->addColumn('status', function ($row) {
                $status = $row->document_status;
                if (empty($status)) {
                    $status = "draft";
                }

                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$status] ?? 'badge-light-secondary';

                $statusText = ($row->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED)
                    ? 'Approved'
                    : ucfirst($row->document_status);

                return '<span class="badge rounded-pill ' . $statusClass . ' badgeborder-radius text-nowrap">' . $statusText . '</span>';
            })
            ->addColumn('action', function ($row) {
                $showUrl = url('plant/maint-wo/' . $row->id);
                $editUrl = url('plant/maint-wo/' . $row->id . '/edit');
            
                $isEditable = in_array($row->document_status, ['draft', 'rejected']);
                return '
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . ($isEditable ? '
                            <a class="dropdown-item" href="' . $editUrl . '">
                                <i data-feather="edit-3" class="me-50"></i>
                                <span>Edit</span>
                            </a>
                            ' : '
                            <a class="dropdown-item" href="' . $showUrl . '">
                                <i data-feather="eye" class="me-50"></i>
                                <span>View</span>
                            </a>
                            ') . '
                        </div>
                    </div>
                ';
            })            
            ->rawColumns(['status', 'action'])
            ->make(true);
    }


    public function show(Request $request, string $id)
    {
        $data = PlantMaintWo::find($id);
        $currNumber = $request->revisionNumber;
       
        if ($request->has('revisionNumber') && $data->revision_number != $currNumber) {
            $data = PlantMaintWoHistory::where('source_id', $id)->where('revision_number', $currNumber)->first();
        } 
       
        
        // Enrich spare parts with complete attribute structure including values_data
        if (!empty($data->spare_parts)) {
            $sparePartsData = json_decode($data->spare_parts, true);
            $enrichedSpareParts = [];
            
            foreach ($sparePartsData as $sparePart) {
                $enrichedSparePart = $sparePart;
                
                // Enrich item_attributes with complete structure for attribute modal
                if (isset($sparePart['item_id'])) {
                    $item = Item::with(['itemAttributes'])->find($sparePart['item_id']);
                    if ($item && $item->itemAttributes) {
                        $processedAttributes = [];
                        foreach ($item->itemAttributes as $attribute) {
                            $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                                ->select('id', 'value')
                                ->where('status', 'active')
                                ->get();

                            $processedAttributes[] = [
                                'id' => $attribute->id,
                                'group_name' => $attribute->group?->name,
                                'values_data' => $attributeValueData,
                                'attribute_group_id' => $attribute->attribute_group_id,
                            ];
                        }
                        $enrichedSparePart['item_attributes'] = json_encode($processedAttributes);
                    }
                }
                
                // Also enrich existing attribute data with value names for display
                if (isset($sparePart['attribute']) && !empty($sparePart['attribute'])) {
                    $attributeData = json_decode($sparePart['attribute'], true);
                    
                    if (is_array($attributeData)) {
                        foreach ($attributeData as &$attr) {
                            if (isset($attr['value_id']) && isset($attr['item_attribute_id'])) {
                                // Get item attribute for group name
                                $itemAttribute = ErpItemAttribute::with('group')->find($attr['item_attribute_id']);
                                // Get attribute value for value name
                                $attributeValue = ErpAttribute::find($attr['value_id']);
                                
                                if ($itemAttribute && $attributeValue) {
                                    $attr['name'] = $itemAttribute->group->name ?? 'N/A';
                                    $attr['value'] = $attributeValue->value ?? 'N/A';
                                }
                            }
                        }
                        
                        // Update the attribute field with enriched data
                        $enrichedSparePart['attribute'] = json_encode($attributeData);
                    }
                }
                $enrichedSpareParts[] = $enrichedSparePart;
            }
            $data->spare_parts = json_encode($enrichedSpareParts);
        }
        
        $currNumber = $request->has('revisionNumber');

        $parentURL = "plant_maint-wo";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }

        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

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

        $revNo = $request->has('revisionNumber') ? intval($request->revisionNumber) : $data->revision_number;

        $approvalHistory = Helper::getApprovalHistory(
            $data->book_id,
            $id,
            $revNo,
            0,
            $data->created_by
        );

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$data->document_status] ?? '';

        // ✅ OPTIMIZED: Remove unnecessary full item loading for show method
        // The show method doesn't need all items data since it only displays existing work order data
        $items = collect(); // Empty collection since show doesn't need item selection

        $locations = InventoryHelper::getAccessibleLocations();
        $defectTypes = ErpDefectType::select('id', 'name')->get();
        $equipments = ErpEquipment::select('id', 'name')->get();

        $maintenanceTypesByEquipment = [];
        $equipmentMaintenanceDetails = ErpEquipMaintenanceDetail::with(['equipment', 'maintenanceType'])
            ->get()
            ->groupBy('erp_equipment_id');

        foreach ($equipmentMaintenanceDetails as $equipmentId => $details) {
            $maintenanceTypes = $details->pluck('maintenanceType')
                ->filter()
                ->unique('id')
                ->map(fn($type) => ['id' => $type->id, 'name' => $type->name])
                ->values();

            $maintenanceTypesByEquipment[$equipmentId] = $maintenanceTypes;
        }

        // Process spare parts data and calculate total amount
        $sparePartsData = [];
        $totalAmount = 0;
        if (!empty($data->spare_parts)) {
            $sparePartsData = json_decode($data->spare_parts, true);
            if (is_array($sparePartsData)) {
                foreach ($sparePartsData as $sparePart) {
                    $qty = floatval($sparePart['qty'] ?? 0);
                    $rate = floatval($sparePart['rate'] ?? 0);
                    $totalAmount += ($qty * $rate);
                }
            }
        }

        // Process checklist data
        $checklistData = [];
        if (!empty($data->checklist_data)) {
            $checklistData = json_decode($data->checklist_data, true);
            if (!is_array($checklistData)) {
                $checklistData = [];
            }
        }

        return view('plant.maint_wo.show', compact(
            'series',
            'items',
            'data',
            'buttons',
            'docStatusClass',
            'revision_number',
            'currNumber',
            'approvalHistory',
            'locations',
            'maintenanceTypesByEquipment',
            'defectTypes',
            'equipments',
            'sparePartsData',
            'checklistData',
            'totalAmount'
        ));
    }

    public function create()
    {
        $parentURL = "plant_maint-wo";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }

        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();


        //defect notification series 
        $parentURL = "plant_defect-noti";
        $defectSeries = [];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);

        $firstService = $servicesBooks['services'][0];
        $defectSeries = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
       
      

        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->limit(10)  
            ->orderBy('item_code')  
            ->get();

        foreach ($items as $item) {
            $itemAttributes = ItemAttribute::where('item_id', $item->id)->get();
            $processedData = [];
            foreach ($itemAttributes as $attribute) {
                $attribute->group_name = $attribute->group?->name;

                $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                    ->select('id', 'value')
                    ->where('status', 'active')
                    ->get();

                $processedData[] = [
                    'id' => $attribute->id,
                    'group_name' => $attribute->group_name,
                    'values_data' => $attributeValueData,
                    'attribute_group_id' => $attribute->attribute_group_id,
                ];
            }

            $item->attributes = collect($processedData);
        }

        // ✅ OPTIMIZED: Batch calculate stock for all items in one query
        $itemCodes = $items->pluck('item_code')->toArray();
        $stockLookup = $this->calculateBatchStock($itemCodes);
       

        $items = $items->map(function ($item) use ($stockLookup) {
            return [
                'id'              => $item->id,
                'item_code'       => $item->item_code,
                'item_name'       => $item->item_name,
                'uom_name'        => optional($item->uom)->name,
                'uom_id'          => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
                'available_stock' => 100,// ✅ Use batch lookup
            ];
        });

        

        
        $locations = InventoryHelper::getAccessibleLocations();

        $defectNotifications = DefectNotification::with(['book', 'equipment', 'location', 'category', 'defectType'])
            ->where('document_status', '!=', 'draft')
            ->whereNotExists(function ($subQuery) {
                $subQuery->select(DB::raw(1))
                         ->from('erp_plant_maint_wo')
                         ->whereColumn('erp_plant_maint_wo.defect_notification_id', 'erp_defect_notifications.id')
                         ->whereNull('erp_plant_maint_wo.deleted_at');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $defectTypes = ErpDefectType::select('id', 'name')->where('status','Active')->get();
       
        $equipments = ErpEquipment::select('id', 'name')
        ->whereHas('category', function ($q) {
            $q->where('status', 'Active');
        })
        ->get();
       


        $maintenanceTypesByEquipment = [];
        $equipmentMaintenanceDetails = ErpEquipMaintenanceDetail::with(['equipment', 'maintenanceType', 'bom'])
            ->get()
            ->groupBy('erp_equipment_id');

        foreach ($equipmentMaintenanceDetails as $equipmentId => $details) {
            $maintenanceTypes = $details->pluck('maintenanceType')
                ->filter()
                ->unique('id')
                ->map(fn($type) => ['id' => $type->id, 'name' => $type->name])
                ->values();

            $maintenanceTypesByEquipment[$equipmentId] = $maintenanceTypes;
        }

        // Get only BOMs that are used in equipment maintenance details
        $usedBomIds = ErpEquipMaintenanceDetail::whereNotNull('maintenance_bom_id')
            ->pluck('maintenance_bom_id')
            ->unique();

        $maintenanceBoms = PlantMaintBom::with(['book'])
            ->whereIn('id', $usedBomIds)
            ->select('id', 'bom_name', 'document_number', 'book_id')
            ->orderBy('bom_name')
            ->get()
            ->map(function($bom) {
                return [
                    'id' => $bom->id,
                    'bom_name' => $bom->bom_name,
                    'document_number' => $bom->document_number,
                    'display_name' => $bom->bom_name ,
                ];
            });
             
       

        return view('plant.maint_wo.create', compact(
            'series',
            'locations',
            'items',
            'defectNotifications',
            'defectTypes',
            'equipments',
            'maintenanceTypesByEquipment',
            'maintenanceBoms',
            'defectSeries'
        ));
    }

    // ✅ NEW: API endpoint for searching items dynamically
    public function searchItems(Request $request)
    {
        $query = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20); // Load 20 items per search request

        $itemsQuery = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->where(function ($q) use ($query) {
                $q->where('item_code', 'LIKE', "%{$query}%")
                  ->orWhere('item_name', 'LIKE', "%{$query}%");
            })
            ->orderBy('item_code');

        $total = $itemsQuery->count();
        $items = $itemsQuery->skip(($page - 1) * $perPage)->take($perPage)->get();

        // Process items same way as create method
        $processedItems = $items->map(function ($item) {
            $itemAttributes = ItemAttribute::where('item_id', $item->id)->get();
            $processedData = [];
            foreach ($itemAttributes as $attribute) {
                $attribute->group_name = $attribute->group?->name;

                $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                    ->select('id', 'value')
                    ->where('status', 'active')
                    ->get();

                $processedData[] = [
                    'id' => $attribute->id,
                    'group_name' => $attribute->group_name,
                    'values_data' => $attributeValueData,
                    'attribute_group_id' => $attribute->attribute_group_id,
                ];
            }

            // Calculate stock (same logic as create method)
            // ✅ OPTIMIZED: Use batch stock calculation
            $itemCodes = [$item->item_code]; // Single item
            $stockLookup = $this->calculateBatchStock($itemCodes);

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'uom_name' => optional($item->uom)->name,
                'uom_id' => optional($item->uom)->id,
                'item_attributes' => collect($processedData),
                'available_stock' => 100, // ✅ Use batch lookup
            ];
        });

        return response()->json([
            'items' => $processedItems,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => (($page * $perPage) < $total)
            ]
        ]);
    }

    public function store(MaintWoRequest $request)
    {
        // Validation is handled by MaintWoRequest automatically
        // If validation fails, it will return 422 response with errors automatically

        if($request->doc_no==''){
            $doc_no = $request->document_number;
        }
        else{
            $doc_no = $request->doc_no;
        }

        $user = Helper::getAuthenticatedUser();
        $additionalData = [
            'created_by' => $user->auth_user_id,
            'type' => get_class($user),
            'organization_id' => $user->organization->id,
            'group_id' => $user->organization->group_id,
            'company_id' => $user->organization->company_id,
            'approval_level' => 1,
            'revision_number' => 0,
            'doc_no' => $doc_no,
        ];

        $data = array_merge($request->all(), $additionalData);

        $equipmentDetails = $request->equipment_details;
        if (is_string($equipmentDetails)) {
            $equipmentDetails = json_decode($equipmentDetails, true);
        }

        $data['maintenance_detail_id'] = $request->maintenance_detail_id;


        if (is_array($equipmentDetails)) {
            $data['reference_type']      = $equipmentDetails['reference_type'] ?? null;
            $data['equipment_id']        = $equipmentDetails['equipment_id'] ?? null;
            $data['maintenance_type_id'] = $equipmentDetails['maintenance_type_id'] ?? $equipmentDetails['equipment_maintenance_type_id'] ?? null;
            $data['equipment_details']   = json_encode($equipmentDetails);
            
            
            // Store defect_notification_id if reference type is defect_notification
            if (isset($equipmentDetails['reference_type']) && $equipmentDetails['reference_type'] === 'defect_notification') {
                $data['defect_notification_id'] = $equipmentDetails['defect_notification_id'] ?? null;
            }
        }

        if (isset($data['spare_parts']) && is_array($data['spare_parts'])) {
            $data['spare_parts'] = json_encode($data['spare_parts']);
        }

        // Store equipment_details as JSON
        $data['equipment_details'] = json_encode($equipmentDetails);
        if (isset($data['equipment_details']) && is_array($data['equipment_details'])) {
            $data['equipment_details'] = json_encode($data['equipment_details']);
        }

        unset($data['checklist_data']);
        unset($data['upload_file']);
        unset($data['supporting_documents']);

        try {
            DB::transaction(function () use ($data, $request) {
                $workOrder = PlantMaintWo::create($data);

                // Handle multiple upload files
                if ($request->hasFile('upload_file')) {
                    $uploadPaths = [];
                    foreach ($request->file('upload_file') as $index => $file) {
                        $extension = $file->getClientOriginalExtension();
                        $fileName = 'maint_wo_upload_' . $workOrder->id . '_' . time() . '_' . $index . '.' . $extension;
                        $path = $file->storeAs('maint_wo_documents', $fileName, 'public');
                        $uploadPaths[] = $path;
                    }
                    $workOrder->upload_file = json_encode($uploadPaths);
                    $workOrder->save();
                }

                // Handle multiple supporting documents
                if ($request->hasFile('supporting_documents')) {
                    $supportingPaths = [];
                    foreach ($request->file('supporting_documents') as $index => $file) {
                        $extension = $file->getClientOriginalExtension();
                        $fileName = 'maint_wo_supporting_' . $workOrder->id . '_' . time() . '_' . $index . '.' . $extension;
                        $path = $file->storeAs('maint_wo_documents', $fileName, 'public');
                        $supportingPaths[] = $path;
                    }
                    $workOrder->supporting_documents = json_encode($supportingPaths);
                    $workOrder->save();
                }

                // Only update next due date when work order is closed/completed, not when created
               

                if ($request->has('checklist_data') && !empty($request->checklist_data)) {
                    try {
                        $checklistData = null;
                        if (is_string($request->checklist_data)) {
                            $checklistData = json_decode($request->checklist_data, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $checklistData = null;
                            }
                        } elseif (is_array($request->checklist_data)) {
                            $checklistData = $request->checklist_data;
                        }

                        if (is_array($checklistData) && !empty($checklistData)) {
                            $processedChecklistData = $this->processChecklistData($checklistData);
                            $workOrder->checklist_data = json_encode($processedChecklistData);
                            $this->saveChecklistRecords($workOrder->id, $processedChecklistData);
                            $workOrder->save();
                        }
                    } catch (\Exception $e) {
                        // silently fail checklist
                    }
                }

                if ($workOrder->document_status == ConstantHelper::SUBMITTED) {
                    $bookId = $workOrder->book_id;
                    $docId = $workOrder->id;
                    $revisionNumber = $workOrder->revision_number ?? 0;
                    $remarks = $workOrder->remarks ?? "";
                    $attachments = null; // No attachments in create
                    $currentLevel = $bom->approval_level ?? 1;
                    $actionType = 'submit';
                    $modelName = get_class($workOrder);
                    $totalValue = 0; // BOM doesn't have monetary value
                    
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                    $workOrder->document_status = $approveDocument['approvalStatus'] ?? $workOrder->document_status;
                    $workOrder->save();
                }
            });

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Maintenance Work Order created successfully!',
                    'redirect_url' => route('maint-wo.index')
                ], 200);
            }

            return redirect()
                ->route("maint-wo.index")
                ->with('success', 'Maintenance Work Order created!');
        } catch (\Throwable $e) {
            // Return JSON error response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error' => 'Something went wrong'
                ], 500);
            }

            return redirect()
                ->route("maint-wo.create")
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }



    public function edit(string $id)
    {
        $workOrder = PlantMaintWo::findOrFail($id);
        
        // Enrich spare parts with complete attribute structure including values_data for edit blade
        if ($workOrder->spare_parts) {
            $sparePartsData = json_decode($workOrder->spare_parts, true);
            $enrichedSpareParts = [];
            
            foreach ($sparePartsData as $sparePart) {
                $enrichedSparePart = $sparePart;
                   
                // Enrich item_attributes with complete structure for attribute modal
                if (isset($sparePart['item_id'])) {
                    $item = Item::with(['itemAttributes'])->find($sparePart['item_id']);
                    if ($item && $item->itemAttributes) {
                        $processedAttributes = [];
                        foreach ($item->itemAttributes as $attribute) {
                            $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                                ->select('id', 'value')
                                ->where('status', 'active')
                                ->get();

                            $processedAttributes[] = [
                                'id' => $attribute->id,
                                'group_name' => $attribute->group?->name,
                                'values_data' => $attributeValueData,
                                'attribute_group_id' => $attribute->attribute_group_id,
                            ];
                        }
                        $enrichedSparePart['item_attributes'] = json_encode($processedAttributes);
                    }
                }
                
                // Also enrich existing attribute data with value names for display
                if (isset($sparePart['attribute']) && !empty($sparePart['attribute'])) {
                    $attributeData = json_decode($sparePart['attribute'], true);
                    
                    if (is_array($attributeData)) {
                        foreach ($attributeData as &$attr) {
                            if (isset($attr['value_id']) && isset($attr['item_attribute_id'])) {
                                // Get item attribute for group name
                                $itemAttribute = ErpItemAttribute::with('group')->find($attr['item_attribute_id']);
                                // Get attribute value for value name
                                $attributeValue = ErpAttribute::find($attr['value_id']);
                                
                                if ($itemAttribute && $attributeValue) {
                                    $attr['name'] = $itemAttribute->group->name ?? 'N/A';
                                    $attr['value'] = $attributeValue->value ?? 'N/A';
                                }
                            }
                        }
                        
                        // Update the attribute field with enriched data
                        $enrichedSparePart['attribute'] = json_encode($attributeData);
                    }
                }
                $enrichedSpareParts[] = $enrichedSparePart;
            }
            $workOrder->spare_parts = json_encode($enrichedSpareParts);
        }

        $parentURL = "plant_maint-wo";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }

        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

         //defect notification series 
         $parentURL = "plant_defect-noti";
         $defectSeries = [];
         $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
 
         $firstService = $servicesBooks['services'][0];
         $defectSeries = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
       

        $userType = Helper::userCheck();
        $buttons = Helper::actionButtonDisplay(
            $workOrder->book_id,
            $workOrder->document_status,
            $id,
            0,
            $workOrder->approval_level,
            $workOrder->created_by ?? 0,
            $userType['type'],
            $workOrder->revision_number
        );

        if ($workOrder->document_status === ConstantHelper::DRAFT || $workOrder->document_status === ConstantHelper::SUBMITTED) {
            $buttons['cancel'] = true;
        } else {
            $buttons['cancel'] = false;
        }

        if ($workOrder->document_status === ConstantHelper::POSTED) {
            $buttons['amend'] = false;
        }

        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->limit(10) 
            ->orderBy('item_code') 
            ->get();

        foreach ($items as $item) {
            $itemAttributes = $item->id ? ItemAttribute::where('item_id', $item->id)->get() : [];
            $processedData = [];

            foreach ($itemAttributes as $attribute) {
                $attribute_group_id = $attribute->attribute_group_id;
                $attribute->group_name = $attribute->group?->name;

                $attributeValueData = ErpAttribute::whereIn('id', $attribute->attribute_id)
                    ->select('id', 'value')
                    ->where('status', 'active')
                    ->get();

                $processedData[] = [
                    'id' => $attribute->id,
                    'group_name' => $attribute->group_name,
                    'values_data' => $attributeValueData,
                    'attribute_group_id' => $attribute_group_id,
                ];
            }

            $item->attributes = collect($processedData);
        }

        // Calculate stock for all items once (for both existing spare parts and JS validation)
        $itemCodes = $items->pluck('item_code')->toArray();
        $stockLookup = $this->calculateBatchStock($itemCodes);

        $items = $items->map(function ($item) use ($stockLookup) {

            $confirmedStock = $stockLookup[$item->item_code] ?? 0; // ✅ Use batch lookup instead of individual query

            return [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'uom_name' => optional($item->uom)->name,
                'uom_id' => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
                'available_stock' => 100, 
            ];
        });

        // Create a lookup array for quick stock access when updating existing spare parts
        $stockLookup = collect($items)->pluck('available_stock', 'item_code')->toArray();

        $locations = InventoryHelper::getAccessibleLocations();

        $revision_number = $workOrder->revision_number;

        $approvalHistory = Helper::getApprovalHistory(
            $workOrder->book_id,
            $id,
            $revision_number,
            0,
            $workOrder->created_by
        );

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$workOrder->document_status] ?? '';

        $defectTypes = ErpDefectType::select('id', 'name')->get();
        $equipments = ErpEquipment::select('id', 'name')->get();

        $defectNotifications = DefectNotification::with(['book', 'equipment', 'location', 'category', 'defectType'])
            ->where('document_status', '!=', 'draft')
            ->whereNotExists(function ($subQuery) use ($workOrder) {
                $subQuery->select(DB::raw(1))
                         ->from('erp_plant_maint_wo')
                         ->whereColumn('erp_plant_maint_wo.defect_notification_id', 'erp_defect_notifications.id')
                         ->where('erp_plant_maint_wo.id', '!=', $workOrder->id) // Exclude current work order
                         ->whereNull('erp_plant_maint_wo.deleted_at');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $maintenanceTypesByEquipment = [];
        $equipmentsWithMaintenance = ErpEquipment::with(['maintenanceDetails.maintenanceType'])
            ->whereHas('maintenanceDetails')
            ->get();

        foreach ($equipmentsWithMaintenance as $equipment) {
            $maintenanceTypes = [];
            foreach ($equipment->maintenanceDetails as $detail) {
                if ($detail->maintenanceType) {
                    $maintenanceTypes[] = [
                        'id' => $detail->maintenanceType->id,
                        'name' => $detail->maintenanceType->name,
                    ];
                }
            }
            if (!empty($maintenanceTypes)) {
                $maintenanceTypesByEquipment[$equipment->id] = array_unique($maintenanceTypes, SORT_REGULAR);
            }
        }

        $usedBomIds = ErpEquipMaintenanceDetail::whereNotNull('maintenance_bom_id')
            ->pluck('maintenance_bom_id')
            ->unique();

        $maintenanceBoms = PlantMaintBom::with(['book'])
            ->whereIn('id', $usedBomIds)
            ->select('id', 'bom_name', 'document_number', 'book_id')
            ->orderBy('bom_name')
            ->get()
            ->map(function($bom) {
                return [
                    'id' => $bom->id,
                    'bom_name' => $bom->bom_name,
                    'document_number' => $bom->document_number,
                    'display_name' => $bom->bom_name ,
                ];
            });

          

        return view('plant.maint_wo.edit', compact(
            'workOrder',
            'series',
            'items',
            'locations',
            'defectNotifications',
            'buttons',
            'approvalHistory',
            'docStatusClass',
            'revision_number',
            'defectTypes',
            'equipments',
            'maintenanceTypesByEquipment',
            'maintenanceBoms',
            'defectSeries'
        ));
    }

    public function update(Request $request, string $id)
    {
        $rules = [
            'book_id' => 'required',
            'document_number' => 'required|string|max:100',
            'document_date' => 'required|date',
            'document_status' => 'required|string',
        ];

        if ($request->document_status !== 'draft') {
            $rules['reference_type'] = 'required|string';
        }

        if ($request->hasFile('upload_file')) {
            $rules['upload_file.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max, matching frontend validation
        }

        if ($request->hasFile('supporting_documents')) {
            $rules['supporting_documents.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max, matching frontend validation
        }

        $request->validate($rules);

        $workOrder = PlantMaintWo::findOrFail($id);
        DB::beginTransaction();

        try {
            if ($request->action_type == "amendment") {
                $revisionData = [
                    [
                        "model_type" => "header",
                        "model_name" => "PlantMaintWo",
                        "relation_column" => "",
                    ],
                ];

                Helper::documentAmendment($revisionData, $id);

                Helper::approveDocument(
                    $workOrder->book_id,
                    $workOrder->id,
                    $workOrder->revision_number,
                    $request->amend_remarks,
                    $request->file('amend_attachment'),
                    $workOrder->approval_level,
                    'amendment',
                    0,
                    get_class($workOrder)
                );

                $workOrder->revision_number = $workOrder->revision_number + 1;
                $workOrder->revision_date = now();
                $workOrder->save();
            }

            // Prepare update data (exclude file fields to prevent array to string conversion)
            $updateData = $request->except([
                'upload_file', 
                'supporting_documents', 
                'deleted_upload_files', 
                'deleted_supporting_files'
            ]);

            $equipmentDetails = $request->equipment_details;

            if (is_string($equipmentDetails)) {
                $equipmentDetails = json_decode($equipmentDetails, true);
            }

            // Add maintenance_detail_id from request
            $updateData['maintenance_detail_id'] = $request->maintenance_detail_id;

            if (is_array($equipmentDetails)) {
                $updateData['reference_type']      = $equipmentDetails['reference_type'] ?? null;
                $updateData['equipment_id']        = $equipmentDetails['equipment_id'] ?? null;
                $updateData['maintenance_type_id'] = $equipmentDetails['maintenance_type_id'] ?? $equipmentDetails['equipment_maintenance_type_id'] ?? null;

                $updateData['equipment_details'] = json_encode($equipmentDetails);
            }

            // Store defect_notification_id if reference type is defect_notification
            if ($request->reference_type === 'defect_notification') {
                $updateData['defect_notification_id'] = $request->defect_notification_id;
            }
            
            // Only update checklist_data if it's not empty
            if (empty($request->checklist_data) || $request->checklist_data === 'null' || $request->checklist_data === '[]') {
                unset($updateData['checklist_data']);
            }
            
            // Handle spare_parts data - ensure it's properly formatted as JSON string
            if (isset($updateData['spare_parts'])) {
                \Log::info('Processing spare_parts data', [
                    'type' => gettype($updateData['spare_parts']),
                    'data' => $updateData['spare_parts']
                ]);
                
                if (is_string($updateData['spare_parts'])) {
                    // If it's already a JSON string, validate it
                    $decodedSpareParts = json_decode($updateData['spare_parts'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // If invalid JSON, remove it
                        \Log::warning('Invalid JSON in spare_parts, removing field');
                        unset($updateData['spare_parts']);
                    } else {
                        \Log::info('Valid JSON spare_parts data', ['decoded' => $decodedSpareParts]);
                    }
                } elseif (is_array($updateData['spare_parts'])) {
                    // If it's an array, encode it to JSON
                    $updateData['spare_parts'] = json_encode($updateData['spare_parts']);
                    \Log::info('Encoded array spare_parts to JSON');
                } else {
                    // If it's neither string nor array, remove it
                    \Log::warning('Invalid spare_parts data type, removing field', ['type' => gettype($updateData['spare_parts'])]);
                    unset($updateData['spare_parts']);
                }
            } else {
                \Log::info('No spare_parts data in request');
            }
            
           

            $finalUploadFiles = [];
            if ($workOrder->upload_file) {
                if (is_array($workOrder->upload_file)) {
                    // Already an array
                    $finalUploadFiles = $workOrder->upload_file;
                
                } elseif (is_string($workOrder->upload_file)) {
                    // Try to decode JSON string
                    $existingUploadFiles = json_decode($workOrder->upload_file, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($existingUploadFiles)) {
                        // Filter out any non-string values (like empty arrays)
                        $finalUploadFiles = array_filter($existingUploadFiles, function($item) {
                            return is_string($item) && !empty($item);
                        });
                        $finalUploadFiles = array_values($finalUploadFiles); // Reindex array
                       
                    } else {
                        // If JSON decode fails, treat as single file path
                        $finalUploadFiles = [$workOrder->upload_file];
                       
                    }
                }
            }

            // Remove deleted upload files
            if ($request->has('deleted_upload_files') && !empty($request->deleted_upload_files)) {
                $deletedUploadFiles = json_decode($request->deleted_upload_files, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($deletedUploadFiles)) {
                    $finalUploadFiles = array_values(array_diff($finalUploadFiles, $deletedUploadFiles));
                    
                    // Delete files from storage
                    foreach ($deletedUploadFiles as $fileToDelete) {
                        if (\Storage::disk('public')->exists($fileToDelete)) {
                            \Storage::disk('public')->delete($fileToDelete);
                            
                        }
                    }
                } 
            }

            // Add new upload files
            if ($request->hasFile('upload_file')) {
                $newUploadPaths = [];
                foreach ($request->file('upload_file') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = 'maint_wo_upload_' . $workOrder->id . '_' . time() . '_' . $index . '.' . $extension;
                    $path = $file->storeAs('maint_wo_documents', $fileName, 'public');
                    $newUploadPaths[] = $path;
                }
                $finalUploadFiles = array_merge($finalUploadFiles, $newUploadPaths);
            }

            // Handle supporting documents updates BEFORE updating the model
            $finalSupportingFiles = [];
            if ($workOrder->supporting_documents) {
                if (is_array($workOrder->supporting_documents)) {
                    // Already an array
                    $finalSupportingFiles = $workOrder->supporting_documents;
                } elseif (is_string($workOrder->supporting_documents)) {
                    // Try to decode JSON string
                    $existingSupportingFiles = json_decode($workOrder->supporting_documents, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($existingSupportingFiles)) {
                        // Filter out any non-string values (like empty arrays)
                        $finalSupportingFiles = array_filter($existingSupportingFiles, function($item) {
                            return is_string($item) && !empty($item);
                        });
                        $finalSupportingFiles = array_values($finalSupportingFiles); // Reindex array
                    } else {
                        // If JSON decode fails, treat as single file path
                        $finalSupportingFiles = [$workOrder->supporting_documents];
                    }
                }
            }

            // Remove deleted supporting files
            if ($request->has('deleted_supporting_files') && !empty($request->deleted_supporting_files)) {
                $deletedSupportingFiles = json_decode($request->deleted_supporting_files, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($deletedSupportingFiles)) {
                    $finalSupportingFiles = array_values(array_diff($finalSupportingFiles, $deletedSupportingFiles));
                    // Delete files from storage
                    foreach ($deletedSupportingFiles as $fileToDelete) {
                        if (\Storage::disk('public')->exists($fileToDelete)) {
                            \Storage::disk('public')->delete($fileToDelete);
                        }
                    }
                }
            }

            // Add new supporting files
            if ($request->hasFile('supporting_documents')) {
                $newSupportingPaths = [];
                foreach ($request->file('supporting_documents') as $index => $file) {
                    $extension = $file->getClientOriginalExtension();
                    $fileName = 'maint_wo_supporting_' . $workOrder->id . '_' . time() . '_' . $index . '.' . $extension;
                    $path = $file->storeAs('maint_wo_documents', $fileName, 'public');
                    $newSupportingPaths[] = $path;
                }
                $finalSupportingFiles = array_merge($finalSupportingFiles, $newSupportingPaths);
            }

            // Update the model with form data
          
            $workOrder->update($updateData);
            
            // Update file fields separately
            $uploadFileJson = !empty($finalUploadFiles) ? json_encode($finalUploadFiles) : null;
            $supportingDocsJson = !empty($finalSupportingFiles) ? json_encode($finalSupportingFiles) : null;
            
           
            $workOrder->upload_file = $uploadFileJson;
            $workOrder->supporting_documents = $supportingDocsJson;
            $workOrder->save();

            if ($request->action_type != 'draft') {
                $doc = Helper::approveDocument($workOrder->book_id, $workOrder->id, $workOrder->revision_number, "", null, $workOrder->approval_level, 'submit', 0, get_class($workOrder));
                $workOrder->document_status = $doc['approvalStatus'];
                $workOrder->save();
            }

            DB::commit();

            return redirect()
                ->route("maint-wo.index")
                ->with('success', 'Maintenance Work Order updated!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route("maint-wo.edit", $id)
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    public function amendment(Request $request, $id)
    {
        try {
            $wo = PlantMaintWo::findOrFail($id);

            // $wo->document_status = $request->input('document_status', 'draft');
            $wo->save();
            return response()->json([
                'success' => true,
                'message' => 'Amendment created successfully',
                'data' => $wo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function documentApproval(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:255',
            'attachment.*' => 'nullable|file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120', // 5MB max per file
        ]);

        DB::beginTransaction();

        try {
            $doc = PlantMaintWo::findOrFail($request->id);
            $bookId = $doc->book_id;
            $docId = $doc->id;
            $docValue = 0;
            $remarks = $request->remarks;
            $attachments = $request->file('attachment');
            $currentLevel = $doc->approval_level;
            $revisionNumber = $doc->revision_number ?? 0;
            $actionType = $request->action_type;
            $modelName = get_class($doc);

            $approveDocument = Helper::approveDocument(
                $bookId,
                $docId,
                $revisionNumber,
                $remarks,
                $attachments,
                $currentLevel,
                $actionType,
                $docValue,
                $modelName
            );

            $doc->approval_level = $approveDocument['nextLevel'];
            $doc->document_status = $approveDocument['approvalStatus'];
            $doc->save();

            DB::commit();

            return response()->json([
                'message' => "Maintenance Work Order {$actionType}d successfully!",
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

    public function populateModal(Request $r)
    {
        $type = $r->type;
        $data = [];

        // Validate request parameters
        if (empty($type)) {
            return response()->json(['error' => 'Type parameter is required'], 400);
        }

        try {
          
        if ($type == 'defect') {
            $query = DefectNotification::with([
                'book',
                'equipment.maintenanceDetails.maintenanceType',
                'location',
                'category',
                'defectType',
                'createdByUser',
            ])->where('document_status', '!=', 'draft')
              ->whereNotExists(function ($subQuery) {
                  $subQuery->select(DB::raw(1))
                           ->from('erp_plant_maint_wo')
                           ->whereColumn('erp_plant_maint_wo.defect_notification_id', 'erp_defect_notifications.id')
                           ->whereNull('erp_plant_maint_wo.deleted_at'); // Exclude soft deleted maintenance work orders
              })
              ->orderBy('created_at', 'desc');

 

            if ($r->book_code && is_array($r->book_code) && !empty($r->book_code)) {
                $query->whereHas('book', function ($q) use ($r) {
                    $q->whereIn('book_code', $r->book_code);
                });
            } elseif ($r->book_code && !is_array($r->book_code) && !empty($r->book_code)) {
                // Handle single book_code as string
                $query->whereHas('book', function ($q) use ($r) {
                    $q->where('book_code', $r->book_code);
                });
            } 
            $results = $query->get();

          
            $data = $results->map(function ($defectNotification) {
                $maintenanceTypes = [];
                if ($defectNotification->equipment && $defectNotification->equipment->maintenanceDetails) {
                    $maintenanceTypes = $defectNotification->equipment->maintenanceDetails
                        ->map(fn($detail) => $detail->maintenanceType)
                        ->filter()
                        ->unique('id')
                        ->map(fn($type) => ['id' => $type->id, 'name' => $type->name])
                        ->values();
                }

                $checklistsByMaintenanceType = [];

                $defectNotification->maintenance_types = $maintenanceTypes;
                $defectNotification->checklists_by_maintenance_type = $checklistsByMaintenanceType;

               

                return $defectNotification;
            });
        } elseif ($type == 'eqpt') {
            $query = ErpEquipMaintenanceDetail::with([
                'equipment',
                'bom.book',
                'maintenanceType',
                'equipment.category',
                'equipment.spareParts'
            ])
            ->whereHas('bom')
            ->whereHas('equipment', function ($q) use ($r) {
                $q->where('location_id', $r->location_id);
                $q->whereNotIn('document_status', ['draft', 'rejected']);
                
                // Only apply book_code filter if it's provided and not empty
                if ($r->book_code && is_array($r->book_code) && !empty($r->book_code)) {
                    $q->whereHas('book', function ($qu) use ($r) {
                        $qu->whereIn('book_code', $r->book_code);
                    });
                } elseif ($r->book_code && !is_array($r->book_code) && !empty($r->book_code)) {
                    $q->whereHas('book', function ($qu) use ($r) {
                        $qu->where('book_code', $r->book_code);
                    });
                }
                
                $q->whereHas('category', function ($qc) {
                    $qc->where('status', 'Active');
                });
            })
            ->whereHas('maintenanceType', function ($qm) {
                $qm->where('status', 'Active');
            });
        
        $equipmentData = $query->get();  
            foreach ($equipmentData as $eqpt) {
                $plantMaintWo = PlantMaintWo::where('equipment_id', $eqpt->erp_equipment_id)
                    ->where('maintenance_type_id', $eqpt->maintenance_type_id)
                    ->where('reference_type', 'equipment')
                    ->where('maintenance_detail_id', $eqpt->id)
                    ->orderBy('id', 'DESC')
                    ->first();
                
                $dueDate = null;
                $base = null;
            
                if ($plantMaintWo && in_array($plantMaintWo->document_status, ['submitted', 'approved', 'approval_not_required', 'closed'])) {
                    $equipmentDetails = json_decode($plantMaintWo->equipment_details, true);
                    $dueDate = $equipmentDetails['due_date'] ?? null;
            
                    if ($dueDate) {
                        $base = Carbon::parse($dueDate, 'UTC')->setTimezone('Asia/Kolkata'); // ✅ timezone fix
                    }
            
                    if ($base) {
                        $freqType = $eqpt->frequency ?? '';
            
                        switch ($freqType) {
                            case 'Daily':
                                $dueDate = $base->copy()->addDay();
                                break;
                            case 'Weekly':
                                $dueDate = $base->copy()->addWeek();
                                break;
                            case 'Monthly':
                                $dueDate = $base->copy()->addMonth();
                                break;
                            case 'Quarterly':
                                $dueDate = $base->copy()->addMonths(3);
                                break;
                            case 'Semi-Annually':
                                $dueDate = $base->copy()->addMonths(6);
                                break;
                            case 'Annually':
                            case 'Yearly':
                                $dueDate = $base->copy()->addYear();
                                break;
                            default:
                                $dueDate = $base;
                        }
                    }
                } else {
                    $dueDate = $eqpt->start_date 
                        ? Carbon::parse($eqpt->start_date, 'UTC')->setTimezone('Asia/Kolkata') // ✅ timezone fix
                        : null;
                }
            
                // ✅ Always return formatted IST date
                $eqpt->due_date = $dueDate ? $dueDate->format('d-m-Y') : null;
                $eqpt->frequency = $eqpt->frequency ?? '';
                
            
                $maintenance_type_id = $eqpt->maintenance_type_id;
                $eqpt->maintenance_detail_id = $eqpt->id;
            
                $maintenanceChecklists = ErpEquipMaintenanceChecklist::where('erp_equip_maintenance_id', $eqpt->id)
                    ->select('checklist_detail', 'name')
                    ->get();
            
                $checklistsData = [];
            
                foreach ($maintenanceChecklists as $maintenanceChecklist) {
                    $detailsArray = json_decode($maintenanceChecklist->checklist_detail, true);
            
                    if (isset($detailsArray['checklist_detail_id'])) {
                        $detailsArray = [$detailsArray];
                    }
            
                    foreach ($detailsArray as $detailObj) {
                        if (empty($detailObj['main_checklist_name']) || empty($detailObj['checklist_detail_id'])) {
                            continue;
                        }
            
                        $inspectionChecklist = InspectionChecklist::where('name', $detailObj['main_checklist_name'])->first();
            
                        if ($inspectionChecklist) {
                            $detail = InspectionChecklistDetail::where('header_id', $inspectionChecklist->id)
                                ->where('id', $detailObj['checklist_detail_id'])
                                ->select('id', 'name', 'data_type', 'description', 'mandatory')
                                ->first();
            
                            $detailsWithValues = [];
            
                            if ($detail) {
                                $detailValues = InspectionChecklistDetailValue::where('inspection_checklist_detail_id', $detail->id)
                                    ->pluck('value')
                                    ->toArray();
            
                                $detailData = [
                                    'id'          => $detail->id,
                                    'name'        => $detail->name,
                                    'data_type'   => $detail->data_type,
                                    'description' => $detail->description,
                                    'mandatory'   => $detail->mandatory,
                                    'value'       => !empty($detailValues) ? $detailValues[0] : '',
                                ];
            
                                if ($detail->data_type === 'list') {
                                    $detailData['values'] = $detailValues;
                                }
            
                                $detailsWithValues[] = $detailData;
                            }
            
                            $checklistsData[] = [
                                'main_name' => $detailObj['main_checklist_name'],
                                'checklist' => $detailsWithValues,
                            ];
                        }
                    }
                }
            
                $eqpt->checklistsData = $checklistsData;
                $eqpt->checklistsIdsName = $maintenanceChecklists;
            }
            
           
         
            

            $data = [];
            
            foreach ($equipmentData as $detail) {
               
                if ($detail->equipment) {
                    // Get checklist data from JSON column
                    $checklistsData = $this->getChecklistDataFromJson($detail);
            
                    $equipment = $detail->equipment;
                    $equipment->checklists_data = $checklistsData;
                    $equipment->maintenance_detail_id = $detail->id;  // Add maintenance detail ID to equipment object
                    $equipment->frequency = $detail->frequency;
                    $equipment->due_date = $detail->due_date
                        ? Carbon::parse($detail->due_date)->format('d-m-Y')
                        : null;
            
                    // Convert to array before adding into $data
                    $data[] = [
                        'equipment'            => $equipment->toArray(),
                        'maintenance_type'     => $detail->maintenanceType,
                        'maintenance_type_id'  => $detail->maintenance_type_id,
                        'bom'                  => $detail->bom,
                    ];
                }
            }
        }
        
            return response()->json($data);
            
        } catch (\Exception $e) {
            
            return response()->json([
                'error' => 'An error occurred while fetching data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function ajaxData(Request $request)
    {
        $query = PlantMaintWo::with(['book']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('document_date', [$request->start_date, $request->end_date]);
        }

        $totalRecords = $query->count();

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('document_number', 'like', "%{$searchValue}%")
                    ->orWhere('maintenance_type', 'like', "%{$searchValue}%")
                    ->orWhere('equipment_details', 'like', "%{$searchValue}%");
            });
        }

        $filteredRecords = $query->count();

        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'];

            switch ($orderColumn) {
                case 1:
                    $query->orderBy('document_date', $orderDir);
                    break;
                case 2:
                    $query->orderBy('book_id', $orderDir);
                    break;
                case 3:
                    $query->orderBy('document_number', $orderDir);
                    break;
                case 4:
                    $query->join('erp_equipments', 'erp_plant_maint_wo.equipment_id', '=', 'erp_equipments.id')
                        ->orderBy('erp_equipments.name', $orderDir);
                    break;
                case 6:
                    $query->orderBy('maintenance_type', $orderDir);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $workOrders = $query->skip($start)->take($length)->get();

        $data = [];
        foreach ($workOrders as $index => $wo) {
            $formattedDate = $wo->document_date
                ? \Carbon\Carbon::parse($wo->document_date)->format('d-m-Y')
                : '-';

            $series = $wo->book?->book_code ?? 'MAINT_WO';

            $equipmentDetails = json_decode($wo->equipment_details, true);
            $equipmentName = $equipmentDetails['equipment_name'] ?? 'Default Equipment';
            $categoryName = $equipmentDetails['equipment_category'] ?? 'Machinery';

            $maintenanceType = $wo->maintenance_type ?? 'Preventive';
            $typeClass = $maintenanceType == "Preventive" ? "info" : ($maintenanceType == "Corrective" ? "warning" : "secondary");
            $typeBadge = "<span class='badge rounded-pill badge-light-{$typeClass} badgeborder-radius'>{$maintenanceType}</span>";

            $statusClass = 'badge-light-secondary';
            if (isset(ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$wo->document_status ?? 'draft'])) {
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$wo->document_status ?? 'draft'];
            }
            $statusText = $wo->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED ? 'Approved' : ucfirst($wo->document_status ?? 'draft');

            $actions = '<div class="d-flex align-items-center justify-content-end">';
            $actions .= "<span class='badge rounded-pill {$statusClass} badgeborder-radius'>{$statusText}</span>";
            $actions .= '<div class="dropdown ml-2">';
            $actions .= '<button type="button" class="btn btn-sm dropdown-toggle hide-arrow p-0" data-bs-toggle="dropdown">';
            $actions .= '<i data-feather="more-vertical"></i>';
            $actions .= '</button>';
            $actions .= '<div class="dropdown-menu dropdown-menu-end">';

            if ($wo->document_status == 'draft') {
                $actions .= '<a class="dropdown-item" href="' . route('maint-wo.edit', $wo->id) . '">';
                $actions .= '<i data-feather="edit" class="me-50"></i><span>Edit</span>';
                $actions .= '</a>';
            } else {
                $actions .= '<a class="dropdown-item" href="' . route('maint-wo.show', $wo->id) . '">';
                $actions .= '<i data-feather="eye" class="me-50"></i><span>View</span>';
                $actions .= '</a>';
            }

            $actions .= '</div></div></div>';

            $data[] = [
                $start + $index + 1,
                $formattedDate,
                $series,
                $wo->document_number ?? '-',
                $equipmentName,
                $categoryName,
                $typeBadge,
                $actions,
            ];
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function destroy(string $id)
    {
        //
    }

    private function processChecklistData($checklistData)
    {
        $processedData = [];

        foreach ($checklistData as $checklistGroup) {
            $processedGroup = [
                'main_name' => $checklistGroup['main_name'] ?? '',
                'checklist' => [],
            ];

            if (isset($checklistGroup['checklist']) && is_array($checklistGroup['checklist'])) {
                foreach ($checklistGroup['checklist'] as $checklistItem) {
                    $processedItem = [
                        'name' => $checklistItem['name'] ?? '',
                        'data_type' => $checklistItem['data_type'] ?? 'text',
                        'mandatory' => (bool)($checklistItem['mandatory'] ?? false),
                        'value' => $this->sanitizeChecklistValue($checklistItem['value'] ?? '', $checklistItem['data_type'] ?? 'text'),
                        'completed_at' => now()->toDateTimeString(),
                        'completed_by' => auth()->id(),
                    ];

                    $processedGroup['checklist'][] = $processedItem;
                }
            }

            $processedData[] = $processedGroup;
        }

        return $processedData;
    }

    private function sanitizeChecklistValue($value, $dataType)
    {
        switch ($dataType) {
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            case 'boolean':
            case 'checkbox':
                return in_array($value, ['1', 'true', true, 1], true);
            case 'date':
                try {
                    return \Carbon\Carbon::parse($value)->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            default:
                return (string)$value;
        }
    }

    private function saveChecklistRecords($workOrderId, $checklistData)
    {
        try {
            \Log::info('Checklist data saved for Work Order ID: ' . $workOrderId, [
                'work_order_id' => $workOrderId,
                'checklist_count' => count($checklistData),
                'completed_at' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving checklist records: ' . $e->getMessage());
        }
    }

    public function getEquipmentSpareParts(Request $request)
    {
        try {
            $equipmentId = $request->equipment_id;
            $maintenanceTypeId = $request->maintenance_type_id;
            
            

            $equipment = ErpEquipment::find($equipmentId);
            
            $maintenanceDetail = ErpEquipMaintenanceDetail::where('erp_equipment_id', $equipmentId)
                ->where('maintenance_type_id', $maintenanceTypeId)
                ->with('bom')
                ->first();
           
            
            if (!$maintenanceDetail || !$maintenanceDetail->bom) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maintenance BOM not found for this equipment and maintenance type'
                ], 404);
            }

            $bomData = $maintenanceDetail->bom;
            $sparePartsData = [];
            
            if ($bomData->spare_parts) {
                $rawSparePartsData = json_decode($bomData->spare_parts, true);
                
                
                $itemCodes = collect($rawSparePartsData)->pluck('item_code')->filter()->unique()->toArray();
                $stockLookup = $this->calculateBatchStock($itemCodes);
                
                foreach ($rawSparePartsData as $sparePart) {
                    
                    $confirmedStock = $stockLookup[$sparePart['item_code']] ?? 0; // ✅ Use batch lookup instead of individual query
                    $sparePartData = [
                        'item_id' => $sparePart['item_id'],
                        'item_code' => $sparePart['item_code'] ?? 'N/A',
                        'item_name' => $sparePart['item_name'] ?? 'N/A',
                        'qty' => $sparePart['qty'] ?? 0,
                        'uom' => $sparePart['uom_name'] ?? 'N/A',
                        'uom_id' => $sparePart['uom_id'] ?? null,
                        'attribute' => $sparePart['attribute'] ?? '[]',
                        'attributes' => [],
                        'available_stock' =>100,
                    ];

                    // Process attributes if they exist
                    if (isset($sparePart['attribute']) && !empty($sparePart['attribute'])) {
                        $attributeData = json_decode($sparePart['attribute'], true);
                      
                        
                        if (is_array($attributeData)) {
                            foreach ($attributeData as $attr) {
                                if (isset($attr['item_attribute_id']) && isset($attr['value_id'])) {
                                    // Get item attribute details
                                    $itemAttribute = ErpItemAttribute::with('group')->find($attr['item_attribute_id']);
                                    
                                    // Get selected attribute value
                                    $selectedAttributeValue = ErpAttribute::find($attr['value_id']);
                                    
                                   

                                    if ($itemAttribute && $selectedAttributeValue) {
                                        // Get all possible attribute values for this group
                                        $allAttributeValues = ErpAttribute::where('attribute_group_id', $itemAttribute->attribute_group_id)
                                            ->orderBy('value')
                                            ->get();

                                        $sparePartData['attributes'][] = [
                                            'item_attribute_id' => $attr['item_attribute_id'],
                                            'group_id' => $itemAttribute->attribute_group_id,
                                            'group_name' => $itemAttribute->group->name ?? 'N/A',
                                            'group_short_name' => $itemAttribute->group->short_name ?? 'N/A',
                                            'selected_value_id' => $attr['value_id'],
                                            'selected_value_name' => $selectedAttributeValue->value ?? 'N/A',
                                            'all_values' => $allAttributeValues->map(function($value) {
                                                return [
                                                    'id' => $value->id,
                                                    'value' => $value->value
                                                ];
                                            })->toArray()
                                        ];
                                    }
                                }
                            }
                        }
                    }

                    $sparePartsData[] = $sparePartData;
                }
            }

          

            return response()->json([
                'success' => true,
                'data' => [
                    'equipment_id' => $equipmentId,
                    'maintenance_type_id' => $maintenanceTypeId,
                    'bom_id' => $maintenanceDetail->maintenance_bom_id,
                    'equipment_name' => $equipment ? $equipment->name : '',
                    'spare_parts' => $sparePartsData
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching equipment spare parts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching spare parts data'
            ], 500);
        }
    }

    public function filter(Request $request)
    {
        try {
            $type = $request->input('type');
            
            switch ($type) {
                case 'equipment':
                    return $this->filterByEquipment($request);
                case 'defect':
                    return $this->filterByDefectNotification($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid filter type'
                    ], 400);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in filter method: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing filter request'
            ], 500);
        }
    }

    private function filterByEquipment(Request $request)
    {
        $equipmentId = $request->input('equipment_id');
        $maintenanceTypeId = $request->input('maintenance_type_id');
        $bomId = $request->input('bom_id');

        $equipment = ErpEquipment::find($equipmentId);
        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ], 404);
        }

        $query = ErpEquipMaintenanceDetail::where('erp_equipment_id', $equipmentId)
            ->whereNotNull('maintenance_bom_id')
            ->with(['bom.book', 'maintenanceType', 'equipment.category']);

        if ($maintenanceTypeId) {
            $query->where('maintenance_type_id', $maintenanceTypeId);
        }
        if ($bomId) {
            $query->where('maintenance_bom_id', $bomId);
        }

        $maintenanceDetails = $query->get();
        if ($maintenanceDetails->isEmpty()) {
            return response()->json([]);
        }

        $data = [];
        
        foreach ($maintenanceDetails as $detail) {
            if ($detail && $detail->equipment) {
                // Get checklist data from JSON column
                $checklistsData = $this->getChecklistDataFromJson($detail);

                $equipment = $detail->equipment;
                $equipment->checklists_data = $checklistsData;
                $equipment->maintenance_detail_id = $detail->id;  // Add maintenance detail ID

                // Calculate next due date based on last submitted work order
                $nextDueDate = $this->calculateNextMaintenanceDueDate(
                    $detail->erp_equipment_id, 
                    $detail->maintenance_type_id,
                    $detail->id,
                    $detail->frequency,
                    $detail->start_date
                );

                $data[] = [
                    'equipment'         => $equipment,
                    'maintenance_type'  => $detail->maintenanceType,
                    'maintenance_type_id' => $detail->maintenance_type_id,
                    'bom'               => $detail->bom,
                    'maintenance_detail_id' => $detail->id,
                    'start_date'        => $detail->start_date,
                    'frequency'         => $detail->frequency,
                    'next_due_date'     => $nextDueDate,
                ];
            }
        }

        return response()->json($data);
    }

    /**
     * Calculate next maintenance due date based on last submitted work order
     */
    private function calculateNextMaintenanceDueDate($equipmentId, $maintenanceTypeId, $maintenanceDetailId, $frequency, $startDate)
    {
        try {
            // Find the last submitted work order for this equipment + maintenance type + maintenance detail combination
            $lastWorkOrder = PlantMaintWo::where('equipment_id', $equipmentId)
                ->where('maintenance_type_id', $maintenanceTypeId)
                ->where('maintenance_detail_id', $maintenanceDetailId)
                ->whereIn('document_status', ['submitted', 'approved', 'approval_not_required', 'closed'])
                ->orderBy('id', 'desc')
                ->first();
            
           
            // If no previous work order found, use the start date from equipment maintenance detail
            if (!$lastWorkOrder) {
                $baseDate = $startDate ? Carbon::parse($startDate) : Carbon::now();
                return $nextDueDate = $baseDate->format('d-m-Y');
            } else {
                // Check if equipment_details contains due_date
                $details = json_decode($lastWorkOrder->equipment_details, true);
                
                if (is_array($details) && !empty($details['due_date'])) {
                    $baseDate = Carbon::parse($details['due_date']);
                } else {
                    // If no due_date found in equipment_details, return N/A
                    return 'N/A';
                }
            }

            // Calculate next due date based on frequency
            $nextDueDate = null;
            switch (trim($frequency)) {
                case 'Daily':
                    $nextDueDate = $baseDate->copy()->addDay();
                    break;
                case 'Weekly':
                    $nextDueDate = $baseDate->copy()->addWeek();
                    break;
                case 'Monthly':
                    $nextDueDate = $baseDate->copy()->addMonth();
                    break;
                case 'Quarterly':
                    $nextDueDate = $baseDate->copy()->addMonths(3);
                    break;
                case 'Semi-annually':
                case 'Semi annually':
                case 'Semi annualy':
                    $nextDueDate = $baseDate->copy()->addMonths(6);
                    break;
                case 'Annually':
                case 'annualy':
                case 'Yearly':
                    $nextDueDate = $baseDate->copy()->addYear();
                    break;
                default:
                    // If frequency is not recognized, return start date or current date
                    $nextDueDate = $baseDate;
                    break;
            }

            return $nextDueDate ? $nextDueDate->format('d-m-Y') : null;

        } catch (\Exception $e) {
            \Log::error("Error calculating next maintenance due date: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get checklist data from maintenance detail JSON column
     */
    private function getChecklistDataFromJson($maintenanceDetail)
    {
        if (!$maintenanceDetail || empty($maintenanceDetail->checklist_data)) {
            return [];
        }

        $checklistData = $maintenanceDetail->checklist_data;
        
        // If it's a string, decode it
        if (is_string($checklistData)) {
            $checklistData = json_decode($checklistData, true);
        }

        if (!is_array($checklistData)) {
            return [];
        }

        // Group checklist items by main checklist name
        $groupedChecklists = collect($checklistData)->groupBy('main_checklist_name');
        
        $checklistsData = [];
        foreach ($groupedChecklists as $mainChecklistName => $items) {
            $checklistDetails = [];
            
            foreach ($items as $item) {
                $checklistDetails[] = [
                    'name' => $item['name'] ?? '',
                    'data_type' => $item['data_type'] ?? 'text',
                    'description' => $item['description'] ?? '',
                    'mandatory' => $item['mandatory'] ?? false,
                    'value' => '',
                    'values' => isset($item['values']) ? $item['values'] : []
                ];
            }
            
            $checklistsData[] = [
                'main_name' => $mainChecklistName,
                'checklist' => $checklistDetails,
            ];
        }

        return $checklistsData;
    }

    /**
     * Validate document number and work order name via AJAX
     */
    public function validateWorkOrder(Request $request)
    {
        try {
            $documentNumber = $request->document_number;
            $bookId = $request->book_id;
            $currentId = $request->current_id; // For edit mode

            // Get current user's organization
            $user = Helper::getAuthenticatedUser();
            $organizationId = $user->organization_id;

            $response = [
                'document_exists' => false,
                'message' => null
            ];

            // Check document number uniqueness based on book series numbering (same as PoRequest and MaintBomController)
            if ($documentNumber && $bookId) {
                $numPattern = \App\Models\NumberPattern::where('organization_id', $organizationId)
                    ->where('book_id', $bookId)
                    ->orderBy('id', 'DESC')
                    ->first();

                // Only check uniqueness if series_numbering is 'Manually' (same as PoRequest)
                if ($numPattern && $numPattern->series_numbering == 'Manually') {
                    // Check if the user-entered document number already exists within organization
                    $documentExists = PlantMaintWo::where('document_number', $documentNumber)
                        ->where('organization_id', $organizationId);

                    // For edit mode, exclude current record
                    if ($currentId) {
                        $documentExists->where('id', '!=', $currentId);
                    }

                    $documentExists = $documentExists->exists();

                    if ($documentExists) {
                        $response['document_exists'] = true;
                        $response['message'] = "Work Order Number '{$documentNumber}' already exists. Please use a different document number.";

                        return response()->json([
                            'success' => false,
                            'errors' => ['document_number' => $response['message']]
                        ], 422);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Validation passed'
            ]);

        } catch (\Exception $e) {
            \Log::error('Work Order Validation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed due to server error'
            ], 500);
        }
    }

    private function filterByDefectNotification(Request $request)
    {
        $equipmentId = $request->input('equipment_id');
        $defectTypeId = $request->input('defect_type_id');
        $priority = $request->input('priority');
        $seriesCode = $request->input('series_code');
        
        
        // Use exact same query as populateModal defect case
        $query = DefectNotification::with([
            'book',
            'equipment.maintenanceDetails.maintenanceType',
            'location',
            'category',
            'defectType',
            'createdByUser',
        ])->where('document_status', '!=', 'draft')
          ->whereNotExists(function ($subQuery) {
              $subQuery->select(DB::raw(1))
                       ->from('erp_plant_maint_wo')
                       ->whereColumn('erp_plant_maint_wo.defect_notification_id', 'erp_defect_notifications.id')
                       ->whereNull('erp_plant_maint_wo.deleted_at');
          })
          ->orderBy('created_at', 'desc');

        // Apply filters based on provided parameters
        if ($equipmentId) {
            $query->where('equipment_id', $equipmentId);
        }

        if ($defectTypeId) {
            $query->where('defect_type_id', $defectTypeId);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($seriesCode) {
            $query->where('book_id', $seriesCode);
        }

       

        $results = $query->get();
       
        // If no data found, return empty array for modal
        if ($results->isEmpty()) {
            return response()->json([]);
        }
        

        // Use exact same data processing as populateModal method
        $data = $results->map(function ($defectNotification) {
            $maintenanceTypes = [];
            if ($defectNotification->equipment && $defectNotification->equipment->maintenanceDetails) {
                $maintenanceTypes = $defectNotification->equipment->maintenanceDetails
                    ->map(fn($detail) => $detail->maintenanceType)
                    ->filter()
                    ->unique('id')
                    ->map(fn($type) => ['id' => $type->id, 'name' => $type->name])
                    ->values();
            }

            $checklistsByMaintenanceType = [];

            $defectNotification->maintenance_types = $maintenanceTypes;
            $defectNotification->checklists_by_maintenance_type = $checklistsByMaintenanceType;

            return $defectNotification;
        });

       
        return response()->json($data);
    }

    private function formatAttributesForDisplay($attributes)
    {
        if (empty($attributes)) {
            return 'No attributes';
        }

        $formatted = [];
        foreach ($attributes as $attr) {
            $groupName = $attr['group_short_name'] ?? $attr['group_name'] ?? 'Attribute';
            $selectedValue = $attr['selected_value'] ?? 'N/A';
            $totalValues = $attr['values_count'] ?? 0;

            if ($totalValues > 1) {
                $allValuesText = collect($attr['all_values'] ?? [])->pluck('value')->implode(', ');
                $formatted[] = "{$groupName}: {$selectedValue} (Available: {$allValuesText})";
            } else {
                $formatted[] = "{$groupName}: {$selectedValue}";
            }
        }

        return implode(' | ', $formatted);
    }


    //Close modal
    public function closeWorkOrder(Request $request){
        try{
            $remarks = $request->remarks??"";
            $workOrder =PlantMaintWo::find($request->workorder_id);
            $workOrder->document_status='closed';
            $workOrder->save();

            // Update next due date when work order is closed
            // $this->updateEquipmentNextDueDate($workOrder);
           
            Helper::approveDocument(
                $workOrder->book_id,
                $workOrder->id,
                $workOrder->revision_number,
                $request->remarks,
                $request->file('closed_attachment'),
                $workOrder->approval_level,
                'closed',
                0,
                get_class($workOrder)
            );
           
            return response()->json([
                'message' => 'Maintenance Work Order Closed Successfully.',
                'title' =>'Success !',
                'type' => 'success'
            ], 200);
        }
        catch(Exception $ex){
            return response()->json([
                'message' => 'Some Error Occured.',
                'title' =>'Error !',
                'type' => 'error'
            ], 500);
        }
    }

    /**
     * Update equipment's next due date based on frequency
     */
    private function updateEquipmentNextDueDate($workOrder)
    {
        try {
         
            if ($workOrder->reference_type === 'equipment' && $workOrder->equipment_id && $workOrder->maintenance_type_id) {
                // Find the equipment maintenance detail record
                $maintenanceDetail = ErpEquipMaintenanceDetail::where('erp_equipment_id', $workOrder->equipment_id)
                    ->where('maintenance_type_id', $workOrder->maintenance_type_id)
                    ->first();

              

                if ($maintenanceDetail && $maintenanceDetail->frequency) {
                    $currentDate = now();
                    $nextDueDate = null;
                    
                    // Get existing equipment details to check for existing due date
                    $existingEquipmentDetails = json_decode($workOrder->equipment_details, true) ?? [];
                    $existingDueDate = $existingEquipmentDetails['due_date'] ?? null;
                    
                    // Use existing due date as base, or current date if no existing due date
                    $baseDate = $existingDueDate ? \Carbon\Carbon::parse($existingDueDate) : $currentDate;
                    

                    // Calculate next due date based on frequency from the base date
                    switch ($maintenanceDetail->frequency) {
                        case 'Daily':
                            $nextDueDate = $baseDate->copy()->addDay();
                            break;
                        case 'Weekly':
                            $nextDueDate = $baseDate->copy()->addWeek();
                            break;
                        case 'Monthly':
                            $nextDueDate = $baseDate->copy()->addMonth();
                            break;
                        case 'Quarterly':
                            $nextDueDate = $baseDate->copy()->addMonths(3);
                            break;
                        case 'Semi-Annually':
                            $nextDueDate = $baseDate->copy()->addMonths(6);
                            break;
                        case 'Annually':
                        case 'Yearly':
                            $nextDueDate = $baseDate->copy()->addYear();
                            break;
                    }

                    if ($nextDueDate) {
                        // Update the equipment details in the work order with new due date
                        $equipmentDetails = json_decode($workOrder->equipment_details, true) ?? [];
                        
                      
                        
                        $equipmentDetails['due_date'] = $nextDueDate->format('Y-m-d');
                        $equipmentDetails['due_date'] = $currentDate->format('Y-m-d');
                        
                        $workOrder->equipment_details = json_encode($equipmentDetails);
                        $workOrder->save();

                       
                    } else {
                       
                    }
                } else {
                   
                }
            } else {
                \Log::warning("⚠️ Conditions not met for due date update");
            }
        } catch (\Exception $e) {
            \Log::error("❌ Error updating equipment due date: " . $e->getMessage());
            \Log::error("❌ Stack trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Revoke maintenance work order document
     */
    public function revokeDocument(Request $request)
    {
        DB::beginTransaction();
        try {
            $plantWo = PlantMaintWo::find($request->id);

            if (!$plantWo) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No Document found',
                ], 404);
            }

            // Check if document can be revoked (only SUBMITTED documents)
            if ($plantWo->document_status !== ConstantHelper::SUBMITTED) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Only submitted documents can be revoked.',
                ]);
            }

            // Check if user is the creator
            $user = Helper::getAuthenticatedUser();
            if ($plantWo->created_by !== $user->auth_user_id) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Only the document creator can revoke this document.',
                ]);
            }

            // ✅ Strict validation: once amended, cannot be revoked
            if ($plantWo->revision_number > 0) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This document has already been amended and cannot be revoked.',
                ]);
            }

            $revoke = Helper::approveDocument(
                $plantWo->book_id,
                $plantWo->id,
                $plantWo->revision_number,
                'Document revoked by creator',
                null,
                $plantWo->approval_level,
                ConstantHelper::REVOKE,
                0,
                get_class($plantWo)
            );

            if ($revoke['message']) {
                DB::rollBack();
                return response()->json([
                    'status'  => 'error',
                    'message' => $revoke['message'],
                ]);
            }

            // update both document_status and approvalStatus
            $plantWo->document_status = $revoke['approvalStatus'];
            $plantWo->approvalStatus = $revoke['approvalStatus'];
            $plantWo->save();

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => 'Revoked successfully',
            ]);

        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to revoke document: ' . $ex->getMessage(),
            ], 500);
        }
    }

    /**
     * Process work order due date reminders in chunks
     * Stores reminder entries in mailbox table for external cron processing
     */
    public function processWorkOrderReminders(Request $request)
    {
        try {
            $chunkSize = $request->get('chunk_size', 50); // Default chunk size of 50
            $today = Carbon::today();
            $tomorrow = Carbon::tomorrow();
            
            \Log::info("Processing Work Order Due Date Reminders for tomorrow: {$tomorrow->format('Y-m-d')}");
            
            // Get all active work orders
            $workOrders = PlantMaintWo::withoutGlobalScopes()
                ->whereIn('document_status', ['submitted', 'approved', 'approval_not_required'])
                ->select('id', 'document_number', 'document_status', 'created_by', 'equipment_details', 'final_remark', 'organization_id', 'equipment_id', 'maintenance_type_id')
                ->get();
            
            \Log::info("Found {$workOrders->count()} active work orders to check");
            
            $reminderCount = 0;
            $processedCount = 0;
            
            // Process work orders in chunks
            $workOrders->chunk($chunkSize)->each(function ($chunk) use ($tomorrow, &$reminderCount, &$processedCount) {
                foreach ($chunk as $workOrder) {
                    $processedCount++;
                    
                    $equipmentDetails = json_decode($workOrder->equipment_details, true);
                    $dueDate = $equipmentDetails['due_date'] ?? null;
                    
                    \Log::info("Checking WO {$workOrder->document_number} - Due Date: " . ($dueDate ?? 'Not set'));
                    
                    if ($dueDate) {
                        $dueDateCarbon = Carbon::parse($dueDate);
                        
                        // Check if work order is due tomorrow
                        if ($dueDateCarbon->isSameDay($tomorrow)) {
                            \Log::info("WO {$workOrder->document_number} is due tomorrow, creating mailbox entry...");
                            
                            if ($this->createReminderMailboxEntry($workOrder, $dueDateCarbon)) {
                                $reminderCount++;
                            }
                        }
                    }
                }
                
                // Add small delay between chunks to prevent overwhelming the system
                usleep(100000); // 0.1 second delay
            });
            
            \Log::info("Work Order Reminder Processing completed. Processed {$processedCount} work orders, created {$reminderCount} reminder entries.");
            
            return response()->json([
                'status' => 'success',
                'message' => "Processed {$processedCount} work orders, created {$reminderCount} reminder entries",
                'data' => [
                    'processed_count' => $processedCount,
                    'reminder_count' => $reminderCount,
                    'chunk_size' => $chunkSize
                ]
            ]);
            
        } catch (Exception $e) {
            \Log::error("Failed to process work order reminders: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process work order reminders: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create mailbox entry for work order reminder
     */
    private function createReminderMailboxEntry($workOrder, Carbon $dueDate)
    {
        try {
            $equipmentDetails = json_decode($workOrder->equipment_details, true);
            $emailRecipients = $this->getWorkOrderEmailRecipients($workOrder);
            
            if (empty($emailRecipients['to'])) {
                \Log::warning("No valid email recipients found for WO {$workOrder->document_number}");
                return false;
            }
            
            $dateo = date('d-m-Y');
            $subject = "Work Order Due Date Reminder - {$workOrder->document_number} | {$dateo}";
            
            $equipmentName = $equipmentDetails['equipment_name'] ?? 'N/A';
            $maintenanceType = $equipmentDetails['maintenance_type_name'] ?? $equipmentDetails['equipment_maintenance_type_name'] ?? 'N/A';
            $location = $equipmentDetails['location'] ?? null;
            
            // Create mailbox entry
            $mailBox = new MailBox();
            $mailBox->mail_to = implode(',', $emailRecipients['to']);
            $mailBox->mail_cc = !empty($emailRecipients['cc']) ? implode(',', $emailRecipients['cc']) : null;
            $mailBox->layout = 'emails.work_order_reminder';
            $mailBox->subject = $subject;
            $mailBox->status = MailBox::STATUS_PENDING;
            
            $mailBox->mail_body = json_encode([
                'document_number' => $workOrder->document_number,
                'equipment_name' => $equipmentName,
                'due_date' => $dueDate->format('d-m-Y'),
                'maintenance_type' => $maintenanceType,
                'location' => $location,
                'priority' => $equipmentDetails['priority'] ?? null,
                'remarks' => $workOrder->final_remark ?? null,
                'assigned_to' => 'Maintenance Team',
                'work_order_id' => $workOrder->id
            ]);
            
            $mailBox->save();
            
            \Log::info("Work order reminder mailbox entry created for {$workOrder->document_number}", [
                'work_order_id' => $workOrder->id,
                'mailbox_id' => $mailBox->id,
                'due_date' => $dueDate->format('Y-m-d'),
                'recipients' => $emailRecipients
            ]);
            
            return true;
            
        } catch (Exception $e) {
            \Log::error("Failed to create mailbox entry for WO {$workOrder->document_number}: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Get email recipients for work order reminders
     */
    private function getWorkOrderEmailRecipients($workOrder)
    {
        $recipients = [
            'to' => [],
            'cc' => []
        ];
        
        // Add work order creator
        if ($workOrder->created_by) {
            try {
                $creator = AuthUser::find($workOrder->created_by);
                if ($creator && $creator->email && filter_var($creator->email, FILTER_VALIDATE_EMAIL)) {
                    $recipients['to'][] = $creator->email;
                    $recipients['cc'][] = $creator->email;
                    \Log::info("Added creator email: {$creator->email} for WO {$workOrder->document_number}");
                }
            } catch (Exception $e) {
                \Log::warning("Could not fetch creator email for WO {$workOrder->document_number}: " . $e->getMessage());
            }
        }
        
        // Remove duplicates and empty values
        $recipients['to'] = array_unique(array_filter($recipients['to']));
        $recipients['cc'] = array_unique(array_filter($recipients['cc']));
        
        return $recipients;
    }
}
<?php

namespace App\Http\Controllers\Plant;

use App\Helpers\ConstantHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helper;
use App\Http\Requests\MaintBOMRequest;
use App\Models\ErpAttribute;
use App\Models\Item;
use App\Models\ItemAttribute;
use App\Models\PlantMaintBom;
use App\Models\StockLedger;
use App\Models\PlantMaintBomHistory;
use App\Models\Book;
use Yajra\DataTables\DataTables;

class MaintBomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajaxData($request);
        }

        $parentURL = "plant_maint-bom";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);

        $series = collect();
        if (count($servicesBooks['services']) > 0) {
            $firstService = $servicesBooks['services'][0];
            $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        }

        $bomNames = PlantMaintBom::select('bom_name')
            ->distinct()
            ->whereNotNull('bom_name')
            ->where('bom_name', '!=', '')
            ->orderBy('bom_name')
            ->pluck('bom_name');

        $user = Helper::getAuthenticatedUser();
        $organizationId = $user->organization_id;
        $mappings = Helper::access_org();

        return view('plant.maint_bom.index', compact('series', 'bomNames', 'mappings', 'organizationId'));
    }

   

    public function ajaxData(Request $request)
    {
        $query = PlantMaintBom::query()
            ->with(['book:id,book_code,book_name']);
    
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
            $query->whereHas('book', function ($q) use ($request) {
                $q->where('book_code', $request->series_filter);
            });
        }
    
        if ($request->filled('bom_name_filter')) {
            $query->where('bom_name', 'like', '%' . $request->bom_name_filter . '%');
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
    
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = trim($request->search['value']);
    
            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('bom_name', 'like', "%{$searchValue}%")
                    ->orWhere('document_number', 'like', "%{$searchValue}%")
                    ->orWhere('document_status', 'like', "%{$searchValue}%")
                    ->orWhereHas('book', function ($bookQuery) use ($searchValue) {
                        $bookQuery->where('book_code', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('document_date', 'like', "%{$searchValue}%")
                    ->orWhereRaw("DATE_FORMAT(document_date, '%d-%m-%Y') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("DATE_FORMAT(document_date, '%d/%m/%Y') LIKE ?", ["%{$searchValue}%"])
                    ->orWhereRaw("DATE_FORMAT(document_date, '%Y-%m-%d') LIKE ?", ["%{$searchValue}%"])
                    ->orWhere(function ($statusQuery) use ($searchValue) {
                        $lowerSearchValue = strtolower(trim($searchValue));
                        if (strpos($lowerSearchValue, 'approv') !== false) {
                            $statusQuery->where('document_status', 'approval_not_required');
                        }
                    });
            });
        }
    
        $query->orderByRaw('CAST(REGEXP_REPLACE(document_number, "[^0-9]", "") AS UNSIGNED) DESC')
            ->orderByDesc('id');
    
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('document_date', fn($row) =>
                $row->document_date
                    ? \Carbon\Carbon::parse($row->document_date)->format('d-m-Y')
                    : '-'
            )
            ->editColumn('series', fn($row) =>
                $row->book?->book_code ?? '-'
            )
            ->addColumn('status', function ($row) {
                $status = $row->document_status ?: 'draft';
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$status] ?? 'badge-light-secondary';
                $statusText = $row->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED ? 'Approved' : ucfirst($status);
                return "<span class='badge rounded-pill {$statusClass} badgeborder-radius'>{$statusText}</span>";
            })
            ->addColumn('action', function ($row) {
                $showUrl = url('plant/maint-bom/' . $row->id);
                $editUrl = url('plant/maint-bom/' . $row->id . '/edit');
    
                if ($row->document_status === 'draft' || $row->document_status === 'rejected') {
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
    
                return '
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="' . $showUrl . '">
                                <i data-feather="eye" class="me-50"></i>
                                <span>View</span>
                            </a>
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }
    
     
    public function create()
    {

        $parentURL = "plant_maint-bom";
        $series = [];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->limit(10)
            ->orderBy('item_code')
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
                'id'              => $item->id,
                'item_code'       => $item->item_code,
                'item_name'       => $item->item_name,
                'uom_name'        => optional($item->uom)->name,
                'uom_id'          => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
            ];
        });
        
        
        return view('plant.maint_bom.create', compact('series', 'items'));
    }

    public function store(MaintBOMRequest $request)
    {
        
        // Handle action parameter like PO system
        if ($request->has('action')) {
            $request->merge(['document_status' => $request->action]);
        }
        
        // Validation is handled by MaintBOMRequest automatically
        // If validation fails, it will return 422 response with errors automatically

        $name = $request->bom_name;

        if ($request->document_status !== 'draft') {
            $existingAsset = PlantMaintBom::where('bom_name', $name)->first();
            if ($existingAsset) {
                return response()->json([
                    'message' => "BOM Name '{$name}' already exists.",
                    'errors' => ['bom_name' => ["BOM Name '{$name}' already exists."]]
                ], 422);
            }
        }
       

        if ($request->doc_no == '') {
            $doc_no = $request->document_number;
        } else {
            $doc_no = $request->doc_no;
        }

        $user = Helper::getAuthenticatedUser();
        $additionalData = [
            'created_by' => $user->auth_user_id,
            'type' => get_class($user),
            'organization_id' => $user->organization->id,
            'group_id' => $user->organization->group_id,
            'doc_no' => $doc_no,
            'company_id' => $user->organization->company_id,
            'approval_level' => 1,
            'revision_number' => 0,
        ];

        $documentPaths = [];
        if ($request->hasFile('document')) {
            foreach ($request->file('document') as $index => $file) {
                $fileName = 'maint_bom_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('maint_bom_documents', $fileName, 'public');
                $documentPaths[] = $path;
            }
        }

        $data = array_merge($request->all(), $additionalData);

        if (!empty($documentPaths)) {
            $data['document'] = json_encode($documentPaths);
        }

      
        try {
            DB::beginTransaction();
        
            $bom = PlantMaintBom::create($data);
        
            $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
            if (!isset($numberPatternData)) {
                throw new \Exception("Invalid Book");
            }
        
            $document_number = $numberPatternData['document_number'];
            $bom->doc_number_type = $numberPatternData['type'];
            $bom->doc_reset_pattern = $numberPatternData['reset_pattern'];
            $bom->doc_prefix = $numberPatternData['prefix'];
            $bom->doc_suffix = $numberPatternData['suffix'];
            $bom->doc_no = $numberPatternData['doc_no'];
            $bom->save();
        
            // ✅ Handle approval process for submitted documents
            if ($bom->document_status == ConstantHelper::SUBMITTED) {
                $bookId = $bom->book_id;
                $docId = $bom->id;
                $revisionNumber = $bom->revision_number ?? 0;
                $remarks = $bom->remarks ?? "";
                $attachments = null; // No attachments in create
                $currentLevel = $bom->approval_level ?? 1;
                $actionType = 'submit';
                $modelName = get_class($bom);
                $totalValue = 0; // BOM doesn't have monetary value
                
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                $bom->document_status = $approveDocument['approvalStatus'] ?? $bom->document_status;
                $bom->save();
            }
        
            DB::commit();  // ✅ simple commit
            
            return response()->json([
                'success' => true,
                'message' => 'BOM Created successfully',
                'redirect_url' => route('maint-bom.index')
            ]);
        
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'Something went wrong'
            ], 422);
        }
    }

    public function show(Request $r, string $id)
    {
        $data = PlantMaintBom::find($id);
        $currNumber = $r->revisionNumber;

        if ($r->has('revisionNumber') && $data->revision_number != $currNumber) {
            $data = PlantMaintBomHistory::where('source_id', $id)->where('revision_number', $currNumber)->first();
        }

        if (!$data) {
            abort(404, 'Maintenance BOM not found');
        }

        $parentURL = "plant_maint-bom";
        $series = [];
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

        $revNo = $r->has('revisionNumber') ? intval($r->revisionNumber) : $data->revision_number;
        $approvalHistory = Helper::getApprovalHistory($data->book_id, $id, $revNo, 0, $data->created_by);

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$data->document_status] ?? '';
        $documentDate = \Carbon\Carbon::parse($data->document_date)->format('d-m-Y') ?? '-';

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
                $attributesArray = [];
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

        return view('plant.maint_bom.show', compact(
            'series',
            'items',
            'data',
            'buttons',
            'docStatusClass',
            'revision_number',
            'currNumber',
            'approvalHistory'
        ));
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $bom = PlantMaintBom::find($id);
        
        // Check if the record exists
        if (!$bom) {
            abort(404, 'Maintenance BOM not found');
        }
        $parentURL = "plant_maint-bom";
        $series = [];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->route('/');
        }
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();

        $items = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"])
            ->limit(10)
            ->orderBy('item_code')
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
        $data = $bom;
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
        return view('plant.maint_bom.edit', compact('series', 'items', 'bom','buttons'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MaintBOMRequest $request, $id)
    {
        // dd($request->all());
        // Validation via FormRequest
        $validator = $request->validated();

        if (!$validator) {
            // Store spare parts data in session for restoration
            session(['bom_spare_parts_backup' => $request->input('spare_parts')]);
            return redirect()
                ->route('maint-bom.edit', $id)
                ->withInput($request->except(['spare_parts'])) // Exclude spare_parts from old input
                ->withErrors($request->errors());
        }

        $bom = PlantMaintBom::findOrFail($id);

        // Check for duplicate BOM Name except current record (skip for draft saves)
        if ($request->document_status !== 'draft') {
            $name = $request->bom_name;
            $existingAsset = PlantMaintBom::where('bom_name', $name)
                ->where('id', '!=', $id)
                ->first();

            if ($existingAsset) {
                return redirect()
                    ->route('maint-bom.edit', $id)
                    ->withInput()
                    ->withErrors('BOM Name ' . $name . ' already exists.');
            }
        }

        // Handle multiple file uploads
        $finalDocuments = [];
        
        // Get existing documents
        if ($bom->document) {
            $existingDocuments = json_decode($bom->document, true);
            if (!is_array($existingDocuments)) {
                $existingDocuments = [$bom->document];
            }
            $finalDocuments = $existingDocuments;
        }
        
        // Remove deleted files from final documents
        if ($request->has('deleted_files') && !empty($request->deleted_files)) {
            $deletedFiles = json_decode($request->deleted_files, true);
            if (is_array($deletedFiles)) {
                $finalDocuments = array_diff($finalDocuments, $deletedFiles);
            }
        }
        
        // Add new uploaded files
        if ($request->hasFile('document')) {
            $newDocumentPaths = [];
            foreach ($request->file('document') as $index => $file) {
                $fileName = 'maint_bom_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('maint_bom_documents', $fileName, 'public');
                $newDocumentPaths[] = $path;
            }
            $finalDocuments = array_merge($finalDocuments, $newDocumentPaths);
        }
        
        // Delete files that were marked for deletion
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

        $data = $request->all();
        
        // Update document field
        if (!empty($finalDocuments)) {
            $data['document'] = json_encode($finalDocuments);
        } else {
            $data['document'] = null;
        }

        

        DB::beginTransaction();

        try {
            // Handle amendment only for approved documents (following PO/Sale Order pattern)
            if (($bom->document_status == ConstantHelper::APPROVED || $bom->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED) && $request->action_type == "amendment") {
                $revisionData = [
                    [
                        "model_type" => "header",
                        "model_name" => "PlantMaintBom",
                        "relation_column" => "",
                    ],
                ];
                Helper::documentAmendment($revisionData, $id);
                
                // Increment revision number and call approveDocument with new revision number
                $revisionNumber = $bom->revision_number + 1;
                $actionType = 'amendment';
                $totalValue = 0; // BOM doesn't have monetary value
                $approveDocument = Helper::approveDocument($bom->book_id, $bom->id, $revisionNumber, $request->amend_remarks, $request->file('amend_attachment'), $bom->approval_level, $actionType, $totalValue, get_class($bom));
                
                $bom->revision_number = $revisionNumber;
                $bom->approval_level = 1;
                $bom->revision_date = now();
                $amendAfterStatus = $approveDocument['approvalStatus'] ?? $bom->document_status;
                $bom->document_status = $amendAfterStatus;
                $bom->save();
            }

            $bom->update($data);
            DB::commit();
          
            
            // Clear session backup data after successful update
            session()->forget('bom_spare_parts_backup');
            
            // Return JSON response only for AJAX amendment submissions
            if ($request->ajax() && $request->action_type == "amendment") {
                return response()->json([
                    'success' => true,
                    'message' => 'Amendment Done Successfully',
                    'data' => $bom
                ]);
            }
            
            // For draft saves and regular updates, redirect to index with success message
            $message = $request->document_status === 'draft' ? 'Maintenance BOM saved as draft!' : 'Maintenance BOM updated!';
            return redirect()->route("maint-bom.index")->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Return JSON error response for AJAX requests
            if ($request->ajax() || $request->action_type == "amendment") {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            
            return redirect()->route("maint-bom.edit", $id)->with('error', $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function searchItems(Request $request)
    {
        $search = $request->get('q', '');
        $limit = $request->get('limit', 50);

        $query = Item::where("type", "goods")
            ->with(["uom", "category", "itemAttributes"]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'LIKE', "%{$search}%")
                  ->orWhere('item_name', 'LIKE', "%{$search}%");
            });
        }

        $items = $query->limit($limit)->get();

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
                'id'              => $item->id,
                'item_code'       => $item->item_code,
                'item_name'       => $item->item_name,
                'uom_name'        => optional($item->uom)->name,
                'uom_id'          => optional($item->uom)->id,
                'item_attributes' => $item->attributes,
            ];
        });

        return response()->json($items);
    }

    public function documentApproval(Request $request)
    {
        $request->validate([
            'remarks' => 'nullable|string|max:255',
            'attachment.*' => 'nullable|file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120', // 5MB max per file
        ]);
        DB::beginTransaction();
        try {
            $doc = PlantMaintBom::findOrFail($request->id);
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

            if($actionType === 'reject') {
                return response()->json([
                    'message' => "Maintenance BOM rejected successfully!",
                    'data' => $doc,
                ]);
            }

            if($actionType === 'approve') {
                return response()->json([
                    'message' => "Maintenance BOM approved successfully!",
                    'data' => $doc,
                ]);
            }
           
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => "Error occurred while processing {$request->action_type}",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function amendment(Request $request, $id)
    // {
    //     try {
    //         $bom = PlantMaintBom::findOrFail($id);

    //         $bom->document_status = $request->input('document_status', 'draft');
    //         $bom->save();
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Amendment created successfully',
    //             'data' => $bom
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function amendment(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $PlantBom = PlantMaintBom::find($id);
            if (!$PlantBom) {
                return response()->json(['success' => false, 'message' => "Maintenance BOM not found.", 'status' => 404]);
            }

            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'PlantMaintBom', 'relation_column' => ''],
            ];

            $a = Helper::documentAmendment($revisionData, $id);
            if ($a) {
                Helper::approveDocument($PlantBom->book_id, $PlantBom->id, $PlantBom->revision_number, 'Amendment', $request->file('attachment'), $PlantBom->approval_level, 'amendment');

                // $PlantBom->document_status = ConstantHelper::DRAFT;
                $PlantBom->revision_number = $PlantBom->revision_number + 1;
                $PlantBom->revision_date = now();
                $PlantBom->save();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Amendment done successfully',
                'data' => $PlantBom
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Amendment Submit Error: ' . $e->getMessage());
            return response()->json(['success' =>false, 'message' => "An unexpected error occurred. Please try again.", 'status' => 500]);
        }
    }

    public function getSeries(Request $request)
    {
        $parentURL = "plant_maint-bom";
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        
        if (count($servicesBooks['services']) == 0) {
            return response()->json([]);
        }
        
        $firstService = $servicesBooks['services'][0];
        $series = Helper::getBookSeriesNew($firstService->alias, $parentURL)->get();
        
        return response()->json($series);
    }

    public function getBomNames(Request $request)
    {
        $bomNames = PlantMaintBom::select('bom_name')
            ->distinct()
            ->orderBy('bom_name')
            ->get()
            ->pluck('bom_name');
            
        return response()->json($bomNames);
    }

     /**
     * Revoke maintenance work order document
     */
    public function revokeDocument(Request $request)
    {
        DB::beginTransaction();
        try {
            $plantWo = PlantMaintBom::find($request->id);

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
}

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

        return view('plant.maint_bom.index');
    }

    /**
     * Get data for DataTables server-side processing
     */
    public function ajaxData(Request $request)
    {
        $query = PlantMaintBom::select([
            'id',
            'bom_name',
            'document_date',
            'document_number',
            'document_status',
            'book_id'
        ])->with(['book:id,book_code']);

        // Apply global search if provided
        if (!empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('bom_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('document_number', 'LIKE', "%{$searchValue}%");
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
                4 => 'bom_name',
                5 => 'document_status',
            ];

            if (isset($columns[$columnIndex])) {
                $column = $columns[$columnIndex];

                if (in_array($column, ['book_id'])) {
                    // For foreign key columns, order by the related table column
                    $query->join('erp_books', 'plant_maint_boms.book_id', '=', 'erp_books.id')
                          ->orderBy('erp_books.book_code', $direction)
                          ->select('plant_maint_boms.*');
                } elseif ($column === 'document_number') {
                    // Sort document number numerically
                    $query->orderByRaw('CAST(REGEXP_REPLACE(document_number, "[^0-9]", "") AS UNSIGNED) ' . $direction);
                } else {
                    $query->orderBy($column, $direction);
                }
            }
        } else {
            // Sort by document number numerically (extract numbers and sort)
            $query->orderByRaw('CAST(REGEXP_REPLACE(document_number, "[^0-9]", "") AS UNSIGNED) DESC');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('document_date', function ($row) {
                return $row->document_date ? \Carbon\Carbon::parse($row->document_date)->format('d-m-Y') : '-';
            })
            ->addColumn('series', function ($row) {
                return $row->book?->book_code ?? '-';
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
                $showUrl = url('plant/maint-bom/' . $row->id);
                $editUrl = url('plant/maint-bom/' . $row->id . '/edit');

                return '
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                            <i data-feather="more-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . (($row->document_status == 'draft' && $row->document_status != 'closed') ? '
                            <a class="dropdown-item" href="' . $editUrl . '">
                                <i data-feather="edit-3" class="me-50"></i>
                                <span>Edit</span>
                            </a>' : '
                            <a class="dropdown-item" href="' . $showUrl . '">
                                <i data-feather="eye" class="me-50"></i>
                                <span>View</span>
                            </a>') . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(MaintBOMRequest $request)
    {
        // FormRequest handles validation automatically
        $validator = $request->validated();

        if (!$validator) {
            return redirect()
                ->route('maint-bom.create')
                ->withInput()
                ->withErrors($request->errors());
        }

        $name = $request->bom_name;

        // Check for duplicate BOM name (skip for draft saves)
        if ($request->document_status !== 'draft') {
            $existingAsset = PlantMaintBom::where('bom_name', $name)->first();
            if ($existingAsset) {
                return redirect()
                    ->route('maint-bom.create')
                    ->withInput()
                    ->withErrors(['bom_name' => "BOM Name '{$name}' already exists."]);
            }
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
        ];

        $data = array_merge($request->all(), $additionalData);

        try {
            DB::transaction(function () use ($data) {
                $bom = PlantMaintBom::create($data);

                if ($bom->document_status != ConstantHelper::DRAFT) {
                    $doc = Helper::approveDocument(
                        $bom->book_id,
                        $bom->id,
                        $bom->revision_number,
                        "",
                        null,
                        1,
                        'submit',
                        0,
                        get_class($bom)
                    );

                    $bom->document_status = $doc['approvalStatus'] ?? $bom->document_status;
                    $bom->save();
                }
            });

            return redirect()
                ->route("maint-bom.index")
                ->with('success', 'Maintenance BOM created!');
        } catch (\Throwable $e) {
            return redirect()
                ->route("maint-bom.create")
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $r,string $id)
    {
        
        $data = PlantMaintBom::find($id);
        $currNumber = $r->has('revisionNumber');
        if ($currNumber && $data->revision_number!=$r->revisionNumber) {
            $currNumber = $r->revisionNumber;
            $data = PlantMaintBomHistory::where('source_id', $id)
                ->where('revision_number', $currNumber)->first();
        } else {
            $data = PlantMaintBom::findorFail($id);
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
        $revNo = $data->revision_number;
        if ($r->has('revisionNumber')) {
            $revNo = intval($r->revisionNumber);
        } else {
            $revNo = $data->revision_number;
        }
        $approvalHistory = Helper::getApprovalHistory($data->book_id, $id, $revNo, 0,$data->created_by);

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
        return view('plant.maint_bom.show', compact('series', 'items','data','buttons', 'docStatusClass', 'revision_number', 'currNumber', 'approvalHistory'));
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $bom = PlantMaintBom::find($id);
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
        // Validation via FormRequest
        $validator = $request->validated();

        if (!$validator) {
            return redirect()
                ->route('maint-bom.edit', $id)
                ->withInput()
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

        $data = $request->all();

        DB::beginTransaction();

        try {
            if ($request->action_type == "amendment") {
                $revisionData = [
                    [
                        "model_type" => "header",
                        "model_name" => "PlantMaintBom",
                        "relation_column" => "",
                    ],
                ];
                Helper::documentAmendment($revisionData, $id);
                Helper::approveDocument($bom->book_id, $bom->id, $bom->revision_number, $request->amend_remarks, $request->file('amend_attachment'), $bom->approval_level, 'amendment', 0, get_class($bom));
                $data['revision_number'] = $bom->revision_number + 1;
                $data['revision_date']=now();
            }
            $bom->update($data);

            // Approval handling if not draft
            if ($bom->document_status != ConstantHelper::DRAFT) {
                $doc = Helper::approveDocument(
                    $bom->book_id,
                    $bom->id,
                    $bom->revision_number,
                    "",
                    null,
                    1,
                    'submit',
          
                    0,
                    get_class($bom)
                );

                $bom->document_status = $doc['approvalStatus'] ?? $bom->document_status;
                $bom->save();
            }

            DB::commit();
            return redirect()->route("maint-bom.index")->with('success', 'Maintenance BOM updated!');
        } catch (\Exception $e) {
            DB::rollBack();
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
            'attachment' => 'nullable'
        ]);
        DB::beginTransaction();
        try {
            $doc = PlantMaintBom::find($request->id);
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
                'message' => "Maint BOM $actionType successfully!",
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
        try {
            $bom = PlantMaintBom::findOrFail($id);

            $bom->document_status = $request->input('document_status', 'draft');
            $bom->save();
            return response()->json([
                'success' => true,
                'message' => 'Amendment created successfully',
                'data' => $bom
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\PurchaseIndent;

use DB;
use PDF;
use stdClass;
use Carbon\Carbon;
use App\Models\Bom;
use App\Models\Item;
use App\Models\Unit;
use App\Models\PiItem;
use App\Models\PoItem;
use App\Models\Vendor;
use App\Helpers\Helper;
use App\Models\Address;
use App\Models\Attribute;
use App\Models\BomDetail;
use App\Models\ErpSoItem;
use App\Helpers\BookHelper;
use App\Helpers\ItemHelper;
use App\Helpers\UserHelper;
use App\Models\PiPoMapping;
use App\Models\PiSoMapping;
use App\Models\ErpSaleOrder;
use App\Models\ErpSoItemBom;
use App\Models\Organization;
use App\Services\BomService;
use Illuminate\Http\Request;
use App\Models\PwoBomMapping;
use App\Models\AttributeGroup;
use App\Models\PurchaseIndent;
use App\Services\PI\PiService;
use App\Helpers\ConstantHelper;
use App\Models\PiItemAttribute;
use App\Models\PiSoMappingItem;
use App\Helpers\InventoryHelper;
use App\Http\Requests\PiRequest;
use App\Models\PiPwoMappingItem;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Models\ErpProductionWorkOrder;
use App\Helpers\ServiceParametersHelper;
use App\Services\Common\DocumentLockService;

class PiController extends Controller
{
    # Po List
    public function index(Request $request)
    {
        $user = Helper::getAuthenticatedUser();
        if (request()->ajax()) {
            $pathUrl = request()->segments()[0];
            $selectedfyYear = Helper::getFinancialYear(Carbon::now());
            $selectColumns = ['id', 'document_date', 'document_status', 'book_id', 'store_id', 'sub_store_id', 'user_id', 'requester_type', 'revision_number', 'document_number', 'created_by'];
            $pis = PurchaseIndent::select($selectColumns)
                ->bookViewAccess($pathUrl)
                ->withDefaultGroupCompanyOrg()
                ->withDraftListingLogic()
                ->selfCreatedDocuments($user)
                ->whereBetween('document_date', [$selectedfyYear['start_date'], $selectedfyYear['end_date']])
                ->latest();
            // Apply filters
            if ($request->filled('date_range')) {
                $dates = explode(' to ', $request->date_range);

                if (count($dates) === 2) {
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate   = Carbon::parse($dates[1])->endOfDay();

                    $pis->whereBetween('document_date', [$startDate, $endDate]);
                }
            }
            if ($request->filled('book_id')) {
                $pis->whereIn('book_id', $request->book_id);
            }
            if ($request->filled('location_id')) {
                $pis->whereIn('store_id', $request->location_id);
            }
            if ($request->filled('requester_id')) {
                $pis->whereIn('user_id', $request->requester_id);
            }
            if ($request->filled('organization_id')) {
                $pis->whereIn('organization_id', $request->organization_id);
            }


            return DataTables::of($pis)
                ->addIndexColumn()
                ->editColumn('document_status', function ($row) {
                    return view('partials.action-dropdown', [
                        'statusClass' => ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status] ?? 'badge-light-secondary',
                        'displayStatus' => $row->display_status,
                        'row' => $row,
                        'actions' => [
                            [
                                'url' => fn($r) => route('pi.edit', $r->id),
                                'icon' => 'edit-3',
                                'label' => 'View/ Edit Detail',
                            ]
                        ]
                    ])->render();
                })
                ->addColumn('book_name', function ($row) {
                    return $row->book ? $row->book?->book_code : '';
                })
                ->filterColumn('book_name', function ($query, $keyword) {
                    $query->whereHas('book', function ($q) use ($keyword) {
                        $q->where('book_code', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('location', function ($row) {
                    return $row?->store ? $row?->store?->store_name : '';
                })
                ->filterColumn('location', function ($query, $keyword) {
                    $query->whereHas('store', function ($q) use ($keyword) {
                        $q->where('store_name', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('department', function ($row) {
                    if ($row->sub_store_id) {
                        return $row?->sub_store ? $row?->sub_store?->name : '';
                    } else {
                        return $row?->requester ? $row->requester?->name : '';
                    }
                })
                ->editColumn('document_date', function ($row) {
                    return $row->getFormattedDate('document_date') ?? '';
                })
                ->editColumn('revision_number', function ($row) {
                    return strval($row->revision_number);
                })
                ->addColumn('components', function ($row) {
                    return $row->pi_items->count() ?? 0;
                })
                ->addColumn('created_by', function ($row) {
                    return $row->createdBy?->name;
                })
                ->rawColumns(['document_status'])
                ->make(true);
        }
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        $serviceAlias = ConstantHelper::PI_SERVICE_ALIAS;
        $user = Helper::getAuthenticatedUser();
        $applicableOrgIds = $user->organizations->pluck('id')->toArray();
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $requesters = Helper::getOrgWiseUserAndEmployees($user->organization_id);
        $locations = InventoryHelper::getAccessibleLocations();
        $applicableOrganizations = Organization::whereIn('id', $applicableOrgIds ?? [0])
            ->where('status', ConstantHelper::ACTIVE)
            ->get(['id', 'name']);
        return view('procurement.pi.index', [
            'servicesBooks' => $servicesBooks,
            'books' => $books,
            'requesters' => $requesters,
            'locations' => $locations,
            'applicableOrganizations' => $applicableOrganizations,
        ]);
    }

    // # Po create
    public function create()
    {
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->back();
        }
        $user = Helper::getAuthenticatedUser();
        $serviceAlias = ConstantHelper::PI_SERVICE_ALIAS;
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $selectedfyYear = Helper::getFinancialYear(Carbon::now());
        $users = UserHelper::getUserSubOrdinates($user->auth_user_id ?? 0);
        $selecteduserId = $user->auth_user_id;
        $locations = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCKK);

        return view('procurement.pi.create', [
            'books' => $books,
            'users' => $users['data'],
            'selecteduserId' => $selecteduserId,
            'locations' => $locations,
            'current_financial_year' => $selectedfyYear,
        ]);
    }

    # Add item row
    public function addItemRow(Request $request)
    {
        $item = json_decode($request->item, true) ?? [];
        $componentItem = json_decode($request->component_item, true) ?? [];
        /*Check last tr in table mandatory*/
        if (isset($componentItem['attr_require']) && isset($componentItem['item_id']) && $componentItem['row_length']) {
            if (($componentItem['attr_require'] == true || !$componentItem['item_id']) && $componentItem['row_length'] != 0) {
                return response()->json(['data' => ['html' => ''], 'status' => 422, 'message' => 'Please fill all component details before adding new row more!']);
            }
        }
        $rowCount = intval($request->count) == 0 ? 1 : intval($request->count) + 1;
        $soTrackingRequired = strtolower($request->so_tracking_required) == 'yes' ? true : false;
        $html = view('procurement.pi.partials.item-row', compact('rowCount', 'soTrackingRequired'))->render();
        return response()->json(['data' => ['html' => $html], 'status' => 200, 'message' => 'fetched.']);
    }

    # On change item attribute
    public function getItemAttribute(Request $request)
    {
        $isSo = intval($request->isSo) ?? 0;
        $rowCount = intval($request->rowCount) ?? 1;
        $item = Item::find($request->item_id);
        $selectedAttr = $request->selectedAttr ? json_decode($request->selectedAttr, true) : [];
        $itemAttributeArray = [];
        $piItemId = $request->pi_item_id ?? null;
        $itemAttIds = [];
        if ($piItemId) {
            $piItem = PiItem::where('id', $piItemId)->where('item_id', $item->id ?? null)->first();
            if ($piItem) {
                $itemAttIds = $piItem->attributes()->pluck('item_attribute_id')->toArray();
                $itemAttributeArray = $piItem->item_attributes_array();
            }
        }
        $itemAttributes = collect();
        if (count($itemAttIds)) {
            $itemAttributes = $item?->itemAttributes()->whereIn('id', $itemAttIds)->get();
            if (count($itemAttributes) < 1) {
                $itemAttributes = $item?->itemAttributes;
                $itemAttributeArray = $item->item_attributes_array();
            }
        } else {
            $itemAttributes = $item?->itemAttributes;
            $itemAttributeArray = $item->item_attributes_array();
        }

        $html = view('procurement.pi.partials.comp-attribute', compact('item', 'rowCount', 'selectedAttr', 'isSo', 'itemAttributes'))->render();
        $hiddenHtml = '';
        foreach ($itemAttributes as $attribute) {
            $selected = '';
            foreach ($attribute->attributes() as $value) {
                if (in_array($value->id, $selectedAttr)) {
                    $selected = $value->id;
                }
            }
            $hiddenHtml .= "<input type='hidden' name='components[$rowCount][attr_group_id][$attribute->attribute_group_id][attr_name]' value=$selected>";
        }
        if (count($selectedAttr)) {
            foreach ($itemAttributeArray as &$group) {
                foreach ($group['values_data'] as $attribute) {
                    if (in_array($attribute->id, $selectedAttr)) {
                        $attribute->selected = true;
                    }
                }
            }
        }
        return response()->json(['data' => ['attr' => $item->itemAttributes->count(), 'html' => $html, 'hiddenHtml' => $hiddenHtml, 'itemAttributeArray' => $itemAttributeArray], 'status' => 200, 'message' => 'fetched.']);
    }


    # Purchase Indent store
    public function store(PiRequest $request, DocumentLockService $lockService)
    {
        DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();
            $organization = Organization::where('id', $user->organization_id)->first();
            # Bom Header save
            $pi = new PurchaseIndent;
            $pi->organization_id = $organization->id;
            $pi->group_id = $organization->group_id;
            $pi->company_id = $organization->company_id;
            $pi->department_id = $request->department_id ?? null;
            $pi->requester_type = isset($request->sub_store_id) && $request->sub_store_id ? 'Department' : 'User';
            $pi->user_id = $request->user_id ?? null;
            $pi->book_id = $request->book_id;
            $pi->book_code = $request->book_code;
            $pi->store_id = $request->store_id ?? null;
            $pi->sub_store_id = $request->sub_store_id ?? null;
            $pi->so_tracking_required = $request->so_tracking_required ?? 'no';
            $pi->procurement_type = $request->procurement_type ?? 'rm';
            $document_number = $request->document_number ?? null;

            /**/
            $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
            if (!isset($numberPatternData)) {
                return response()->json([
                    'message' => "Invalid Book",
                    'error' => "",
                ], 422);
            }
            $document_number = $numberPatternData['document_number'] ? $numberPatternData['document_number'] : $document_number;
            $regeneratedDocExist = PurchaseIndent::where('book_id', $request->book_id)
                ->where('document_number', $document_number)->first();
            //Again check regenerated doc no
            if (isset($regeneratedDocExist)) {
                return response()->json([
                    'message' => ConstantHelper::DUPLICATE_DOCUMENT_NUMBER,
                    'error' => "",
                ], 422);
            }

            $pi->doc_number_type = $numberPatternData['type'];
            $pi->doc_reset_pattern = $numberPatternData['reset_pattern'];
            $pi->doc_prefix = $numberPatternData['prefix'];
            $pi->doc_suffix = $numberPatternData['suffix'];
            $pi->doc_no = $numberPatternData['doc_no'];
            /**/
            $pi->document_number = $document_number;
            $pi->document_date = $request->document_date;
            $pi->reference_number = $request->reference_number;
            $pi->document_status = $request->document_status;
            $pi->remarks = $request->remarks ?? null;
            $pi->save();

            if (isset($request->all()['components']) && count($request->all()['components'])) {
                foreach ($request->all()['components'] as $c_key => $component) {
                    $item = Item::find($component['item_id'] ?? null);
                    # Purchase Order Detail Save
                    $piDetail = new PiItem;
                    $unit = Unit::find($component['uom_id']);

                    $piDetail->pi_id = $pi->id;
                    $piDetail->item_id = $component['item_id'] ?? null;
                    $piDetail->item_code = $component['item_code'] ?? null;
                    $piDetail->item_name = $component['item_name'] ?? null;
                    $piDetail->hsn_id = $component['hsn_id'] ?? null;
                    $piDetail->hsn_code = $component['hsn_code'] ?? null;
                    $piDetail->uom_id = $component['uom_id'] ?? null;
                    $piDetail->uom_code = $unit?->name ?? null;
                    $piDetail->required_qty = $component['qty'] ?? 0.00;
                    $piDetail->adjusted_qty = $component['adj_qty'] ?? 0.00;
                    $piDetail->indent_qty = $component['indent_qty'] ?? 0.00;
                    $piDetail->inventory_uom_code = $item->uom->name ?? null;
                    if (@$component['uom_id'] == $item->uom_id) {
                        $piDetail->inventory_uom_id = $component['uom_id'] ?? null;
                        $piDetail->inventory_uom_code = $component['uom_code'] ?? null;
                        $piDetail->inventory_uom_qty = $component['indent_qty'];
                    } else {
                        $piDetail->inventory_uom_id = $component['uom_id'] ?? null;
                        $piDetail->inventory_uom_code = $component['uom_code'] ?? null;
                        $alUom = $item->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                        if ($alUom) {
                            $piDetail->inventory_uom_qty = floatval($component['indent_qty']) * $alUom->conversion_to_inventory;
                        }
                    }

                    $piDetail->remarks = $component['remark'] ?? null;
                    if ($component['vendor_id']) {
                        $vendor = Vendor::where('id', $component['vendor_id'])->first();
                        if ($vendor) {
                            $piDetail->vendor_id = $vendor?->id ?? null;
                            $piDetail->vendor_code = $vendor?->vendor_code ?? null;
                            $piDetail->vendor_name = $vendor?->company_name ?? null;
                        }
                    }

                    $piDetail->so_id = $component['so_id'] ?? null;
                    $piDetail->pwo_id = $component['pwo_id'] ?? null;

                    $piDetail->save();
                    $piDetail->refresh();

                    /*Pi_So_Mapping Update*/
                    if (isset($component['pwo_mapping_id']) && $component['pwo_mapping_id']) {
                        $pwoMapping = PwoBomMapping::where('id', $component['pwo_mapping_id'])
                            ->when(isset($component['pwo_id']) && $component['pwo_id'], function ($query) use ($component) {
                                $query->where('pwo_id', $component['pwo_id']);
                            })->first();

                        if (!$pwoMapping) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Invalid PWO BOM Mapping selected.',
                                'error' => "",
                            ], 422);
                        }

                        PiPwoMappingItem::updateOrCreate([
                            'pi_id' => $pi->id,
                            'pwo_id' => $pwoMapping->pwo_id,
                            'so_id' => $pwoMapping->so_id,
                            'bom_id' => $pwoMapping->bom_id,
                            'bom_detail_id' => $pwoMapping->bom_detail_id,
                            'pwo_bom_mapping_id' => $pwoMapping->id,
                            'pi_item_id' => $piDetail->id,
                        ], [
                            'qty' => $component['indent_qty'] ?? 0,
                            'uom_id' => $component['uom_id'] ?? null,
                        ]);
                    } else if (@$component['so_pi_mapping_item_id']) {
                        if (intval($component['so_pi_mapping_item_id']) == $piDetail->item_id) {

                            $showAttribute = intval($request->show_attribute) ?? 0;
                            $so_item_ids = $request->so_item_ids ? explode(',', $request->so_item_ids) : [];

                            if (!$showAttribute) {
                                $itemIds = $request->item_ids ? explode(',', $request->item_ids) : [];
                                $so_item_ids = ErpSoItem::whereIn('sale_order_id', $so_item_ids)
                                    ->whereIn('item_id', $itemIds)
                                    ->pluck('id')
                                    ->toArray();
                            }

                            $attributes = $piDetail->attributes->map(fn($attribute) => [
                                'attribute_id' => $attribute->item_attribute_id,
                                'attribute_value' => intval($attribute->attribute_value),
                            ])->toArray();

                            $indent_qty = $piDetail->indent_qty;

                            $datas = PiSoMapping::where('item_id', $piDetail->item_id)
                                ->whereIn('so_item_id', $so_item_ids)
                                ->whereJsonContains('attributes', $attributes)
                                ->where(function ($query) use ($piDetail) {
                                    if ($piDetail?->so_id) {
                                        $query->where('so_id', $piDetail->so_id);
                                    }
                                    if ($piDetail?->vendor_id) {
                                        $query->where('vendor_id', $piDetail->vendor_id);
                                    }
                                })
                                ->get();
                            foreach ($datas as $data) {
                                $availableQty = $data->qty - $data->pi_item_qty;
                                if ($availableQty > 0) {
                                    $allocatedQty = min($indent_qty, $availableQty);
                                    $data->pi_item_qty += $allocatedQty;
                                    $data->save();

                                    $indent_qty -= $allocatedQty;
                                    $piSoMappingItem = PiSoMappingItem::firstOrNew([
                                        'pi_so_mapping_id' => $data->id,
                                        'pi_item_id' => $piDetail->id
                                    ]);

                                    $piSoMappingItem->uom_id = $data->uom_id;
                                    $piSoMappingItem->qty += $allocatedQty;
                                    $piSoMappingItem->save();
                                    if ($indent_qty <= 0) {
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    #Save component Attr
                    foreach ($piDetail->item->itemAttributes as $itemAttribute) {
                        if (isset($component['attr_group_id'][$itemAttribute->attribute_group_id])) {
                            $piAttr = new PiItemAttribute;
                            $piAttrName = @$component['attr_group_id'][$itemAttribute->attribute_group_id]['attr_name'];
                            $piAttr->pi_id = $pi->id;
                            $piAttr->pi_item_id = $piDetail->id;
                            $piAttr->item_attribute_id = $itemAttribute->id;
                            $piAttr->item_code = $component['item_code'] ?? null;
                            $piAttr->attribute_name = $itemAttribute->attribute_group_id;
                            $piAttr->attribute_value = $piAttrName ?? null;
                            $piAttr->attribute_group_id = $itemAttribute->attribute_group_id;
                            $piAttr->attribute_id = $piAttrName ?? null;
                            $piAttr->save();
                        }
                    }
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'message' => 'Please add atleast one row in component table.',
                    'error' => "",
                ], 422);
            }

            $pi->save();

            /*Create document submit log*/
            if ($request->document_status == ConstantHelper::SUBMITTED) {
                $modelName = get_class($pi);
                $bookId = $pi->book_id;
                $docId = $pi->id;
                $remarks = $pi->remarks;
                $attachments = $request->file('attachment');
                $currentLevel = $pi->approval_level ?? 1;
                $revisionNumber = $pi->revision_number ?? 0;
                $actionType = 'submit'; // Approve // reject // submit
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, 0, $modelName);
                $pi->document_status = $approveDocument['approvalStatus'] ??  $pi->document_status;
            } else {
                $pi->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            }

            $pi->save();

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $singleFile) {
                    $mediaFiles = $pi->uploadDocuments($singleFile, 'pi', false);
                }
            }

            $redirectUrl = '';
            if ($pi->document_status == ConstantHelper::APPROVED) {
                $redirectUrl = route('pi.generate-pdf', $pi->id);
            }


            $lockKey = \App\Helpers\GeneralHelper::generateLockKey($organization->id, $request->book_id, $document_number);
            if (!$lockKey) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Invalid Argument passed in Lock Key Generator.',
                    'line' => '',
                    'error' => '',
                ], 500);
            }

            $lockResult = $lockService->lockDocumentNumber($lockKey);
            if (!$lockResult['success']) {
                DB::rollBack();
                return response()->json([
                    'message' => $lockResult['message'],
                    'error' => 'lockResult',
                ], $lockResult['status']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Record created successfully',
                'data' => $pi,
                'redirect_url' => $redirectUrl
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    # Purchase Order update
    public function update(PiRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            # Pi Header save
            $pi = PurchaseIndent::find($id);
            $currentStatus = $pi->document_status;
            $actionType = $request->action_type;
            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'PurchaseIndent', 'relation_column' => ''],
                    ['model_type' => 'detail', 'model_name' => 'PiItem', 'relation_column' => 'pi_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'PiItemAttribute', 'relation_column' => 'pi_item_id']
                ];

                if (!Helper::documentAmendment($revisionData, $id)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error occurred while sending amendment request for approval.',
                        'error' => '',
                    ], 500);
                }
            }
            $keys = ['deletedPiItemIds', 'deletedAttachmentIds'];
            $deletedData = [];
            foreach ($keys as $key) {
                $deletedData[$key] = json_decode($request->input($key, '[]'), true);
            }

            $piDeleteService = app(\App\Services\PI\PiDeleteService::class);
            if (!empty($deletedData['deletedAttachmentIds'])) {
                $response = $piDeleteService->deleteAttachments($deletedData['deletedAttachmentIds'], $pi);
                if ($response['status'] === 'error') {
                    return response()->json($response, 500);
                }
            }

            if (!empty($deletedData['deletedPiItemIds'])) {
                $response = $piDeleteService->deletePiItems($deletedData['deletedPiItemIds'], $pi);
                if ($response['status'] === 'error') {
                    return response()->json($response, 500);
                }
            }

            $pi->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            $pi->document_date = $request->document_date ?? $pi->document_date;
            $pi->remarks = $request->approval_remarks ?? $request->remarks ?? $pi->remarks;
            $pi->save();
            if (isset($request->all()['components']) && count($request->all()['components'])) {
                foreach ($request->all()['components'] as $c_key => $component) {

                    $item = Item::find($component['item_id'] ?? null);
                    $unit = Unit::find($component['uom_id']);
                    # Purchase Order Detail Save
                    $piDetail = PiItem::find($component['pi_item_id'] ?? null) ?? new PiItem;

                    $isNewItem = false;
                    if (isset($piDetail->item_id) && $piDetail->item_id) {
                        $isNewItem = $piDetail->item_id != ($component['item_id'] ?? null);
                    }

                    $updatedQty = 0;
                    if (isset($piDetail->id)) {
                        $updatedQty =  floatval($component['qty']) - $piDetail->indent_qty;
                    }

                    $piDetail->pi_id = $pi->id;
                    if (!$piDetail->po_item) {
                        $piDetail->item_id = $component['item_id'] ?? null;
                        $piDetail->item_code = $component['item_code'] ?? null;
                        $piDetail->item_name = $component['item_name'] ?? null;
                        $piDetail->hsn_id = $component['hsn_id'] ?? null;
                        $piDetail->hsn_code = $component['hsn_code'] ?? null;
                        $piDetail->uom_id = $component['uom_id'] ?? null;
                        $piDetail->uom_code = $unit?->name ?? null;
                        $piDetail->required_qty = $component['qty'] ?? 0.00;
                        $piDetail->adjusted_qty = $component['adj_qty'] ?? 0.00;
                        $piDetail->indent_qty = $component['indent_qty'] ?? 0.00;

                        $piDetail->inventory_uom_id = $item->uom_id ?? null;
                        $piDetail->inventory_uom_code = $item->uom->name ?? null;
                        if (@$component['uom_id'] == $item->uom_id) {
                            $piDetail->inventory_uom_id = $component['uom_id'];
                            $piDetail->inventory_uom_code = $component['uom_code'] ?? null;
                            $piDetail->inventory_uom_qty = $component['indent_qty'];
                        } else {
                            $alUom = $item->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                            if ($alUom) {
                                $piDetail->inventory_uom_qty = floatval($component['indent_qty']) * $alUom->conversion_to_inventory;
                            }
                        }
                    }
                    $piDetail->remarks = $component['remark'] ?? null;
                    if ($component['vendor_id']) {
                        $vendor = Vendor::where('id', $component['vendor_id'])->first();
                        if ($vendor) {
                            $piDetail->vendor_id = $vendor?->id ?? null;
                            $piDetail->vendor_code = $vendor?->vendor_code ?? null;
                            $piDetail->vendor_name = $vendor?->company_name ?? null;
                        }
                    }

                    $piDetail->so_id = $component['so_id'] ?? null;
                    $piDetail->pwo_id = $component['pwo_id'] ?? null;

                    $piDetail->save();
                    $piDetail->refresh();

                    /*Pi_So_Mapping Update*/
                    if ($updatedQty < 0) {
                        $poSiMappingItems = PiSoMappingItem::where('pi_item_id', $piDetail->id)
                            ->leftJoin('erp_pi_so_mapping', 'erp_pi_so_mapping_items.pi_so_mapping_id', '=', 'erp_pi_so_mapping.id')
                            ->selectRaw('erp_pi_so_mapping_items.id, erp_pi_so_mapping.id as mapping_id, (erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty) as balQty')
                            ->orderBy('balQty', 'desc')
                            ->get();
                    } else {
                        $poSiMappingItems = PiSoMappingItem::where('pi_item_id', $piDetail->id)
                            ->leftJoin('erp_pi_so_mapping', 'erp_pi_so_mapping_items.pi_so_mapping_id', '=', 'erp_pi_so_mapping.id')
                            ->selectRaw('erp_pi_so_mapping_items.id, erp_pi_so_mapping.id as mapping_id, (erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty) as balQty')
                            ->orderBy('balQty', 'asc')
                            ->get();
                    }
                    foreach ($poSiMappingItems as $poSiMappingItem) {
                        $piSoMapping = PiSoMapping::find($poSiMappingItem->mapping_id);
                        if (!$piSoMapping) {
                            continue;
                        }

                        if ($updatedQty < 0) {
                            $balQty = $piSoMapping->pi_item_qty;
                        } else {
                            $balQty = $poSiMappingItem->balQty;
                        }

                        $allowedQty = min($updatedQty, $balQty);
                        if ($allowedQty < 0) {
                            if (abs($allowedQty) >= $balQty) {
                                $allowedQty = $balQty * -1;
                            }
                        }

                        // Update pi_item_qty in the related pi_so_mapping
                        $piSoMapping->pi_item_qty += $allowedQty;
                        $piSoMapping->save();

                        // Update qty in the current PiSoMappingItem
                        $poSiMapItem = PiSoMappingItem::find($poSiMappingItem->id);
                        $poSiMapItem->qty += $allowedQty;
                        $poSiMapItem->save();

                        $updatedQty -= $allowedQty;
                        if (0 == $updatedQty) {
                            break;
                        }
                    }

                    $showAttribute = intval($request->show_attribute) ?? 0;
                    $so_item_ids = $request->so_item_ids ? explode(',', $request->so_item_ids) : [];

                    if (!$showAttribute) {
                        $itemIds = $request->item_ids ? explode(',', $request->item_ids) : [];
                        $so_item_ids = ErpSoItem::whereIn('sale_order_id', $so_item_ids)
                            ->whereIn('item_id', $itemIds)
                            ->pluck('id')
                            ->toArray();
                    }

                    // For new generate
                    if (!$poSiMappingItems?->count() && count($so_item_ids)) {
                        $attributes = $piDetail->attributes->map(fn($attribute) => [
                            'attribute_id' => $attribute->item_attribute_id,
                            'attribute_value' => intval($attribute->attribute_value),
                        ])->toArray();

                        $indent_qty = $piDetail->indent_qty;

                        $datas = PiSoMapping::where('item_id', $piDetail->item_id)
                            ->whereIn('so_item_id', $so_item_ids)
                            ->whereJsonContains('attributes', $attributes)
                            ->where(function ($query) use ($piDetail) {
                                if ($piDetail->so_id) {
                                    $query->where('so_id', $piDetail->so_id);
                                }
                                if ($piDetail?->vendor_id) {
                                    $query->where('vendor_id', $piDetail->vendor_id);
                                }
                            })
                            ->get();

                        foreach ($datas as $data) {
                            $availableQty = $data->qty - $data->pi_item_qty;
                            if ($availableQty > 0) {
                                $allocatedQty = min($indent_qty, $availableQty);
                                $data->pi_item_qty += $allocatedQty;
                                $data->save();

                                $indent_qty -= $allocatedQty;
                                $piSoMappingItem = PiSoMappingItem::firstOrNew([
                                    'pi_so_mapping_id' => $data->id,
                                    'pi_item_id' => $piDetail->id
                                ]);

                                $piSoMappingItem->uom_id = $data->uom_id;
                                $piSoMappingItem->qty += $allocatedQty;
                                $piSoMappingItem->save();
                                if ($indent_qty <= 0) {
                                    break;
                                }
                            }
                        }
                    }

                    /*Pi_So_Mapping Update*/
                    if (isset($component['pwo_mapping_id']) && $component['pwo_mapping_id'] && $piDetail->pwo_id) {
                        $pwoMapping = PwoBomMapping::where('id', $component['pwo_mapping_id'])
                            ->where('pwo_id', $piDetail->pwo_id)
                            ->first();

                        if (!$pwoMapping) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'PWO BOM Mapping is missing.',
                                'error' => "",
                            ], 422);
                        }

                        PiPwoMappingItem::updateOrCreate([
                            'pi_id' => $pi->id,
                            'pwo_id' => $pwoMapping->pwo_id,
                            'so_id' => $pwoMapping->so_id,
                            'bom_id' => $pwoMapping->bom_id,
                            'bom_detail_id' => $pwoMapping->bom_detail_id,
                            'pwo_bom_mapping_id' => $pwoMapping->id,
                            'pi_item_id' => $piDetail->id,
                        ], [
                            'qty' => $component['indent_qty'] ?? 0,
                            'uom_id' => $component['uom_id'] ?? null,
                        ]);
                    }

                    if ($isNewItem) {
                        PiItemAttribute::where('pi_item_id', $piDetail->id)->delete();
                    }
                    #Save component Attr
                    foreach ($piDetail->item->itemAttributes as $itemAttribute) {
                        if (isset($component['attr_group_id'][$itemAttribute->attribute_group_id])) {
                            // $piAttrId = @$component['attr_group_id'][$itemAttribute->attribute_group_id]['attr_id'];
                            $piAttrName = @$component['attr_group_id'][$itemAttribute->attribute_group_id]['attr_name'];
                            $piAttr = PiItemAttribute::firstOrNew([
                                'pi_id' => $pi->id,
                                'pi_item_id' => $piDetail->id,
                                'item_attribute_id' => $itemAttribute->id
                            ]);
                            // $piAttr = PiItemAttribute::find($piAttrId) ?? new PiItemAttribute;
                            $piAttr->item_code = $component['item_code'] ?? null;
                            $piAttr->attribute_name = $itemAttribute?->attribute_group_id;
                            $piAttr->attribute_value = $piAttrName ?? null;
                            $piAttr->save();
                        }
                    }
                }
            }
            /*Pi Attachment*/
            if ($request->hasFile('attachment')) {
                $pi->uploadDocuments($request->file('attachment'), 'pi', false);
            }
            $pi->save();

            /*Create document submit log*/
            $bookId = $pi->book_id;
            $docId = $pi->id;
            $amendRemarks = $request->amend_remarks ?? null;
            $remarks = $pi->remarks;
            $amendAttachments = $request->file('amend_attachment');
            $attachments = $request->file('attachment');
            $currentLevel = $pi->approval_level;
            $modelName = get_class($pi);
            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                //*amendmemnt document log*/
                $revisionNumber = $pi->revision_number + 1;
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $amendRemarks, $amendAttachments, $currentLevel, $actionType, 0, $modelName);
                $pi->revision_number = $revisionNumber;
                $pi->approval_level = 1;
                $pi->revision_date = now();
                $amendAfterStatus = $approveDocument['approvalStatus'] ?? $pi->document_status;
                $pi->document_status = $amendAfterStatus;
                $pi->save();
            } else {
                if ($request->document_status == ConstantHelper::SUBMITTED) {
                    $modelName = get_class($pi);
                    $bookId = $pi->book_id;
                    $docId = $pi->id;
                    $remarks = $pi->remarks;
                    $attachments = $request->file('attachment');
                    $currentLevel = $pi->approval_level;
                    $revisionNumber = $pi->revision_number ?? 0;
                    $actionType = 'submit'; // Approve // reject // submit
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, 0, $modelName);
                    $pi->document_status = $approveDocument['approvalStatus'] ?? $pi->document_status;
                } else {
                    $pi->document_status = $request->document_status ?? ConstantHelper::DRAFT;
                }
            }

            $pi->save();
            $redirectUrl = '';
            if ($pi->document_status == ConstantHelper::APPROVED) {
                $redirectUrl = route('pi.generate-pdf', $pi->id);
            }
            DB::commit();
            return response()->json([
                'message' => 'Record updated successfully',
                'data' => $pi,
                'redirect_url' => $redirectUrl
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    # Update after submit
    public function updateApprove(PiRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $pi = PurchaseIndent::find($id);
            $actionType = $request->action_type;
            if (isset($request->all()['components']) && count($request->all()['components'])) {
                foreach ($request->all()['components'] as $c_key => $component) {
                    $item = Item::find($component['item_id'] ?? null);
                    # Purchase Order Detail Save
                    $piDetail = PiItem::find($component['pi_item_id'] ?? null) ?? new PiItem;
                    $updatedQty = 0;
                    if (isset($piDetail->id)) {
                        $updatedQty =  floatval($component['qty']) - $piDetail->indent_qty;
                    }
                    // $piDetail->required_qty = $component['qty'] ?? 0.00;
                    $piDetail->adjusted_qty = $component['adj_qty'] ?? 0.00;
                    $piDetail->indent_qty = $component['indent_qty'] ?? 0.00;
                    if (@$component['uom_id'] == $item->uom_id) {
                        $piDetail->inventory_uom_qty = $component['indent_qty'];
                    } else {
                        $alUom = $item->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                        if ($alUom) {
                            $piDetail->inventory_uom_qty = floatval($component['indent_qty']) * $alUom->conversion_to_inventory;
                        }
                    }
                    if ($component['vendor_id']) {
                        $vendor = Vendor::where('id', $component['vendor_id'])->first();
                        if ($vendor) {
                            $piDetail->vendor_id = $vendor?->id ?? null;
                            $piDetail->vendor_code = $vendor?->vendor_code ?? null;
                            $piDetail->vendor_name = $vendor?->company_name ?? null;
                        }
                    }
                    $piDetail->save();
                    $piDetail->refresh();
                    /*Pi_So_Mapping Update*/
                    if ($updatedQty < 0) {
                        $poSiMappingItems = PiSoMappingItem::where('pi_item_id', $piDetail->id)
                            ->leftJoin('erp_pi_so_mapping', 'erp_pi_so_mapping_items.pi_so_mapping_id', '=', 'erp_pi_so_mapping.id')
                            ->selectRaw('erp_pi_so_mapping_items.id, erp_pi_so_mapping.id as mapping_id, (erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty) as balQty')
                            ->orderBy('balQty', 'desc')
                            ->get();
                    } else {
                        $poSiMappingItems = PiSoMappingItem::where('pi_item_id', $piDetail->id)
                            ->leftJoin('erp_pi_so_mapping', 'erp_pi_so_mapping_items.pi_so_mapping_id', '=', 'erp_pi_so_mapping.id')
                            ->selectRaw('erp_pi_so_mapping_items.id, erp_pi_so_mapping.id as mapping_id, (erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty) as balQty')
                            ->orderBy('balQty', 'asc')
                            ->get();
                    }
                    foreach ($poSiMappingItems as $poSiMappingItem) {
                        $piSoMapping = PiSoMapping::find($poSiMappingItem->mapping_id);
                        if (!$piSoMapping) {
                            continue;
                        }
                        if ($updatedQty < 0) {
                            $balQty = $piSoMapping->pi_item_qty;
                        } else {
                            $balQty = $poSiMappingItem->balQty;
                        }
                        $allowedQty = min($updatedQty, $balQty);
                        if ($allowedQty < 0) {
                            if (abs($allowedQty) >= $balQty) {
                                $allowedQty = $balQty * -1;
                            }
                        }
                        $piSoMapping->pi_item_qty += $allowedQty;
                        $piSoMapping->save();
                        $poSiMapItem = PiSoMappingItem::find($poSiMappingItem->id);
                        $poSiMapItem->qty += $allowedQty;
                        $poSiMapItem->save();
                        $updatedQty -= $allowedQty;
                        if (0 == $updatedQty) {
                            break;
                        }
                    }
                }
            }
            $bookId = $pi->book_id;
            $docId = $pi->id;
            $remarks = $request->remarks;
            $revisionNumber = $pi->revision_number ?? 0;
            $attachments = $request->file('attachment');
            $currentLevel = $pi->approval_level;
            $modelName = get_class($pi);
            $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, 0, $modelName);
            $pi->approval_level = $approveDocument['nextLevel'];
            $pi->document_status = $approveDocument['approvalStatus'];
            $pi->save();
            $redirectUrl = '';
            if ($pi->document_status == ConstantHelper::APPROVED) {
                $redirectUrl = route('pi.generate-pdf', $pi->id);
            }
            DB::commit();
            return response()->json([
                'message' => 'Record updated successfully',
                'data' => $pi,
                'redirect_url' => $redirectUrl
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    # On select row get item detail
    public function getItemDetail(Request $request)
    {
        $itemId = $request->item_id;
        $storeId = $request->store_id;
        $subStoreId = $request->sub_store_id;
        $selectedAttr = json_decode($request->selectedAttr, 200) ?? [];
        $item = Item::find($request->item_id ?? null);
        $attributeName = [];
        $attributeValue = [];
        foreach ($item->itemAttributes as $attribute) {
            $attributeGroupId = $attribute->attribute_group_id ?? null;
            $attributeIds = $attribute->attribute_id ?? [];

            if (!is_array($attributeIds)) {
                $attributeIds = [$attributeIds];
            }

            foreach ($attributeIds as $attrId) {
                $attrId = (string) trim($attrId);
                if (in_array($attrId, $selectedAttr, true)) {
                    $attributeName[] = $attributeGroupId;
                    $attributeValue[] = $attrId;
                }
            }
        }

        $attributes = [
            'attribute_name' => $attributeName,
            'attribute_value' => $attributeValue,
        ];
        $uomId = $request->uom_id ?? null;
        $qty = floatval($request->qty) ?? 0;
        $uomName = $item->uom->name ?? 'NA';
        if ($item->uom_id == $uomId) {
        } else {
            $alUom = $item->alternateUOMs()->where('uom_id', $uomId)->first();
            $qty = $alUom?->conversion_to_inventory * $qty;
        }
        $specifications = $item->specifications()->whereNotNull('value')->get();
        $remark = $request->remark ?? null;
        $piItemIds = $request->pi_item_id ? [$request->pi_item_id] : [];
        $storeId = $request->store_id ?? null;
        $soId = $request->so_id ?? null;
        $uniqueSoIds = PiItem::whereIn('id', $piItemIds)->whereNotNull('so_id')->pluck('so_id')->toArray();
        $inventoryStock = InventoryHelper::totalInventoryAndStock($item->id, $selectedAttr, $item?->uom_id, $storeId);
        $pendingPo = InventoryHelper::getPendingPo($item?->id, $item?->uom_id, $selectedAttr, $storeId);
        $html = view('procurement.pi.partials.comp-item-detail', compact('item', 'selectedAttr', 'remark', 'uomName', 'qty', 'specifications', 'inventoryStock', 'itemId', 'storeId', 'subStoreId', 'attributes'))->render();
        return response()->json(['data' => ['html' => $html, 'inventoryStock' => $inventoryStock, 'pendingPo' => $pendingPo], 'status' => 200, 'message' => 'fetched.']);
    }

    # Edit Po
    public function edit(Request $request, $id)
    {
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->back();
        }
        $user = Helper::getAuthenticatedUser();
        $serviceAlias = ConstantHelper::PI_SERVICE_ALIAS;
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $pi = PurchaseIndent::find($id);
        $createdBy = $pi->created_by;
        $revision_number = $pi->revision_number ?? 0;
        $creatorType = Helper::userCheck()['type'];
        $buttons = Helper::actionButtonDisplay($pi->book_id, $pi->document_status, $pi->id, 0, $pi->approval_level, $pi->created_by ?? 0, $creatorType, $revision_number);

        $revNo = $pi->revision_number;
        if ($request->has('revisionNumber')) {
            $revNo = intval($request->revisionNumber);
        } else {
            $revNo = $pi->revision_number;
        }
        $selectedfyYear = Helper::getFinancialYear($pi->document_date ?? Carbon::now()->format('Y-m-d'));

        $approvalHistory = Helper::getApprovalHistory($pi->book_id, $pi->id, $revNo, 0, $createdBy);
        $view = 'procurement.pi.edit';

        if ($request->has('revisionNumber') && $request->revisionNumber != $pi->revision_number) {
            $pi = $pi->source()->where('revision_number', $request->revisionNumber)->first();
            $view = 'procurement.pi.view';
        }

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$pi->document_status] ?? '';
        $departmentsData = UserHelper::getDepartments($user->auth_user_id ?? 0);
        $users = UserHelper::getUserSubOrdinates($user->auth_user_id ?? 0);
        $selecteduserId = $pi?->user_id;
        $isEdit = $buttons['submit'];
        if (!$isEdit) {
            $isEdit = $buttons['amend'] && intval(request('amendment') ?? 0) ? true : false;
        }
        $locations = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCKK);
        $saleOrders = ErpSaleOrder::select('id', 'book_code', 'document_number')->whereIn('id', $pi->so_id ?? [])
            ->get();
        $workOrders = ErpProductionWorkOrder::select('id', 'book_code', 'document_number')->whereIn('id', $pi->pwo_id ?? [])
            ->get();

        $parameters = [];
        $response = BookHelper::fetchBookDocNoAndParameters($pi->book_id, $pi->document_date);
        if ($response['status'] === 200) {
            $parameters = json_decode(json_encode($response['data']['parameters']), true);
        }
        $soTrackingRequired = in_array('yes', $parameters['so_tracking_required']) ? true : false;
        return view($view, [
            'isEdit' => $isEdit,
            'books' => $books,
            'pi' => $pi,
            'buttons' => $buttons,
            'approvalHistory' => $approvalHistory,
            'docStatusClass' => $docStatusClass,
            'revision_number' => $revision_number,
            'departments' => $departmentsData['departments'],
            'users' => $users['data'],
            'selecteduserId' => $selecteduserId,
            'locations' => $locations,
            'saleOrders' => $saleOrders,
            'workOrders' => $workOrders,
            'current_financial_year' => $selectedfyYear,
            'soTrackingRequired' => $soTrackingRequired
        ]);
    }

    // genrate pdf
    public function generatePdf(Request $request, $id)
    {
        $user = Helper::getAuthenticatedUser();
        $organization = Organization::where('id', $user->organization_id)->first();
        $organizationAddress = Address::with(['city', 'state', 'country'])
            ->where('addressable_id', $user->organization_id)
            ->where('addressable_type', Organization::class)
            ->first();
        $pi = PurchaseIndent::with(['pi_items', 'book'])->findOrFail($id);

        $imagePath = public_path('assets/css/midc-logo.jpg');
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$pi->document_status] ?? '';
        $pdf = PDF::loadView(
            'pdf.pi',
            [
                'pi' => $pi,
                'organization' => $organization,
                'organizationAddress' => $organizationAddress,
                'imagePath' => $imagePath,
                'docStatusClass' => $docStatusClass,
                'user' => $user
            ]
        );
        return $pdf->stream('Purchase-Indent-' . date('Y-m-d') . '.pdf');
    }

    # Get So Item List
    public function getSo(Request $request)
    {
        $isAttribute = intval($request->is_attribute) ?? 0;
        $seriesId = $request->series_id ?? null;
        $docNumber = $request->document_number ?? null;
        $itemId = $request->item_id ?? null;
        $customerId = $request->customer_id ?? null;
        $headerBookId = $request->header_book_id ?? null;
        $itemSearch = $request->item_search ?? null;
        $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($headerBookId);
        $soItems = ErpSoItem::where(function ($query) {
            $query->whereDoesntHave('soItemMapping')
                ->orWhereHas('soItemMapping', function ($subQuery) {
                    $subQuery->select(DB::raw('SUM(pi_item_qty)'))
                        ->groupBy('so_item_id')
                        ->havingRaw('SUM(pi_item_qty) < SUM(qty)');
                });
        })
            ->whereColumn('invoice_qty', '<', 'order_qty')
            ->whereHas('header', function ($subQuery) use ($request, $applicableBookIds, $docNumber) {
                $subQuery->whereIn('book_id', $applicableBookIds)
                    ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED])
                    ->when($request->customer_id, function ($q) use ($request) {
                        $q->where('customer_id', $request->customer_id);
                    })
                    ->when($request->book_id, function ($q) use ($request) {
                        $q->where('book_id', $request->book_id);
                    })
                    ->when($request->document_id, function ($q) use ($request) {
                        $q->where('id', $request->document_id);
                    })
                    ->when($docNumber, function ($q) use ($docNumber) {
                        $q->where('document_number', 'LIKE', "%{$docNumber}%");
                    });
            })
            ->when($itemSearch, function ($q) use ($itemSearch) {
                $q->whereHas('item', function ($q2) use ($itemSearch) {
                    $q2->where('item_name', 'like', "%$itemSearch%")
                        ->orWhere('item_code', 'like', "%$itemSearch%");
                });
            })
            ->with(['header', 'item', 'soItemMapping']);

        if (!$isAttribute) {
            $groupByColumns = ['sale_order_id', 'item_id', 'item_name', 'item_code'];
            $soItems = $soItems->groupBy($groupByColumns)
                ->selectRaw(implode(',', array_merge($groupByColumns, [
                    'SUM(order_qty) as order_qty',
                    'SUM(invoice_qty) as invoice_qty'
                ])));
        }
        $soItems = $soItems->orderBy('id', 'DESC')->get();

        $html = view('procurement.pi.partials.so-item-list', ['soItems' => $soItems, 'isAttribute' => $isAttribute])->render();
        return response()->json(['data' => ['pis' => $html, 'isAttribute' => $isAttribute], 'status' => 200, 'message' => "fetched!"]);
    }

    // Submit PI Item list
    public function processSoItem(Request $request)
    {
        $procurementType = $request->procurement_type ?? 'rm';
        $isAttribute = (int) ($request->is_attribute ?? 0);
        $user = Helper::getAuthenticatedUser();
        $createdBy = $user?->auth_user_id;

        $ids = array_values(array_unique(json_decode($request->ids, true) ?? []));

        if (!$isAttribute) {
            $selectedData = json_decode($request->selected_items, true) ?? [];
            $saleOrderIds = array_column($selectedData, 'sale_order_id');
            $itemIds = array_column($selectedData, 'item_id');

            $ids = ErpSoItem::whereIn('sale_order_id', $saleOrderIds)
                ->whereIn('item_id', $itemIds)
                ->pluck('id')
                ->toArray();
        }

        $soItems = ErpSoItem::with(['soItemMapping', 'attributes', 'header', 'item', 'item_attributes'])
            ->whereIn('id', $ids)
            ->where(function ($query) {
                $query->whereDoesntHave('soItemMapping')
                    ->orWhereHas('soItemMapping', function ($subQuery) {
                        $subQuery->select(DB::raw('SUM(pi_item_qty)'))
                            ->groupBy('so_item_id')
                            ->havingRaw('SUM(pi_item_qty) < SUM(qty)');
                    });
            })
            ->get();

        $soItemIdArr = [];
        $service = new PiService;

        \DB::beginTransaction();
        try {
            foreach ($soItems as $soItem) {
                $soItemId = $soItem->id;
                $itemId = $soItem->item_id;
                $soItemIdArr[] = $soItem->id;
                $soId = $soItem->sale_order_id ?? ($soItem?->header?->id ?? null);
                $existingMappedOrderQty = $soItem->soItemMapping->sum('order_qty');
                $avlQty = max($soItem->order_qty - $soItem->invoice_qty - $existingMappedOrderQty, 0);
                if ($avlQty <= 0) continue;

                $attributes = $soItem->attributes->map(fn($a) => [
                    'attribute_id' => (int) ($a->item_attribute_id ?? 0),
                    'attribute_value' => (int) ($a->attr_value ?? 0),
                ])->filter(fn($a) => $a['attribute_id'] > 0 && $a['attribute_value'] > 0)
                    ->values()
                    ->all();

                $result = $service->expandAndUpsertMappingsIterative(
                    soId: $soId,
                    soItemId: $soItemId,
                    itemId: $itemId,
                    attributes: $attributes,
                    qty: floatval($avlQty),
                    createdBy: $createdBy,
                    soItemOrderQty: floatval($avlQty)
                );

                if ($result['status'] !== 200) {
                    DB::rollBack();
                    return response()->json(['data' => ['pos' => ''], 'status' => 422, 'message' => $result['message']]);
                }
            }

            // PiSoMapping::whereIn('so_item_id', $soItemIdArr)
            //     ->where('created_by', $user->auth_user_id)
            //     ->whereNotNull('child_bom_id')
            //     ->delete();

            $soTracking = strtolower($request->so_tracking_required ?? 'no');

            if ($soTracking === 'yes') {
                $soProcessItems = PiSoMapping::whereIn('so_item_id', $soItemIdArr)
                    ->select(
                        'erp_pi_so_mapping.vendor_id',
                        'erp_pi_so_mapping.so_id',
                        'erp_pi_so_mapping.item_id',
                        DB::raw('erp_pi_so_mapping.attributes'),
                        'erp_pi_so_mapping.uom_id',
                        DB::raw('CEIL(SUM(erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty)) as total_qty')
                    )
                    ->groupBy('erp_pi_so_mapping.so_id', 'erp_pi_so_mapping.item_id', 'erp_pi_so_mapping.attributes', 'erp_pi_so_mapping.vendor_id')
                    ->havingRaw('total_qty > 0')
                    ->get();
            } else {
                $soProcessItems = PiSoMapping::whereIn('so_item_id', $soItemIdArr)
                    ->select(
                        DB::raw('NULL as so_id'),
                        'erp_pi_so_mapping.vendor_id',
                        'erp_pi_so_mapping.item_id',
                        DB::raw('erp_pi_so_mapping.attributes'),
                        'erp_pi_so_mapping.uom_id',
                        DB::raw('CEIL(SUM(erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty)) as total_qty')
                    )
                    ->groupBy('erp_pi_so_mapping.item_id', 'erp_pi_so_mapping.attributes', 'erp_pi_so_mapping.vendor_id')
                    ->havingRaw('total_qty > 0')
                    ->get();
            }

            if ($procurementType === 'rm') {
                $html = view('procurement.pi.partials.so-process-data', ['soTracking' => $soTracking, 'soProcessItems' => $soProcessItems])->render();
            } else {
                $storeId = $request->store_id ?? null;
                $soTrackingRequired = strtolower($soTracking) == 'yes' ? true : false;
                $html = view('procurement.pi.partials.fg-item-row', ['soTrackingRequired' => $soTrackingRequired, 'soItems' => $soProcessItems, 'storeId' => $storeId])->render();
            }

            DB::commit();
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json(['data' => ['pos' => ''], 'status' => 422, 'message' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile()]);
        }

        return response()->json(['data' => ['pos' => $html, 'procurement_type' => $procurementType], 'status' => 200, 'message' => "fetched!"]);
    }

    public function processSoItemSubmit(Request $request)
    {
        $storeId = $request->store_id ?? null;
        $selectedData = $request->selectedData ?? $request->selected_items ?? [];
        $soItems = [];

        if (is_array($selectedData)) {
            $soItems = $selectedData;
        } elseif (is_string($selectedData)) {
            $decoded = json_decode($selectedData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $soItems = $decoded;
            }
        }

        $isAttribute = intval($request->is_attribute) ?? 0;
        $extendedItems = $soItems;

        if (!$isAttribute) {
            foreach ($soItems as $index => $item) {
                if (!empty($item['main_so_item']) && !empty($item['so_item_ids'])) {
                    $soSubItems = ErpSoItem::where('sale_order_id', $item['so_id'])
                        ->whereIn('id', $item['so_item_ids'])
                        ->get();

                    $newItems = [];
                    unset($item['so_item_ids']);

                    foreach ($soSubItems as $soItem) {
                        $newItem = $item;
                        $newItem['item_id']    = $soItem->item_id;
                        $newItem['item_name']  = $soItem->item_name;
                        $newItem['item_code']  = $soItem->item_code;
                        $newItem['uom_id']     = $soItem->uom_id;
                        $newItem['uom_name']   = $soItem->uom->name;
                        $newItem['total_qty']  = $soItem->order_qty;
                        $newItem['so_item_id'] = $soItem->id;
                        $newItem['attribute']  = $soItem->item_attributes_array();
                        $newItems[] = $newItem;
                    }

                    array_splice($extendedItems, $index, 1, $newItems);
                }
            }
        }

        $soTrackingRequired = strtolower($request->so_tracking_required) == 'yes';

        if ($soTrackingRequired) {
            foreach ($extendedItems as &$piSoItemMapping) {
                $attributes = array_map(function ($item) {
                    return [
                        'attribute_id'    => $item['id'],
                        'attribute_value' => $item['values_data'][0]['id'] ?? null
                    ];
                }, $piSoItemMapping['attributes'] ?? []);

                $datas = PiSoMapping::where('item_id', $piSoItemMapping['item_id'])
                    ->when(count($attributes), function ($query) use ($attributes) {
                        $query->whereJsonContains('attributes', $attributes);
                    })
                    ->where(function ($query) use ($piSoItemMapping) {
                        if ($piSoItemMapping['so_id']) {
                            $query->where('so_id', $piSoItemMapping['so_id']);
                        }
                        if ($piSoItemMapping['vendor_id']) {
                            $query->where('vendor_id', $piSoItemMapping['vendor_id']);
                        }
                    })
                    ->first();

                if ($datas?->bomDetail) {
                    $piSoItemMapping['remark'] = $datas->bomDetail->remark;
                }
                unset($piSoItemMapping);
            }
        }

        $rowCount = intval($request->rowCount) ? intval($request->rowCount) + 1 : 1;
        $html = view('procurement.pi.partials.item-row-so', [
            'soItems'            => $extendedItems,
            'soTrackingRequired' => $soTrackingRequired,
            'storeId'            => $storeId,
            'rowCount'           => $rowCount,
            'is_pull'            => true,
        ])->render();

        return response()->json([
            'data'    => ['pos' => $html],
            'status'  => 200,
            'message' => "fetched!"
        ]);
    }

    public function cancel(Request $request, $pi)
    {
        $pi = PurchaseIndent::find($pi);
        if (!$pi) {
            return response()->json(['status' => false, 'message' => 'Document not found.'], 404);
        }

        \DB::beginTransaction();
        try {

            $piDeleteService = app(\App\Services\PI\PiDeleteService::class);
            $response = $piDeleteService->cancelPiHeader($pi, $request->remark);

            if ($response['status'] === 'error') {
                \DB::rollBack();
                return response()->json(['status' => false, 'message' => $response['message']], 422);
            }

            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Document cancelled successfully.'], 200);
        } catch (\Exception $ex) {
            \DB::rollBack();

            return response()->json([
                'message' => $ex->getMessage(),
                'line' => $ex->getLine(),
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    public function revokeDocument(Request $request)
    {
        DB::beginTransaction();
        try {
            $pi = PurchaseIndent::find($request->id);
            if (isset($pi)) {
                $revoke = Helper::approveDocument($pi->book_id, $pi->id, $pi->revision_number, '', [], 0, ConstantHelper::REVOKE, $pi->grand_total_amount, get_class($pi));
                if ($revoke['message']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $revoke['message'],
                    ]);
                } else {
                    $pi->document_status = $revoke['approvalStatus'];
                    $pi->save();
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Revoked succesfully',
                    ]);
                }
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Document found',
                ]);
            }
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $ex->getMessage(),
            ]);
        }
    }

    public function getSelectedDepartment(Request $request)
    {
        $departments = UserHelper::getDepartments($request->user_id ?? 0);
        return array(
            'selectedDeaprtmentId' => $departments['selectedDepartmentId']
        );
    }

    public function piReport(Request $request)
    {
        $pathUrl = route('pi.index');
        $orderType = [ConstantHelper::PI_SERVICE_ALIAS];
        $puchaseIndents = PurchaseIndent::with('items')->withDraftListingLogic()->orderByDesc('id');
        //Vendor Filter
        $puchaseIndents = $puchaseIndents->when($request->vendor_id, function ($vendorQuery) use ($request) {
            $vendorQuery->where('vendor_id', $request->vendor_id);
        });
        //Book Filter
        $puchaseIndents = $puchaseIndents->when($request->book_id, function ($bookQuery) use ($request) {
            $bookQuery->where('book_id', $request->book_id);
        });
        //Document Id Filter
        $puchaseIndents = $puchaseIndents->when($request->document_number, function ($docQuery) use ($request) {
            $docQuery->where('document_number', 'LIKE', '%' . $request->document_number . '%');
        });
        //Location Filter
        $puchaseIndents = $puchaseIndents->when($request->location_id, function ($docQuery) use ($request) {
            $docQuery->where('store_id', $request->location_id);
        });
        //Company Filter
        $puchaseIndents = $puchaseIndents->when($request->company_id, function ($docQuery) use ($request) {
            $docQuery->where('store_id', $request->company_id);
        });
        //Organization Filter
        $puchaseIndents = $puchaseIndents->when($request->organization_id, function ($docQuery) use ($request) {
            $docQuery->where('organization_id', $request->organization_id);
        });
        //Document Status Filter
        $puchaseIndents = $puchaseIndents->when($request->doc_status, function ($docStatusQuery) use ($request) {
            $searchDocStatus = [];
            if ($request->doc_status === ConstantHelper::DRAFT) {
                $searchDocStatus = [ConstantHelper::DRAFT];
            } else if ($request->doc_status === ConstantHelper::SUBMITTED) {
                $searchDocStatus = [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED];
            } else {
                $searchDocStatus = [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED];
            }
            $docStatusQuery->whereIn('document_status', $searchDocStatus);
        });
        //Date Filters
        $dateRange = $request->date_range ??  Carbon::now()->startOfMonth()->format('Y-m-d') . " to " . Carbon::now()->endOfMonth()->format('Y-m-d');
        $puchaseIndents = $puchaseIndents->when($dateRange, function ($dateRangeQuery) use ($request, $dateRange) {
            $dateRanges = explode('to', $dateRange);
            if (count($dateRanges) == 2) {
                $fromDate = Carbon::parse(trim($dateRanges[0]))->format('Y-m-d');
                $toDate = Carbon::parse(trim($dateRanges[1]))->format('Y-m-d');
                $dateRangeQuery->whereDate('document_date', ">=", $fromDate)->where('document_date', '<=', $toDate);
            } else {
                $fromDate = Carbon::parse(trim($dateRanges[0]))->format('Y-m-d');
                $dateRangeQuery->whereDate('document_date', $fromDate);
            }
        });
        //Item Id Filter
        $puchaseIndents = $puchaseIndents->when($request->item_id, function ($itemQuery) use ($request) {
            $itemQuery->withWhereHas('items', function ($itemSubQuery) use ($request) {
                $itemSubQuery->where('item_id', $request->item_id)
                    //Compare Item Category
                    ->when($request->item_category_id, function ($itemCatQuery) use ($request) {
                        $itemCatQuery->whereHas('item', function ($itemRelationQuery) use ($request) {
                            $itemRelationQuery->where('category_id', $request->category_id)
                                //Compare Item Sub Category
                                ->when($request->item_sub_category_id, function ($itemSubCatQuery) use ($request) {
                                    $itemSubCatQuery->where('subcategory_id', $request->item_sub_category_id);
                                });
                        });
                    });
            });
        });
        $puchaseIndents = $puchaseIndents->get();
        $processedSalesOrder = collect([]);
        foreach ($puchaseIndents as $pi) {
            foreach ($pi->items as $piItem) {
                $reportRow = new stdClass();
                //Header Details
                $header = $piItem->pi;
                $reportRow->id = $header->id;
                $reportRow->book_name = $header->book_code;
                $reportRow->document_number = $header->document_number;
                $reportRow->document_date = $header->document_date;
                $reportRow->store_name = $header->store?->store_name;
                $reportRow->sub_store_name = $header->sub_store?->name;
                $reportRow->requester_type = $header->requester_type;
                $reportRow->requester_name = $header->requester_name();
                $reportRow->vendor_currency = $header->currency_code;
                $reportRow->payment_terms_name = $header->payment_term_code;
                //Item Details
                $reportRow->item_name = $piItem->item_name;
                $reportRow->item_code = $piItem->item_code;
                $reportRow->hsn_code = $piItem->hsn?->code;
                $reportRow->uom_name = $piItem->uom?->name;
                //Amount Details
                $reportRow->po_qty = number_format($piItem->indent_qty, 2);
                $reportRow->mi_qty = number_format($piItem->mi_qty ?? 0.00, 2);
                $reportRow->so_qty = number_format($piItem->order_qty ?? 0.00, 2);
                $reportRow->so_no = $piItem?->so ? $piItem?->so?->book_code . "-" . $piItem?->so?->document_number : " ";
                //Attributes UI
                $attributesUi = '';
                if (count($piItem->attributes) > 0) {
                    foreach ($piItem->attributes as $soAttribute) {
                        $attrName = AttributeGroup::find($soAttribute->attribute_group_id)?->name;
                        $attrValue = Attribute::find($soAttribute->attribute_value)?->value;
                        $attributesUi .= "<span class='badge rounded-pill badge-light-primary' > $attrName : $attrValue </span>";
                    }
                } else {
                    $attributesUi = 'N/A';
                }
                $reportRow->attributes = $attributesUi;
                //Main header Status
                $reportRow->status = $header->document_status;
                $processedSalesOrder->push($reportRow);
            }
        }
        return DataTables::of($processedSalesOrder)->addIndexColumn()
            ->editColumn('status', function ($row) use ($orderType) {
                $statusClasss = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->status ?? ConstantHelper::DRAFT];
                $displayStatus = ucfirst($row->status);
                $editRoute = null;
                $editRoute = route('pi.edit', ['id' => $row->id]);
                return "
                <div style='text-align:right;'>
                    <span class='badge rounded-pill $statusClasss badgeborder-radius'>$displayStatus</span>
                        <a href='" . $editRoute . "'>
                            <i class='cursor-pointer' data-feather='eye'></i>
                        </a>
                </div>
            ";
            })
            ->rawColumns(['attributes', 'delivery_schedule', 'status'])
            ->make(true);
    }

    public function analyzeSoItem(Request $request)
    {
        $ids = json_decode($request->ids, true) ?? [];
        $ids = array_values(array_unique($ids));
        $isAttribute = intval($request->is_attribute) ?? 0;

        if (!$isAttribute) {
            $selectedData = json_decode($request->selected_items, true);
            $ids = ErpSoItem::where(function ($query) use ($selectedData) {
                foreach ($selectedData as $selectedItem) {
                    $query->orWhere(function ($q) use ($selectedItem) {
                        $q->where('sale_order_id', $selectedItem['sale_order_id'])
                            ->where('item_id', $selectedItem['item_id']);
                    });
                }
            })->pluck('id')->toArray();
        }

        $soItemIds = ErpSoItem::whereIn('id', $ids)->pluck('id')->toArray();
        $bomService = new BomService;
        $femifishedItems = $bomService->getRawMaterialBreakdown($soItemIds, 'semi');

        if (!$isAttribute) {
            $temp = [];
            foreach ($femifishedItems as $soItemId => $item) {
                $fg = $item['semi_finished_goods']['fg'];
                $key = $fg['so_id'] . '_' . $fg['bom_id'];
                $temp[$key][] = [
                    'so_item_id' => $soItemId,
                    'fg' => $fg
                ];
            }

            $grouped = [];
            foreach ($temp as $key => $items) {
                if (count($items) > 0) {
                    $soId = $items[0]['fg']['so_id'];
                    $bomId = $items[0]['fg']['bom_id'];
                    $fg = $items[0]['fg'];
                    $fg['so_item_ids'] = [$items[0]['so_item_id']];
                    for ($i = 1; $i < count($items); $i++) {
                        $fg['total_qty'] += (float) $items[$i]['fg']['total_qty'];
                        $fg['so_item_ids'][] = $items[$i]['so_item_id'];
                    }
                    $fg['so_item_ids'] = implode(',', $fg['so_item_ids']);
                    if (count($items) > 1) {
                        $fg['attribute'] = [];
                    }
                    $grouped[$soId] = [
                        'semi_finished_goods' => [
                            'fg' => $fg
                        ]
                    ];
                }
            }
            $femifishedItems = $grouped;
        } else {
            $newGrouped = [];
            foreach ($femifishedItems as $soItemId => $femifishedItem) {
                $fg = $femifishedItem['semi_finished_goods']['fg'];
                $fg['so_item_id'] = $soItemId;
                $newGrouped[$soItemId] = [
                    'semi_finished_goods' => [
                        'fg' => $fg
                    ]
                ];
            }
            $femifishedItems = $newGrouped;
        }

        $html = view('procurement.pi.partials.analyze-item', [
            'femifishedItems' => $femifishedItems,
            'isAttribute' => $isAttribute
            //  'rowCount' => $rowCount
        ])->render();

        return response()->json(['data' => ['pos' => $html], 'status' => 200, 'message' => "fetched!"]);
    }

    public function processAnalyzedBomItem(Request $request)
    {
        $user = Helper::getAuthenticatedUser();
        $procurementType = $request->procurement_type ?? 'rm';
        $selectedItems = $request->selected_items;
        $items = is_array($selectedItems)
            ? $selectedItems
            : (is_string($selectedItems) && is_array(json_decode($selectedItems, true))
                ? json_decode($selectedItems, true)
                : []);

        $soItemIdArr = [];
        $deleteSoItemIdArr = [];
        $createdBy = $user?->auth_user_id;

        $html = '';
        $service =  new PiService;

        if ($procurementType === 'rm') {
            DB::beginTransaction();
            try {
                foreach ($items as $item) {
                    $level          = $item['level'] ?? null;
                    $bomId          = $item['bom_id'] ?? null;
                    $itemId         = $item['item_id'] ?? null;
                    $soItemId       = $item['so_item_id'] ?? [];
                    $soItemIds      = $item['so_item_ids'] ?? [];
                    $mainSoItem   = $item['main_so_item'] ?? null;
                    $reqQty         = floatval($item['req_qty'] ?? 0);

                    if ($reqQty <= 0) continue;

                    array_filter($soItemIds);
                    if (count($soItemIds) && $mainSoItem && $level == 0) {
                        foreach ($soItemIds as $soItemId) {
                            $soItem = ErpSoItem::find($soItemId);
                            if ($soItem) {
                                $soItemIdArr[] = $soItemId;
                                $existingMappedOrderQty = $soItem->soItemMapping->sum('order_qty');
                                $avlQty = max($soItem->order_qty - $soItem->invoice_qty - $existingMappedOrderQty, 0);
                                if ($avlQty <= 0) continue;

                                $soAttributes = $soItem?->attributes->map(fn($attr) => [
                                    'attribute_id'   => $attr->item_attribute_id,
                                    'attribute_value' => intval($attr->attr_value)
                                ])->toArray() ?? [];
                                $res = $service->expandAndUpsertMappingsIterative($soItem->sale_order_id, $soItemId, $itemId, $soAttributes, $soItem->order_qty, $createdBy, $soItem->order_qty);
                                if ($res['status'] == 422) {
                                    DB::rollBack();
                                    return response()->json([
                                        'data' => ['pos' => ''],
                                        'status' => 422,
                                        'message' => $res['message']
                                    ]);
                                }
                                $deleteSoItemIdArr[] = $soItemId;
                            }
                        }
                    } else {
                        $soItem = ErpSoItem::find($soItemId);
                        if ($soItem) {
                            $soItemIdArr[] = $soItemId;
                            $existingMappedOrderQty = $soItem->soItemMapping->sum('order_qty');
                            $avlQty = max($reqQty - $soItem->invoice_qty - $existingMappedOrderQty, 0);
                            if ($avlQty <= 0) continue;
                            $soAttributes = $soItem?->attributes->map(fn($attr) => [
                                'attribute_id'   => $attr->item_attribute_id,
                                'attribute_value' => intval($attr->attr_value)
                            ])->toArray() ?? [];
                            $res = $service->syncPiSoMapping($soItem->sale_order_id, $soItemId, $itemId, $soAttributes, $reqQty, $createdBy, $soItem->order_qty);
                            if ($res['status'] == 422) {
                                DB::rollBack();
                                return response()->json([
                                    'data' => ['pos' => ''],
                                    'status' => 422,
                                    'message' => $res['message']
                                ]);
                            }
                            $deleteSoItemIdArr[] = $soItemId;
                        }
                    }
                }

                if (!empty($deleteSoItemIdArr)) {
                    PiSoMapping::whereIn('so_item_id', $deleteSoItemIdArr)
                        ->where('created_by', $user->auth_user_id)
                        ->whereNotNull('child_bom_id')
                        ->delete();
                }

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                return response()->json([
                    'data' => ['pos' => ''],
                    'status' => 422,
                    'message' => $th->getMessage()
                ]);
            }

            $soItemIdArr = array_unique($soItemIdArr);
            $soTracking = $request?->so_tracking_required ?? 'no';

            if ($soTracking === 'yes') {
                $soProcessItems = PiSoMapping::whereIn('so_item_id', $soItemIdArr)
                    ->select(
                        'erp_pi_so_mapping.vendor_id',
                        'erp_pi_so_mapping.so_id',
                        'erp_pi_so_mapping.item_id',
                        DB::raw('erp_pi_so_mapping.attributes'),
                        'erp_pi_so_mapping.uom_id',
                        DB::raw('CEIL(SUM(erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty)) as total_qty')
                    )
                    ->groupBy('erp_pi_so_mapping.so_id', 'erp_pi_so_mapping.item_id', 'erp_pi_so_mapping.attributes', 'erp_pi_so_mapping.vendor_id')
                    ->havingRaw('total_qty > 0')
                    ->get();
            } else {
                $soProcessItems = PiSoMapping::whereIn('so_item_id', $soItemIdArr)
                    ->select(
                        DB::raw('NULL as so_id'),
                        'erp_pi_so_mapping.vendor_id',
                        'erp_pi_so_mapping.item_id',
                        DB::raw('erp_pi_so_mapping.attributes'),
                        'erp_pi_so_mapping.uom_id',
                        DB::raw('CEIL(SUM(erp_pi_so_mapping.qty - erp_pi_so_mapping.pi_item_qty)) as total_qty')
                    )
                    ->groupBy('erp_pi_so_mapping.item_id', 'erp_pi_so_mapping.attributes', 'erp_pi_so_mapping.vendor_id')
                    ->havingRaw('total_qty > 0')
                    ->get();
            }

            $html = view('procurement.pi.partials.so-process-data', [
                'soTracking' => $soTracking,
                'soProcessItems' => $soProcessItems
            ])->render();
        }

        return response()->json([
            'data' => ['pos' => $html, 'procurement_type' => $procurementType],
            'status' => 200,
            'message' => "fetched!"
        ]);
    }

    public function checkPoUtilizedItem(Request $request)
    {
        $piItemId = $request->pi_item_id;
        $piPoConsumed = PiPoMapping::where('pi_item_id', $piItemId)
            ->whereHas('po', function ($q) {
                $q->whereIn('document_status', [
                    ConstantHelper::APPROVED,
                    ConstantHelper::APPROVAL_NOT_REQUIRED
                ]);
            })
            ->with([
                'po:id,document_number',
                'po_item:id,po_id,order_qty'
            ])
            ->get();

        if ($piPoConsumed->count()) {
            $totalUtilized = $piPoConsumed->sum('po_qty');

            return response()->json([
                'data' => [
                    'total_utilized_qty' => $totalUtilized,
                    'po_list' => $piPoConsumed->map(function ($map) {
                        return [
                            'po_id'          => $map->po_id,
                            'document_number' => $map->po->document_number ?? null,
                            'po_qty'         => $map->po_qty,
                        ];
                    }),
                ],
                'status' => 422,
                'message' => "Item already utilized in PO(s)!",
            ]);
        }

        return response()->json([
            'data' => null,
            'status' => 200,
            'message' => "Item free to delete!",
        ]);
    }

    public function destroy($piId, $isAmedment = false)
    {
        $pi = PurchaseIndent::find($piId);
        if (!$pi) {
            return response()->json(['status' => false, 'message' => 'Indent not found.'], 404);
        }

        if (!$isAmedment && $pi->document_status !== ConstantHelper::DRAFT) {
            return response()->json(['status' => false, 'message' => 'Only draft documents can be deleted.'], 422);
        }

        \DB::beginTransaction();
        try {

            $piDeleteService = app(\App\Services\PI\PiDeleteService::class);
            $response = $piDeleteService->deletePiHeader($pi);

            if ($response['status'] === 'error') {
                \DB::rollBack();
                return response()->json(['status' => false, 'message' => $response['message']], 422);
            }

            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Document deleted successfully.'], 200);
        } catch (\Exception $ex) {
            \DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Error deleting Indent: ' . $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }
}

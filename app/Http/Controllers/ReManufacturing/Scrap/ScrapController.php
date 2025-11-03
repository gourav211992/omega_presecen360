<?php

namespace App\Http\Controllers\ReManufacturing\Scrap;

use DB;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\Unit;
use App\Helpers\Helper;
use App\Models\Address;
use App\Models\Attribute;
use App\Helpers\BookHelper;
use App\Helpers\UserHelper;
use App\Models\ErpPslipItem;
use App\Models\Organization;
use Illuminate\Http\Request;
use App\Helpers\GeneralHelper;
use App\Models\AttributeGroup;
use App\Models\Scrap\ErpScrap;
use App\Helpers\ConstantHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\InventoryHelper;
use Yajra\DataTables\DataTables;
use App\Models\Scrap\ErpScrapItem;
use App\Helpers\DynamicFieldHelper;
use App\Http\Requests\ScrapRequest;
use App\Http\Controllers\Controller;
use App\Helpers\ServiceParametersHelper;
use App\Helpers\TransactionReportHelper;
use App\Helpers\Common\OrganizationHelper;
use App\Services\Scrap\ScrapDeleteService;
use App\Models\Scrap\ErpScrapItemAttribute;
use App\Services\Common\DocumentLockService;
use App\Services\Common\FinancialYearService;
use App\Models\Scrap\ErpScrapPslipItemMapping;

class ScrapController extends Controller
{
    public function index(Request $request)
    {
        $user = Helper::getAuthenticatedUser();
        $parentUrl = request()->segments()[0];
        if (request()->ajax()) {
            $dateRange = $request->date_range ??  null;
            $organization = OrganizationHelper::getAuthenticatedOrganization();
            $accessible_locations = InventoryHelper::getAccessibleLocations()->pluck('id')->toArray();
            $selectedfyYear = app(FinancialYearService::class)->getFinancialYear(date('Y-m-d'), request()->user());

            $scrapHeaders = ErpScrap::withDraftListingLogic()
                ->bookViewAccess($parentUrl)
                ->withDefaultGroupCompanyOrg()
                ->selfCreatedDocuments($user)
                ->whereBetween('document_date', [$selectedfyYear['start_date'], $selectedfyYear['end_date']])
                ->with(['createdBy'])
                ->whereBetween('document_date', [$selectedfyYear['start_date'], $selectedfyYear['end_date']])
                ->when($request->customer_id, function ($custQuery) use ($request) {
                    $custQuery->where('customer_id', $request->customer_id);
                })->when($request->book_id, function ($bookQuery) use ($request) {
                    $bookQuery->where('book_id', $request->book_id);
                })->when($request->document_number, function ($docQuery) use ($request) {
                    $docQuery->where('document_number', 'LIKE', '%' . $request->document_number . '%');
                })->when($request->location_id, function ($docQuery) use ($request) {
                    $docQuery->where('store_id', $request->location_id);
                })->when($request->company_id, function ($docQuery) use ($request) {
                    $docQuery->where('company_id', $request->company_id);
                })->when($request->organization_id, function ($docQuery) use ($request) {
                    $docQuery->where('organization_id', $request->organization_id);
                })->when($request->created_by, function ($docQuery) use ($request) {
                    $docQuery->where('created_by', $request->created_by);
                })->when($request->status, function ($docStatusQuery) use ($request) {
                    $searchDocStatus = [];
                    if ($request->status === ConstantHelper::DRAFT) {
                        $searchDocStatus = [ConstantHelper::DRAFT];
                    } else if ($request->status === ConstantHelper::SUBMITTED) {
                        $searchDocStatus = [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED];
                    } else {
                        $searchDocStatus = [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED];
                    }
                    $docStatusQuery->whereIn('document_status', $searchDocStatus);
                })->when($dateRange, function ($dateRangeQuery) use ($request, $dateRange) {
                    $dateRanges = explode('to', $dateRange);
                    if (count($dateRanges) == 2) {
                        $fromDate = Carbon::parse(trim($dateRanges[0]))->format('Y-m-d');
                        $toDate = Carbon::parse(trim($dateRanges[1]))->format('Y-m-d');
                        $dateRangeQuery->whereDate('document_date', ">=", $fromDate)->where('document_date', '<=', $toDate);
                    } else {
                        $fromDate = Carbon::parse(trim($dateRanges[0]))->format('Y-m-d');
                        $dateRangeQuery->whereDate('document_date', $fromDate);
                    }
                })->when($request->item_id, function ($itemQuery) use ($request) {
                    $itemQuery->withWhereHas('items', function ($itemSubQuery) use ($request) {
                        $itemSubQuery->where('item_id', $request->item_id)
                            ->when($request->item_category_id, function ($itemCatQuery) use ($request) {
                                $itemCatQuery->whereHas('item', function ($itemRelationQuery) use ($request) {
                                    $itemRelationQuery->where('category_id', $request->category_id)
                                        ->when($request->item_sub_category_id, function ($itemSubCatQuery) use ($request) {
                                            $itemSubCatQuery->where('subcategory_id', $request->item_sub_category_id);
                                        });
                                });
                            });
                    });
                })->orderByDesc('id')
                ->latest();

            return DataTables::of($scrapHeaders)
                ->addIndexColumn()
                ->editColumn('document_status', function ($row) {
                    return view('partials.action-dropdown', [
                        'statusClass' => ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status] ?? 'badge-light-secondary',
                        'displayStatus' => $row->display_status,
                        'row' => $row,
                        'actions' => [
                            [
                                'url' => fn($r) => route('scrap.edit', $r->id),
                                'icon' => 'edit-3',
                                'label' => 'View/ Edit Detail',
                            ],
                        ],
                    ])->render();
                })
                ->addColumn('book_name', function ($row) {
                    return $row->book ? $row->book?->book_name : '';
                })
                ->addColumn('store', function ($row) {
                    return $row?->store ? $row?->store?->store_name : '';
                })
                ->addColumn('sub_store', function ($row) {
                    return $row?->subStore ? $row?->subStore?->name : '';
                })
                ->addColumn('reference_from', function ($row) {
                    return $row?->reference_type ? $row?->reference_type : '-';
                })
                ->addColumn('total_reference_qty', function ($row) {
                    return $row->reference_type === 'pslip'
                        ? ($row->pslipItems ? number_format($row?->pslipItems?->sum('rejected_qty'), 2) : 0)
                        : 0;
                })
                ->addColumn('total_qty', function ($row) {
                    return $row?->total_qty ? number_format($row?->total_qty, 2) : '';
                })
                // ->addColumn('total_cost', function ($row) {
                //     return $row?->total_cost ? number_format($row?->total_cost, 2) : '';
                // })
                ->editColumn('document_date', function ($row) {
                    return $row->getFormattedDate('document_date') ?? '';
                })
                ->editColumn('revision_number', function ($row) {
                    return strval($row->revision_number);
                })
                ->addColumn('components', function ($row) {
                    return $row->items->count() ?? 0;
                })
                ->addColumn('created_by', function ($row) {
                    return $row->createdBy?->name;
                })
                ->rawColumns(['document_status'])
                ->make(true);
        }
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);

        return view('remanufacturing.scrap.index', [
            'servicesBooks' => $servicesBooks,
            'filterArray' => TransactionReportHelper::FILTERS_MAPPING[ConstantHelper::SCRAP_SERVICE_ALIAS],
        ]);
    }

    public function create()
    {
        $parentUrl = request()->segments()[0];
        $user = Helper::getAuthenticatedUser();
        $selecteduserId = $user->auth_user_id;
        $serviceAlias = ConstantHelper::SCRAP_SERVICE_ALIAS;
        $locations = InventoryHelper::getAccessibleLocations();
        $selectedfyYear = Helper::getFinancialYear(Carbon::now());
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS['draft'];
        $users = UserHelper::getUserSubOrdinates($user->auth_user_id ?? 0);
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();

        return view('remanufacturing.scrap.create_edit', [
            'books' => $books,
            'users' => $users['data'],
            'locations' => $locations,
            'selecteduserId' => $selecteduserId,
            'docStatusClass' => $docStatusClass,
            'current_financial_year' => $selectedfyYear,
        ]);
    }

    // Edit Po
    public function edit(Request $request, $id)
    {
        $parentUrl = request()->segments()[0];
        $user = Helper::getAuthenticatedUser();
        $serviceAlias = ConstantHelper::SCRAP_SERVICE_ALIAS;
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $scrap = ErpScrap::find($id);
        $createdBy = $scrap->created_by;
        $revision_number = $scrap->revision_number ?? 0;
        $creatorType = Helper::userCheck()['type'];
        $buttons = Helper::actionButtonDisplay($scrap->book_id, $scrap->document_status, $scrap->id, 0, $scrap->approval_level, $scrap->created_by ?? 0, $creatorType, $revision_number);

        $revNo = $scrap->revision_number;
        if ($request->has('revisionNumber')) {
            $revNo = intval($request->revisionNumber);
        } else {
            $revNo = $scrap->revision_number;
        }

        $selectedfyYear = Helper::getFinancialYear($scrap->document_date ?? Carbon::now()->format('Y-m-d'));
        $approvalHistory = Helper::getApprovalHistory($scrap->book_id, $scrap->id, $revNo, 0, $createdBy);
        $view = 'remanufacturing.scrap.create_edit';

        if ($request->has('revisionNumber') && $request->revisionNumber != $scrap->revision_number) {
            $scrap = $scrap->source()->where('revision_number', $request->revisionNumber)->first();
            $view = 'remanufacturing.scrap.view';
        }

        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$scrap->document_status] ?? '';
        $users = UserHelper::getUserSubOrdinates($user->auth_user_id ?? 0);
        $selecteduserId = $scrap?->user_id;
        $isEdit = $buttons['submit'];
        if (!$isEdit) {
            $isEdit = $buttons['amend'] && intval(request('amendment') ?? 0) ? true : false;
        }
        $locations = InventoryHelper::getAccessibleLocations();
        $parameters = [];
        $response = BookHelper::fetchBookDocNoAndParameters($scrap->book_id, $scrap->document_date);
        if ($response['status'] === 200) {
            $parameters = json_decode(json_encode($response['data']['parameters']), true);
        }

        return view($view, [
            'isEdit' => $isEdit,
            'books' => $books,
            'scrap' => $scrap,
            'buttons' => $buttons,
            'approvalHistory' => $approvalHistory,
            'docStatusClass' => $docStatusClass,
            'revision_number' => $revision_number,
            'users' => $users['data'],
            'selecteduserId' => $selecteduserId,
            'locations' => $locations,
            'parameters' => $parameters,
            'current_financial_year' => $selectedfyYear,
        ]);
    }

    public function store(ScrapRequest $request, DocumentLockService $lockService)
    {
        DB::beginTransaction();
        try {
            $user = Helper::getAuthenticatedUser();
            $organization = OrganizationHelper::getAuthenticatedOrganization();
            // $item_attributes = json_decode($request->item_attributes[0],  true) ?? [];

            $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
            if (! $numberPatternData) {
                return response()->json(['message' => 'Invalid Book', 'error' => ''], 422);
            }

            $document_number = $numberPatternData['document_number'] ?? $request->document_number;
            $erpScrap = ErpScrap::where('book_id', $request->book_id)
                ->where('document_number', $document_number)
                ->first();

            /* check added */
            if ($erpScrap) {
                return response()->json(['message' => ConstantHelper::DUPLICATE_DOCUMENT_NUMBER, 'error' => ''], 422);
            }

            if (!$erpScrap) {
                $erpScrap = new ErpScrap([
                    'organization_id' => $organization->id,
                    'group_id' => $organization->group_id,
                    'company_id' => $organization->company_id,
                    'user_id' => $request->user_id,
                    'book_id' => $request->book_id,
                    'book_code' => $request->book_code,
                    'store_id' => $request->store_id,
                    'sub_store_id' => $request->sub_store_id,
                    'remarks' => $request->document_remarks,
                    'document_date' => $request->document_date,
                    'reference_type' => $request->reference_type,
                ]);
            }

            $erpScrap->fill([
                'doc_number_type' => $numberPatternData['type'],
                'doc_reset_pattern' => $numberPatternData['reset_pattern'],
                'doc_prefix' => $numberPatternData['prefix'],
                'doc_suffix' => $numberPatternData['suffix'],
                'doc_no' => $numberPatternData['doc_no'],
                'document_number' => $document_number,
            ]);

            $erpScrap->save();

            /** ------------------------------
             * Save Components
             * ------------------------------ */
            $components = $request->input('components', []);
            if (empty($components)) {
                DB::rollBack();
                return response()->json(['message' => 'Please add at least one row in component table.', 'error' => ''], 422);
            }

            $totalScrapQty = 0;
            $totalScrapCost = 0;
            foreach ($components as $component) {
                $item = Item::find($component['item_id'] ?? null);
                $unit = Unit::find($component['uom_id'] ?? null);

                $qty = floatval($component['qty'] ?? 0);
                $rate = floatval($component['rate'] ?? 0);
                $totalCost = floatval($component['total_cost'] ?? 0) ?? ($qty * $rate);

                $totalScrapQty += $qty;
                $totalScrapCost += $totalCost;

                $erpScrapItem = new ErpScrapItem([
                    'erp_scrap_id' => $erpScrap->id,
                    'item_id' => $component['item_id'],
                    'item_code' => $component['item_code'],
                    'item_name' => $component['item_name'],
                    'hsn_id' => $component['hsn_id'],
                    'hsn_code' => $component['hsn_code'],
                    'uom_id' => $component['uom_id'],
                    'uom_code' => $unit?->name,
                    'qty' => $qty,
                    'rate' => $rate,
                    'total_cost' => $totalCost,
                    'cost_center_name' => $component['cost_center'],
                    'cost_center_id' => $component['cost_center_id'],
                    'remarks' => $component['remark'],
                ]);

                $erpScrapItem->inventory_uom_code = $item?->uom?->name;
                if ($component['uom_id'] == $item?->uom_id) {
                    $erpScrapItem->inventory_uom_id = $component['uom_id'];
                    $erpScrapItem->inventory_uom_code = $component['uom_code'] ?? $item?->uom?->name;
                    $erpScrapItem->inventory_uom_qty = $component['qty'] ?? 0;
                } else {
                    $alUom = $item?->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                    $erpScrapItem->inventory_uom_id = $component['uom_id'];
                    $erpScrapItem->inventory_uom_code = $component['uom_code'] ?? $unit?->name;
                    $erpScrapItem->inventory_uom_qty = ($component['qty'] ?? 0) * ($alUom?->conversion_to_inventory ?? 1);
                }

                $erpScrapItem->save();

                if (isset($component['attr_group_id']) && is_array($component['attr_group_id'])) {
                    foreach ($component['attr_group_id'] as $key => $value) {

                        $itemAttribute = $item?->itemAttributes()->where('attribute_group_id', $key)->first();
                        if (!$itemAttribute) continue;

                        $scrapAttr = new ErpScrapItemAttribute;
                        $scrapAttrName = $value['attr_name'];
                        $scrapAttr->erp_scrap_id = $erpScrap->id;
                        $scrapAttr->scrap_item_id = $erpScrapItem->id;
                        $scrapAttr->attribute_group_id = $itemAttribute->attribute_group_id;
                        $scrapAttr->item_attribute_id = $itemAttribute->id;
                        $scrapAttr->item_code = $component['item_code'] ?? null;
                        $scrapAttr->attribute_name = $key;
                        $scrapAttr->attribute_value = $scrapAttrName ?? null;
                        $scrapAttr->save();
                    }
                }
            }

            /** ------------------------------
             * Document Status & Workflow
             * ------------------------------ */
            if ($request->document_status == ConstantHelper::SUBMITTED) {
                $approveDocument = Helper::approveDocument($erpScrap->book_id, $erpScrap->id, $erpScrap->revision_number ?? 0, $erpScrap->remarks, $request->file('attachment'), $erpScrap->approval_level ?? 1, 'submit', 0, get_class($erpScrap));

                $erpScrap->document_status = $approveDocument['approvalStatus'] ?? $erpScrap->document_status;
            } else {
                $erpScrap->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            }

            $erpScrap->total_qty = $totalScrapQty;
            $erpScrap->total_cost = $totalScrapCost;


            /*Store currency data*/
            $currencyExchangeData = CurrencyHelper::getCurrencyExchangeRates($organization->currency->id, $erpScrap->document_date);

            $erpScrap->org_currency_id = $currencyExchangeData['data']['org_currency_id'];
            $erpScrap->org_currency_code = $currencyExchangeData['data']['org_currency_code'];
            $erpScrap->org_currency_exg_rate = $currencyExchangeData['data']['org_currency_exg_rate'];
            $erpScrap->comp_currency_id = $currencyExchangeData['data']['comp_currency_id'];
            $erpScrap->comp_currency_code = $currencyExchangeData['data']['comp_currency_code'];
            $erpScrap->comp_currency_exg_rate = $currencyExchangeData['data']['comp_currency_exg_rate'];
            $erpScrap->group_currency_id = $currencyExchangeData['data']['group_currency_id'];
            $erpScrap->group_currency_code = $currencyExchangeData['data']['group_currency_code'];
            $erpScrap->group_currency_exg_rate = $currencyExchangeData['data']['group_currency_exg_rate'];

            $erpScrap->save();
            $erpScrap->refresh();

            if ($erpScrap) {
                $data = self::maintainStockLedger($erpScrap);
                if ($data['status'] === 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $data['message'],
                        'error'   => 'ERR_maintainStockLedger'
                    ], 422);
                }
            }

            /** ------------------------------
             * Reference Type Mappings
             * ------------------------------ */
            if ($request->reference_type) {
                switch ($request->reference_type) {
                    case 'pslip':
                        $psItemIds = json_decode($request->input('ps_item_ids', '[]'), true);
                        if (!empty($psItemIds) && is_array($psItemIds)) {
                            foreach ($psItemIds as $psItemId) {
                                $psItem = ErpPslipItem::find($psItemId);
                                if ($psItem) {
                                    ErpScrapPslipItemMapping::updateOrCreate(
                                        [
                                            'erp_scrap_id'      => $erpScrap->id,
                                            'erp_pslip_item_id' => $psItem->id,
                                        ],
                                        [
                                            'group_id'          => $erpScrap->group_id,
                                            'company_id'        => $erpScrap->company_id,
                                            'organization_id'   => $erpScrap->organization_id,
                                            'erp_pslip_id'      => $psItem->pslip_id,
                                            'erp_scrap_item_id' => null,
                                            'rejected_qty'      => $psItem->rejected_qty ?? 0,
                                        ]
                                    );
                                }
                            }
                        }
                        break;

                    case 'repairOrder':
                        break;
                    default:
                        break;
                }
            }

            /** ------------------------------
             * Attachments
             * ------------------------------ */
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $singleFile) {
                    $erpScrap->uploadDocuments($singleFile, 'scrap', false);
                }
            }

            $redirectUrl = route('scrap.index');

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
                'data' => $erpScrap,
                'redirect_url' => $redirectUrl,
            ]);
        } catch (\Throwable $ex) {

            DB::rollBack();
            return response()->json([
                'message' => $ex->getMessage(),
                'line' => $ex->getLine(),
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    public function update(ScrapRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $erpScrap = ErpScrap::findOrFail($id);
            $organization = OrganizationHelper::getAuthenticatedOrganization();
            $currentStatus = $erpScrap->document_status;
            $actionType = $request->action_type;

            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'Scrap\\ErpScrap', 'relation_column' => ''],
                    ['model_type' => 'detail', 'model_name' => 'Scrap\\ErpScrapItem', 'relation_column' => 'erp_scrap_id'],
                    ['model_type' => 'detail', 'model_name' => 'Scrap\\ErpScrapDynamicField', 'relation_column' => 'header_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'Scrap\\ErpScrapItemAttribute', 'relation_column' => 'scrap_item_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'Scrap\\ErpScrapPslipItemMapping', 'relation_column' => 'scrap_item_id'],
                ];

                if (!Helper::documentAmendment($revisionData, $erpScrap->id)) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error occurred while sending amendment request for approval.',
                        'error' => '',
                    ], 500);
                }
            }

            $deletedData = [
                'deletedPsItemIds' => json_decode($request->input('deleted_ps_item_ids', '[]'), true),
                'deletedErpScrapItemIds' => json_decode($request->input('deleted_scrap_item_ids', '[]'), true),
                'deletedAttachmentIds' => json_decode($request->input('deleted_attachment_ids', '[]'), true),
            ];

            if (!empty($deletedData['deletedPsItemIds'])) {
                $erpScrapDeleteService = new ScrapDeleteService();
                $response = $erpScrapDeleteService->removePsMapping($deletedData['deletedPsItemIds'], $erpScrap);
                if (isset($response['error'])) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error occurred while updating the record.',
                        'error' => $response['error'],
                    ], 500);
                }
            }

            if (!empty($deletedData['deletedAttachmentIds'])) {
                $erpScrapDeleteService = new ScrapDeleteService();
                $response = $erpScrapDeleteService->deleteAttachments($deletedData['deletedAttachmentIds'], $erpScrap);
                if ($response['status'] == 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $response['message'],
                        'error' => $response['status'],
                    ], 500);
                }
            }

            if (!empty($deletedData['deletedErpScrapItemIds'])) {
                $erpScrapDeleteService = new ScrapDeleteService();
                $response = $erpScrapDeleteService->deleteScrapItems($deletedData['deletedErpScrapItemIds'], $erpScrap);

                if ($response['status'] == 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $response['message'],
                        'error' => $response['status'],
                    ], 500);
                }
            }

            // Update header
            $erpScrap->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            $erpScrap->reference_type = $request->reference_type ?? $erpScrap->reference_type;
            $erpScrap->book_id = $request->book_id ?? $erpScrap->book_id;
            $erpScrap->book_code = $request->book_code ?? $erpScrap->book_code;
            $erpScrap->store_id = $request->store_id ?? $erpScrap->store_id;
            $erpScrap->sub_store_id = $request->sub_store_id ?? $erpScrap->sub_store_id;
            $erpScrap->remarks = $request->document_remarks ?? $erpScrap->document_remarks;
            $erpScrap->document_date = $request->document_date ?? $erpScrap->document_date;
            $erpScrap->reference_type = $request->reference_type ?? $erpScrap->reference_type;
            $erpScrap->save();

            $totalScrapQty = 0;
            $totalScrapCost = 0;

            // Update components
            $components = $request->input('components', []);
            foreach ($components as $component) {
                $item = Item::find($component['item_id'] ?? null);
                $unit = Unit::find($component['uom_id']);
                $erpScrapItem = ErpScrapItem::find($component['scrap_item_id'] ?? null) ?? new ErpScrapItem;

                $qty = floatval($component['qty'] ?? 0);
                $rate = floatval($component['rate'] ?? 0);
                $totalCost = floatval($component['total_cost'] ?? 0) ?? ($qty * $rate);

                $totalScrapQty += $qty;
                $totalScrapCost += $totalCost;

                $erpScrapItem->fill([
                    'erp_scrap_id' => $erpScrap->id,
                    'item_id' => $component['item_id'],
                    'item_code' => $component['item_code'],
                    'item_name' => $component['item_name'],
                    'hsn_id' => $component['hsn_id'],
                    'hsn_code' => $component['hsn_code'],
                    'uom_id' => $component['uom_id'],
                    'uom_code' => $unit?->name,
                    'qty' => $qty,
                    'rate' => $rate,
                    'total_cost' => $totalCost,
                    'cost_center_name' => $component['cost_center'],
                    'cost_center_id' => $component['cost_center_id'],
                    'remarks' => $component['remark'],
                ]);

                $erpScrapItem->inventory_uom_code = $item?->uom?->name;
                if ($component['uom_id'] == $item?->uom_id) {
                    $erpScrapItem->inventory_uom_id = $component['uom_id'];
                    $erpScrapItem->inventory_uom_code = $component['uom_code'] ?? $item?->uom?->name;
                    $erpScrapItem->inventory_uom_qty = $qty ?? 0;
                } else {
                    $alUom = $item?->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                    $erpScrapItem->inventory_uom_id = $component['uom_id'];
                    $erpScrapItem->inventory_uom_code = $component['uom_code'] ?? $unit?->name;
                    $erpScrapItem->inventory_uom_qty = ($qty ?? 0) * ($alUom?->conversion_to_inventory ?? 1);
                }

                $erpScrapItem->save();

                if (isset($component['attr_group_id']) && is_array($component['attr_group_id'])) {
                    foreach ($component['attr_group_id'] as $key => $value) {
                        $itemAttribute = $item?->itemAttributes()->where('attribute_group_id', $key)->first();
                        if (!$itemAttribute) continue;

                        $scrapAttr = ErpScrapItemAttribute::firstOrNew([
                            'erp_scrap_id' => $erpScrap->id,
                            'scrap_item_id' => $erpScrapItem->id,
                            'item_attribute_id' => $itemAttribute->id,
                            'attribute_group_id' => $itemAttribute->attribute_group_id,
                        ]);

                        $scrapAttrName = $value['attr_name'];
                        $scrapAttr->item_code = $component['item_code'] ?? null;
                        $scrapAttr->attribute_name = $key;
                        $scrapAttr->attribute_value = $scrapAttrName ?? null;
                        $scrapAttr->save();
                    }
                }
            }

            $erpScrap->total_qty = $totalScrapQty;
            $erpScrap->total_cost = $totalScrapCost;

            /*Store currency data*/
            $currencyExchangeData = CurrencyHelper::getCurrencyExchangeRates($organization->currency->id, $erpScrap->document_date);

            $erpScrap->org_currency_id = $currencyExchangeData['data']['org_currency_id'];
            $erpScrap->org_currency_code = $currencyExchangeData['data']['org_currency_code'];
            $erpScrap->org_currency_exg_rate = $currencyExchangeData['data']['org_currency_exg_rate'];
            $erpScrap->comp_currency_id = $currencyExchangeData['data']['comp_currency_id'];
            $erpScrap->comp_currency_code = $currencyExchangeData['data']['comp_currency_code'];
            $erpScrap->comp_currency_exg_rate = $currencyExchangeData['data']['comp_currency_exg_rate'];
            $erpScrap->group_currency_id = $currencyExchangeData['data']['group_currency_id'];
            $erpScrap->group_currency_code = $currencyExchangeData['data']['group_currency_code'];
            $erpScrap->group_currency_exg_rate = $currencyExchangeData['data']['group_currency_exg_rate'];

            $erpScrap->save();
            $erpScrap->refresh();

            /** ------------------------------
             * Reference Type Mappings
             * ------------------------------ */
            if ($request->reference_type) {
                switch ($request->reference_type) {
                    case 'pslip':
                        $psItemIds = json_decode($request->input('ps_item_ids', '[]'), true);
                        if (!empty($psItemIds) && is_array($psItemIds)) {
                            foreach ($psItemIds as $psItemId) {
                                $psItem = ErpPslipItem::find($psItemId);
                                if ($psItem) {
                                    ErpScrapPslipItemMapping::updateOrCreate(
                                        [
                                            'erp_scrap_id'      => $erpScrap->id,
                                            'erp_pslip_item_id' => $psItem->id,
                                        ],
                                        [
                                            'group_id'          => $erpScrap->group_id,
                                            'company_id'        => $erpScrap->company_id,
                                            'organization_id'   => $erpScrap->organization_id,
                                            'erp_pslip_id'      => $psItem->pslip_id,
                                            'erp_scrap_item_id' => null,
                                            'rejected_qty'      => $psItem->rejected_qty ?? 0,
                                        ]
                                    );
                                }
                            }
                        }
                        break;

                    case 'repairOrder':
                        break;
                    default:
                        break;
                }
            }

            /*Create document submit log*/
            $bookId = $erpScrap->book_id;
            $docId = $erpScrap->id;
            $amendRemarks = $request->amend_remarks ?? null;
            $remarks = $erpScrap->remarks;
            $amendAttachments = $request->file('amend_attachment');
            $attachments = $request->file('attachment');
            $currentLevel = $erpScrap->approval_level;
            $modelName = get_class($erpScrap);
            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                //*amendmemnt document log*/
                $revisionNumber = $erpScrap->revision_number + 1;
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $amendRemarks, $amendAttachments, $currentLevel, $actionType, 0, $modelName);
                $erpScrap->revision_number = $revisionNumber;
                $erpScrap->approval_level = 1;
                $erpScrap->revision_date = now();
                $amendAfterStatus = $approveDocument['approvalStatus'] ?? $erpScrap->document_status;
                $erpScrap->document_status = $amendAfterStatus;
            } else {
                if ($request->document_status == ConstantHelper::SUBMITTED) {
                    $modelName = get_class($erpScrap);
                    $bookId = $erpScrap->book_id;
                    $docId = $erpScrap->id;
                    $remarks = $erpScrap->remarks;
                    $attachments = $request->file('attachment');
                    $currentLevel = $erpScrap->approval_level;
                    $revisionNumber = $erpScrap->revision_number ?? 0;
                    $actionType = 'submit'; // Approve // reject // submit
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, 0, $modelName);
                    $erpScrap->document_status = $approveDocument['approvalStatus'] ?? $erpScrap->document_status;
                } else {
                    $erpScrap->document_status = $request->document_status ?? ConstantHelper::DRAFT;
                }
            }

            $erpScrap->save();

            if ($erpScrap) {
                $data = self::maintainStockLedger($erpScrap);
                if ($data['status'] === 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $data['message'],
                        'error'   => 'ERR_maintainStockLedger'
                    ], 422);
                }
            }

            /** ------------------------------
             * Attachments
             * ------------------------------ */
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $singleFile) {
                    $erpScrap->uploadDocuments($singleFile, 'scrap', false);
                }
            }

            $redirectUrl = '';
            if ($erpScrap->document_status == ConstantHelper::APPROVED) {
                $redirectUrl = route('scrap.generate-pdf', $erpScrap->id);
            }

            DB::commit();
            return response()->json([
                'message' => 'Record updated successfully',
                'data' => $erpScrap,
                'redirect_url' => $redirectUrl
            ]);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'message' => $ex->getMessage(),
                'line' => $ex->getLine(),
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }

    public function updateApprove(ScrapRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $scrap = ErpScrap::findOrFail($id);
            $actionType = $request->action_type;

            // Update qty/uom for components
            foreach ($request->input('components', []) as $component) {
                $item = Item::find($component['item_id'] ?? null);
                $unit = Unit::find($component['uom_id']);
                $erpScrapItem = ErpScrapItem::find($component['scrap_item_id'] ?? null);

                if ($erpScrapItem) {
                    $erpScrapItem->qty = $component['qty'] ?? 0.00;

                    if ($component['uom_id'] == $item?->uom_id) {
                        $erpScrapItem->inventory_uom_qty = $component['qty'];
                    } else {
                        $alUom = $item?->alternateUOMs()->where('uom_id', $component['uom_id'])->first();
                        $erpScrapItem->inventory_uom_qty = floatval($component['qty']) * ($alUom?->conversion_to_inventory ?? 1);
                    }

                    $erpScrapItem->save();
                }
            }

            // Workflow approval
            $approveDocument = Helper::approveDocument(
                $scrap->book_id,
                $scrap->id,
                $scrap->revision_number ?? 0,
                $request->remarks,
                $request->file('attachment'),
                $scrap->approval_level,
                $actionType,
                0,
                get_class($scrap)
            );

            $scrap->approval_level = $approveDocument['nextLevel'];
            $scrap->document_status = $approveDocument['approvalStatus'];
            $scrap->save();

            DB::commit();
            return response()->json([
                'message' => 'Record approved successfully',
                'data' => $scrap,
                'redirect_url' => ''
            ]);
        } catch (\Throwable $ex) {
            DB::rollBack();
            return response()->json([
                'message' => $ex->getMessage(),
                'line' => $ex->getLine(),
                'error' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ], 500);
        }
    }


    public function generatePdf(Request $request, $id)
    {
        $user = Helper::getAuthenticatedUser();
        $scrap = ErpScrap::with(['items', 'book'])->findOrFail($id);
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $organizationAddress = Address::with(['city', 'state', 'country'])
            ->where('addressable_id', $organization->id)
            ->where('addressable_type', Organization::class)
            ->first();

        $imagePath = public_path('assets/css/midc-logo.jpg');
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$scrap->document_status] ?? '';

        $pdf = \PDF::loadView(
            'pdf.scrap',
            [
                'user' => $user,
                'scrap' => $scrap,
                'imagePath' => $imagePath,
                'organization' => $organization,
                'docStatusClass' => $docStatusClass,
                'organizationAddress' => $organizationAddress,
            ]
        );
        return $pdf->stream('Scrap-' . date('Y-m-d') . '.pdf');
    }

    public function report(Request $request)
    {
        $erpScraps = ErpScrapItem::with([
            'scrap.book',
            'scrap.store',
            'scrap.subStore',
            'item',
            'costCenter',
            'uom',
            'attributes',
        ])
            ->whereHas('scrap', function ($headerQuery) use ($request) {
                $headerQuery->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id));
                $headerQuery->when(
                    $request->document_number,
                    fn($q) =>
                    $q->where('document_number', 'LIKE', "%{$request->document_number}%")
                );
                $headerQuery->when($request->location_id, fn($q) => $q->where('cost_center_id', $request->cost_center_id));
                $headerQuery->when($request->location_id, fn($q) => $q->where('store_id', $request->location_id));
                $headerQuery->when($request->company_id, fn($q) => $q->where('company_id', $request->company_id));
                $headerQuery->when($request->organization_id, fn($q) => $q->where('organization_id', $request->organization_id));
                $headerQuery->when($request->doc_status, function ($q) use ($request) {
                    $status = match ($request->doc_status) {
                        ConstantHelper::DRAFT => [ConstantHelper::DRAFT],
                        ConstantHelper::SUBMITTED => [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED],
                        default => [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED],
                    };
                    $q->whereIn('document_status', $status);
                });
                $dateRange = $request->date_range ?? now()->startOfMonth()->format('Y-m-d') . " to " . now()->endOfMonth()->format('Y-m-d');
                $headerQuery->when($dateRange, function ($q) use ($dateRange) {
                    $dates = explode('to', $dateRange);
                    $from = trim($dates[0]);
                    $to = trim($dates[1] ?? $dates[0]);
                    $q->whereBetween('document_date', [
                        Carbon::parse($from)->format('Y-m-d'),
                        Carbon::parse($to)->format('Y-m-d')
                    ]);
                });
                $headerQuery->when($request->item_id, function ($query) use ($request) {
                    $query->where('item_id', $request->item_id)
                        ->when($request->item_category_id, function ($catQuery) use ($request) {
                            $catQuery->whereHas(
                                'item',
                                fn($item) => $item->where('category_id', $request->item_category_id)
                                    ->when(
                                        $request->item_sub_category_id,
                                        fn($subCat) =>
                                        $subCat->where('subcategory_id', $request->item_sub_category_id)
                                    )
                            );
                        });
                });
            })
            ->orderByDesc('id');

        $dynamicFields = DynamicFieldHelper::getServiceDynamicFields(ConstantHelper::SCRAP_SERVICE_ALIAS);

        $datatables = DataTables::of($erpScraps)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                $status = $row->scrap->document_status ?? ConstantHelper::DRAFT;
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$status];
                $editUrl = route('scrap.edit', ['id' => $row->scrap->id, 'type' => request()->route('type')]);
                return "
                <div class='text-center'>
                    <span class='badge rounded-pill {$statusClass}'>{$status}</span>
                    <a href='{$editUrl}' class='ms-2'><i class='cursor-pointer' data-feather='eye'></i></a>
                </div>
            ";
            })
            ->addColumn('book_name', fn($row) => $row?->scrap?->book?->book_code)
            ->addColumn('document_number', fn($row) => $row?->scrap?->document_number)
            ->addColumn('document_date', fn($row) => $row?->scrap?->document_date)
            ->addColumn('store_name', fn($row) => $row?->scrap?->store?->store_name)
            ->addColumn('sub_store_name', fn($row) => $row?->scrap?->sub_store?->name)
            ->addColumn('item_name', fn($row) => $row?->item?->item_name)
            ->addColumn('item_code', fn($row) => $row?->item?->item_code)
            ->addColumn('uom_name', fn($row) => $row?->uom?->name)
            ->addColumn('qty', fn($row) => number_format($row->qty, 2))
            ->addColumn('item_attributes', function ($row) {
                if ($row->attributes->isEmpty()) {
                    return 'N/A';
                }
                return $row->attributes->map(function ($attr) {
                    $name = optional(AttributeGroup::find($attr->attribute_name))->name ?? '';
                    $value = optional(Attribute::find($attr->attribute_value))->value ?? '';
                    return "<span class='badge rounded-pill badge-light-primary'>{$name}: {$value}</span>";
                })->implode(' ');
            });

        foreach ($dynamicFields as $field) {
            $datatables->addColumn($field->name, function ($row) use ($field) {
                return collect($row->scrap?->dynamic_fields)
                    ->firstWhere('name', $field->name)?->value ?? '';
            });
        }


        return $datatables->rawColumns(['item_attributes', 'status'])->make(true);
    }

    public function getItemDetail(Request $request)
    {
        $tab = $request->tab ?? null;
        $itemId = $request->item_id;
        $storeId = $request->store_id;
        $subStoreId = $request->sub_store_id;
        $selectedAttr = json_decode($request->selectedAttr, true) ?? [];

        $item = Item::find($itemId);
        if (! $item) {
            return response()->json([
                'status' => 404,
                'message' => 'Item not found',
                'data' => [],
            ]);
        }

        /* Attribute handling */
        $attributeName = [];
        $attributeValue = [];
        foreach ($item->itemAttributes as $attribute) {
            $attributeGroupId = $attribute->attribute_group_id ?? null;
            $attributeIds = $attribute->attribute_id ?? [];

            if (! is_array($attributeIds)) {
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

        /* Qty & UOM */
        $uomId = $request->uom_id ?? null;
        $qty = floatval($request->qty) ?? 0;
        $uomName = $item->uom->name ?? 'NA';

        if ($item->uom_id != $uomId && $uomId) {
            $alUom = $item->alternateUOMs()->where('uom_id', $uomId)->first();
            $qty = $alUom?->conversion_to_inventory * $qty;
        }

        /* Extra Data */
        $remark = $request->remark ?? null;
        $specifications = $item->specifications()->whereNotNull('value')->get();
        $scrapItemIds = $request->scrap_item_id ? [$request->scrap_item_id] : [];
        $inventoryStock = InventoryHelper::totalInventoryAndStock($item->id, $selectedAttr, $item?->uom_id, $storeId, $subStoreId);

        switch ($tab) {
            case 'scavenging':
                $view = 'remanufacturing.scrap.partials.comp-item-detail';
                break;
            case 'repairOrder':
                $view = 'remanufacturing.scrap.partials.comp-item-detail';
                break;
            case 'productionSlip':
                $view = 'remanufacturing.scrap.partials.comp-item-detail';
                break;
            default:
                $view = 'remanufacturing.scrap.partials.comp-item-detail';
                break;
        }

        $html = view($view, compact(
            'item',
            'selectedAttr',
            'remark',
            'uomName',
            'qty',
            'specifications',
            'inventoryStock',
            'itemId',
            'storeId',
            'subStoreId',
            'attributes'
        ))->render();

        return response()->json([
            'data' => [
                'html' => $html,
                'inventoryStock' => $inventoryStock,
            ],
            'status' => 200,
            'message' => 'Fetched successfully',
        ]);
    }

    public function getPs(Request $request)
    {
        $query = $this->buildPsQuery($request);

        return DataTables::of($query)
            ->addColumn('select_checkbox', function ($row) {
                return '<div class="form-check form-check-inline me-0">
                            <input class="form-check-input ps_item_checkbox"
                                type="checkbox"
                                name="ps_item_check[]"

                                data-item-id="' . e($row?->item_id) . '"
                                value="' . e($row?->id) . '">
                        </div>';
            })
            ->addColumn('book_name', fn($row) => $row?->pslip?->book?->book_code ?? '')
            ->addColumn('doc_no', fn($row) => $row?->pslip?->document_number ?? '')
            ->addColumn('doc_date', fn($row) => $row?->pslip?->getFormattedDate('document_date') ?? '')
            ->addColumn('item_name', fn($row) => $row?->item?->item_name ?? '')
            ->addColumn('item_code', fn($row) => $row?->item?->item_code ?? '')
            ->addColumn('attributes', fn($row) => app(\App\View\Components\Po\Attribute::class, ['row' => $row])->resolveView()->render())
            ->addColumn('uom', fn($row) => $row?->uom?->name ?? '')
            ->addColumn('rejected_qty', fn($row) => number_format($row?->rejected_qty ?? 0, 2))
            ->addColumn('remarks', fn($row) => $row?->remarks ?? '')
            ->rawColumns([
                'select_checkbox',
                'attributes',
            ])
            ->make(true);
    }

    protected function buildPsQuery(Request $request)
    {
        $storeId = $request->store_id ?? null;
        $pslipId = $request->pslip_id ?? null;
        $seriesId = $request->series_id ?? null;
        $itemSearch = $request->item_search ?? null;
        $subStoreId = $request->sub_store_id ?? null;
        $headerBookId = $request->header_book_id ?? null;
        $ps_item_ids = json_decode($request->ps_item_ids, true) ?? [];
        $selected_ps_item_ids = json_decode($request->selected_ps_item_ids, true) ?? [];
        $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($headerBookId);

        $ErpPslipItem = ErpPslipItem::where(function ($query) use ($applicableBookIds, $selected_ps_item_ids, $ps_item_ids, $itemSearch, $storeId, $subStoreId, $pslipId) {
            if (count($selected_ps_item_ids)) {
                $query->whereNotIn('id', $selected_ps_item_ids);
            }

            if (count($ps_item_ids)) {
                $query->whereIn('id', $ps_item_ids);
            }

            $query->whereHas('pslip', function ($pslip) use ($applicableBookIds, $storeId, $subStoreId, $pslipId) {

                $pslip->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED]);

                // if ($seriesId) {
                //     $pslip->where('book_id', $seriesId);
                // }

                if (count($applicableBookIds)) {
                    $pslip->whereIn('book_id', $applicableBookIds);
                }
                if ($storeId) {
                    $pslip->where('store_id', $storeId);
                }
                if ($subStoreId) {
                    $pslip->where('rg_sub_store_id', $subStoreId);
                }
                if ($pslipId) {
                    $pslip->where('id', $pslipId);
                }
            });

            if ($itemSearch) {
                $query->whereHas('item', function ($query) use ($itemSearch) {
                    $query->searchByKeywords($itemSearch);
                });
            }

            $query->whereNull('erp_scrap_id');
            $query->whereRaw('rejected_qty > 0');
        });

        return $ErpPslipItem;
    }

    // Add item row
    public function addItemRow(Request $request)
    {
        $item = json_decode($request->item, true) ?? [];
        $componentItem = json_decode($request->component_item, true) ?? [];

        /* Check last tr in table mandatory */
        if (isset($componentItem['attr_require']) && isset($componentItem['item_id']) && isset($componentItem['count'])) {
            if (($componentItem['attr_require'] == true || ! $componentItem['item_id']) && $componentItem['count'] != 0) {
                return response()->json(['data' => ['html' => ''], 'status' => 422, 'message' => 'Please fill all component details before adding new row more!']);
            }
        }

        $rowCount = intval($request->count);
        $html = view('remanufacturing.scrap.partials.item-row', compact('rowCount'))->render();

        return response()->json(['data' => ['html' => $html], 'status' => 200, 'message' => 'fetched.']);
    }

    // On change item attribute
    public function getItemAttribute(Request $request)
    {
        $hiddenHtml = '';
        $itemAttIds = [];
        $itemAttributeArray = [];
        $mode = $request->mode ?? 'edit';
        $item = Item::find($request->item_id);
        $rowCount = intval($request->rowCount) ?? 1;
        $scrapItemId = $request->scrap_item_id ?? null;
        $requestHeader = $request->type ? $request->type . '_components' : 'components';
        $selectedAttr = $request->selectedAttr ? json_decode($request->selectedAttr, true) : [];
        $addHiddenInputs = $request->hidden_inputs && $request->hidden_inputs == true ? true : false;

        if ($scrapItemId) {
            $scrapItem = ErpPslipItem::where('id', $scrapItemId)->where('item_id', $item->id ?? null)->first();
            if ($scrapItem) {
                $itemAttIds = $scrapItem->attributes()->pluck('item_attribute_id')->toArray();
                $itemAttributeArray = $scrapItem->item_attributes_array();
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

        $html = view('remanufacturing.scrap.partials.comp-attribute', compact('item', 'rowCount', 'selectedAttr', 'mode', 'requestHeader', 'itemAttributes'))->render();

        foreach ($itemAttributes as $attribute) {
            $selected = '';
            foreach ($attribute->attributes() as $value) {
                if (in_array($value->id, $selectedAttr)) {
                    $selected = $value->id;
                }
            }
            $hiddenHtml .= "<input type='hidden' name='" . $requestHeader . "[$rowCount][attr_group_id][$attribute->attribute_group_id][attr_name]' value=$selected>";
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

    public function processItem(Request $request)
    {
        $html = '';
        $type = $request->type ?? null;
        $ids = json_decode($request->ids, true) ?? [];

        if ($type == 'pslip') {
            $items = ErpPslipItem::whereIn('id', $ids)->get();
        }

        $current_row_count = intval($request->current_row_count);

        foreach ($items as $item) {
            $html .= view('remanufacturing.scrap.partials.pull-items', [
                'type' => $type,
                'item' => $item,
                'rowCount' => $current_row_count++,
            ])->render();
        }

        return response()->json([
            'data' => ['pos' => $html],
            'status' => 200,
            'message' => 'fetched!'
        ]);
    }

    public function destroy($erpScrapId, $isAmedment = false)
    {
        $erpScrap = ErpScrap::find($erpScrapId);
        if (!$erpScrap) {
            return response()->json(['status' => false, 'message' => 'Production Slip not found.'], 404);
        }

        if (!$isAmedment && $erpScrap->document_status !== ConstantHelper::DRAFT) {
            return response()->json(['status' => false, 'message' => 'Only draft documents can be deleted.'], 422);
        }

        \DB::beginTransaction();
        try {

            $erpScrapDeleteService = new ScrapDeleteService();
            $response = $erpScrapDeleteService->deleteScrapHeader($erpScrap);

            if ($response['status'] === 'error') {
                \DB::rollBack();
                return response()->json(['status' => false, 'message' => $response['message']], 422);
            }

            \DB::commit();
            return response()->json(['status' => true, 'message' => 'Document deleted successfully.'], 200);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Error deleting Document: ' . $e->getTraceAsString(),
            ], 500);
        }
    }

    public function cancel(Request $request, $erpScrapId)
    {
        $erpScrap = ErpScrap::find($erpScrapId);
        if (!$erpScrap) {
            return response()->json(['status' => false, 'message' => 'Document not found.'], 404);
        }

        \DB::beginTransaction();
        try {

            $erpScrapDeleteService = new ScrapDeleteService();
            $response = $erpScrapDeleteService->cancelScrapHeader($erpScrap, $request->remark);

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
            $erpScrap = ErpScrap::find($request->id);
            if ($erpScrap) {
                $revoke = Helper::approveDocument($erpScrap->book_id, $erpScrap->id, $erpScrap->revision_number, '', [], 0, ConstantHelper::REVOKE, $erpScrap->grand_total_amount, get_class($erpScrap));
                if ($revoke['message']) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => $revoke['message'],
                    ]);
                } else {
                    $erpScrap->document_status = $revoke['approvalStatus'];
                    $erpScrap->save();
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
                'message' => $ex->getMessage() . ' at ' . $ex->getLine() . ' in ' . $ex->getFile(),
            ]);
        }
    }

    // Maintain Stock Ledger
    private static function maintainStockLedger($scrap)
    {
        $detailIds = $scrap->items->pluck('id')->toArray();
        $data = InventoryHelper::settlementOfInventoryAndStock($scrap->id, $detailIds, ConstantHelper::SCRAP_SERVICE_ALIAS, $scrap->document_status, 'receipt');
        return $data;
    }
}

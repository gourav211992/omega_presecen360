<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiGenericException;
use App\Helpers\Common\OrganizationHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\DynamicFieldHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\EInvoiceHelper;
use App\Helpers\FinancialPostingHelper;
use App\Helpers\Helper;
use App\Helpers\InventoryHelper;
use App\Helpers\ItemHelper;
use App\Helpers\MasterIndiaHelper;
use App\Helpers\NumberHelper;
use App\Helpers\SaleModuleHelper;
use App\Models\Customer;
use App\Models\ErpSaleInvoice;
use App\Services\Common\FinancialYearService;
use App\Helpers\ServiceParametersHelper;
use App\Helpers\TaxHelper;
use App\Helpers\TransactionReportHelper;
use App\Http\Requests\ErpSaleReturnRequest;
use App\Jobs\SendEmailJob;
use App\Models\Country;
use App\Models\Address;
use App\Models\ErpAddress;
use App\Models\ErpInvoiceItem;
use App\Models\ErpRack;
use App\Models\ErpSaleReturn;
use App\Models\ErpSrDynamicField;
use App\Models\ErpSaleReturnItem;
use App\Models\ErpSaleReturnItemLocation;
use App\Models\ErpSaleReturnItemAttribute;
use App\Models\ErpSaleReturnHistory;
use App\Models\ErpSaleReturnTed;
use App\Models\EwayBillMaster;
use App\Models\ErpSrItemLotDetail;
use App\Models\ErpSrMedia;
use App\Models\Item;
use App\Models\ErpSoItem;
use App\Models\ErpStore;
use App\Models\Organization;
use Carbon\Carbon;
use DB;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use PDF;
use Exception;
use Illuminate\Http\Request;
use Storage;
use Validator;
use Yajra\DataTables\DataTables;

use stdClass;

class ErpSaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $pathUrl = request()->segments()[0];
        $orderType = SaleModuleHelper::SALES_RETURN_DEFAULT_TYPE;
        $redirectUrl = route('sale.return.index');
        $createRoute = route('sale.return.create');
        $user = request()->user();
        request()->merge(['type' => $orderType]);
        $typeName = SaleModuleHelper::getAndReturnReturnTypeName($orderType);
        $accessible_locations = InventoryHelper::getAccessibleLocations()->pluck('id')->toArray();
        $parentURL = request() -> segments()[0];
        $selectedfyYear = app(FinancialYearService::class)->getFinancialYear(date('Y-m-d'), $user);
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL);
        $create_button = (isset($servicesBooks['services'])  && count($servicesBooks['services']) > 0 && isset($selectedfyYear['authorized']) && $selectedfyYear['authorized'] && !$selectedfyYear['lock_fy']) ? true : false;
        //Date Filters
        $dateRange = $request -> date_range ??  null;
        if ($request -> ajax()) {
            $returns = ErpSaleReturn::withDefaultGroupCompanyOrg()
                -> whereIn('store_id',$accessible_locations)
                ->whereBetween('document_date', [$selectedfyYear['start_date'], $selectedfyYear['end_date']])
                ->withDraftListingLogic()
                -> when($request -> customer_id, function ($custQuery) use($request) {
                    $custQuery -> where('customer_id', $request -> customer_id);
                }) -> when($request -> book_id, function ($bookQuery) use($request) {
                    $bookQuery -> where('book_id', $request -> book_id);
                }) -> when($request -> document_number, function ($docQuery) use($request) {
                    $docQuery -> where('document_number', 'LIKE', '%' . $request -> document_number . '%');
                }) -> when($request -> location_id, function ($docQuery) use($request) {
                    $docQuery -> where('store_id', $request -> location_id);
                }) -> when($request -> company_id, function ($docQuery) use($request) {
                    $docQuery -> where('store_id', $request -> company_id);
                }) -> when($request -> organization_id, function ($docQuery) use($request) {
                    $docQuery -> where('organization_id', $request -> organization_id);
                }) -> when($request -> status, function ($docStatusQuery) use($request) {
                    $searchDocStatus = [];
                    if ($request -> status === ConstantHelper::DRAFT) {
                        $searchDocStatus = [ConstantHelper::DRAFT];
                    } else if ($request -> status === ConstantHelper::SUBMITTED) {
                        $searchDocStatus = [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED];
                    } else {
                        $searchDocStatus = [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED];
                    }
                    $docStatusQuery -> whereIn('document_status', $searchDocStatus);
                }) -> when($dateRange, function ($dateRangeQuery) use($request, $dateRange) {
                $dateRanges = explode('to', $dateRange);
                if (count($dateRanges) == 2) {
                        $fromDate = Carbon::parse(trim($dateRanges[0])) -> format('Y-m-d');
                        $toDate = Carbon::parse(trim($dateRanges[1])) -> format('Y-m-d');
                        $dateRangeQuery -> whereDate('document_date', ">=" , $fromDate) -> where('document_date', '<=', $toDate);
                }
                else{
                    $fromDate = Carbon::parse(trim($dateRanges[0])) -> format('Y-m-d');
                    $dateRangeQuery -> whereDate('document_date', $fromDate);
                }
                }) -> when($request -> item_id, function ($itemQuery) use($request) {
                    $itemQuery -> withWhereHas('items', function ($itemSubQuery) use($request) {
                        $itemSubQuery -> where('item_id', $request -> item_id)
                        //Compare Item Category
                        -> when($request -> item_category_id, function ($itemCatQuery) use($request) {
                            $itemCatQuery -> whereHas('item', function ($itemRelationQuery) use($request) {
                                $itemRelationQuery -> where('category_id', $request -> category_id)
                                //Compare Item Sub Category
                                -> when($request -> item_sub_category_id, function ($itemSubCatQuery) use($request) {
                                    $itemSubCatQuery -> where('subcategory_id', $request -> item_sub_category_id);
                                });
                            });
                        });
                    });
                }) -> orderByDesc('id');

            return DataTables::of($returns)
                ->addIndexColumn()
                ->editColumn('document_status', function ($row) use ($orderType) {
                    $statusClasss = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status ?? ConstantHelper::DRAFT];    
                    $displayStatus = $row -> display_status;
                    $editRoute = route('sale.return.edit', ['id' => $row -> id]); 
                    return "
                        <div style='text-align:right;'>
                            <span class='badge rounded-pill $statusClasss badgeborder-radius'>$displayStatus</span>
                            <div class='dropdown' style='display:inline;'>
                                <button type='button' class='btn btn-sm dropdown-toggle hide-arrow py-0 p-0' data-bs-toggle='dropdown'>
                                    <i data-feather='more-vertical'></i>
                                </button>
                                <div class='dropdown-menu dropdown-menu-end'>
                                    <a class='dropdown-item' href='" . $editRoute . "'>
                                        <i data-feather='edit-3' class='me-50'></i>
                                        <span>View/ Edit Detail</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    ";
                })
                ->addColumn('document_type', function ($row) {
                    return 'Sales Return';
                })
                ->addColumn('book_name', function ($row) {
                    return $row->book_code ? $row->book_code : 'N/A';
                })
                ->addColumn('curr_name', function ($row) {
                    return $row->currency ? ($row->currency?->short_name ?? $row->currency?->name) : 'N/A';
                })
                ->editColumn('document_date', function ($row) {
                    return $row->getFormattedDate('document_date') ?? 'N/A';
                })
                ->editColumn('revision_number', function ($row) {
                    return strval($row->revision_number);
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->customer?->company_name ?? 'NA';
                })
                ->addColumn('items_count', function ($row) {
                    return $row->items->count();
                })
                ->editColumn('total_return_value', function ($row) {
                    return number_format($row->total_return_value, 2);
                })
                ->editColumn('total_discount_value', function ($row) {
                    return number_format($row->total_discount_value, 2);
                })
                ->editColumn('total_tax_value', function ($row) {
                    return number_format($row->total_tax_value, 2);
                })
                ->editColumn('total_expense_value', function ($row) {
                    return number_format($row->total_expense_value, 2);
                })
                ->editColumn('grand_total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->editColumn('e_invoice_status', function ($row) {
                    return ucfirst($row->e_invoice_status);
                })
                ->editColumn('delivery_status', function ($row) {
                    return ucfirst($row->delivery_status ? 'Delivered' : 'Not Delivered');
                })
                ->editColumn('is_ewb_generated', function ($row) {
                    return ucfirst($row->total_amount > EInvoiceHelper::EWAY_BILL_MIN_AMOUNT_LIMIT && $row -> irnDetail ? ($row -> is_ewb_generated ? 'Generated' : 'Pending') : '');
                })
                ->editColumn('created_by', function ($row) {
                    return ucfirst(isset($row->createdBy) ? $row->createdBy->name : '');
                })
                ->rawColumns(['document_status'])
                ->make(true);
        }

        return view('salesReturn.index', [
            'typeName' => $typeName,
            'redirect_url' => $redirectUrl,
            'create_route' => $createRoute,
            'filterArray' => TransactionReportHelper::FILTERS_MAPPING[ConstantHelper::SR_SERVICE_ALIAS],
            'create_button' => $create_button,
        ]);
    }
    public function create(Request $request)
    {
        $parentURL = request()->segments()[0];
        $redirectUrl = route('sale.return.index');
        $user = request()->user();
        $selectedfyYear = app(FinancialYearService::class)->getFinancialYear(date('Y-m-d'), $user);
        $type = SaleModuleHelper::getAndReturnReturnType($request->type ?? ConstantHelper::SR_SERVICE_ALIAS);
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL,'',$user);
        $firstService = $servicesBooks['services'][0];
        $bookType = $type;
        $typeName = "Sales Return";
        $countries = Country::select('id AS value', 'name AS label') -> where('status', ConstantHelper::ACTIVE) -> get();
        $stores = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCKK);
        $books = Helper::getBookSeries($bookType)->get();
        $transportationModes = EwayBillMaster::where('status', 'active')
                                ->where('type', '=', 'transportation-mode')
                                ->orderBy('id', 'ASC')
                                ->get();
        $data = [
            'user' => $user,
            'stores' => $stores,
            'services' => $servicesBooks['services'],
            'selectedService' => $firstService?->id ?? null,
            'countries' => $countries,
            'series' => $books,
            'type' => $type,
            'typeName' => $typeName,
            'redirect_url' => $redirectUrl,
            'current_financial_year' => $selectedfyYear,
            'transportationModes' => $transportationModes,
            'einvoice' => null

        ];
        return view('salesReturn.create_edit', $data);
    }
    public function edit(Request $request, string $id)
    {
        try {
            $user = $request->user();
            $revisionNumber = $request->revisionNumber ?? null;

            // ---- Load order (either current or from history) ----
            $orderQuery = $revisionNumber
                ? ErpSaleReturnHistory::query()
                    ->where('revision_number', $revisionNumber)
                    ->where('source_id', $id)
                : ErpSaleReturn::query()->where('id', $id);

            $order = $orderQuery->with([
                'discount_ted',
                'media_files',
                'expense_ted',
                'header_tax',
                'billing_address_details',
                'shipping_address_details',
                'location_address_details',
                'items' => function ($query) {
                    $query->with([
                        'discount_ted',
                        'tax_ted',
                        'item_locations',
                        'item_attributes',
                        'item' => function ($itemQuery) {
                            $itemQuery->with([
                                'specifications',
                                'alternateUoms.uom',
                                'uom'
                            ]);
                        }
                    ]);
                }
            ])->first();

            if (!$order) {
                return redirect()->route('sale.return.index')
                    ->with('error', 'Sales Return not found');
            }

            $ogReturn = $revisionNumber
                ? ErpSaleReturn::find($id)
                : $order;

            // ---- Preload related invoice items to avoid N+1 ----
            $siItemIds = $order->items->pluck('si_item_id')->filter()->unique();
            $pulledItems = ErpInvoiceItem::whereIn('id', $siItemIds)->get()->keyBy('id');

            foreach ($order->items as $siItem) {
                $pulled = $siItem->si_item_id ? $pulledItems->get($siItem->si_item_id) : null;

                if ($pulled) {
                    $siItem->max_attribute = $siItem->order_qty + $pulled->return_balance_qty;
                    $siItem->is_editable = false;
                } else {
                    $siItem->max_attribute = 999999;
                    $siItem->is_editable = true;
                }

                // Lock editing if document is not in DRAFT
                if ($order->document_status !== ConstantHelper::DRAFT) {
                    $siItem->is_editable = false;
                }
            }

            // ---- Basic derived values ----
            $revision_number = $order->revision_number ?? null;
            $totalValue = ($order->total_return_value - $order->total_discount_value)
                + $order->total_tax_value
                + $order->total_expense_value;

            // ---- Permissions, book, and service details ----
            $userType = $user->user_type;
            $parentURL = $request->segment(1);

            $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentURL, $order?->book?->service?->alias);
            $firstService = $servicesBooks['services'][0] ?? null;

            $buttons = Helper::actionButtonDisplay(
                $order->book_id,
                $order->document_status,
                $order->id,
                $totalValue,
                $order->approval_level,
                $order->created_by ?? 0,
                $userType,
                $revision_number
            );

            // ---- Document Type ----
            $type = SaleModuleHelper::getAndReturnReturnType(
                $request->type ?? ConstantHelper::SR_SERVICE_ALIAS
            );

            $books = Helper::getBookSeries($type)->get();

            // ---- Financial Year ----
            $selectedfyYear = app(FinancialYearService::class)
                ->getFinancialYear(date('Y-m-d'), $user);

            // ---- Other static data ----
            $countries = Country::select('id AS value', 'name AS label')
                ->where('status', ConstantHelper::ACTIVE)
                ->get();

            $revNo = $revisionNumber ? intval($revisionNumber) : $order->revision_number;
            $docValue = $order->total_amount ?? 0;

            // ---- Approval History ----
            $approvalHistory = Helper::getApprovalHistory($order->book_id, $ogReturn->id, $revNo, $docValue);

            // ---- Display / Status ----
            $display_status = $order->document_status === 'approval_not_required'
                ? 'Approved'
                : $order->display_status;

            $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$order->document_status] ?? '';

            // ---- Stores & e-Invoice ----
            $stores = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCK);
            $enableEinvoice = $order->gst_invoice_type === EInvoiceHelper::B2B_INVOICE_TYPE;
            $einvoice = $order->irnDetail()->first();

            // ---- Transportation ----
            $transportationModes = EwayBillMaster::where('status', ConstantHelper::ACTIVE)
                ->where('type', 'transportation-mode')
                ->orderBy('id')
                ->get();

            $editTransporterFields = !$einvoice?->ewb_no
                && $order->total_amount > EInvoiceHelper::EWAY_BILL_MIN_AMOUNT_LIMIT;

            // ---- Dynamic UI ----
            $dynamicFieldsUI = $order->dynamicfieldsUi();

            // ---- Return final view ----
            return view('salesReturn.create_edit', [
                'user' => $user,
                'services' => $servicesBooks['services'],
                'stores' => $stores,
                'selectedService' => $firstService?->id,
                'series' => $books,
                'order' => $order,
                'countries' => $countries,
                'buttons' => $buttons,
                'approvalHistory' => $approvalHistory,
                'type' => $type,
                'revision_number' => $revision_number,
                'docStatusClass' => $docStatusClass,
                'typeName' => 'Sales Return',
                'display_status' => $display_status,
                'redirect_url' => route('sale.return.index'),
                'einvoice' => $einvoice,
                'transportationModes' => $transportationModes,
                'enableEinvoice' => $enableEinvoice,
                'dynamicFieldsUi' => $dynamicFieldsUI,
                'current_financial_year' => $selectedfyYear,
                'editTransporterFields' => $editTransporterFields,
            ]);
        } catch (Exception $ex) {
            report($ex);
            return redirect()->back()->with('error', 'Something went wrong while editing Sales Return.');
        }
    }



    public function store(ErpSaleReturnRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = request()->user();;
            $type = SaleModuleHelper::getAndReturnReturnType($request->type ?? ConstantHelper::SR_SERVICE_ALIAS);
            $request->merge(['type' => $type]);
            //Auth credentials
            $store = ErpStore::find($request -> store_id);
            $subStore = ErpStore::find($request -> sub_store_id);
            $organization = Organization::find($user->organization_id);
            $organizationId = $organization?->id ?? null;
            $groupId = $organization?->group_id ?? null;
            $companyId = $organization?->company_id ?? null;
            //Tax Country and State
            $firstAddress = $organization->addresses->first();
            $companyCountryId = null;
            $companyStateId = null;
            if ($firstAddress) {
                $companyCountryId = $store?->address?->country->id??null;
                $companyStateId = $store?->address?->state->id??null;
            } else {
                return response()->json([
                    'message' => 'Please create an organization first'
                ], 422);
            }
            $currencyExchangeData = CurrencyHelper::getCurrencyExchangeRates($request->currency_id, $request->document_date);
            if ($currencyExchangeData['status'] == false) {
                return response()->json([
                    'message' => $currencyExchangeData['message']
                ], 422);
            }
            $documentNo = $request->document_number ?? null;
            $itemTaxIds = [];
            $itemAttributeIds = [];
            if (!$request->sale_invoice_id) {
                $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
                if (!isset($numberPatternData)) {
                    return response()->json([
                        'message' => "Invalid Book",
                        'error' => "",
                    ], 422);
                }
                $document_number = $numberPatternData['document_number'] ? $numberPatternData['document_number'] : $request->document_no;
                $regeneratedDocExist = ErpSaleReturn::withDefaultGroupCompanyOrg()->where('book_id', $request->book_id)
                    ->where('document_number', $document_number)->first();
                //Again check regenerated doc no
                if (isset($regeneratedDocExist)) {
                    return response()->json([
                        'message' => ConstantHelper::DUPLICATE_DOCUMENT_NUMBER,
                        'error' => "",
                    ], 422);
                }
            }
            $saleInvoice = null;
            $transportationMode = EwayBillMaster::find($request->transporter_mode);
            if(isset($request -> reference_id))
            {
                $referenec_doc = ErpSaleInvoice::find($request -> reference_id);
                $tcsAssessableAmt = $referenec_doc -> header_tax() -> where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) -> first() ?-> assessment_amount ?? 0;
                $oldNonTcsAccesableAmt = ($referenec_doc -> total_item_value - $referenec_doc -> total_discount_value) - $tcsAssessableAmt;
            }
            else
            {
                $oldNonTcsAccesableAmt = 0;
            }
            if ($request->sale_return_id) { //Update
                $saleInvoice = ErpSaleReturn::find($request->sale_return_id);
                $saleInvoice->document_date = $request->document_date;
                $saleInvoice->reference_number = $request->reference_no;
                $saleInvoice->consignee_name = $request->consignee_name;
                $saleInvoice->consignment_no = $request->consignment_no;
                $saleInvoice->customer_gstin = $request->customer_gstin;
                $saleInvoice->vehicle_no = $request->vehicle_no;
                $saleInvoice->transporter_name = $request->transporter_name;
                $saleInvoice -> transportation_mode = $transportationMode ?-> description;
                $saleInvoice -> eway_bill_master_id = $transportationMode ?-> id;
                $tcsAssessableAmt = $saleInvoice -> header_tax() -> where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) -> first() ?-> assessment_amount ?? 0;
                $oldNonTcsAccesableAmt = ($saleInvoice -> total_return_value - $saleInvoice -> total_discount_value) - $tcsAssessableAmt;
                // $saleInvoice->eway_bill_no = $request->eway_bill_no;
                $locationAddress = $saleInvoice -> location_address_details;
                $billingAddress = $saleInvoice -> billing_address_details;
                $saleInvoice->remarks = $request->final_remarks;
                $actionType = $request->action_type ?? '';
                //Amend backup
                if (($saleInvoice->document_status == ConstantHelper::APPROVED || $saleInvoice->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED) && $actionType == 'amendment') {
                    $revisionData = [
                        ['model_type' => 'header', 'model_name' => 'ErpSaleReturn', 'relation_column' => ''],
                        ['model_type' => 'detail', 'model_name' => 'ErpSaleReturnItem', 'relation_column' => 'sale_return_id'],
                        ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnItemAttribute', 'relation_column' => 'sale_return_item_id'],
                        ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnItemLocation', 'relation_column' => 'sale_return_item_id'],
                        ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnTed', 'relation_column' => 'sale_return_item_id'],
                    ];
                    $a = Helper::documentAmendment($revisionData, $saleInvoice->id);

                }
                $keys = ['deletedItemDiscTedIds', 'deletedHeaderDiscTedIds', 'deletedHeaderExpTedIds', 'deletedSiItemIds', 'deletedDelivery', 'deletedAttachmentIds'];
                $deletedData = [];

                foreach ($keys as $key) {
                    $deletedData[$key] = json_decode($request->input($key, '[]'), true);
                }
                if (count($deletedData['deletedHeaderExpTedIds'])) {
                    ErpSaleReturnTed::whereIn('id', $deletedData['deletedHeaderExpTedIds'])->delete();
                }

                if (count($deletedData['deletedHeaderDiscTedIds'])) {
                    ErpSaleReturnTed::whereIn('id', $deletedData['deletedHeaderDiscTedIds'])->delete();
                }

                if (count($deletedData['deletedItemDiscTedIds'])) {
                    ErpSaleReturnTed::whereIn('id', $deletedData['deletedItemDiscTedIds'])->delete();
                }

                if (count($deletedData['deletedSiItemIds'])) {
                    $srItems = ErpSaleReturnItem::whereIn('id', $deletedData['deletedSiItemIds'])->get();
                    # all ted remove item level
                    foreach ($srItems as $srItem) {

                        // if ($siItem -> dnote_item_id) {
                        //     $refSiItem = ErpInvoiceItem::find($siItem -> dnote_item_id);
                        //     if (isset($refSiItem)) {
                        //         $refSiItem -> srn_qty -= $siItem -> invoice_qty;
                        //         $refSiItem -> save(); 
                        //     }
                        // }
                        $srItem->teds()->delete();
                        #delivery remove
                        // $siItem->item_deliveries()->delete();
                        # all attr remove
                        $srItem->attributes()->delete();

                        // $refereceItemIds = $siItem -> mapped_so_item_ids();
                        // if (count($refereceItemIds) > 0) {
                        //     foreach ($refereceItemIds as $referenceFromId) {
                        //         $referenceItem = ErpSoItem::where('id', $referenceFromId) -> first();
                        //         $existingMapping = ErpSoDnMapping::where([
                        //             ['sale_order_id', $referenceItem -> sale_order_id],
                        //             ['so_item_id', $referenceItem -> id],
                        //             ['delivery_note_id', $saleInvoice -> id],
                        //             ['dn_item_id', $siItem -> id],
                        //         ]) -> first();
                        //         if (isset($existingMapping)) {
                        //             $referenceItem -> dnote_qty = $referenceItem -> dnote_qty - $siItem -> order_qty;
                        //             if (!$invoiceRequiredParam) {
                        //                 $referenceItem -> invoice_qty = $referenceItem -> invoice_qty - $siItem -> order_qty;
                        //             }
                        //             $referenceItem -> save();
                        //             $existingMapping -> delete();
                        //         }
                        //     }
                        // }

                        $srItem->delete();
                        if ($srItem->si_item_id) {
                            $siItem = ErpInvoiceItem::find($srItem->si_item_id);

                            if (isset($siItem)) {
                                $siItem->srn_qty -= $srItem->order_qty;
                                if ($siItem->header->document_type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS ||
                                        $siItem->header->document_type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS) {
                                        $siItem->srn_qty -= $srItem->order_qty;
                                        if ($siItem->so_item_id) {
                                            $soItem = $siItem->sale_order_item();
                                            if (isset($soItem)) {
                                                $soItem->srn_qty -= $srItem->order_qty;
                                                $soItem->save();
                                            }
                                        }
                                }
                                if ($siItem->header->document_type === ConstantHelper::SI_SERVICE_ALIAS)
                                {
                                    $siItem->inv_srn_qty -= $srItem -> order_qty;
                                    if(isset($siItem->dnote_item_id))
                                    {
                                        $dnoteItem = $siItem -> dnoteItem;
                                        if(isset($dnoteItem))
                                        {
                                            $dnoteItem -> inv_srn_qty -= $srItem -> order_qty;  
                                            $dnoteItem -> save();
                                        } 
                                    }
                                    if(isset($siItem -> so_item_id))
                                    {
                                        $soItem = $siItem -> sale_order_item();
                                        if(isset($soItem))
                                        {
                                            $soItem -> inv_srn_qty -= $srItem -> order_qty;
                                            $soItem -> save();
                                        }
                                    }
                                }
                                $siItem->save();

                               
                            }
                        }
                    }
                }

                if (count($deletedData['deletedAttachmentIds'])) {
                    $files = ErpSrMedia::whereIn('id',$deletedData['deletedAttachmentIds'])->get();
                    foreach ($files as $singleMedia) {
                        $filePath = $singleMedia -> file_name;
                        if (Storage::exists($filePath)) {
                            Storage::delete($filePath);
                        }
                        $singleMedia -> delete();
                    }
                }
            }



            //Delete all Item references
            // foreach ($saleInvoice -> items as $item) {
            //     InventoryHelper::deleteIssueStock($saleInvoice->id, $item->id, $item->item_id, 'invoice', 'issue');
            //     if (($request -> type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS || $request -> type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)) {
            //     }
            //     $item -> item_attributes() -> forceDelete();
            //     $item -> discount_ted() -> forceDelete();
            //     $item -> tax_ted() -> forceDelete();
            //     $item -> item_locations() -> forceDelete();
            //     $item -> forceDelete();
            // }
            //Delete all header TEDs
            // foreach ($saleInvoice -> discount_ted as $saleInvoiceDiscount) {
            //     $saleInvoiceDiscount -> forceDelete(); 
            // }
            // foreach ($saleInvoice -> expense_ted as $saleInvoiceExpense) {
            //     $saleInvoiceExpense -> forceDelete(); 
            // }
            if (!$request->sale_return_id) {
                $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
                if (!isset($numberPatternData)) {
                    return response()->json([
                        'message' => "Invalid Book",
                        'error' => "",
                    ], 422);
                }
                $document_number = $numberPatternData['document_number'] ? $numberPatternData['document_number'] : $request->document_no;
                $regeneratedDocExist = ErpSaleReturn::withDefaultGroupCompanyOrg()->where('book_id', $request->book_id)
                    ->where('document_number', $document_number)->first();
                //Again check regenerated doc no
                if (isset($regeneratedDocExist)) {
                    return response()->json([
                        'message' => ConstantHelper::DUPLICATE_DOCUMENT_NUMBER,
                        'error' => "",
                    ], 422);
                }
            }
            $saleInvoice = null;
            if ($request->sale_return_id) { //Update
                $saleInvoice = ErpSaleReturn::find($request->sale_return_id);
                $saleInvoice->document_date = $request->document_date;
                $saleInvoice->reference_number = $request->reference_no;
                $saleInvoice->consignee_name = $request->consignee_name;
                $saleInvoice->consignment_no = $request->consignment_no;
                $saleInvoice -> return_type = $request -> type_input;
                $saleInvoice -> reference_id = $request -> reference_id;
                $saleInvoice -> reference_doc_type = $request -> reference_doc_type;
                $saleInvoice->vehicle_no = $request->vehicle_no;
                $saleInvoice->transporter_name = $request->transporter_name;
                $saleInvoice -> transportation_mode = $request -> transporter_mode;
                $saleInvoice -> eway_bill_master_id = $transportationMode ?-> id;
                $tcsAssessableAmt = $saleInvoice -> header_tax() -> where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) -> first() ?-> assessment_amount ?? 0;
                $oldNonTcsAccesableAmt = ($saleInvoice -> total_return_value - $saleInvoice -> total_discount_value) - $tcsAssessableAmt;
                // $saleInvoice->eway_bill_no = $request->eway_bill_no;
                $saleInvoice->remarks = $request->final_remarks;
                $locationAddress = $saleInvoice -> location_address_details;
                $billingAddress = $saleInvoice -> billing_address_details;
                //Update all Item references
                // foreach ($saleInvoice->items as $item) {
                //     InventoryHelper::addReturnedStock($saleInvoice->id, $item->id, $item->item_id, 'return', 'receive');
                    // if (($request -> type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS || $request -> type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)) {
                    // }
                    // $item -> item_attributes() -> forceDelete();
                    // $item -> discount_ted() -> forceDelete();
                    // $item -> tax_ted() -> forceDelete();
                    // $item -> item_locations() -> forceDelete();
                    // $item -> forceDelete();
                // }
                //Delete all header TEDs
                // foreach ($saleInvoice -> discount_ted as $saleInvoiceDiscount) {
                //     $saleInvoiceDiscount -> forceDelete(); 
                // }
                // foreach ($saleInvoice -> expense_ted as $saleInvoiceExpense) {
                //     $saleInvoiceExpense -> forceDelete(); 
                // }
            } else { //Create
                // $department = Department::find($request -> department_id);
                
                $saleInvoice = ErpSaleReturn::create([
                    'organization_id' => $organizationId,
                    'group_id' => $groupId,
                    'company_id' => $companyId,
                    'book_id' => $request->book_id,
                    'book_code' => $request->book_code,
                    'document_type' => $type,
                    'document_number' => $request->document_no,
                    'doc_number_type' => $numberPatternData['type'],
                    'doc_reset_pattern' => $numberPatternData['reset_pattern'],
                    'doc_prefix' => $numberPatternData['prefix'],
                    'doc_suffix' => $numberPatternData['suffix'],
                    'doc_no' => $numberPatternData['doc_no'],
                    'document_date' => $request->document_date,
                    'reference_number' => $request->reference_no,
                    'reference_id' => $request -> reference_id ?? null,
                    'reference_doc_type' => $request -> reference_doc_type ?? null,
                    'return_type' => $request -> type_input ?? 0,
                    'store_id' => $request->store_id ?? null,
                    'store_code' => $store?->store_code ?? null,
                    'sub_store_id' => $request->sub_store_id ?? null,
                    'sub_store_code' => $subStore?->sub_store_code ?? null,
                    // 'department_id' => $request->department_id,
                    // 'department_code' => $department?->name ?? null,
                    'customer_id' => $request->customer_id,
                    'customer_code' => $request->customer_code,
                    'consignee_name' => $request->consignee_name,
                    'consignment_no' => $request->consignment_no,
                    'vehicle_no' => $request->vehicle_no,
                    'transporter_name' => $request->transporter_name,
                    'transportation_mode' => $transportationMode ?-> description,
                    'eway_bill_master_id' => $transportationMode ?-> id,
                    // 'eway_bill_no' => $request->eway_bill_no,
                    'billing_address' => null,
                    'shipping_address' => null,
                    'currency_id' => $request->currency_id,
                    'currency_code' => $request->currency_code,
                    'payment_term_id' => $request->payment_terms_id,
                    'payment_term_code' => $request->payment_terms_code,
                    'document_status' => ConstantHelper::DRAFT,
                    'approval_level' => 1,
                    'remarks' => $request->final_remarks,
                    'org_currency_id' => $currencyExchangeData['data']['org_currency_id'],
                    'org_currency_code' => $currencyExchangeData['data']['org_currency_code'],
                    'org_currency_exg_rate' => $currencyExchangeData['data']['org_currency_exg_rate'],
                    'comp_currency_id' => $currencyExchangeData['data']['comp_currency_id'],
                    'comp_currency_code' => $currencyExchangeData['data']['comp_currency_code'],
                    'comp_currency_exg_rate' => $currencyExchangeData['data']['comp_currency_exg_rate'],
                    'group_currency_id' => $currencyExchangeData['data']['group_currency_id'],
                    'group_currency_code' => $currencyExchangeData['data']['group_currency_code'],
                    'group_currency_exg_rate' => $currencyExchangeData['data']['group_currency_exg_rate'],
                    'total_return_value' => 0,
                    'total_discount_value' => 0,
                    'total_tax_value' => 0,
                    'total_expense_value' => 0,
                ]);
                //Billing Address
                $customerBillingAddress = ErpAddress::find($request->billing_address);
                if (isset($customerBillingAddress)) {
                    $billingAddress = $saleInvoice->billing_address_details()->create([
                        'address' => $customerBillingAddress->address,
                        'country_id' => $customerBillingAddress->country_id,
                        'state_id' => $customerBillingAddress->state_id,
                        'city_id' => $customerBillingAddress->city_id,
                        'type' => 'billing',
                        'pincode' => $customerBillingAddress->pincode,
                        'phone' => $customerBillingAddress->phone,
                        'fax_number' => $customerBillingAddress->fax_number
                    ]);
                }
                // Shipping Address
                $customerShippingAddress = ErpAddress::find($request->shipping_address);
                if (isset($customerShippingAddress)) {
                    $shippingAddress = $saleInvoice->shipping_address_details()->create([
                        'address' => $customerShippingAddress->address,
                        'country_id' => $customerShippingAddress->country_id,
                        'state_id' => $customerShippingAddress->state_id,
                        'city_id' => $customerShippingAddress->city_id,
                        'type' => 'shipping',
                        'pincode' => $customerShippingAddress->pincode,
                        'phone' => $customerShippingAddress->phone,
                        'fax_number' => $customerShippingAddress->fax_number
                    ]);
                }
                //Location Address
                $orgLocationAddress = ErpStore::with('address') -> find($request -> store_id);
                if (!isset($orgLocationAddress) || !isset($orgLocationAddress -> address)) {
                    DB::rollBack();
                    return response() -> json([
                        'message' => 'Location Address not assigned',
                        'error' => ''
                    ], 422);
                }
                $locationAddress = $saleInvoice -> location_address_details() -> create([
                    'address' => $orgLocationAddress -> address -> address,
                    'country_id' => $orgLocationAddress -> address -> country_id,
                    'state_id' => $orgLocationAddress -> address -> state_id,
                    'city_id' => $orgLocationAddress -> address -> city_id,
                    'type' => 'location',
                    'pincode' => $orgLocationAddress -> address -> pincode,
                    'phone' => $orgLocationAddress -> address -> phone,
                    'fax_number' => $orgLocationAddress -> address -> fax_number
                ]);
            }
            $saleInvoice -> gst_invoice_type = EInvoiceHelper::getGstInvoiceType($request -> customer_id, $saleInvoice -> shipping_address_details -> country_id, $saleInvoice ?->  location_address_details ?-> country_id);
            //Dynamic Fields
            $status = DynamicFieldHelper::saveDynamicFields(ErpSrDynamicField::class, $saleInvoice -> id, $request -> dynamic_field ?? []);
            if ($status && !$status['status'] ) {
                DB::rollBack();
                return response() -> json([
                    'message' => $status['message'],
                    'error' => ''
                ], 422);
            }
            //Get Header Discount
            $totalHeaderDiscount = 0;
            if (isset($request->order_discount_value) && count($request->order_discount_value) > 0)
                foreach ($request->order_discount_value as $orderDiscountValue) {
                    $totalHeaderDiscount += $orderDiscountValue;
                }
            //Initialize item discount to 0
            $itemTotalDiscount = 0;
            $itemTotalValue = 0;
            $totalTax = 0;
            $totalItemValueAfterDiscount = 0;

            $saleInvoice->billing_address = $request -> billing_address ??  null;
            $saleInvoice->shipping_address = $request -> shipping_address ?? null;
            $saleInvoice->save();
            //Seperate array to store each item calculation
            $itemsData = array();
            if ($request->item_id && count($request->item_id) > 0) {
                //Items
                $totalValueAfterDiscount = 0;
                foreach ($request->item_id as $itemKey => $itemId) {
                    $item = Item::find($itemId);
                    if (isset($item)) {
                        $itemValue = (isset($request->item_qty[$itemKey]) ? $request->item_qty[$itemKey] : 0) * (isset($request->item_rate[$itemKey]) ? $request->item_rate[$itemKey] : 0);
                        $itemDiscount = 0;
                        //Item Level Discount
                        if (isset($request->item_discount_value[$itemKey]) && count($request->item_discount_value[$itemKey]) > 0) {
                            foreach ($request->item_discount_value[$itemKey] as $itemDiscountValue) {
                                $itemDiscount += $itemDiscountValue;
                            }
                        }
                        $itemTotalValue += $itemValue;
                        $itemTotalDiscount += $itemDiscount;
                        $itemValueAfterDiscount = $itemValue - $itemDiscount;
                        $totalValueAfterDiscount += $itemValueAfterDiscount;
                        $totalItemValueAfterDiscount += $itemValueAfterDiscount;
                        $inventoryUomQty = ItemHelper::convertToBaseUom($item -> id, $request -> uom_id[$itemKey] ?? 0, isset($request -> item_qty[$itemKey]) ? $request -> item_qty[$itemKey] : 0);
                        array_push($itemsData, [
                            'sale_return_id' => $saleInvoice->id,
                            'item_id' => $item->id,
                            'item_code' => $item->item_code,
                            'item_name' => $item->item_name,
                            'hsn_id' => $item->hsn_id,
                            'hsn_code' => $item->hsn?->code,
                            'uom_id' => isset($request->uom_id[$itemKey]) ? $request->uom_id[$itemKey] : null, //Need to change
                            'uom_code' => isset($request->item_uom_code[$itemKey]) ? $request->item_uom_code[$itemKey] : null,
                            'order_qty' => isset($request->item_qty[$itemKey]) ? $request->item_qty[$itemKey] : 0,
                            'store_id' => isset($request->item_store[$itemKey])?$request->item_store[$itemKey]:null,
                            'sub_store_id' => isset($request->item_sub_store[$itemKey])?$request->item_sub_store[$itemKey]:null,
                            'invoice_qty' => 0,
                            'inventory_uom_id' => $item->uom?->id,
                            'inventory_uom_code' => $item->uom?->name,
                            'inventory_uom_qty' => $inventoryUomQty,
                            'rate' => isset($request->item_rate[$itemKey]) ? $request->item_rate[$itemKey] : 0,
                            'item_discount_amount' => $itemDiscount,
                            'header_discount_amount' => 0,
                            'item_expense_amount' => 0, //Need to change
                            'header_expense_amount' => 0, //Need to change
                            'tax_amount' => 0,
                            'company_currency_id' => null,
                            'company_currency_exchange_rate' => null,
                            'group_currency_id' => null,
                            'group_currency_exchange_rate' => null,
                            'remarks' => isset($request->item_remarks[$itemKey]) ? $request->item_remarks[$itemKey] : null,
                            'value_after_discount' => $itemValueAfterDiscount,
                            'item_value' => $itemValue
                        ]);
                    }
                }
                foreach ($itemsData as $itemDataKey => $itemDataValue) {
                    //Discount
                    $headerDiscount = 0;
                    if ($totalValueAfterDiscount > 0) {
                        $headerDiscount = ($itemDataValue['value_after_discount'] / $totalValueAfterDiscount) * $totalHeaderDiscount;
                    }
                    $valueAfterHeaderDiscount = $itemDataValue['value_after_discount'] - $headerDiscount;
                    //Expense
                    $itemExpenseAmount = 0;
                    $itemHeaderExpenseAmount = 0;
                    //Tax
                    $itemTax = 0;
                    $itemPrice = ($itemDataValue['item_value'] + $headerDiscount + $itemDataValue['item_discount_amount']) / $itemDataValue['order_qty'];
                    $partyCountryId = isset($billingAddress) ? $billingAddress->country_id : null;
                    $partyStateId = isset($billingAddress) ? $billingAddress->state_id : null;
                    $taxDetails = TaxHelper::calculateTax($itemDataValue['hsn_id'], $itemPrice, $companyCountryId, $companyStateId, $partyCountryId ?? $request->billing_country_id, $partyStateId ?? $request->billing_state_id, 'sale');
                    if (isset($taxDetails) && count($taxDetails) > 0) {
                        foreach ($taxDetails as $taxDetail) {
                            $itemTax += ((double) $taxDetail['tax_percentage'] / 100 * $valueAfterHeaderDiscount);
                        }
                        if($taxDetail['applicability_type']=="collection")
                        {
                            $totalTax += $itemTax;
                        }
                        else
                        {
                            $totalTax -= $itemTax;
                        }
                    }
                    // dd($itemDataValue);
                    $itemRowData = [
                        'sale_return_id' => $saleInvoice->id,
                        'item_id' => $itemDataValue['item_id'],
                        'item_code' => $itemDataValue['item_code'],
                        'item_name' => $itemDataValue['item_name'],
                        'hsn_id' => $itemDataValue['hsn_id'],
                        'hsn_code' => $itemDataValue['hsn_code'],
                        'uom_id' => $itemDataValue['uom_id'], //Need to change
                        'uom_code' => $itemDataValue['inventory_uom_code'],
                        'store_id' => $itemDataValue['store_id'],
                        'sub_store_id' => $itemDataValue['sub_store_id'],
                        'order_qty' => $itemDataValue['order_qty'],
                        'rate' => $itemDataValue['rate'],
                        'item_discount_amount' => $itemDataValue['item_discount_amount'],
                        'header_discount_amount' => $headerDiscount,
                        'item_expense_amount' => $itemExpenseAmount,
                        'header_expense_amount' => $itemHeaderExpenseAmount,
                        'total_item_amount' => ($itemDataValue['order_qty'] * $itemDataValue['rate']) - ($itemDataValue['item_discount_amount'] + $headerDiscount) + ($itemExpenseAmount + $itemHeaderExpenseAmount) + $itemTax,
                        'tax_amount' => $itemTax,
                        'remarks' => $itemDataValue['remarks'],
                    ];
                    // dd($request->si_item_id[$itemDataKey]);
                    if (isset($request->si_item_id[$itemDataKey])) {
                        $oldSoItem = ErpSaleReturnItem::find($request->si_item_id[$itemDataKey]);
                        $soItem = ErpSaleReturnItem::updateOrCreate(['id' => $request->si_item_id[$itemDataKey]], $itemRowData);
                    } else {
                        $soItem = ErpSaleReturnItem::create($itemRowData);
                    }

                    //Order Pulling condition 
                    if (($request->quotation_item_ids && isset($request->quotation_item_ids[$itemDataKey]) && isset($request->quotation_item_type[$itemDataKey])) || $soItem->si_item_id) {
                        $pullType = $request->quotation_item_type[$itemDataKey];
                        if ($pullType === ConstantHelper::SI_SERVICE_ALIAS) {
                            $qtItem = ErpInvoiceItem::find($request->quotation_item_ids[$itemDataKey] ?? 0);
                            if ($qtItem) {
                                $extraQty = $oldSoItem->order_qty ?? 0;

                                $qtItem->inv_srn_qty = ($qtItem->inv_srn_qty ?? 0) + $itemDataValue['order_qty'] - $extraQty;
                                if (($qtItem->inv_srn_qty ?? 0) > ($qtItem->order_qty ?? 0)) {
                                    DB::rollBack();
                                    return response()->json([
                                        'status'  => 'error',
                                        'title'   => 'Quantity Exceeded',
                                        'message' => 'You cannot return more than the invoiced quantity.',
                                    ], 422);
                                }
                                $qtItem->save();

                                $soItem->si_item_id = $qtItem->id;
                                $soItem->save();

                                if (isset($qtItem->dnote_item_id)) {
                                    $dnoteItem = $qtItem->dnoteItem;
                                    if ($dnoteItem) {
                                        $dnoteItem->inv_srn_qty = ($dnoteItem->inv_srn_qty ?? 0) + $itemDataValue['order_qty'] - $extraQty;
                                        //($qtItem->srn_qty ?? 0) > (($qtItem->order_qty ?? 0) - ($qtItem->invoice_qty ?? 0) + ($qtItem->inv_srn_qty ?? 0)
                                        //order_qty > (COALESCE(invoice_qty, 0) - COALESCE(inv_srn_qty, 0) + COALESCE(srn_qty, 0))'
                                        if (($dnoteItem->order_qty ?? 0) < ($dnoteItem->invoice_qty ?? 0) - ($dnoteItem->inv_srn_qty ?? 0) + ($dnoteItem->srn_qty ?? 0)) {
                                            DB::rollBack();
                                            return response()->json([
                                                'status'  => 'error',
                                                'title'   => 'Quantity Exceeded',
                                                'message' => 'You cannot return more than the invoiced quantity.',
                                            ], 422);
                                        }
                                        $dnoteItem->save();
                                    }
                                }
                                
                                if (isset($qtItem->so_item_id)) {
                                    $orderItem = $qtItem->sale_order_item();
                                    if ($orderItem) {
                                        $orderItem->inv_srn_qty = ($orderItem->inv_srn_qty ?? 0) + $itemDataValue['order_qty'] - $extraQty;
                                        //($qtItem->srn_qty ?? 0) > (($qtItem->order_qty ?? 0) - ($qtItem->invoice_qty ?? 0) + ($qtItem->inv_srn_qty ?? 0)
                                        //order_qty > (COALESCE(invoice_qty, 0) - COALESCE(inv_srn_qty, 0) + COALESCE(srn_qty, 0))'
                                        if (($orderItem->order_qty ?? 0) < ($orderItem->invoice_qty ?? 0) - ($orderItem->inv_srn_qty ?? 0) + ($orderItem->srn_qty ?? 0)) {
                                            DB::rollBack();
                                            return response()->json([
                                                'status'  => 'error',
                                                'title'   => 'Quantity Exceeded',
                                                'message' => 'You cannot return more than the invoiced quantity.',
                                            ], 422);
                                        }
                                        $orderItem->save();
                                    }
                                }

                            }
                        } elseif (
                            $pullType === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS
                            || $pullType === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS
                        ) {
                            $qtItem = ErpInvoiceItem::find($request->quotation_item_ids[$itemDataKey] ?? 0);
                            if ($qtItem) {
                                $additionalQty = $itemDataValue['order_qty'] - ($oldSoItem->order_qty ?? 0);
                                $qtItem->srn_qty = ($qtItem->srn_qty ?? 0) + $additionalQty;

                                // Null-safe COALESCE-style check

                                if (($qtItem->order_qty ?? 0) < ($qtItem->invoice_qty ?? 0) - ($qtItem->inv_srn_qty ?? 0) + ($qtItem->srn_qty ?? 0)) {
                                    DB::rollBack();
                                    return response()->json([
                                        'status'  => 'error',
                                        'title'   => 'Quantity Exceeded',
                                        'message' => 'You cannot return more than the dnote quantity.',
                                    ], 422);
                                
                                }

                                $qtItem->save();
                                $soItem->si_item_id = $qtItem->id;
                                $soItem->save();

                                if (isset($qtItem->so_item_id)) {
                                    $orderItem = ErpSOItem::find($qtItem->so_item_id);
                                    if ($orderItem) {
                                        $orderItem->srn_qty = ($orderItem->srn_qty ?? 0) + $additionalQty;

                                        if (($orderItem->order_qty ?? 0) < ($orderItem->dnote_qty ?? 0) - ($orderItem->srn_qty ?? 0)) {
                                            DB::rollBack();
                                            return response()->json([
                                                'status'  => 'error',
                                                'title'   => 'Quantity Exceeded',
                                                'message' => 'You cannot return more than the dnote quantity.',
                                            ], 422);
                                        
                                        }

                                        $orderItem->save();
                                    }
                                }
                            }
                        }

                    }

                    //TED Data (DISCOUNT)
                    if (isset($request->item_discount_value[$itemDataKey])) {
                        foreach ($request->item_discount_value[$itemDataKey] as $itemDiscountKey => $itemDiscountTed) {
                            $itemDiscountRowData = [
                                'sale_return_id' => $saleInvoice->id,
                                'sale_return_item_id' => $soItem->id,
                                'ted_type' => 'Discount',
                                'ted_level' => 'D',
                                'ted_id' => isset($request->item_discount_master_id[$itemDataKey][$itemDiscountKey]) ? $request->item_discount_master_id[$itemDataKey][$itemDiscountKey] : null,
                                'ted_name' => isset($request->item_discount_name[$itemDataKey][$itemDiscountKey]) ? $request->item_discount_name[$itemDataKey][$itemDiscountKey] : null,
                                'assessment_amount' => $itemDataValue['rate'] * $itemDataValue['order_qty'],
                                'ted_percentage' => isset($request->item_discount_percentage[$itemDataKey][$itemDiscountKey]) ? $request->item_discount_percentage[$itemDataKey][$itemDiscountKey] : null,
                                'ted_amount' => $itemDiscountTed,
                                'applicable_type' => 'Deduction',
                            ];
                            if (isset($request->item_discount_id[$itemDataKey][$itemDiscountKey])) {
                                $soItemTedForDiscount = ErpSaleReturnTed::updateOrCreate(['id' => $request->item_discount_id[$itemDataKey][$itemDiscountKey]], $itemDiscountRowData);
                            } else {
                                $soItemTedForDiscount = ErpSaleReturnTed::create($itemDiscountRowData);
                            }

                        }
                    }
                    //TED Data (TAX)
                    if (isset($taxDetails) && count($taxDetails) > 0) {
                        foreach ($taxDetails as $taxDetail) {

                            $soItemTedForDiscount = ErpSaleReturnTed::updateOrCreate(
                                [
                                    'sale_return_id' => $saleInvoice->id,
                                    'sale_return_item_id' => $soItem->id,
                                    'ted_type' => 'Tax',
                                    'ted_level' => 'D',
                                    'ted_id' => $taxDetail['id'],
                                ],
                                [
                                    'ted_group_code' => $taxDetail['tax_group'],
                                    'ted_name' => $taxDetail['tax_type'],
                                    'assessment_amount' => $valueAfterHeaderDiscount,
                                    'ted_percentage' => (double) $taxDetail['tax_percentage'],
                                    'ted_amount' => ((double) $taxDetail['tax_percentage'] / 100 * $valueAfterHeaderDiscount),
                                    'applicable_type' => $taxDetail['applicability_type'],
                                ]
                            );
                            array_push($itemTaxIds, $soItemTedForDiscount->id);

                        }
                    }

                    //Item Attributes
                    if (isset($request->item_attributes[$itemDataKey])) {
                        $attributesArray = json_decode($request->item_attributes[$itemDataKey], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($attributesArray)) {
                            foreach ($attributesArray as $attributeKey => $attribute) {
                                $attributeVal = "";
                                $attributeValId = null;
                                foreach ($attribute['values_data'] as $valData) {
                                    if ($valData['selected']) {
                                        $attributeVal = $valData['value'];
                                        $attributeValId = $valData['id'];
                                        break;
                                    }
                                }
                                $itemAttribute = ErpSaleReturnItemAttribute::updateOrCreate(
                                    [
                                        'sale_return_id' => $saleInvoice->id,
                                        'sale_return_item_id' => $soItem->id,
                                        'item_attribute_id' => $attribute['id'],
                                    ],
                                    [
                                        'item_code' => $soItem->item_code,
                                        'attribute_name' => $attribute['group_name'],
                                        'attr_name' => $attribute['attribute_group_id'],
                                        'attribute_value' => $attributeVal,
                                        'attr_value' => $attributeValId,
                                    ]
                                );
                                array_push($itemAttributeIds, $itemAttribute->id);
                                // ErpInvoiceItemAttribute::create([
                                //     'sale_invoice_id' => $saleInvoice -> id,
                                //     'invoice_item_id' => $soItem -> id,
                                //     'item_attribute_id' => $attribute['id'],
                                //     'item_code' => $soItem -> item_code,
                                //     'attribute_name' => $attribute['group_name'],
                                //     'attr_name' => $attribute['attribute_group_id'],
                                //     'attribute_value' => $attributeVal,
                                //     'attr_value' => $attributeValId,
                                // ]);
                            }
                        } else {
                            return response()->json([
                                'message' => 'Item No. ' . ($itemDataKey + 1) . ' has invalid attributes',
                                'error' => ''
                            ], 422);
                        }
                    }

                    // Item Locations (only in case of DN and Inv CUM DN)
                    // if (isset($request -> item_locations[$itemDataKey]) && ($request -> type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS || $request -> type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)) {
                    if (isset($request->item_store[$itemDataKey])) {
                        $itemLocations = json_decode($request->item_locations[$itemDataKey], true);
                            
                        // if (json_last_error() === JSON_ERROR_NONE && is_array($itemLocations)) {
                            $item_store = ErpStore::find($request->item_store[$itemDataKey]);
                            ErpSaleReturnItemLocation::where('sale_return_id', $saleInvoice->id)
                            ->where('sale_return_item_id', $soItem->id)
                            ->delete();
                            $total_item_qty = 0;
                            // foreach ($itemLocations as $itemLocationKey => $itemLocationData) {
                                $total_item_qty += $itemLocationData['store_qty'] ?? $request->item_qty[$itemDataKey];
                                if($total_item_qty <= $soItem->order_qty){
                                //  $itemLocationData && $itemLocationData['store_id']>0 ? $itemLocationData['store_id'] : for reference
                                    ErpSaleReturnItemLocation::create([
                                        'sale_return_id' => $saleInvoice->id,
                                        'sale_return_item_id' => $soItem->id,
                                        'item_id' => $soItem->item_id,
                                        'item_code' => $soItem->item_code,
                                        'store_id' => $item_store->id,
                                        'store_code' => $item_store->store_name ,
                                        'rack_id' =>  null,
                                        'rack_code' => null,
                                        'shelf_id' => null,
                                        'shelf_code' => null,
                                        'bin_id' => null,
                                        'bin_code' => null,
                                        'returned_qty' => $request->item_qty[$itemDataKey],
                                        'inventory_uom_qty' => ItemHelper::convertToBaseUom($itemDataValue['item_id'], $itemDataValue['uom_id'],$request->item_qty[$itemDataKey]) ?? 0
                                    ]);
                                }
                                else{
                                    return response()->json([
                                        'message' => '',
                                        'errors' => ['item_qty.'.$itemDataKey => 'Item Store has invalid item quantity'],
                                    ], 422);
                                }
                            // }
                        // } else {
                        //     return response()->json([
                        //         'message' => 'Item No. ' . ($itemDataKey + 1) . ' has invalid store locations',
                        //         'error' => ''
                        //     ], 422);
                        // }
                    }

                    // //Media
                    if ($request->hasFile('attachments')) {
                        foreach ($request->file('attachments') as $singleFile) {
                            $mediaFiles = $saleInvoice->uploadDocuments($singleFile, 'sale_order', false);
                        }
                    }
                    if (($request->type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS || $request->type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)) {
                        //Update Inventory Stock Settlement
                    }
                    // InventoryHelper::settlementOfInventoryAndStock($saleInvoice->id, $soItem->id, 'invoice', $request->document_status ?? ConstantHelper::DRAFT);

                    $lotCheck =[];
                    // Handle Lot Data
                    if (isset($request->batch_details[$itemKey])) {
                        $itemLots = $request->batch_details[$itemKey];

                        // If it's a string, decode it
                        if (is_string($itemLots)) {
                            $lotArray = json_decode($itemLots, true);
                            if (json_last_error() !== JSON_ERROR_NONE || !is_array($lotArray)) {
                                DB::rollBack();
                                return response()->json([
                                    'message' => 'Invalid lot details format for item No. ' . ($itemKey + 1),
                                    'error' => ''
                                ], 422);
                            }
                        } else {
                            $lotArray = is_array($itemLots) ? $itemLots : [];
                        }

                        $currentYear = (int)date('Y');
                        $today = date('Y-m-d');
                        $totalLotQty = 0;

                        foreach ($lotArray as $lot) {
                            $manufacturingYear = (int)($lot['manufacturing_year'] ?? 0);
                            $expiryDate = $lot['expiry_date'] ?? null;
                            $lotQty = (float)($lot['lot_qty'] ?? 0);

                            // Add to total
                            $totalLotQty += $lotQty;

                            // Manufacturing year validation
                            if ($manufacturingYear !== 0 && ($manufacturingYear < 2000 || $manufacturingYear > $currentYear)) {
                                DB::rollBack();
                                return response()->json([
                                    'message' => 'Manufacturing year for item No. ' . ($itemKey + 1) . ' must be between 2000 and ' . $currentYear . ', or zero.',
                                    'error' => ''
                                ], 422);
                            }

                            // Expiry date validation
                            if ($expiryDate && $expiryDate < $today) {
                                DB::rollBack();
                                return response()->json([
                                    'message' => 'Lot expiry date for item No. ' . ($itemKey + 1) . ' must be today or a future date.',
                                    'error' => ''
                                ], 422);
                            }

                            // Save / update lot
                            ErpSrItemLotDetail::updateOrCreate(
                                [
                                    'sr_item_id' => $soItem->id,
                                    'lot_number' => $lot['lot_number'],
                                ],
                                [
                                    'lot_qty' => $lotQty,
                                    'manufacturing_year' => $manufacturingYear > 0 ? $manufacturingYear : null,
                                    'expiry_date' => $expiryDate ?? null,
                                    'total_lot_qty' => $lot['total_lot_qty'] ?? $lotQty,
                                    'inventory_uom_qty' => ItemHelper::convertToBaseUom($soItem->item_id, $soItem->uom_id, $lotQty),
                                    'original_receipt_date' => $lot['original_receipt_date'] ?? $soItem->header->document_date,
                                ]
                            );
                        }

                        // ✅ Validate after loop
                        if (round($totalLotQty, 6) != round($soItem->order_qty, 6)) {
                            $lotCheck['item_qty_'.$itemKey] = 'Total lot quantity must equal to Returned quantity';
                        }
                    } else {
                        // If lots missing but required, throw error
                        if ((isset($item->is_batch_no) && $item->is_batch_no == "1") ||
                            (isset($item['is_batch_no']) && $item['is_batch_no'] == "1") || $saleInvoice-> reference -> document_type !== ConstantHelper::SI_SERVICE_ALIAS) {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'Please provide lot details for item No. ' . ($itemKey + 1),
                                'error' => ''
                            ], 422);
                        } else {
                            // Auto-generate lot
                            // $lot_number = date('Y/M/d', strtotime($saleInvoice->document_date)) . '/' . $saleInvoice->book_code . '/' . $saleInvoice->document_number;
                            // ErpSrItemLotDetail::updateOrCreate(
                            //     [
                            //         'sr_item_id' => $soItem->id,
                            //         'lot_number' => strtoupper($lot_number),
                            //     ],
                            //     [
                            //         'lot_qty' => $soItem->order_qty,
                            //         'manufacturing_year' => null,
                            //         'expiry_date' => null,
                            //         'total_lot_qty' => $soItem->order_qty,
                            //         'inventory_uom_qty' => ItemHelper::convertToBaseUom($soItem->item_id, $soItem->uom_id, $soItem->order_qty),
                            //         'original_receipt_date' => $soItem->header->document_date,
                            //     ]
                            // );
                        }
                    }
                    if(count($lotCheck))
                    {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'Total lot quantity must equal to Returned quantity ',
                            'errors' => $lotCheck,
                        ], 422);
                    }
                }

            } else {
                DB::rollBack();
                return response()->json([
                    'message' => 'Please select Items',
                    'error' => "",
                ], 422);
            }

            //Header TED (Discount)
            if (isset($request->order_discount_value) && count($request->order_discount_value) > 0) {
                foreach ($request->order_discount_value as $orderDiscountKey => $orderDiscountVal) {
                    $headerDiscountRowData = [
                        'sale_return_id' => $saleInvoice->id,
                        'sale_return_item_id' => null,
                        'ted_type' => 'Discount',
                        'ted_level' => 'H',
                        'ted_id' => isset($request->order_discount_master_id[$orderDiscountKey]) ? $request->order_discount_master_id[$orderDiscountKey] : null,
                        'ted_name' => isset($request->order_discount_name[$orderDiscountKey]) ? $request->order_discount_name[$orderDiscountKey] : null,
                        'assessment_amount' => $totalItemValueAfterDiscount,
                        'ted_percentage' => isset($request->order_discount_percentage[$orderDiscountKey]) ? ($request->order_discount_percentage[$orderDiscountKey]) : null,
                        'ted_amount' => $orderDiscountVal,
                        'applicable_type' => 'Deduction',
                    ];
                    if (isset($request->order_discount_id[$orderDiscountKey])) {
                        ErpSaleReturnTed::updateOrCreate(['id' => $request->order_discount_id[$orderDiscountKey]], $headerDiscountRowData);
                    } else {
                        ErpSaleReturnTed::create($headerDiscountRowData);
                    }
                    // ErpSaleInvoiceTed::create([
                    //     'sale_invoice_id' => $saleInvoice -> id,
                    //     'invoice_item_id' => null,
                    //     'ted_type' => 'Discount',
                    //     'ted_level' => 'H',
                    //     'ted_id' => null,
                    //     'ted_name' => isset($request -> order_discount_name[$orderDiscountKey]) ? $request -> order_discount_name[$orderDiscountKey] : null,
                    //     'assessment_amount' => $totalItemValueAfterDiscount,
                    //     'ted_percentage' => $orderDiscountVal / $totalItemValueAfterDiscount * 100 ,
                    //     'ted_amount' => $orderDiscountVal,
                    //     'applicable_type' => 'Deduction',
                    // ]);
                }
            }
            //Header TED (Expense)
            $totalValueAfterTax = $totalItemValueAfterDiscount + $totalTax;
            $totalExpenseAmount = 0;
            if (isset($request->order_expense_value) && count($request->order_expense_value) > 0) {
                foreach ($request->order_expense_value as $orderExpenseKey => $orderExpenseVal) {
                    $headerExpenseRowData = [
                        'sale_return_id' => $saleInvoice->id,
                        'sale_return_item_id' => null,
                        'ted_type' => 'Expense',
                        'ted_level' => 'H',
                        'ted_id' => isset($request->order_expense_master_id[$orderExpenseKey]) ? $request->order_expense_master_id[$orderExpenseKey] : null,
                        'ted_name' => isset($request->order_expense_name[$orderExpenseKey]) ? $request->order_expense_name[$orderExpenseKey] : null,
                        'assessment_amount' => $totalItemValueAfterDiscount,
                        'ted_percentage' => isset($request->order_expense_percentage[$orderExpenseKey]) ? $request->order_expense_percentage[$orderExpenseKey] : null, // Need to change
                        'ted_amount' => $orderExpenseVal,
                        'applicable_type' => 'Collection',
                    ];
                    if (isset($request->order_expense_id[$orderExpenseKey])) {
                        ErpSaleReturnTed::updateOrCreate(['id' => $request->order_expense_id[$orderExpenseKey]], $headerExpenseRowData);
                    } else {
                        ErpSaleReturnTed::create($headerExpenseRowData);
                    }

                    // ErpSaleInvoiceTed::create([
                    //     'sale_invoice_id' => $saleInvoice -> id,
                    //     'invoice_item_id' => null,
                    //     'ted_type' => 'Expense',
                    //     'ted_level' => 'H',
                    //     'ted_id' => null,
                    //     'ted_name' => isset($request -> order_expense_name[$orderExpenseKey]) ? $request -> order_expense_name[$orderExpenseKey] : null,
                    //     'assessment_amount' => $totalItemValueAfterDiscount,
                    //     'ted_percentage' => $orderExpenseVal / $totalValueAfterTax * 100 , // Need to change
                    //     'ted_amount' => $orderExpenseVal,
                    //     'applicable_type' => 'Collection',
                    // ]);
                    $totalExpenseAmount += $orderExpenseVal;
                }
            }
            $invTcsAssessAmt = 0;
            $returnedTcsAssessAmt = 0;
            $balanceTcsAssessAmt = 0;
            $tcsQuery = null;
            //Sale Return TCS Tax
            if (in_array($saleInvoice->reference_doc_type, [
                ConstantHelper::SI_SERVICE_ALIAS,
                ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS
            ])) {
                $totalTaxableValue = ($itemTotalValue - ($totalHeaderDiscount + $itemTotalDiscount));

                // Invoice TCS total
                $invTcsAssess = $saleInvoice->reference->header_tax()
                    ->where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS)->first();
                $invTcsAssessAmt = $invTcsAssess?->assessment_amount ?? 0;
                $ted_perc = $invTcsAssess?->ted_percentage ?? 0;
                $ted_id = $invTcsAssess?->ted_id ?? null;
                $applicable_type = $invTcsAssess?->applicable_type ?? 'Collection';
                // Returned TCS excluding current one (if editing)
                $returnedTcsAssessAmt = ErpSaleReturn::where('reference_id', $saleInvoice->reference_id)
                    ->where('reference_doc_type', $saleInvoice->reference_doc_type)
                    ->when($request->sale_return_id, fn($q) => $q->where('id', '!=', $request->sale_return_id))
                    ->with(['header_tax' => fn($q) => $q->where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS)])
                    ->get()
                    ->pluck('header_tax')
                    ->flatten()
                    ->sum('assessment_amount') ?? 0;

                $balanceTcsAssessAmt = $invTcsAssessAmt - $returnedTcsAssessAmt;
                if ($balanceTcsAssessAmt > 0) {
                    $newTcsAssessableAmt = min($totalTaxableValue, $balanceTcsAssessAmt);
                    $tcsQuery = ErpSaleReturnTed::updateOrCreate(
                        [
                            'sale_return_id' => $saleInvoice->id,
                            'sale_return_item_id' => null,
                            'ted_type' => "Tax",
                            'ted_level' => 'H',
                            'ted_id' => $ted_id,
                        ],
                        [
                            'ted_name' => ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS,
                            'assessment_amount' => $newTcsAssessableAmt,
                            'ted_percentage' => $ted_perc,
                            'ted_amount' => ($ted_perc / 100 * $newTcsAssessableAmt),
                            'applicable_type' => $applicable_type,
                        ]
                    );
                } else {
                    // No TCS applicable, remove if exists
                    $tcsQuery = ErpSaleReturnTed::where([
                        'sale_return_id' => $saleInvoice->id,
                        'sale_return_item_id' => null,
                        'ted_type' => ConstantHelper::TCS,
                        'ted_level' => 'H',
                        'ted_id' => $ted_id,
                    ])->delete();
                }
                if(isset($tcsQuery) && $tcsQuery instanceof ErpSaleReturnTed)
                {
                    $tcsQuery ->save();
                }
            }

            if ($itemTotalValue - ($totalHeaderDiscount + $itemTotalDiscount) + $totalExpenseAmount < 0) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document Value cannot be less than 0'
                ], 422);
            }
            $saleInvoice->total_discount_value = $totalHeaderDiscount + $itemTotalDiscount;
            $saleInvoice->total_return_value = $itemTotalValue;
            $saleInvoice->total_tax_value = $totalTax;
            $saleInvoice->total_expense_value = $totalExpenseAmount;
            $saleInvoice->total_amount = ($itemTotalValue - ($totalHeaderDiscount + $itemTotalDiscount)) + $totalTax + $totalExpenseAmount;
            $saleInvoice->save();
            //Approval check
            if (in_array($saleInvoice -> reference_doc_type, [ConstantHelper::SI_SERVICE_ALIAS, ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS])) {
                $saleInvoice -> refresh();
                $currentTcsAssessableAmt = $saleInvoice -> header_tax() -> where('ted_name', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) -> first() ?-> assessment_amount ?? 0;
                $currentNonTcsAssessableAmt = ($saleInvoice -> total_return_value - $saleInvoice -> total_discount_value) - $currentTcsAssessableAmt - ($request->sale_return_id ? $tcsAccesableAmt : 0);
                $currentNonTcsAssessableAmt = ($currentNonTcsAssessableAmt)* -1;
                TaxHelper::buildTaxThresholdUtilization($saleInvoice, 'sale', ConstantHelper::TCS, ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS, $currentNonTcsAssessableAmt);
            }
            if ($request->sale_return_id) { //Update condition
                $bookId = $saleInvoice->book_id;
                $docId = $saleInvoice->id;
                $amendRemarks = $request->amend_remarks ?? null;
                $remarks = $saleInvoice->remarks;
                $amendAttachments = $request->file('amend_attachments');
                $attachments = $request->file('attachment');
                $currentLevel = $saleInvoice->approval_level;
                $modelName = get_class($saleInvoice);
                $actionType = $request->action_type ?? "";
                if (($saleInvoice->document_status == ConstantHelper::APPROVED || $saleInvoice->document_status == ConstantHelper::APPROVAL_NOT_REQUIRED) && $actionType == 'amendment') {
                    //*amendmemnt document log*/
                    $revisionNumber = $saleInvoice->revision_number + 1;
                    $actionType = 'amendment';
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $amendRemarks, $amendAttachments, $currentLevel, $actionType, 0, $modelName);
                    $saleInvoice->revision_number = $revisionNumber;
                    $saleInvoice->approval_level = 1;
                    $saleInvoice->revision_date = now();
                    $amendAfterStatus = $approveDocument['approvalStatus'] ?? $saleInvoice -> document_status;
                    $saleInvoice->document_status = $amendAfterStatus;
                    $saleInvoice->save();

                } else {
                    if ($request->document_status == ConstantHelper::SUBMITTED) {
                        $revisionNumber = $saleInvoice->revision_number ?? 0;
                        $actionType = 'submit';
                        $totalValue = $saleInvoice -> total_amount ?? 0;
                        $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber , $remarks, $attachments, $currentLevel, $actionType, 0, $modelName);
                        if ($approveDocument['message']) {
                            DB::rollBack();
                            return response()->json([
                                'message' => $approveDocument['message'],
                                'error' => "",
                            ], 422);
                        }
                        $document_status = $approveDocument['approvalStatus'] ?? $saleInvoice -> document_status;
                        $saleInvoice->document_status = $document_status;
                    } else {
                        $saleInvoice->document_status = $request->document_status ?? ConstantHelper::DRAFT;
                    }
                }

            } else { //Create condition
                if ($request->document_status == ConstantHelper::SUBMITTED) {
                    $bookId = $saleInvoice->book_id;
                    $docId = $saleInvoice->id;
                    $remarks = $saleInvoice->remarks;
                    $attachments = $request->file('attachment');
                    $currentLevel = $saleInvoice->approval_level;
                    $revisionNumber = $saleInvoice->revision_number ?? 0;
                    $actionType = 'submit'; // Approve // reject // submit
                    $modelName = get_class($saleInvoice);
                    $totalValue = $saleInvoice->total_amount ?? 0;
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber , $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                    $saleInvoice->document_status = $approveDocument['approvalStatus'] ?? $saleInvoice->document_status;
                }
                $saleInvoice -> save();
            }
            $saleInvoice -> document_type = isset($request -> type) && in_array($request -> type, ConstantHelper::SALE_INVOICE_DOC_TYPES) ? $request -> type : ConstantHelper::SI_SERVICE_ALIAS;
            $saleInvoice -> save();
            //Media
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $singleFile) {
                    $mediaFiles = $saleInvoice->uploadDocuments($singleFile, 'sale_return', false);
                }
            }

            $saleInvoice->document_type = isset($request->type) && in_array($request->type, ConstantHelper::SALE_RETURN_DOC_TYPES) ? $request->type : ConstantHelper::SR_SERVICE_ALIAS;
            $saleInvoice->save();
            //Logs
            // if ($request->document_status == ConstantHelper::SUBMITTED) {
            //     $bookId = $saleInvoice->book_id; 
            //     $docId = $saleInvoice->id;
            //     $remarks = $saleInvoice->remarks;
            //     $attachments = null;
            //     $currentLevel = $saleInvoice->approval_level;
            //     $revisionNumber = $saleInvoice->revision_number ?? 0;
            //     $actionType = 'submit'; // Approve // reject // submit
            //     $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber , $remarks, $attachments, $currentLevel, $actionType);
            // }
            if($saleInvoice && $saleInvoice-> reference -> document_type !== ConstantHelper::SI_SERVICE_ALIAS){
                $invoiceLedger = self::maintainStockLedger($saleInvoice);
            }
            $gstInvoiceType = EInvoiceHelper::getGstInvoiceType($saleInvoice -> customer_id, $saleInvoice ?->shipping_address_details  ?-> country_id, $saleInvoice -> location_address_details ?-> country_id);
                if ($saleInvoice -> document_status === ConstantHelper::POSTED){
                    if ($gstInvoiceType === EInvoiceHelper::B2B_INVOICE_TYPE) {
                        SaleModuleHelper::updateEInvoiceDataFromHelper($saleInvoice);
                        $data = EInvoiceHelper::saveGstIn($saleInvoice);
                        if (isset($data) && $data['status'] == 'error') {
                            DB::rollBack();
                            return response()->json([
                                'message' => $data['message'],
                                'error' => $data['message'],
                            ], 500);
                        } else {
                            $saleInvoice->e_invoice_status=ConstantHelper::GENERATED;
                            $saleInvoice->save();
                        }
                    }
                }
            $saleInvoice -> e_invoice_status = EInvoiceHelper::getEInvoicePendingDocumentStatus($saleInvoice, $saleInvoice -> gst_invoice_type);
            $saleInvoice -> save();
            DB::commit();
            $module = "Sales Return";
            if ($request->type == ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS) {
                $module = "Delivery Return";
            } else if ($request->type == ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS) {
                $module = "DN Cum Return";
            }

            return response()->json([
                'message' => $module . " created successfully",
                'redirect_url' => route('sale.return.index', ['type' => $request->type ?? ConstantHelper::SR_SERVICE_ALIAS])
            ]);


        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $ex->getMessage() . $ex->getFile() . $ex->getLine(),
            ], 500);
        }
    }
    
    public static function BundleRemoval($saleReturn)
    {
        $bundleItems = $saleReturn->whereIn('reference_doc_type',[ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS,ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS])->items;
        foreach($bundleItems as $bundleItem)
        {
            if(isset($bundleItem->invoice_item->bundles) && count($bundleItem->invoice_item->bundles) > 0)
            {
                foreach($bundleItem->invoice_item->bundles as $bundle)
                {
                    if($bundle)
                    {
                        $bundle -> dn_item_id = null;
                        $bundle -> save();
                    }
                }
            }
        }

    }

    public function amendmentSubmit(Request $request, $id)
    {
        DB::beginTransaction();
        try {

            $saleInvoice = ErpSaleReturn::where('id', $id)->first();
            if (!$saleInvoice) {
                return response()->json(['data' => [], 'message' => "Sale Return not found.", 'status' => 404]);
            }

            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'ErpSaleReturn', 'relation_column' => ''],
                ['model_type' => 'detail', 'model_name' => 'ErpSaleReturnItem', 'relation_column' => 'sale_return_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnItemAttribute', 'relation_column' => 'sale_return_item_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnItemLocation', 'relation_column' => 'return_item_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleReturnTed', 'relation_column' => 'return_item_id'],
            ];

            $a = Helper::documentAmendment($revisionData, $id);
            if ($a) {
                //*amendmemnt document log*/
                $bookId = $saleInvoice->book_id;
                $docId = $saleInvoice->id;
                $remarks = 'Amendment';
                $attachments = $request->file('attachment');
                $currentLevel = $saleInvoice->approval_level;
                $revisionNumber = $saleInvoice->revision_number;
                $actionType = 'amendment'; // Approve // reject // submit // amend
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType);


                $saleInvoice->document_status = ConstantHelper::DRAFT;
                $saleInvoice->revision_number = $saleInvoice->revision_number + 1;
                $saleInvoice->approval_level = 1;
                $saleInvoice->revision_date = now();
                $saleInvoice->save();
            }

            DB::commit();
            return response()->json(['data' => [], 'message' => "Amendment done!", 'status' => 200]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Amendment Submit Error: ' . $e->getMessage());
            return response()->json(['data' => [], 'message' => "An unexpected error occurred. Please try again.", 'error' => $e->getMessage(), 'status' => 500]);
        }
    }

    //Function to get all items of sales module depending upon the doc type - order , invoice, delivery note
    public function getInvoiceItemsForPulling(Request $request)
    {
        try {
            $selectedIds = $request->selected_ids ?? [];
            $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($request->header_book_id);
            $query = null;
            if ($request->doc_type === ConstantHelper::SI_SERVICE_ALIAS) {
                $referedHeaderId = ErpInvoiceItem::whereIn('id', $selectedIds)?->first()?->header?->id;

                $query = ErpInvoiceItem::with(['attributes', 'uom', 'header.customer', 'header.shipping_address_details'])
                    ->whereHas('header', function ($subQuery) use ($request, $applicableBookIds, $referedHeaderId) {
                        $subQuery->when($referedHeaderId, fn($refQuery) => $refQuery->where('id', $referedHeaderId))
                            ->where('document_type', ConstantHelper::SI_SERVICE_ALIAS)
                            ->where(function ($q) {
                                $q->whereDoesntHave('pendingJob') // ✅ allow if no job exists
                                ->orWhereHas('pendingJob', function ($jobQuery) {
                                    $jobQuery->where('status', '!=', 'pending'); // ✅ allow if job exists but not pending
                                });
                            })
                            ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::POSTED])
                            ->whereIn('book_id', $applicableBookIds)
                            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
                            ->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id))
                            ->when($request->document_id, fn($q) => $q->where('id', $request->document_id))
                            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                            ->when($request->sub_store_id, fn($q) => $q->where('sub_store_id', $request->sub_store_id));
                    })
                    ->when(count($selectedIds) > 0, fn($q) => $q->whereNotIn('id', $selectedIds))
                    ->whereColumn('inv_srn_qty', '<', 'order_qty');

            } elseif ($request->doc_type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS) {
                $referedHeaderId = ErpInvoiceItem::whereIn('id', $selectedIds)?->first()?->header?->id;

                $query = ErpInvoiceItem::with(['attributes', 'uom', 'header.customer', 'header.shipping_address_details'])
                    ->whereHas('header', function ($subQuery) use ($request, $applicableBookIds, $referedHeaderId) {
                        $subQuery->when($referedHeaderId, fn($refQuery) => $refQuery->where('id', $referedHeaderId))
                            ->whereIn('document_type', [ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS])
                            ->where(function ($q) {
                                $q->whereDoesntHave('pendingJob') // ✅ allow if no job exists
                                ->orWhereHas('pendingJob', function ($jobQuery) {
                                    $jobQuery->where('status', '!=', 'pending'); // ✅ allow if job exists but not pending
                                });
                            })

                            ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::POSTED])
                            ->whereIn('book_id', $applicableBookIds)
                            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
                            ->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id))
                            ->when($request->document_id, fn($q) => $q->where('id', $request->document_id))
                            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                            ->when($request->sub_store_id, fn($q) => $q->where('sub_store_id', $request->sub_store_id));
                    })
                    ->when(count($selectedIds) > 0, fn($q) => $q->whereNotIn('id', $selectedIds))
                    ->whereRaw('(order_qty - (srn_qty + invoice_qty - inv_srn_qty)) > 0');
            } elseif ($request->doc_type === ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS) {
                $referedHeaderId = ErpInvoiceItem::whereIn('id', $selectedIds)?->first()?->header?->id;

                $query = ErpInvoiceItem::with(['attributes', 'uom', 'header.customer', 'header.shipping_address_details'])
                    ->whereHas('header', function ($subQuery) use ($request, $applicableBookIds, $referedHeaderId) {
                        $subQuery->when($referedHeaderId, fn($refQuery) => $refQuery->where('id', $referedHeaderId))
                            ->whereIn('document_type', [ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS])
                            ->where(function ($q) {
                                $q->whereDoesntHave('pendingJob') // ✅ allow if no job exists
                                ->orWhereHas('pendingJob', function ($jobQuery) {
                                    $jobQuery->where('status', '!=', 'pending'); // ✅ allow if job exists but not pending
                                });
                            })

                            ->whereIn('document_status', [ConstantHelper::APPROVED, ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::POSTED])
                            ->whereIn('book_id', $applicableBookIds)
                            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
                            ->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id))
                            ->when($request->document_id, fn($q) => $q->where('id', $request->document_id))
                            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
                            ->when($request->sub_store_id, fn($q) => $q->where('sub_store_id', $request->sub_store_id));
                    })
                    ->when(count($selectedIds) > 0, fn($q) => $q->whereNotIn('id', $selectedIds))
                    ->whereRaw('(order_qty - (srn_qty + invoice_qty - inv_srn_qty)) > 0');
            }

            if ($request->item_id && isset($query) && $request->doc_type !== ConstantHelper::LAND_LEASE) {
                $query = $query->where('item_id', $request->item_id);
            }
            if (!$query) {
                return DataTables::of(collect([]))->make(true);
            }

            return DataTables::of($query)
                ->addColumn('book_code', fn($item) => $item?->header?->book_code ?? ($item?->header?->book?->book_code ?? ''))
                ->addColumn('document_number', fn($item) => $item?->header?->document_number ?? '')
                ->addColumn('document_date', fn($item) =>
                    isset($item->header) && method_exists($item->header, 'getFormattedDate')
                        ? $item->header->getFormattedDate("document_date")
                        : ''
                )
                ->addColumn('customer_code', fn($item) => $item?->header?->customer?->customer_code ?? '')
                ->addColumn('customer_name', fn($item) => $item?->header?->customer?->company_name ?? '')

                // Item info
                ->addColumn('item_code', fn($item) => $item?->item?->item_code ?? '')
                ->addColumn('item_name', fn($item) => $item?->item?->item_name ?? '')
                ->addColumn('uom_name', fn($item) => $item?->uom?->name ?? '')

                // Qty / Rate with formatting
                ->editColumn('order_qty', fn($item) => number_format($item->order_qty ?? 0, 2))
                ->editColumn('srn_qty', fn($item) => number_format($item->return_balance_qty ?? 0, 2))
                ->editColumn('dnote_qty', fn($item) => number_format($item->order_qty ?? 0, 2))
                ->editColumn('rate', fn($item) => number_format($item->rate ?? 0, 2))

                // Balance qty logic
                ->addColumn('balance_qty', function ($item) use ($request) {
                    if ($request->doc_type === ConstantHelper::SI_SERVICE_ALIAS) {
                        return number_format($item->return_balance_qty ?? 0, 6);
                    }
                    return number_format(($item->invoice_qty ?? 0) - ($item->srn_qty ?? 0), 2);
                })

                // Attributes array (raw values for JSON)
                ->addColumn('attributes_array', fn($item) =>
                    $item->attributes->map(fn($attr) => [
                        'attribute_name' => $attr->attribute_name,
                        'attribute_value' => $attr->attribute_value,
                    ])->values()
                )

                // Attributes UI (badges)
                ->addColumn('attributes_data', function ($item) {
                    $attributesUI = '';
                    foreach ($item->attributes as $attr) {
                        $attributesUI .= "<span class='badge rounded-pill badge-light-primary'>{$attr->attribute_name} : {$attr->attribute_value}</span> ";
                    }
                    return $attributesUI;
                })

                // Stock qty (if model has method)
                ->addColumn('stock_qty', function ($item) use ($request) {
                    return method_exists($item, 'getStockBalanceQty')
                        ? $item->getStockBalanceQty($request->store_id ?? 0, $request->sub_store_id ?? 0)
                        : 0;
                })

                // Check stock flag
                ->addColumn('check_stock', fn() => "yes")

                // Sale order reference (if any)
                ->addColumn('sale_order', function ($item) {
                    return [
                        'book_code' => $item?->sale_order?->book_code,
                        'document_number' => $item?->sale_order?->document_number,
                        'document_date'   => isset($item->sale_order) && method_exists($item->sale_order, 'getFormattedDate')
                            ? $item->sale_order->getFormattedDate("document_date")
                            : '',
                        'customer_code'   => $item?->sale_order?->customer?->customer_code,
                        'so_item_ids' => $item?->items && $item->items->isNotEmpty()
                            ? $item->items->pluck('so_item_id')->toArray()
                            : ($item?->so_item_id ? [$item->so_item_id] : [$item->id]),
                    ];
                })

                ->rawColumns(['attributes_data'])
                ->make(true);


        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Some internal error occurred',
                'error' => $ex->getMessage()
            ]);
        }
    }


    //Function to get all items of sales module depending upon the doc type - order , invoice, delivery note
    public function processPulledItems(Request $request)
    {
        try {
            $modelName = null;
            $headers = [];
            if ($request->doc_type === ConstantHelper::SR_SERVICE_ALIAS) {
                $modelName = resolve("App\\Models\\ErpSaleReturn");
            } else if ($request->doc_type === ConstantHelper::SI_SERVICE_ALIAS || $request->doc_type === ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS) {
                $modelName = resolve("App\\Models\\ErpSaleInvoice");
            } else {
                $modelName = null;
            }
            if (isset($modelName)) {
                $headers = $modelName::with(['discount_ted', 'expense_ted', 'header_tax' ,  'billing_address_details', 'shipping_address_details','location_address_details'])->with('customer', function ($sQuery) {
                    $sQuery->with(['currency', 'payment_terms']);
                })->whereHas('items', function ($subQuery) use ($request) {
                    $subQuery->whereIn('id', $request->items_id);
                })->with('items', function ($itemQuery) use ($request) {
                    $itemQuery->whereIn('id', $request->items_id)->with(['discount_ted', 'tax_ted'])->with([
                        'item' => function ($itemQuery) {
                            $itemQuery->with(['specifications', 'alternateUoms.uom', 'uom', 'hsn']);
                        }
                    ]);
                })->whereIn('id', $request->order_id)->get();
                foreach ($headers as $header) {
                    if ($modelName::class == "App\\Models\\ErpSaleInvoice") {
                        $saleOrderItems = $header->sale_order_items();
                        // dd($saleOrderItems);
                        foreach ($saleOrderItems as &$saleOrderItem) {
                            $saleOrderItem->actual_qty = $saleOrderItem->order_qty;
                        }
                    }
                    foreach ($header->items as $orderItemKey => &$orderItem) {
                       $orderItem->stock_qty = $orderItem->getStockBalanceQty();
                        if($header->document_type == ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)
                        {
                            if(isset($orderItem -> dnoteItem))
                            {
                                $lotdata = InventoryHelper::getIssueTransactionLotNumbers($header->document_type, $orderItem?->dnoteItem?->header->id, $orderItem->dnote_item_id,$orderItem->uom_id);
                            }
                            else
                            {
                                $lotdata = null;
                            }
                        }
                        else{
                                $lotdata = InventoryHelper::getIssueTransactionLotNumbers(ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS, $header?->id, $orderItem?->id,$orderItem->uom_id);
                        }
                        $orderItem->lotdata =$lotdata;
                        $orderItem->item_attributes_array = $orderItem->item_attributes_array();
                        // if (isset($saleOrderItems[$orderItemKey])) {
                        //     $header->items[$orderItemKey] = $saleOrderItems[$orderItemKey];
                        //     $header->items[$orderItemKey]->item_attributes_array = $orderItem->item_attributes_array();
                        // /
                    }
                }
            }
            return response()->json([
                'message' => 'Data found',
                'data' => $headers
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Some internal error occurred',
                'error' => $ex
            ]);
        }
    }

    // genrate pdf
    public function generatePdf(Request $request, $id, $pattern)
    {
        $user = request()->user();
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $organizationAddress = Address::with(['city', 'state', 'country'])
            ->where('addressable_id', $user->organization_id)
            ->where('addressable_type', Organization::class)
            ->first();
        $order = ErpSaleReturn::with(
            [
                'customer',
                'currency',
                'discount_ted',
                'expense_ted',
                'billing_address_details',
                'shipping_address_details'
            ]
        )
            ->with('items', function ($query) {
                $query->with('discount_ted', 'tax_ted', 'item_locations')->with([
                    'item' => function ($itemQuery) {
                        $itemQuery->with(['specifications', 'alternateUoms.uom', 'uom']);
                    }
                ]);
            })
            ->find($id);
        // $creator = AuthUser::with(['authUser'])->find($order->created_by);
        // dd($creator,$order->created_by);
        $shippingAddress = $order->shipping_address_details;
        $billingAddress = $order->billing_address_details;

        $approvedBy = Helper::getDocStatusUser(get_class($order), $order -> id, $order -> document_status);

        // dd($user);
        // $type = ConstantHelper::SERVICE_LABEL[$order->document_type];
        $totalItemValue = $order->total_return_value ?? 0.00;
        $totalDiscount = $order->total_discount_value ?? 0.00;
        $totalTaxes = $order->total_tax_value ?? 0.00;
        $totalTaxableValue = ($totalItemValue - $totalDiscount);
        $totalAfterTax = ($totalTaxableValue + $totalTaxes);
        $totalExpense = $order->total_expense_value ?? 0.00;
        $totalAmount = ($totalAfterTax + $totalExpense);
        $amountInWords = NumberHelper::convertAmountToWords($totalAmount);
        // $storeAddress = ErpStore::with('address')->where('id',$order->store_id)->get();
        // dd($order->location->address);
        // Path to your image (ensure the file exists and is accessible)
        $approvedBy = Helper::getDocStatusUser(get_class($order), $order -> id, $order -> document_status);
        $imagePath = public_path('assets/css/midc-logo.jpg'); // Store the image in the public directory

        $eInvoice = $order->irnDetail()->first();
        $qrCodeBase64 = null;
        if (isset($eInvoice)) {
            $qrCodeBase64 = EInvoiceHelper::generateQRCodeBase64($eInvoice->signed_qr_code);
        }

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $hsnSummary = $order->items->groupBy(fn($item) => $item->hsn?->code)->map(function($items, $hsn) {
            $taxableValue = $items->sum(fn($i) =>
                ($i->order_qty * $i->rate) - $i->item_discount_amount - $i->header_discount_amount
            );
            $cgst = $items->sum(fn($i) => floatval($i->cgst_value['value'] ?? 0));
            $sgst = $items->sum(fn($i) => floatval($i->sgst_value['value'] ?? 0));
            $igst = $items->sum(fn($i) => floatval($i->igst_value['value'] ?? 0));

            return [
                'hsn'           => $hsn,
                'taxable_value' => $taxableValue,
                'cgst'          => $cgst,
                'sgst'          => $sgst,
                'igst'          => $igst,
                'total_tax'     => $cgst + $sgst + $igst,
            ];
        });

        $data_array = [
            'type' => $pattern,
            'order' => $order,
            'user' => $user,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'organization' => $organization,
            'amountInWords' => $amountInWords,
            'organizationAddress' => $organizationAddress,
            'totalItemValue' => $totalItemValue,
            'totalDiscount' => $totalDiscount,
            'totalTaxes' => $totalTaxes,
            'totalTaxableValue' => $totalTaxableValue,
            'totalAfterTax' => $totalAfterTax,
            'totalExpense' => $totalExpense,
            'totalAmount' => $totalAmount,
            'imagePath' => $imagePath,
            'approvedBy' => $approvedBy,
            'eInvoice' => $eInvoice,
            'hsnSummary' => $hsnSummary,
            'qrCodeBase64' => $qrCodeBase64,
        ];
        $pdfViewFile = 'pdf.sales-document';
        if ($pattern === 'Credit Note') {
            $pdfViewFile = 'pdf.sales-return-pdf';
        }
        $html = view($pdfViewFile,
            $data_array
        )->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $fileName = ($order->book_code . '-' . $order -> document_number);

        $pdfPath = 'sale-returns/pdfs/return_' . $fileName . '.pdf';
        Storage::disk('local')->put($pdfPath, $dompdf->output());

        return response($dompdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Einvoice_' .$fileName . '.pdf"');
    }

    public function getPostingDetails(Request $request)
    {
        try {
            $data = FinancialPostingHelper::financeVoucherPosting($request->book_id ?? 0, $request->document_id ?? 0, "get");
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'status' => 'exception',
                'message' => 'Some internal error occured',
                'error' => $ex->getMessage()." ".$ex->getLine()." ".$ex->getFile(),
            ]);
        }
    }

    public function postReturn(Request $request)
    {
        try {
            DB::beginTransaction();
            $saleReturn = ErpSaleReturn::find($request->document_id);
            // $enableEinvoice = $saleReturn -> gst_invoice_type === EInvoiceHelper::B2B_INVOICE_TYPE ? true : false;
            // $eInvoice = $saleReturn?->irnDetail()->first();
            // if (!$eInvoice && $enableEinvoice) {
            //     $data = [
            //         'message' => 'Please generate IRN First.',
            //     ];
            //     DB::rollBack();
            //     return response()->json([
            //         'status' => 'error',
            //         'data' => $data
            //     ], 422);
            // }
            if($saleReturn -> reference -> document_status != ConstantHelper::POSTED)
                {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please Post Referenced Document First',
                    ],422);
                } 
            $data = FinancialPostingHelper::financeVoucherPosting($request->book_id ?? 0, $request->document_id ?? 0, "post");
            if ($data['status']) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'status' => 'exception',
                'message' => 'Some internal error occured',
                'error' => $ex->getMessage()
            ]);
        }
    }

    public function revokeSalesReturn(Request $request)
    {
        DB::beginTransaction();
        try {
            $saleDocument = ErpSaleReturn::find($request->id);
            if (isset($saleDocument)) {
                $revoke = Helper::approveDocument($saleDocument->book_id, $saleDocument->id, $saleDocument->revision_number, '', [], 0, ConstantHelper::REVOKE, $saleDocument->total_amount, get_class($saleDocument));
                if ($revoke['message']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $revoke['message'],
                    ]);
                } else {
                    $saleDocument->document_status = $revoke['approvalStatus'];
                    $saleDocument->save();

                }
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Revoked succesfully',
                ]);

            } else {
                DB::rollBack();
                throw new ApiGenericException("No Document found");
            }
        } catch (Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex->getMessage());
        }
    }

    private static function maintainStockLedger($saleReturn)
    {
        $user = request()->user();;
        $detailIds = $saleReturn->items->pluck('id')->toArray();
        InventoryHelper::settlementOfInventoryAndStock($saleReturn->id, $detailIds, ConstantHelper::SR_SERVICE_ALIAS, $saleReturn->document_status);
        return true;
    }
    public function getRacksAndBins(Request $request)
    {
        try {
            $storeData = ErpStore::with(['racks', 'bins'])->find($request->store_id);
    
            if (!$storeData) {
                return response()->json([
                    'message' => 'Store not found',
                    'stores' => [
                        'code' => 404,
                        'message' => 'No store data available',
                        'status' => 'error',
                    ]
                ], 404);
            }
    
            $storeResponse = [
                'id' => $storeData->id,
                'store_code' => $storeData->store_code,
                'store_name' => $storeData->store_name,
                'store_location_type' => $storeData->store_location_type,
                'racks' => $storeData->racks->map(function ($rack) {
                    return [
                        'id' => $rack->id,
                        'rack_code' => $rack->rack_code,
                    ];
                }),
                'bins' => $storeData->bins->map(function ($bin) {
                    return [
                        'id' => $bin->id,
                        'bin_code' => $bin->bin_code,
                    ];
                }),
            ];
    
            return response()->json([
                'message' => 'Store details found',
                'stores' => [
                    'code' => 200,
                    'message' => '',
                    'status' => 'success',
                    'store' => $storeResponse,
                ]
            ]);
    
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Some internal error occurred',
                'error' => $ex->getMessage()
            ], 500);
        }
    }
    public function getShelfs(Request $request)
    {
        try {
            $rack = ErpRack::with('shelfs')->find($request->rack_id);
            if (!$rack) {
                return response()->json([
                    'message' => 'Rack not found',
                    'data' => [
                        'code' => 404,
                        'message' => 'No rack data available',
                        'status' => 'error',
                    ]
                ], 404);
            }
    
            $rackResponse =$rack->shelfs->map(function ($shelf) {
                return [
                    'id' => $shelf->id,
                    'shelf_code' => $shelf->shelf_code,
                ];
            });
    
            return response()->json([
                'message' => 'Rack details found',
                'shelfs' => $rackResponse,
            ]);
    
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Some internal error occurred',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

   public function generateEInvoice(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_no' => [
                    'required',
                    'regex:/^[A-Z]{2}[0-9]{2}[A-Z]{0,3}[0-9]{4}$/'
                ],
                'transporter_mode' => 'required|integer',
                "transporter_name" => [
                   "required",
                   'string'
                ],
            ],
            [
                'vehicle_no.required' => 'Vehicle number is required.',
                'vehicle_no.regex' => 'Vehicle number format is invalid. Example: MH12AB1234.',
                'transporter_mode.required' => 'Transporter mode is required.',
                'transporter_mode.integer' => 'Transporter mode must be an integer.',
                'transporter_name.required' => 'Transporter name is required.',
                'transporter_name.string' => 'Transporter name must be a string.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->messages()->first(),
            ], 422);
        }
        $id = $request -> id;
        try{
            $authUser = Helper::getAuthenticatedUser();
            $documentHeader = ErpSaleReturn::find($id);
            // $documentHeader = SaleModuleHelper::updateEInvoiceDataFromHelper($documentHeader);
            $documentDetails = ErpSaleReturnItem::where('sale_return_id', $id)->get();
            // $generateInvoice = EInvoiceHelper::generateInvoice($documentHeader, $documentDetails);

            $shippingAddress = $documentHeader->billing_address_details;
            $storeAddress = $documentHeader->location_address_details;

            // $gstInvoiceType = EInvoiceHelper::getGstInvoiceType($documentHeader -> vendor_id, $shippingAddress -> country_id, $storeAddress -> country_id, 'vendor');
            // if ($gstInvoiceType === EInvoiceHelper::B2B_INVOICE_TYPE) {
            //     $data = EInvoiceHelper::saveGstIn($documentHeader);
            $gstInvoiceType = MasterIndiaHelper::getGstInvoiceType($documentHeader -> customer_id, $shippingAddress -> country_id, $storeAddress -> country_id, 'customer');
            if ($gstInvoiceType === MasterIndiaHelper::B2B_INVOICE_TYPE) {
                $data = MasterIndiaHelper::saveGstIn($documentHeader, $authUser);
                if (isset($data) && (isset($data['status']) && ($data['status'] == 'error'))) {
                    return response()->json([
                        'status' => 'error',
                        'error' => 'error',
                        'message' => $data['message'],
                    ], 500);
                } else{
                    $transportationMode = EwayBillMaster::find($request->transporter_mode);

                    $documentHeader->transporter_name=$request->transporter_name;
                    $documentHeader->transportation_mode=$transportationMode?->description ?? null;
                    $documentHeader->eway_bill_master_id=$transportationMode?->id ?? null;
                    $documentHeader->vehicle_no=$request->vehicle_no;

                    $documentHeader->e_invoice_status = ConstantHelper::GENERATED;
                    $documentHeader->save();
                    
                    return response() -> json([
                        'status' => 'success',
                        'results' => $data,
                        'message' => 'E-CRN generated succesfully',
                    ]);
                }
            } else{
                return response()->json([
                    'error' => 'error',
                    'message' => 'Not valid for '.$gstInvoiceType,
                ], 500);
            }

        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
     
    public function CreditNoteMail(Request $request)
    {
        $request->validate([
            'email_to' => 'required|email',
        ], [
            'email_to.required' => 'Recipient email is required.',
            'email_to.email' => 'Please enter a valid email address.',
        ]);

        $invoice = ErpSaleReturn::with(['customer'])->find($request->id);
        $customer = $invoice->customer;

        $sendTo = $request->email_to ?? $customer->email;
        $customer->email = $sendTo;

        $title = "Credit Note Generated";
        $pattern = "Credit Note";
        $remarks = $request->remarks ?? null;

        $mail_from = '';
        $mail_from_name = '';
        $cc = $request->cc_to ? implode(',', $request->cc_to) : null;
        $name = $customer->company_name;

        $description = <<<HTML
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-family: Arial, sans-serif;">
            <tr>
                <td>
                    <h2 style="color: #2c3e50;">Your Credit Note</h2>
                    <p style="font-size: 16px; color: #555;">Dear {$name},</p>
                    <p style="font-size: 15px; color: #333;">{$remarks}</p>
                    <p style="font-size: 15px; color: #333;">
                        Please find the attached Credit Note PDF for your reference. You can download and review it at your convenience.
                    </p>
                    <p style="font-size: 15px; color: #333;">
                        If you have any questions or need further assistance, feel free to reach out.
                    </p>
                </td>
            </tr>
        </table>
        HTML;


        $attachments = [];

        // Attach generated credit note PDF
        try {
            $pdfContent = $this->generatePdf(
                $request,
                $request->id,
                $pattern,
            );

            $pdfFileName = "CreditNote_{$invoice->document_number}.pdf";
            $attachments[] = [
                'file' => $pdfContent,
                'options' => [
                    'as' => $pdfFileName,
                    'mime' => 'application/pdf',
                ]
            ];
        } catch (\Exception $e) {
            \Log::error("Failed to generate credit note PDF for email: " . $e->getMessage());
        }

        // Attach any uploaded files
        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $uploadedFile) {
                $attachments[] = [
                    'file' => file_get_contents($uploadedFile->getRealPath()),
                    'options' => [
                        'as' => $uploadedFile->getClientOriginalName(),
                        'mime' => $uploadedFile->getMimeType(),
                    ]
                ];
            }
        }

        // Send email with attachments
        return self::sendMail(
            $customer,
            $title,
            $description,
            $cc,
            $attachments,
            $mail_from,
            $mail_from_name
        );
    }

    public function sendMail($receiver, $title, $description, $cc = null, $attachments = [], $mail_from = null, $mail_from_name = null,$bcc=null)
    {
        try {
            if (!$receiver || !isset($receiver->email)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Receiver details are missing or invalid.',
                ], 400);
            }

            // Prepare attachment paths to pass to the job (avoid binary content in queue)
            $storedAttachments = [];

            foreach ($attachments as $attachment) {
                $filename = $attachment['options']['as'] ?? uniqid() . '.pdf';
                $mime = $attachment['options']['mime'] ?? 'application/octet-stream';
                $tempPath = storage_path("app/temp_mails/{$filename}");

                // Ensure directory exists
                if (!file_exists(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0777, true);
                }

                file_put_contents($tempPath, $attachment['file']);

                $storedAttachments[] = [
                    'path' => $tempPath,
                    'as' => $filename,
                    'mime' => $mime
                ];
            }

            dispatch(new SendEmailJob(
            $receiver,
            $mail_from,
            $mail_from_name,
            $title,
            $description,
            $cc,
            $bcc,
            $storedAttachments
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Email request sent successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending email: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email. ' . $e->getMessage(),
            ], 500);
        }
    }
    // public function returnPod(Request $request)
    // {
    //     $request->validate([
    //         'remarks' => 'nullable|string|max:255',
    //         'attachment' => 'nullable'
    //     ]);
    //     DB::beginTransaction();
    //     try {
    //         $saleReturn = ErpSaleReturn::find($request->id);
    //         $bookId = $saleReturn->book_id;
    //         $docId = $saleReturn->id;
    //         $docValue = $saleReturn->total_amount;
    //         $remarks = $request->remarks;
    //         $attachments = $request->file('attachments');
    //         $currentLevel = $saleReturn->approval_level;
    //         $revisionNumber = $saleReturn->revision_number ?? 0;
    //         $actionType = "Delivered"; // Approve or reject
    //         $modelName = get_class($saleReturn);
            // $saleReturn->delivery_status = 1;
    //         $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $docValue, $modelName);
    //         $saleReturn->save();

    //         DB::commit();
    //         return response()->json([
    //             'message' => "POD Updated successfully!",
    //             'data' => $saleReturn,
    //         ]);
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => "Error occurred while Updating POD of the document.",
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    public function generateEwayBill(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_no' => [
                    'required',
                    'regex:/^[A-Z]{2}[0-9]{2}[A-Z]{0,3}[0-9]{4}$/'
                ],
                'transporter_mode' => 'required|integer',
                "transporter_name" => [
                   "required",
                   'string'
                ],
            ],
            [
                'vehicle_no.required' => 'Vehicle number is required.',
                'vehicle_no.regex' => 'Vehicle number format is invalid. Example: MH12AB1234.',
                'transporter_mode.required' => 'Transporter mode is required.',
                'transporter_mode.integer' => 'Transporter mode must be an integer.',
                'transporter_name.required' => 'Transporter name is required.',
                'transporter_name.string' => 'Transporter name must be a string.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->messages()->first(),
            ], 422);
        }
        $user = request()->user();;

        try{
            $documentHeader = ErpSaleReturn::find($request->id);
            $transportationMode = EwayBillMaster::find($request->transporter_mode);
            $documentHeader->transporter_name=$request->transporter_name;
            $documentHeader->transportation_mode=$transportationMode?->description ?? null;
            $documentHeader->eway_bill_master_id=$transportationMode?->id ?? null;
            $documentHeader->vehicle_no=$request->vehicle_no;
            $data = EInvoiceHelper::generateEwayBill($documentHeader);
            if (isset($data) && (isset($data['status']) && ($data['status'] == 'error'))) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'error',
                    'message' => $data['message'],
                ], 500);
            } else{
                $eInvoice = $documentHeader->irnDetail()->first();
                $eInvoice->ewb_no = $data['EwbNo'];
                $eInvoice->ewb_date = date('Y-m-d H:i:s', strtotime($data['EwbDt']));
                $eInvoice->ewb_valid_till = date('Y-m-d H:i:s', strtotime($data['EwbValidTill']));
                $eInvoice->save();

                $documentHeader -> is_ewb_generated = 1;
                $documentHeader -> save();

                return response() -> json([
                    'status' => 'success',
                    'results' => $data,
                    'message' => 'E-Way bill generated succesfully',
                ]);
            }
            
        } catch(Exception $ex) {
            return response() -> json([
                'status' => 'error',
                'message' => $ex -> getMessage(),
            ]);
        }
    }

    public function salesreturnReport(Request $request)
    {
        $pathUrl = route('sale.return.index');
        $orderType = [ConstantHelper::SR_SERVICE_ALIAS];
        $salesOrders = ErpSaleReturn::with('items')->whereIn('document_type', $orderType)-> withDefaultGroupCompanyOrg() -> withDraftListingLogic() -> orderByDesc('id');
        //Customer Filter
        $salesOrders = $salesOrders -> when($request -> customer_id, function ($custQuery) use($request) {
            $custQuery -> where('customer_id', $request -> customer_id);
        });
        //Book Filter
        $salesOrders = $salesOrders -> when($request -> book_id, function ($bookQuery) use($request) {
            $bookQuery -> where('book_id', $request -> book_id);
        });
        //Document Id Filter
        $salesOrders = $salesOrders -> when($request -> document_number, function ($docQuery) use($request) {
            $docQuery -> where('document_number', 'LIKE', '%' . $request -> document_number . '%');
        });
        //Location Filter
        $salesOrders = $salesOrders -> when($request -> location_id, function ($docQuery) use($request) {
            $docQuery -> where('store_id', $request -> location_id);
        });
        //Company Filter
        $salesOrders = $salesOrders -> when($request -> company_id, function ($docQuery) use($request) {
            $docQuery -> where('store_id', $request -> company_id);
        });
        //Organization Filter
        $salesOrders = $salesOrders -> when($request -> organization_id, function ($docQuery) use($request) {
            $docQuery -> where('organization_id', $request -> organization_id);
        });
        //Document Status Filter
        $salesOrders = $salesOrders -> when($request -> doc_status, function ($docStatusQuery) use($request) {
            $searchDocStatus = [];
            if ($request -> doc_status === ConstantHelper::DRAFT) {
                $searchDocStatus = [ConstantHelper::DRAFT];
            } else if ($request -> doc_status === ConstantHelper::SUBMITTED) {
                $searchDocStatus = [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED];
            } else {
                $searchDocStatus = [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED];
            }
            $docStatusQuery -> whereIn('document_status', $searchDocStatus);
        });
        //Date Filters
        $dateRange = $request -> date_range ??  Carbon::now()->startOfMonth()->format('Y-m-d') . " to " . Carbon::now()->endOfMonth()->format('Y-m-d');
        $salesOrders = $salesOrders -> when($dateRange, function ($dateRangeQuery) use($request, $dateRange) {
           $dateRanges = explode('to', $dateRange);
           if (count($dateRanges) == 2) {
                $fromDate = Carbon::parse(trim($dateRanges[0])) -> format('Y-m-d');
                $toDate = Carbon::parse(trim($dateRanges[1])) -> format('Y-m-d');
                $dateRangeQuery -> whereDate('document_date', ">=" , $fromDate) -> where('document_date', '<=', $toDate);
           }
           else{
                $fromDate = Carbon::parse(trim($dateRanges[0])) -> format('Y-m-d');
                $dateRangeQuery -> whereDate('document_date', $fromDate);
            }
        });
        //Item Id Filter
        $salesOrders = $salesOrders -> when($request -> item_id, function ($itemQuery) use($request) {
            $itemQuery -> withWhereHas('items', function ($itemSubQuery) use($request) {
                $itemSubQuery -> where('item_id', $request -> item_id)
                //Compare Item Category
                -> when($request -> item_category_id, function ($itemCatQuery) use($request) {
                    $itemCatQuery -> whereHas('item', function ($itemRelationQuery) use($request) {
                        $itemRelationQuery -> where('category_id', $request -> category_id)
                        //Compare Item Sub Category
                        -> when($request -> item_sub_category_id, function ($itemSubCatQuery) use($request) {
                            $itemSubCatQuery -> where('subcategory_id', $request -> item_sub_category_id);
                        });
                    });
                });
            });
        });
        //Invoice No Filter
        $salesOrders = $salesOrders -> when($request -> si_no, function ($orderNoQuery) use($request) {
            $orderNoQuery -> whereHas('items', function ($soItemQuery) use($request) {
                $soItemQuery -> whereHas('invoice_item', function ($invoiceQuery) use($request) {
                    $invoiceQuery -> whereHas('header', function ($headerQuery) use($request) {
                        $headerQuery -> where('document_number', 'LIKE', '%' . $request -> si_no . '%')
                        ->orWhere('book_code',"LIKE",'%'. $request->si_no . '%');
                    });
                });
            });
        });
        //SI Date Range Filter
        $salesOrders = $salesOrders -> when($request -> si_dt, function ($orderDtQuery) use($request) {
            if (count($request -> si_dt) == 2) {
                $fromDate = Carbon::parse(trim($request -> si_dt[0])) -> format('Y-m-d');
                $toDate = Carbon::parse(trim($request -> si_dt[1])) -> format('Y-m-d');
                $orderDtQuery -> whereDate('document_date', ">=" , $fromDate) -> where('document_date', '<=', $toDate);
           }
           else{
                $fromDate = Carbon::parse(trim($request -> si_dt[0])) -> format('Y-m-d');
                $orderDtQuery -> whereDate('document_date', $fromDate);
            }
        });
        //Order No Filter
        $salesOrders = $salesOrders -> when($request -> so_no, function ($orderNoQuery) use($request) {
            $orderNoQuery -> whereHas('items', function ($soItemQuery) use($request) {
                $soItemQuery -> whereHas('invoice_item', function ($invoiceQuery) use($request) {
                    $invoiceQuery -> whereHas('sale_order', function ($sale_orderQuery) use($request) {
                        $sale_orderQuery -> where('document_number', 'LIKE', '%' . $request -> so_no . '%')
                        -> orWhere('book_code', 'LIKE', '%' . $request -> so_no . '%')
                        ;
                    });
                });
            });
        });
        //SO Date Range Filter
        $salesOrders = $salesOrders -> when($request -> so_dt, function ($orderDtQuery) use($request) {
            $orderDtQuery -> whereDate('document_date', '>=', $request -> so_dt[0])
                           -> whereDate('document_date', '<=', $request -> so_dt[1]);
        });
        $salesOrders = $salesOrders -> get();
        $processedSalesOrder = collect([]);
        foreach ($salesOrders as $saleOrder) {
            foreach ($saleOrder -> items as $soItem) {
                $reportRow = new stdClass();
                //Header Details
                $header = $soItem -> header;
                $reportRow -> id = $header -> id;
                $reportRow -> book_name = $header -> book_code;
                $reportRow -> document_number = $header -> document_number;
                $reportRow -> document_date = $header -> document_date;
                $reportRow -> store_name = $header -> erpStore ?-> store_name;
                $reportRow -> customer_name = $header -> customer ?-> company_name;
                $reportRow -> customer_currency = $header -> currency_code;
                $reportRow -> payment_terms_name = $header -> payment_term_code;
                //Item Details
                $reportRow -> item_name = $soItem -> item_name;
                $reportRow -> item_code = $soItem -> item_code;
                $reportRow -> hsn_code = $soItem -> hsn ?-> code;
                $reportRow -> uom_name = $soItem -> uom ?-> name;
                //Amount Details
                $reportRow -> sr_qty = number_format($soItem -> order_qty, 2);
                $reportRow -> si_qty = number_format($soItem -> invoice_item ?-> order_qty ?? 0.00, 2);
                $reportRow -> si_date = $soItem ?-> invoice_item ?-> header ?-> document_date ?? " ";
                $reportRow -> si_no = $soItem->invoice_item ?-> header ? $soItem ?-> invoice_item ?-> header ?-> book_code."-".$soItem ?-> invoice_item ?-> header ?-> document_number : " ";
                $reportRow -> so_qty = number_format($soItem -> invoice_item ?-> sale_order_item() -> order_qty ?? 0.00, 2);
                $reportRow -> so_date = $soItem ?-> invoice_item ?-> sale_order ?-> document_date ?? " ";
                $reportRow -> so_no = $soItem->invoice_item ?-> sale_order ? $soItem ?-> invoice_item ?-> header ?-> book_code."-".$soItem ?-> invoice_item ?-> header ?-> document_number : " ";
                $reportRow -> rate = number_format($soItem -> rate, 2);
                $reportRow -> total_discount_amount = number_format($soItem -> header_discount_amount + $soItem -> item_discount_amount, 2);
                $reportRow -> tax_amount = number_format($soItem -> tax_amount, 2);
                $reportRow -> taxable_amount = number_format($soItem -> total_item_amount - $soItem -> tax_amount, 2);
                $reportRow -> total_item_amount = number_format($soItem -> total_item_amount, 2);
                //Delivery Schedule UI
                // $deliveryHtml = '';
                // if (count($soItem -> item_deliveries) > 0) {
                //     foreach ($soItem -> item_deliveries as $itemDelivery) {
                //         $deliveryDate = Carbon::parse($itemDelivery -> delivery_date) -> format('d-m-Y');
                //         $deliveryQty = number_format($itemDelivery -> qty, 2);
                //         $deliveryHtml .= "<span class='badge rounded-pill badge-light-primary'><strong>$deliveryDate</strong> : $deliveryQty</span>";
                //     }
                // } else {
                //     $parsedDeliveryDate = Carbon::parse($soItem -> delivery_date) -> format('d-m-Y');
                //     $deliveryHtml .= "$parsedDeliveryDate";
                // }
                // $reportRow -> delivery_schedule = $deliveryHtml;
                //Attributes UI
                $attributesUi = '';
                if (count($soItem -> item_attributes) > 0) {
                    foreach ($soItem -> item_attributes as $soAttribute) {
                        $attrName = $soAttribute -> attribute_name;
                        $attrValue = $soAttribute -> attribute_value;
                        $attributesUi .= "<span class='badge rounded-pill badge-light-primary' > $attrName : $attrValue </span>";
                    }
                } else {
                    $attributesUi = 'N/A';
                }
                $reportRow -> item_attributes = $attributesUi;
                //Main header Status
                $reportRow -> status = $header -> document_status;
                $processedSalesOrder -> push($reportRow);
            }
        }
        return DataTables::of($processedSalesOrder) ->addIndexColumn()
        ->editColumn('status', function ($row) use($orderType) {
            $statusClasss = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->status ?? ConstantHelper::DRAFT];    
            $displayStatus = ucfirst($row -> status);
            $editRoute = null;
            $editRoute = route('sale.return.edit', ['id' => $row->id]);
            return "
            <div style='text-align:right;'>
                <span class='badge rounded-pill $statusClasss badgeborder-radius'>$displayStatus</span>
                    <a href='" . $editRoute . "'>
                        <i class='cursor-pointer' data-feather='eye'></i>
                    </a>
            </div>
        ";
        })
        ->rawColumns(['item_attributes','delivery_schedule','status'])
        ->make(true);
    }
    public function getBatchData($item)
    {
        
    }
}


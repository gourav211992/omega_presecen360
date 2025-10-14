<?php
namespace App\Http\Controllers;

use DB;
use PDF;
use Auth;
use View;
use Session;
use stdClass;
use DateTime;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Http\Request;
use App\Http\Requests\ExpAllocationRequest;
use App\Http\Requests\EditExpAllocationRequest;

use App\Models\ExpenseAllocation\Header;
use App\Models\ExpenseAllocation\PoDetail;
use App\Models\ExpenseAllocation\GrnDetail;
use App\Models\ExpenseAllocation\PoAttribute;
use App\Models\ExpenseAllocation\GrnAttribute;
use App\Models\ExpenseAllocation\Allocation;
use App\Models\ExpenseAllocation\Media;
use App\Models\ExpenseAllocation\DynamicField;

use App\Models\ExpenseAllocation\HeaderHistory;
use App\Models\ExpenseAllocation\PoDetailHistory;
use App\Models\ExpenseAllocation\GrnDetailHistory;
use App\Models\ExpenseAllocation\PoAttributeHistory;
use App\Models\ExpenseAllocation\GrnAttributeHistory;
use App\Models\ExpenseAllocation\AllocationHistory;
use App\Models\ExpenseAllocation\MediaHistory;
use App\Models\ExpenseAllocation\DynamicFieldHistory;

use App\Models\Unit;
use App\Models\Item;
use App\Models\PoItem;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\ErpStore;
use App\Models\Customer;
use App\Models\MrnHeader;
use App\Models\MrnDetail;
use App\Models\ErpSoItem;
use App\Models\ErpAddress;
use App\Models\CostCenter;
use App\Models\Organization;
use App\Models\ErpSaleOrder;
use App\Models\AlternateUOM;
use App\Models\PurchaseOrder;
use App\Models\AttributeGroup;

use App\Models\ErpItem;
use App\Models\AuthUser;
use App\Models\Category;
use App\Models\Employee;
use App\Models\ErpVendor;

use App\Helpers\Helper;
use App\Helpers\TaxHelper;
use App\Helpers\BookHelper;
use App\Helpers\NumberHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\InventoryHelper;
use App\Helpers\DynamicFieldHelper;
use App\Helpers\FinancialPostingHelper;
use App\Helpers\ServiceParametersHelper;
use App\Helpers\Common\OrganizationHelper;
use App\Helpers\InventoryHelperV2;
use App\Jobs\SendEmailJob;
use App\Models\Api\Currency;
use App\Models\Currency as ModelsCurrency;
use App\Models\ErpCurrency;
use App\Services\ExpAlc\StoreService;
use App\Services\ExpAlc\UpdateService;
use App\Services\ExpAlc\DeleteService;
use App\Services\ExpAlc\AmendmentService;

class ExpenseAllocationController extends Controller
{
    protected $expenseService;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        $selectedfyYear = Helper::getFinancialYear(Carbon::now()->format('Y-m-d'));
        $orderType = ConstantHelper::EXP_ALC_SERVICE_ALIAS;
        request()->merge(['type' => $orderType]);
        if (request()->ajax()) {
            $selectColumns = [
                'id',
                'document_status',
                'book_id',
                'document_number',
                'store_id',
                'sub_store_id',
                'document_date',
                'revision_number',
                'total_allocated_value',
                'total_landed_cost_value'
            ];
            $records = Header::select($selectColumns)
                ->bookViewAccess($orderType)
                ->whereBetween('document_date', [$selectedfyYear['start_date'], $selectedfyYear['end_date']])
                ->withDraftListingLogic()
                ->latest();


            // Apply drawer filters
            if ($request->filled('date_range')) {
                $dates = explode(' to ', $request->date_range);

                if (count($dates) === 2) {
                    $startDate = Carbon::parse($dates[0])->startOfDay();
                    $endDate = Carbon::parse($dates[1])->endOfDay();

                    $records->whereBetween('document_date', [$startDate, $endDate]);
                }
            }
            if ($request->filled('book_id')) {
                $records->whereIn('book_id', $request->book_id);
            }
            if ($request->filled('location_id')) {
                $records->whereIn('store_id', $request->location_id);
            }
            if ($request->filled('store_id')) {
                $records->whereIn('sub_store_id', $request->location_id);
            }
            if ($request->filled('organization_id')) {
                $records->whereIn('organization_id', $request->organization_id);
            }
            return DataTables::of($records)
                ->addIndexColumn()
                ->editColumn('document_status', function ($row) {
                    $statusClasss = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->document_status];
                    $route = route('exp-allocation.edit', $row->id);
                    $displayStatus = $row->display_status;
                    return "<div style='text-align:right;'>
                        <span class='badge rounded-pill $statusClasss badgeborder-radius'>$displayStatus</span>
                        <div class='dropdown' style='display:inline;'>
                            <button type='button' class='btn btn-sm dropdown-toggle hide-arrow py-0 p-0' data-bs-toggle='dropdown'>
                                <i data-feather='more-vertical'></i>
                            </button>
                            <div class='dropdown-menu dropdown-menu-end'>
                                <a class='dropdown-item' href='" . $route . "'>
                                    <i data-feather='edit-3' class='me-50'></i>
                                    <span>View/ Edit Detail</span>
                                </a>
                            </div>
                        </div>
                    </div>";
                })
                ->addColumn('book_code', function ($row) {
                    return $row->book ? $row?->book?->book_code : 'N/A';
                })
                ->editColumn('document_date', function ($row) {
                    return date('d/m/Y', strtotime($row->document_date)) ?? 'N/A';
                })
                ->addColumn('location', function ($row) {
                    return strval($row->erpStore?->store_name) ?? 'N/A';
                })
                ->addColumn('revision_number', function ($row) {
                    return strval($row->revision_number);
                })
                ->editColumn('total_allocated_value', function ($row) {
                    return number_format($row->total_allocated_value, 2);
                })
                ->addColumn('total_landed_cost_value', function ($row) {
                    return number_format($row->total_landed_cost_value, 2);
                })
                ->rawColumns(['document_status'])
                ->make(true);
        }
        return view('procurement.expense-allocation.index', [
            'servicesBooks' => $servicesBooks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = request()->user();
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->back();
        }
        $serviceAlias = ConstantHelper::EXP_ALC_SERVICE_ALIAS;
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $vendors = Vendor::where('status', ConstantHelper::ACTIVE)->get();
        $customers = Customer::where('status', ConstantHelper::ACTIVE)->get();
        $purchaseOrders = PurchaseOrder::with('vendor')->get();
        $saleOrders = ErpSaleOrder::with('customer')->get();
        $locations = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCKK);
        return view('procurement.expense-allocation.create', [
            'books' => $books,
            'vendors' => $vendors,
            'customers' => $customers,
            'locations' => $locations,
            'saleOrders' => $saleOrders,
            'servicesBooks' => $servicesBooks,
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    # Store Function
    public function store(ExpAllocationRequest $request)
    {
        $user = request()->user();
        \DB::beginTransaction();
        try {
            $parameters = [];
            $response = BookHelper::fetchBookDocNoAndParameters($request->book_id, $request->document_date);
            if ($response['status'] === 200) {
                $parameters = json_decode(json_encode($response['data']['parameters']), true);
            }

            $organization = OrganizationHelper::getAuthenticatedOrganization();
            //Tax Country and State
            $firstAddress = $organization->addresses->first();
            $companyCountryId = null;
            $companyStateId = null;
            $applicabilityType = '';
            if ($firstAddress) {
                $companyCountryId = $firstAddress->country_id;
                $companyStateId = $firstAddress->state_id;
            } else {
                return response()->json([
                    'message' => 'Please create an organization first'
                ], 422);
            }

            $expense = new Header();
            $expense->fill($request->all());
            $expense->organization_id = $organization->id;
            $expense->currency_id = $organization->currency_id;
            $expense->currency_code = $organization?->currency?->short_name;
            $expense->group_id = $organization->group_id;
            $expense->company_id = $organization->company_id;
            $expense->book_code = $request->book_code;
            $expense->book_id = $request->book_id;
            $expense->supplier_invoice_no = $request->supplier_invoice_no;
            $expense->supplier_invoice_date = date('Y-m-d', strtotime($request->supplier_invoice_date));
            $expense->revision_number = 0;
            $document_number = $request->document_number ?? null;
            $numberPatternData = Helper::generateDocumentNumberNew($request->book_id, $request->document_date);
            if (!isset($numberPatternData)) {
                return response()->json([
                    'message' => "Invalid Book",
                    'error' => "",
                ], 422);
            }
            $document_number = $numberPatternData['document_number'] ? $numberPatternData['document_number'] : $request->document_no;
            $regeneratedDocExist = Header::withDefaultGroupCompanyOrg()->where('book_id', $request->book_id)
                ->where('document_number', $document_number)->first();
            //Again check regenerated doc no
            if (isset($regeneratedDocExist)) {
                return response()->json([
                    'message' => ConstantHelper::DUPLICATE_DOCUMENT_NUMBER,
                    'error' => "",
                ], 422);
            }

            $expense->doc_number_type = $numberPatternData['type'];
            $expense->doc_reset_pattern = $numberPatternData['reset_pattern'];
            $expense->doc_prefix = $numberPatternData['prefix'];
            $expense->doc_suffix = $numberPatternData['suffix'];
            $expense->doc_no = $numberPatternData['doc_no'];
            $expense->document_number = $document_number;
            $expense->document_date = $request->document_date;
            $expense->remark = $request->remark ?? null;
            $expense->store_id = $request->header_store_id ?? '';
            $expense->cost_center_id = $request->cost_center_id ?? '';
            $expense->total_po_value = 0.00;
            $expense->total_grn_value = 0.00;
            $expense->total_allocated_value = 0.00;
            $expense->total_landed_cost_value = 0.00;
            $expense->save();

            # Store location address
            if ($expense?->erpStore) {
                $storeAddress = $expense?->erpStore->address;
                $storeLocation = $expense->store_address()->firstOrNew();
                $storeLocation->fill([
                    'type' => 'location',
                    'address' => $storeAddress->address,
                    'country_id' => $storeAddress->country_id,
                    'state_id' => $storeAddress->state_id,
                    'city_id' => $storeAddress->city_id,
                    'pincode' => $storeAddress->pincode,
                    'phone' => $storeAddress->phone,
                    'fax_number' => $storeAddress->fax_number,
                ]);
                $storeLocation->save();
            }

            $storeService = new StoreService();
            $storeResponse = $storeService->store($request->all(), $expense, $user, $organization);
            if ($storeResponse['status'] === 'error') {
                \DB::rollBack();
                return response()->json([
                    'message' => $storeResponse['message'],
                    'error' => ''
                ], 422);
            }

            /*Create document submit log*/
            if ($request->document_status == ConstantHelper::SUBMITTED) {
                $bookId = $expense->book_id;
                $docId = $expense->id;
                $remarks = $expense->remarks;
                $attachments = $request->file('attachment');
                $currentLevel = $expense->approval_level ?? 1;
                $revisionNumber = $expense->revision_number ?? 0;
                $actionType = 'submit'; // Approve // reject // submit
                $modelName = get_class($expense);
                $totalValue = $expense->total_amount ?? 0;
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                $expense->document_status = $approveDocument['approvalStatus'] ?? $expense->document_status;
                // $expense->document_status = $document_status;
            } else {
                $expense->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            }

            /*Expense Attachment*/
            if ($request->hasFile('attachment')) {
                $mediaFiles = $expense->uploadDocuments($request->file('attachment'), 'exp-alc', false);
            }
            $expense->save();

            if (($expense->document_status == ConstantHelper::APPROVED)) {
                $expenseData = InventoryHelperV2::saveMrnExpenses($expense);
                if ($expenseData && $expenseData['status'] == 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $expenseData['message'],
                        'error' => ''
                    ], 422);
                }
            }

            $redirectUrl = '';
            if (($expense->document_status == ConstantHelper::APPROVED) || ($expense->document_status == ConstantHelper::POSTED)) {
                $parentUrl = request()->segments()[0];
                $redirectUrl = url($parentUrl . '/' . $expense->id . '/pdf');
            }
            // $status = DynamicFieldHelper::saveDynamicFields(ErpExpDynamicField::class, $expense->id, $request->dynamic_field ?? []);
            // if ($status && !$status['status']) {
            //     DB::rollBack();
            //     return response()->json([
            //         'message' => $status['message'],
            //         'error' => ''
            //     ], 422);
            // }
            DB::commit();

            return response()->json([
                'message' => 'Record created successfully',
                'data' => $expense,
                'redirectUrl' => $redirectUrl
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        $user = request()->user();
        $expense = Header::with(['vendor', 'currency', 'items', 'book'])
            ->findOrFail($id);
        $totalItemValue = $expense->items()->sum('basic_value');
        $userType = Helper::userCheck();
        $buttons = Helper::actionButtonDisplay($expense->series_id, $expense->document_status, $expense->id, $expense->total_amount, $expense->approval_level, $expense->created_by ?? 0, $userType['type']);
        $approvalHistory = Helper::getApprovalHistory($expense->series_id, $expense->id, $expense->revision_number);
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$expense->document_status];
        $revisionNumbers = $approvalHistory->pluck('revision_number')->unique()->values()->all();
        $erpStores = ErpStore::withDefaultGroupCompanyOrg()
            ->orderBy('id', 'DESC')
            ->get();
        return view('procurement.expense-allocation.view', [
            'mrn' => $expense,
            'buttons' => $buttons,
            'erpStores' => $erpStores,
            'totalItemValue' => $totalItemValue,
            'approvalHistory' => $approvalHistory,
            'docStatusClass' => $docStatusClass,
            'revisionNumbers' => $revisionNumbers,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $user = request()->user();
        $parentUrl = request()->segments()[0];
        $servicesBooks = Helper::getAccessibleServicesFromMenuAlias($parentUrl);
        if (count($servicesBooks['services']) == 0) {
            return redirect()->back();
        }
        $serviceAlias = ConstantHelper::EXP_ALC_SERVICE_ALIAS;
        $books = Helper::getBookSeriesNew($serviceAlias, $parentUrl)->get();
        $expense = Header::with(
            [
                'book',
                'erpStore',
                'poDetails',
                'grnDetails',
            ]
        )
            ->findOrFail($id);

        $poDetails = $expense['poDetails'] ?? [];
        $grnDetails = $expense['grnDetails'] ?? [];
        $poHeaderField = 'po_header_id';
        $grnHeaderField = 'grn_header_id';
        $poDetailsField = 'po_detail_id';
        $grnDetailsField = 'grn_detail_id';

        $poHeaderIds = collect($poDetails)
            ->pluck($poHeaderField)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $grnHeaderIds = collect($grnDetails)
            ->pluck($grnHeaderField)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $poDetailsIds = collect($poDetails)
            ->pluck($poDetailsField)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $grnDetailsIds = collect($grnDetails)
            ->pluck($grnDetailsField)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $revision_number = $expense->revision_number;
        $userType = Helper::userCheck();
        $buttons = Helper::actionButtonDisplay($expense->book_id, $expense->document_status, $expense->id, $expense->total_allocated_value, $expense->approval_level, $expense->created_by ?? 0, $userType['type'], $revision_number);
        $revNo = $expense->revision_number;
        if ($request->has('revisionNumber')) {
            $revNo = intval($request->revisionNumber);
        } else {
            $revNo = $expense->revision_number;
        }
        $approvalHistory = Helper::getApprovalHistory($expense->book_id, $expense->id, $revNo, $expense->total_allocated_value);
        $view = 'procurement.expense-allocation.edit';
        if ($request->has('revisionNumber') && $request->revisionNumber != $expense->revision_number) {
            $expense = $expense->source;
            $expense = HeaderHistory::where('revision_number', $request->revisionNumber)
                ->where('source_id', $expense->source_id)
                ->first();
            $view = 'procurement.expense-allocation.view';
        }
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$expense->document_status] ?? '';
        $costCenters = CostCenter::withDefaultGroupCompanyOrg()->get();
        $locations = InventoryHelper::getAccessibleLocations(ConstantHelper::STOCKK);
        $distributionTypes = ConstantHelper::getDistributionTypes();
        $dynamicFieldsUI = $expense->dynamicfieldsUi();

        return view($view, [
            'user' => $user,
            'books' => $books,
            'buttons' => $buttons,
            'expense' => $expense,
            'locations' => $locations,
            'poDetails' => $poDetails,
            'grnDetails' => $grnDetails,
            'costCenters' => $costCenters,
            'poHeaderIds' => $poHeaderIds,
            'grnHeaderIds' => $grnHeaderIds,
            'poDetailsIds' => $poDetailsIds,
            'grnDetailsIds' => $grnDetailsIds,
            'docStatusClass' => $docStatusClass,
            'revision_number' => $revision_number,
            'approvalHistory' => $approvalHistory,
            'dynamicFieldsUI' => $dynamicFieldsUI,
            'distributionTypes' => $distributionTypes
        ]);
    }

    # Expense Update
    public function update(EditExpAllocationRequest $request, $id)
    {
        $user = request()->user();
        $expense = Header::find($id);
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        //Tax Country and State
        $firstAddress = $organization->addresses->first();
        $companyCountryId = null;
        $companyStateId = null;
        $applicabilityType = '';
        if ($firstAddress) {
            $companyCountryId = $firstAddress->country_id;
            $companyStateId = $firstAddress->state_id;
        } else {
            return response()->json([
                'message' => 'Please create an organization first'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $parameters = [];
            $response = BookHelper::fetchBookDocNoAndParameters($request->book_id, $request->document_date);
            if ($response['status'] === 200) {
                $parameters = json_decode(json_encode($response['data']['parameters']), true);
            }

            $currentStatus = $expense->document_status;
            $actionType = $request->action_type;

            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'Header', 'relation_column' => ''],
                    ['model_type' => 'detail', 'model_name' => 'PoDetail', 'relation_column' => 'header_id'],
                    ['model_type' => 'detail', 'model_name' => 'GrnDetail', 'relation_column' => 'header_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'PoAttribute', 'relation_column' => 'detail_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'GrnAttribute', 'relation_column' => 'detail_id'],
                    ['model_type' => 'sub_detail', 'model_name' => 'Allocation', 'relation_column' => 'grn_detail_id']
                ];
                // $a = Helper::documentAmendment($revisionData, $id);
                $this->amendmentSubmit($request, $id);
            }

            $keys = ['deletedMrnItemIds'];
            $deletedData = [];

            foreach ($keys as $key) {
                $deletedData[$key] = json_decode($request->input($key, '[]'), true);
            }

            // $deleteService = new DeleteService();
            // $deleteResponse = $deleteService->store($deletedData, $expense);
            // if ($deleteResponse['status'] === 'error') {
            //     \DB::rollBack();
            //     return response()->json([
            //         'message' => $deleteResponse['message'],
            //         'error' => ''
            //     ], 422);
            // }

            # Expense Header save
            $expense->document_status = $request->document_status ?? ConstantHelper::DRAFT;
            $expense->save();

            # Store location address
            if ($expense?->erpStore) {
                $storeAddress = $expense?->erpStore->address;
                $storeLocation = $expense->store_address()->firstOrNew();
                $storeLocation->fill([
                    'type' => 'location',
                    'address' => $storeAddress->address,
                    'country_id' => $storeAddress->country_id,
                    'state_id' => $storeAddress->state_id,
                    'city_id' => $storeAddress->city_id,
                    'pincode' => $storeAddress->pincode,
                    'phone' => $storeAddress->phone,
                    'fax_number' => $storeAddress->fax_number,
                ]);
                $storeLocation->save();
            }

            $updateService = new UpdateService();
            $updateResponse = $updateService->update($request->all(), $expense, $user, $organization);
            if ($updateResponse['status'] === 'error') {
                \DB::rollBack();
                return response()->json([
                    'message' => $updateResponse['message'],
                    'error' => ''
                ], 422);
            }

            /*Create document submit log*/
            $bookId = $expense->book_id;
            $docId = $expense->id;
            $amendRemarks = $request->amend_remarks ?? null;
            $remarks = $expense->remarks;
            $amendAttachments = $request->file('amend_attachment');
            $attachments = $request->file('attachment');
            $currentLevel = $expense->approval_level ?? 1;
            $modelName = get_class($expense);
            if ($currentStatus == ConstantHelper::APPROVED && $actionType == 'amendment') {
                //*amendmemnt document log*/
                $revisionNumber = $expense->revision_number + 1;
                $actionType = 'amendment';
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $amendRemarks, $amendAttachments, $currentLevel, $actionType, $expense->total_amount, $modelName);
                $expense->revision_number = $revisionNumber;
                $expense->approval_level = 1;
                $expense->revision_date = now();
                $amendAfterStatus = $expense->document_status;
                $checkAmendment = Helper::checkAfterAmendApprovalRequired($request->book_id);
                if (isset($checkAmendment->approval_required) && $checkAmendment->approval_required) {
                    $totalValue = $expense->grand_total_amount ?? 0;
                    $amendAfterStatus = Helper::checkApprovalRequired($request->book_id, $totalValue);
                }
                if ($amendAfterStatus == ConstantHelper::SUBMITTED) {
                    $actionType = 'submit';
                    $totalValue = $expense->total_amount ?? 0;
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                }
                $expense->document_status = $amendAfterStatus;
                $expense->save();
            } else {
                if ($request->document_status == ConstantHelper::SUBMITTED) {
                    $revisionNumber = $expense->revision_number ?? 0;
                    $actionType = 'submit';
                    $totalValue = $expense->total_amount ?? 0;
                    $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType, $totalValue, $modelName);
                    $totalValue = $expense->grand_total_amount ?? 0;
                    $document_status = Helper::checkApprovalRequired($request->book_id, $totalValue);
                    $expense->document_status = $document_status;
                } else {
                    $expense->document_status = $request->document_status ?? ConstantHelper::DRAFT;
                }
            }

            /*Expense Attachment*/
            if ($request->hasFile('attachment')) {
                $mediaFiles = $expense->uploadDocuments($request->file('attachment'), 'expense', false);
            }
            $expense->save();

            if (($expense->document_status == ConstantHelper::APPROVED)) {
                $expenseData = InventoryHelperV2::saveMrnExpenses($expense);
                if ($expenseData && $expenseData['status'] == 'error') {
                    DB::rollBack();
                    return response()->json([
                        'message' => $expenseData['message'],
                        'error' => ''
                    ], 422);
                }
            }

            $redirectUrl = '';
            if (($expense->document_status == ConstantHelper::APPROVED) || ($expense->document_status == ConstantHelper::POSTED)) {
                $parentUrl = request()->segments()[0];
                $redirectUrl = url($parentUrl . '/' . $expense->id . '/pdf');
            }

            $status = DynamicFieldHelper::saveDynamicFields(DynamicField::class, $expense->id, $request->dynamic_field ?? []);
            if ($status && !$status['status']) {
                DB::rollBack();
                return response()->json([
                    'message' => $status['message'],
                    'error' => ''
                ], 422);
            }
            DB::commit();
            return response()->json([
                'message' => 'Record updated successfully',
                'data' => $expense,
                'redirectUrl' => $redirectUrl
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while creating the record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addItemRow(Request $request)
    {
        $user = request()->user();
        $item = json_decode($request->item, true) ?? [];
        $componentItem = json_decode($request->component_item, true) ?? [];
        $distributionTypes = ConstantHelper::getDistributionTypes();
        /*Check last tr in table mandatory*/
        if (isset($componentItem['attr_require']) && isset($componentItem['item_id']) && $componentItem['row_length']) {
            if (($componentItem['attr_require'] == true || !$componentItem['item_id']) && $componentItem['row_length'] != 0) {
                // return response()->json(['data' => ['html' => ''], 'status' => 422, 'message' => 'Please fill all component details before adding new row more!']);
            }
        }
        $rowCount = intval($request->count) == 0 ? 1 : intval($request->count) + 1;
        $html = view('procurement.expense-allocation.partials.item-row', compact(['rowCount', 'distributionTypes']))->render();
        return response()->json(['data' => ['html' => $html], 'status' => 200, 'message' => 'fetched.']);
    }

    // PO Item Rows
    public function poItemRows(Request $request)
    {
        $user = request()->user();
        $organization = Organization::where('id', $user->organization_id)->first();
        $item_ids = explode(',', $request->item_ids);
        $items = PoItem::whereIn('id', $item_ids)
            ->get();
        $costCenters = CostCenter::withDefaultGroupCompanyOrg()->get();
        $vendor = Vendor::with(['currency:id,name', 'paymentTerms:id,name'])->find($request->vendor_id);
        $currency = $vendor->currency;
        $paymentTerm = $vendor->paymentTerms;
        $shipping = $vendor->addresses()->where(function ($query) {
            $query->where('type', 'shipping')->orWhere('type', 'both');
        })->latest()->first();
        $billing = $vendor->addresses()->where(function ($query) {
            $query->where('type', 'billing')->orWhere('type', 'both');
        })->latest()->first();
        $html = view(
            'procurement.expense-allocation.partials.po-item-row',
            compact(
                'items',
                'costCenters'
            )
        )
            ->render();
        return response()->json(
            [
                'data' =>
                    [
                        'html' => $html,
                        'vendor' => $vendor,
                        'currency' => $currency,
                        'paymentTerm' => $paymentTerm,
                        'shipping' => $shipping,
                        'billing' => $billing,
                    ],
                'status' => 200,
                'message' => 'fetched.'
            ]
        );
    }

    // PO Item Rows
    public function soItemRows(Request $request)
    {
        $user = request()->user();
        $organization = Organization::where('id', $user->organization_id)->first();
        $item_ids = explode(',', $request->item_ids);
        $items = ErpSoItem::whereIn('id', $item_ids)
            ->get();
        $costCenters = CostCenter::withDefaultGroupCompanyOrg()->get();
        $html = view(
            'procurement.expense-allocation.partials.so-item-row',
            compact(
                'items',
                'costCenters'
            )
        )
            ->render();
        return response()->json(
            [
                'data' =>
                    [
                        'html' => $html,
                    ],
                'status' => 200,
                'message' => 'fetched.'
            ]
        );
    }

    # On change item attribute
    public function getItemAttribute(Request $request)
    {
        $attributeGroups = AttributeGroup::with('attributes')->where('status', ConstantHelper::ACTIVE)->get();
        $rowCount = intval($request->rowCount) ?? 1;
        $item = Item::find($request->item_id);
        $selectedAttr = $request->selectedAttr ? json_decode($request->selectedAttr, true) : [];
        $expenseDetailId = $request->detail_id ?? null;
        $itemAttIds = [];
        $itemAttributeArray = [];
        if ($expenseDetailId) {
            $detail = Detail::find($expenseDetailId);
            if ($detail) {
                $itemAttIds = collect($detail->attributes)->pluck('item_attribute_id')->toArray();
                $itemAttributeArray = $detail->item_attributes_array();
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
        $html = view('procurement.expense-allocation.partials.comp-attribute', compact('item', 'attributeGroups', 'rowCount', 'selectedAttr', 'itemAttributes'))->render();
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

    # Add discount row
    public function addDiscountRow(Request $request)
    {
        $tblRowCount = intval($request->tbl_row_count) ? intval($request->tbl_row_count) + 1 : 1;
        $rowCount = intval($request->row_count);
        $disName = $request->dis_name;
        $disPerc = $request->dis_perc;
        $disAmount = $request->dis_amount;
        $html = view('procurement.expense-allocation.partials.add-disc-row', compact('tblRowCount', 'rowCount', 'disName', 'disAmount', 'disPerc'))->render();
        return response()->json(['data' => ['html' => $html], 'status' => 200, 'message' => 'fetched.']);
    }

    # get tax calcualte
    public function taxCalculation(Request $request)
    {
        $user = request()->user();
        $location = ErpStore::find($request->location_id ?? null);

        $organization = $user->organization;
        $firstAddress = $location?->address ?? null;
        if (!$firstAddress) {
            $firstAddress = $organization?->addresses->first();
        }
        if ($firstAddress) {
            $companyCountryId = $firstAddress->country_id;
            $companyStateId = $firstAddress->state_id;
        } else {
            return response()->json(['error' => 'No address found for the organization.'], 404);
        }
        $price = $request->input('price', 6000);
        $document_date = $request->document_date ?? date('Y-m-d');
        $hsnId = null;
        $item = Item::find($request->item_id);
        if (isset($item)) {
            $hsnId = $item->hsn_id;
        } else {
            return response()->json(['error' => 'Invalid Item'], 500);
        }
        $transactionType = $request->input('transaction_type', 'purchase');
        if ($transactionType === "purchase") {
            $fromCountry = $companyCountryId;
            $fromState = $companyStateId;
            $upToCountry = $request->input('party_country_id', $companyCountryId) ?? 0;
            $upToState = $request->input('party_state_id', $companyStateId) ?? 0;
        } else {
            $fromCountry = $request->input('party_country_id', $companyCountryId) ?? 0;
            $fromState = $request->input('party_state_id', $companyStateId) ?? 0;
            $upToCountry = $companyCountryId;
            $upToState = $companyStateId;
        }
        try {
            $taxDetails = TaxHelper::calculateTax($hsnId, $price, $fromCountry, $fromState, $upToCountry, $upToState, 'purchase', $document_date);
            $rowCount = intval($request->rowCount) ?? 1;
            $itemPrice = floatval($request->price) ?? 0;
            $html = view('procurement.expense-allocation.partials.item-tax', compact('taxDetails', 'rowCount', 'itemPrice'))->render();
            return response()->json(['data' => ['html' => $html, 'rowCount' => $rowCount], 'message' => 'fetched', 'status' => 200]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Get Address
    public function getAddress(Request $request)
    {
        $user = request()->user();
        $vendorId = $request?->id ?? null;
        $type = $request?->type ?? null;
        $typeId = $request?->typeId ?? null;

        $vendor = Vendor::withDefaultGroupCompanyOrg()
            ->with(['currency:id,name', 'paymentTerms:id,name'])->find($vendorId);

        $moduleTypeId = match ($type) {
            'po' => $typeId,
            default => $vendorId,
        };

        $typeData = match ($type) {
            'po' => PurchaseOrder::withDefaultGroupCompanyOrg()
                ->with(['currency:id,name', 'paymentTerms:id,name'])
                ->find($typeId),
            default => Vendor::withDefaultGroupCompanyOrg()
                ->with(['currency:id,name', 'paymentTerms:id,name'])
                ->find($vendorId),
        };

        $currency = $typeData?->currency;
        $paymentTerm = $typeData?->paymentTerms;

        $documentDate = $request?->document_date;

        $vendorAddress = match ($type) {
            'po' => $typeData?->latestShippingAddress() ?? $typeData?->ship_address,
            default => ErpAddress::where('addressable_id', $moduleTypeId)
                ->where('addressable_type', Vendor::class)
                ->latest()
                ->first(),
        };

        if (!$vendorAddress) {
            return response()->json([
                'data' => array(
                    'error_message' => 'Address not found for ' . $vendor?->company_name
                )
            ]);
        }
        if (!isset($typeData->currency_id)) {
            return response()->json([
                'data' => array(
                    'error_message' => 'Currency not found for ' . $vendor?->company_name
                )
            ]);
        }
        if (!isset($paymentTerm)) {
            return response()->json([
                'data' => array(
                    'error_message' => 'Payment Terms not found for ' . $vendor?->company_name
                )
            ]);
        }
        $currencyData = CurrencyHelper::getCurrencyExchangeRates($typeData?->currency_id ?? 0, $documentDate ?? '');

        $storeId = $request?->store_id ?? null;
        $store = ErpStore::find($storeId);
        $locationAddress = $store?->address;

        $organization = Organization::where('id', $user->organization_id)->first();
        $organizationAddress = Address::with(['city', 'state', 'country'])
            ->where('addressable_id', $user->organization_id)
            ->where('addressable_type', Organization::class)
            ->first();
        $orgAddress = $organizationAddress?->display_address;

        return response()->json(
            [
                'data' => [
                    'status' => 200,
                    'vendor' => $vendor,
                    'message' => 'fetched',
                    'currency' => $currency,
                    'org_address' => $orgAddress,
                    'paymentTerm' => $paymentTerm,
                    'vendor_address' => $vendorAddress,
                    'currency_exchange' => $currencyData
                ],
                'delivery_address' => $locationAddress,
            ]
        );
    }

    # Get edit address modal
    public function editAddress(Request $request)
    {
        $type = $request->type;
        $addressId = $request->address_id;
        $vendor = Vendor::find($request->vendor_id ?? null);
        if (!$vendor) {
            return response()->json([
                'message' => 'Please First select vendor.',
                'error' => null,
            ], 500);
        }
        if ($request->type == 'shipping') {
            $addresses = $vendor->addresses()->where(function ($query) {
                $query->where('type', 'shipping')->orWhere('type', 'both');
            })->latest()->get();

            $selectedAddress = $vendor->addresses()->where('id', $addressId)->where(function ($query) {
                $query->where('type', 'shipping')->orWhere('type', 'both');
            })->latest()->first();
        } else {
            $addresses = $vendor->addresses()->where(function ($query) {
                $query->where('type', 'billing')->orWhere('type', 'both');
            })->latest()->get();
            $selectedAddress = $vendor->addresses()->where('id', $addressId)->where(function ($query) {
                $query->where('type', 'billing')->orWhere('type', 'both');
            })->latest()->first();
        }
        $html = '';
        if (!intval($request->onChange)) {
            $html = view('procurement.expense-allocation.partials.edit-address-modal', compact('addresses', 'selectedAddress'))->render();
        }
        return response()->json(['data' => ['html' => $html, 'selectedAddress' => $selectedAddress], 'status' => 200, 'message' => 'fetched!']);
    }

    # Save Address
    public function addressSave(Request $request)
    {
        $addressId = $request->address_id;
        $request->validate([
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
            'pincode' => 'required',
            'address' => 'required'
        ]);

        $addressType = $request->address_type;
        $vendorId = $request->hidden_vendor_id;
        $countryId = $request->country_id;
        $stateId = $request->state_id;
        $cityId = $request->city_id;
        $pincode = $request->pincode;
        $address = $request->address;

        $vendor = Vendor::find($vendorId ?? null);
        $selectedAddress = $vendor->addresses()
            ->where('id', $addressId)
            ->where(function ($query) use ($addressType) {
                if ($addressType == 'shipping') {
                    $query->where('type', 'shipping')
                        ->orWhere('type', 'both');
                } else {
                    $query->where('type', 'billing')
                        ->orWhere('type', 'both');
                }
            })
            ->first();

        $newAddress = null;

        if ($selectedAddress) {
            $newAddress = $vendor->addresses()->firstOrNew([
                'type' => $addressType ?? 'both',
            ]);
            $newAddress->fill([
                'country_id' => $countryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'pincode' => $pincode,
                'address' => $address,
                'addressable_id' => $vendorId,
                'addressable_type' => Vendor::class,
            ]);
            $newAddress->save();
        } else {
            $newAddress = $vendor->addresses()->create([
                'type' => $addressType ?? 'both',
                'country_id' => $countryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'pincode' => $pincode,
                'address' => $address,
                'addressable_id' => $vendorId,
                'addressable_type' => Vendor::class
            ]);
        }
        return response()->json(['data' => ['new_address' => $newAddress], 'status' => 200, 'message' => 'fetched!']);
    }

    public function getItemDetail(Request $request)
    {
        $selectedAttr = json_decode($request->selectedAttr, 200) ?? [];

        $purchaseOrder = null;
        $poDetail = null;
        $quantity = $request->qty;
        $headerId = $request->headerId;
        $detailId = $request->detailId;
        $item = Item::find($request->item_id ?? null);
        $uomId = $request->uom_id ?? null;
        $qty = intval($request->qty) ?? 0;
        $uomName = $item->uom->name ?? 'NA';
        if ($item->uom_id == $uomId) {
        } else {
            $alUom = $item->alternateUOMs()->where('uom_id', $uomId)->first();
            $qty = @$alUom->conversion_to_inventory * $qty;
        }
        $remark = $request->remark ?? null;
        $specifications = $item?->specifications()->whereNotNull('value')->get() ?? [];
        $purchaseOrder = PurchaseOrder::find($request->purchase_order_id);
        $poDetail = PoItem::find($request->po_detail_id);
        $type = $request->type;
        if ($type == 'po') {
            $purchaseOrder = PurchaseOrder::find($request->purchase_order_id);
            $poDetail = PoItem::find($request->po_detail_id);
        }
        if ($type == 'jo') {
            $purchaseOrder = JobOrder::find($request->job_order_id);
            // $poDetail = JoProduct::find($request->service_item_id);
            $poDetail = JoProduct::find($request->jo_detail_id);
        }
        $type = $request->type;

        $html = view(
            'procurement.expense-allocation.partials.comp-item-detail',
            compact(
                'item',
                'purchaseOrder',
                'selectedAttr',
                'remark',
                'uomName',
                'qty',
                'headerId',
                'detailId',
                'specifications',
                'poDetail',
                'type'
            )
        )->render();
        return response()->json(['data' => ['html' => $html], 'status' => 200, 'message' => 'fetched.']);
    }

    // genrate pdf
    public function generatePdf(Request $request, $id)
    {
        $user = request()->user();

        $organization = Organization::where('id', $user->organization_id)->first();
        $organizationAddress = Address::with(['city', 'state', 'country'])
            ->where('addressable_id', $user->organization_id)
            ->where('addressable_type', Organization::class)
            ->first();
        $expense = Header::with(['vendor', 'currency', 'items', 'book', 'expenses'])
            ->findOrFail($id);

        $shippingAddress = $expense->shippingAddress;
        $billingAddress = $expense->billingAddress;

        $totalItemValue = $expense->total_item_amount ?? 0.00;
        $totalDiscount = $expense->total_discount ?? 0.00;
        $totalTaxes = $expense->total_taxes ?? 0.00;
        $totalTaxableValue = ($totalItemValue - $totalDiscount);
        $totalAfterTax = ($totalTaxableValue + $totalTaxes);
        $totalExpense = $expense->expense_amount ?? 0.00;
        $totalAmount = ($totalAfterTax + $totalExpense);
        $amountInWords = NumberHelper::convertAmountToWords($expense->total_amount);
        // Path to your image (ensure the file exists and is accessible)
        $imagePath = public_path('assets/css/midc-logo.jpg'); // Store the image in the public directory
        $docStatusClass = ConstantHelper::DOCUMENT_STATUS_CSS[$expense->document_status] ?? '';
        $taxes = Ted::where('header_id', $expense->id)
            ->where('ted_type', 'Tax')
            ->select('ted_type', 'ted_id', 'ted_name', 'ted_percentage', DB::raw('SUM(ted_amount) as total_amount'), DB::raw('SUM(assesment_amount) as total_assesment_amount'))
            ->groupBy('ted_name', 'ted_percentage')
            ->get();
        $sellerShippingAddress = $expense->latestShippingAddress();
        $sellerBillingAddress = $expense->latestBillingAddress();
        $buyerAddress = $expense?->erpStore?->address;

        $pdf = PDF::loadView(
            'pdf.expense',
            [
                'exp' => $expense,
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
                'docStatusClass' => $docStatusClass,
                'taxes' => $taxes,
                'sellerShippingAddress' => $sellerShippingAddress,
                'sellerBillingAddress' => $sellerBillingAddress,
                'buyerAddress' => $buyerAddress
            ]
        );

        $fileName = 'Expense-Advice-' . date('Y-m-d') . '.pdf';
        return $pdf->stream($fileName);
    }

    # Submit Amendment
    public function amendmentSubmit(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Header
            $expenseAlc = Header::find($id);
            if (!$expenseAlc) {
                return response()->json([
                    'error' => 'Expense not found.'
                ], 404);
            }
            if (!in_array($expenseAlc->document_status, [ConstantHelper::APPROVED])) {
                return response()->json([
                    'error' => 'Only Approved/Posted document can be amended.',
                ], 404);
            }
            $currentStatus = $expenseAlc->document_status;
            $actionType = 'amendment';
            $attachments = $request->file('amend_attachment');
            $attachment = $request->file('attachment');
            $amendementService = new AmendmentService();
            $amendementResponse = $amendementService->submit($request->all(), $expenseAlc, $attachments, $attachment);
            if ($amendementResponse['status'] === 'error') {
                DB::rollBack();
                return response()->json([
                    'error' => $amendementResponse['message']
                ], 404);
            }

            DB::commit();
            return response()->json([
                'message' => 'Amendement done successfully!',
                'data' => $expenseAlc,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error occurred while amendement.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    # Get PO Item List
    public function getPo(Request $request)
    {
        $query = $this->buildPoQuery($request);
        return DataTables::of($query)
            ->addColumn('select_checkbox', function ($row) use ($request) {
                $moduleType = 'p-order';
                $ref_no = ($row?->po?->book?->book_code ?? 'NA') . '-' . ($row?->po?->document_number ?? 'NA');

                $dataCurrentPo = ($row->purchase_order_id ?? 'null');

                $decoded = urldecode(urldecode($request->selected_po_ids));
                $selected_po_ids = json_decode($decoded, true) ?? [];
                $poDetail = PoItem::find($selected_po_ids)->pluck('purchase_order_id')->toArray();
                $dataExistingPo = $request->type == 'create' && $row?->purchase_order_id
                    ? ($selected_po_ids[0] ?? 'null')
                    : 'null';

                // Determine if checkbox should be disabled
                if (empty($selected_po_ids)) {
                    $disabled = '';
                } else {
                    $disabled = (!in_array($dataCurrentPo, $poDetail)) ? 'disabled' : '';
                }

                return "<div class='form-check form-check-inline me-0'>
                            <input class='form-check-input po_item_checkbox' type='checkbox' name='po_item_check' value='{$row->id}' data-module='{$moduleType}' data-current-po='{$dataCurrentPo}' data-existing-po='{$dataExistingPo}' {$disabled}>
                            <input type='hidden' name='reference_no' id='reference_no' value='{$ref_no}'>
                        </div>";
            })
            ->addColumn('vendor', fn($row) => $row?->po?->vendor?->company_name ?? 'NA')
            ->addColumn('po_doc', fn($row) => ($row?->po?->book?->book_code ?? 'NA') . ' - ' . ($row?->po?->document_number ?? 'NA'))
            ->addColumn('po_date', fn($row) => $row?->po?->getFormattedDate('document_date') ?? '-')
            ->addColumn('item_code', fn($row) => $row?->item?->item_code ?? 'NA')
            ->addColumn('item_name', fn($row) => $row?->item?->item_name ?? 'NA')
            ->addColumn('attributes', function ($row) {
                return $row?->attributes->map(function ($attr) {
                    return "<span class='badge rounded-pill badge-light-primary'><strong>{$attr->headerAttribute->name}</strong>: {$attr->headerAttributeValue->value}</span>";
                })->implode(' ');
            })
            ->addColumn('order_qty', function ($row) {
                return number_format((($row->order_qty ?? 0) - ($row->short_close_qty ?? 0)), 2);
            })
            ->addColumn('inv_order_qty', function ($row) {
                if ($row?->po?->supp_invoice_required == 'yes') {
                    return number_format((($row->balance_qty ?? 0)), 2);
                }
                return number_format(0, 2);
            })
            ->addColumn('expense_advise_qty', fn($row) => number_format(($row->expense_advise_qty ?? 0), 2))
            ->addColumn('balance_qty', function ($row) {
                $orderQty = ($row->order_qty ?? 0) - ($row->short_close_qty ?? 0);
                $expQty = $row->expense_advise_qty ?? 0;
                if ($row?->po?->supp_invoice_required == 'yes') {
                    $orderQty = ($row->balance_qty ?? 0);
                }
                return number_format(($orderQty - $expQty), 2);
            })
            ->addColumn('rate', fn($row) => number_format(($row->rate ?? 0), 2))
            ->addColumn('total_amount', function ($row) {
                $orderQty = ($row->order_qty ?? 0) - ($row->short_close_qty ?? 0);
                $expQty = $row->expense_advise_qty ?? 0;
                if ($row?->po?->supp_invoice_required == 'yes') {
                    $orderQty = ($row->balance_qty ?? 0);
                }
                return number_format(($orderQty - $expQty) * ($row->rate ?? 0), 2);
            })
            ->rawColumns([
                'select_checkbox',
                'attributes',
                'vendor',
                'po_doc',
                'po_date',
                'item_code',
                'item_name',
                'order_qty',
                'inv_order_qty',
                'expense_advise_qty',
                'balance_qty',
                'rate',
                'total_amount'
            ])
            ->make(true);
    }

    # This for both bulk and single po
    protected function buildPoQuery(Request $request)
    {
        $documentDate = $request->document_date ?? null;
        $seriesId = $request->series_id ?? null;
        $docNumber = $request->document_number ?? null;
        $itemId = $request->item_id ?? null;
        $storeId = $request->store_id ?? null;
        $vendorId = $request->vendor_id ?? null;
        $headerBookId = $request->header_book_id ?? null;
        $itemSearch = $request->item_search ?? null;

        $decoded = urldecode(urldecode($request->selected_po_ids));
        $selected_po_ids = json_decode($decoded, true) ?? [];

        $keys = [
            'po_header_ids',
            'po_details_ids',
        ];

        foreach ($keys as $key) {
            $$key = $request->$key ?? null;

            if (is_string($$key)) {
                $decoded = urldecode(urldecode($key));

                if (strpos($decoded, ',') !== false) {
                    $$key = array_filter(explode(',', $decoded));
                } else {
                    $$key = strlen($decoded) ? [$decoded] : [];
                }
            } elseif (!is_array($$key)) {
                $$key = [];
            }
        }

        $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($headerBookId);

        $poItems = PoItem::select(
            'erp_po_items.*',
            'erp_purchase_orders.id as po_id',
            'erp_purchase_orders.vendor_id',
            'erp_purchase_orders.book_id',
            'erp_purchase_orders.currency_id',
        )
            ->leftJoin('erp_purchase_orders', 'erp_purchase_orders.id', 'erp_po_items.purchase_order_id')
            ->whereIn('erp_purchase_orders.book_id', $applicableBookIds)
            ->where('erp_purchase_orders.gate_entry_required', 'no')
            ->where('erp_purchase_orders.supp_invoice_required', 'no')
            ->whereNull('erp_po_items.exp_allocation_id')
            ->whereHas('item', function ($item) use ($itemSearch) {
                $item->where('type', 'Service');
                if ($itemSearch) {
                    $item->where(function ($query) use ($itemSearch) {
                        $query->where('erp_items.item_name', 'LIKE', "%{$itemSearch}%")
                            ->orWhere('erp_items.item_code', 'LIKE', "%{$itemSearch}%");
                    });
                }
            })
            ->whereHas('po', function ($po) use ($seriesId, $docNumber, $vendorId, $storeId) {
                $po->whereIn('document_status', [
                    ConstantHelper::APPROVED,
                    ConstantHelper::APPROVAL_NOT_REQUIRED,
                    ConstantHelper::POSTED
                ]);
                if ($seriesId) {
                    $po->where('erp_purchase_orders.book_id', $seriesId);
                }
                if ($docNumber) {
                    $po->where('erp_purchase_orders.id', $docNumber);
                }
                if ($vendorId) {
                    $po->where('erp_purchase_orders.vendor_id', $vendorId);
                }
                if ($storeId) {
                    $po->where('erp_purchase_orders.store_id', $storeId);
                }
            });

        if ($itemId) {
            $poItems->where('item_id', $itemId);
        }

        if (count($selected_po_ids)) {
            $poItems->whereNotIn('erp_po_items.id', $selected_po_ids);
            if (count($po_header_ids)) {
                $poItems->whereIn('erp_purchase_orders.id', $header_ids);
            }
            if (count($po_details_ids)) {
                $poItems->whereNotIn('erp_po_items.id', $po_details_ids);
            }
        }

        $poItems = $poItems->orderBy('po_id', 'desc')->get();

        $poItemMap = [];
        foreach ($poItems as $poItem) {
            $poItemId = $poItem->id;
            if (!isset($poItemMap[$poItemId])) {
                $poItem->balance_qty = ($poItem->order_qty - $poItem->short_close_qty);
                $poItemMap[$poItemId] = $poItem;
            }
        }

        return $poItemMap;
    }

    # Process PO Item list
    public function processPoItem(Request $request)
    {
        $user = request()->user();
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $currency = ModelsCurrency::find($organization->currency_id);
        $ids = json_decode($request->ids, true) ?? [];
        $tableRowCount = $request->tableRowCount ?: 0;

        $distributionTypes = ConstantHelper::getDistributionTypes();

        $poItems = PoItem::whereIn('id', $ids)
            ->get();

        $html = view(
            'procurement.expense-allocation.partials.po-item-row',
            [
                'poItems' => $poItems,
                'currency' => $currency,
                'tableRowCount' => $tableRowCount,
                'distributionTypes' => $distributionTypes,
            ]
        )
            ->render();

        return response()->json(
            [
                'data' => [
                    'pos' => $html
                ],
                'status' => 200,
                'message' => "fetched!"
            ]
        );
    }

    # Get GRN Item List
    public function getGrn(Request $request)
    {
        $queryData = $this->buildGrnQuery($request);
        $mrnItemsQuery = $queryData['mrn_items'];
        return DataTables::of($mrnItemsQuery)
            ->addColumn('select_checkbox', function ($row) use ($request) {
                $moduleType = 'mrn-order';
                $ref_no = ($row?->header?->book?->book_code ?? 'NA') . '-' . ($row?->header?->document_number ?? 'NA');

                $dataCurrentMrn = ($row->mrn_header_id ?? 'null');
                $decoded = urldecode(urldecode($request->selected_mrn_ids));
                $selected_mrn_ids = json_decode($decoded, true) ?? [];
                $mrnDetail = MrnDetail::find($selected_mrn_ids)->pluck('mrn_header_id')->toArray();
                $dataExistingMrn = $request->type == 'create' && $row?->mrn_header_id
                    ? ($selected_mrn_ids[0] ?? 'null')
                    : 'null';

                // Determine if checkbox should be disabled
                if (empty($selected_mrn_ids)) {
                    $disabled = '';
                } else {
                    $disabled = (!in_array($dataCurrentMrn, $mrnDetail)) ? 'disabled' : '';
                }

                return "<div class='form-check form-check-inline me-0'>
                            <input class='form-check-input grn_item_checkbox' type='checkbox' name='grn_item_checkbox' value='{$row->id}' data-module='{$moduleType}' data-current-grn='{$dataCurrentMrn}' data-existing-grn='{$dataExistingMrn}' {$disabled}>
                            <input type='hidden' name='reference_no' id='reference_no' value='{$ref_no}'>
                        </div>";
            })
            ->addColumn(
                'vendor',
                fn($row) =>
                $row?->vendor_name ?? 'NA'
            )
            ->addColumn(
                'doc_no',
                fn($row) =>
                ($row?->book_code ?? 'NA') . ' - ' . ($row?->document_number ?? 'NA')
            )
            ->addColumn(
                'doc_date',
                fn($row) =>
                $row?->mrnHeader?->getFormattedDate('document_date') ?? ''
            )
            ->addColumn(
                'item_code',
                fn($row) =>
                $row?->item_code ?? 'NA'
            )
            ->addColumn(
                'item_name',
                fn($row) =>
                $row?->item?->item_name ?? ''
            )
            ->addColumn(
                'attributes',
                fn($row) =>
                app(\App\View\Components\PR\Attribute::class, ['row' => $row])->resolveView()->render()
            )
            ->addColumn(
                'order_qty',
                fn($row) =>
                number_format((float) $row?->order_qty ?? 0, 2)
            )
            ->addColumn('available_qty', function ($row) {
                $convertedQty = \App\Helpers\ItemHelper::convertToAltUom(
                    $row->item_id,
                    $row->uom_id,
                    (float) $row->available_qty ?? 0
                );

                return number_format($convertedQty, 2);
            })
            ->addColumn(
                'rate',
                fn($row) =>
                number_format((float) $row?->rate ?? 0, 2)
            )
            ->addColumn('amount', function ($row) {
                $convertedQty = \App\Helpers\ItemHelper::convertToAltUom(
                    $row->item_id,
                    $row->uom_id,
                    (float) $row->available_qty ?? 0
                );

                return number_format(($convertedQty ?? 0) * ($row->rate ?? 0), 2);
            })
            ->addColumn(
                'uom',
                fn($row) =>
                $row?->uom?->name ?? ''
            )
            ->addColumn(
                'remark',
                fn($row) =>
                $row?->remark ?? ''
            )
            ->rawColumns([
                'select_checkbox',
                'doc_no',
                'doc_date',
                'item_code',
                'item_name',
                'uom',
                'remark',
                'attributes'
            ])
            ->make(true);
    }


    # This for both bulk and single mrn
    protected function buildGrnQuery(Request $request)
    {
        $finalData = array();
        $applicableBookIds = array();
        $seriesId = $request->series_id ?? null;
        $docNumber = $request->document_number ?? null;
        $vendorId = $request->vendor_id ?? null;
        $headerBookId = $request->header_book_id ?? null;
        $storeId = $request->store_id ?? null;
        $itemSearch = $request->item_search ?? null;
        $applicableBookIds = ServiceParametersHelper::getBookCodesForReferenceFromParam($headerBookId);

        $decoded = urldecode(urldecode($request->selected_po_ids));
        $selected_mrn_ids = json_decode($decoded, true) ?? [];

        $keys = [
            'grn_header_ids',
            'grn_details_ids',
        ];

        foreach ($keys as $key) {
            $$key = $request->$key ?? null;

            if (is_string($$key)) {
                $decoded = urldecode(urldecode($key));

                if (strpos($decoded, ',') !== false) {
                    $$key = array_filter(explode(',', $decoded));
                } else {
                    $$key = strlen($decoded) ? [$decoded] : [];
                }
            } elseif (!is_array($$key)) {
                $$key = [];
            }
        }

        $mrnItems = MrnDetail::query()
            ->select([
                'erp_mrn_details.*',
                'erp_mrn_headers.id as mrn_header_id',
                'erp_mrn_headers.vendor_id',
                'erp_vendors.company_name as vendor_name',
                'erp_mrn_headers.book_id',
                'erp_mrn_headers.book_code',
                \DB::raw('stock_ledger.receipt_qty' . ' as available_qty'),
                'stock_ledger.document_header_id',
                'stock_ledger.document_detail_id',
                'stock_ledger.lot_number as lg_lot_number',
                'stock_ledger.utilized_id',
                'stock_ledger.book_type',
                'stock_ledger.document_number',
                'stock_ledger.transaction_type',
                'stock_ledger.deleted_at as stock_ledger_del_at',
                'stock_ledger.document_status as stock_ledger_status',
            ])
            ->join('stock_ledger', function ($join) use ($storeId) {
                $join->on('stock_ledger.document_detail_id', '=', 'erp_mrn_details.id')
                    ->where('stock_ledger.book_type', '=', ConstantHelper::MRN_SERVICE_ALIAS)
                    ->whereColumn('stock_ledger.document_detail_id', '=', 'erp_mrn_details.id')
                    ->whereRaw('stock_ledger.receipt_qty > 0')
                    ->where('stock_ledger.store_id', $storeId)
                    ->whereNull('stock_ledger.utilized_id')
                    ->where('stock_ledger.transaction_type', 'receipt')
                    ->whereNull('stock_ledger.deleted_at'); // ✅ this line is required;
            })
            ->leftJoin('erp_mrn_headers', 'erp_mrn_headers.id', '=', 'erp_mrn_details.mrn_header_id')
            ->leftJoin('erp_vendors', 'erp_vendors.id', '=', 'erp_mrn_headers.vendor_id');

        // Stock ledger status filter
        $mrnItems->whereIn('stock_ledger.document_status', [
            ConstantHelper::APPROVED,
            ConstantHelper::APPROVAL_NOT_REQUIRED,
            ConstantHelper::POSTED
        ]);

        // Filter related to item/mrnHeader
        $mrnItems->where(function ($query) use ($seriesId, $applicableBookIds, $docNumber, $vendorId, $storeId, $itemSearch) {
            $query->whereHas('item', function ($item) use ($itemSearch) {
                $item->where('type', 'Goods');
                if ($itemSearch) {
                    $item->where(function ($query) use ($itemSearch) {
                        $query->where('erp_items.item_name', 'LIKE', "%{$itemSearch}%")
                            ->orWhere('erp_items.item_code', 'LIKE', "%{$itemSearch}%");
                    });
                }
            });

            $query->whereHas('mrnHeader', function ($mrn) use ($seriesId, $applicableBookIds, $docNumber, $vendorId, $storeId) {
                $mrn->withDefaultGroupCompanyOrg();
                $mrn->whereIn('document_status', [
                    ConstantHelper::APPROVED,
                    ConstantHelper::APPROVAL_NOT_REQUIRED,
                    ConstantHelper::POSTED
                ]);

                if ($seriesId) {
                    $mrn->where('book_id', $seriesId);
                } elseif (!empty($applicableBookIds)) {
                    $mrn->whereIn('book_id', $applicableBookIds);
                }

                if ($docNumber) {
                    $mrn->where('id', $docNumber);
                }

                if ($vendorId) {
                    $mrn->where('vendor_id', $vendorId);
                }

                if ($storeId) {
                    $mrn->where('store_id', $storeId);
                }
            });

            if ($itemSearch) {
                $query->whereHas('item', function ($query) use ($itemSearch) {
                    $query->searchByKeywords($itemSearch);
                });
            }
        });

        if (count($selected_mrn_ids)) {
            $mrnItems->whereNotIn('erp_mrn_details.id', $selected_mrn_ids);
            // if (count($grn_header_ids)) {
            //     $mrnItems->whereIn('erp_mrn_details.mrn_header_id', $grn_header_ids);
            // }
            if (count($grn_details_ids)) {
                $mrnItems->whereNotIn('erp_mrn_details.id', $grn_details_ids);
            }
        }

        // ❌ Do not call get()
        // ✅ Return query
        $finalData = [
            'mrn_items' => $mrnItems
        ];

        return $finalData;

    }

    # Process Mrn Item
    public function processGrnItem(Request $request)
    {
        $user = request()->user();
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $currency = ModelsCurrency::find($organization->currency_id);

        // Filters and config
        $ids = json_decode($request->ids, true) ?? [];
        $tableRowCount = $request->tableRowCount ?: 0;
        $distributionTypes = ConstantHelper::getDistributionTypes();

        $uniqueMrnIds = MrnDetail::whereIn('id', $ids)
            ->distinct()
            ->pluck('mrn_header_id')
            ->toArray();

        // MRN detail query with stock_ledger join
        $mrnItems = MrnDetail::query()
            ->select([
                'erp_mrn_details.*',
                'erp_mrn_headers.id as mrn_header_id',
                'erp_mrn_headers.vendor_id',
                'erp_vendors.company_name as vendor_name',
                'erp_mrn_headers.book_id',
                'erp_mrn_headers.book_code',
                \DB::raw('stock_ledger.receipt_qty as available_qty'),
                'stock_ledger.document_header_id',
                'stock_ledger.document_detail_id',
                'stock_ledger.lot_number as lg_lot_number',
                'stock_ledger.utilized_id',
                'stock_ledger.book_type',
                'stock_ledger.document_number',
                'stock_ledger.document_status as stock_ledger_status',
            ])
            ->join('stock_ledger', function ($join) {
                $join->on('stock_ledger.document_detail_id', '=', 'erp_mrn_details.id')
                    ->where('stock_ledger.book_type', '=', ConstantHelper::MRN_SERVICE_ALIAS)
                    ->whereRaw('stock_ledger.receipt_qty > 0')
                    // ->where('stock_ledger.store_id', $storeId)
                    // ->where('stock_ledger.sub_store_id', $subStoreId)
                    ->whereNull('stock_ledger.utilized_id')
                    ->where('stock_ledger.transaction_type', 'receipt')
                    ->whereNull('stock_ledger.deleted_at'); // ✅ this line is required;
            })
            ->leftJoin('erp_mrn_headers', 'erp_mrn_headers.id', '=', 'erp_mrn_details.mrn_header_id')
            ->leftJoin('erp_vendors', 'erp_vendors.id', '=', 'erp_mrn_headers.vendor_id')
            ->whereIn('stock_ledger.document_status', [
                ConstantHelper::APPROVED,
                ConstantHelper::APPROVAL_NOT_REQUIRED,
                ConstantHelper::POSTED
            ])
            ->whereIn('erp_mrn_details.id', $ids)
            ->get();

        $uniqueMrnIds = $mrnItems->pluck('mrn_header_id')->unique()->values()->toArray();
        $mrnHeader = MrnHeader::find($uniqueMrnIds[0]);

        // UI + Location + HTML render
        $html = view('procurement.expense-allocation.partials.grn-item-row', [
            'mrnItems' => $mrnItems,
            'currency' => $currency,
            'tableRowCount' => $tableRowCount,
            'distributionTypes' => $distributionTypes,
        ])->render();

        return response()->json([
            'data' => [
                'pos' => $html,
                'mrnHeader' => $mrnHeader,
            ],
            'status' => 200,
            'message' => "fetched!"
        ]);
    }

    public function getPostingDetails(Request $request)
    {
        try {
            $data = FinancialPostingHelper::financeVoucherPosting($request->book_id ?? 0, $request->document_id ?? 0, "get");
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'status' => 'exception',
                'message' => 'Some internal error occured',
                'error' => $ex->getMessage()
            ]);
        }
    }

    public function postExpenseAllocation(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = FinancialPostingHelper::financeVoucherPosting($request->book_id ?? 0, $request->document_id ?? 0, "post");
            if ($data['status']) {
                \DB::commit();
            } else {
                \DB::rollBack();
            }
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $ex) {
            \DB::rollBack();
            return response()->json([
                'status' => 'exception',
                'message' => 'Some internal error occured',
                'error' => $ex->getMessage()
            ]);
        }
    }

    // Revoke Document
    public function revokeDocument(Request $request)
    {
        DB::beginTransaction();
        try {
            $expense = Header::find($request->id);
            if (isset($expense)) {
                $revoke = Helper::approveDocument($expense->book_id, $expense->id, $expense->revision_number, '', [], 0, ConstantHelper::REVOKE, $expense->total_amount, get_class($expense));
                if ($revoke['message']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => $revoke['message'],
                    ]);
                } else {
                    $expense->document_status = $revoke['approvalStatus'];
                    $expense->save();
                    DB::commit();
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Revoked succesfully',
                    ]);
                }
            } else {
                DB::rollBack();
                throw new ApiGenericException("No Document found");
            }
        } catch (Exception $ex) {
            DB::rollBack();
            throw new ApiGenericException($ex->getMessage());
        }
    }

    // Expense Advise Report
    public function Report()
    {
        $user = request()->user();
        $categories = Category::withDefaultGroupCompanyOrg()->where('parent_id', null)->get();
        $sub_categories = Category::withDefaultGroupCompanyOrg()->where('parent_id', '!=', null)->get();
        $items = Item::withDefaultGroupCompanyOrg()->get();
        $vendors = Vendor::withDefaultGroupCompanyOrg()->get();
        $employees = Employee::where('organization_id', $user->organization_id)->get();
        $users = AuthUser::where('organization_id', Helper::getAuthenticatedUser()->organization_id)
            ->where('status', ConstantHelper::ACTIVE)
            ->get();
        $attribute_groups = AttributeGroup::withDefaultGroupCompanyOrg()->get();
        $purchaseOrderIds = Header::withDefaultGroupCompanyOrg()
            ->distinct()
            ->pluck('purchase_order_id');
        $purchaseOrders = PurchaseOrder::whereIn('id', $purchaseOrderIds)->get();
        $soIds = Detail::whereHas('expenseHeader', function ($query) {
            $query->withDefaultGroupCompanyOrg();
        })
            ->distinct()
            ->pluck('so_id');

        $so = ErpSaleOrder::whereIn('id', $soIds)->get();
        $gateEntry = Header::withDefaultGroupCompanyOrg()->get();
        $statusCss = ConstantHelper::DOCUMENT_STATUS_CSS_LIST;
        // $attributes = Attribute::get();
        return view('procurement.expense-allocation.detail_report', compact('categories', 'sub_categories', 'items', 'vendors', 'employees', 'users', 'attribute_groups', 'so', 'purchaseOrders', 'gateEntry', 'statusCss'));
    }

    public function getReportFilter(Request $request)
    {
        $user = request()->user();
        $period = $request->query('period');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $poId = $request->query('poNo');
        $gateEntryId = $request->query('gateEntryNo');
        $soId = $request->query('soNo');
        $vendorId = $request->query('vendor');
        $itemId = $request->query('item');
        $status = $request->query('status');
        $mCategoryId = $request->query('m_category');
        $mSubCategoryId = $request->query('m_subCategory');
        $mAttribute = $request->query('m_attribute');
        $mAttributeValue = $request->query('m_attributeValue');

        $query = Header::query()
            ->withDefaultGroupCompanyOrg();

        if ($poId) {
            $query->where('purchase_order_id', $poId);
        }
        if ($gateEntryId) {
            $query->where('id', $gateEntryId);
        }

        $query->with([
            'items' => function ($query) use ($itemId, $soId, $mCategoryId, $mSubCategoryId) {
                $query->whereHas('item', function ($q) use ($itemId, $soId, $mCategoryId, $mSubCategoryId) {
                    if ($itemId) {
                        $q->where('id', $itemId);
                    }
                    if ($soId) {
                        $q->where('so_id', $soId);
                    }
                    if ($mCategoryId) {
                        $q->where('category_id', $mCategoryId);
                    }
                    if ($mSubCategoryId) {
                        $q->where('subcategory_id', $mSubCategoryId);
                    }
                });
            },
            'items.item',
            'items.item.category',
            'items.item.subCategory',
            'vendor',
            'items.so',
            'po'
        ]);

        // Date Filtering
        if (($startDate && $endDate) || $period) {
            if ($startDate && $endDate) {
                $startDate = Carbon::createFromFormat('d-m-Y', $startDate);
                $endDate = Carbon::createFromFormat('d-m-Y', $endDate);
            }
            if (!$startDate || !$endDate) {
                switch ($period) {
                    case 'this-month':
                        $startDate = Carbon::now()->startOfMonth();
                        $endDate = Carbon::now()->endOfMonth();
                        break;
                    case 'last-month':
                        $startDate = Carbon::now()->subMonth()->startOfMonth();
                        $endDate = Carbon::now()->subMonth()->endOfMonth();
                        break;
                    case 'this-year':
                        $startDate = Carbon::now()->startOfYear();
                        $endDate = Carbon::now()->endOfYear();
                        break;
                }
            }
            $query->whereBetween('document_date', [$startDate, $endDate]);
        }

        // Vendor Filter
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        // Status Filter
        if ($status) {
            $query->where('document_status', $status);
        }

        // Fetch Results
        $po_reports = $query->get();

        DB::enableQueryLog();

        return response()->json($po_reports);
    }

    public function addScheduler(Request $request)
    {
        try {
            $headers = $request->input('displayedHeaders');
            $data = $request->input('displayedData');
            $itemName = '';
            $poNo = '';
            $gateEntryNo = '';
            $soNo = '';
            $lotNo = '';
            $status = '';
            $vendorName = '';
            $categoryName = '';
            $subCategoriesName = '';
            $formattedstartDate = '';
            $formattedendDate = '';
            $startDate = '';
            $endDate = '';
            if ($request->filled('startDate')) {
                $startDate = new DateTime($request->input('startDate'));
            }

            if ($request->filled('endDate')) {
                $endDate = new DateTime($request->input('endDate'));
            }
            $period = $request->input('period');

            if (($startDate && $endDate) || $period) {
                if (!$startDate || !$endDate) {
                    switch ($period) {
                        case 'this-month':
                            $startDate = Carbon::now()->startOfMonth();
                            $endDate = Carbon::now()->endOfMonth();
                            break;
                        case 'last-month':
                            $startDate = Carbon::now()->subMonth()->startOfMonth();
                            $endDate = Carbon::now()->subMonth()->endOfMonth();
                            break;
                        case 'this-year':
                            $startDate = Carbon::now()->startOfYear();
                            $endDate = Carbon::now()->endOfYear();
                            break;
                    }
                }
                $formattedstartDate = $startDate->format('d-m-y');
                $formattedendDate = $endDate->format('d-m-y');
            }

            if ($request->filled('po_no')) {
                $poData = PurchaseOrder::find($request->input('po_no'));
                $poNo = optional($poData)->document_number;
            }

            if ($request->filled('so_no')) {
                $soData = ErpSaleOrder::find($request->input('so_no'));
                $soNo = optional($soData)->document_number;
            }

            if ($request->filled('gate_entry_no')) {
                $gateEntryNo = $request->input('gate_entry_no');
            }

            if ($request->filled('lot_no')) {
                $lotNo = $request->input('lot_no');
            }

            if ($request->filled('status')) {
                $status = $request->input('status');
            }

            if ($request->filled('m_category')) {
                $categories = Category::find($request->input('m_category'));
                $categoryName = optional($categories)->name;
            }

            if ($request->filled('m_subCategory')) {
                $subCategories = Category::find($request->input('m_subCategory'));
                $subCategoriesName = optional($subCategories)->name;
            }

            if ($request->filled('item')) {
                $itemData = ErpItem::find($request->input('item'));
                $itemName = optional($itemData)->item_name;
            }

            if ($request->filled('vendor')) {
                $vendorData = ErpVendor::find($request->input('vendor'));
                $vendorName = optional($vendorData)->company_name;
            }

            $blankSpaces = count($headers) - 1;
            $centerPosition = (int) floor($blankSpaces / 2);
            $filters = [
                'Filters',
                'Item: ' . $itemName,
                'Vendor: ' . $vendorName,
                'PO No: ' . $poNo,
                'SO No: ' . $soNo,
                'Status:' . $status,
                'Category:' . $categoryName,
                'Sub Category' . $subCategoriesName,
            ];

            $fileName = 'expense-advise.xlsx';
            $filePath = storage_path('app/public/expense-advise/' . $fileName);
            $directoryPath = storage_path('app/public/expense-advise');
            if ($formattedstartDate && $formattedendDate) {
                $customHeader = array_merge(
                    array_fill(0, $centerPosition, ''),
                    ['Expense Advise Report(From ' . $formattedstartDate . ' to ' . $formattedendDate . ')'],
                    array_fill(0, $blankSpaces - $centerPosition, '')
                );
            } else {
                $customHeader = array_merge(
                    array_fill(0, $centerPosition, ''),
                    ['Expense Advise Report'],
                    array_fill(0, $blankSpaces - $centerPosition, '')
                );
            }

            $remainingSpaces = $blankSpaces - count($filters) + 1;
            $filterHeader = array_merge($filters, array_fill(0, $remainingSpaces, ''));

            $excelData = Excel::raw(new ExpenseAdviceExport($customHeader, $filterHeader, $headers, $data), \Maatwebsite\Excel\Excel::XLSX);

            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0755, true);
            }
            file_put_contents($filePath, $excelData);
            if (!file_exists($filePath)) {
                throw new \Exception('File does not exist at path: ' . $filePath);
            }

            $email_to = $request->email_to ?? [];
            $email_cc = $request->email_cc ?? [];

            foreach ($email_to as $email) {
                $user = AuthUser::where('email', $email)
                    ->where('organization_id', Helper::getAuthenticatedUser()->organization_id)
                    ->where('status', ConstantHelper::ACTIVE)
                    ->get();

                if ($user->isEmpty()) {
                    $user = new AuthUser();
                    $user->email = $email;
                }
                $title = "Expense Advise Report Generated";
                $heading = "Expense Advise Report";

                $remarks = $request->remarks ?? null;
                $mail_from = '';
                $mail_from_name = '';
                $cc = implode(', ', $email_cc);
                $bcc = null;
                $attachment = $filePath ?? null;
                // $name = $user->name;
                $description = <<<HTML
                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); font-family: Arial, sans-serif; line-height: 1.6;">
                    <tr>
                        <td>
                            <h2 style="color: #2c3e50; font-size: 24px; margin-bottom: 20px;">{$heading}</h2>
                            <p style="font-size: 16px; color: #555; margin-bottom: 20px;">
                                Dear <strong style="color: #2c3e50;">user</strong>,
                            </p>

                            <p style="font-size: 15px; color: #333; margin-bottom: 20px;">
                                We hope this email finds you well. Please find your expense advise report attached below.
                            </p>
                            <p style="font-size: 15px; color: #333; margin-bottom: 30px;">
                                <strong>Remark:</strong> {$remarks}
                            </p>
                            <p style="font-size: 14px; color: #777;">
                                If you have any questions or need further assistance, feel free to reach out to us.
                            </p>
                        </td>
                    </tr>
                </table>
                HTML;
                self::sendMail($user, $title, $description, $cc, $bcc, $attachment, $mail_from, $mail_from_name);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'emails sent successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function sendMail($receiver, $title, $description, $cc = null, $bcc = null, $attachment, $mail_from = null, $mail_from_name = null)
    {
        if (!$receiver || !isset($receiver->email)) {
            return "Error: Receiver details are missing or invalid.";
        }

        dispatch(new SendEmailJob($receiver, $mail_from, $mail_from_name, $title, $description, $cc, $bcc, $attachment));
        return response()->json([
            'status' => 'success',
            'message' => 'Email request sent succesfully',
        ]);
    }

    public function expenseAdviseReport(Request $request)
    {
        $user = request()->user();
        $pathUrl = route('expense-adv.index');
        $orderType = ConstantHelper::EXP_ALC_SERVICE_ALIAS;
        $expenseAdvises = Header::withDefaultGroupCompanyOrg()
            // ->where('document_type', $orderType)
            // ->bookViewAccess($pathUrl)
            ->withDraftListingLogic()
            ->orderByDesc('id');

        // Vendor Filter
        $expenseAdvises = $expenseAdvises->when($request->vendor, function ($vendorQuery) use ($request) {
            $vendorQuery->where('vendor_id', $request->vendor);
        });

        // PO No Filter
        $expenseAdvises = $expenseAdvises->when($request->po_no, function ($poQuery) use ($request) {
            $poQuery->where('purchase_order_id', $request->po_no);
        });

        // Document Status Filter
        $expenseAdvises = $expenseAdvises->when($request->status, function ($docStatusQuery) use ($request) {
            $searchDocStatus = [];
            if ($request->status === ConstantHelper::DRAFT) {
                $searchDocStatus = [ConstantHelper::DRAFT];
            } else if ($request->status === ConstantHelper::SUBMITTED) {
                $searchDocStatus = [ConstantHelper::SUBMITTED, ConstantHelper::PARTIALLY_APPROVED];
            } else {
                $searchDocStatus = [ConstantHelper::APPROVAL_NOT_REQUIRED, ConstantHelper::APPROVED];
            }
            $docStatusQuery->whereIn('document_status', $searchDocStatus);
        });

        // Date Filters
        $dateRange = $request->date_range ?? Carbon::now()->startOfMonth()->format('Y-m-d') . " to " . Carbon::now()->endOfMonth()->format('Y-m-d');
        $expenseAdvises = $expenseAdvises->when($dateRange, function ($dateRangeQuery) use ($request, $dateRange) {
            $dateRanges = explode('to', $dateRange);
            if (count($dateRanges) == 2) {
                $fromDate = Carbon::parse(trim($dateRanges[0]))->format('Y-m-d');
                $toDate = Carbon::parse(trim($dateRanges[1]))->format('Y-m-d');
                $dateRangeQuery->whereDate('document_date', ">=", $fromDate)->where('document_date', '<=', $toDate);
            }
        });

        // Item Id Filter
        // $materialReceipts = $materialReceipts->when($request->item_id, function ($itemQuery) use ($request) {
        //     $itemQuery->withWhereHas('items', function ($itemSubQuery) use ($request) {
        //         $itemSubQuery->where('item_id', $request->item_id)
        //             // Compare Item Category
        //             ->when($request->item_category_id, function ($itemCatQuery) use ($request) {
        //                 $itemCatQuery->whereHas('item', function ($itemRelationQuery) use ($request) {
        //                     $itemRelationQuery->where('category_id', $request->item_category_id)
        //                         // Compare Item Sub Category
        //                         ->when($request->item_sub_category_id, function ($itemSubCatQuery) use ($request) {
        //                             $itemSubCatQuery->where('subcategory_id', $request->item_sub_category_id);
        //                         });
        //                 });
        //             });
        //     });
        // });

        $expenseAdvises->with([
            'items' => function ($query) use ($request) {
                $query
                    ->when($request->item_id, function ($subQuery) use ($request) {
                        $subQuery->where('item_id', $request->item_id);
                    })
                    ->when($request->so_no, function ($subQuery) use ($request) {
                        $subQuery->where('so_id', $request->so_no);
                    })
                    ->whereHas('item', function ($q) use ($request) {
                        $q->when($request->m_category_id, function ($subQ) use ($request) {
                            $subQ->where('category_id', $request->m_category_id);
                        });

                        $q->when($request->m_subcategory_id, function ($subQ) use ($request) {
                            $subQ->where('category_id', $request->m_subcategory_id);
                        });
                    });
            },
            'items.item',
            'items.item.category',
            'items.item.subCategory',
            'vendor',
            'items.so',
            'po'
        ]);


        $expenseAdvises = $expenseAdvises->get();
        $processedExpenseAllocations = collect([]);

        foreach ($expenseAdvises as $expenseAdvise) {
            foreach ($expenseAdvise->items as $expenseAdviseItem) {
                $reportRow = new stdClass();

                // Header Details
                $header = $expenseAdviseItem->expenseHeader;
                $total_item_value = (($expenseAdviseItem?->rate ?? 0.00) * ($expenseAdviseItem?->accepted_qty ?? 0.00)) - ($expenseAdviseItem?->discount_amount ?? 0.00);
                $reportRow->id = $expenseAdviseItem->id;
                $reportRow->book_code = $header->book_code;
                $reportRow->document_number = $header->document_number;
                $reportRow->document_date = $header->document_date;
                $reportRow->po_no = !empty($header->po?->book_code) && !empty($header->po?->document_number)
                    ? $header->po?->book_code . ' - ' . $header->po?->document_number
                    : '';
                $reportRow->so_no = !empty($header->so?->book_code) && !empty($header->so?->document_number)
                    ? $header->so?->book_code . ' - ' . $header->so?->document_number
                    : '';
                $reportRow->vendor_name = $header->vendor?->company_name;
                $reportRow->vendor_rating = null;
                $reportRow->category_name = $expenseAdviseItem->item?->category?->name;
                $reportRow->sub_category_name = $expenseAdviseItem->item?->category?->name;
                $reportRow->item_type = $expenseAdviseItem->item?->type;
                $reportRow->sub_type = null;
                $reportRow->item_name = $expenseAdviseItem->item?->item_name;
                $reportRow->item_code = $expenseAdviseItem->item?->item_code;

                // Amount Details
                $reportRow->receipt_qty = number_format($expenseAdviseItem->accepted_qty, 2);
                $reportRow->store_name = $expenseAdviseItem?->erpStore?->store_name;
                $reportRow->rate = number_format($expenseAdviseItem->rate);
                $reportRow->basic_value = number_format($expenseAdviseItem->basic_value, 2);
                $reportRow->item_discount = number_format($expenseAdviseItem->discount_amount, 2);
                $reportRow->header_discount = number_format($expenseAdviseItem->header_discount_amount, 2);
                $reportRow->item_amount = number_format($total_item_value, 2);

                // Attributes UI
                // $attributesUi = '';
                // if (count($mrnItem->item_attributes) > 0) {
                //     foreach ($mrnItem->item_attributes as $mrnAttribute) {
                //         $attrName = $mrnAttribute->attribute_name;
                //         $attrValue = $mrnAttribute->attribute_value;
                //         $attributesUi .= "<span class='badge rounded-pill badge-light-primary' > $attrName : $attrValue </span>";
                //     }
                // } else {
                //     $attributesUi = 'N/A';
                // }
                // $reportRow->item_attributes = $attributesUi;

                // Document Status
                $reportRow->status = $header->document_status;
                $processedExpenseAllocations->push($reportRow);
            }
        }

        return DataTables::of($processedExpenseAllocations)
            ->addIndexColumn()
            ->editColumn('status', function ($row) use ($orderType) {
                $statusClass = ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$row->status ?? ConstantHelper::DRAFT];
                $displayStatus = ucfirst($row->status);
                return "
                    <div style='text-align:right;'>
                        <span class='badge rounded-pill $statusClass'>$displayStatus</span>
                    </div>
                ";
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    private static function processPurchaseOrderComponent($component, $item, $inputQty)
    {
        $po = PoItem::find($component['po_detail_id']);
        return $po ? self::updatePoQty($item, $po, $inputQty, 'purchase-order') : self::notFoundResponse('PO Item');
    }

    // Update Purchase Order Quantity
    private static function updatePoQty($item, $poDetail, $inputQty, $type)
    {
        $orderQty = floatval($poDetail->order_qty);
        $expQty = floatval($poDetail->expense_advise_qty ?? 0);
        $totalQty = $expQty + $inputQty;
        if ($totalQty > $orderQty) {
            return response()->json(['message' => 'Order Qty cannot exceed PO Qty.'], 422);
        }

        $poDetail->expense_advise_qty += $inputQty;
        $poDetail->save();

        return true;
    }

    private static function processJobOrderComponent($component, $item, $inputQty)
    {
        $jo = JoProduct::find($component['jo_detail_id']);
        return $jo ? self::updateJoQty($item, $jo, $inputQty, 'job-order') : self::notFoundResponse('JO Item');
    }

    // Update Job Order Quantity
    private static function updateJoQty($item, $joDetail, $inputQty, $type)
    {
        $orderQty = floatval($joDetail->order_qty);
        $expQty = floatval($joDetail->expense_advise_qty ?? 0);
        $totalQty = $expQty + $inputQty;
        if ($totalQty > $orderQty) {
            return response()->json(['message' => 'Order Qty cannot exceed JO Qty.'], 422);
        }

        $joDetail->expense_advise_qty += $inputQty;
        $joDetail->save();

        return true;
    }

    private static function notFoundResponse($label)
    {
        \DB::rollBack();
        return response()->json(['message' => "{$label} not found."], 422);
    }

    // # Validate Order Qty For Frontend
    private static function validateQuantityBackend($component, $refType)
    {
        $inputData = [
            'item_id' => $component['item_id'] ?? null,
            'purchase_order_id' => $component['purchase_order_id'] ?? null,
            'po_detail_id' => $component['po_detail_id'] ?? null,
            'job_order_id' => $component['job_order_id'] ?? null,
            'jo_detail_id' => $component['jo_detail_id'] ?? null,
            'expense_item_id' => $component['detail_id'] ?? null,
            'qty' => $component['accepted_qty'] ?? 0.00,
            'type' => $refType ?? 'po',
        ];

        $checkService = new ExpenseCheckAndUpdateService();
        $data = $checkService->validateOrderQuantity($inputData);
        return $data;
    }

    // Validate Order Qty For Frontend
    public function validateQuantity(Request $request)
    {
        $inputData = [
            'item_id' => $request->item_id,
            'po_header_id' => $request->purchase_order_id,
            'po_detail_id' => $request->po_detail_id,
            'jo_header_id' => $request->job_order_id,
            'jo_detail_id' => $request->jo_detail_id,
            'expense_item_id' => $request->detail_id,
            'qty' => $request->qty,
            'type' => $request->type,
        ];
        $checkService = new ExpenseCheckAndUpdateService();
        $data = $checkService->validateOrderQuantity($inputData);
        if ($data['status'] === 'success') {
            return response()->json(['message' => $data['message'], 'status' => 200, 'accepted_qty' => $data['accepted_qty']['accepted_qty'] ?? 0.00]);
        } else {
            return response()->json(['message' => $data['message'], 'status' => 422, 'accepted_qty' => $data['accepted_qty']['accepted_qty'] ?? 0.00]);
        }
    }

    private static function processDirectComponent($component, $item, $inputQty)
    {
        return true;
        // return self::validateComponentQuantities($component, $inputQty) === true ? true : self::validateComponentQuantities($component, $inputQty);
    }
}

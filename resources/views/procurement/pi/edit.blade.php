@extends('layouts.app')
@section('styles')
    <style>
        #soModal .table-responsive {
            overflow-y: auto;
            max-height: 300px;
            /* Set the height of the scrollable body */
            position: relative;
        }

        #soModal .po-order-detail {
            width: 100%;
            border-collapse: collapse;
        }

        #soModal .po-order-detail thead {
            position: sticky;
            top: 0;
            /* Stick the header to the top of the table container */
            background-color: white;
            /* Optional: Make sure header has a background */
            z-index: 1;
            /* Ensure the header stays above the body content */
        }

        #soModal .po-order-detail th {
            background-color: #f8f9fa;
            /* Optional: Background for the header */
            text-align: left;
            padding: 8px;
        }

        #soModal .po-order-detail td {
            padding: 8px;
        }
    </style>
@endsection
@section('content')
    @if ($buttons['approve'])
        <form id="piEditForm" data-module="pi" class="ajax-input-form" action="{{ route('pi.update.approve', $pi->id) }}" method="POST" data-redirect="/purchase-indent" enctype="multipart/form-data">
        @else
            <form id="piEditForm" data-module="pi" class="ajax-input-form" action="{{ route('pi.update', $pi->id) }}" method="POST" data-redirect="/purchase-indent" enctype="multipart/form-data">
    @endif
    @csrf
    <input type="hidden" name="procurement_type_param" id="procurement_type_param" value="all">
    <input type="hidden" name="procurement_type" id="procurement_type" value="rm">
    <input type="hidden" name="pi_item_count" id="pi_item_count" value="{{ $pi->pi_items->count() }}">
    <input type="hidden" name="so_item_ids" id="so_item_ids">
    <input type="hidden" name="item_ids" id="item_ids">
    <input type="hidden" name="requester_type" id="requester_type" value="{{ $pi->requester_type }}">
    <input type="hidden" name="show_attribute" value="0" id="show_attribute">
    <input type="hidden" name="so_tracking_required" value="{{ $pi->so_tracking_required }}" id="so_tracking_required">
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">Purchase Indent</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.html">Home</a>
                                        </li>
                                        <li class="breadcrumb-item active">Edit</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                        <div class="form-group breadcrumb-right">
                            <input type="hidden" name="document_status" value="{{ $pi->document_status }}" id="document_status">
                            <button type="button" onClick="javascript: history.go(-1)" class="btn btn-secondary btn-sm mb-50 mb-sm-0"><i data-feather="arrow-left-circle"></i> Back</button>
                            @if ($buttons['draft'])
                                <button type="submit" class="btn btn-outline-primary btn-sm mb-50 mb-sm-0 submit-button" name="action" value="draft"><i data-feather='save'></i> Save as Draft</button>
                            @endif
                            @if (!intval(request('amendment') ?? 0) && $pi->document_status != \App\Helpers\ConstantHelper::DRAFT && $pi->document_status != \App\Helpers\ConstantHelper::SUBMITTED)
                                <a href="{{ route('pi.generate-pdf', $pi->id) }}" target="_blank" class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg> Print
                                </a>
                            @endif

                            @if ($buttons['submit'])
                                <button type="submit" class="btn btn-primary btn-sm submit-button" name="action" value="submitted"><i data-feather="check-circle"></i> Submit</button>
                            @endif
                            @if ($buttons['approve'])
                                <button type="button" id="reject-button" class="btn btn-danger btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg> Reject</button>
                                <button type="button" class="btn btn-primary btn-sm" id="approved-button" name="action" value="approved"><i data-feather="check-circle"></i> Approve</button>
                            @endif
                            @if ($buttons['amend'] && intval(request('amendment') ?? 0))
                                <button type="button" class="btn btn-primary btn-sm" id="amendmentBtn"><i data-feather="check-circle"></i> Submit</button>
                            @else
                                @if ($buttons['amend'])
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#amendmentconfirm" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='edit'></i> Amendment</button>
                                @endif
                            @endif

                            @if ($buttons['revoke'])
                                <button id = "revokeButton" type="button" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='rotate-ccw'></i> Revoke</button>
                            @endif
                            @if ($pi->document_status == 'draft' || $pi->document_status == 'rejected')
                                @if ($buttons['cancel'])
                                    @php $cancelButtonSet = true; @endphp
                                    <button type="button" id="cancelButton" class="btn btn-danger btn-sm mb-50 mb-sm-0 cancell-btn" data-url="{{ route('pi.cancel', ['id' => $pi->id]) }}" data-redirect="{{ route('pi.index') }}" data-message="Are you sure you want to cancel document?">
                                        <i data-feather="trash-2" class="me-50"></i> Cancel
                                    </button>
                                @endif
                            @endif
                            @if ($buttons['cancel'] && !isset($cancelButtonSet) && (isset($cancelAmendButtonSet) && $cancelAmendButtonSet))
                                <button type="button" id="cancelButton" class="btn btn-danger btn-sm mb-50 mb-sm-0 cancell-btn" data-url="{{ route('pi.cancel', ['id' => $pi->id]) }}" data-redirect="{{ route('pi.index') }}" data-message="Are you sure you want to cancel document?">
                                    <i data-feather="trash-2" class="me-50"></i> Cancel
                                </button>
                            @endif
                            @if ($buttons['cancel'] && !isset($cancelButtonSet) && !isset($cancelAmendButtonSet))
                                @php $cancelAmendButtonSet = true; @endphp
                                <button type="button" id="cancelButton" class="btn btn-danger btn-sm mb-50 mb-sm-0 cancell-btn" data-url="{{ route('pi.cancel', ['id' => $pi->id]) }}" data-redirect="{{ route('pi.index') }}" data-message="Are you sure you want to cancel document?">
                                    <i data-feather="trash-2" class="me-50"></i> Cancel
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card" id="basic_section">
                                <div class="card-body customernewsection-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                                                <div>
                                                    <h4 class="card-title text-theme">Basic Information</h4>
                                                    <p class="card-text">Fill the details</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-sm-end">
                                            <span class="badge rounded-pill badge-light-secondary forminnerstatus">
                                                Status : <span class="{{ $docStatusClass }}">{{ $pi->display_status }}</span>
                                            </span>
                                        </div>
                                        <div class="col-md-8 basic-information">
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Series <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="hidden" name="book_id" id="book_id" value="{{ $pi->book_id }}" />
                                                    <select disabled class="form-select" id="book_id" name="book_id">
                                                        @foreach ($books as $book)
                                                            <option value="{{ $book->id }}" {{ $book->id == $pi->book_id ? 'selected' : '' }}>{{ ucfirst($book->book_code) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="book_code" id="{{ $pi->book->book_code }}" id="book_code">
                                                </div>
                                            </div>
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Indent No <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input readonly type="text" name="document_number" id="document_number" value="{{ $pi->document_number }}" class="form-control">
                                                </div>
                                            </div>
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Indent Date <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="date" class="form-control" value="{{ $pi->document_date }}" name="document_date">
                                                </div>
                                            </div>

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Location <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <select class="form-select" disabled id="store_id" name="store_id">
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->id }}" {{ $location->id == $pi?->store_id ? 'selected' : '' }}>{{ $location?->store_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row align-items-center mb-1 d-none" id = "department_id_header">
                                                <div class="col-md-3">
                                                    <label class="form-label">Requester</label>
                                                </div>
                                                <div class="col-md-5">
                                                    <select class="form-select" disabled id="sub_store_id" name="sub_store_id">
                                                        <option value="{{ $pi?->sub_store_id }}">{{ $pi?->sub_store?->name ?? $pi?->requester?->name }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            @if ($pi->requester_type === 'User')
                                                <div class="row align-items-center mb-1" id = "user_id_header">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Requester <span class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select disabled class="form-select" id="user_id" name="user_id">
                                                            <option value="">Select</option>
                                                            @foreach ($users as $user)
                                                                <option value="{{ $user->id }}" {{ $selecteduserId == $user->id ? 'selected' : '' }}>{{ ucfirst($user->name) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif
                                            {{-- <div class="row align-items-center mb-1" id="department_id_header">
                                            <div class="col-md-3">
                                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                            </div>
                                            <div class="col-md-5">
                                                <select class="form-select" id="department_id" name="department_id">
                                                    <option value="">Select</option>
                                                    @foreach ($departments as $department)
                                                    <option value="{{$department->id}}" {{$pi->department_id == $department->id ? 'selected' : ''}}>{{ucfirst($department->name)}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div> --}}
                                            <div class="row align-items-center mb-1 d-none" id="reference_from">
                                                <div class="col-md-3">
                                                    <label class="form-label">Reference from</label>
                                                </div>
                                                <div class="col-md-5 action-button">
                                                    <button type="button" @if (!$isEdit) disabled @endif class="btn btn-outline-primary btn-sm mb-0 soSelect"><i data-feather="plus-square"></i> Sale Order</button>
                                                    <button type="button" @if (!$isEdit) disabled @endif class="btn btn-outline-primary btn-sm mb-0 pwoSelect"><i data-feather="plus-square"></i> PWO</button>
                                                </div>
                                            </div>
                                            <div class="row align-items-center mb-1">
                                                @if ($saleOrders?->count())
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sales Order</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" readonly class="form-control" value="{{ $saleOrders->map(fn($saleOrder) => strtoupper($saleOrder->book_code) . ' - ' . $saleOrder->document_number)->join(', ') }}">
                                                    </div>
                                                @elseif($workOrders?->count())
                                                    <div class="col-md-3">
                                                        <label class="form-label">PWO</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" readonly class="form-control" value="{{ $workOrders->map(fn($workOrder) => strtoupper($workOrder->book_code) . ' - ' . $workOrder->document_number)->join(', ') }}">
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        {{-- Approval History Section --}}
                                        @include('partials.approval-history', ['document_status' => $pi->document_status, 'revision_number' => $revision_number])
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="item_section">
                                <div class="card-body customernewsection-form">
                                    <div class="border-bottom mb-2 pb-25">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="newheader ">
                                                    <h4 class="card-title text-theme">Indent Item Wise Detail</h4>
                                                    <p class="card-text">Fill the details</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-sm-end">
                                                <a href="javascript:;" id="deleteBtn" class="btn btn-sm btn-outline-danger me-50">
                                                    <i data-feather="x-circle"></i> Delete</a>
                                                <a href="javascript:;" id="addNewItemBtn" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="plus"></i> Add Item</a>
                                                <a href="javascript:;" onclick="copyItemRow();" id="copy_item_section" style="{{ isset($pi->pi_items) && count($pi->pi_items) ? '' : 'display:none;' }}" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="copy"></i> Copy Item</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive pomrnheadtffotsticky">
                                                <table id="itemTable" class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad" data-json-key="components_json" data-row-selector="tr[id^='row_']">
                                                    <thead>
                                                        <tr>
                                                            <th class="customernewsection-form">
                                                                <div class="form-check form-check-primary custom-checkbox">
                                                                    <input type="checkbox" class="form-check-input" id="Email">
                                                                    <label class="form-check-label" for="Email"></label>
                                                                </div>
                                                            </th>
                                                            <th width="200px">Item Code</th>
                                                            <th width="300px">Item Name</th>
                                                            <th max-width="180px">Attributes</th>
                                                            <th>UOM</th>
                                                            <th class="text-end">Req Qty</th>
                                                            <th class="text-end">Avl Stock</th>
                                                            <th class="text-end">Pending PO</th>
                                                            <th class="text-end">Adj Qty</th>
                                                            <th class="text-end">Order Qty</th>
                                                            <th width="240px">Vendor Name</th>
                                                            <th width="100px" id="so_no">SO No.</th>
                                                            <th width="350px">Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="mrntableselectexcel">
                                                        @include('procurement.pi.partials.item-row-edit')
                                                    </tbody>
                                                    <tfoot>
                                                        <tr valign="top">
                                                            <td colspan="13" rowspan="10">
                                                                <table class="table border">
                                                                    <tbody id="itemDetailDisplay">
                                                                        <tr>
                                                                            <td class="p-0">
                                                                                <h6 class="text-dark mb-0 bg-light-primary py-1 px-50"><strong>Item Details</strong></h6>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                        </tr>
                                                                        <tr>
                                                                        </tr>
                                                                        <tr>
                                                                        </tr>
                                                                        <tr>
                                                                        </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-1">
                                                                <label class="form-label">Upload Document</label>
                                                                <input type="file" name="attachment[]" class="form-control" onchange = "addFiles(this,'main_pi_preview')" max_file_count = "5" multiple>
                                                                <span class = "text-primary small">{{ __('message.attachment_caption') }}</span>
                                                            </div>
                                                        </div>
                                                        @include('partials.document-preview', ['documents' => $pi->getDocuments(), 'document_status' => $pi->document_status, 'elementKey' => 'main_pi_preview'])
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="mb-1">
                                                        <label class="form-label">Final Remarks</label>
                                                        <textarea maxlength="250" rows="4" name="remarks" class="form-control" placeholder="Enter Remarks here...">{{ old('remarks', $pi->remarks) }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    @include('procurement.pi.partials.amendment-modal', ['id' => $pi->id])
    @include('procurement.pi.partials.approve-modal', ['id' => $pi->id])
    </form>

    {{-- Attribute popup --}}
    <div class="modal fade" id="attribute" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-2 mx-50 pb-2">
                    <h1 class="text-center mb-1" id="shareProjectTitle">Select Attribute</h1>
                    <p class="text-center">Enter the details below.</p>
                    <div class="table-responsive-md customernewsection-form">
                        <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
                            <thead>
                                <tr>
                                    <th>Attribute Name</th>
                                    <th>Attribute Value</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                    <button type="button" data-bs-dismiss="modal" class="btn btn-primary">Select</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Item Remark Modal --}}
    <div class="modal fade" id="itemRemarkModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-2 mx-50 pb-2">
                    <h1 class="text-center mb-1" id="shareProjectTitle">Remarks</h1>
                    {{-- <p class="text-center">Enter the details below.</p> --}}
                    <div class="row mt-2">
                        <div class="col-md-12 mb-1">
                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                            <input type="hidden" name="row_count" id="row_count">
                            <textarea maxlength="250" class="form-control" placeholder="Enter Remarks"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                    <button type="button" class="btn btn-primary itemRemarkSubmit">Submit</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete component modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="deleteComponentModal" tabindex="-1" aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body alertmsg text-center warning">
                    <i data-feather='alert-circle'></i>
                    <h2>Are you sure?</h2>
                    <p>Are you sure you want to delete selected <strong>Components</strong>?</p>
                    <button type="button" class="btn btn-secondary me-25" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="deleteConfirm" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Amendment Modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="amendmentconfirm" tabindex="-1" aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body alertmsg text-center warning">
                    <i data-feather='alert-circle'></i>
                    <h2>Are you sure?</h2>
                    <p>Are you sure you want to <strong>Amendment</strong> this <strong>PI</strong>? After Amendment this action cannot be undone.</p>
                    <button type="button" class="btn btn-secondary me-25" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="amendmentSubmit" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    @include('procurement.pi.partials.so-modal')
    @include('procurement.pi.partials.so-modal-submit')
@endsection
@section('scripts')
    <script>
        const getSoUrl = '{{ route('pi.get.so') }}';
        const piIndexUrl = '{{ route('pi.index') }}';
        const getPwoUrl = '{{ route('pi.get.pwo') }}';
        const getPiItemRowUrl = '{{ route('pi.item.row') }}';
        const getPiItemAttUrl = '{{ route('pi.item.attr') }}';
        const processPwoUrl = '{{ route('pi.process.pwo-item') }}';
        const analyzeSoItemUrl = '{{ route('pi.analyze.so-item') }}';
        const processSoItemUrl = '{{ route('pi.process.so-item') }}';
        const getItemDetailsUrl = '{{ route('pi.get.itemdetail') }}';
        const processPwoItemUrl = '{{ route('pi.process.pwo-item') }}';
        const processSoActionUrl = '{{ route('pi.process.so-item.submit') }}';
        const processPwoActionUrl = '{{ route('pi.process.pwo-item.submit') }}';
        const soServiceAlias = '{{ \App\Helpers\ConstantHelper::SO_SERVICE_ALIAS }}';
        const pwoServiceAlias = '{{ \App\Helpers\ConstantHelper::PWO_SERVICE_ALIAS }}';
        const processAnalyzedBomItem = '{{ route('pi.process.analyzed.bom-item') }}';
    </script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-attr-ui.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/pi.js') }}"></script>
    <script type="text/javascript" src="{{ asset('app-assets/js/file-uploader.js') }}"></script>
    <script>
        setTimeout(() => {
            localStorage.removeItem('deletedPiItemIds');
        }, 0);
        @if ($buttons['amend'] && intval(request('amendment') ?? 0))
        @else
            @if ($pi->document_status != 'draft' && $pi->document_status != 'rejected')
                $(':input').prop('readonly', true);
                $('[name="attachment[]"]').not('#approveModal [name="attachment[]"]').prop('disabled', true);
                $('input[autocomplete], .ui-autocomplete-input').prop('disabled', true);
                $('select').not('.amendmentselect select').prop('disabled', true);
                @if ($buttons['approve'])
                    $("#itemTable").find('[name*="adj_qty"]').prop('readonly', false);
                    $("#itemTable").find('[name*="adj_qty"]').prop('disabled', false);
                    $('input[autocomplete], .ui-autocomplete-input').prop('disabled', false);
                    $('input[autocomplete], .ui-autocomplete-input').prop('readonly', false);
                @endif
                $("#deleteBtn").remove();
                $("#addNewItemBtn").remove();
                $("#copy_item_section").remove();
                $("#itemTable .form-check-input").prop("disabled", true);
                $(document).on('show.bs.modal', function(e) {
                    if (e.target.id != 'approveModal') {
                        $(e.target).find('.modal-footer').remove();
                        $('select').not('.amendmentselect select').prop('disabled', true);
                    }
                    if (e.target.id == 'approveModal') {
                        $(e.target).find(':input').prop('readonly', false);
                        $(e.target).find('select').prop('readonly', false);
                    }
                    $('.add-contactpeontxt').remove();
                    let text = $(e.target).find('thead tr:first th:last').text();
                    if (text.includes("Action")) {
                        $(e.target).find('thead tr').each(function() {
                            $(this).find('th:last').remove();
                        });
                        $(e.target).find('tbody tr').each(function() {
                            $(this).find('td:last').remove();
                        });
                    }
                });
            @endif
        @endif

        $(document).on('change', '#book_id', (e) => {
            let bookId = e.target.value;
            if (bookId) {
                getDocNumberByBookId(bookId);
            } else {
                $("#document_number").val('');
                $("#book_id").val('');
                $("#document_number").attr('readonly', false);
            }
        });

        function getDocNumberByBookId(bookId) {
            let document_date = $("[name='document_date']").val();
            let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId + '&document_date=' + document_date;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        $("#book_code").val(data.data.book_code);
                        //   if(!data.data.doc.document_number) {
                        //      $("#document_number").val('');
                        //  }
                        //  $("#document_number").val(data.data.doc.document_number);
                        if (data.data.doc.type == 'Manually') {
                            $("#document_number").attr('readonly', false);
                        } else {
                            $("#document_number").attr('readonly', true);
                        }
                        const parameters = data.data.parameters;
                        setServiceParameters(parameters);
                    }
                    if (data.status == 404 || data.status == 500) {
                        $("#book_code").val('');
                        $("#document_number").val('');
                        const docDateInput = $("[name='document_date']");
                        docDateInput.removeAttr('min');
                        docDateInput.removeAttr('max');
                        // docDateInput.val(new Date().toISOString().split('T')[0]);
                        toggleSubmitButton('.ajax-input-form', true);
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                        });
                    } else {
                        toggleSubmitButton('.ajax-input-form', false);
                    }
                });
            });
        }

        let selectedBookId = $("#book_id").val() || '';
        getDocNumberByBookId(selectedBookId);

        /* Delete Row */
        $(document).on('click', '#deleteBtn', (e) => {
            let itemIds = [];
            let editItemIds = [];
            let poItemIds = [];

            $(".form-check-input:checked").each(function(index, item) {
                let tr = $(item).closest('tr');
                let trIndex = tr.index();
                let po_item_id = Number($(tr).find('[name*="[po_item_id]"]').val()) || 0;
                if (po_item_id > 0) {
                    poItemIds.push({
                        index: trIndex + 1,
                        pi_item_id: po_item_id
                    });
                }
            });

            if (poItemIds.length) {
                e.preventDefault();
                let rowNumbers = poItemIds.map(item => item.index).join(", ");
                Swal.fire({
                    title: 'Error!',
                    text: `You cannot delete pi(using in po) item(s) at row(s): ${rowNumbers}`,
                    icon: 'error',
                });
                return false;
            }

            $('#itemTable > tbody .form-check-input').each(function() {
                if ($(this).is(":checked")) {
                    if ($(this).attr('data-id')) {
                        editItemIds.push($(this).attr('data-id'));
                    } else {
                        itemIds.push($(this).val());
                    }
                }
            });

            if (itemIds.length) {
                itemIds.forEach(function(item) {
                    $(`#row_${item}`).remove();
                });
            }

            if (editItemIds.length == 0 && itemIds.length == 0) {
                alert("Please first add & select row item.");
                document.getElementById('copy_item_section').style.display = "none";
            }

            if (editItemIds.length) {
                checkPoUtilizedItem(editItemIds).then((hasError) => {
                    if (!hasError) {
                        $("#deleteComponentModal").find("#deleteConfirm").attr('data-ids', JSON.stringify(editItemIds));
                        $("#deleteComponentModal").modal('show');
                    }
                });
            }
        });

        /* Check if PI items are utilized in PO */
        function checkPoUtilizedItem(editItemIds) {
            let hasError = false;
            let errorMessages = [];

            let checkUrl = '{{ route('pi.check-po-consumed-qty') }}';
            let requests = editItemIds.map((piItemId) => {
                return $.ajax({
                    url: checkUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        pi_item_id: piItemId
                    },
                    success: function(data) {
                        if (data.status === 422 && data.data) {
                            hasError = true;
                            let $row = $(`#itemTable`).find(`.form-check-input[data-id='${piItemId}']`).closest('tr');
                            let $checkbox = $row.find(".form-check-input");

                            $checkbox.prop("checked", false);

                            (data.data.po_list || []).forEach((po) => {
                                errorMessages.push(
                                    `Row ${$row.index() + 1}: Item already utilized in PO #${po.document_number} (Qty: ${po.po_qty})`
                                );
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error checking item utilization:', xhr.responseText);
                    }
                });
            });

            return $.when.apply($, requests).then(() => {
                if (hasError && errorMessages.length) {
                    Swal.fire({
                        title: 'Cannot Delete Items',
                        html: errorMessages.join("<br>"),
                        icon: 'error'
                    });
                }
                return hasError;
            });
        }

        /*Delete server side rows*/
        $(document).on('click', '#deleteConfirm', (e) => {
            let ids = e.target.getAttribute('data-ids');
            ids = JSON.parse(ids);
            localStorage.setItem('deletedPiItemIds', JSON.stringify(ids));
            $("#deleteComponentModal").modal('hide');

            if (ids.length) {
                ids.forEach((id, index) => {
                    $(`.form-check-input[data-id='${id}']`).closest('tr').remove();
                });
            }
            if (!$("#itemTable [id*=row_]").length) {
                $("th .form-check-input").prop('checked', false);
                $("#reference_from").removeClass('d-none');
                $("#orderTypeSelect").prop('disabled', false);
            }
        });

        // # Revision Number On Chage
        $(document).on('change', '#revisionNumber', (e) => {
            let actionUrl = location.pathname + '?revisionNumber=' + e.target.value;
            let revision_number = Number("{{ $revision_number }}");
            let revisionNumber = Number(e.target.value);
            if (revision_number == revisionNumber) {
                location.href = actionUrl;
            } else {
                window.open(actionUrl, '_blank');
            }
        });

        /*Open amendment popup*/
        $(document).on('click', '#amendmentBtn', (e) => {
            $("#amendmentModal").modal('show');
        });

        $(document).on('click', '#amendmentSubmit', (e) => {
            let url = new URL(window.location.href);
            url.search = '';
            url.searchParams.set('amendment', 1);
            let amendmentUrl = url.toString();
            window.location.replace(amendmentUrl);
        });


        /*Amendment btn submit*/
        $(document).on('click', '#amendmentBtnSubmit', (e) => {
            let remark = $("#amendmentModal").find('[name="amend_remarks"]').val();
            if (!remark) {
                e.preventDefault();
                $("#amendRemarkError").removeClass("d-none");
                return false;
            } else {
                $("#amendmentModal").modal('hide');
                $("#amendRemarkError").addClass("d-none");
                e.preventDefault();
                let $actionInput = $("#piEditForm").find('input[name="action_type"]');
                if ($actionInput) {
                    $actionInput.val("amendment");
                }
                $("#piEditForm").submit();
            }
        });


        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("search_filter");
            const tableBody = document.getElementById("soSubmitDataTable");

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase().trim();

                Array.from(tableBody.getElementsByTagName("tr")).forEach((row) => {
                    const itemCodeCell = row.cells[1]?.innerText.toLowerCase() || "";
                    const itemNameCell = row.cells[2]?.innerText.toLowerCase() || "";

                    // Check if row matches the search term in either column
                    const matchesItemCode = itemCodeCell.includes(searchTerm);
                    const matchesItemName = itemNameCell.includes(searchTerm);
                    const checkbox = row.querySelector("input[type='checkbox']");

                    // Show row if it matches the search term in any column
                    if (matchesItemCode || matchesItemName) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    }
                });
            }
            searchInput.addEventListener("input", filterTable);
        });

        // Revoke Document
        $(document).on('click', '#revokeButton', (e) => {
            let actionUrl = '{{ route('pi.revoke.document') }}' + '?id=' + '{{ $pi->id }}';
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 'error') {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                        });
                    } else {
                        Swal.fire({
                            title: 'Success!',
                            text: data.message,
                            icon: 'success',
                        });
                    }
                    location.reload();
                });
            });
        });

        /*Final process submit*/
        $(document).on('click', '.clearPiFilter', (e) => {
            $("#item_name_search").val('');
            $("#item_name_input_qt").val('');
            $("#item_id_qt_val").val('');
            $("#department_po").val('');
            $("#department_id_po").val('');
            $("#customer_code_input_qt").val('');
            $("#customer_id_qt_val").val('');
            $("#book_code_input_qt").val('');
            $("#book_id_qt_val").val('');
            $("#document_no_input_qt").val('');
            $("#document_id_qt_val").val('');
            getSoItems();
        });

        function updateDropdown(storeId) {
            if ($("#requester_type").val().includes('Department')) {
                let selectedId = '{{ $pi->sub_store_id }}' || '';
                let actionUrl = '{{ route('subStore.get.from.stores') }}' + '?store_id=' + storeId;
                fetch(actionUrl).then(response => {
                    return response.json().then(data => {
                        let option = '<option value="">Select</option>';
                        if (data.data.length) {
                            data.data.forEach(function(item) {
                                option += `<option value="${item.id}" ${selectedId == item.id ? 'selected' : ''}>${item.name}</option>`;
                            })
                            $("#department_id_header").removeClass('d-none');
                        } else {
                            $("#department_id_header").addClass('d-none');
                        }
                        $("#sub_store_id").empty().append(option);
                    });
                });
            }
        }

        $(document).on('change', "[name='store_id']", function() {
            updateDropdown(this.value);
        });

        $(document).on('change', "[name='store_id']", (e) => {
            let storeId = e.target.value || '';
            updateDropdown(storeId);
        });

        setTimeout(() => {
            let storeId = $("#store_id").val() || '';
            if (storeId) {
                updateDropdown(storeId);
            }
        }, 100);

        setTimeout(() => {
            $("#itemTable .mrntableselectexcel tr").each(function(index, item) {
                let currentIndex = index + 1;
                setAttributesUIHelper(currentIndex, "#itemTable");
            });

        }, 100);
        @if ($pi->pi_items->count())
            $("#orderTypeSelect").prop('disabled', true);
        @endif
    </script>
@endsection

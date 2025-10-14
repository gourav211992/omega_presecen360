@extends('layouts.app')
@section('styles')
    <style>
        #poModal .table-responsive {
            overflow-y: auto;
            max-height: 300px;
            /* Set the height of the scrollable body */
            position: relative;
        }

        #poModal .po-order-detail {
            width: 100%;
            border-collapse: collapse;
        }

        #poModal .po-order-detail thead {
            position: sticky;
            top: 0;
            /* Stick the header to the top of the table container */
            background-color: white;
            /* Optional: Make sure header has a background */
            z-index: 1;
            /* Ensure the header stays above the body content */
        }

        #poModal .po-order-detail th {
            background-color: #f8f9fa;
            /* Optional: Background for the header */
            text-align: left;
            padding: 8px;
        }

        #poModal .po-order-detail td {
            padding: 8px;
        }
    </style>
@endsection
@section('content')
    <form id="expAlcEditForm" data-module="exp-alc" class="ajax-input-form" method="POST"
        action="{{ route('exp-allocation.update', $expense->id) }}" data-redirect="/expense-allocation"
        enctype="multipart/form-data">
        @csrf
        <div class="app-content content ">
            <div class="content-overlay"></div>
            <div class="header-navbar-shadow"></div>
            <div class="content-wrapper container-xxl p-0">
                <div class="content-header pocreate-sticky">
                    <div class="row">
                        <div class="content-header-left col-md-6 mb-2">
                            <div class="row breadcrumbs-top">
                                <div class="col-12">
                                    <h2 class="content-header-title float-start mb-0">Expense Allocation</h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item">
                                                <a href="/">Home</a>
                                            </li>
                                            <li class="breadcrumb-item active">Edit</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <input type="hidden" name="document_status" value="{{ $expense->document_status }}"
                                    id="document_status">
                                <button type="button" onClick="javascript: history.go(-1)"
                                    class="btn btn-secondary btn-sm mb-50 mb-sm-0">
                                    <i data-feather="arrow-left-circle"></i> Back
                                </button>
                                @if (
                                    !intval(request('amendment') ?? 0) &&
                                        $expense->document_status != \App\Helpers\ConstantHelper::DRAFT &&
                                        $expense->document_status != \App\Helpers\ConstantHelper::SUBMITTED &&
                                        $expense->document_status != \App\Helpers\ConstantHelper::PARTIALLY_APPROVED)
                                    <a href="{{ route('exp-allocation.generate-pdf', $expense->id) }}" target="_blank"
                                        class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer">
                                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                            <path
                                                d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2">
                                            </path>
                                            <rect x="6" y="14" width="12" height="8"></rect>
                                        </svg>
                                        Print
                                    </a>
                                    @if ($buttons['post'])
                                        <button id="postButton" onclick="onPostVoucherOpen();" type="button"
                                            class="btn btn-warning btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg
                                                xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                class="feather feather-check-circle">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg> Post</button>
                                    @endif
                                @endif
                                @if ($buttons['draft'])
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm mb-50 mb-sm-0 submit-button" name="action"
                                        value="draft">
                                        <i data-feather='save'></i> Save as Draft
                                    </button>
                                @endif
                                @if ($buttons['submit'])
                                    <button type="button" class="btn btn-primary btn-sm submit-button" name="action"
                                        value="submitted">
                                        <i data-feather="check-circle"></i> Submit
                                    </button>
                                @endif
                                @if ($buttons['voucher'])
                                    <button type="button" onclick="onPostVoucherOpen('posted');"
                                        class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-file-text">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg> Voucher</button>
                                @endif
                                @if ($buttons['approve'])
                                    <button type="button" class="btn btn-primary btn-sm" id="approved-button"
                                        name="action" value="approved"><i data-feather="check-circle"></i>
                                        Approve</button>
                                    <button type="button" id="reject-button"
                                        class="btn btn-danger btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-x-circle">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg> Reject</button>
                                @endif
                                @if ($buttons['amend'] && intval(request('amendment') ?? 0))
                                    <button type="button" class="btn btn-primary btn-sm" id="amendmentBtn"><i
                                            data-feather="check-circle"></i> Submit</button>
                                @else
                                    @if ($buttons['amend'])
                                        <button type="button" data-bs-toggle="modal" data-bs-target="#amendmentconfirm"
                                            class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='edit'></i>
                                            Amendment</button>
                                    @endif
                                @endif
                                @if ($buttons['revoke'])
                                    <button id = "revokeButton" type="button"
                                        class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='rotate-ccw'></i>
                                        Revoke</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-body">
                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="row">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div
                                                        class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                                                        <div>
                                                            <h4 class="card-title text-theme">Basic Information</h4>
                                                            <p class="card-text">Fill the details</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-sm-end">
                                                    <span
                                                        class="badge rounded-pill badge-light-{{ $expense->display_status === 'Posted' ? 'info' : 'secondary' }} forminnerstatus">
                                                        <span class = "text-dark">Status</span> : <span
                                                            class="{{ $docStatusClass }}">{{ $expense->display_status }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="hidden" name="book_id" class="form-control book_id"
                                                            id="book_id" value="{{ $expense->book_id }}" readonly>
                                                        <input readonly type="text" class="form-control"
                                                            value="{{ $expense->book->book_code }}" id="book_code">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Document No <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control document_number"
                                                            readonly value="{{ @$expense->document_number }}"
                                                            id="document_number">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Document Date <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="date" name="document_date"
                                                            class="form-control document_date"
                                                            value="{{ @$expense->document_date }}">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select header_store_id"
                                                            name="header_store_id">
                                                            <option value=""
                                                                {{ is_null($expense->store_id) ? 'selected' : '' }}>
                                                            </option>
                                                            @foreach ($locations as $erpStore)
                                                                <option value="{{ $erpStore->id }}"
                                                                    {{ $expense->store_id == $erpStore->id ? 'selected' : '' }}>
                                                                    {{ ucfirst($erpStore->store_name) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                @if ($expense->document_status == 'draft' || $expense->document_status == 'rejected')
                                                    <div class="row align-items-center mb-1 reference_from"
                                                        id="reference_from">
                                                        <div class="col-md-3">
                                                            <label class="form-label">
                                                                Reference From
                                                            </label>
                                                        </div>
                                                        <div class="col-md-5 action-button">
                                                            <button type="button"
                                                                class="btn btn-outline-primary btn-sm mb-0 poSelect">
                                                                <i data-feather="plus-square"></i>
                                                                Outstanding PO
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            {{-- Approval History Section --}}
                                            @include('partials.approval-history', [
                                                'document_status' => $expense->document_status,
                                                'revision_number' => $revision_number,
                                            ])
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader">
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="step-custhomapp bg-light">
                                            <ul class="nav nav-tabs my-25 custapploannav" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" href="#poItems">
                                                        Expenses
                                                    </a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#grnItems">
                                                        GRN Items
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="tab-content pb-1">
                                            <div class="tab-pane active poItems" id="poItems">
                                                <div class="text-end mb-50">
                                                    <a href="javascript:;" id="distributeBtn"
                                                        class="btn btn-sm btn-outline-success me-50 distributeBtn">
                                                        <i data-feather="package"></i>
                                                        Allocate
                                                    </a>
                                                    {{-- <a href="javascript:;" id="delete-po-items"
                                                        class="btn btn-sm btn-outline-danger me-50 delete-po-items">
                                                        <i data-feather="x-circle"></i>
                                                        Delete
                                                    </a> --}}
                                                    <a href="javascript:;" id="addNewItemBtn"
                                                        class="btn btn-sm btn-outline-primary addNewItemBtn">
                                                        <i data-feather="plus"></i>
                                                        Add Item
                                                    </a>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="table-responsive pomrnheadtffotsticky">
                                                            <table id="poItemsTable"
                                                                class="ItemsTable poItemsTable table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad"
                                                                data-row-selector="tr[id^='row_']">
                                                                <thead id="poItemsThead" class="poItemsThead">
                                                                    <tr>
                                                                        <th class="customernewsection-form">
                                                                            <div
                                                                                class="form-check form-check-primary custom-checkbox">
                                                                                <input type="checkbox"
                                                                                    class="form-check-input"
                                                                                    id="Email">
                                                                                <label class="form-check-label"
                                                                                    for="Email"></label>
                                                                            </div>
                                                                        </th>
                                                                        <th width="150">Item Code</th>
                                                                        <th width="225">Item Name</th>
                                                                        <th>UOM</th>
                                                                        <th>Currency</th>
                                                                        <th>Org Currency</th>
                                                                        <th class="text-end">Qty</th>
                                                                        <th class="text-end">Rate</th>
                                                                        <th class="text-end">Po Value</th>
                                                                        <th class="text-end">Value</th>
                                                                        <th>Allocation Type</th>
                                                                        <th width="225">Vendor</th>
                                                                        <th width="150">Po No.</th>
                                                                        <th width="150">Po Date</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="mrntableselectexcel poItemsTbody"
                                                                    id="poItemsTbody">
                                                                    @include('procurement.expense-allocation.partials.po-item-row-edit')
                                                                </tbody>
                                                                <tfoot>
                                                                    <tr class="totalsubheadpodetail">
                                                                        <td colspan="6"></td>
                                                                        <td class="text-end total-po-qty"
                                                                            id="total-po-qty">
                                                                            {{ @$expense->poDetails->sum('receipt_qty') }}
                                                                        </td>
                                                                        <td colspan="1"></td>
                                                                        <td class="text-end total-po-old-value"
                                                                            id="total-po-old-value">
                                                                            {{ @$expense->poDetails->sum('po_value') }}
                                                                        </td>
                                                                        <td class="text-end total-po-value"
                                                                            id="total-po-value">
                                                                            {{ @$expense->poDetails->sum('value') }}
                                                                        </td>
                                                                        <td colspan="4"></td>
                                                                    </tr>
                                                                    <tr valign="top">
                                                                        <td colspan="14" rowspan="12">
                                                                            <table
                                                                                class="table border po-item-detail-display"
                                                                                id="po-item-detail-display">
                                                                                <tr>
                                                                                    <td class="p-0">
                                                                                        <h6
                                                                                            class="text-dark mb-0 bg-light-primary py-1 px-50">
                                                                                            <strong>Item Details</strong>
                                                                                        </h6>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- GRN Items --}}
                                            <div class="tab-pane grnItems" id="grnItems">
                                                <div class="text-end mb-50">
                                                    {{-- <a href="javascript:;" id="delete-grn-items"
                                                        class="btn btn-sm btn-outline-danger me-50 delete-grn-items">
                                                        <i data-feather="x-circle"></i> Delete</a> --}}
                                                    <a href="javascript:;"
                                                        class="btn btn-outline-primary btn-sm mb-0 grnSelect">
                                                        <i data-feather="plus-square"></i>
                                                        Add GRN
                                                    </a>
                                                </div>
                                                <div class="table-responsive pomrnheadtffotsticky">
                                                    <table id="grnItemsTable"
                                                        class="ItemsTable grnItemsTable table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                                        <thead>
                                                            <tr>
                                                                <th class="customernewsection-form">
                                                                    <div
                                                                        class="form-check form-check-primary custom-checkbox">
                                                                        <input type="checkbox" class="form-check-input"
                                                                            id="Email">
                                                                        <label class="form-check-label"
                                                                            for="Email"></label>
                                                                    </div>
                                                                </th>
                                                                <th width="225">Vendor Name</th>
                                                                <th width="150">GRN No.</th>
                                                                <th width="150">GRN Date</th>
                                                                <th width="150">Item Code</th>
                                                                <th width="225">Item Name</th>
                                                                <th>Attributes</th>
                                                                <th>UOM</th>
                                                                <th>Currency</th>
                                                                <th>Org Currency</th>
                                                                <th class="text-end">Qty</th>
                                                                <th class="text-end">Grn Value</th>
                                                                <th class="text-end">Value</th>
                                                                <th class="text-end">Weight</th>
                                                                <th class="text-end">Volume(CFT)</th>
                                                                <th width="200">Allocated Expense</th>
                                                                <th class="text-end">Landed Cost</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="mrntableselectexcel itemTable grnItemsTbody"
                                                            id="grnItemsTbody">
                                                            @include('procurement.expense-allocation.partials.grn-item-row-edit')
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="totalsubheadgrndetail">
                                                                <td colspan="10"></td>
                                                                <td class="text-end total-grn-qty" id="total-grn-qty">
                                                                    {{ @$expense->grnDetails->sum('receipt_qty') }}
                                                                </td>
                                                                <td class="text-end total-old-grn-value"
                                                                    id="total-old-grn-value">
                                                                    {{ @$expense->grnDetails->sum('grn_value') }}
                                                                </td>
                                                                <td class="text-end total-grn-value" id="total-grn-value">
                                                                    {{ @$expense->grnDetails->sum('value') }}
                                                                </td>
                                                                <td class="text-end total-grn-weight"
                                                                    id="total-grn-weight">
                                                                    {{ @$expense->grnDetails->sum('weight') }}
                                                                </td>
                                                                <td class="text-end total-grn-volume"
                                                                    id="total-grn-volume">
                                                                    {{ @$expense->grnDetails->sum('volume') }}
                                                                </td>
                                                                <td class="total-allocated-cost"
                                                                    id="total-allocated-cost">
                                                                    {{ @$expense->grnDetails->sum('allocated_cost') }}
                                                                </td>
                                                                <td class="text-end total-landed-cost"
                                                                    id="total-landed-cost">
                                                                    {{ @$expense->grnDetails->sum('landed_cost') }}
                                                                </td>
                                                            </tr>
                                                            <tr valign="top">
                                                                <td colspan="17" rowspan="12">
                                                                    <table class="table border grn-item-detail-display"
                                                                        id="grn-item-detail-display">
                                                                        <tr>
                                                                            <td class="p-0">
                                                                                <h6
                                                                                    class="text-dark mb-0 bg-light-primary py-1 px-50">
                                                                                    <strong>Item Details</strong>
                                                                                </h6>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <div class="col-md-4">
                                                    <div class="mb-1">
                                                        <label class="form-label">Upload Document</label>
                                                        <input type="file" name="attachment[]" class="form-control"
                                                            onchange = "addFiles(this,'main_expense_preview')" multiple>
                                                        <span
                                                            class = "text-primary small">{{ __('message.attachment_caption') }}</span>
                                                    </div>
                                                </div>
                                                @include('partials.document-preview', [
                                                    'documents' => $expense->getDocuments(),
                                                    'document_status' => $expense->document_status,
                                                    'elementKey' => 'main_expense_preview',
                                                ])
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-1">
                                                    <label class="form-label">Final Remarks</label>
                                                    <textarea type="text" rows="4" name="remark" class="form-control" placeholder="Enter Remarks here...">{!! $expense->remark !!}</textarea>
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
        {{-- Add Outstanding PO modal --}}
        @include('procurement.expense-allocation.partials.outstanding-po-modal')
        {{-- Add Outstanding GRN modal --}}
        @include('procurement.expense-allocation.partials.outstanding-grn-modal')
        {{-- Add Amendment modal --}}
        @include('procurement.expense-allocation.partials.amendement-modal', ['id' => $expense->id])
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
                    <button type="button" data-bs-dismiss="modal"
                        class="btn btn-primary submitAttributeBtn">Select</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Approve/Reject Modal -->
    @include('procurement.expense-allocation.partials.approve-modal', ['id' => $expense->id])
    {{-- Amendment Modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="amendmentconfirm" tabindex="-1"
        aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body alertmsg text-center warning">
                    <i data-feather='alert-circle'></i>
                    <h2>Are you sure?</h2>
                    <p>Are you sure you want to <strong>Amendment</strong> this <strong>Allocation </strong>? After
                        Amendment
                        this action cannot be undone.</p>
                    <button type="button" class="btn btn-secondary me-25" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="amendmentSubmit" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <!-- GL Posting Modal -->
    <div class="modal fade text-start show" id="postvoucher" tabindex="-1" aria-labelledby="postVoucherModal"
        aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 1000px">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="postVoucherModal"> Voucher
                            Details</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Series <span class="text-danger">*</span></label>
                                <input id = "voucher_book_code" class="form-control" disabled="">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Voucher No <span class="text-danger">*</span></label>
                                <input id = "voucher_doc_no" class="form-control" disabled="" value="">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Voucher Date <span class="text-danger">*</span></label>
                                <input id = "voucher_date" class="form-control" disabled="" value="">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <input id = "voucher_currency" class="form-control" disabled="" value="">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table
                                    class="mt-1 table table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Group</th>
                                            <th>Leadger Code</th>
                                            <th>Leadger Name</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="posting-table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <button style="margin: 1%;" onclick = "postVoucher(this);" id="posting_button" type = "button"
                        class="btn btn-primary btn-sm waves-effect waves-float waves-light">Submit</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript">
        var qtyChangeUrl = '{{ route('exp-allocation.get.validate-quantity') }}';
        let bookUrl = '{{ route('book.get.doc_no_and_parameters') }}';
        let indexUrl = '{{ route('exp-allocation.index') }}';
        let processPoUrl = '{{ route('exp-allocation.process.po-item') }}';
        let processGrnUrl = '{{ route('exp-allocation.process.grn-item') }}';
        let getPoUrl = '{{ route('exp-allocation.get.po') }}';
        let getGrnUrl = '{{ route('exp-allocation.get.grn') }}';
        let addItemRowUrl = '{{ route('exp-allocation.item.row') }}';
    </script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-datatable.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-attr-ui.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/expense-allocation.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-expense-alc.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/dist-expense-alc.js') }}"></script>
    <script type="text/javascript" src="{{ asset('app-assets/js/file-uploader.js') }}"></script>
    <script>
        let tableRowCount = 0;
        selectedCostCenterId = "";
        currentProcessType = null;
        po_header_ids = @json($poHeaderIds);
        grn_header_ids = @json($grnHeaderIds);
        po_details_ids = @json($poDetailsIds);
        grn_details_ids = @json($grnDetailsIds);
        @if ($buttons['amend'] && intval(request('amendment') ?? 0))
        @else
            @if ($expense->document_status != 'draft' && $expense->document_status != 'rejected')
                $(':input').prop('readonly', true);
                $('textarea[name="amend_remark"], input[type="file"][name="amend_attachment[]"]').prop('readonly', false)
                    .prop('disabled', false);
                $('select').not('.amendmentselect select').prop('disabled', true);
                $(".delete-po-items").remove();
                $(".delete-grn-items").remove();
                $(".distributeBtn").remove();
                $(".addNewItemBtn").remove();
                $(".grnSelect").remove();
                $(document).on('show.bs.modal', function(e) {
                    if (e.target.id != 'approveModal') {
                        if (e.target.id != 'shortCloseModal') {
                            $(e.target).find('.modal-footer').remove();
                        }
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
        window.onload = function() {
            localStorage.removeItem("selectedPoIds");
            localStorage.removeItem("selectedMrnIds");
            currentProcessType = null;
        };

        /*Delete Row*/
        $(document).on('click', '#deleteBtn', (e) => {
            let itemIds = [];
            $('#itemTable > tbody .form-check-input').each(function() {
                if ($(this).is(":checked")) {
                    itemIds.push($(this).val());
                }
            });

            if (itemIds.length) {
                itemIds.forEach(function(item, index) {
                    let poItemHiddenId = $(`#row_${item}`).find("input[name*='[po_item_hidden_ids]']")
                        .val();

                    if (poItemHiddenId) {
                        let idsToRemove = poItemHiddenId.split(',');
                        let selectedPoIds = localStorage.getItem('selectedPoIds');
                        if (selectedPoIds) {
                            selectedPoIds = JSON.parse(selectedPoIds);
                            let updatedIds = selectedPoIds.filter(id => !idsToRemove.includes(id));
                            localStorage.setItem('selectedPoIds', JSON.stringify(updatedIds));
                        }
                    }
                    $(`#row_${item}`).remove();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: "Please first add & select row item.",
                    icon: 'error',
                });
            }

            if (!$("#itemTable .mrntableselectexcel").find("tr[id*='row_']").length) {
                $(".poSelect").removeClass('d-none');
                $(".grnSelect").removeClass('d-none');
                $("#referenceNoDiv").hide();
                $("#addNewItemBtn").show();
                $("#itemTable > thead .form-check-input").prop('checked', false);
                $(".reference_type_input").val('');
                getLocation();
            }
            setTableCalculation();
        });

        /*Check box check and uncheck*/
        $(document).on('change', '#itemTable > thead .form-check-input', (e) => {
            if (e.target.checked) {
                $("#itemTable > tbody .form-check-input").each(function() {
                    $(this).prop('checked', true);
                });
            } else {
                $("#itemTable > tbody .form-check-input").each(function() {
                    $(this).prop('checked', false);
                });
            }
        });
        $(document).on('change', '#itemTable > tbody .form-check-input', (e) => {
            if (!$("#itemTable > tbody .form-check-input:not(:checked)").length) {
                $('#itemTable > thead .form-check-input').prop('checked', true);
            } else {
                $('#itemTable > thead .form-check-input').prop('checked', false);
            }
        });

        /*Check attrubute*/
        $(document).on('click', '.attributeBtn', (e) => {
            let tr = e.target.closest('tr');
            let item_name = tr.querySelector('[name*=item_code]').value;
            let item_id = tr.querySelector('[name*=item_id]').value;
            let selectedAttr = [];
            const attrElements = tr.querySelectorAll('[name*=attr_name]');
            if (attrElements.length > 0) {
                selectedAttr = Array.from(attrElements).map(element => element.value);
                selectedAttr = JSON.stringify(selectedAttr);
            }
            if (item_name && item_id) {
                let rowCount = tr.getAttribute('data-index');
                getItemAttribute(item_id, rowCount, selectedAttr, tr);
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: "Please select first item name.",
                    icon: 'error',
                });
            }
        });

        /*For comp attr*/
        function getItemAttribute(itemId, rowCount, selectedAttr, tr) {
            if (currentProcessType && currentProcessType != null) {
                rowCount = tableRowCount;
            }
            let expense_detail_id = "";
            let actionUrl = '{{ route('exp-allocation.item.attr') }}' + '?item_id=' + itemId + '&expense_detail_id=' +
                expense_detail_id + `&rowCount=${rowCount}&selectedAttr=${selectedAttr}`;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        $("#attribute tbody").empty();
                        $("#attribute table tbody").append(data.data.html)
                        $(tr).find('td:nth-child(2)').find("[name*=attr_name]").remove();
                        $(tr).find('td:nth-child(2)').append(data.data.hiddenHtml);
                        $(tr).find("td[id*='itemAttribute_']").attr('attribute-array', JSON.stringify(data
                            .data.itemAttributeArray));
                        if (data.data.attr) {
                            $("#attribute").modal('show');
                            $(".select2").select2();
                        }
                        qtyEnabledDisabled();
                        initAttributeAutocomplete();
                    }
                });
            });
        }

        /*Display item detail*/
        $(document).on('input change focus', '#itemTable tr input ', function(e) {
            let currentTr = e.target.closest('tr');
            getItemDetail(currentTr, currentProcessType);
        });

        function getItemDetail(currentTr) {
            const getVal = (selector) => {
                let el = $(currentTr).find(selector);
                return el.length ? el.val() : '';
            };

            let itemId = getVal("[name*='[item_id]']");
            if (!itemId) return;

            let selectedAttr = [];
            $(currentTr).find("[name*='[attr_name]']").each(function() {
                const val = $(this).val();
                if (val) selectedAttr.push(val);
            });

            let data = {
                item_id: itemId,
                purchase_order_id: getVal("[name*='[purchase_order_id]']"),
                po_detail_id: getVal("[name*='[po_detail_id]']"),
                job_order_id: getVal("[name*='[job_order_id]']"),
                jo_detail_id: getVal("[name*='[jo_detail_id]']"),
                remark: getVal("[name*='[remark]']"),
                uom_id: getVal("[name*='[uom_id]']"),
                qty: getVal("[name*='[accepted_qty]']"),
                headerId: getVal("[name*='[header_id]']"),
                detailId: getVal("[name*='[detail_id]']"),
                selectedAttr: JSON.stringify(selectedAttr),
                itemStoreData: JSON.parse(getVal("[id*='components_stores_data']") || "[]"),
                type: currentProcessType
            };

            let actionUrl = '{{ route('exp-allocation.get.itemdetail') }}?' + new URLSearchParams(data).toString();

            fetch(actionUrl)
                .then(res => res.json())
                .then(data => {
                    if (data.status == 200) {
                        $("#itemDetailDisplay").html(data.data.html);
                    }
                });
        }

        /*Tbl row highlight*/
        $(document).on('click', '.mrntableselectexcel tr', (e) => {
            $(e.target.closest('tr')).addClass('trselected').siblings().removeClass('trselected');
        });
        $(document).on('keydown', function(e) {
            if (e.which == 38) {
                /*bottom to top*/
                $('.trselected').prev('tr').addClass('trselected').siblings().removeClass('trselected');
            } else if (e.which == 40) {
                /*top to bottom*/
                $('.trselected').next('tr').addClass('trselected').siblings().removeClass('trselected');
            }
            if ($('.trselected').length) {
                // $('html, body').scrollTop($('.trselected').offset().top - 200);
            }
        });

        /*Amendment modal open*/
        $(document).on('click', '.amendmentBtn', (e) => {
            $("#amendmentconfirm").modal('show');
        });

        $(document).on('click', '#amendmentSubmit', (e) => {
            let url = new URL(window.location.href);
            url.search = '';
            url.searchParams.set('amendment', 1);
            let amendmentUrl = url.toString();
            window.location.replace(amendmentUrl);
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
                $("#expAlcEditForm").submit();
            }
        });

        // GL Posting
        function resetPostVoucher() {
            document.getElementById('voucher_doc_no').value = '';
            document.getElementById('voucher_date').value = '';
            document.getElementById('voucher_book_code').value = '';
            document.getElementById('voucher_currency').value = '';
            document.getElementById('posting-table').innerHTML = '';
            document.getElementById('posting_button').style.display = 'none';
        }

        function onPostVoucherOpen(type = "not_posted") {
            // resetPostVoucher();
            const apiURL = "{{ route('exp-allocation.posting.get') }}";
            $.ajax({
                url: apiURL + "?book_id=" + $("#book_id").val() + "&document_id=" +
                    "{{ isset($expense) ? $expense->id : '' }}",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if (!data.data.status) {
                        Swal.fire({
                            title: 'Error!',
                            text: data.data.message,
                            icon: 'error',
                        });
                        return;
                    }
                    const voucherEntries = data.data.data;
                    var voucherEntriesHTML = ``;
                    Object.keys(voucherEntries.ledgers).forEach((voucher) => {
                        voucherEntries.ledgers[voucher].forEach((voucherDetail, index) => {
                            voucherEntriesHTML += `
                            <tr>
                            <td>${voucher}</td>
                            <td class="fw-bolder text-dark">${voucherDetail.ledger_group_code ? voucherDetail.ledger_group_code : ''}</td>
                            <td>${voucherDetail.ledger_code ? voucherDetail.ledger_code : ''}</td>
                            <td>${voucherDetail.ledger_name ? voucherDetail.ledger_name : ''}</td>
                            <td class="text-end">${voucherDetail.debit_amount > 0 ? parseFloat(voucherDetail.debit_amount).toFixed(2) : ''}</td>
                            <td class="text-end">${voucherDetail.credit_amount > 0 ? parseFloat(voucherDetail.credit_amount).toFixed(2) : ''}</td>
                            </tr>
                            `
                        });
                    });
                    voucherEntriesHTML += `
                    <tr>
                        <td colspan="4" class="fw-bolder text-dark text-end">Total</td>
                        <td class="fw-bolder text-dark text-end">${voucherEntries.total_debit.toFixed(2)}</td>
                        <td class="fw-bolder text-dark text-end">${voucherEntries.total_credit.toFixed(2)}</td>
                    </tr>
                    `;
                    document.getElementById('posting-table').innerHTML = voucherEntriesHTML;
                    document.getElementById('voucher_doc_no').value = voucherEntries.document_number;
                    document.getElementById('voucher_date').value = moment(voucherEntries.document_date).format(
                        'D/M/Y');
                    document.getElementById('voucher_book_code').value = voucherEntries.book_code;
                    document.getElementById('voucher_currency').value = voucherEntries.currency_code;
                    if (type === "posted") {
                        document.getElementById('posting_button').style.display = 'none';
                    } else {
                        document.getElementById('posting_button').style.removeProperty('display');
                    }
                    $('#postvoucher').modal('show');
                }
            });
        }

        function postVoucher(element) {
            const bookId = "{{ isset($expense) ? $expense->book_id : '' }}";
            const documentId = "{{ isset($expense) ? $expense->id : '' }}";
            const postingApiUrl = "{{ route('exp-allocation.post') }}"
            if (bookId && documentId) {
                $.ajax({
                    url: postingApiUrl,
                    type: "POST",
                    dataType: "json",
                    contentType: "application/json", // Specifies the request payload type
                    data: JSON.stringify({
                        // Your JSON request data here
                        book_id: bookId,
                        document_id: documentId,
                    }),
                    success: function(data) {
                        const response = data.data;
                        if (response.status) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                            });
                            location.reload();
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error',
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Some internal error occured',
                            icon: 'error',
                        });
                    }
                });

            }
        }

        /*Get location based on vendor*/
        function getLocation(locationId = '') {
            let actionUrl = '{{ route('store.get') }}' + '?location_id=' + locationId;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        let options = '';
                        data.data.locations.forEach(function(location) {
                            options +=
                                `<option value="${location.id}">${location.store_code}</option>`;
                        });
                        $("[name='header_store_id']").empty().append(options);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                        });
                    }
                });
            });
        }
    </script>
@endsection

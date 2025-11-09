@extends('layouts.app')
@section('styles')
    <style>
        .tooltip-inner {
            text-align: left
        }
    </style>
@endsection
@section('content')
    @php
        $routeName = $servicesBooks['services'][0]->alias ?? 'material-receipt';
        $routeAlias = $routeName && $routeName == 'mrn' ? 'material-receipt' : $routeName;
        $routeRedirect = $routeAlias && $routeAlias == 'material-receipt' ? 'material-receipts' : $routeAlias;
    @endphp
    <form id="mrnEditForm" data-module="mrn" class="ajax-input-form">
        @csrf
        <input type="hidden" name="tax_required" id="tax_required" value="">
        <input type="hidden" name="bill_to_follow" id="bill_to_follow" class="bill_to_follow"
            value={{ $mrn->bill_to_follow }}>
        <input type="hidden" name="inspection_required" id="inspection_required" class="inspection_required"
            value="{{ $mrn->is_inspection_completion === 1 ? 'no' : 'yes' }}">
        <div class="app-content content ">
            <div class="content-overlay"></div>
            <div class="header-navbar-shadow"></div>
            <div class="content-wrapper container-xxl p-0">
                <div class="content-header pocreate-sticky">
                    <div class="row">
                        <div class="content-header-left col-md-6 mb-2">
                            <div class="row breadcrumbs-top">
                                <div class="col-12">
                                    <h2 class="content-header-title float-start mb-0">
                                        {{ $servicesBooks['services'][0]->name ?? 'GRN' }}
                                    </h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="/">Home</a>
                                            </li>
                                            <li class="breadcrumb-item active">View</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <input type="hidden" name="document_status" value="{{ $mrn->document_status }}"
                                    id="document_status">
                                <button type="button" onClick="javascript: history.go(-1)"
                                    class="btn btn-secondary btn-sm mb-50 mb-sm-0">
                                    <i data-feather="arrow-left-circle"></i> Back
                                </button>
                                @if ($mrn->document_status == 'posted')
                                    <button type="button" onclick="onPostVoucherOpen('posted')"
                                        class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="feather feather-file-text">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13">
                                            </line>
                                            <line x1="16" y1="17" x2="8" y2="17">
                                            </line>
                                            <polyline points="10 9 9 9 8 9"></polyline>
                                        </svg> Voucher
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
                                <div class="card">
                                    <div class="card-body customernewsection-form">
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
                                                    class="badge rounded-pill badge-light-{{ $mrn->display_status === 'Posted' ? 'info' : 'secondary' }} forminnerstatus">
                                                    <span class = "text-dark">Status</span> : <span
                                                        class="{{ $docStatusClass }}">{{ $mrn->display_status }}</span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="hidden" name="book_id" class="form-control"
                                                            id="book_id" value="{{ $mrn->series_id }}" readonly>
                                                        <input readonly type="text" class="form-control"
                                                            value="{{ $mrn->book->book_code }}" id="book_code">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Document No <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control document_number"
                                                            readonly value="{{ @$mrn->document_number }}"
                                                            id="document_number">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Document Date <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="date" id="document_date" name="document_date"
                                                            class="form-control document_date"
                                                            value="{{ date('Y-m-d', strtotime($mrn->document_date)) }}">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select header_store_id" id="header_store_id"
                                                            name="header_store_id">
                                                            @foreach ($locations as $erpStore)
                                                                <option value="{{ $erpStore->id }}"
                                                                    {{ $mrn->store_id == $erpStore->id ? 'selected' : '' }}>
                                                                    {{ ucfirst($erpStore->store_name) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Store <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select sub_store" id="sub_store_id"
                                                            name="sub_store_id">
                                                            <option value="{{ $mrn->sub_store_id }}"
                                                                data-warehouse-required="{{ $mrn?->is_warehouse_required }}">
                                                                {{ ucfirst($mrn?->erpSubStore?->name) }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <input type="hidden" class="is_warehouse_required"
                                                        name="is_warehouse_required" id="is_warehouse_required"
                                                        value="{{ $mrn?->is_warehouse_required }}">
                                                </div>
                                                <input type="hidden" name="mrn_tds_id" class="form-control mrn_tds_id"
                                                    id="mrn_tds_input" value="{{ $mrn?->header_tax?->id }}" readonly>
                                            </div>
                                            {{-- Approval History Section --}}
                                            @include('partials.approval-history', [
                                                'document_status' => $mrn->document_status,
                                                'revision_number' => $revision_number,
                                            ])
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card quation-card">
                                            <div class="card-header newheader">
                                                <div>
                                                    <h4 class="card-title">Vendor Details</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">Vendor <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" placeholder="Select"
                                                                class="form-control mw-100 ledgerselecct vendor_name"
                                                                id="vendor_name" name="vendor_name"
                                                                {{ count($mrn->items) > 0 ? 'readonly' : '' }}
                                                                value="{{ @$mrn->vendor->company_name }}" />
                                                            <input type="hidden" value="{{ @$mrn->vendor_id }}"
                                                                id="vendor_id" name="vendor_id" class="vendor_id" />
                                                            <input type="hidden" value="{{ @$mrn->vendor_code }}"
                                                                id="vendor_code" name="vendor_code" />
                                                            @if ($mrn->latestShippingAddress() || $mrn->latestBillingAddress())
                                                                <input type="hidden"
                                                                    value="{{ $mrn->latestShippingAddress() }}"
                                                                    id="shipping_id" name="shipping_id" />
                                                                <input type="hidden" id="billing_id"
                                                                    value="{{ $mrn->latestBillingAddress()->id }}"
                                                                    name="billing_id" />
                                                                <input type="hidden"
                                                                    value="{{ $mrn->latestBillingAddress()->state?->id }}"
                                                                    id="hidden_state_id" name="hidden_state_id" />
                                                                <input type="hidden"
                                                                    value="{{ $mrn->latestBillingAddress()->country?->id }}"
                                                                    id="hidden_country_id" name="hidden_country_id" />
                                                            @else
                                                                <input type="hidden" value="{{ $mrn->ship_to }}"
                                                                    id="shipping_id" name="shipping_id" />
                                                                <input type="hidden" id="billing_id"
                                                                    value="{{ $mrn->billing_to }}" name="billing_id" />
                                                                <input type="hidden"
                                                                    value="{{ $mrn?->billingAddress?->state?->id }}"
                                                                    id="hidden_state_id" name="hidden_state_id" />
                                                                <input type="hidden"
                                                                    value="{{ $mrn?->billingAddress?->country?->id }}"
                                                                    id="hidden_country_id" name="hidden_country_id" />
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">Currency <span
                                                                    class="text-danger">*</span></label>
                                                            <select class="form-select" name="currency_id" disabled>
                                                                <option value="{{ @$mrn->currency_id }}">
                                                                    {{ @$mrn->currency->name }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">Payment Terms <span
                                                                    class="text-danger">*</span></label>
                                                            <select class="form-select" name="payment_term_id" disabled>
                                                                <option value="{{ @$mrn->payment_term_id }}">
                                                                    {{ @$mrn->paymentTerm->name }}</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">Credit Days <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text" class="form-control mw-100"
                                                                id="credit_days" name="credit_days"
                                                                value="{{ @$mrn->credit_days }}" readonly />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="customer-billing-section h-100">
                                                            <p>Vendor Address</p>
                                                            <div class="bilnbody">
                                                                <div class="genertedvariables genertedvariablesnone">
                                                                    <label class="form-label w-100">Vendor Address <span
                                                                            class="text-danger">*</span> <a
                                                                            href="javascript:;"
                                                                            class="float-end font-small-2 editAddressBtn d-none"
                                                                            data-type="billing"><i
                                                                                data-feather='edit-3'></i> Edit</a></label>
                                                                    <div class="mrnaddedd-prim billing_detail">
                                                                        @if ($mrn->latestBillingAddress())
                                                                            {{ $mrn->latestBillingAddress()->display_address }}
                                                                        @else
                                                                            {{ $mrn->bill_address?->display_address }}
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="customer-billing-section h-100">
                                                            <p>Billing Address</p>
                                                            <div class="bilnbody">
                                                                <div class="genertedvariables genertedvariablesnone">
                                                                    <label class="form-label w-100">Billing Address <span
                                                                            class="text-danger">*</span>
                                                                        {{-- <a href="javascript:;" class="float-end font-small-2 editAddressBtn" data-type="billing"><i data-feather='edit-3'></i> Edit</a> --}}
                                                                    </label>
                                                                    <div class="mrnaddedd-prim org_address">
                                                                        {{ $deliveryAddress }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="customer-billing-section h-100">
                                                            <p>Delivery Address</p>
                                                            <div class="bilnbody">
                                                                <div class="genertedvariables genertedvariablesnone">
                                                                    <label class="form-label w-100">Delivery Address <span
                                                                            class="text-danger">*</span>
                                                                        {{-- <a href="javascript:;" class="float-end font-small-2 editAddressBtn" data-type="billing"><i data-feather='edit-3'></i> Edit</a> --}}
                                                                    </label>
                                                                    <div class="mrnaddedd-prim delivery_address">
                                                                        {{ $deliveryAddress }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card quation-card">
                                            <div class="card-header newheader">
                                                <div>
                                                    <h4 class="card-title">General Information</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    @if ($mrn->cost_center_id !== null)
                                                        <div class="col-md-3" id="cost_center_div" style="display:none;">
                                                            <div class="mb-1">
                                                                <label class="form-label">Cost Center <span
                                                                        class="text-danger">*</span></label>
                                                                <select class="form-select cost_center"
                                                                    id="cost_center_id" name="cost_center_id">
                                                                </select>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">LOT No </label>
                                                            <input type="text" name="lot_number"
                                                                value="{{ @$mrn->lot_number }}" class="form-control"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Gate Entry No.
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="text" name="gate_entry_no"
                                                                class="form-control gate_entry_no"
                                                                value="{{ @$mrn->gate_entry_no }}"
                                                                placeholder="Enter Gate Entry no" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Gate Entry Date
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="date" name="gate_entry_date"
                                                                value="{{ date('Y-m-d', strtotime($mrn->gate_entry_date)) }}"
                                                                class="form-control gate-entry gate_entry_date"
                                                                id="datepicker2" placeholder="Enter Gate Entry Date"
                                                                readonly>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                E-Way Bill No.
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="text" name="eway_bill_no"
                                                                value="{{ @$mrn->eway_bill_no }}" class="form-control"
                                                                placeholder="Enter Eway Bill No.">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Consignment No.
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="text" name="consignment_no"
                                                                value="{{ @$mrn->consignment_no }}" class="form-control"
                                                                placeholder="Enter Consignment No.">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Supplier Invoice No.
                                                            </label>
                                                            <input type="text" name="supplier_invoice_no"
                                                                value="{{ @$mrn->supplier_invoice_no }}"
                                                                class="form-control supplier_invoice_no"
                                                                placeholder="Enter Supplier Invoice No.">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Supplier Invoice Date
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="date" name="supplier_invoice_date"
                                                                value="{{ date('Y-m-d', strtotime($mrn->supplier_invoice_date)) }}"
                                                                class="form-control gate-entry supplier_invoice_date"
                                                                id="datepicker3"
                                                                placeholder="Enter Supplier Invoice Date">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Transporter Name
                                                            </label>
                                                            <input type="text" name="transporter_name"
                                                                value="{{ @$mrn->transporter_name }}"
                                                                class="form-control" placeholder="Enter Transporter Name">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Vehicle No.
                                                                <i class="ml-2 fas fa-info-circle text-primary"
                                                                    data-bs-toggle="tooltip" data-bs-html="true"
                                                                    title="Format:<br>[A-Z]{2} – 2 uppercase letters (e.g., 'MH')<br>[0-9]{2} – 2 digits (e.g., '12')<br>[A-Z]{0,3} – 0 to 3 uppercase letters (e.g., 'AB', 'ABZ')<br>[0-9]{4} – 4 digits (e.g., '1234')"></i>
                                                            </label>
                                                            <input type="text" name="vehicle_no"
                                                                class="form-control vehicle_no"
                                                                value="{{ @$mrn->vehicle_no }}"
                                                                placeholder="Enter Vehicle No." />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Manual Entry No.
                                                                <!-- <span class="text-danger">*</span> -->
                                                            </label>
                                                            <input type="text" name="manual_entry_no"
                                                                class="form-control" value="{{ @$mrn->manual_entry_no }}"
                                                                placeholder="Enter Manual Entry no">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="col-md-12 {{ (isset($mrn) && count($mrn->dynamic_fields)) > 0 ? '' : 'd-none' }}">
                                    @if (isset($dynamicFieldsUI))
                                        {!! $dynamicFieldsUI !!}
                                    @endif
                                </div>
                                <div class="card" id="item_section">
                                    <div class="card-body customernewsection-form">
                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader ">
                                                        <h4 class="card-title text-theme">Item Wise Detail</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="table-responsive pomrnheadtffotsticky">
                                                    <table id="itemTable"
                                                        class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad itemTable"
                                                        data-json-key="components_json"
                                                        data-row-selector="tr[id^='row_']">
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
                                                                <th width="150px">Item Code</th>
                                                                <th width="240px">Item Name</th>
                                                                <th>Attributes</th>
                                                                <th>UOM</th>
                                                                <th class="text-end">
                                                                    {{ $mrn->reference_type == 'po' ? 'PO Qty' : ($mrn->reference_type == 'jo' ? 'JO Qty' : 'Qty') }}
                                                                </th>
                                                                <th class="text-end">Recpt Qty</th>
                                                                <th class="text-end">Acpt. Qty</th>
                                                                <th class="text-end">Rej. Qty</th>
                                                                <th class="text-end">Foc Qty</th>
                                                                <th class="text-end" id="rateHeader">Rate</th>
                                                                <th class="text-end">Value</th>
                                                                <th>Discount</th>
                                                                <th class="text-end">Total</th>
                                                                <th width="50px">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="mrntableselectexcel">
                                                            @include('procurement.material-receipt.partials.item-row-edit')
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="totalsubheadpodetail">
                                                                <td colspan="11"></td>
                                                                <td class="text-end" id="totalItemValue">
                                                                    {{ @$mrn->items->sum('basic_value') }}
                                                                </td>
                                                                <td class="text-end" id="totalItemDiscount">
                                                                    {{ @$mrn->items->sum('discount_amount') }}
                                                                </td>
                                                                <td class="text-end" id="TotalEachRowAmount">
                                                                    {{ @$mrn->items->sum('net_value') }}
                                                                </td>
                                                            </tr>
                                                            <tr valign="top">
                                                                <td rowspan="10" colspan="9">
                                                                    <table class="table border">
                                                                        <tbody id="itemDetailDisplay">
                                                                            <tr>
                                                                                <td class="p-0">
                                                                                    <h6
                                                                                        class="text-dark mb-0 bg-light-primary py-1 px-50">
                                                                                        <strong>Item Details</strong>
                                                                                    </h6>
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
                                                                            <tr>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                                <td colspan="6">
                                                                    <table class="table border mrnsummarynewsty">
                                                                        <tr>
                                                                            <td colspan="2" class="p-0">
                                                                                <h6
                                                                                    class="text-dark mb-0 bg-light-primary py-1 px-50 d-flex justify-content-between">
                                                                                    <strong>Document Summary</strong>
                                                                                    <div class="addmendisexpbtn">
                                                                                        <button type="button"
                                                                                            class="btn p-25 btn-sm btn-outline-secondary summaryTaxBtn">{{-- <i data-feather="plus"></i> --}}
                                                                                            Tax</button>
                                                                                        <button type="button"
                                                                                            class="btn p-25 btn-sm btn-outline-secondary summaryDisBtn"><i
                                                                                                data-feather="plus"></i>
                                                                                            Discount</button>
                                                                                        <button type="button"
                                                                                            class="btn p-25 btn-sm btn-outline-secondary summaryExpBtn"><i
                                                                                                data-feather="plus"></i>
                                                                                            Expenses</button>
                                                                                    </div>
                                                                                </h6>
                                                                            </td>
                                                                        </tr>
                                                                        <tr class="totalsubheadpodetail">
                                                                            <td width="55%"><strong>Sub Total</strong>
                                                                            </td>
                                                                            <td class="text-end" id="f_sub_total">
                                                                                <!-- {{ number_format(@$mrn->total_item_amount, 2) }} -->
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Item Discount</strong></td>
                                                                            <td class="text-end" id="f_total_discount">
                                                                                <!-- {{ number_format(@$mrn->item_discount, 2) }} -->
                                                                            </td>
                                                                        </tr>
                                                                        @if ($mrn->headerDiscount)
                                                                            <tr id="f_header_discount_hidden">
                                                                                <td><strong>Header Discount</strong></td>
                                                                                <td class="text-end"
                                                                                    id="f_header_discount">
                                                                                    {{ $mrn->headerDiscount()->sum('ted_amount') }}
                                                                                </td>
                                                                            </tr>
                                                                        @else
                                                                            <tr class="d-none"
                                                                                id="f_header_discount_hidden">
                                                                                <td><strong>Header Discount</strong></td>
                                                                                <td class="text-end"
                                                                                    id="f_header_discount">0.00</td>
                                                                            </tr>
                                                                        @endif
                                                                        <tr class="totalsubheadpodetail">
                                                                            <td><strong>Taxable Value</strong></td>
                                                                            <td class="text-end" id="f_taxable_value"
                                                                                amount="">
                                                                                <!-- {{ number_format(@$mrn->taxable_amount, 2) }} -->
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Tax</strong></td>
                                                                            <td class="text-end" id="f_tax">
                                                                                <!-- {{ number_format(@$mrn->total_taxes, 2) }}         -->
                                                                                <input id = "tax_amount_header"
                                                                                    type="hidden"
                                                                                    name="taxes_amount_header" />
                                                                            </td>
                                                                        </tr>
                                                                        <tr class="totalsubheadpodetail">
                                                                            <td><strong>Total After Tax</strong></td>
                                                                            <td class="text-end" id="f_total_after_tax">
                                                                                <!-- {{ number_format(@$mrn->total_after_tax_amount, 2) }} -->
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Exp.</strong></td>
                                                                            <td class="text-end" id="f_exp">
                                                                                <!-- {{ number_format(@$mrn->expense_amount, 2) }} -->
                                                                            </td>
                                                                            <input type="hidden" name="expense_amount"
                                                                                class="text-end" id="expense_amount"
                                                                                value="{{ $mrn->expense_amount }}">
                                                                        </tr>
                                                                        <tr class="voucher-tab-foot">
                                                                            <td class="text-primary"><strong>Total After
                                                                                    Exp.</strong></td>
                                                                            <td>
                                                                                <div
                                                                                    class="quottotal-bg justify-content-end">
                                                                                    <h5 id="f_total_after_exp">
                                                                                        <!-- {{ number_format(@$mrn->total_amount, 2) }} -->
                                                                                    </h5>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-12">
                                                        <div class="col-md-4">
                                                            <div class="mb-1">
                                                                <label class="form-label">Upload Document</label>
                                                                <input type="file" name="attachment[]"
                                                                    class="form-control"
                                                                    onchange = "addFiles(this,'main_mrn_preview')"
                                                                    multiple>
                                                                <span
                                                                    class = "text-primary small">{{ __('message.attachment_caption') }}</span>
                                                            </div>
                                                        </div>
                                                        @include('partials.document-preview', [
                                                            'documents' => $mrn->getDocuments(),
                                                            'document_status' => $mrn->document_status,
                                                            'elementKey' => 'main_mrn_preview',
                                                        ])
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="mb-1">
                                                            <label class="form-label">Final Remarks</label>
                                                            <textarea type="text" rows="4" name="remarks" class="form-control" placeholder="Enter Remarks here...">{!! $mrn->final_remarks !!}</textarea>
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
        {{-- Discount summary modal --}}
        @include('procurement.material-receipt.partials.summary-disc-modal')
        {{-- Add expenses modal --}}
        @include('procurement.material-receipt.partials.summary-exp-modal')
        {{-- Asset Detail Modal --}}
        @include('procurement.material-receipt.partials.asset-detail-modal')
        {{-- Item Batch --}}
        @include('procurement.material-receipt.partials.item-batch-modal')
        {{-- Edit Address --}}
        <div class="modal fade" id="edit-address" tabindex="-1" aria-labelledby="shareProjectTitle"
            aria-hidden="true">
            <div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
            </div>
        </div>
        @include('procurement.material-receipt.partials.amendement-modal', ['id' => $mrn->id])
    </form>
    {{-- Item upload modal --}}
    @include('partials.import-item-modal')
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

    {{-- Add each row discount popup --}}
    <div class="modal fade" id="itemRowDiscountModal" tabindex="-1" aria-labelledby="shareProjectTitle"
        aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-2 mx-50 pb-2">
                    <h1 class="text-center mb-1" id="shareProjectTitle">Discount</h1>
                    <div class="text-end"></div>
                    <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
                        <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <label class="form-label">Type<span class="text-danger">*</span></label>
                                    <input type="text" id="new_item_dis_name_select" placeholder="Select"
                                        class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off"
                                        value="">
                                    <input type = "hidden" id = "new_item_discount_id" />
                                    <input type = "hidden" id = "new_item_dis_name" />
                                </td>
                                <td>
                                    <label class="form-label">Percentage <span class="text-danger">*</span></label>
                                    <input step="any" type="number" id="new_item_dis_perc"
                                        class="form-control mw-100" />
                                </td>
                                <td>
                                    <label class="form-label">Value <span class="text-danger">*</span></label>
                                    <input step="any" type="number" id="new_item_dis_value"
                                        class="form-control mw-100" />
                                </td>
                                <td>
                                    <a href="javascript:;" id="add_new_item_dis" class="text-primary can_hide">
                                        <i data-feather="plus-square"></i>
                                    </a>
                                </td>
                            </tr>
                        </thead>
                    </table>
                    <div class="table-responsive-md customernewsection-form">
                        <table id="eachRowDiscountTable"
                            class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th width="150px">Discount Name</th>
                                    <th>Discount %</th>
                                    <th>Discount Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="disItemFooter">
                                    <input type="hidden" name="row_count" id="row_count" value="1">
                                    <td colspan="2"></td>
                                    <td class="text-dark"><strong>Total</strong></td>
                                    <td class="text-dark text-end"><strong id="total">0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                    <button type="button" class="btn btn-primary itemDiscountSubmit">Submit</button>
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
                    {{--
                    <p class="text-center">Enter the details below.</p>
                    --}}
                    <div class="row mt-2">
                        <div class="col-md-12 mb-1">
                            <label class="form-label">Remarks</label>
                            <input type="hidden" name="row_count" id="row_count">
                            <textarea class="form-control" placeholder="Enter Remarks"></textarea>
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
    {{-- Item Locations --}}
    @include('procurement.material-receipt.partials.item-location-modal')
    <!-- Item Locations Modal End -->

    {{-- Delete component modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="deleteComponentModal" tabindex="-1"
        aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
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

    {{-- Delete Item discount modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="deleteItemDiscModal" tabindex="-1"
        aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
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
                    <button type="button" id="deleteItemDiscConfirm" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Header discount modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="deleteHeaderDiscModal" tabindex="-1"
        aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
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
                    <button type="button" id="deleteHeaderDiscConfirm" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete header exp modal --}}
    <div class="modal fade text-start alertbackdropdisabled" id="deleteHeaderExpModal" tabindex="-1"
        aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
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
                    <button type="button" id="deleteHeaderExpConfirm" class="btn btn-primary">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    @include('procurement.material-receipt.partials.approve-modal', ['id' => $mrn->id])

    {{-- Taxes --}}
    @include('procurement.material-receipt.partials.tax-detail-modal')

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
                    <p>Are you sure you want to <strong>Amendment</strong> this <strong>Document</strong>? After Amendment
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
    {{-- Storage Points --}}
    @include('procurement.material-receipt.partials.storage-point-modal')
    {{-- Add Outstanding PO modal --}}
    @include('procurement.material-receipt.partials.outstanding-po-modal')
    {{-- Add Outstanding JO modal --}}
    @include('procurement.material-receipt.partials.outstanding-jo-modal')
    {{-- Add Outstanding JO modal --}}
    @include('procurement.material-receipt.partials.outstanding-so-modal')
    <!-- Close Deviation Modal -->
    <div class="modal fade" id="deviateModal" tabindex="-1" aria-labelledby="deviateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <form class="ajax-input-form" method="POST" action="{{ route('document.approval.material-receipt') }}"
                    data-redirect="{{ route('material-receipt.index') }}" enctype="multipart/form-data">

                    @csrf
                    <input type="hidden" name="action_type" id="action_type">
                    <input type="hidden" name="closing_job_id" id="closing_job_id"
                        value="{{ $mrn->deviationJob?->id ?? '' }}">
                    <input type="hidden" name="id" value="{{ $mrn->id ?? '' }}">

                    <!-- Modal Header -->
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold" id="deviateModalLabel">
                            <i class="bi bi-exclamation-triangle me-2"></i>Putaway Deviation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body px-4 py-3">
                        <div class="row text-center mb-4">
                            <div class="col">
                                <div class="bg-light rounded p-1 border">
                                    <h6 class="mb-1 text-secondary">Total Packets</h6>
                                    <h5 class="mb-0 fw-bold text-dark">{{ $itemUniqueCodes['total_unique_codes'] }}</h5>
                                </div>
                            </div>
                            <div class="col">
                                <div class="bg-light rounded p-1 border">
                                    <h6 class="mb-1 text-secondary">Scanned Packets</h6>
                                    <h5 class="mb-0 fw-bold text-dark">{{ $itemUniqueCodes['scanned_unique_codes'] }}</h5>
                                </div>
                            </div>
                            <div class="col">
                                <div class="bg-light rounded p-1 border">
                                    <h6 class="mb-1 text-secondary">Deviation</h6>
                                    <h5
                                        class="mb-0 fw-bold {{ $itemUniqueCodes['pending_unique_codes'] > 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ $itemUniqueCodes['pending_unique_codes'] }}</h5>
                                </div>
                            </div>
                            <!-- <div id="deviation-batch-table-wrap" class="mt-3"></div>
                                                                                                                                <input type="hidden" name="deviation_breakup_json" id="deviation_breakup_json"> -->
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label fw-semibold text-dark">Remarks</label>
                            <textarea maxlength="250" name="closing_remarks" id="remarks" class="form-control" rows="4"
                                placeholder="Enter your remarks here..."></textarea>
                            <!-- <div class="form-text text-muted">Max 250 characters</div> -->
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer border-0 justify-content-center pb-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary px-5">
                            Close Deviation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-attr-ui.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-datatable.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/mrn.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/asset-registration.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/modules/item-batch.js') }}"></script>
    <script>
        let mrnData = @json($mrn);
        var actionUrlTax = '{{ route('material-receipt.tax.calculation') }}';
        selectedCostCenterId = @json($mrn->cost_center_id);
        let currentProcessType = @json($mrn->reference_type);
        var qtyChangeUrl = '{{ route('material-receipt.get.validate-quantity') }}';
        let taxCalUrl = '{{ route('tax.group.calculate') }}';
        console.log('mrnData');

        let calTaxTdsUrl = '{{ route('tax.calculate.tds') }}';

        let currentIndex = '';
        if ($(".bill_to_follow").val() === 'no') {
            setTimeout(() => {
                getTdsTax();
            }, 1000);
        }
        $(".joSelect").hide();
        $(".poSelect").hide();
        $(".soSelect").hide();
        $("#addNewItemBtn").hide();
        $("#importItem").hide();
        $(".dnoteSelect").hide();

        let tableRowCount = 0;
        /*Clear local storage*/
        setTimeout(() => {
            localStorage.removeItem('deletedItemDiscTedIds');
            localStorage.removeItem('deletedHeaderDiscTedIds');
            localStorage.removeItem('deletedHeaderExpTedIds');
            localStorage.removeItem('deletedItemLocationIds');
            localStorage.removeItem('deletedMrnItemIds');
        }, 0);
        @if ($subStoreCount > 0)
            // Set colspan to 9
            $("td.dynamic-colspan").attr("colspan", 10);
            $("td.dynamic-summary-colspan").attr("colspan", 10);
        @else
            // Set colspan to 8
            $("td.dynamic-colspan").attr("colspan", 9);
            $("td.dynamic-summary-colspan").attr("colspan", 9);
        @endif

        let header_ids = @json($headerIds);
        let details_ids = @json($detailsIds);
        let asn_header_ids = @json($asnHeaderIds);
        let asn_details_ids = @json($asnDetailsIds);
        let ge_header_ids = @json($geHeaderIds);
        let ge_details_ids = @json($geDetailsIds);
        let exist_payment_term_id = @json($existPaymentTermId);
        let exist_credit_days = @json($existCreditDays);

        @if ($buttons['amend'] && intval(request('amendment') ?? 0))
        @else
            @if ($mrn->document_status != 'draft' && $mrn->document_status != 'rejected')
                $(':input').prop('readonly', true);
                $('textarea[name="amend_remark"], textarea[name="closing_remarks"], input[type="file"][name="amend_attachment[]"]')
                    .prop('readonly', false).prop('disabled', false);
                $('select').not('.amendmentselect select').prop('disabled', true);
                $("#deleteBtn").remove();
                $("#addNewItemBtn").remove();
                $(".editAddressBtn").remove();
                $("#add_new_item_dis").remove();
                $(".deleteItemDiscountRow").remove();
                $("#add_new_head_dis").remove();
                $(".deleteSummaryDiscountRow").remove();
                $("#add_new_head_exp").remove();
                $(".deleteExpRow").remove();
                $('a.add-batch-row-header, button.add-batch-row-header').remove();
                $('a.remove-batch-row, button.remove-batch-row, .delete-batch-row-header').remove();

                $(document).on('show.bs.modal', function(e) {
                    if (e.target.id != 'approveModal') {
                        if (e.target.id != 'deviateModal') {
                            $(e.target).find('.modal-footer').remove();
                        }
                        $('select').not('.amendmentselect select').prop('disabled', true);
                    }
                    if (e.target.id == 'approveModal') {
                        $(e.target).find(':input').prop('readonly', false);
                        $(e.target).find('select').prop('readonly', false);
                    }
                    // $('.add-contactpeontxt').remove();
                    // let text = $(e.target).find('thead tr:first th:last').text();
                    // if(text.includes("Action")){
                    //     $(e.target).find('thead tr').each(function() {
                    //         $(this).find('th:last').remove();
                    //     });
                    //     $(e.target).find('tbody tr').each(function() {
                    //         $(this).find('td:last').remove();
                    //     });
                    // }
                });
            @endif
        @endif

        // Change BookId
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
            let storeId = $("[name='header_store_id']").val();
            let subStoreId = $("[name='sub_store_id']").val();
            let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId + '&document_date=' +
                document_date;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        const parameters = data.data.parameters;
                        setServiceParameters(parameters);

                        if (parameters?.tax_required.some(val => val.toLowerCase() === 'yes')) {
                            $("#tax_required").val(parameters?.tax_required[0]);
                        } else {
                            $("#tax_required").val("");
                        }
                        setTableCalculation(true);
                        // checkWarehouseSetup(storeId, subStoreId);
                        $(".inspection_required").val(parameters?.inspection_required[0]);
                    }
                    if (data.status == 404) {
                        $("#book_code").val('');
                        $("#document_number").val('');
                        $("#tax_required").val("");
                        const docDateInput = $("[name='document_date']");
                        docDateInput.removeAttr('min');
                        docDateInput.removeAttr('max');
                        docDateInput.val(new Date().toISOString().split('T')[0]);
                    }
                });
            });
        }

        /*for trigger on edit cases*/
        setTimeout(() => {
            let bookId = $("#book_id").val();
            getDocNumberByBookId(bookId);
        }, 1000);

        /*Set Service Parameter*/
        function setServiceParameters(parameters) {
            /*Date Validation*/
            const docDateInput = $("[name='document_date']");
            let isFeature = false;
            let isPast = false;
            if (parameters.future_date_allowed && parameters.future_date_allowed.includes('yes')) {
                let futureDate = new Date();
                futureDate.setDate(futureDate.getDate() /*+ (parameters.future_date_days || 1)*/ );
                // docDateInput.val(futureDate.toISOString().split('T')[0]);
                docDateInput.attr("min", new Date().toISOString().split('T')[0]);
                isFeature = true;
            } else {
                isFeature = false;
                docDateInput.attr("max", new Date().toISOString().split('T')[0]);
            }
            if (parameters.back_date_allowed && parameters.back_date_allowed.includes('yes')) {
                let backDate = new Date();
                backDate.setDate(backDate.getDate() /*- (parameters.back_date_days || 1)*/ );
                // docDateInput.val(backDate.toISOString().split('T')[0]);
                // docDateInput.attr("max", "");
                isPast = true;
            } else {
                isPast = false;
                docDateInput.attr("min", new Date().toISOString().split('T')[0]);
            }
            /*Date Validation*/
            if (isFeature && isPast) {
                docDateInput.removeAttr('min');
                docDateInput.removeAttr('max');
            }

            /*Reference from*/
        }

        /*Vendor drop down*/
        function initializeAutocomplete1(selector, type) {
            $(selector).autocomplete({
                minLength: 0,
                source: function(request, response) {
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: 'vendor_list'
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    label: item.company_name,
                                    code: item.vendor_code,
                                    addresses: item.addresses
                                };
                            }));
                        },
                        error: function(xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                select: function(event, ui) {
                    var $input = $(this);
                    var itemName = ui.item.value;
                    var itemId = ui.item.id;
                    var itemCode = ui.item.code;
                    $input.attr('data-name', itemName);
                    $input.val(itemName);
                    $("#vendor_id").val(itemId);
                    $("#vendor_code").val(itemCode);
                    vendorOnChange(itemId);
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $(this).attr('data-name', '');
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
        }
        initializeAutocomplete1("#vendor_name");

        // for component item code
        function initializeAutocomplete2(selector, type) {
            $(selector).autocomplete({
                source: function(request, response) {
                    let selectedAllItemIds = [];
                    $("#itemTable tbody [id*='row_']").each(function(index, item) {
                        if (Number($(item).find('[name*="item_id"]').val())) {
                            selectedAllItemIds.push(Number($(item).find('[name*="item_id"]').val()));
                        }
                    });
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: 'goods_item_list',
                            selectedAllItemIds: JSON.stringify(selectedAllItemIds)
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    label: `${item.item_name} (${item.item_code})`,
                                    code: item.item_code || '',
                                    item_id: item.id,
                                    item_name: item.item_name,
                                    is_inspection: item.is_inspection,
                                    uom_name: item.uom?.name,
                                    uom_id: item.uom_id,
                                    hsn_id: item.hsn?.id,
                                    hsn_code: item.hsn?.code,
                                    alternate_u_o_ms: item.alternate_u_o_ms,
                                    is_attr: item.item_attributes_count,
                                    is_asset: item.is_asset,
                                    asset_name: item.item_name,
                                    asset_category_id: item.asset_category_id,
                                    asset_category_name: item.asset_category?.name,
                                    brand_name: item.brand_name,
                                    model_no: item.model_no,
                                    estimated_life: item.expected_life,
                                    salvage_percentage: item.getSalvagePercentage ?? 0,
                                    procurement_type: 'BUY',
                                    is_batch_number: item.is_batch_no,
                                    is_expiry: item.is_expiry,
                                };
                            }));
                        },
                        error: function(xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    let $input = $(this);
                    let itemCode = ui.item.code;
                    let itemName = ui.item.value;
                    let itemN = ui.item.item_name;
                    let itemId = ui.item.item_id;
                    let uomId = ui.item.uom_id;
                    let uomName = ui.item.uom_name;
                    let hsnId = ui.item.hsn_id;
                    let hsnCode = ui.item.hsn_code;
                    let isInspection = ui.item.is_inspection;
                    let batchNumber = ui.item.is_batch_number;
                    let expiry = ui.item.is_expiry;
                    $input.attr('data-name', itemName);
                    $input.attr('data-code', itemCode);
                    $input.attr('data-id', itemId);
                    $input.val(itemCode);
                    let closestTr = $input.closest('tr');
                    closestTr.find('[name*=item_id]').val(itemId);
                    closestTr.find('[name*=item_code]').val(itemCode);
                    closestTr.find('[name*=item_name]').val(itemN);
                    closestTr.find('[name*=hsn_id]').val(hsnId);
                    closestTr.find('[name*=hsn_code]').val(hsnCode);
                    closestTr.find("td[id*='itemAttribute_']").html(defautAttrBtn);
                    let uomOption = `<option value=${uomId}>${uomName}</option>`;
                    if (ui.item?.alternate_u_o_ms) {
                        for (let alterItem of ui.item.alternate_u_o_ms) {
                            uomOption +=
                                `<option value="${alterItem.uom_id}" ${alterItem.is_purchasing ? 'selected' : ''}>${alterItem.uom?.name}</option>`;
                        }
                    }
                    closestTr.find('[name*=uom_id]').append(uomOption);
                    closestTr.find('.attributeBtn').trigger('click');
                    if (ui.item.is_asset === 1) {
                        const assetPayload = {
                            asset_id: null,
                            asset_name: ui.item.asset_name ?? '',
                            asset_category_id: ui.item.asset_category_id ?? null,
                            asset_category_name: ui.item.asset_category_name ?? '',
                            asset_code: null,
                            brand_name: ui.item.brand_name ?? '',
                            model_no: ui.item.model_no ?? '',
                            estimated_life: ui.item.estimated_life ?? '',
                            salvage_percentage: ui.item.salvage_percentage ?? 0,
                            salvage_value: ui.item.salvage_percentage ?? 0,
                            procurement_type: ui.item.procurement_type ?? null,
                            capitalization_date: new Date().toISOString().split('T')[0]
                        };


                        closestTr.find('[name*="[assetDetailData]"]').val(JSON.stringify(assetPayload));
                        closestTr.find('.assetDetailBtn')
                            .removeClass('d-none')
                            .attr('data-asset', JSON.stringify(assetPayload));
                    } else {
                        closestTr.find('[name*="[assetDetailData]"]').val('');
                        closestTr.find('.assetDetailBtn')
                            .addClass('d-none')
                            .removeAttr('data-asset');
                    }

                    closestTr.find('.addBatchBtn').attr('data-is-batch-number', batchNumber);
                    closestTr.find('.addBatchBtn').attr('data-is-expiry', expiry);
                    let price = 0;
                    let transactionType = 'collection';
                    let partyCountryId = $("#hidden_country_id").val();
                    let partyStateId = $("#hidden_state_id").val();
                    let rowCount = Number(closestTr.attr('data-index'));
                    let queryParams = new URLSearchParams({
                        price: price,
                        item_id: itemId,
                        transaction_type: transactionType,
                        party_country_id: partyCountryId,
                        party_state_id: partyStateId,
                        rowCount: rowCount
                    }).toString();
                    getItemDetail(closestTr);
                    let storeLocation = $('.header_store_id').val();
                    getSubStores(storeLocation, itemId);
                    setTimeout(() => {
                        if (ui.item.is_attr) {
                            $input.closest('tr').find('.attributeBtn').trigger('click');
                        } else {
                            $input.closest('tr').find('.attributeBtn').trigger('click');
                            $input.closest('tr').find('[name*="[order_qty]"]').focus();
                        }
                    }, 100);
                    initializeStationAutocomplete();
                    getItemCostPrice($input.closest('tr'));
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        // $('#itemId').val('');
                        $(this).attr('data-name', '');
                        $(this).attr('data-code', '');
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
        }
        initializeAutocomplete2(".comp_item_code");

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
                    text: 'Please select first item name.',
                    icon: 'error',
                });
                return false;
            }
        });

        /*For comp attr*/
        function getItemAttribute(itemId, rowCount, selectedAttr, tr) {
            let checkAttr = 0;
            if (currentProcessType && currentProcessType != null) {
                rowCount = tableRowCount;
                let isPo = $(tr).find('[name*="purchase_order_item_id"]').val() ? 1 : 0;
                let isJo = $(tr).find('[name*="job_order_item_id"]').val() ? 1 : 0;
                if ((!isPo) || (!isJo)) {
                    if ($(tr).find('td[id*="itemAttribute_"]').data('disabled')) {
                        checkAttr = 1;
                    }
                }
            }
            let mrn_detail_id = $(tr).find("input[name*='[mrn_detail_id]']").val() || '';
            let actionUrl = '{{ route('material-receipt.item.attr') }}' + '?item_id=' + itemId + '&mrn_detail_id=' +
                mrn_detail_id + `&rowCount=${rowCount}&selectedAttr=${selectedAttr}&checkAttr=${checkAttr}`;
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
                        initAttributeAutocomplete();
                    }
                });
            });
        }

        /*Display item detail*/
        $(document).on('change focus', '#itemTable tr input ', function(e) {
            let currentTr = e.target.closest('tr');
            getItemDetail(currentTr);
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
                store_id: $('.header_store_id').val(),
                sub_store_id: $('.sub_store').val(),
                remark: getVal("[name*='[remark]']"),
                uom_id: getVal("[name*='[uom_id]']"),
                qty: getVal("[name*='[order_qty]']"),
                headerId: getVal("[name*='[mrn_header_id]']"),
                detailId: getVal("[name*='[mrn_detail_id]']"),
                selectedAttr: JSON.stringify(selectedAttr),
                itemStoreData: JSON.parse(getVal("[id*='components_stores_data']") || "[]"),
                type: currentProcessType,
            };

            let actionUrl = '{{ route('material-receipt.get.itemdetail') }}?' + new URLSearchParams(data).toString();

            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        // Update the modal or display section
                        $("#itemDetailDisplay").html(data.data.html);
                        applyInspectionState();

                        var approvedStockLedger = data.data.checkApprovedQuantity;
                        if (approvedStockLedger) {
                            if ((approvedStockLedger['code'] == 200) && (approvedStockLedger['status'] ==
                                    'error')) {
                                let approved_stock = approvedStockLedger['approvedStock'];
                                let receipt_qty = $(currentTr).find("[name*='[order_qty]']").val() || '';
                                let rejQtyElement = $(currentTr).find(
                                    "[name*='[rejected_qty]']"); // Get the jQuery object, not the value
                                let rejQty = rejQtyElement.val() ||
                                    ''; // Get the value of the rejected_qty input
                                if (qty < approved_stock) {
                                    if (qtyElement.length > 0) { // Ensure the element was found
                                        qtyElement.val(
                                            receipt_qty); // Set the value of qtyElement (jQuery object)
                                        rejQtyElement.val(
                                            0.00); // Set the value of qtyElement (jQuery object)
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: "Accepted quantity input not found",
                                            icon: 'error',
                                        });
                                    }
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: "Accepted quantity is higher than the approved stock.",
                                        icon: 'error',
                                    });
                                }
                            }
                        }

                    }
                });
            });
        }

        $(document).on('click', 'td[id*="itemAttribute_"]', (e) => {
            let dataAttributes = $(e.target).attr('data-attributes');
            // dataAttributes = JSON.parse(dataAttributes);
            // dataAttributes.
        });

        function initializeAutocompleteTED(selector, idSelector, nameSelector, type, percentageVal) {
            $("#" + selector).autocomplete({
                source: function(request, response) {
                    let ids = [];
                    $('.modal.show').find("tbody tr").each(function(index, item) {
                        let tedId = $(item).find("input[name*='ted_']").val();
                        if (tedId) {
                            ids.push(tedId);
                        }
                    });
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: type,
                            ids: JSON.stringify(ids)
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    hsn_id: item.hsn_id,
                                    label: `${item.name}`,
                                    percentage: `${item.percentage}`,
                                };
                            }));
                        },
                        error: function(xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    var $input = $(this);
                    var itemId = ui.item.id;
                    var hsnId = ui?.item?.hsn_id;
                    var itemName = ui.item.label;
                    var itemPercentage = ui.item.percentage;

                    $input.val(itemName);
                    $("#" + idSelector).val(itemId).attr("data-hsn-id", hsnId);
                    $("#" + nameSelector).val(itemName);
                    $("#" + percentageVal).val(itemPercentage).trigger('keyup');
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $("#" + idSelector).val("").attr("data-hsn-id", "");
                        $("#" + nameSelector).val("");
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
        }

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

        function onPostVoucherOpen(type = "not_posted") {
            // resetPostVoucher();
            const apiURL = "{{ route('material-receipt.posting.get-history') }}";
            let urlType = 'view';
            let transactionType = 'history';

            $.ajax({
                url: apiURL + "?book_id=" + $("#book_id").val() + "&document_id=" +
                    "{{ isset($mrn) ? $mrn->id : '' }}" + "&type=" + urlType,
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
                            <td class="text-end">${voucherDetail.due_date ? moment(voucherDetail.due_date).format('D/M/Y') : ''}</td>
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

        setTimeout(() => {
            $("#itemTable .mrntableselectexcel tr").each(function(index, item) {
                let currentIndex = index + 1;
                setAttributesUIHelper(currentIndex, "#itemTable");
            });
        }, 100);
    </script>
@endsection

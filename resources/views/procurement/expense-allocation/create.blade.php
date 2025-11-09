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
    <form class="ajax-input-form" data-module="exp" method="POST" action="{{ route('exp-allocation.store') }}"
        data-redirect="/expense-allocation" enctype="multipart/form-data">
        <input type="hidden" name="tax_required" id="tax_required" class="tax_required" value="">
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
                                            <li class="breadcrumb-item active">Add New</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <input type="hidden" name="document_status" value="draft" id="document_status">
                                <button type="button" onClick="javascript: history.go(-1)"
                                    class="btn btn-secondary btn-sm mb-50 mb-sm-0">
                                    <i data-feather="arrow-left-circle"></i> Back
                                </button>
                                <button type="submit"
                                    class="btn btn-outline-primary btn-sm mb-50 mb-sm-0 submit-button distributeBtn"
                                    name="action" value="draft"><i data-feather='save'></i> Save as Draft</button>
                                <button type="submit" class="btn btn-primary btn-sm submit-button distributeBtn"
                                    name="action" value="submitted"><i data-feather="check-circle"></i> Submit</button>
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
                                            <div class="col-md-12">
                                                <div
                                                    class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                                                    <div>
                                                        <h4 class="card-title text-theme">Basic Information</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select book_id" id="book_id" name="book_id">
                                                            <!-- <option value="">Select</option> -->
                                                            @foreach ($books as $book)
                                                                <option value="{{ $book->id }}">
                                                                    {{ ucfirst($book->book_code) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="book_code" id="book_code"
                                                            class="book_code">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Expense No <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" name="document_number"
                                                            class="form-control document_number" id="document_number">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Expense Date <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="date" class="form-control"
                                                            value="{{ date('Y-m-d') }}" name="document_date">
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select store_id header_store_id"
                                                            id="header_store_id" name="header_store_id">
                                                            @foreach ($locations as $erpStore)
                                                                <option value="{{ $erpStore->id }}"
                                                                    {{ old('header_store_id', $selectedStoreId ?? '') == $erpStore->id ? 'selected' : '' }}>
                                                                    {{ ucfirst($erpStore->store_name) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1 reference_from d-none"
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
                                                <div class="row align-items-center mb-1" id="referenceNoDiv"
                                                    style="display: none;">
                                                    <div class="col-md-5">
                                                        <input type="hidden" name="reference_type"
                                                            class="form-control reference_type reference_type_input"
                                                            id="reference_type_input" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- <div class="row" id="general_section">
                                    <div class="col-md-12">
                                        <div class="card quation-card">
                                            <div class="card-header newheader">
                                                <div>
                                                    <h4 class="card-title">General Information</h4>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3" id="cost_center_div" style="display:none;">
                                                        <div class="mb-1">
                                                            <label class="form-label">Cost Center</label>
                                                            <select class="form-select cost_center" id="cost_center_id"
                                                                name="cost_center_id">
                                                                <!-- Options will be populated here by the AJAX request -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">
                                                                Supplier Invoice No.
                                                            </label>
                                                            <input type="text" name="supplier_invoice_no"
                                                                class="form-control bg-white supplier_invoice_no"
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
                                                                class="form-control bg-white gate-entry supplier_invoice_date"
                                                                id="datepicker3"
                                                                placeholder="Enter Supplier Invoice Date">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-md-12 " id = "dynamic_fields_section">
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
                                                    <a href="javascript:;" id="delete-po-items"
                                                        class="btn btn-sm btn-outline-danger me-50 delete-po-items">
                                                        <i data-feather="x-circle"></i>
                                                        Delete
                                                    </a>
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
                                                                        <th class="text-end">Qty</th>
                                                                        <th class="text-end">Rate</th>
                                                                        <th class="text-end">
                                                                            Value({{ $currency?->short_name }})</th>
                                                                        <th class="text-end">Po Value</th>
                                                                        <th>Allocation Type</th>
                                                                        <th width="225">Vendor</th>
                                                                        <th width="150">Po No.</th>
                                                                        <th width="150">Po Date</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="mrntableselectexcel poItemsTbody"
                                                                    id="poItemsTbody"></tbody>
                                                                <tfoot>
                                                                    <tr class="totalsubheadpodetail">
                                                                        <td colspan="5"></td>
                                                                        <td class="text-end total-po-qty"
                                                                            id="total-po-qty">0.00</td>
                                                                        <td colspan="1"></td>
                                                                        <td class="text-end total-po-old-value"
                                                                            id="total-po-old-value">0.00</td>
                                                                        <td class="text-end total-po-value"
                                                                            id="total-po-value">0.00</td>
                                                                        <td colspan="4"></td>
                                                                    </tr>
                                                                    <tr valign="top">
                                                                        <td colspan="13" rowspan="12">
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
                                                    <a href="javascript:;" id="delete-grn-items"
                                                        class="btn btn-sm btn-outline-danger me-50 delete-grn-items">
                                                        <i data-feather="x-circle"></i> Delete</a>
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
                                                                <th class="text-end">Qty</th>
                                                                <th class="text-end">Value({{ $currency?->short_name }})
                                                                <th class="text-end">Grn Value</th>
                                                                </th>
                                                                <th class="text-end">Weight</th>
                                                                <th class="text-end">Volume(CFT)</th>
                                                                <th width="200">Allocated Expense</th>
                                                                <th class="text-end">Landed Cost</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="mrntableselectexcel itemTable grnItemsTbody"
                                                            id="grnItemsTbody"></tbody>
                                                        <tfoot>
                                                            <tr class="totalsubheadgrndetail">
                                                                <td colspan="9"></td>
                                                                <td class="text-end total-grn-qty" id="total-grn-qty">0.00
                                                                </td>
                                                                <td class="text-end total-old-grn-value"
                                                                    id="total-old-grn-value">
                                                                    0.00</td>
                                                                <td class="text-end total-grn-value" id="total-grn-value">
                                                                    0.00</td>
                                                                <td class="text-end total-grn-weight"
                                                                    id="total-grn-weight">0.00</td>
                                                                <td class="text-end total-grn-volume"
                                                                    id="total-grn-volume">0.00</td>
                                                                <td class="total-allocated-cost"
                                                                    id="total-allocated-cost">0.00</td>
                                                                <td class="text-end total-landed-cost"
                                                                    id="total-landed-cost">0.00</td>
                                                            </tr>
                                                            <tr valign="top">
                                                                <td colspan="16" rowspan="12">
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
                                                            onchange = "addFiles(this,'main_expense_file_preview')"
                                                            multiple>
                                                        <span
                                                            class = "text-primary small">{{ __('message.attachment_caption') }}</span>
                                                    </div>
                                                </div>
                                                <div class = "col-md-6" style = "margin-top:19px;">
                                                    <div class = "row" id = "main_expense_file_preview">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-1">
                                                    <label class="form-label">Final Remarks</label>
                                                    <textarea type="text" rows="4" name="remark" class="form-control" placeholder="Enter Remarks here..."></textarea>
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
        {{-- Edit Address --}}
        <div class="modal fade" id="edit-address" tabindex="-1" aria-labelledby="shareProjectTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px">
            </div>
        </div>
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
        const selectedCostCenterId = "";
        currentProcessType = null;
        let tableRowCount = 0;
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

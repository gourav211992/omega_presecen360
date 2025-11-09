@extends('layouts.app')

@section('content')
        <!-- BEGIN: Content -->
   <form class="ajax-input-form" data-module="rca" method="POST" action="{{ route('rca.update', $rca->id) }}" data-redirect="{{ route('rca.index') }}" id="rcaForm" enctype="multipart/form-data">
     @csrf
     @method('PUT')

        <div class="app-content content">
            <div class="content-overlay"></div>
            <div class="header-navbar-shadow"></div>
            <div class="content-wrapper container-xxl p-0">

                {{-- Breadcrumb & Header --}}
                <div class="content-header pocreate-sticky">
                    <div class="row">
                         @include('layouts.partials.breadcrumb-add-edit', [
                            'title' => 'Root Cause Analysis',
                            'menu' => 'Home',
                            'menu_url' => url('home'),
                            'sub_menu' => 'Edit'
                        ])
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <input type="hidden" id="document_status" name="document_status">
                                <button type="button" onClick="javascript: history.go(-1)" class="btn btn-secondary btn-sm mb-50 mb-sm-0">
                                    <i data-feather="arrow-left-circle"></i> Back
                                </button>

                                @if($buttons['draft'])
                                    <button type="submit" class="btn btn-outline-primary btn-sm mb-50 mb-sm-0 submit-button" name="action" value="draft">
                                        <i data-feather='save'></i> Save as Draft
                                    </button>
                                @endif

                                @if($buttons['submit'])
                                    <button type="submit" class="btn btn-primary btn-sm submit-button" name="action" value="submitted">
                                        <i data-feather="check-circle"></i> Submit
                                    </button>
                                @endif

                                @if($buttons['approve'])
                                    <button type="button" id="reject-button" class="btn btn-danger btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg> Reject
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" id="approved-button" name="action" value="approved">
                                        <i data-feather="check-circle"></i> Approve
                                    </button>
                                @endif

                                @if($buttons['amend'] && intval(request('amendment') ?? 0))
                                    <button type="button" class="btn btn-primary btn-sm" id="amendmentBtn">
                                        <i data-feather="check-circle"></i> Submit
                                    </button>
                                @elseif($buttons['amend'])
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#amendmentconfirm" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                        <i data-feather='edit'></i> Amendment
                                    </button>
                                @endif

                                @if($buttons['revoke'])
                                    <button id="revokeButton" type="button" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                        <i data-feather='rotate-ccw'></i> Revoke
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Content Body --}}
                <div class="content-body">
                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">

                                {{-- Basic Information Card --}}
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                       <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader">
                                                        <h4 class="card-title text-theme">Root Cause Analysis Information</h4>
                                                        <p class="card-text">Fill the RCA details</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-sm-end">
                                                    <span class="badge rounded-pill badge-light-secondary forminnerstatus">
                                                        Status : <span class="{{ $docStatusClass }}">{{ $rca->display_status }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                       <div class="row">
                                         <div class="col-md-8">
                                                   <div class="basic-information">
                                                    {{-- Series --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Series <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <select class="form-select" id="book_id" name="book_id" disabled>
                                                                @foreach($books as $book)
                                                                    <option value="{{ $book->id }}" 
                                                                        {{ isset($rca) && $rca->book_id == $book->id ? 'selected' : '' }}>
                                                                        {{ ucfirst($book->book_code) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <input type="hidden" name="book_code" value="{{ $rca->book_code ?? '' }}" id="book_code">
                                                        </div>
                                                    </div>

                                                    {{-- Document No --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Document No <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" name="document_number" id="document_number" 
                                                                value="{{ $rca->document_number ?? '' }}" class="form-control" readonly>
                                                        </div>
                                                    </div>

                                                    {{-- Document Date --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Document Date <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="date" name="document_date" class="form-control" 
                                                                value="{{ $rca->document_date ?? date('Y-m-d') }}" readonly>
                                                        </div>
                                                    </div>

                                                    {{-- Location --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Location <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <select class="form-select" id="store_id" name="store_id" disabled>
                                                                @foreach($locations as $location)
                                                                    <option value="{{ $location->id }}" 
                                                                        {{ isset($rca) && $rca->store_id == $location->id ? 'selected' : '' }}>
                                                                        {{ $location->store_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <input type="hidden" id="store_name" name="store_name" value="{{ $rca->store_name ?? '' }}">
                                                        </div>
                                                    </div>

                                                    {{-- Discrepancy Type --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Discrepancy Type <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <input type="text" class="form-control" value="{{ $rca->discrepancy_type ?? '' }}" readonly>
                                                        </div>
                                                    </div>
                                                </div>
                                               </div>
                                         {{-- History Code --}}
                                        @include('partials.approval-history', ['document_status' => $rca->document_status,'revision_number' => $rca->revision_number ?? 0])
                                      </div>
                                    </div>
                                </div>
                               {{-- General Information Card --}}
                               <div class="row">
                                    <div class="col-md-12">
                                        <div class="card quation-card">
                                            <div class="card-header newheader">
                                                <h4 class="card-title">General Information</h4>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">

                                                    {{-- Customer Name --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->customer->company_name ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Phone No --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Phone No <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->customer_phone_no ?? '' }}" disabled>
                                                    </div>

                                                    {{-- FUR ID --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">FUR ID <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->fur_id ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Unloading Date --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Unloading Date <span class="text-danger">*</span></label>
                                                        <input type="date" class="form-control" value="{{ $rca->unloading_date ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Trip No --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Trip No <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->trip_no ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Vehicle No --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Vehicle No <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->vehicle_no ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Champ Name --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Champ Name <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->champ_name ?? '' }}" disabled>
                                                    </div>

                                                    {{-- Total Items --}}
                                                    <div class="col-md-3 mb-1">
                                                        <label class="form-label">Total Items <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" value="{{ $rca->items_count ?? 0 }}" disabled>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                              </div>
                               {{-- Item Detail Card --}}
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader">
                                                        <h4 class="card-title text-theme">Item Detail</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Item Table --}}
                                        <div class="table-responsive pomrnheadtffotsticky">
                                            <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad" id="itemDetailTable">
                                                <thead>
                                                    <tr>
                                                        <th width="62" class="customernewsection-form">S.No</th>
                                                        <th>Item Code</th>
                                                        <th>Item Name</th>
                                                        <th>Attributes</th>
                                                        <th>UOM</th>
                                                        <th>UID</th>
                                                        <th>Scheduled Qty</th>
                                                        <th>Missing Qty</th>
                                                        <th>RGR Detail</th>
                                                        <th>RCA</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="pickupItemBody">
                                                    @include('rca.partials.item-row')
                                                </tbody>
                                                <tfoot>
                                                    <tr valign="top">
                                                        <td colspan="10">
                                                            <table class="table border">
                                                                <tr>
                                                                    <td class="p-0">
                                                                        <h6 class="text-dark mb-0 bg-light-primary py-1 px-50"><strong>Item Details</strong></h6>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="poprod-decpt" id="item_detail_name">
                                                                        <span class="poitemtxt mw-100"><strong>Name</strong>:</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="poprod-decpt" id="item_detail_hsn">
                                                                        <span class="badge rounded-pill badge-light-primary"><strong>HSN</strong>:</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="poprod-decpt" id="item_detail_uom_qty">
                                                                        <span class="badge rounded-pill badge-light-primary"><strong>Inv. UOM</strong>:</span>
                                                                        <span class="badge rounded-pill badge-light-primary"><strong>Qty.</strong>:</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="poprod-decpt" id="item_detail_remarks">
                                                                        <span class="badge rounded-pill badge-light-secondary"><strong>Remarks</strong>:</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        {{-- Upload Document & Remarks --}}
                                        <div class="row mt-2">
                                        
                                             <div class="col-md-12">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-1">
                                                            <label class="form-label">Upload Document</label>
                                                            <input type="file" name="attachment[]" class="form-control"
                                                                onchange="addFiles(this,'main_rca_file_preview')" multiple>
                                                            <span class="text-primary small">{{ __("message.attachment_caption") }}</span>
                                                        </div>
                                                    </div>
                                                     @include('partials.document-preview',['documents' => $rca->getDocuments(), 'document_status' => $rca->document_status,'elementKey' => 'main_rca_file_preview'])
                                                </div>
                                            </div>

                                            <div class="col-md-12 mt-2">
                                                <div class="mb-1">
                                                    <label class="form-label">Final Remarks</label>
                                                    <textarea maxlength="250" name="remarks" rows="4" class="form-control" placeholder="Enter Remarks here...">{{ $rca->remarks ?? '' }}</textarea>
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
    </form>

    <!-- END: Content -->
{{-- Approval Modal --}}
@include('rca.partials.approve-modal', ['id' => $rca->id])
@include('rca.partials.rgr-defect-modal')
@include('rca.partials.add-rca-modal')
@endsection

@section('scripts')
<!-- ==================== Common Attribute & File Uploader JS ==================== -->
<script type="text/javascript" src="{{ asset('assets/js/modules/common-attr-ui.js') }}"></script>
<script type="text/javascript" src="{{asset('app-assets/js/file-uploader.js')}}"></script>
<!-- ==================== Repair Order & Document JS ==================== -->
<script type="text/javascript">
$(function() {

    // ------------------- Book Change: Fetch Document Number & Parameters -------------------
    $(document).on('change', '#book_id', function(e) {
        let bookId = e.target.value;
        if (bookId) {
            getDocNumberByBookId(bookId);
        } else {
            $("#document_number").val('');
            $("#book_code").val('');
            $("#document_number").attr('readonly', false);
        }
    });

    function getDocNumberByBookId(bookId) {
        let document_date = $("[name='document_date']").val();
        let actionUrl = '{{ route("book.get.doc_no_and_parameters") }}' + '?book_id=' + bookId + '&document_date=' + document_date;

        fetch(actionUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status == 200) {
                    $("#book_code").val(data.data.book_code);
                    $("#document_number").val(data.data.doc.document_number || '');
                    $("#document_number").attr('readonly', data.data.doc.type !== 'Manually');

                    setServiceParameters(data.data.parameters);
                } else if (data.status == 404) {
                    $("#book_code").val('');
                    $("#document_number").val('');
                    let docDateInput = $("[name='document_date']");
                    docDateInput.removeAttr('min max').val(new Date().toISOString().split('T')[0]);
                    alert(data.message);
                }
            });
    }

    function setServiceParameters(parameters) {
        let docDateInput = $("[name='document_date']");
        let today = new Date().toISOString().split('T')[0];

        if (parameters.future_date_allowed?.includes('yes')) {
            docDateInput.attr('min', today);
        } else {
            docDateInput.removeAttr('min');
        }

        if (parameters.back_date_allowed?.includes('yes')) {
            docDateInput.removeAttr('max');
        } else {
            docDateInput.attr('max', today);
        }

        // Reference and Add New Item Visibility
        let reference_from_service = parameters.reference_from_service || [];
        if (reference_from_service.includes('{{ \App\Helpers\ConstantHelper::PDS_SERVICE_ALIAS }}')) {
            $("#reference_from").removeClass('d-none');
        } else {
            $("#reference_from").addClass('d-none');
        }

        if (reference_from_service.includes('d')) {
            $("#addNewItemBtn").removeClass('d-none');
        } else {
            $("#addNewItemBtn").addClass('d-none');
        }
    }
    // ------------------- Update Item Details on Row Click -------------------
    function updateItemDetails(row) {
        let itemName = row.find("td:nth-child(3)").text().trim();
        let hsnCode = row.find("input[name*='[hsn_code]']").val();
        let invUom = row.find("input[name*='[uom_code]']").val();
        let qty = row.find("input[name*='[scheduled_qty]']").val();
        let remarks = row.find("input[name*='[remark]']").val();

        $("#item_detail_name .poitemtxt").html("<strong>Name</strong>: " + (itemName || '-'));
        $("#item_detail_hsn .badge").html("<strong>HSN</strong>: " + (hsnCode || '-'));
        $("#item_detail_uom_qty").html(
            `<span class="badge rounded-pill badge-light-primary"><strong>Inv. UOM</strong>: ${invUom || '-'}</span>
             <span class="badge rounded-pill badge-light-primary"><strong>Qty.</strong>: ${qty || '-'}</span>`
        );
        $("#item_detail_remarks .badge").html("<strong>Remarks</strong>: " + (remarks || '-'));
    }

    // Initialize details with last item
    let itemRows = $("#rcaItemBody .item_detail_row");
    if (itemRows.length > 0) {
        updateItemDetails(itemRows.last());
    }

    $(document).on("click", "#rcaItemBody .item_detail_row", function() {
        updateItemDetails($(this));
        $('html, body').animate({scrollTop: $("#itemDetailTable").offset().top - 100}, 400);
    });

    // ------------------- Revoke Document -------------------
    $(document).on('click', '#revokeButton', function() {
        let actionUrl = '{{ route("rca.revoke") }}' + '?id={{ $rca->id }}';
        fetch(actionUrl)
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    title: data.status === 'error' ? 'Error!' : 'Success!',
                    text: data.message,
                    icon: data.status === 'error' ? 'error' : 'success'
                });
                location.reload();
            });
    });

    // ------------------- Approve / Reject Modal -------------------
    $(document).on('click', '#approved-button', function() {
        $("#approveModal #popupTitle").text("Approve Application");
        $("#approveModal #action_type").val('approve');
        $("#approveModal").modal('show');
    });

    $(document).on('click', '#reject-button', function() {
        $("#approveModal #popupTitle").text("Reject Application");
        $("#approveModal #action_type").val('reject');
        $("#approveModal").modal('show');
    });

});
</script>

<!-- ==================== RGR Modal Dynamic JS ==================== -->
<script>
$(document).ready(function() {
     $(document).on('click', '.open-rgr-modal', function () {
        const segregationData = $(this).data('rgr-segregation') || {};
        const images = $(this).data('rgr-images') || [];

        $('#chkPacking').prop('checked', segregationData.packing_status == 1);
        $('#chkLabel').prop('checked', segregationData.label_status == 1);
        $('#chkDelivery').prop('checked', segregationData.delivery_cancel == 1);
        $('#chkWrongProduct').prop('checked', !!segregationData.new_item_id);

        $('#modalSegregationCategory').text(segregationData.segregation_category || '');
        $('#modalDefectType').text(segregationData.defect_type || '');
        $('#modalDamageNature').text(segregationData.damage_nature || '');
        $('#modalDefectRemarks').text(segregationData.remarks || '');

        $('#modalNewItemCode').text(segregationData.new_item_code || '');
        $('#modalNewItemName').text(segregationData.new_item_name || '');
        $('#modalNewItemId').val(segregationData.new_item_id || ''); 
        $('#SegregationId').val(segregationData.id || ''); 

        const attrContainer = $('#modalNewItemAttributes');
        attrContainer.empty();

        let attributes = segregationData.new_item_attributes;

        if (typeof attributes === 'string' && attributes.trim() !== '') {
            try {
                attributes = JSON.parse(attributes);
            } catch {
                attributes = [];
            }
        }

       if (Array.isArray(attributes) && attributes.length > 0) {
            const formattedAttributes = attributes
                .filter(attr => (attr.attribute_value || attr.attr_value)) 
                .map(attr => {
                    const label = attr.attribute_name || attr.attr_name || '';
                    const value = attr.attribute_value || attr.attr_value || '';
                    return `${label}: ${value}`;
                })
                .join(', ');

            if (formattedAttributes.trim() !== '') {
                attrContainer.html(`<span class="text-dark">${formattedAttributes}</span>`);
            } else {
                attrContainer.html(''); 
            }
        } else {
            attrContainer.html(''); 
        }

        // ✅ Handle Images
        const imgContainer = $('#modalDefectImages');
        imgContainer.empty();

        if (Array.isArray(images) && images.length > 0) {
            images.forEach(img => {
                imgContainer.append(`
                    <a href="${img}" target="_blank">
                        <img src="${img}" class="rounded border" style="width:70px; height:70px; object-fit:cover;">
                    </a>
                `);
            });
        } else {
            imgContainer.append(`<span class="text-muted">No images</span>`);
        }

        $('#attribute').modal('show');
   });

    // Open RCA modal and populate data
    $(document).on('click', '.open-rca-modal', function () {
        const $modal = $($(this).data('bs-target'));
        const itemId = $(this).data('item-id');
        $modal.find('#rca_item_id').val(itemId);
        $modal.find('#rca_remark').val($(this).data('rca-remarks') || '');

        let images = $(this).data('rca-images') || [];
        if (typeof images === 'string') {
            try { images = JSON.parse(images); } catch(e) { images = []; }
        }

        const $preview = $modal.find('#rca_item_preview');
        const $deletedContainer = $modal.find('#deleted_media_container');

        $preview.empty();
        $deletedContainer.empty();

        images.forEach(img => {
            const fileUrl = img.file_name ? `/storage/${img.file_name}` : (img.url || '');
            if(fileUrl) {
                $preview.append(`
                    <div class="image-uplodasection d-inline-block me-1 mb-1 position-relative" data-id="${img.id || ''}">
                        <img src="${fileUrl}" style="width:80px;height:80px;object-fit:contain;object-position:center;" />
                        <div class="delete-img text-danger position-absolute top-0 end-0" style="cursor:pointer;">
                            <i data-feather="x"></i>
                        </div>
                    </div>
                `);
            }
        });

        if (window.feather) feather.replace();
    });

// Delete image
$(document).on('click', '.delete-img', function (e) {
    e.preventDefault();
    const $imgContainer = $(this).closest('.image-uplodasection');
    const fileId = $imgContainer.data('id'); 
    const $modal = $(this).closest('.modal');

    if (fileId) {
        let $input = $modal.find('input[name="deletedMediaIds"]');
        if ($input.length === 0) {
            $modal.find('#deleted_media_container').append(`
                <input type="hidden" name="deletedMediaIds" value="${fileId}">
            `);
        } else {
            const currentVal = $input.val();
            const newVal = currentVal ? currentVal + ',' + fileId : fileId;
            $input.val(newVal);
        }
    }

    $imgContainer.remove();
});

// Preview newly selected images
window.previewRcaImages = function(input, previewId) {
    const $preview = $('#' + previewId);
    if (input.files && input.files.length) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                $preview.append(`
                    <div class="image-uplodasection d-inline-block me-1 mb-1 position-relative">
                        <img src="${e.target.result}" style="width:80px;height:80px;object-fit:contain;object-position:center;" />
                        <div class="delete-img text-danger position-absolute top-0 end-0" style="cursor:pointer;">
                            <i data-feather="x"></i>
                        </div>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });
    }
};

   // AJAX submit
    $(document).on('submit', '#rcaForm', function(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                $('#addRcaModal').modal('hide'); 
                Swal.fire({
                    icon: 'success',
                    title: 'Saved',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    location.reload();
                }, 1600);
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Something went wrong!',
                });
            }
        });
    });
});
</script>
@endsection



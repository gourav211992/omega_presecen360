@extends('layouts.app')

@section('styles')
<style>
    /* Expandable input field styles */
    .expandable-input-container {
        position: relative;
    }

    .expandable-input {
        min-height: 38px;
        height: auto;
        resize: none;
        overflow: hidden;
        border: 1px solid #d8d6de;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-family: inherit;
        font-size: 0.875rem;
        line-height: 1.4;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .expandable-input:focus {
        border-color: #7367f0;
        box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.25);
        outline: 0;
    }

    .expandable-input.is-invalid {
        border-color: #ea5455;
    }

    /* Cursor fixes */
    .card-header,
    .card-body,
    .form-label,
    .form-select,
    .form-control {
        cursor: default !important;
    }

    .form-select,
    .form-control,
    input[type="text"],
    input[type="date"],
    textarea {
        cursor: text !important;
    }

    .file-upload-preview {
        cursor: pointer !important;
    }
</style>
@endsection

@section('content')
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
        <div class="content-header pocreate-sticky">
            <div class="row">
                <div class="content-header-left col-md-6 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Edit Defect Notification</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('/') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('defect-notification.index') }}">Defect Notifications</a></li>
                                    <li class="breadcrumb-item active">Edit</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <a href="{{ route('defect-notification.index') }}">
                            <button class="btn btn-secondary btn-sm"><i data-feather="arrow-left-circle"></i> Back</button>
                        </a>
                        @if ($defectNotification->document_status == 'draft' || ($buttons['amend'] && request('amendment') == 1))
                            <button class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" type="button" id="save-draft-btn">
                                <i data-feather="save"></i> Save as Draft
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="submit-btn">
                                <i data-feather="check-circle"></i> Submit
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="content-body">
            <form id="defect-notification-form" method="POST" action="{{ route('defect-notification.update', $defectNotification->id) }}" enctype="multipart/form-data" novalidate>
                @csrf
                @method('PUT')

                <input type="hidden" name="document_status" id="document_status" value="{{ $defectNotification->document_status }}">
                <input type="hidden" name="book_code" id="book_code_input" value="{{ $defectNotification->book_code }}">
                <input type="hidden" name="doc_number_type" id="doc_number_type" value="{{ $defectNotification->doc_number_type }}">
                <input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern" value="{{ $defectNotification->doc_reset_pattern }}">
                <input type="hidden" name="doc_prefix" id="doc_prefix" value="{{ $defectNotification->doc_prefix }}">
                <input type="hidden" name="doc_suffix" id="doc_suffix" value="{{ $defectNotification->doc_suffix }}">
                <input type="hidden" name="doc_no" id="doc_no" value="{{ $defectNotification->doc_no }}">

                {{-- ======================== --}}
                {{-- BASIC INFORMATION SECTION --}}
                {{-- ======================== --}}
                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body customernewsection-form">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                                                <div>
                                                    <h4 class="card-title text-theme">Basic Information</h4>
                                                    <p class="card-text">Update the details</p>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge rounded-pill {{ App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$defectNotification->document_status] ?? '' }} forminnerstatus">
                                                        <span class="text-dark">Status:</span>
                                                        <span class="{{ App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS[$defectNotification->document_status] ?? '' }}">
                                                            {{ ucfirst($defectNotification->document_status) }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-8">
                                            {{-- Series --}}
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"><label class="form-label">Series <span class="text-danger">*</span></label></div>
                                                <div class="col-md-5">
                                                    <select class="form-select" id="book_id" name="book_id" disabled required>
                                                        @foreach($series ?? [] as $book)
                                                            <option value="{{ $book->id }}" {{ $book->id == $defectNotification->book_id ? 'selected' : '' }}>{{ $book->book_code }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Doc No --}}
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"><label class="form-label">Doc No <span class="text-danger">*</span></label></div>
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" id="document_number" name="document_number" value="{{ $defectNotification->document_number }}" disabled required>
                                                </div>
                                            </div>

                                            {{-- Doc Date --}}
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"><label class="form-label">Doc Date <span class="text-danger">*</span></label></div>
                                                <div class="col-md-5">
                                                    <input type="date" value="{{ $defectNotification->document_date ? \Carbon\Carbon::parse($defectNotification->document_date)->format('Y-m-d') : date('Y-m-d') }}" class="form-control" id="document_date" name="document_date" required>
                                                </div>
                                            </div>

                                            {{-- Location --}}
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"><label class="form-label">Location <span class="text-danger">*</span></label></div>
                                                <div class="col-md-5">
                                                    <select class="form-select" name="location_id" id="location_id" required>
                                                        <option value="">Select Location</option>
                                                        @foreach($locations ?? [] as $location)
                                                            <option value="{{ $location->id }}" {{ $location->id == $defectNotification->location_id ? 'selected' : '' }}>{{ $location->store_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        @include('partials.approval-history', [
                                            'document_status' => $defectNotification->document_status,
                                            'revision_number' => $defectNotification->revision_number,
                                            'approvalHistory' => $approvalHistory
                                        ])
                                    </div>
                                </div>
                            </div>

                            {{-- ===================== --}}
                            {{-- EQUIPMENT DETAILS --}}
                            {{-- ===================== --}}
                            <div class="card quation-card">
                                <div class="card-header newheader">
                                    <h4 class="card-title">Equipment Details</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        {{-- Category --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Category <span class="text-danger">*</span></label>
                                            <select class="form-select" name="category_id" id="category_id" required>
                                                <option value="">Select Category</option>
                                                @foreach($categories ?? [] as $category)
                                                    <option value="{{ $category->id }}" {{ $category->id == $defectNotification->category_id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Equipment --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Equipment <span class="text-danger">*</span></label>
                                            <select class="form-select" name="equipment_id" id="equipment_id" required>
                                                <option value="">Select Equipment</option>
                                                @foreach($equipments ?? [] as $equipment)
                                                    <option value="{{ $equipment->id }}" {{ $equipment->id == $defectNotification->equipment_id ? 'selected' : '' }}>{{ $equipment->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Defect Type --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Defect Type <span class="text-danger">*</span></label>
                                            <select class="form-select" name="defect_type_id" id="defect_type_id" required>
                                                <option value="">Select Defect Type</option>
                                                @foreach($defectTypes ?? [] as $defectType)
                                                    <option value="{{ $defectType->id }}" {{ $defectType->id == $defectNotification->defect_type_id ? 'selected' : '' }}>{{ $defectType->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Problem --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Problem <span class="text-danger">*</span></label>
                                            <textarea name="problem" class="form-control expandable-input" required>{{ $defectNotification->problem ?? '' }}</textarea>
                                        </div>

                                        {{-- Priority --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                                            <select class="form-select" name="priority" required>
                                                <option value="">Select</option>
                                                @foreach(['High','Medium','Low','Critical'] as $p)
                                                    <option value="{{ $p }}" {{ $defectNotification->priority == $p ? 'selected' : '' }}>{{ $p }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Report Date & Time --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label">Report Date & Time <span class="text-danger">*</span></label>
                                            <input type="text" name="report_date_time" value="{{ $defectNotification->report_date_time ? \Carbon\Carbon::parse($defectNotification->report_date_time)->format('d-m-Y H:i') : '' }}" class="form-control" placeholder="dd-mm-yyyy HH:mm" required>
                                        </div>

                                        {{-- Attachment --}}
                                        <div class="col-md-3 mb-1">
                                            <label class="form-label"><i data-feather="paperclip"></i> Attachment</label>
                                            <div class="d-flex align-items-center">
                                                <input type="file" name="attachment" id="attachment" class="form-control" accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf" />
                                                @if($defectNotification->attachment)
                                                    <div class="file-upload-preview ms-2" onclick="window.open('{{ asset('storage/' . $defectNotification->attachment) }}','_blank')">
                                                        <i data-feather="file-text"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <span class="text-primary small">{{ __("message.attachment_caption") }}</span>
                                        </div>

                                        {{-- Observations --}}
                                        <div class="col-md-12 mb-1">
                                            <label class="form-label">Detailed Observations</label>
                                            <textarea name="detailed_oberservation" class="form-control expandable-input">{{ $defectNotification->detailed_oberservation ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/js/modules/common-attr-ui.js') }}"></script>

<script>
const isAmendmentMode = {{ (request('amendment') == 1) ? 'true' : 'false' }};
window.allowFormSubmission = false; // default block

console.log('Page loaded - isAmendmentMode:', isAmendmentMode);
console.log('Buttons amend:', {{ $buttons['amend'] ? 'true' : 'false' }});

$(document).ready(function () {
    console.log('DOM Ready - Amendment modal exists:', $('#amendmentModal').length > 0);

    // ✅ Prevent Enter key from submitting the form except inside textarea
    $('#defect-notification-form').on('keypress', function(e) {
        if (e.which === 13 && !$(e.target).is('textarea, button, select')) {
            e.preventDefault();
            console.log('🛑 Prevented Enter key submission');
            return false;
        }
    });

    // ✅ Initialize Feather icons
    if (feather) {
        feather.replace({ width: 14, height: 14 });
    }

    // ✅ Guarded form submission
    $('#defect-notification-form').on('submit', function(e) {
        console.log('Form submission attempt detected');
        if (!window.allowFormSubmission) {
            e.preventDefault();
            e.stopPropagation();
            console.warn('❌ Blocked unauthorized submit');
            return false;
        }
        console.log('✅ Allowed form submission');
    });

    // ✅ Prevent unwanted clicks inside card
    $('.card').on('click', function(e) {
        if (!$(e.target).is('input, select, textarea, button, a, .file-upload-preview, .file-upload-preview *')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Prevented unwanted click submission');
        }
    });
});

// ===============================
// 🔹 Book change handler
// ===============================
$('#book_id').on('change', function () {
    let currentDate = new Date().toISOString().split('T')[0];
    let document_date = $('#document_date').val();
    let bookId = $('#book_id').val();
    let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId +
        "&document_date=" + document_date;
    fetch(actionUrl).then(response => {
        return response.json().then(data => {
            if (data.status == 200) {
                $("#book_code_input").val(data.data.book_code);
                if (!data.data.doc.document_number) {
                    $("#document_number").val('');
                    $('#doc_number_type').val('');
                    $('#doc_reset_pattern').val('');
                    $('#doc_prefix').val('');
                    $('#doc_suffix').val('');
                    $('#doc_no').val('');
                } else {
                    $("#document_number").val(data.data.doc.document_number);
                    $('#doc_number_type').val(data.data.doc.type);
                    $('#doc_reset_pattern').val(data.data.doc.reset_pattern);
                    $('#doc_prefix').val(data.data.doc.prefix);
                    $('#doc_suffix').val(data.data.doc.suffix);
                    $('#doc_no').val(data.data.doc.doc_no);
                }
                if (data.data.doc.type == 'Manually') {
                    $("#document_number").attr('readonly', false);
                } else {
                    $("#document_number").attr('readonly', true);
                }
            }
            if (data.status == 404) {
                $("#document_number").val('');
                $('#doc_number_type').val('');
                $('#doc_reset_pattern').val('');
                $('#doc_prefix').val('');
                $('#doc_suffix').val('');
                $('#doc_no').val('');
                showToast('error', data.message);
            }
        });
    });
});

// ===============================
// 🔹 File validation
// ===============================
function validateFile(input) {
    const file = input.files[0];
    if (!file) return true;

    const maxSize = 5 * 1024 * 1024;
    const allowedExtensions = ['png', 'jpeg', 'jpg', 'xls', 'xlsx', 'docx', 'pdf'];
    const ext = file.name.split('.').pop().toLowerCase();

    if (file.size > maxSize) {
        Swal.fire({
            icon: 'error',
            title: 'File Too Large!',
            text: 'Maximum allowed size is 5MB.',
        });
        input.value = '';
        return false;
    }

    if (!allowedExtensions.includes(ext)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid File!',
            text: 'Only PNG, JPG, XLS, XLSX, DOCX, PDF allowed.',
        });
        input.value = '';
        return false;
    }

    return true;
}

$(document).on('change', '#attachment, #amendmentModal [name="amend_attachment"]', function () {
    validateFile(this);
});

// ===============================
// 🔹 Save as Draft
// ===============================
$(document).on('click', '#save-draft-btn', function () {
    $('#document_status').val('draft');
    window.allowFormSubmission = true;
    $('.preloader').show();

    if (isAmendmentMode) {
        $('#amendmentModal').modal('show');
    } else {
        $('#defect-notification-form').trigger('submit');
    }
});

// ===============================
// 🔹 Submit Button
// ===============================
$(document).on('click', '#submit-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    $('#document_status').val('submitted');
    if (isAmendmentMode) {
        $('#amendmentModal').modal('show');
    } else {
        $('.preloader').show();
        window.allowFormSubmission = true;
        $('#defect-notification-form').trigger('submit');
    }
});

// ===============================
// 🔹 Amendment Modal Submit
// ===============================
$(document).on('click', '#amendmentBtnSubmit', function (e) {
    e.preventDefault();
    let remark = $('[name="amend_remarks"]').val();

    if (!remark) {
        $('#amendRemarkError').removeClass('d-none');
        return;
    }

    $('#amendRemarkError').addClass('d-none');
    $('#amendmentModal').modal('hide');
    $('#document_status').val('submitted');
    window.allowFormSubmission = true;

    $('<input>').attr({ type: 'hidden', name: 'action_type', value: 'amendment' }).appendTo('#defect-notification-form');
    $('<input>').attr({ type: 'hidden', name: 'amend_remarks', value: remark }).appendTo('#defect-notification-form');

    const fileInput = $("#amendmentModal").find('[name="amend_attachment"]')[0];
    if (fileInput && fileInput.files.length > 0) {
        if (!validateFile(fileInput)) return;
        const formData = new FormData($('#defect-notification-form')[0]);
        formData.append('amend_attachment', fileInput.files[0]);

        $.ajax({
            url: $('#defect-notification-form').attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                $('.preloader').hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Amendment Submitted!',
                    text: 'Your amendment has been submitted successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = "{{ route('defect-notification.show', $defectNotification->id) }}";
                });
            },
            error: function () {
                $('.preloader').hide();
                Swal.fire({
                    title: 'Error!',
                    text: 'Error submitting amendment. Please try again.',
                    icon: 'error',
                });
            }
        });
    } else {
        $('.preloader').show();
        $('#defect-notification-form').trigger('submit');
    }
});

// ===============================
// 🔹 Toast helper
// ===============================
function showToast(icon, title) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });
    Toast.fire({ icon, title });
}

@if (session('success'))
    $('.preloader').hide();
    showToast('success', "{{ session('success') }}");
@endif

@if (session('error'))
    $('.preloader').hide();
    showToast('error', "{{ session('error') }}");
@endif

@if ($errors->any())
    $('.preloader').hide();
    showToast('error', "@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach");
@endif

// ===============================
// 🔹 Auto-expand textarea
// ===============================
function autoExpand(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}
$(document).on('input focus paste', '.expandable-input', function () {
    const t = this;
    setTimeout(() => autoExpand(t), 10);
});
$('.expandable-input').each(function () { autoExpand(this); });
</script>

<!-- 🔹 Amendment Modal -->
<div class="modal fade" id="amendmentModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <input type="hidden" name="action_type" value="{{ $buttons['amend'] && request('amendment')==1 ? 'amendment':'submit' }}" id="action_type">
            <input type="hidden" name="id" value="{{ $defectNotification->id }}">
            <div class="modal-header">
                <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">Amendment Application</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="row mt-1">
                    <div class="col-md-12">
                        <div class="mb-2">
                            <label class="form-label">Remarks <span class="text-danger">*</span></label>
                            <textarea maxlength="250" name="amend_remarks" class="form-control" placeholder="Please provide reason for amendment..."></textarea>
                            <span id="amendRemarkError" class="ajax-validation-error-span form-label text-danger d-none" style="font-size:12px" role="alert">*Required</span>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name="amend_attachment" class="form-control" accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf" />
                            <span class="text-primary small">{{ __("message.attachment_caption") }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="reset" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                <button type="button" id="amendmentBtnSubmit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection




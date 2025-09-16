@php
    use App\Helpers\ConstantHelper;
@endphp
@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" type="text/css" href="{{ url('/app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
    <link rel="stylesheet" href="{{ url('/app-assets/js/jquery-ui.css') }}">
    <style>
        .badge-light-primary span {
            font-weight: bold;
            /* Makes the INR text bold */
            color: #6B12B7;
            /* Sets the text color to blue (you can change this to any color) */
        }
    </style>
@endsection

@section('content')
      @php
        $unauthorizedMonths = [];
        foreach ($fy_months as $month) {
            if (!$month['authorized']) {
                $unauthorizedMonths[] = $month['fy_month'];
            }
        }
    @endphp
    
    <script>
        const locationCostCentersMap = @json($cost_centers);
        const unauthorizedMonths = @json($unauthorizedMonths);
        const fy = @json($fy_months);
        console.log("fy",fy);
        console.log("unauthorizedMonths",unauthorizedMonths);
        
    </script>
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">

            <form id="voucherForm" action="{{ route('vouchers.import.save') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="doc_number_type" id="doc_number_type">
                <input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern">
                <input type="hidden" name="doc_prefix" id="doc_prefix">
                <input type="hidden" name="doc_suffix" id="doc_suffix">
                <input type="hidden" name="doc_no" id="doc_no">
                <input type="hidden" name="org_currency_id" id="org_currency_id">
                <input type="hidden" name="org_currency_code" id="org_currency_code">
                <input type="hidden" name="org_currency_exg_rate" id="org_currency_exg_rate">

                <input type="hidden" name="comp_currency_id" id="comp_currency_id">
                <input type="hidden" name="comp_currency_code" id="comp_currency_code">
                <input type="hidden" name="comp_currency_exg_rate" id="comp_currency_exg_rate">

                <input type="hidden" name="group_currency_id" id="group_currency_id">
                <input type="hidden" name="group_currency_code" id="group_currency_code">
                <input type="hidden" name="group_currency_exg_rate" id="group_currency_exg_rate">

                <input type="hidden" name="currency_code" id="currency_code">
                <input type="hidden" name="document_status" id="document_status" value="draft">

                <input type="hidden" name="status" id="status">

                <div class="content-header pocreate-sticky">
                    <div class="row">
                        <div class="content-header-left col-md-6 col-6 mb-2">
                            <div class="row breadcrumbs-top">
                                <div class="col-12">
                                    <h2 class="content-header-title float-start mb-0">Voucher Import</h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="{{ route('/') }}">Home</a></li>
                                            <li class="breadcrumb-item"><a href="{{ route('vouchers.index') }}">Vouchers
                                                    List</a></li>
                                            <li class="breadcrumb-item active">Add New</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-end col-md-6 col-6 mb-2 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <button onClick="javascript: history.go(-1)" class="btn btn-secondary btn-sm"><i
                                        data-feather="arrow-left-circle"></i> Back</button>
                                <button type="button" onclick="submitForm('draft');" id="draft"
                                    class="btn btn-outline-primary btn-sm mb-50 mb-sm-0"><i data-feather='save'></i> Save as
                                    Draft</button>
                                <button type="button" onclick="submitForm('submitted');"
                                    class="btn btn-primary btn-sm mb-50 mb-sm-0" id="submitted"><i
                                        data-feather="check-circle"></i>
                                    Submit</button>
                                <input id="submitButton" type="submit" value="Submit" class="hidden" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <div class="newheader">
                                                        <h4 class="card-title text-theme">Basic Information</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 d-flex align-items-center justify-content-end">
                                                    <a href="{{route('vouchers.download.sample')}}"
                                                        class="btn btn-outline-primary waves-effect">
                                                        <i class="fas fa-download me-1"></i> Download Sample
                                                    </a>
                                                    <a class="d-none btn btn-outline-danger waves-effect download-error-file-url mx-1"
                                                        href="#">
                                                        <i class="fas fa-download me-1"></i> Dowload Error File
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher Type <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-select" name="book_type_id"
                                                            id="book_type_id" required onchange="getBooks()">
                                                            @foreach ($bookTypes as $bookType)
                                                                <option value="{{ $bookType->id }}"
                                                                    data-alias="{{ $bookType->alias }}"
                                                                    @if ($lastVoucher) @if ($lastVoucher->book_type_id == $bookType->id) selected @endif
                                                                    @endif>{{ $bookType->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-select" id="book_id" name="book_id"
                                                            required onchange="getDocNumberByBookId()">
                                                            <option disabled selected value="">Select</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div hidden class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher Name <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" name="voucher_name"
                                                            id="voucher_name" required value="{{ old('voucher_name') }}"
                                                            readonly />
                                                        @error('voucher_name')
                                                            <span class="text-danger" style="font-size:12px">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Voucher No. <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" id="voucher_no"
                                                            name="voucher_no" required value="{{ old('voucher_no') }}"
                                                            readonly />
                                                        @error('voucher_no')
                                                            <span class="text-danger" style="font-size:12px">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                </div>


                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Date <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="date" class="form-control" id="date"
                                                            name="date" required value="{{ date('Y-m-d') }}"
                                                            max="{{ date('Y-m-d') }}" />
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select id="locations" class="form-select"
                                                            name="location" data-row="${rowCount + 1}" required>
                                                            @foreach ($locations as $location)
                                                                <option value="{{ $location->id }}">
                                                                    {{ $location->store_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Currency <span
                                                                class="text-danger">*</span></label>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <select class="form-control select2" name="currency_id"
                                                            id="currency_id" onchange="getExchangeRate()">
                                                            <option value="" @if($orgCurrency==null) selected @endif>Select Currency</option>
                                                            @foreach ($currencies as $currency)
                                                                <option value="{{ $currency->id }}"
                                                                    @if ($orgCurrency == $currency->id) selected @endif>
                                                                    {{ $currency->name . ' (' . $currency->short_name . ')' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label mt-50">Exchange Rate</label>


                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" class="form-control" id="orgExchangeRate"
                                                            id="orgExchangeRate" oninput="calculate_cr_dr()"
                                                            onclick="rate_change()" />
                                                    </div>
                                                    <div hidden class="col-md-7">

                                                        <div class="d-flex align-items-center">
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="base_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />



                                                                    </div>
                                                                    <label class="form-label">Base</label>
                                                                </div>

                                                                <div hidden class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="company_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />

                                                                        <input type="text" class="form-control"
                                                                            readonly id="company_exchange_rate"
                                                                            style="width: 80px;  border-radius:0 7px 7px 0" />


                                                                    </div>
                                                                    <label class="form-label">Company</label>
                                                                </div>

                                                                <div hidden class="col-md-4">
                                                                    <div class="d-flex">
                                                                        <input type="text" class="form-control"
                                                                            readonly id="grp_currency_code"
                                                                            style="text-transform:uppercase;width: 80px; border-right: none; border-radius: 7px 0 0 7px" />

                                                                        <input type="text" class="form-control"
                                                                            readonly id="grp_exchange_rate"
                                                                            style="width: 80px;  border-radius:0 7px 7px 0" />


                                                                    </div>
                                                                    <label class="form-label">Group</label>
                                                                </div>

                                                               
                                                            </div>



                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-2">
                                                        <label class="form-label">Import File <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="file" accept=".xlsx, .xls, .csv"
                                                            name="import_file" class="form-control"
                                                            onchange = "addFiles(this,'voucher_file_preview')">
                                                        <span
                                                            class="text-primary small">{{ __('(Allowed formats: .xlsx, .xls, .csv)') }}</span>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="row" id="voucher_file_preview">
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
                            <div class="hide-this-section" style="display: none;">
            <div class="content-body">
                <!-- Import Summary Cards -->
                <div class="row mb-2">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bolder mb-75 text-success" id="success-count">0</h3>
                                        <p class="card-text" id="success-count-badge">Records Succeeded: 0</p>
                                    </div>
                                    <div class="avatar bg-light-success p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="check-circle" class="font-medium-5"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card border-left-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bolder mb-75 text-danger" id="failed-count">0</h3>
                                        <p class="card-text">Records Failed</p>
                                    </div>
                                    <div class="avatar bg-light-danger p-50 m-0">
                                        <div class="avatar-content">
                                            <i data-feather="x-circle" class="font-medium-5"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Buttons -->
                <!-- <div class="row mb-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Export Options</h4>
                                    <div>
                                        <form id="exportForm" method="GET" action="{{ route('vouchers.export.successful') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm me-1 exportBtn">
                                                <i data-feather="download"></i> Export Records
                                            </button>
                                        </form>
                                        <form id="exportFailedForm" method="GET" action="{{ route('vouchers.export.failed') }}" style="display: none;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i data-feather="download"></i> Export Failed Records
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Results Tables with Tabs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Import Results</h4>
                            </div>
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs border-bottom" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#successful-records">Records Succeeded &nbsp;<span id="success-count">(0)</span></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-danger" data-bs-toggle="tab" href="#failed-records">Records Failed &nbsp;<span id="failed-count">(0)</span></a>
                                    </li>
                                </ul>

                                <!-- Tab content -->
                                <div class="tab-content">
                                    <div class="tab-pane active" id="successful-records">
                                        <div class="text-end my-1">
                                            <a href="{{ route('vouchers.export.successful') }}" class="btn btn-success btn-sm mb-50 mb-sm-0 me-50 waves-effect" id="exportSuccessBtn" style="display: none;">
                                                <i data-feather="download"></i> Export Successful Records
                                            </a>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="datatables-basic1 datatables-success table myrequesttablecbox">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Ledger Code</th>
                                                        <th>Ledger Name</th>
                                                        <th>Debit Amount</th>
                                                        <th>Credit Amount</th>
                                                        <th>Status</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="success-table-body">
                                                    <tr>
                                                        <td colspan="7">No records found</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="failed-records">
                                        <div class="text-end my-1">
                                            <a href="{{ route('vouchers.export.failed') }}" class="btn btn-danger btn-sm mb-50 mb-sm-0 me-50 waves-effect" id="exportFailedBtn" style="display: none;">
                                                <i data-feather="download"></i> Export Failed Records
                                            </a>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="datatables-basic datatables-failed table myrequesttablecbox">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Ledger Code</th>
                                                        <th>Ledger Name</th>
                                                        <th>Debit Amount</th>
                                                        <th>Credit Amount</th>
                                                        <th>Cost Center</th>
                                                        <th>Status</th>
                                                        <th>Remarks</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="failed-table-body">
                                                    <tr>
                                                        <td colspan="8">No records found</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
            </form>

        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ url('/app-assets/js/jquery-ui.js') }}"></script>
    <script type="text/javascript" src="{{ asset('app-assets/js/file-uploader.js') }}"></script>
    <script>
        function getMonthName(ym) {
            // ym = '2024-07'
            const [year, month] = ym.split('-');
            const d = new Date(year, parseInt(month) - 1);
            return d.toLocaleString('default', { month: 'long', year: 'numeric' });
        }

        document.getElementById('date').addEventListener('input', function() {
             if (!isDateAuthorized(this.value)) {
                this.value = '';
                this.focus();
            }
        });

        window.addEventListener('DOMContentLoaded', function() {
            var dateInput = document.getElementById('date');
            if (dateInput && dateInput.value && !isDateAuthorized(dateInput.value)) {
                dateInput.value = '';
                dateInput.focus();
            }
        });
        // $('#voucherForm').on('submit', function () {
        //     $('.preloader').show();
        // });
        $('.voucher_details').hide();

        function getMonthName(ym) {
            const [y, m] = ym.split('-').map(Number);
            return new Intl.DateTimeFormat('en', {month:'long', year:'numeric'}).format(new Date(y, m-1, 1));
        }

        function isDateAuthorized(dateValue) {
            if (!dateValue) return true;
            var selectedMonth = dateValue.substring(0, 7);
            if (unauthorizedMonths.includes(selectedMonth)) {
                var monthLabel = getMonthName(selectedMonth);
                Swal.fire({
                    icon: 'error',
                    title: 'Not allowed',
                    text: `You are not authorized to select dates from ${monthLabel}. Please pick another month.`,
                    confirmButtonText: 'OK'
                    });
                return false;
            }
            else{
                console.log("authorized month name",selectedMonth);
                
            }
            return true;
        }
       
        const dateEl = document.getElementById('date');
        let lastValid = dateEl.value;  // keep previous value

        dateEl.addEventListener('change', function () {
            if (!isDateAuthorized(this.value)) {
                this.value = lastValid; // revert if unauthorized
            } else {
                lastValid = this.value; // update last valid
            }
        });
        
        var currencies = {!! json_encode($currencies) !!};
        var orgCurrency = {{ $orgCurrency }};
        var orgCurrencyName = '';


        $(document).ready(function() {
            $('#book_type_id').trigger('change');
            $('#locations').trigger('change');
            // Trigger change event to update voucher details
            if (orgCurrency != "") {
                $.each(currencies, function(key, value) {
                    if (value['id'] == orgCurrency) {
                        orgCurrencyName = value['short_name'];
                    }
                });
                //$('#orgCurrencyName').text(orgCurrencyName);
            }
            getExchangeRate();



            // Unified event handler for row and input/select clicks
            $('#item-details-body').on('click', 'tr, input, select', function(event) {
                const row = $(this).closest('tr'); // Get the closest tr element from the clicked element
                const rowId = row.attr('id'); // Get the row ID
                $('#item-details-body tr').removeClass('trselected');
                row.addClass('trselected');
                handleRowClick(rowId);
            });


            $('.remark-btn').on('click', function() {
                const rowId = $(this).data('row-id'); // Get the row ID
                const currentRemarks = $(`#hiddenRemarks_${rowId}`)
                    .val(); // Fetch the current remarks from the hidden input

                // Populate the modal
                $('#currentRowId').val(rowId);
                $('#remarksInput').val(currentRemarks.trim());
            })
            // Handle modal submission
            $('#submitRemarks').on('click', function() {
                const rowId = $('#currentRowId').val();
                const newRemarks = $('#remarksInput').val();

                // Update the hidden input
                $(`#hiddenRemarks_${rowId}`).val(newRemarks);
                handleRowClick(rowId);


                $('#remarksModal').modal('hide'); // Close the modal

            });


        });
   
        function getExchangeRate() {
            $('#item-details-body tr').removeClass('trselected');
            $('.voucher_details').hide();
            $('.selectedCurrencyName').text('');

            if (orgCurrency != "") {
                let currency = parseFloat($('#currency_id').val()) || 0;
                if (currency != 0) {
                    console.log(currency);
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: '{{ route('getExchangeRate') }}',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            date: $('#date').val(),
                            '_token': '{!! csrf_token() !!}',
                            currency: currency
                        },
                        success: function(response) {
                            if (response.status) {

                                $('#orgExchangeRate').val(response.data.org_currency_exg_rate)
                                    .trigger(
                                        'change');

                                $('#currency_code').val(response.data.party_currency_code);
                                $('#org_currency_id').val(response.data.org_currency_id);
                                $('#org_currency_code').val(response.data.org_currency_code);
                                $('#base_currency_code').val(response.data.org_currency_code);

                                $('.selectedCurrencyName').text("(" + $('#org_currency_code').val() + ")");

                                $('#org_currency_exg_rate').val(response.data
                                    .org_currency_exg_rate);

                                $('#comp_currency_id').val(response.data.comp_currency_id);
                                $('#comp_currency_code').val(response.data.comp_currency_code);
                                $('#comp_currency_exg_rate').val(response.data
                                    .comp_currency_exg_rate);

                                $('#company_currency_code').val(response.data.comp_currency_code);
                                $('#company_exchange_rate').val(response.data
                                    .comp_currency_exg_rate);

                                $('#group_currency_id').val(response.data.group_currency_id);
                                $('#group_currency_code').val(response.data.group_currency_code);
                                $('#group_currency_exg_rate').val(response.data
                                    .group_currency_exg_rate);
                                $('#grp_currency_code').val(response.data.group_currency_code);
                                $('#grp_exchange_rate').val(response.data
                                    .group_currency_exg_rate);

                                calculate_cr_dr();

                            } else {
                                resetCurrencies();
                                $('#orgExchangeRate').val('');
                                showToast("error", response.message);
                            }
                        }
                    });

                } else {
                    resetCurrencies();
                }
            } else {
                showToast("error", 'Organization currency is not set!!');
            }
        }

        function resetCurrencies() {
            $('#org_currency_id').val('');
            $('#org_currency_code').val('');
            $('#org_currency_exg_rate').val('');

            $('#comp_currency_id').val('');
            $('#comp_currency_code').val('');
            $('#comp_currency_exg_rate').val('');

            $('#group_currency_id').val('');
            $('#group_currency_code').val('');
            $('#group_currency_exg_rate').val('');

            $('#base_currency_code').val('');

            $('#company_currency_code').val('');
            $('#company_exchange_rate').val('');

            $('#grp_currency_code').val('');
            $('#grp_exchange_rate').val('');


            $('#orgExchangeRate').val('');
        }


        function submitForm(status) {
            var dateInput = document.getElementById('date');
            if (dateInput && !isDateAuthorized(dateInput.value)) {
                dateInput.value = '';
                dateInput.focus();
                return false; // Prevent form submission
            }
            $('#status').val(status);
            $('#submitButton').click();
        }


        var costcenters = {!! json_encode($cost_centers) !!};
        var bookTypes = {!! json_encode($bookTypes) !!};
        var lastVoucher = {!! json_encode($lastVoucher) !!};

        $(function() {
            $(".ledgerselect").autocomplete({

                source: function(request, response) {


                    // get all pre selected ledgers
                    var preLedgers = [];
                    $('.ledgers').each(function() {
                        if ($(this).val() != "") {
                            preLedgers.push($(this).val());
                        }
                    });
                    if ($('#book_type_id').val() != null) {

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                    'content')
                            },
                            url: '{{ route('ledgers.search') }}',
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: request.term,
                                series: $('#book_type_id').val(),
                                ids: preLedgers,
                                '_token': '{!! csrf_token() !!}'
                            },
                            success: function(data) {
                                response(
                                    data);
                                // Pass the data to the response callback
                            },
                            error: function() {
                                response(
                                    []);
                                // Respond with an empty array in case of error
                            }
                        });
                    }
                },
                minLength: 0,
                select: function(event, ui) {

                    $(this).val(ui.item.label);

                    // This function is called when an item is selected from the list
                    console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                        .value);
                    let ledgerId = ui.item.value; // The value of the selected ledger
                    let rowId = $(this).data('id'); // The unique ID for the row

                    console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);

                    // Use rowId to target the corresponding group dropdown
                    let groupDropdown = $(`#groupSelect${rowId}`);
                    var preGroups = [];
                    $('.ledgerGroup').each(function(index) {
                        let ledgerGroup = $(this).val(); // Get the value of the select/input
                        let ledger_id = $(this).data(
                            'ledger'); // Get the ledger ID from data attribute

                        if (ledgerGroup !== "") {
                            preGroups.push({
                                ledger_id: ledger_id, // Ledger ID from data attribute
                                ledgerGroup: ledgerGroup // Selected value
                            });
                        }
                    });


                    if (ledgerId) {
                        $.ajax({
                            url: '{{ route('voucher.getLedgerGroups') }}',
                            method: 'GET',
                            data: {
                                ledger_id: ledgerId,
                                ids: preGroups,
                                _token: $('meta[name="csrf-token"]').attr(
                                    'content') // CSRF token
                            },
                            success: function(response) {
                                groupDropdown.empty(); // Clear previous options

                                response.forEach(item => {
                                    groupDropdown.append(
                                        `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                                    );
                                });
                                groupDropdown.data('ledger', ledgerId);
                                handleRowClick(rowId);

                            },
                            error: function(xhr) {
                                let errorMessage =
                                    'Error fetching group items.'; // Default message

                                if (xhr.responseJSON && xhr.responseJSON.error) {
                                    errorMessage = xhr.responseJSON
                                        .error; // Use API error message if available
                                }
                                showToast("error", errorMessage);


                            }
                        });
                    }



                    // console.log(ui.item);

                    // You can also perform other actions here
                    const id = $(this).attr("data-id");
                    $('#ledger_id' + id).val(ui.item.value);
                    // if (ui.item.cost_center_id != "") {
                    //     console.log(ui.item.cost_center_id);
                    //     $.each(costcenters, function(ckey, cvalue) {
                    //         if (ui.item.cost_center_id == cvalue['value']) {
                    //             $("#cost_center_name" + id).val(cvalue['label']);
                    //             $("#cost_center_id" + id).val(cvalue['value']);
                    //         }
                    //     });
                    // }

                    return false;
                },
                change: function(event, ui) {
                    // If the selected item is invalid (i.e., user has not selected from the list)
                    if (!ui.item) {
                        // Clear the input field
                        $(this).val("");

                        // You can also perform other actions here
                        const id = $(this).attr("data-id");
                        $('#ledger_id' + id).val('');
                    }
                },
                focus: function(event, ui) {
                    // Prevent value from being inserted on focus
                    return false; // Prevents default behavior
                },
            }).focus(function() {
                if (this.value == "") {
                    $(this).autocomplete("search");
                }
                return false; // Prevents default behavior
            });

            // Monitor input field for empty state
            $(".ledgerselect").on('input', function() {
                const id = $(this).attr("data-id");
                let grp = $(`#groupSelect${id}`).empty();
                var inputValue = $(this).val();
                if (inputValue.trim() === '') {
                    const id = $(this).attr("data-id");
                    $('#ledger_id' + id).val('');
                    $(`#groupSelect${id}`).empty();

                }
            });

            //     $(".centerselecct").autocomplete({
            //         source: costcenters,
            //         minLength: 0,
            //         select: function(event, ui) {
            //             $(this).val(ui.item.label);

            //             // This function is called when an item is selected from the list
            //             console.log("Selected: " + ui.item.label + " with ID: " + ui.item
            //                 .value);
            //             console.log(ui.item);
            //             let ledgerId = ui.item.value;
            //             console.log(ledgerId);

            //             let groupDropdown = $(`#groupSelect${rowId}`);

            //             var preGroups = [];
            //             $('.ledgerGroup').each(function(index) {
            //                 let ledgerGroup = $(this).val(); // Get the value of the select/input
            //                 let ledger_id = $(this).data(
            //                     'ledger'); // Get the ledger ID from data attribute

            //                 if (ledgerGroup !== "") {
            //                     preGroups.push({
            //                         ledger_id: ledger_id, // Ledger ID from data attribute
            //                         ledgerGroup: ledgerGroup // Selected value
            //                     });
            //                 }
            //             });



            //             if (ledgerId) {
            //                 $.ajax({
            //                     url: '{{ route('voucher.getLedgerGroups') }}',
            //                     method: 'GET',
            //                     data: {
            //                         ledger_id: ledgerId,
            //                         ids: preGroups,
            //                         _token: $('meta[name="csrf-token"]').attr(
            //                             'content') // CSRF token
            //                     },
            //                     success: function(response) {
            //                         groupDropdown.empty(); // Clear previous options

            //                         response.forEach(item => {
            //                             groupDropdown.append(
            //                                 `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
            //                             );
            //                         });
            //                         groupDropdown.data('ledger', ledgerId);
            //                         handleRowClick(rowId);

            //                     },
            //                     error: function(xhr) {
            // let errorMessage = 'Error fetching group items.'; // Default message

            // if (xhr.responseJSON && xhr.responseJSON.error) {
            //     errorMessage = xhr.responseJSON.error; // Use API error message if available
            // }
            // showToast("error", errorMessage);
            // }
            //                 });
            //             }

            //             // You can also perform other actions here
            //             const id = $(this).attr("data-id");
            //             $('#cost_center_id' + id).val(ui.item.value);

            //             return false;
            //         },
            //         change: function(event, ui) {
            //             // If the selected item is invalid (i.e., user has not selected from the list)
            //             if (!ui.item) {
            //                 // Clear the input field
            //                 $(this).val("");

            //                 // You can also perform other actions here
            //                 const id = $(this).attr("data-id");
            //                 $('#cost_center_id' + id).val('');
            //             }
            //         }
            //     }).focus(function() {
            //         if (this.value == "") {
            //             $(this).autocomplete("search");
            //         }
            //     });
        });

        $(document).bind('ctrl+n', function() {
            document.getElementById('addnew').click();
        });

      

        $(document).on('input', '.dbt_amt, .crd_amt, .dbt_amt_inr, .crd_amt_inr,.remarks_', function() {
            const inVal = parseFloat(removeCommas($(this).val())) || 0;
            const rowId = $(this).closest('tr').attr('id'); // Get the row ID
            const $row = $(this).closest('tr'); // Find the row of the current input

            if ($(this).hasClass('dbt_amt')) {
                $row.find('.crd_amt').val(0);
            } else if ($(this).hasClass('crd_amt')) {
                $row.find('.dbt_amt').val(0);
            }

            handleRowClick(rowId);
            calculate_cr_dr();
        });

        // Moving between input fields on pressing ENTER
        $(document).on('keydown', function(event) {
            if (event.keyCode === 13) {
                var activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA') {
                    // Check if the input is not hidden
                    if (activeElement.type !== 'hidden') {
                        event.preventDefault(); // Prevent default enter key behavior

                        // Get the next sibling in the current row
                        var nextField = activeElement.nextElementSibling;
                        while (nextField && nextField.type === 'hidden') {
                            nextField = nextField.nextElementSibling;
                        }

                        // If there's a next field in the row, focus on it
                        if (nextField) {
                            nextField.focus();
                            return; // Stop further navigation within the row
                        }

                        // Otherwise, find the first input in the next column
                        var nextColumn = activeElement.closest('td').nextElementSibling;
                        if (nextColumn) {
                            nextField = nextColumn.querySelector('input, textarea');
                            if (nextField) {
                                nextField.focus();
                                return; // Stop further navigation within the row
                            }
                        }

                        // Otherwise, find the first input in the next row
                        var nextRow = activeElement.closest('tr').nextElementSibling;
                        if (nextRow) {
                            nextField = nextRow.querySelector('input, textarea');
                            if (nextField) {
                                nextField.focus();
                            }
                        }
                    }
                }
            }
        });

        // Remove item row
        $(document).on('click', '.remove-item', function() {
            $(this).closest('tr').remove(); // Remove the entire row
            updateRowNumbers();
            calculate_cr_dr(); // Call your custom function
        });

        function rate_change() {
            $('.voucher_details').hide();

        }

        function populateCostCenterDropdowns() {
            let selectedLocationIds = $('#locations').val();

            const costCenterSet = locationCostCentersMap.filter(center => {
                if (!center.location) return false;
                const locationArray = Array.isArray(center.location) ?
                    center.location.flatMap(loc => loc.split(',')) :
                    [];
                return locationArray.includes(String(selectedLocationIds));
            });

            // Update all .costCenter selects
            $('.costCenter').each(function() {
                let $dropdown = $(this);
                $dropdown.empty();
                costCenterSet.forEach((center) => {
                    $dropdown.append(`<option value="${center.id}">${center.name}</option>`);
                });
            });
        }

        function populateSingleCostCenterDropdown($dropdown,val) {
            let selectedLocationIds = $('#locations').val();

            const costCenterSet = locationCostCentersMap.filter(center => {
                if (!center.location) return false;
                const locationArray = Array.isArray(center.location)
                    ? center.location.flatMap(loc => loc.split(','))
                    : [];
                return locationArray.includes(String(selectedLocationIds));
            });

            $dropdown.empty();

            costCenterSet.forEach((center) => {
                const isSelected = String(center.id) === String(val) ? 'selected' : '';
                $dropdown.append(`<option value="${center.id}" ${isSelected}>${center.name}</option>`);
            });
            console.log(`Cost center dropdown populated with value: ${val}`);
        }

        function calculate_cr_dr() {
            $('#org_currency_exg_rate').val($('#orgExchangeRate').val());
            const exchangeRate = parseFloat($('#orgExchangeRate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            const exchangeRateComp = parseFloat($('#comp_currency_exg_rate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            const exchangeRateGroup = parseFloat($('#group_currency_exg_rate').val()) ||
                1; // Assume an input for exchange rate with id 'exchange_rate'

            $('#item-details-body tr').each(function() {
                const rowId = $(this).attr('id'); // Get the row ID

                // Get the debit and credit values for the current row
                const debitAmt = parseFloat(removeCommas($(`#dept_${rowId}`).val())) || 0;
                const creditAmt = parseFloat(removeCommas($(`#crd_${rowId}`).val())) || 0;

                // Organization Rate
                $(`#dept_inr_${rowId}`).val((debitAmt * exchangeRateComp).toFixed(2));
                $(`#crd_inr_${rowId}`).val((creditAmt * exchangeRateComp).toFixed(2));

                //Company Rate
                $(`#comp_debit_amt_${rowId}`).val((debitAmt * exchangeRateComp).toFixed(2));
                $(`#comp_credit_amt_${rowId}`).val((creditAmt * exchangeRateComp).toFixed(2));


                //Group Rate
                $(`#group_debit_amt_${rowId}`).val((debitAmt * exchangeRateGroup).toFixed(2));
                $(`#group_credit_amt_${rowId}`).val((creditAmt * exchangeRateGroup).toFixed(2));
            });

            let cr_sum = 0;
            let cr_sum_inr = 0;
            let dr_sum = 0;
            let dr_sum_inr = 0;
            $('.crd_amt').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                cr_sum += value;
            });

            // Iterate over credit INR amount fields
            $('.crd_amt_inr').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                cr_sum_inr += value;
            });

            // Iterate over debit amount fields
            $('.dbt_amt').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                dr_sum += value;
            });

            // Iterate over debit INR amount fields
            $('.dbt_amt_inr').each(function() {
                const value = parseFloat(removeCommas($(this).val())) || 0;
                dr_sum_inr += value;
            });
            $('#crd_total_inr').text(formatIndianNumber(cr_sum_inr.toFixed(2)));
            $('#crd_total').text(formatIndianNumber(cr_sum.toFixed(2)));
            $('#dbt_total').text(formatIndianNumber(dr_sum.toFixed(2)));
            $('#dbt_total_inr').text(formatIndianNumber(dr_sum_inr.toFixed(2)));

            $('#amount').val(dr_sum);

        }

        var books = [];
        document.addEventListener('DOMContentLoaded', function() {
            // Add new item row
            document.querySelector('.add-item-row').addEventListener('click', function(e) {
                e.preventDefault();

                var cr_amount = 0;
                var dr_amount = 0;

                $('.dbt_amt').each(function() {
                    const value = parseFloat(removeCommas($(this).val())) || 0;
                    $(this).val(value.toFixed(2));

                });
                $('.crd_amt').each(function() {
                    const value = parseFloat(removeCommas($(this).val())) || 0;
                    $(this).val(value.toFixed(2));

                });
                if (parseFloat(removeCommas($('#crd_total').text())) == parseFloat(removeCommas($(
                            '#dbt_total')
                        .text()))) {} else if (
                    parseFloat(removeCommas($('#crd_total').text())) > parseFloat(removeCommas($(
                        '#dbt_total').text()))) {
                    dr_amount = parseFloat(removeCommas($('#crd_total').text())) - parseFloat(removeCommas(
                        $('#dbt_total')
                        .text()));
                } else {
                    cr_amount = parseFloat(removeCommas($('#dbt_total').text())) - parseFloat(removeCommas(
                        $('#crd_total')
                        .text()));
                }


                let rowCount = document.querySelectorAll('#item-details-body tr').length;
                rowCount = Number($('#item-details-body tr:last').attr('id')) ||
                    0; // Fallback if no rows exist
                let totalDebit = parseFloat(removeCommas($('#dbt_total').text()));
                let totalCredit = parseFloat(removeCommas($('#crd_total').text()));
                let balanceDebit = totalDebit - totalCredit; // Calculate the balance for debit
                let balanceCredit = totalCredit - totalDebit; // Calculate the balance for credit
                balanceDebit = balanceDebit.toFixed(2);
                balanceCredit = balanceCredit.toFixed(2);

                let newRow = `
                <tr id="${rowCount + 1}">
                    <td class="number">${rowCount + 1}</td>
                    <td class="poprod-decpt">
                        <input type="text"
                            class="form-control mw-100 ledgerselect"
                            placeholder="Select Ledger" name="ledger_name${rowCount + 1}"
                            required id="ledger_name${rowCount + 1}"
                            data-id="${rowCount + 1}" />
                        <input type="hidden" name="ledger_id[]" type="hidden" id="ledger_id${rowCount + 1}" class="ledgers" />
                    </td>
                    <td>
                        <select required id="groupSelect${rowCount + 1}" name="parent_ledger_id[]" class="ledgerGroup form-select mw-100">
                        </select>
                    </td>
                    <input type="hidden" name="group_debit_amt[]" id="group_debit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="comp_debit_amt[]" id="comp_debit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="group_credit_amt[]" id="group_credit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" name="comp_credit_amt[]" id="comp_credit_amt_${rowCount + 1}" value="0">
                    <input type="hidden" class="dbt_amt_inr debt_inr_${rowCount + 1}" name="org_debit_amt[]" id="dept_inr_${rowCount + 1}" />
                    <input type="hidden" class="crd_amt_inr crd_inr_${rowCount + 1}" name="org_credit_amt[]" id="crd_inr_${rowCount + 1}" />

                    <td>
                        <input type="number" class="form-control mw-100 dbt_amt debt_${rowCount + 1} text-end" onfocus="focusInput(this)"
                            name="debit_amt[]" id="dept_${rowCount + 1}" min="0" step="0.01"
                            value="${balanceCredit > 0 ? balanceCredit : 0}"/>
                    </td>
                    <td>
                        <input type="number" class="form-control mw-100 crd_amt crd_${rowCount + 1} text-end" onfocus="focusInput(this)"
                            name="credit_amt[]" id="crd_${rowCount + 1}" min="0" step="0.01"
                            value="${balanceDebit > 0 ? balanceDebit : 0}"/>
                    </td>
                    <td>
                        <select class="costCenter form-select mw-100" name="cost_center_id[]" id="cost_center_id${rowCount + 1}">
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control mw-100 remarks_" placeholder="Enter Remarks"
                            id="hiddenRemarks_${rowCount + 1}" name="item_remarks[]" value="">
                    </td>
                    <td>
                        <div class="d-flex">
                            <div hidden class="me-50 cursor-pointer remark-btn" data-row-id="${rowCount + 1}" data-bs-toggle="modal"
                                data-bs-target="#remarksModal"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary"><i data-feather="file-text"></i></span></div>
                            <div class="me-50 cursor-pointer"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" class="text-danger remove-item"><i data-feather="trash-2"></i></span></div>
                        </div>
                    </td>
                </tr>
                `;

                updateRowNumbers();
                document.querySelector('#item-details-body').insertAdjacentHTML('beforeend', newRow);
                // Populate cost centers for the new row's dropdown
                let selected = $(`#cost_center_id${rowCount}`).val();
                populateSingleCostCenterDropdown($(`#cost_center_id${rowCount + 1}`),selected);
                console.log(`Cost center for row ${rowCount + 1} populated with value: ${$(`#cost_center_id${rowCount}`).val()}`);
                calculate_cr_dr();


                feather.replace({
                    width: 14,
                    height: 14
                });
                $(".ledgerselect").autocomplete({

                    source: function(request, response) {



                        // get all pre selected ledgers
                        var preLedgers = [];
                        $('.ledgers').each(function() {
                            if ($(this).val() != "") {
                                preLedgers.push($(this).val());
                            }
                        });

                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                                    'content')
                            },
                            url: '{{ route('ledgers.search') }}',
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: request.term,
                                series: $('#book_type_id').val(),
                                ids: preLedgers,
                                '_token': '{!! csrf_token() !!}'
                            },
                            success: function(data) {
                                response(
                                    data); // Pass the data to the response callback
                            },
                            error: function() {
                                response(
                                    []
                                ); // Respond with an empty array in case of error
                            }
                        });
                    },
                    minLength: 0,
                    select: function(event, ui) {
                        $(this).val(ui.item.label);

                        // This function is called when an item is selected from the list
                        console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                            .value);
                        let ledgerId = ui.item.value; // The value of the selected ledger
                        let rowId = $(this).data('id'); // The unique ID for the row

                        console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);

                        // Use rowId to target the corresponding group dropdown
                        let groupDropdown = $(`#groupSelect${rowId}`);
                        var preGroups = [];
                        $('.ledgerGroup').each(function(index) {
                            let ledgerGroup = $(this)
                                .val(); // Get the value of the select/input
                            let ledger_id = $(this).data('ledger') ||
                                0; // Get the ledger ID from data attribute

                            if (ledgerGroup !== "") {
                                preGroups.push({
                                    ledger_id: ledger_id, // Ledger ID from data attribute
                                    ledgerGroup: ledgerGroup // Selected value
                                });
                            }
                        });




                        if (ledgerId) {
                            $.ajax({
                                url: '{{ route('voucher.getLedgerGroups') }}',
                                method: 'GET',
                                data: {
                                    ledger_id: ledgerId,
                                    ids: preGroups,
                                    _token: $('meta[name="csrf-token"]').attr(
                                        'content') // CSRF token
                                },
                                success: function(response) {
                                    groupDropdown.empty(); // Clear previous options

                                    response.forEach(item => {
                                        groupDropdown.append(
                                            `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                                        );
                                    });
                                    groupDropdown.data('ledger', ledgerId);
                                    handleRowClick(rowId);


                                },
                                error: function(xhr) {
                                    let errorMessage =
                                        'Error fetching group items.'; // Default message

                                    if (xhr.responseJSON && xhr.responseJSON
                                        .error) {
                                        errorMessage = xhr.responseJSON
                                            .error; // Use API error message if available
                                    }
                                    showToast("error", errorMessage);
                                }
                            });
                        }



                        // console.log(ui.item);

                        // You can also perform other actions here
                        const id = $(this).attr("data-id");
                        $('#ledger_id' + id).val(ui.item.value);
                        // if (ui.item.cost_center_id != "") {
                        //     console.log(ui.item.cost_center_id);
                        //     $.each(costcenters, function(ckey, cvalue) {
                        //         if (ui.item.cost_center_id == cvalue['value']) {
                        //             $("#cost_center_name" + id).val(cvalue['label']);
                        //             $("#cost_center_id" + id).val(cvalue['value']);
                        //         }
                        //     });
                        // }

                        return false;
                    },
                    change: function(event, ui) {
                        // If the selected item is invalid (i.e., user has not selected from the list)
                        if (!ui.item) {
                            // Clear the input field
                            $(this).val("");

                            // You can also perform other actions here
                            const id = $(this).attr("data-id");
                            $('#ledger_id' + id).val('');
                        }
                    },
                    focus: function(event, ui) {
                        // Prevent value from being inserted on focus
                        return false; // Prevents default behavior
                    },
                }).focus(function() {
                    if (this.value == "") {
                        $(this).autocomplete("search");
                    }
                    return false; // Prevents default behavior
                });

                // Monitor input field for empty state
                $(".ledgerselect").on('input', function() {
                    const id = $(this).attr("data-id");
                    let grp = $(`#groupSelect${id}`).empty();

                    var inputValue = $(this).val();
                    if (inputValue.trim() === '') {
                        $('#ledger_id' + id).val('');
                    }
                });

                //         $(".centerselecct").autocomplete({
                //             source: costcenters,
                //             minLength: 0,
                //             select: function(event, ui) {
                //                 $(this).val(ui.item.label);

                //                 // This function is called when an item is selected from the list
                //                 console.log("Selected: " + ui.item.label + " with ID: " + ui.item
                //                     .value);
                //                 console.log(ui.item);
                //                 let ledgerId = ui.item.value;
                //                 console.log(ledgerId);

                //                 let groupDropdown = $(`#groupSelect${rowId}`);
                //                 var preGroups = [];
                //                 $('.ledgerGroup').each(function(index) {
                //                     let ledgerGroup = $(this)
                //                         .val(); // Get the value of the select/input
                //                     let ledger_id = $(this).data(
                //                         'ledger'); // Get the ledger ID from data attribute

                //                     if (ledgerGroup !== "") {
                //                         preGroups.push({
                //                             ledger_id: ledger_id, // Ledger ID from data attribute
                //                             ledgerGroup: ledgerGroup // Selected value
                //                         });
                //                     }
                //                 });


                //                 if (ledgerId) {
                //                     $.ajax({
                //                         url: '{{ route('voucher.getLedgerGroups') }}',
                //                         method: 'GET',
                //                         data: {
                //                             ids: preGroups,
                //                             ledger_id: ledgerId,
                //                             _token: $('meta[name="csrf-token"]').attr(
                //                                 'content') // CSRF token
                //                         },
                //                         success: function(response) {
                //                             groupDropdown.empty(); // Clear previous options

                //                             response.forEach(item => {
                //                                 groupDropdown.append(
                //                                     `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                //                                 );
                //                             });
                //                             groupDropdown.data('ledger', ledgerId);
                //                             handleRowClick(rowId);

                //                         },
                //                         error: function(xhr) {
                // let errorMessage = 'Error fetching group items.'; // Default message

                // if (xhr.responseJSON && xhr.responseJSON.error) {
                //     errorMessage = xhr.responseJSON.error; // Use API error message if available
                // }

                // showToast("error", errorMessage); }
                //                     });
                //                 }

                //                 // You can also perform other actions here
                //                 const id = $(this).attr("data-id");
                //                 $('#cost_center_id' + id).val(ui.item.value);

                //                 return false;
                //             },
                //             change: function(event, ui) {
                //                 // If the selected item is invalid (i.e., user has not selected from the list)
                //                 if (!ui.item) {
                //                     // Clear the input field
                //                     $(this).val("");

                //                     // You can also perform other actions here
                //                     const id = $(this).attr("data-id");
                //                     $('#cost_center_id' + id).val('');
                //                 }
                //             }
                //         }).focus(function() {
                //             if (this.value == "") {
                //                 $(this).autocomplete("search");
                //             }
                //         });

            });
        });

        function getBooks() {
            $('#book_id').empty();
            $('#voucher_name').val('');
            $('#voucher_no').val('');
            //$('#book_id').prepend('<option disabled selected value="">Select Series</option>');
            $.ajax({
                url: '{{ route('get_voucher_series', ['placeholder']) }}'.replace('placeholder', $('#book_type_id')
                    .val()),
                type: 'GET',
                success: function(books) {
                    $.each(books, function(key, value) {
                        $("#book_id").append("<option value ='" + value['id'] + "'>" +
                            value['book_code'] + " </option>");
                    });
                     $('#book_id').trigger('change'); 

                }
            });
            let selectedOption = $('#book_type_id').find('option:selected');
            let cv = @json(ConstantHelper::CONTRA_VOUCHER);
            let allowedNames = @json($allowedCVGroups);
            let jv = @json(ConstantHelper::JOURNAL_VOUCHER);
            let excludeNames = @json($exlucdeJVGroups);

            // Check if selected option's data-alias is equal to contra_alias (e.g., 'cv')
            if (selectedOption.data('alias') === cv) {
                $('.ledgerGroup').each(function() {
                    let text = $(this).text().trim();
                    console.log("allowed " + allowedNames, text);
                    // get the visible text of each ledger group
                    if (!allowedNames.includes(text) && (text != "")) {
                        let id = $(this).closest('tr').attr('id');
                        $('#ledger_name' + id).val('');
                        $('#ledger_id' + id).val('');
                        $('#groupSelect' + id).val('');
                    }
                });
            } else if (selectedOption.data('alias') === jv) {
                $('.ledgerGroup').each(function() {
                    let text = $(this).text().trim();
                    console.log("exclude " + excludeNames, text);
                    if (excludeNames.includes(text) && (text != "")) {
                        let id = $(this).closest('tr').attr('id');
                        console.log(excludeNames, text, id);

                        $('#ledger_name' + id).val('');
                        $('#ledger_id' + id).val('');
                        $('#groupSelect' + id).val('');

                    }
                });
            }

           

        }



        function get_voucher_details() {
            $.each(books, function(key, value) {
                if (value['id'] == $('#book_id').val()) {
                    $('#voucher_name').val(value['book_name']);
                }
            });

            $.ajax({
                url: '{{ url('get_voucher_no') }}/' + $('#book_id').val(),
                type: 'GET',
                success: function(data) {
                    if (data.type == "Auto") {
                        $("#voucher_no").attr("readonly", true);
                        $('#voucher_no').val(data.voucher_no);
                    } else {
                        $("#voucher_no").attr("readonly", false);
                    }
                }
            });
        }

        function resetParametersDependentElements(data) {
            let backDateAllowed = false;
            let futureDateAllowed = false;

            if (data != null) {
                if (Array.isArray(data?.parameters?.back_date_allowed)) {
                    for (let i = 0; i < data.parameters.back_date_allowed.length; i++) {
                        if (data.parameters.back_date_allowed[i].trim().toLowerCase() === "yes") {
                            backDateAllowed = true;
                            break; // Exit the loop once we find "yes"
                        }
                    }
                }
                if (Array.isArray(data?.parameters?.future_date_allowed)) {
                    for (let i = 0; i < data.parameters.future_date_allowed.length; i++) {
                        if (data.parameters.future_date_allowed[i].trim().toLowerCase() === "yes") {
                            futureDateAllowed = true;
                            break; // Exit the loop once we find "yes"
                        }
                    }
                }
                //console.log(backDateAllowed, futureDateAllowed);

            }

            const dateInput = document.getElementById("date");

            // Determine the max and min values for the date input
            const today = moment().format("YYYY-MM-DD");
            const fyearStartDate = "{{ $fyear['start_date'] }}";
            const fyearEndDate = "{{ $fyear['end_date'] }}";
            // console.log('here',1,fyearStartDate, fyearEndDate);

            if (backDateAllowed && futureDateAllowed) {
                // dateInput.removeAttribute("min");
                // dateInput.removeAttribute("max");
                 console.log('here',1,fyearStartDate, fyearEndDate);
                dateInput.setAttribute("min", fyearStartDate);
                dateInput.setAttribute("max", fyearEndDate);
            } else if (backDateAllowed) {
                dateInput.setAttribute("max", today);
                dateInput.setAttribute("min", fyearStartDate);
                console.log('here',2);
            } else if (futureDateAllowed) {
                dateInput.setAttribute("min", today);
                dateInput.setAttribute("max", fyearEndDate);
                console.log('here',3);
            } else {
                dateInput.setAttribute("min", today);
                dateInput.setAttribute("max", today);
                // console.log('here',4);
            }
        }

        function getDocNumberByBookId() {
            resetParametersDependentElements(null);
            let currentDate = new Date().toISOString().split('T')[0];
            let bookId = $('#book_id').val();
            let document_date = $('#date').val();
            let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId +
                "&document_date=" +
                document_date;
            fetch(actionUrl).then(response => {
                return response.json().then(data => {
                    if (data.status == 200) {
                        resetParametersDependentElements(data.data);
                        $("#book_code_input").val(data.data.book_code);
                        $("#voucher_name").val($("#book_id option:selected").text());
                        if (!data.data.doc.document_number) {
                            $("#voucher_no").val('');
                            $('#doc_number_type').val('');
                            $('#doc_reset_pattern').val('');
                            $('#doc_prefix').val('');
                            $('#doc_suffix').val('');
                            $('#doc_no').val('');
                        } else {
                            $("#voucher_no").val(data.data.doc.document_number);
                            $('#doc_number_type').val(data.data.doc.type);
                            $('#doc_reset_pattern').val(data.data.doc.reset_pattern);
                            $('#doc_prefix').val(data.data.doc.prefix);
                            $('#doc_suffix').val(data.data.doc.suffix);
                            $('#doc_no').val(data.data.doc.doc_no);
                        }
                        if (data.data.doc.type == 'Manually') {
                            $("#voucher_no").attr('readonly', false);
                        } else {
                            $("#voucher_no").attr('readonly', true);
                        }

                    }
                    if (data.status == 404) {
                        $("#voucher_no").val('');
                        $('#doc_number_type').val('');
                        $('#doc_reset_pattern').val('');
                        $('#doc_prefix').val('');
                        $('#doc_suffix').val('');
                        $('#doc_no').val('');
                        showToast("error", data.message);
                    }
                });
            });
        }

        function handleRowClick(rowId) {
            $('.voucher_details').show();

            const row = $(`#item-details-body tr#${rowId}`);
            const ledgerName = row.find('td').eq(1).find('input[name^="ledger_name"]').val();
            const debitAmount = row.find('td').eq(3).find('input').val();
            // const debitAmountINR = row.find('td').eq(4).find('input').val();
            const creditAmount = row.find('td').eq(4).find('input').val();
            //const creditAmountINR = row.find('td').eq(6).find('input').val();
            const compCurrency = $('#comp_currency_code').val() || ''; // If #curre is a <select> dropdown
            const groupCurrency = $('#group_currency_code').val() || ''; // If #curre is a <select> dropdown
            const baseCurrency = $('#org_currency_code').val() || ''; // If #curre is a <select> dropdown
            const companyDebit = (debitAmount) * (parseFloat($('#comp_currency_exg_rate').val() || 1));
            const companyCredit = (creditAmount) * (parseFloat($('#comp_currency_exg_rate').val() || 1));
            const groupCredit = (creditAmount) * (parseFloat($('#group_currency_exg_rate').val() || 1));
            const groupDebit = (debitAmount) * (parseFloat($('#group_currency_exg_rate').val() || 1));
            const baseCredit = (creditAmount) * (parseFloat($('#org_currency_exg_rate').val() || 1));
            const baseDebit = (debitAmount) * (parseFloat($('#org_currency_exg_rate').val() || 1));



            const remark = $(`#hiddenRemarks_${rowId}`).val() ||
                'No remarks available'; // Fetch the remark, default to 'No remarks available'

            $('#ledger_name_details').text(ledgerName || '-'); // Update ledger name
            $('#company-currency').text(compCurrency); // Set company currency
            $('#company-debit').text(formatIndianNumber(companyDebit.toFixed(2))); // Set company debit amount
            $('#company-credit').text(formatIndianNumber(companyCredit.toFixed(2))); // Set company credit amount
            $('#group-currency').text(groupCurrency); // Set group currency
            $('#base-currency').text(baseCurrency); // Set group currency
            $('#group-debit').text(formatIndianNumber(groupDebit.toFixed(2))); // Set group debit amount
            $('#group-credit').text(formatIndianNumber(groupCredit.toFixed(2))); // Set group credit amount
            $('#base-debit').text(formatIndianNumber(baseDebit.toFixed(2))); // Set group debit amount
            $('#base-credit').text(formatIndianNumber(baseCredit.toFixed(2))); // Set group credit amount
            $('#remarks').text(remark); // Set remarks in the voucher details section
            $('#voucher-details-row').data('row-id', rowId); // Set row ID for the voucher details

        }

        function focusInput(inputElement) {
            // Check if the input value is "0"
            if (inputElement.value === "0" || inputElement.value === "0.00") {
                // Clear the input field
                inputElement.value = "";
            }
        }

        function checkFileTypeandSize(event) {
            $('#preview').empty();
            const file = event.target.files[0];

            if (file) {
                const maxSizeMB = 5;
                const fileSizeMB = file.size / (1024 * 1024);

                const videoExtensions = /(\.mp4|\.avi|\.mov|\.wmv|\.mkv)$/i;
                if (videoExtensions.exec(file.name)) {
                    showToast("error", "Video files are not allowed.");
                    event.target.value = "";
                    return;
                }

                if (fileSizeMB > maxSizeMB) {
                    showToast("error", "File size should not exceed 5MB.");
                    event.target.value = "";
                    return;
                }

                handleFileUpload(event, `#preview`);

            }
        }

        function handleFileUpload(event, previewElement) {
            var files = event.target.files;
            var previewContainer = $(previewElement); // The container where previews will appear
            previewContainer.empty(); // Clear previous previews

            if (files.length > 0) {
                // Loop through each selected file
                for (var i = 0; i < files.length; i++) {
                    // Get the file extension
                    var fileName = files[i].name;
                    var fileExtension = fileName.split('.').pop().toLowerCase(); // Get file extension

                    // Set default icon
                    var fileIconType = 'file-text'; // Default icon for unknown types

                    // Map file extension to specific Feather icons
                    switch (fileExtension) {
                        case 'pdf':
                            fileIconType = 'file'; // Icon for PDF files
                            break;
                        case 'doc':
                        case 'docx':
                            fileIconType = 'file'; // Icon for Word documents
                            break;
                        case 'xls':
                        case 'xlsx':
                            fileIconType = 'file'; // Icon for Excel files
                            break;
                        case 'png':
                        case 'jpg':
                        case 'jpeg':
                        case 'gif':
                            fileIconType = 'image'; // Icon for image files
                            break;
                        case 'zip':
                        case 'rar':
                            fileIconType = 'archive'; // Icon for compressed files
                            break;
                        default:
                            fileIconType = 'file'; // Default icon
                            break;
                    }

                    // Generate the file preview div dynamically
                    var fileIcon = `
                        <div class="image-uplodasection expenseadd-sign" data-file-index="${i}">
                            <i data-feather="${fileIconType}" class="fileuploadicon"></i>
                            <div class="delete-img text-danger" data-file-index="${i}">
                                <i data-feather="x"></i>
                            </div>
                        </div>
                    `;

                    // Append the generated fileIcon div to the preview container
                    previewContainer.append(fileIcon);
                }
                // Replace icons with Feather icons after appending the new elements
                feather.replace();
            }


            // Add event listener to delete the file preview when clicked
            previewContainer.find('.delete-img').click(function() {
                var fileIndex = $(this).parent().data('file-index'); // Get the correct index from parent
                removeFilePreview(fileIndex, previewContainer, event.target);
            });
        }

        // Function to remove a single file from the FileList
        function removeFilePreview(fileIndex, previewContainer, inputElement) {
            var dt = new DataTransfer(); // Create a new DataTransfer object to hold the remaining files
            var files = inputElement.files;

            // Loop through the files and add them to the DataTransfer object, except the one to delete
            for (var i = 0; i < files.length; i++) {
                if (i !== fileIndex) {
                    dt.items.add(files[i]); // Add file to DataTransfer if it's not the one being deleted
                }
            }

            // Update the input element with the new file list
            inputElement.files = dt.files;

            // Remove the preview of the deleted file
            previewContainer.children(`[data-file-index="${fileIndex}"]`).remove();

            // Now re-index the remaining file previews
            var remainingPreviews = previewContainer.children();
            remainingPreviews.each(function(index) {
                $(this).attr('data-file-index', index); // Update data-file-index correctly
                $(this).find('.delete-img').attr('data-file-index', index); // Also update delete button index
            });

            // Debugging logs
            console.log(`Remaining files after deletion: ${dt.files.length}`);
            console.log(`Remaining preview elements: ${remainingPreviews.length}`);

            // If no files are left after deleting, reset the file input
            if (dt.files.length === 0) { // Check the updated DataTransfer's files length
                inputElement.value = ""; // Clear the input value to reset it
            }
        }

        function updateRowNumbers() {
            $('#item-details-body tr').each(function(index) {
                // Update the number column (index starts at 0, so add 1)
                $(this).find('.number').text(index + 1);
            });
        }

        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                },
            });
            Toast.fire({
                icon,
                title
            });

        }
        $(document).on('change', '.costCenter', function() {
            var selectedValue = $(this).val(); // Get the selected cost center value
            $('.costCenter').val(selectedValue); // Set the same value for all dropdowns


        });
        // $(document).on('change', '.costCenter', function() {
        //     var selectedValue = $(this).val(); // Get the selected cost center value
        //     $('.costCenter').val(selectedValue); // Set the same value for all dropdowns
        // });

        $('#locations').on('change', function() {
            populateCostCenterDropdowns();
        });

        function submitForm(status) {
            var dateInput = document.getElementById('date');
            if (dateInput && !isDateAuthorized(dateInput.value)) {
                dateInput.value = '';
                dateInput.focus();
                return false;
            }
            
            // Validate required fields
            if (!$('#book_type_id').val()) {
                showToast('error', 'Please select voucher type');
                return false;
            }
            
            if (!$('#book_id').val()) {
                showToast('error', 'Please select series');
                return false;
            }
            
            if (!$('#date').val()) {
                showToast('error', 'Please select date');
                return false;
            }
            
            if (!$('#locations').val()) {
                showToast('error', 'Please select location');
                return false;
            }
            
            if (!$('#currency_id').val()) {
                showToast('error', 'Please select currency');
                return false;
            }
            
            if (!$('input[name="import_file"]')[0].files.length) {
                showToast('error', 'Please select import file');
                return false;
            }

            $('#document_status').val(status);
            $('#draft').attr('disabled', true);
            $('#submitted').attr('disabled', true);
            
            // Show loading
            $('.preloader').show();
            
            // Submit form via AJAX
            var formData = new FormData($('#voucherForm')[0]);
            
            $.ajax({
                url: $('#voucherForm').attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('.preloader').hide();
                    $('#draft').attr('disabled', false);
                    $('#submitted').attr('disabled', false);
                    
                    if (response.error) {
                        Swal.fire({
                            title: 'Import Failed!',
                            text: response.message,
                            icon: 'error',
                        });
                        if (response.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 2000);
                        }
                    } else {
                        Swal.fire({
                            title: 'Import Successful!',
                            text: response.message,
                            icon: 'success',
                        });
                        
                        // Get success/failed data from session via AJAX
                        $.ajax({
                            url: '{{ route("vouchers.import.success") }}',
                            type: 'GET',
                            success: function(successResponse) {
                                // Parse the response to extract data
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(successResponse, 'text/html');
                                
                                // Extract successful and failed vouchers data from the response
                                const successfulVouchers = @json(session('voucher_import_successful', []));
                                const failedVouchers = @json(session('voucher_import_failed', []));
                                
                                // Populate tables with results
                                populateVoucherTable('#success-table-body', successfulVouchers);
                                populateVoucherTable('#failed-table-body', failedVouchers);
                                
                                // Update counts
                                $('#success-count-badge').text(`Records Succeeded: ${successfulVouchers.length}`);
                                $('#success-count').text(`(${successfulVouchers.length})`);
                                $('#failed-count').text(`(${failedVouchers.length})`);
                                
                                $('.hide-this-section').show();
                                
                                // Show/hide export buttons based on results
                                if (failedVouchers.length > 0) {
                                    $('.editbtnNew').show();
                                } else {
                                    $('.editbtnNew').hide();
                                }
                                
                                // Setup export button handlers
                                // $('.exportBtn').off('click').on('click', function() {
                                //     const activeTab = $('.nav-link.active').attr('aria-controls');
                                //     if (activeTab === 'successful-records') {
                                //         window.location.href = '{{ route("vouchers.export.successful") }}';
                                //     } else if (activeTab === 'failed-records') {
                                //         window.location.href = '{{ route("vouchers.export.failed") }}';
                                //     }
                                // });
                            }
                        });
                    }
                },
                error: function(xhr) {
                    $('.preloader').hide();
                    $('#draft').attr('disabled', false);
                    $('#submitted').attr('disabled', false);
                    
                    var errorMessage = 'An error occurred while importing voucher';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showToast('error', errorMessage);
                }
            });
        }

        function populateVoucherTable(tableBodySelector, vouchers) {
            console.log('Populating table:', tableBodySelector, vouchers);
            const tableBody = $(tableBodySelector);
            tableBody.empty();

            if (vouchers.length > 0) {
                vouchers.forEach((voucher, index) => {
                    const row = `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="fw-bolder text-dark">${voucher.ledger_code || 'N/A'}</td>
                            <td>${voucher.ledger_name || 'N/A'}</td>
                            <td>${voucher.debit_amount || 0}</td>
                            <td>${voucher.credit_amount || 0}</td>
                            <td class="${voucher.status === 'success' ? 'text-success' : 'text-danger'}">
                                ${voucher.status === 'success' ? 'Success' : (voucher.status === 'failed' ? 'Failed' : voucher.status)}
                            </td>
                            <td class="${voucher.remarks && voucher.status === 'failed' ? 'text-danger' : 'text-success'}">
                                ${voucher.remarks || 'Successfully processed'}
                            </td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            } else {
                const noDataRow = `<tr><td colspan="8" class="text-center">No records found</td></tr>`;
                tableBody.append(noDataRow);
            }
        }

        function populateCostCenterDropdowns() {
            let selectedLocationIds = $('#locations').val();
            const costCenterSet = locationCostCentersMap.filter(center => {
                if (!center.location) return false;
                const locationArray = Array.isArray(center.location)
                    ? center.location.flatMap(loc => loc.split(','))
                    : [];
                return locationArray.includes(String(selectedLocationIds));
            });

            $('.costCenter').each(function() {
                let $dropdown = $(this);
                $dropdown.empty();
                costCenterSet.forEach((center) => {
                    $dropdown.append(`<option value="${center.id}">${center.name}</option>`);
                });
            });
        }
    </script>
@endsection

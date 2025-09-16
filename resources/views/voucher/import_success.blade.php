@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" type="text/css" href="{{ url('/app-assets/css/core/menu/menu-types/vertical-menu.css') }}">
    <style>
        .success-card {
            border-left: 4px solid #28a745;
        }
        .failed-card {
            border-left: 4px solid #dc3545;
        }
        .table-success {
            background-color: #d4edda;
        }
        .table-danger {
            background-color: #f8d7da;
        }
    </style>
@endsection

@section('content')
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-9 col-12 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0 border-0">Voucher Import Results</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('/') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('vouchers.index') }}">Vouchers</a></li>
                                    <li class="breadcrumb-item active">Import Results</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-md-end col-md-3 col-12 d-md-block d-none">
                    <div class="mb-1 breadcrumb-right">
                        <div class="dropdown">
                            <a href="{{ route('vouchers.index') }}" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                <i data-feather="arrow-left"></i> Back to Vouchers
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-body">
                <!-- Import Summary Cards -->
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-12">
                        <div class="card success-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bolder mb-75 text-success">{{ count($successfulVouchers) }}</h3>
                                        <p class="card-text">Successful Records</p>
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
                        <div class="card failed-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="fw-bolder mb-75 text-danger">{{ count($failedVouchers) }}</h3>
                                        <p class="card-text">Failed Records</p>
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
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="card-title">Export Options</h4>
                                    <div>
                                        @if(count($successfulVouchers) > 0)
                                            <a href="{{ route('vouchers.export.successful') }}" class="btn btn-success btn-sm me-1">
                                                <i data-feather="download"></i> Export Successful Records
                                            </a>
                                        @endif
                                        @if(count($failedVouchers) > 0)
                                            <a href="{{ route('vouchers.export.failed') }}" class="btn btn-danger btn-sm">
                                                <i data-feather="download"></i> Export Failed Records
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Successful Records Table -->
                @if(count($successfulVouchers) > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title text-success">
                                    <i data-feather="check-circle"></i> Successful Records ({{ count($successfulVouchers) }})
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Row #</th>
                                                <th>Ledger Code</th>
                                                <th>Ledger Name</th>
                                                <th>Debit Amount</th>
                                                <th>Credit Amount</th>
                                                <th>Cost Center</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($successfulVouchers as $voucher)
                                            <tr>
                                                <td>{{ $voucher['row_number'] ?? 'N/A' }}</td>
                                                <td>{{ $voucher['ledger_code'] ?? 'N/A' }}</td>
                                                <td>{{ $voucher['ledger_name'] ?? 'N/A' }}</td>
                                                <td>{{ number_format($voucher['debit_amount'] ?? 0, 2) }}</td>
                                                <td>{{ number_format($voucher['credit_amount'] ?? 0, 2) }}</td>
                                                <td>{{ $voucher['cost_center_id'] ?? 'N/A' }}</td>
                                                <td><span class="badge badge-light-success">{{ $voucher['status'] ?? 'Success' }}</span></td>
                                                <td>{{ $voucher['remarks'] ?? 'Successfully processed' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Failed Records Table -->
                @if(count($failedVouchers) > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title text-danger">
                                    <i data-feather="x-circle"></i> Failed Records ({{ count($failedVouchers) }})
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-danger">
                                            <tr>
                                                <th>Row #</th>
                                                <th>Ledger Code</th>
                                                <th>Ledger Name</th>
                                                <th>Debit Amount</th>
                                                <th>Credit Amount</th>
                                                <th>Status</th>
                                                <th>Error Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($failedVouchers as $voucher)
                                            <tr>
                                                <td>{{ $voucher['row_number'] ?? 'N/A' }}</td>
                                                <td>{{ $voucher['ledger_code'] ?? 'N/A' }}</td>
                                                <td>{{ $voucher['ledger_name'] ?? 'N/A' }}</td>
                                                <td>{{ number_format($voucher['debit_amount'] ?? 0, 2) }}</td>
                                                <td>{{ number_format($voucher['credit_amount'] ?? 0, 2) }}</td>
                                                <td><span class="badge badge-light-danger">{{ $voucher['status'] ?? 'Failed' }}</span></td>
                                                <td class="text-danger">{{ $voucher['remarks'] ?? 'Processing failed' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if(count($successfulVouchers) == 0 && count($failedVouchers) == 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h4 class="card-title">No Import Data Found</h4>
                                <p class="card-text">No import tracking data is available. Please try importing again.</p>
                                <a href="{{ route('vouchers.import') }}" class="btn btn-primary">
                                    <i data-feather="upload"></i> Import Vouchers
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <!-- END: Content-->
@endsection

@section('scripts')
    <script>
        // Clear session data after displaying results
        @if(count($successfulVouchers) > 0 || count($failedVouchers) > 0)
            // Optional: You can add JavaScript here to clear session data via AJAX if needed
        @endif
    </script>
@endsection

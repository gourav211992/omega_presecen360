@extends('layouts.app')

@section('content')
<!-- BEGIN: Content-->
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
        <div class="content-body">

            <section id="import-results">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs border-bottom" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#successful-records">
                                            Records Succeeded &nbsp;
                                            <span id="success-count">
                                                ({{ $data->filter(fn($d) => strtolower($d->status) == 'success')->count() }})
                                            </span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-danger" data-bs-toggle="tab" href="#failed-records">
                                            Records Failed &nbsp;
                                            <span id="failed-count">
                                                ({{ $data->filter(fn($d) => strtolower($d->status) == 'failed')->count() }})
                                            </span>
                                        </a>
                                    </li>
                                </ul>

                                <!-- Tab content -->
                                <div class="tab-content">

                                    {{-- ✅ Successful Records --}}
                                    <div class="tab-pane active" id="successful-records">
                                        <div class="table-responsive">
                                            <table class="table table-striped datatables-basic myrequesttablecbox">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Furbook Code</th>
                                                        <th>Location</th>
                                                        <th>Organization</th>
                                                        <th>Currency</th>
                                                        <th>Cost Center</th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th>Amount</th>
                                                        <th>Document Date</th>
                                                        <th>Remark</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($data->filter(fn($d) => strtolower($d->status) == 'success') as $item)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $item->furbooks_code }}</td>
                                                            <td>{{ $item?->location?->name }}</td>
                                                            <td>{{ $item?->organization?->name }}</td>
                                                            <td>{{ $item?->currency_code }}</td>
                                                            <td>{{ $item?->cost_center }}</td>
                                                            <td>{{ $item?->debit_amount }}</td>
                                                            <td>{{ $item?->credit_amount }}</td>
                                                            <td>{{ $item?->amount }}</td>
                                                            <td>{{ date('d-m-Y', strtotime($item->document_date)) }}</td>
                                                            <td>{{ $item?->remark }}</td>
                                                            <td><span class="text-success fw-bold">Success</span></td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="12" class="text-center">No records found</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- ❌ Failed Records --}}
                                    <div class="tab-pane" id="failed-records">
                                        <div class="table-responsive">
                                            <table class="table table-striped datatables-basic myrequesttablecbox">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Furbook Code</th>
                                                        <th>Location</th>
                                                        <th>Organization</th>
                                                        <th>Currency</th>
                                                        <th>Cost Center</th>
                                                        <th>Debit</th>
                                                        <th>Credit</th>
                                                        <th>Amount</th>
                                                        <th>Document Date</th>
                                                        <th>Remark</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($data->filter(fn($d) => strtolower($d->status) == 'failed') as $item)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $item->furbooks_code }}</td>
                                                            <td>{{ $item?->location?->name }}</td>
                                                            <td>{{ $item?->organization?->name }}</td>
                                                            <td>{{ $item?->currency_code }}</td>
                                                            <td>{{ $item?->cost_center }}</td>
                                                            <td>{{ $item?->debit_amount }}</td>
                                                            <td>{{ $item?->credit_amount }}</td>
                                                            <td>{{ $item?->amount }}</td>
                                                            <td>{{ date('d-m-Y', strtotime($item->document_date)) }}</td>
                                                            <td>{{ $item?->remark }}</td>
                                                            <td><span class="text-danger fw-bold">Failed</span></td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="12" class="text-center">No records found</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div> <!-- End tab-content -->

                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>
@endsection

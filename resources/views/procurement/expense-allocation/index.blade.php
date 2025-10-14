@extends('layouts.app')

@section('content')
    <!-- BEGIN: Content-->
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-5 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Expense Allocation</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="{{ url('/') }}">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Expense Allocation</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter"
                            data-bs-toggle="modal">
                            <i data-feather="filter"></i> Filter
                        </button>
                        @if (count($servicesBooks['services']) > 0)
                            <a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{ route('exp-allocation.create') }}">
                                <i data-feather="plus-circle"></i> Create
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="content-body">
                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="table-responsive">
                                    <table class="datatables-basic table myrequesttablecbox tableistlastcolumnfixed">
                                        <thead>
                                            <tr>
                                                <th>S No.</th>
                                                <th>Series</th>
                                                <th>Doc No.</th>
                                                <th>Doc Date</th>
                                                <th>Location</th>
                                                <th class="text-end">Allocation Amt</th>
                                                <th class="text-end">Landed Cost</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    {{-- END: Content --}}
    <div class="modal modal-slide-in fade filterpopuplabel" id="filter">
        <div class="modal-dialog sidebar-sm">
            <form class="add-new-record modal-content pt-0">
                <div class="modal-header mb-1">
                    <h5 class="modal-title" id="exampleModalLabel">Apply Filter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label" for="fp-range">Select Date</label>
                        {{-- <input type="text" id="fp-default" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" /> --}}
                        <input type="text" id="fp-range" class="form-control flatpickr-range bg-white"
                            placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Status</label>
                        <select class="form-select">
                            <option>Select</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="button" class="btn btn-primary data-submit mr-1">Apply</button>
                    <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
    <script type="text/javascript" src="{{ asset('assets/js/modules/common-datatable.js') }}"></script>
    <script>
        $(window).on("load", function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14,
                });
            }
        });
        $(document).ready(function() {
            function renderData(data) {
                return data ? data : '';
            }
            var columns = [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'book_code',
                    name: 'book_code',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('no-wrap');
                    }
                },
                {
                    data: 'document_number',
                    name: 'document_number',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('no-wrap');
                    }
                },
                {
                    data: 'document_date',
                    name: 'document_date',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('no-wrap');
                    }
                },
                {
                    data: 'location',
                    name: 'location',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('no-wrap');
                    }
                },
                {
                    data: 'total_allocated_value',
                    name: 'total_allocated_value',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-end');
                    }
                },
                {
                    data: 'total_landed_cost_value',
                    name: 'total_landed_cost_value',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('text-end');
                    }
                },
                {
                    data: 'document_status',
                    name: 'document_status',
                    render: renderData,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('no-wrap');
                    }
                },
            ];
            // Define your dynamic filters
            var filters = {
                book_id: '#filter-book',
                location_id: '#filter-location',
                date_range: '#fp-range',
                organization_id: '#filter-organization',
            };
            let title = 'Expense Allocation';
            var exportColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8]; // Columns to export
            var table = initializeDataTable('.datatables-basic',
                "{{ route('exp-allocation.index') }}",
                columns,
                filters,
                title,
                exportColumns,
                // [[1, "desc"]] // default order
            );
            $(".data-submit").on("click", function() {
                table.ajax.reload();
                $("#filter").modal('hide');
            });

            $(".reset-filter").on("click", function() {
                $("#filter-book").val(null).trigger("change");
                $("#filter-location").val(null).trigger("change");
                $("#filter-organization").val(null).trigger("change");
                $("#fp-range").val("");
                table.ajax.reload();
            });
        });
    </script>
@endsection

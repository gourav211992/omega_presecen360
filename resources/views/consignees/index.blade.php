@extends('layouts.app')

@section('content')
<!-- BEGIN: Content-->
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">

        <!-- Header -->
        <div class="content-header row">
            <div class="content-header-left col-md-6 col-6 mb-2">
                <h2 class="content-header-title float-start mb-0">Consignees</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('consignees.index') }}">Home</a></li>
                        <li class="breadcrumb-item active">Consignee List</li>
                    </ol>
                </div>
            </div>
            <div class="content-header-right text-end col-md-6 col-6 mb-2 mb-sm-0">
                <a href="{{ route('consignees.create') }}" class="btn btn-primary btn-sm">
                    <i data-feather="plus-circle"></i> Add New
                </a>
            </div>
        </div>

        <!-- Body -->
        <div class="content-body">
            <section id="consignee-datatable">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="table-responsive">
                                <table class="datatables-basic table">
                                    <thead>
                                        <tr>
                                            <th>Consignee Name</th>
                                            <th>Consignee Code</th>
                                            <th>Consignee Type</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Mobile</th>
                                            <th>Status</th>
                                            <th>Action</th>
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
<!-- END: Content-->
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        var dt_basic_table = $('.datatables-basic');
        function renderData(data) {
            return data ? data : 'N/A';
        }

        if (dt_basic_table.length) {
            var dt_basic = dt_basic_table.DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('consignees.index') }}",
               columns: [
                    { data: 'consignee_name', render: renderData },
                    { data: 'consignee_code', render: renderData },
                    { data: 'consignee_type', orderable: false, searchable: false },
                    { data: 'email', render: renderData },
                    { data: 'phone', render: renderData },
                    { data: 'mobile', render: renderData },
                    { data: 'status', render: renderData, orderable: false },
                    { data: 'action', orderable: false, searchable: false }
                ],
                dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 withoutheadbuttin dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                buttons: [
                    {
                        extend: 'collection',
                        className: 'btn btn-outline-secondary dropdown-toggle',
                        text: feather.icons['share-2'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export',
                        buttons: [
                            {
                                extend: 'print',
                                text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + 'Print',
                                className: 'dropdown-item',
                                title: 'Consignees',
                                exportOptions: { columns: [0, 1, 2, 3] }
                            },
                            {
                                extend: 'csv',
                                text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Csv',
                                className: 'dropdown-item',
                                title: 'Consignees',
                                exportOptions: { columns: [0, 1, 2, 3] }
                            },
                            {
                                extend: 'excel',
                                text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + 'Excel',
                                className: 'dropdown-item',
                                title: 'Consignees',
                                exportOptions: { columns: [0, 1, 2, 3] }
                            },
                            {
                                extend: 'pdf',
                                text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + 'Pdf',
                                className: 'dropdown-item',
                                title: 'Consignees',
                                exportOptions: { columns: [0, 1, 2, 3] }
                            },
                            {
                                extend: 'copy',
                                text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + 'Copy',
                                className: 'dropdown-item',
                                title: 'Consignees',
                                exportOptions: { columns: [0, 1, 2, 3] }
                            }
                        ],
                        init: function(api, node, config) {
                            $(node).removeClass('btn-secondary');
                            $(node).parent().removeClass('btn-group');
                            setTimeout(function() {
                                $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                            }, 50);
                        }
                    }
                ],
                drawCallback: function() {
                    feather.replace();
                },
                language: {
                    paginate: { previous: '&nbsp;', next: '&nbsp;' }
                },
                search: { caseInsensitive: true }
            });
        }
    });
</script>
@endsection

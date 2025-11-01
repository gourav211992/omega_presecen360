
@extends('layouts.app')
@section('styles')
<link rel="stylesheet" type="text/css" href="{{asset('app-assets/vendors/css/pickers/flatpickr/flatpickr.min.css')}}">
@endsection
@section('content')
<div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-5 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Equipment</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('equipment.index') }}">Home</a></li>
                                    <li class="breadcrumb-item active">Equipment List</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button> 
						<a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{ route('equipment.create') }}"><i data-feather="plus-circle"></i> Add New</a>
                    </div>
                </div>
            </div>
            <div class="content-body">

				<section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="table-responsive">
                                <table id="equipmentsTable" class="datatables-basic table myrequesttablecbox tableistlastcolumnfixed newerptabledesignlisthome"> 
                                    <thead>
                                        <tr>
                                            <th>S.No</th>
                                            <th>Date</th>
                                            <th>Equipment</th>
                                            <th>Organization</th>
                                            <th>Location</th>
                                            <th>Alias</th>
                                            <th>Category</th>
                                            <th>Maintenance Type</th>
                                            <th>Checklist Name</th>
                                            <th>Last Maint Date</th>
                                            <th>Maint Due Date</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      
                                    </tbody>
                                </table>
            								</div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal to add new record -->
                    <div class="modal modal-slide-in fade" id="modals-slide-in">
                        <div class="modal-dialog sidebar-sm">
                            <form class="add-new-record modal-content pt-0">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                                <div class="modal-header mb-1">
                                    <h5 class="modal-title" id="exampleModalLabel">New Record</h5>
                                </div>
                                <div class="modal-body flex-grow-1">
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-fullname">Full Name</label>
                                        <input type="text" class="form-control dt-full-name" id="basic-icon-default-fullname" placeholder="John Doe" aria-label="John Doe" />
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-post">Post</label>
                                        <input type="text" id="basic-icon-default-post" class="form-control dt-post" placeholder="Web Developer" aria-label="Web Developer" />
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-email">Email</label>
                                        <input type="text" id="basic-icon-default-email" class="form-control dt-email" placeholder="john.doe@example.com" aria-label="john.doe@example.com" />
                                        <small class="form-text"> You can use letters, numbers & periods </small>
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label" for="basic-icon-default-date">Joining Date</label>
                                        <input type="text" class="form-control dt-date" id="basic-icon-default-date" placeholder="MM/DD/YYYY" aria-label="MM/DD/YYYY" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="basic-icon-default-salary">Salary</label>
                                        <input type="text" id="basic-icon-default-salary" class="form-control dt-salary" placeholder="$12000" aria-label="$12000" />
                                    </div>
                                    <button type="button" class="btn btn-primary data-submit me-1">Submit</button>
                                    <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>
                 

            </div>
        </div>
</div>
	 
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
						  <input type="text" id="fp-range" class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
					</div>
					
					<div class="mb-1">
						<label class="form-label">Equipment Category</label>
						<select class="form-select" id="filter-equipment-category">
							<option value="">Select</option>
							@if(isset($equipmentCategories) && $equipmentCategories)
								@foreach($equipmentCategories as $id => $name)
									<option value="{{ $name }}">{{ $name }}</option>
								@endforeach
							@endif
						</select>
					</div>
					
					<div class="mb-1">
						<label class="form-label">Maintenance Type</label>
						<select class="form-select" id="filter-maintenance-type">
							<option value="">Select</option>
							@if(isset($maintenanceTypes) && $maintenanceTypes)
								@foreach($maintenanceTypes as $id => $name)
									<option value="{{ $name }}">{{ $name }}</option>
								@endforeach
							@endif
						</select>
					</div>
					
					<div class="mb-1">
						<label class="form-label">Status</label>
						<select class="form-select" id="filter-status">
							<option value="">Select</option>
							<option value="draft">Draft</option>
							<option value="submitted">Submitted</option>
							<option value="approved">Approved</option>
							<option value="rejected">Rejected</option>
						</select>
					</div>
					
                    
                    <div class="mb-1">
						<label class="form-label">Organization</label>
						<select id="filter-organization" class="form-select select2" multiple name="filter_organization">
							<option value="" disabled>Select</option>
							@foreach($mappings as $organization)
								<option value="{{ $organization->organization->id }}"
									{{ $organization->organization->id == $organizationId ? 'selected' : '' }}>
									{{ $organization->organization->name }}
								</option>
							@endforeach
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
<script type="text/javascript" src="{{asset('assets/js/modules/common-datatable.js')}}"></script>
<script src="{{asset('app-assets/vendors/js/pickers/flatpickr/flatpickr.min.js')}}"></script>
<script>
    $(window).on('load', function() {
        if (feather) {
            feather.replace({
                width: 14,
                height: 14
            });
        }
    });

    $(document).ready(function () {
        if ($.fn.DataTable.isDataTable('#equipmentsTable')) {
    $('#equipmentsTable').DataTable().destroy();
}

var dt_basic = $('#equipmentsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('equipments.data') }}",
        type: 'GET',
        data: function(d) {
            d.date_range = $('#fp-range').val();
            d.equipment_category_filter = $('#filter-equipment-category').val();
            d.maintenance_type_filter = $('#filter-maintenance-type').val();
            d.status_filter = $('#filter-status').val();
            d.organization_filter = $('#filter-organization').val();
            console.log('DataTable request data:', d);
            return d;
        }
    },
    columns: [
        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'created_at', name: 'created_at', width: '100px', className: 'text-nowrap'},
        {data: 'equipment', name: 'equipment'},
        {data: 'organization', name: 'organization'},
        {data: 'location', name: 'location'},
        {data: 'alias', name: 'alias', searchable: false},
        {data: 'category', name: 'category'},
        {data: 'maintenance_type', name: 'maintenance_type'},
        {data: 'checklists', orderable: false, searchable: true},
        {data: 'last_date', orderable: false, searchable: true},
        {data: 'due_date', orderable: false, searchable: true},
        {data: 'status', orderable: false, searchable: true},
        {data: 'action', orderable: false, searchable: false}
    ],
    pageLength: 10,
    order: [[0, 'asc']],
    dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    buttons: [
            {
                extend: 'collection',
                className: 'btn btn-outline-secondary dropdown-toggle',
                text: feather.icons['share'].toSvg({ class: 'font-small-4 mr-50' }) + ' Export',
                buttons: [
                    {
                        extend: 'print',
                        text: feather.icons['printer'].toSvg({ class: 'font-small-4 mr-50' }) + ' Print',
                        className: 'dropdown-item',
                        title: 'Equipment',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
                    },
                    {
                        extend: 'csv',
                        text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + ' CSV',
                        className: 'dropdown-item',
                        title: 'Equipment',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
                    },
                    {
                        extend: 'excel',
                        text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + ' Excel',
                        className: 'dropdown-item',
                        title: 'Equipment',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
                    },
                    {
                        extend: 'pdf',
                        text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + ' PDF',
                        className: 'dropdown-item',
                        title: 'Equipment',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
                    },
                    {
                        extend: 'copy',
                        text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + ' Copy',
                        className: 'dropdown-item',
                        title: 'Equipment',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11] }
                    }
                ],
                init: function (api, node, config) {
                    $(node).removeClass('btn-secondary').parent().removeClass('btn-group');
                    setTimeout(function () {
                        $(node).closest('.dt-buttons').removeClass('btn-group').addClass('d-inline-flex');
                    }, 50);
                }
            }
         ],
   
    drawCallback: function () {
        feather.replace(); // Initialize Feather icons for action buttons
        // Initialize Bootstrap dropdowns
        $('[data-bs-toggle="dropdown"]').dropdown();
    },
    language: {
        paginate: { previous: '&nbsp;', next: '&nbsp;' }
    }
    });



    $('#equipmentsTable tbody').on('click', 'tr', function () {
        $(this).addClass('trselected').siblings().removeClass('trselected');
    });

    $(document).on('keydown', function(e) { 
        let selected = $('.trselected');
        if (e.which == 38) {
            selected.prev('tr').addClass('trselected').siblings().removeClass('trselected');
        } else if (e.which == 40) {
            selected.next('tr').addClass('trselected').siblings().removeClass('trselected');
        } 
        if ($('.trselected').length) {
            $('html, body').scrollTop($('.trselected').offset().top - 100); 
        }
    });

    // Apply Filters
    $('.data-submit').on('click', function() {
        // Reload the DataTable with new filter values
        dt_basic.ajax.reload();
        $('#filter').modal('hide');
    });

    // Reset Filters
    $('button[type="reset"]').on('click', function() {
        $('#fp-range').val('');
        $('#filter-equipment-category').val('');
        $('#filter-maintenance-type').val('');
        $('#filter-status').val('');
        
        // Reset Select2 dropdown
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filter-organization').val(null).trigger('change');
        } else {
            $('#filter-organization').val('');
        }
        
        // Reload the DataTable to clear filters
        dt_basic.ajax.reload();
    });

    // Initialize flatpickr for date range
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#fp-range', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true,
            placeholder: 'YYYY-MM-DD to YYYY-MM-DD'
        });
    }
    
    // Initialize Select2 for organization dropdown
    if (typeof $.fn.select2 !== 'undefined') {
        $('#filter-organization').select2({
            placeholder: 'Select Organizations',
            allowClear: true,
            dropdownParent: $('#filter')
        });
    }
});

</script>
@endsection


@extends('layouts.app')
@section('styles')
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
                                            <th>#</th>
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
<!--                        <input type="text" id="fp-default" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" />-->
						  <input type="text" id="fp-range" class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
					</div>
					
					<div class="mb-1">
						<label class="form-label">Equipemnt</label>
						<select class="form-select">
							<option>Select</option>
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
        ajax: "{{ route('equipments.data') }}",
        pageLength: 10,
        order: [[0, 'asc']],
        dom: 
          '<"d-flex justify-content-between align-items-center mx-2 row"' +
            '<"col-sm-12 col-md-6"l>' +
            '<"col-sm-12 col-md-3 withoutheadbuttin dt-action-buttons text-end"B>' +
            '<"col-sm-12 col-md-3"f>' +
          '>t' +
          '<"d-flex justify-content-between mx-2 row"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
          '>',  
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-outline-secondary',
                text: feather.icons['file'].toSvg({ class: 'font-small-4 me-50' }) + 'Excel',
                exportOptions: { columns: ':visible' }
            }
        ],
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'equipment', name: 'erp_equipments.name'},
            {data: 'organization', name: 'organization.name'},
            {data: 'location', name: 'location.store_name',
                render: function (data, type, row) {
                    return `<div title="${row.location_full ?? ''}">${data ?? ''}</div>`;
                }
            },
            {data: 'alias', name: 'alias'},
            {data: 'category', name: 'category.name'},
            {data: 'maintenance_type', orderable: false, searchable: false},
            {data: 'checklists', orderable: false, searchable: false},
            {data: 'last_date', orderable: false, searchable: false},
            {data: 'due_date', orderable: false, searchable: false},
            {data: 'status', orderable: false, searchable: false},
            {data: 'action', orderable: false, searchable: false}
        ],
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
});

</script>
@endsection

@extends('layouts.app')
@section('content')

   <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-5 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">Defect Notifications</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{route('/')}}">Home</a></li>  
                                    <li class="breadcrumb-item active">Defect Notification List</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button> 
						<a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{route('defect-notification.create')}}"><i data-feather="plus-circle"></i> Add New</a> 
                    </div>
                </div>
            </div>
            <div class="content-body">
                
				
				<section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								
								   
                                <div class="table-responsive">
									<table id="defect-notifications-table" class="datatables-basic table myrequesttablecbox "> 
                                        <thead>
                                             <tr>
                                                <th height="18">S.No</th>
												<th>Date</th>
												<th>Series</th>
												<th>Doc No.</th>
												<th>Equipment</th>
												<th>Category</th>
												<th>Location</th>
												<th>Defect Type</th>
												<th>Problem</th>
												<th>Priority</th>
												<th class="text-end">Status</th>
												<th class="text-end">Action</th>
										  </tr>
											</thead>
											<tbody>
												<!-- Data will be loaded via Ajax -->
											</tbody>
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


    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

    <!-- BEGIN: Footer-->
    <!-- END: Footer-->
	
	 
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
						<label class="form-label">Series</label>
						<select class="form-select" id="filter-series">
							<option value="">Select</option>
							@foreach($series as $seriesItem)
								<option value="{{ $seriesItem->book_code }}">{{ $seriesItem->book_code }}</option>
							@endforeach
						</select>
					</div> 
                    
                    <div class="mb-1">
						<label class="form-label">Equipment</label>
						<select class="form-select select2" id="filter-equipment">
							<option value="">Select</option>
							@foreach($equipments as $equipment)
								<option value="{{ $equipment->id }}">{{ $equipment->name }}</option>
							@endforeach
						</select>
					</div>
                    
                    <div class="mb-1">
						<label class="form-label">Category</label>
						<select class="form-select select2" id="filter-category">
							<option value="">Select</option>
							@foreach($categories as $category)
								<option value="{{ $category->id }}">{{ $category->name }}</option>
							@endforeach
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
					 
				</div>
				<div class="modal-footer justify-content-start">
					<button type="button" class="btn btn-primary data-submit mr-1">Apply</button>
					<button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
				</div>
			</form>
		</div>
	</div>

@endsection

@section('styles')
<style>
    #defect-notifications-table td:nth-child(2) {
        white-space: nowrap !important;
        min-width: 100px;
    }
    #defect-notifications-table td:nth-child(3) {
        white-space: nowrap !important;
        min-width: 120px;
    }
    #defect-notifications-table td:nth-child(4) {
        white-space: nowrap !important;
        min-width: 100px;
        text-align: center !important;
    }
    .datatables-basic td {
        vertical-align: middle;
    }
    .datatables-basic th {
        text-align: center;
        vertical-align: middle;
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('assets/js/modules/common-datatable.js')}}"></script>
<script>
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

    @if (session('success'))
        showToast("success", "{{ session('success') }}");
    @endif

    @if (session('error'))
        showToast("error", "{{ session('error') }}");
    @endif

    @if ($errors->any())
        showToast('error',
            "@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach"
        );
    @endif

    $('#defect-notifications-table').DataTable({
        processing: true,
        serverSide: true,
        colReorder: true,
        ajax: {
            url: '{{ route("defect-notification.index") }}',
            type: 'GET',
            data: function(d) {
                d.date_range = $('#fp-range').val();
                d.series_filter = $('#filter-series').val();
                d.equipment_filter = $('#filter-equipment').val();
                d.category_filter = $('#filter-category').val();
                d.filter_organization = $('#filter-organization').val();
                d.status_filter = $('#filter-status').val();
                console.log('DataTable request data:', d);
                return d;
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'document_date', name: 'document_date', searchable: true },
            { data: 'series', name: 'book_id', searchable: true },
            { data: 'document_number', name: 'document_number', searchable: true },
            { data: 'equipment', name: 'equipment.name', searchable: true },
            { data: 'category', name: 'category.name', searchable: true },
            { data: 'location', name: 'location.store_name', searchable: true },
            { data: 'defect_type', name: 'defectType.name', searchable: true },
            { data: 'problem', name: 'problem', searchable: true },
            { data: 'priority', name: 'priority', searchable: true },
            { data: 'status', name: 'document_status', orderable: false, searchable: true },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']], // sort by document_number
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
                        title: 'Maintenance Work Orders',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
                    },
                    {
                        extend: 'csv',
                        text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + ' CSV',
                        className: 'dropdown-item',
                        title: 'Maintenance Work Orders',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
                    },
                    {
                        extend: 'excel',
                        text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + ' Excel',
                        className: 'dropdown-item',
                        title: 'Maintenance Work Orders',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
                    },
                    {
                        extend: 'pdf',
                        text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + ' PDF',
                        className: 'dropdown-item',
                        title: 'Maintenance Work Orders',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
                    },
                    {
                        extend: 'copy',
                        text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + ' Copy',
                        className: 'dropdown-item',
                        title: 'Maintenance Work Orders',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
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
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: 'No defect notifications found',
            zeroRecords: 'No matching defect notifications found'
        },
        drawCallback: function(settings) {
            // Re-initialize Feather icons after table draw
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    });

    // Filter data is now loaded directly from controller - no AJAX needed

    // Apply Filters
    $('.data-submit').on('click', function() {
        // Reload the DataTable with new filter values
        $('#defect-notifications-table').DataTable().ajax.reload();
        $('#filter').modal('hide');
    });

    // Reset Filters
    $('button[type="reset"]').on('click', function() {
        $('#fp-range').val('');
        $('#filter-series').val('');
        $('#filter-status').val('');
        
        // Reset Select2 dropdowns
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filter-equipment').val(null).trigger('change');
            $('#filter-category').val(null).trigger('change');
            $('#filter-organization').val(null).trigger('change');
        } else {
            $('#filter-equipment').val('');
            $('#filter-category').val('');
            $('#filter-organization').val('');
        }
        
        // Reload the DataTable to clear filters
        $('#defect-notifications-table').DataTable().ajax.reload();
    });

    // Initialize date picker and select2 on page load
    $(document).ready(function() {
        // Initialize flatpickr for date range
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#fp-range', {
                mode: 'range',
                dateFormat: 'Y-m-d',
                allowInput: true,
                placeholder: 'YYYY-MM-DD to YYYY-MM-DD'
            });
        }
        
        // Initialize Select2 for dropdowns if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('#filter-equipment').select2({
                placeholder: 'Select Equipment',
                allowClear: true,
                dropdownParent: $('#filter')
            });
            
            $('#filter-category').select2({
                placeholder: 'Select Category',
                allowClear: true,
                dropdownParent: $('#filter')
            });
            
            $('#filter-organization').select2({
                placeholder: 'Select Organizations',
                allowClear: true,
                dropdownParent: $('#filter')
            });
        }
    });
</script>
 
@endsection
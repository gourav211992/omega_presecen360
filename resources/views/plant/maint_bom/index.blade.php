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
                            <h2 class="content-header-title float-start mb-0">Maintenance BOM</h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>  
                                    <li class="breadcrumb-item active">BOM List</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button> 
						<a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{route('maint-bom.create')}}"><i data-feather="plus-circle"></i> Add New</a> 
                    </div>
                </div>
            </div>
            <div class="content-body">
                 
                
				
				<section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								
								   
                                <div class="table-responsive">
									<table id="maint-bom-table" class="datatables-basic table myrequesttablecbox"> 
                                        <thead>
                                             <tr>
												<th>#</th>
												<th>Date</th>
												<th>Series</th>
												<th>Doc No.</th>
												<th>BOM Name</th>
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
<!--                        <input type="text" id="fp-default" class="form-control flatpickr-basic" placeholder="YYYY-MM-DD" />-->
						  <input type="text" id="fp-range" class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
					</div>
					
					<div class="mb-1">
						<label class="form-label">Series</label>
						<select class="form-select" id="filter-series">
							<option value="">Select</option>
						</select>
					</div> 
                    
                    <div class="mb-1">
						<label class="form-label">BOM Name</label>
						<select class="form-select select2" id="filter-bom-name">
							<option value="">Select</option> 
						</select>
					</div>
                    
                     
                    
                    <div class="mb-1">
						<label class="form-label">Status</label>
						<select class="form-select" id="filter-status">
							<option value="">Select</option>
							<option value="draft">Draft</option>
							<option value="approved">Approved</option>
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
<script>
    var table = $('#maint-bom-table').DataTable({
    processing: true,
    serverSide: true,
    colReorder: true,
    ajax: {
        url: '{{ route("maint-bom.index") }}',
        type: 'GET',
        data: function(d) {
            console.log('DataTable request data:', d);
            return d;
        }
    },
    columns: [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'document_date', name: 'document_date', searchable: true },
        { data: 'series', name: 'book_code', searchable: true },
        { data: 'document_number', name: 'document_number', searchable: true },
        { data: 'bom_name', name: 'bom_name', searchable: true },
        { data: 'status', name: 'document_status', orderable: false, searchable: true },
        { data: 'action', name: 'action', orderable: false, searchable: false }
    ],
    order: [[3, 'desc']], // sort by document_number
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
    buttons: [
        // export buttons...
    ],
    drawCallback: function () {
        feather.replace();
        $(document).on("click", "#maint-bom-table tbody tr", (e) => {
            $("#maint-bom-table tr").removeClass("trselected");
            $(e.target).closest("tr").addClass("trselected");
        });
    },
    language: {
        processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
        paginate: { previous: '&nbsp;', next: '&nbsp;' }
    },
    search: { caseInsensitive: true }
});

// Populate Series Dropdown
function populateSeriesDropdown() {
    $.ajax({
        url: '{{ route("maint-bom.get-series") }}',
        type: 'GET',
        success: function(data) {
            var seriesSelect = $('#filter-series');
            seriesSelect.empty();
            seriesSelect.append('<option value="">Select</option>');
            
            $.each(data, function(index, series) {
                seriesSelect.append('<option value="' + series.book_code + '">' + series.book_code + '</option>');
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading series:', error);
        }
    });
}

// Populate BOM Names Dropdown
function populateBomNamesDropdown() {
    $.ajax({
        url: '{{ route("maint-bom.get-bom-names") }}',
        type: 'GET',
        success: function(data) {
            var bomNameSelect = $('#filter-bom-name');
            bomNameSelect.empty();
            bomNameSelect.append('<option value="">Select</option>');
            
            $.each(data, function(index, bomName) {
                bomNameSelect.append('<option value="' + bomName + '">' + bomName + '</option>');
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading BOM names:', error);
        }
    });
}

// Apply Filters
$('.data-submit').on('click', function() {
    var dateRange = $('#fp-range').val();
    var series = $('#filter-series').val();
    var bomName = $('#filter-bom-name').val();
    var status = $('#filter-status').val();
    
    // Apply column-specific filters
    table.column(1).search(dateRange); // Date column
    table.column(2).search(series); // Series column
    table.column(4).search(bomName); // BOM Name column
    table.column(5).search(status); // Status column
    
    table.draw();
    $('#filter').modal('hide');
});

// Reset Filters
$('button[type="reset"]').on('click', function() {
    $('#fp-range').val('');
    $('#filter-series').val('');
    $('#filter-bom-name').val('');
    $('#filter-status').val('');
    
    // Clear all column filters
    table.columns().search('').draw();
});

// Initialize dropdowns on page load
$(document).ready(function() {
    populateSeriesDropdown();
    populateBomNamesDropdown();
});

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
      Toast.fire({ icon, title });
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
</script>
@endsection
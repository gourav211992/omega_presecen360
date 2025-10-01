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
												<th class="text-end">Status</th>
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
						<select class="form-select">
							<option>Select</option>
						</select>
					</div> 
                    
                    <div class="mb-1">
						<label class="form-label">BOM Name</label>
						<select class="form-select select2">
							<option>Select</option> 
						</select>
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
<script type="text/javascript" src="{{asset('assets/js/modules/common-datatable.js')}}"></script>
<script>
   $(function() {
      // Initialize DataTable with proper configuration like maint-wo table
      $('#maint-bom-table').DataTable({
         processing: true,
         serverSide: true,
         colReorder: true,  // Enable column reordering
         ajax: {
            url: '{{ route("maint-bom.index") }}',
            type: 'GET'
         },
         columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-nowrap' },
            { data: 'document_date', name: 'document_date', className: 'fw-bolder text-dark text-nowrap' },
            { data: 'series', name: 'book_id', orderable: false, className: 'text-nowrap' },
            { data: 'document_number', name: 'document_number', className: 'text-nowrap' },
            { data: 'bom_name', name: 'bom_name', className: 'text-nowrap' },
            { data: 'status', name: 'document_status', orderable: false, searchable: false, className: 'tableactionnew text-end' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
         ],
         order: [[3, 'desc']], // Default sort by document number descending
         pageLength: 10, // Changed from 25 to 10
         lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
         columnDefs: [
            {
                targets: "_all",
                defaultContent: "N/A", // Set default content like book table
            },
         ],
         dom: '<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3 dt-action-buttons text-end"B><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>', // Proper DOM with export buttons
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
                        title: 'Maintenance BOM',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'csv',
                        text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + ' CSV',
                        className: 'dropdown-item',
                        title: 'Maintenance BOM',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'excel',
                        text: feather.icons['file'].toSvg({ class: 'font-small-4 mr-50' }) + ' Excel',
                        className: 'dropdown-item',
                        title: 'Maintenance BOM',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'pdf',
                        text: feather.icons['clipboard'].toSvg({ class: 'font-small-4 mr-50' }) + ' PDF',
                        className: 'dropdown-item',
                        title: 'Maintenance BOM',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
                    },
                    {
                        extend: 'copy',
                        text: feather.icons['copy'].toSvg({ class: 'font-small-4 mr-50' }) + ' Copy',
                        className: 'dropdown-item',
                        title: 'Maintenance BOM',
                        exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
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
            // Add row selection functionality
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

      $('div.head-label').html('<h6 class="mb-0">Maintenance BOM</h6>');

      // Flatpickr for filter input
      $('#fp-range').flatpickr({
         mode: 'range',
         dateFormat: 'Y-m-d'
      });
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
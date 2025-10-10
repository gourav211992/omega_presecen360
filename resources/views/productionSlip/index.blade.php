@extends('layouts.app')

@section('content')

<div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header row">
                <div class="content-header-left col-md-5 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0">
                                {{$typeName}}
                            </h2>
                            <div class="breadcrumb-wrapper">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#">Home</a></li>  
                                    <li class="breadcrumb-item active">List</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal" ><i data-feather="filter"></i> Filter</button> 
                        @if ($create_button)
                        <a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{$create_route}}"><i data-feather="plus-circle"></i>
                            {{'Create'}}
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
                                                <th>S.No</th>
                                                <th style="display:none;"></th> 
                                                <th>Date</th>
                                                <th>Series</th>
                                                <th>Doc No.</th>
                                                <th>Location</th>
                                                <th>Sub Location</th>
                                                <th>Station</th>
                                                <th>SO No.</th>
                                                <th>MO No.</th>
                                                <th>Product</th>
                                                <th>Type</th>
                                                <th>Produced Qty</th>
                                                <th>Accepted (A)</th>
                                                <th>Substandard (B)</th>
                                                <th>Rejected (C)</th>
                                                <th class="{{$isWipQty ? '' : 'd-none'}}">Wip Qty</th>
                                                <th class="{{$isWipQty ? '' : 'd-none'}}">Total Qty</th>
                                                {{-- <th>Value</th> --}}
                                                <th>Shift</th>
                                                {{-- <th class = "numeric-alignment">Amount</th> --}}
                                                <th>Created By</th>
                                                <th style = 'text-align:center'>Status</th>
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
                <div class="modal-body flex-grow-1"  id = "auto-complete-filters-row">
                    
                
                
                </div>
                <div class="modal-footer justify-content-start">
                    <button type="button" class="btn btn-primary data-submit mr-1" onclick="applyFilters();">Apply</button>
                    <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    @endsection
    @section('scripts')
<script type="text/javascript" src="{{asset('assets/js/modules/common-datatable.js')}}"></script>
<script>
    let reportDataTableInstance = null;

    $(document).ready(function() {
    function renderData(data) {
        return data ? data : 'N/A'; 
    }
    let showWipQty = '{{$isWipQty}}' ? true : false;
    var columns = [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'id', name: 'id', visible: false, searchable: false },
        { data: 'document_date', name: 'document_date', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'book_name', name: 'book_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'document_number', name: 'document_number', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'store_name', name: 'store_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'sub_store_name', name: 'sub_store_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'station_name', name: 'station_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'so_no', name: 'so_no', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'mo_no', name: 'mo_no', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'mo_product', name: 'mo_product', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'type', name: 'type', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'produced_qty', name: 'produced_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'accepted_qty', name: 'accepted_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'subprime_qty', name: 'subprime_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'rejected_qty', name: 'rejected_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'wip_qty', className: showWipQty ? '' : 'd-none', name: 'wip_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'total_qty', className: showWipQty ? '' : 'd-none', name: 'total_qty', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        // { data: 'value', name: 'value', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
        //        $(td).addClass('no-wrap');
        //     }
        // },
        { data: 'shift_name', name: 'shift_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'created_by', name: 'created_by', render: renderData, createdCell: function(td, cellData, rowData, row, col)
            {
               $(td).addClass('no-wrap');
            }
        },
        { data: 'document_status', name: 'document_status', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
        },
    ];
   let filtersComponents = @json($autoCompleteFilters);
   let filters = {
        'date_range' : '#document_date_filter'
    };
    filtersComponents.forEach(filter => {
        filters[filter.requestName] = "#" + (filter.id + "_input");
    });
    var exportColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    reportDataTableInstance = initializeDataTable('.datatables-basic', 
        "{{ $redirect_url }}", 
        columns,
        filters, 
        "{{$typeName}}",  
        exportColumns,  
        [[1, 'desc']],
        'landscape'
    );
});



// autocomplete get data
let filtersComponents = @json($autoCompleteFilters);

let autoCompleteFiltersContainer = document.getElementById('auto-complete-filters-row');

filtersComponents.forEach(filterData => {
   if (filterData.type === 'auto_complete') {
      autoCompleteFiltersContainer.innerHTML += `
            <div class="mb-1">
                  <label class="form-label">${filterData.label}</label>
                  <input type="text" id = "${filterData.id}" placeholder="Select" class="form-control mw-100 ledgerselecct ui-autocomplete-input reportFilter" autocomplete="off">
                  <input class = "reportFilter" type='hidden' name="${filterData.requestName}" id = "${filterData.id + "_input"}"/>
            </div>
      `;
   } else if (filterData.type === 'input_text') {
      autoCompleteFiltersContainer.innerHTML += `
            <div class="mb-1">
                  <label class="form-label">${filterData.label}</label>
                  <input type="text" name = "${filterData.requestName}" id = "${filterData.id + "_input"}" placeholder="Search" class="form-control mw-100 reportFilter">
            </div>
      `;
   } else if (filterData.type === 'date_range') {
      autoCompleteFiltersContainer.innerHTML += `
            <div class="mb-1">
               <label class="form-label">${filterData.label}</label>
               <input type="text" class="form-control flatpickr-range flatpickr-input flatpickr-filter" name="${filterData.requestName}" id="${filterData.id + "_input"}" />
            </div>
      `;
   }

});

filtersComponents.forEach(filterData => {
   if (filterData.type === 'auto_complete') {
      initializeAutoCompleteFilter(filterData.id, filterData.term, filterData.value_key, filterData.label_key, filterData.dependent);
   }
});
function initializeAutoCompleteFilter(selector, type, valueKey, labelKey, dependentElements = []) {
    $("#" + selector).autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '/search',
                method: 'GET',
                dataType: 'json',
                data: {
                    q: request.term,
                    type: type,
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            id: item[valueKey],
                            label: item[labelKey],
                        };
                    }));
                },
                error: function(xhr) {
                    console.error('Error fetching customer data:', xhr.responseText);
                }
            });
        },
        minLength: 0,
        select: function(event, ui) {
            var $input = $(this);
            var itemCode = ui.item.label;
            var itemId = ui.item.id;

            $input.val(itemCode);
            $("#" + selector + "_input").val(itemId);
            //Reset the dependent elements
            dependentElements.forEach(elementId => {
                let dependentElement = document.getElementById(elementId);
                let dependentElementInput = document.getElementById(elementId + "_input");
                if (dependentElement) {
                    dependentElement.value = "";
                }
                if (dependentElementInput) {
                    dependentElementInput.value = "";
                }
            });
            return false;
        },
        change: function(event, ui) {
            if (!ui.item) {
                $("#" + selector + "_input").val("");
            }
        }
    }).focus(function() {
        if (this.value === "") {
            $(this).autocomplete("search", "");
        }
    });
}
function applyFilters()
{
    reportDataTableInstance.ajax.reload(); 
    $("#filter").modal('hide');
}
</script>
@endsection
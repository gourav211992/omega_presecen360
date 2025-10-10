@extends('layouts.app')
@section('content')
@php
$routeAlias = $servicesBooks['services'][0]?->alias ?? null;
if($routeAlias == App\Helpers\ConstantHelper::BOM_SERVICE_ALIAS)
{
   $routeAlias = 'bill-of-material';
} else {
   $routeAlias = 'quotation-bom';
}
@endphp

<div class="app-content content ">
   <div class="content-overlay"></div>
   <div class="header-navbar-shadow"></div>
   <div class="content-wrapper container-xxl p-0">
      <div class="content-header row">
         @include('layouts.partials.breadcrumb-list', [
         'title' => $routeAlias == 'quotation-bom' ? 'Quotation BOM' : 'Production BOM',
         'menu' => 'Home',
         'menu_url' => url('home'),
         'sub_menu' => 'BOM List'
         ])
         <div class="content-header-right text-sm-end col-md-7 mb-50 mb-sm-0">
            <div class="form-group breadcrumb-right">
               <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button>
               @if(count($servicesBooks['services']))
               <a href="{{url($routeAlias)}}/import" class="btn btn-warning btn-sm mb-50 mb-sm-0">
                  <i data-feather="upload"></i>Import
              </a>

               <a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{url($routeAlias)}}/create"><i data-feather="plus-circle"></i> Add New</a>
               @endif

               <a class="btn btn-dark btn-sm mb-50 mb-sm-0" href="{{ route('transactions.report', ['serviceAlias' => 'bom']) }}"><i data-feather="bar-chart-2"></i>Report</a>
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
                                 <th>Date</th>
                                 <th>Series</th>
                                 <th>Doc No.</th>
                                 {{-- <th>Rev No.</th> --}}
                                 <th>Product Code</th>
                                 <th>Product Name</th>
                                 <th>Attributes</th>
                                 <th>UOM</th>
                                 <th>Production Type</th>
                                 <th>Components</th>
                                 @if($canView)
                                    <th>Item Cost</th>
                                    <th>Overheads</th>
                                    <th>Total Cost</th>
                                 @endif
                                 <th>Created By</th>
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
        <div class="modal-body flex-grow-1" id = "auto-complete-filters-row">
					 <!-- Multiple Dynamic filters -->
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
 $(window).on("load", function () {
     if (feather) {
         feather.replace({
             width: 14,
             height: 14,
         });
     }
 });
let reportDataTableInstance = null;
$(document).ready(function() {
  function renderData(data) {
       return data ? data : '';
   }


    var columns = [
        { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
        { data: 'document_date', name: 'document_date', render: renderData },
        { data: 'book_name', name: 'book_name', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
         },
        { data: 'document_number', name: 'document_number', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('no-wrap');
            }
         },
        // { data: 'revision_number', name: 'revision_number', render: renderData },
        { data: 'item_code', name: 'item_code', render: renderData },
        { data: 'item_name', name: 'item_name', render: renderData },
        { data: 'attributes', name: 'attributes', render: renderData },
        { data: 'uom_name', name: 'uom_name', render: renderData },
        { data: 'production_type', name: 'production_type', render: renderData },
        { data: 'components', name: 'components', render: renderData },
        @if($canView)
        { data: 'total_item_value', name: 'total_item_value', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('text-end');
            },

         },
        { data: 'overhead', name: 'overhead', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('text-end');
            },

         },
        { data: 'total_cost', name: 'total_cost', render: renderData, createdCell: function(td, cellData, rowData, row, col) {
               $(td).addClass('text-end');
            },

         },
         @endif
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
    // Define your dynamic filters
   let filtersComponents = @json($autoCompleteFilters);
   let filters = {
        'date_range' : '#document_date_filter'
    };
    filtersComponents.forEach(filter => {
        filters[filter.requestName] = "#" + (filter.id + "_input");
    });
    var exportColumns = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]; // Columns to export
    reportDataTableInstance = initializeDataTable('.datatables-basic',
        "{{ route('bill.of.material.index') }}"+'?type='+'{{@$servicesBooks['services'][0]?->alias}}',
        columns,
        filters,  
        'Bill Of Material', 
        exportColumns,  

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

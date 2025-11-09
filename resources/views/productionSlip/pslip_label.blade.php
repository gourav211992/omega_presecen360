@extends('layouts.app')
@use(App\Helpers\ConstantHelper)
@section('styles')
<style>
#rescdule .table-responsive {
    overflow-y: auto;
    max-height: 350px; /* Set the height of the scrollable body */
    position: relative;
}

#rescdule .po-order-detail {
    width: 100%;
    border-collapse: collapse;
}

#rescdule .po-order-detail thead {
    position: sticky;
    top: 0; /* Stick the header to the top of the table container */
    background-color: white; /* Optional: Make sure header has a background */
    z-index: 1; /* Ensure the header stays above the body content */
}
#rescdule .po-order-detail th {
    background-color: #f8f9fa; /* Optional: Background for the header */
    text-align: left;
    padding: 8px;
}

#rescdule .po-order-detail td {
    padding: 8px;
}
/* .nav-tabs .nav-link.tab-error-highlight {
    border-bottom: 3px solid red !important;
    color: red !important;
} */

</style>
@endsection
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">

            <div class="content-header pocreate-sticky">
				<div class="row">
                    @include('layouts.partials.breadcrumb-add-edit', [
                        'title' => $title,
                        'menu' => 'Home',
                        'menu_url' => url('home'),
                        'sub_menu' =>$sub
                    ])
					<div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
						<div class="form-group breadcrumb-right" id = "buttonsDiv">
                            @if (!empty($bundlePack))
                                
                                <button id="print_qr_btn" type="button" class="btn btn-dark btn-sm ">
                                    <i class="fa fa-print"></i> Print QR
                                </button>
                            @else
                                <button id="print_labels_btn" type="button" class="btn btn-dark btn-sm ">
                                    <i class="fa fa-print"></i> Print Labels
                                </button>
                            @endif

						</div>
					</div>
				</div>
			</div>

            <div class="content-body">
                <section id="basic-datatable">
                    {{-- print check box option --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card quation-card">
                              
                                @if(!empty($bundlePack))
                              
                                 <div class="card-body">
                                    {{--Start here write code for bundle QR Printing --}}
                                    <div class="table-responsive pomrnheadtffotsticky">
                                        <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad" data-json-key="components_json" data-row-selector="tr[id^='item_row_0']" >
                                            <thead>
                                                <tr>
                                                  
                                                    <th width="150px">Product Code</th>
                                                    <th width="240px">Product Name</th>
                                                    <th max-width="180px">Attributes</th>
                                                    <th>Bundle No</th>
                                                    <th>Bundle Qty</th>
                                                    <th class="customernewsection-form">
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox" class="form-check-input" id="select_all_items_checkbox">
                                                            <label class="form-check-label" for="select_all_items_checkbox" ></label>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="mrntableselectexcel" id="item_header">
                                            @if (isset($slip))
                                            @foreach ($bundlePack as $packIndex => $pack)
                                

                                                <tr id = "item_row_{{$packIndex}}" class = "item_header_rows" onclick = "onItemClick('{{$packIndex}}');" data-detail-id = "{{$pack -> id}}" data-id = "{{$pack -> id}}">
                                                    <input type = 'hidden' name = "package_id[]" value = "{{$pack->id}}">
                                                   
                                                    <td class="poprod-decpt">
                                                        <input type="text" id="item_code_{{$packIndex}}" name="item_code[{{$packIndex}}]" class="form-control mw-100"  value="{{$pack?->pslipItem?->item_code}}" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text" id="item_name_{{$packIndex}}" name="item_name[{{$packIndex}}]" class="form-control mw-100"  value="{{$pack?->pslipItem?->item_name}}" readonly>
                                                    </td>
                                                    <td class="poprod-decpt" id="attribute_section_{{$packIndex}}">
                                                        <div class="d-flex align-items-center gap-2">
                                                    

                                                        {{-- Show badges if attributes exist --}}
                                                    @if(!empty($pack?->pslipItem?->item_attributes_array()))
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($pack?->pslipItem?->item_attributes_array() as $attr)
                                                                @php
                                                                    // Safely get values_data (works for array or object)
                                                                    $valuesData = is_array($attr)
                                                                        ? ($attr['values_data'] ?? [])
                                                                        : ($attr->values_data ?? []);

                                                                    // Safely get group name
                                                                    $groupName = is_array($attr)
                                                                        ? ($attr['group_name'] ?? '')
                                                                        : ($attr->group_name ?? '');

                                                                    // Safely get selected value
                                                                    $selectedValue = collect($valuesData)
                                                                        ->firstWhere('selected', true)?->value;
                                                                @endphp

                                                                @if($selectedValue)
                                                                    <span class="badge rounded-pill badge-light-primary border">
                                                                        <strong>{{ $groupName }}</strong>: {{ $selectedValue }}
                                                                    </span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    </div>

                                                    </td>
                                                    <td class="poprod-decpt">
                                                        <input type="text" id = "package_qty_{{$packIndex}}"  value = "{{$pack->bundle_no}}"  name = "package_qty[{{$packIndex}}]" readonly class="form-control mw-100 text-start" />
                                                    </td>  
                                                    <td class="poprod-decpt">
                                                        <input type="text" id = "item_qty_{{$packIndex}}"  value = "{{$pack->qty}}"  name = "item_qty[{{$packIndex}}]" class="form-control mw-100 text-start" />
                                                    </td>
                                                    <td class="customernewsection-form">
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox" class="form-check-input item_row_checks" id="item_checkbox_{{$packIndex}}" del-index = "{{$packIndex}}">
                                                            <label class="form-check-label" for="item_checkbox_{{$packIndex}}"></label>
                                                        </div>
                                                    </td>
                                                </tr>

                                            @endforeach
                                            @endif


                                            </tbody>

                                        </table>
                                    </div>
                                  
                                {{--End here write code for bundle QR Printing --}}
                                @else
                                 <div class="card-body">
                                    <div class="table-responsive pomrnheadtffotsticky">
                                        <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad" data-json-key="components_json" data-row-selector="tr[id^='item_row_0']" >
                                            <thead>
                                                <tr>
                                                  
                                                    <th width="150px">Product Code</th>
                                                    <th width="240px">Product Name</th>
                                                    <th max-width="180px">Attributes</th>
                                                    <th>Package No</th>
                                                    <th>Qty</th>
                                                    <th class="customernewsection-form">
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox" class="form-check-input" id="select_all_items_checkbox">
                                                            <label class="form-check-label" for="select_all_items_checkbox" ></label>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="mrntableselectexcel" id="item_header">
                                            @if (isset($slip))
                                          
                                            @foreach ($slip -> items as $slipItemIndex => $slipItem)
                                                @php
                                                    $package = $slipItem->item->packagingDetails;
                                                    if (empty($package) || $package->isEmpty()) {
                                                        // Create a default-like object/collection
                                                        $package = collect([
                                                            (object)[
                                                                'id' => 0,     
                                                                'packet_no' =>1,
                                                                'count' => 1  
                                                            ]
                                                        ]);
                                                    }
                                                @endphp
                                                @foreach ($package as $pack)

                                                <tr id = "item_row_{{$slipItemIndex}}" class = "item_header_rows" onclick = "onItemClick('{{$slipItemIndex}}');" data-detail-id = "{{$slipItem -> id}}" data-id = "{{$slipItem -> id}}">
                                                    <input type = 'hidden' name = "pslip_item_id[]" value = "{{$slipItem->id}}">
                                                   
                                                    <td class="poprod-decpt">
                                                        <input type="text" id="item_code_{{$slipItemIndex}}" name="item_code[{{$slipItemIndex}}]" class="form-control mw-100"  value="{{$slipItem?->item_code}}" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text" id="item_name_{{$slipItemIndex}}" name="item_name[{{$slipItemIndex}}]" class="form-control mw-100"  value="{{$slipItem?->item_name}}" readonly>
                                                    </td>
                                                    <td class="poprod-decpt" id="attribute_section_{{$slipItemIndex}}">
                                                        <div class="d-flex align-items-center gap-2">
                                                    

                                                        {{-- Show badges if attributes exist --}}
                                                    @if(!empty($slipItem->item_attributes_array()))
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($slipItem->item_attributes_array() as $attr)
                                                                @php
                                                                    // Safely get values_data (works for array or object)
                                                                    $valuesData = is_array($attr)
                                                                        ? ($attr['values_data'] ?? [])
                                                                        : ($attr->values_data ?? []);

                                                                    // Safely get group name
                                                                    $groupName = is_array($attr)
                                                                        ? ($attr['group_name'] ?? '')
                                                                        : ($attr->group_name ?? '');

                                                                    // Safely get selected value
                                                                    $selectedValue = collect($valuesData)
                                                                        ->firstWhere('selected', true)?->value;
                                                                @endphp

                                                                @if($selectedValue)
                                                                    <span class="badge rounded-pill badge-light-primary border">
                                                                        <strong>{{ $groupName }}</strong>: {{ $selectedValue }}
                                                                    </span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    </div>

                                                    </td>
                                                    <td class="poprod-decpt">
                                                        <input type="text" id = "package_qty_{{$slipItemIndex}}"  value = "{{$pack->packet_no}}/{{$package->count()}}"  name = "package_qty[{{$slipItemIndex}}]" readonly class="form-control mw-100 text-start" />
                                                        <input type="hidden" id = "package_id_{{$slipItemIndex}}"  value = "{{$pack->id}}"  name = "package_id[{{$slipItemIndex}}]"/>
                                                    </td>  
                                                    <td class="poprod-decpt">
                                                        <input type="text" id = "item_qty_{{$slipItemIndex}}"  value = "{{$slipItem->qty}}"  name = "item_qty[{{$slipItemIndex}}]" class="form-control mw-100 text-start" />
                                                    </td>
                                                    <td class="customernewsection-form">
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox" class="form-check-input item_row_checks" id="item_checkbox_{{$slipItemIndex}}" del-index = "{{$slipItemIndex}}">
                                                            <label class="form-check-label" for="item_checkbox_{{$slipItemIndex}}"></label>
                                                        </div>
                                                    </td>
                                                </tr>

                                                @endforeach
                                            @endforeach
                                            @endif


                                            </tbody>

                                        </table>
                                    </div>
                                @endif

                                 </div>
                            </div>
                        </div>
                    </div>
                    
                </section>  
            </div>    
        </div>
</div>
@section('scripts')
<script>
    setTimeout(() => {
        let storeId = $("#store_id_input").val() || '';
        locationOnChange(storeId);
        $(".select2").select2();
    }, 0);
    // Sub Store
    function locationOnChange(storeId = '') {
        let actionUrl = '{{route("production.slip.substore")}}'+'?store_id='+storeId;
        fetch(actionUrl).then(response => {
            return response.json().then(data => {
                if (data.status == 200) {
                    let subStore = ``;
                    let selId = @json($slip->sub_store_id ?? '');
                    if(data?.data?.sub_store?.length) {
                        data?.data?.sub_store?.forEach(element => {
                            let selected = element.id == selId ? 'selected' : '';
                            subStore += `<option value="${element.id}" ${selected} data-station-wise-consumption="${element.station_wise_consumption}">${element.name}</option>`;
                        });
                
                    }
                    $("#sub_store_id").empty().append(subStore);

                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const order = @json(isset($slip) ? $slip : null);
        onSeriesChange(document.getElementById('service_id_input'), order ? false : true);
    });
   function resetSeries()
    {
        document.getElementById('series_id_input').innerHTML = '';
    }

    function onSeriesChange(element, reset = true)
    {
        resetSeries();
        $.ajax({
            url: "{{route('book.service-series.get')}}",
            method: 'GET',
            dataType: 'json',
            data: {
                menu_alias: "{{request() -> segments()[0]}}",
                service_alias: element.value,
                book_id : reset ? null : "{{isset($slip) ? $slip -> book_id : null}}"
            },
            success: function(data) {
                if (data.status == 'success') {
                    let newSeriesHTML = ``;
                    data.data.forEach((book, bookIndex) => {
                        newSeriesHTML += `<option value = "${book.id}" ${bookIndex == 0 ? 'selected' : ''} >${book.book_code}</option>`;
                    });
                    document.getElementById('series_id_input').innerHTML = newSeriesHTML;
                } else {
                    document.getElementById('series_id_input').innerHTML = '';
                }
            },
            error: function(xhr) {
                console.error('Error fetching customer data:', xhr.responseText);
                document.getElementById('series_id_input').innerHTML = '';
            }
        });
    }
document.addEventListener('DOMContentLoaded', function() {

    const selectAllCheckbox = document.getElementById('select_all_items_checkbox');
    const itemCheckboxes = document.querySelectorAll('.item_row_checks');

    // ✅ Select/Deselect all when header checkbox is clicked
    selectAllCheckbox.addEventListener('change', function() {
        itemCheckboxes.forEach(cb => cb.checked = this.checked);
    });

    // ✅ Update header checkbox state when individual ones are toggled
    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const total = itemCheckboxes.length;
            const checked = document.querySelectorAll('.item_row_checks:checked').length;
            selectAllCheckbox.checked = total === checked;
        });
    });

});


document.addEventListener('DOMContentLoaded', function() {

    const printBtn = document.getElementById('print_labels_btn');
    const baseUrl = "{{ route('production.slip.generate-labels', $slip->id) }}";

    printBtn.addEventListener('click', function() {
        // Collect checked rows
        const checkedData = [];

        document.querySelectorAll('.item_row_checks:checked').forEach(cb => {
            const row = cb.closest('tr');
            
            const itemIdInput = row.querySelector('input[name="pslip_item_id[]"]');
            const packageIdInput = row.querySelector('input[name^="package_id"]');
            const qtyInput = row.querySelector('input[name^="item_qty"]');

            if (itemIdInput) {
                checkedData.push({
                    id: itemIdInput.value,
                    package_id: packageIdInput ? packageIdInput.value : '',
                    qty: qtyInput ? qtyInput.value : ''
                });
            }
        });

        if (checkedData.length === 0) {
            alert('Please select at least one item to print.');
            return;
        }

        // Build query string for multiple arrays
        const query = checkedData.map(data =>
            `ids[]=${encodeURIComponent(data.id)}&package_ids[]=${encodeURIComponent(data.package_id)}&qtys[]=${encodeURIComponent(data.qty)}`
        ).join('&');

        const url = `${baseUrl}?${query}`;

        console.log('Opening:', url);
        window.open(url, '_blank');
    });
    });

document.addEventListener('DOMContentLoaded', function() {

    // for QR
    const qrbaseUrl = "{{ route('production.slip.generate-qr', $slip->id) }}";

    const printQRBtn = document.getElementById('print_qr_btn');
      printQRBtn.addEventListener('click', function() {
        // Collect checked rows
        const checkedData = [];

        document.querySelectorAll('.item_row_checks:checked').forEach(cb => {
            const row = cb.closest('tr');
            
            const packageIdInput = row.querySelector('input[name^="package_id"]');
            const qtyInput = row.querySelector('input[name^="item_qty"]');

            if (packageIdInput) {
                checkedData.push({
                    package_id: packageIdInput ? packageIdInput.value : '',
                    qty: qtyInput ? qtyInput.value : ''
                });
            }
        });

        if (checkedData.length === 0) {
            alert('Please select at least one item to print.');
            return;
        }

        // Build query string for multiple arrays
        const query = checkedData.map(data =>
            `package_ids[]=${encodeURIComponent(data.package_id)}&qtys[]=${encodeURIComponent(data.qty)}`
        ).join('&');

        const url = `${qrbaseUrl}?${query}`;

        console.log('Opening:', url);
        window.open(url, '_blank');
    });


});



</script>
@endsection
@endsection
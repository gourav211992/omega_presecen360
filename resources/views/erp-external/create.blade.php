@extends('layouts.app')
@section('content')

<form class="ajax-input-form" method="POST" action="{{ route('external-integration.store') }}" data-redirect="{{ url('/external-integration') }}">
    @csrf
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">New External Integration</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href = "#">Home</a></li>
                                        <li class="breadcrumb-item active">Add New</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                        <div class="form-group breadcrumb-right">
                           <a href="{{ route('external-integration.index') }}" class="btn btn-secondary btn-sm"><i data-feather="arrow-left-circle"></i> Back</a>
                            <button type="submit" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                <i data-feather="check-circle"></i> Create
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="content-body">
                <section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body customernewsection-form">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="newheader border-bottom mb-2 pb-25">
                                                <h4 class="card-title text-theme">Basic Information</h4>
                                                <p class="card-text">Fill the details</p>
                                            </div>
                                        </div>
                                     
                                        <div class="col-md-9">

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Organization<span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <select name="organization_id" id="organization_id" class="form-select select2" onchange="changeOrg()">
                                                        <option value="">Select Organization</option>
                                                        @foreach($allOrganizations as $organization)
                                                            <option value="{{ $organization->id }}" data-address='@json($organization->addresses->first())'>
                                                                {{ $organization->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Store<span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <select name="store_id" class="form-select select2" id = "store_id" >
                                                        <option value="">Select Store</option>

                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"> 
                                                    <label class="form-label">Trip Book Series <span class="text-danger">*</span></label>  
                                                </div>  
                                                <div class="col-md-5">
                                                <select class="form-select select2" id="trip_book_id" name="trip_book_id">
                                                    @foreach($tripbook as $tbook)
                                                        <option value="{{$tbook->id}}">{{ucfirst($tbook->book_code)}}</option>
                                                    @endforeach 
                                                </select>  
                                                <input type="hidden" name="trip_book_code" id="trip_book_code">
                                                </div>
                                            </div>

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"> 
                                                    <label class="form-label">SO Book Series <span class="text-danger">*</span></label>  
                                                </div>  
                                                <div class="col-md-5">
                                                <select class="form-select select2" id="so_book_id" name="so_book_id">
                                                    @foreach($sobook as $sbook)
                                                        <option value="{{$sbook->id}}">{{ucfirst($sbook->book_code)}}</option>
                                                    @endforeach 
                                                </select>  
                                                <input type="hidden" name="so_book_code" id="so_book_code">
                                                </div>
                                            </div>  
                                            
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3"> 
                                                    <label class="form-label">DNote Book Series <span class="text-danger">*</span></label>  
                                                </div>  
                                                <div class="col-md-5">
                                                <select class="form-select select2" id="dn_book_id" name="dn_book_id">
                                                    @foreach($dnbook as $dbook)
                                                        <option value="{{$dbook->id}}">{{ucfirst($dbook->book_code)}}</option>
                                                    @endforeach 
                                                </select>  
                                                <input type="hidden" name="dn_book_code" id="dn_book_code">
                                                </div>
                                            </div>

                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="hidden" name="customer_id" id="customer_id">
                                                    <input type="text" id="customer" placeholder="Select" class="form-control mw-100 ledgerselecct" name="customer" />
                                               
                                                </div>
                                            </div>
                                           
                                        </div>
                                     <div class="col-md-3 border-start">
                                        <div class="row align-items-center mb-2">
                                                    <div class="col-md-12"> 
                                                        <label class="form-label text-primary"><strong>Status</strong></label>   
                                                        <div class="demo-inline-spacing">
                                                            @foreach ($status as $option)
                                                                <div class="form-check form-check-primary mt-25">
                                                                    <input
                                                                        type="radio"
                                                                        id="status_{{ strtolower($option) }}"
                                                                        name="status"
                                                                        value="{{ $option }}"
                                                                        class="form-check-input"
                                                                        {{ $option == 'active' ? 'checked' : '' }} >
                                                                        <label class="form-check-label fw-bolder" for="status_{{ strtolower($option) }}">
                                                                            {{ucfirst($option)}}
                                                                        </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        @error('status')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div> 
                                            </div> 
                                        </div>

                                        {{-- write code here add multiple mapping  --}}
                                    
                                        <div class="tab-content ">
                                            <div class="tab-pane active transaction_service_tab" id="Pattern">
                                                <div class="table-responsive-md">
                                                    <table
                                                        class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border">
                                                        <thead>
                                                            <tr>
                                                                <th width = "20px">#</th>
                                                                <th width="300px"> Stock Type<span class="text-danger">*</span></th>
                                                                <th width="300px">Sub Store<span class="text-danger">*</span></th>
                                                                <th width="150px" class="text-center">Is Primary<span class="text-danger">*</span></th>
                                                                <th class = "center-align-content" width = "20px">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="item-details-body">
                                                            <tr>
                                                                <td class="serial-number">1</td>
                                                                <td>
                                                                    <input type="text" id="stock_type1" placeholder="Enter Stock Type" class="form-control mw-100" name="stock_type[]" onkeyup="getfetchSubStore(event);" required/>
                                                                </td>
                                                                
                                                                <td>
                                                                    <select
                                                                        class="form-select mw-100 select2 subLocationSelect" data-id="1" name="subLocation_id[]" id="subLocation_id1" required >
                                                                        <option disabled selected value="">Select </option>

                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <input type="checkbox" name="is_primary[]" id="is_primary[]" value="0">
                                                                </td>

                                                                <td class = "center-align-content">
                                                                    <a href="#" class="text-primary add_number_pattern">
                                                                        <i data-feather="plus-square"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        
                                        </div>
                                                

                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</form>
@endsection
@section('scripts')
<script>
     initializeAutocompleteCustomer("#customer");

       function initializeAutocompleteCustomer(selector, type) {
            $(selector).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type:'customer'
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    label: `${item.company_name} (${item.customer_code})`,
                                    code: item.customer_code || '',
                                    name:item.display_name || item.company_name,
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
                    var customerCode = ui.item.code;
                    var customerName = ui.item.name;
                    var customerId = ui.item.id;
                    $input.val(customerCode);
                    $("#customer_id").val(customerId);
                    $("#customer_name").val(customerName);
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $("#customer_id").val('');
                        $("#customer_name").val('');
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                    $("#customer_id").val('');
                    $("#customer_name").val('');
                }
            });
    }


    // add stock type mapping code 
    document.addEventListener('DOMContentLoaded', function() {
            // Add new item row
            document.querySelector('.add_number_pattern').addEventListener('click', function(e) {
                e.preventDefault();
                let rowCount = document.querySelectorAll('#item-details-body tr').length;
                rowCount++
                let newRow = `<tr>
                            <td class="serial-number"></td>
                            <td>
                                <div class="position-relative">
                                   <input type="text" id="stock_type`+rowCount+`" placeholder="Enter Stock Type" class="form-control mw-100" name="stock_type[]" onkeyup="getfetchSubStore(event);" required />

                                </div>
                            </td>
                            <td>
                            <div class="position-relative">
                                    <select
                                        class="form-select mw-100 select2 subLocationSelect" data-id="1" name="subLocation_id[]" id="subLocation_id`+rowCount+`" required  >
                                        <option disabled selected value="">Select </option>

                                    </select>
                            </div>
                            </td>
                           
                            <td class="text-center">
                                <input type="checkbox" name="is_primary[]" id="is_primary[]">
                            </td>

                            <td class = "center-align-content"><a href="#" class="text-danger remove-item"><i data-feather="trash-2"></i></a></td>
                        </tr>`;
                document.querySelector('#item-details-body').insertAdjacentHTML('beforeend', newRow);

                feather.replace({
                    width: 14,
                    height: 14
                });

                $('.select2').select2();
                updateSerialNumbers();
            });
            initializeDynamicFieldDropdown();
    });
    function updateSerialNumbers() {
            $('#item-details-body tr:visible').each(function (index) {
                $(this).find('.serial-number').text(index + 1);
            });
        }

        $(document).on('click', '.remove-item', function () {
            const $row = $(this).closest('tr');
            resetHiddenRow($row)
            updateSerialNumbers();
        });

        function resetHiddenRow($row) {
            $row.show();
            $row.find('input, select, textarea').prop('disabled', false);
            $row.find('input, select, textarea').each(function () {
                if (this.tagName === 'SELECT') {
                    $(this).val('').trigger('change');
                } else if ($(this).attr('type') === 'radio' || $(this).attr('type') === 'checkbox') {
                    $(this).val('').prop('checked', false);
                }  else {
                    $(this).val('');
                }
            });
            $row.hide();
    }

    function changeOrg(){
        let org=$("#organization_id").val();
        if(org){
            getStore(org);
        }
    }   
    
 
    // get store data
    function getStore(org) {
        $.ajax({
            url: '{{route("external-integration.getStore")}}',   
            type: 'GET',
            data: { org_id: org }, 
            dataType: 'json',
            success: function(response) {
                let $storeSelect = $("#store_id");
                $.each(response, function(index, store) {
                    $storeSelect.append(
                        `<option value="${store.id}">${store.store_name}</option>`
                    );
                });
            },
            error: function(xhr) {
                console.error("Error fetching store data:", xhr.responseText);
            }
        });
    }  
    function getfetchSubStore(event) {
        let $input = $(event.target);
        let type = $input.val();
        let $tr = $input.closest('tr');           
        let $substoreSelect = $tr.find('.subLocationSelect'); 
        let store_id=$("#store_id").val();
        if (type) {
            getSubStore(store_id,type, $substoreSelect);
        }
    }
  function getSubStore(store_id, type, $select) {
    $.ajax({
        url: '{{ route("external-integration.getSubstore") }}',
        type: 'GET',
        data: { 
            type: type,
            store_id: store_id
         },
        dataType: 'json',
        success: function(response) {
            $select.empty().append('<option value="">Select</option>');

            $.each(response, function(index, subStore) {
                $select.append(
                    `<option value="${subStore.id}">${subStore.name}</option>`
                );
            });
        },
        error: function(xhr) {
            console.error("Error fetching subStore data:", xhr.responseText);
        }
    });
}

    // Using event delegation
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[name="is_primary"]')) {
            if (e.target.checked) {
                // Uncheck all other checkboxes
                document.querySelectorAll('input[name="is_primary"]').forEach(function(other) {
                    if (other !== e.target) {
                        other.checked = false;
                    }
                });
            }
        }
    });

</script>
@endsection


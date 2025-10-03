@extends('layouts.app')
@section('content')
    <form class="ajax-input-form" method="POST" action="{{ route('external-integration.update', $external->id) }}"
        data-redirect="{{ url('/external-integration') }}">
        <input type="hidden" name="id" value="{{ $external->id }}">
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
                                    <h2 class="content-header-title float-start mb-0">Update External Integration</h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href = "#">Home</a></li>
                                            <li class="breadcrumb-item active">Update</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                <a href="{{ route('external-integration.index') }}" class="btn btn-secondary btn-sm"><i
                                        data-feather="arrow-left-circle"></i> Back</a>
                                <button type="button"
                                    class="btn btn-danger btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light delete-btn"
                                    data-url="{{ route('external-integration.destroy', $external->id) }}"
                                    data-redirect="{{ route('external-integration.index') }}"
                                    data-message="Are you sure you want to delete this record?">
                                    <i data-feather="trash-2" class="me-50"></i> Delete
                                </button>
                                <button type="submit" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                                    <i data-feather="check-circle"></i> Update
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
                                                        <select name="organization_id" id="organization_id" class="form-select select2" onchange="changeOrg()" disabled>
                                                            <option value="">Select Organization</option>
                                                            @foreach($allOrganizations as $organization)
                                                                <option value="{{ $organization->id }}" {{$organization->id == $external->organization_id?'selected':''}} data-address='@json($organization->addresses->first())'>
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
                                                        <select name="store_id" class="form-select select2" id ="store_id" disabled>
                                                            <option value="">Select Store</option>

                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Trip Book Series <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" id="trip_book_id" name="trip_book_id">
                                                            @foreach ($tripbook as $tbook)
                                                                <option value="{{ $tbook->id }}" {{$tbook->id == $external->trip_book_id?'selected':''}}> {{ ucfirst($tbook->book_code) }}</option>
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
                                                            @foreach ($sobook as $sbook)
                                                                <option value="{{ $sbook->id }}" {{$sbook->id == $external->so_book_id?'selected':''}}> {{ ucfirst($sbook->book_code) }} </option>
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
                                                            <option value="{{$dbook->id}}" {{$dbook->id == $external->dnote_book_id?'selected':''}}>{{ucfirst($dbook->book_code)}}</option>
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
                                                   
                                                        <input type="text" id="customer" name="customer_code" value="{{ old('customer_code', ($external->customer ? $external->customer->company_name . ' (' . $external->customer->customer_code . ')' : '')) }}" class="form-control">
                                                        <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', $external->customer_id ?? '') }}">
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="col-md-3 border-start">
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-md-12">
                                                        <label
                                                            class="form-label text-primary"><strong>Status</strong></label>
                                                        <div class="demo-inline-spacing">
                                                            @foreach ($status as $option)
                                                                <div class="form-check form-check-primary mt-25">
                                                                    <input type="radio" id="status_{{ strtolower($option) }}" name="status" value="{{ $option }}" class="form-check-input"  {{ $external->status == $option ? 'checked' : '' }}>
                                                                    <label class="form-check-label fw-bolder" for="status_{{ strtolower($option) }}"> {{ ucfirst($option) }} </label>
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
                                                    
                                                            @if(isset($external->stockStoreMapping) && !empty($external->stockStoreMapping ))

                                                                @foreach ($external->stockStoreMapping as $key=>$item)
                                                                    <tr>
                                                                        <td class="serial-number">{{$key+1}}</td>
                                                                        <td>
                                                                            <input type="text" id="stock_type_{{$key+1}}" placeholder="Enter Stock Type" class="form-control mw-100" name="data[{{$key+1}}][stock_type]" onkeyup="getfetchSubStore(event);"  value="{{$item->stock_type}}" readonly required/>
                                                                            <input type="hidden" id="stock_id_{{$key+1}}" class="form-control mw-100 stock_id_hidden" name="data[{{$key+1}}][stock_id]" value="{{$item->id}}"/>
                                                                        </td>
                                                                        
                                                                        <td>
                                                                            <select
                                                                                class="form-select mw-100 select2 subLocationSelect" data-id="1" name="data[{{$key+1}}][subLocation_id]" id="subLocation_id_{{$key+1}}" disabled required >
                                                                               
                                                                                <option disabled selected value="{{$item->subStore?->id}}">{{$item->subStore?->name}} </option>

                                                                            </select>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <input type="checkbox" name="data[{{$key+1}}][is_primary]" id="is_primary_{{$key+1}}" value="1" @if($item->is_primary == 1) checked @endif>
                                                                        </td>
                                                                        @if ($key==0)
                                                                            <td class = "center-align-content">
                                                                                <a href="#" class="text-primary add_number_pattern">
                                                                                    <i data-feather="plus-square"></i>
                                                                                </a>
                                                                          
                                                                                <a href="#" class="text-danger remove-item"><i data-feather="trash-2"></i></a>
                                                                            </td>
                                                                            @else
                                                                            <td class = "center-align-content">
                                                                                <a href="#" class="text-danger remove-item"><i data-feather="trash-2"></i></a>
                                                                            </td>
                                                                        @endif
                                                                    </tr>
                                                                @endforeach
                                                            @else
                                                            <tr>
                                                                <td class="serial-number">1</td>
                                                                <td>
                                                                    <input type="text" id="stock_type_1" placeholder="Enter Stock Type" class="form-control mw-100" name="data[1][stock_type]" onkeyup="getfetchSubStore(event);" required/>
                                                                </td>
                                                                
                                                                <td>
                                                                    <select
                                                                        class="form-select mw-100 select2 subLocationSelect" data-id="1" name="data[1][subLocation_id]" id="subLocation_id_1" required >
                                                                        <option disabled selected value="">Select </option>

                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <input type="checkbox" name="data[1][is_primary]" id="is_primary_1">
                                                                </td>

                                                                <td class = "center-align-content">
                                                                    <a href="#" class="text-primary add_number_pattern">
                                                                        <i data-feather="plus-square"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            @endif
                                                           

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
    $(document).ready(function () {
        changeOrg();

        localStorage.setItem('deletedItemIds', JSON.stringify([]));


        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();

            $row = $(this).closest('tr');
            let stockId = $row.find('.stock_id_hidden').first().val();

            if (stockId) {
                let deletedIds = JSON.parse(localStorage.getItem('deletedItemIds'));
                deletedIds.push(stockId);
                localStorage.setItem('deletedItemIds', JSON.stringify(deletedIds));
            }

            $row.remove();
            resetHiddenRow($row)
            updateSerialNumbers();
            moveAddButtonToFirstRow();
        });

        function moveAddButtonToFirstRow() {
            let $firstRow = $('table tbody tr:first');
            let addBtnHtml = '<a href="#" class="text-primary add_number_pattern"><i data-feather="plus-square"></i></a>';

            $('a.add_number_pattern').remove();

            $firstRow.find('td.center-align-content').prepend(addBtnHtml);

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    });
        
    initializeAutocompleteCustomer("#customer");

    function initializeAutocompleteCustomer(selector) {
        $(selector).autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/search',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        q: request.term,
                        type: 'customer'
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                label: `${item.company_name} (${item.customer_code})`,
                                code: item.customer_code || '',
                                name: item.display_name || item.company_name,
                            };
                        }));
                    }
                });
            },
            minLength: 0,
            select: function(event, ui) {
                console.log(ui);
                $("#customer").val(ui.item.name+'( '+ui.item.code+' )');
                $("#customer_id").val(ui.item.id);
                return false;
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#customer_id").val('');
                }
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
                                        <input type="text" id="stock_type_`+rowCount+`" placeholder="Enter Stock Type" class="form-control mw-100" name="data[`+rowCount+`][stock_type]" onkeyup="getfetchSubStore(event);" />
                                    </div>
                                </td>
                                <td>
                                    <div class="position-relative">
                                        <select class="form-select mw-100 select2 subLocationSelect" data-id="1" name="data[`+rowCount+`][subLocation_id]" id="subLocation_id_`+rowCount+`"  >
                                            <option disabled selected value="">Select </option>
                                        </select>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="data[`+rowCount+`][is_primary]" id="is_primary_`+rowCount+`">
                                </td>
                                <td class = "center-align-content">
                                    <a href="#" class="text-danger remove-item"><i data-feather="trash-2"></i></a>
                                </td>
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

    function resetHiddenRow($row) {
                $row.show();
                $row.find('input, select, textarea').prop('disabled', false);
                $row.find('input, select, textarea').each(function () {
                    if (this.tagName === 'SELECT') {
                        $(this).val('').trigger('change');
                    } else if ($(this).attr('type') === 'radio' || $(this).attr('type') === 'checkbox') {
                        $(this).val('').prop('checked', false);
                    } else {
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
                    let storeId = @json($external->store_id);
                    if(storeId) {
                        $storeSelect.val(storeId).trigger('change');
                    }
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
                store_id:store_id
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
    document.addEventListener('change', function(e) {
        if (e.target && e.target.matches('input[name^="data"][name$="[is_primary]"]')) {
            if (e.target.checked) {
                // Uncheck all other checkboxes
                document.querySelectorAll('input[name^="data"][name$="[is_primary]"]').forEach(function(other) {
                    if (other !== e.target) {
                        other.checked = false;
                    }
                });
            }
        }
    });

    </script>
@endsection

@extends('layouts.app')
@section('content')
    <form class="ajax-input-form" method="POST" action="{{ route('external-integration.store') }}"
        data-redirect="{{ url('/external-integration') }}">
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
                                <a href="{{ route('external-integration.index') }}" class="btn btn-secondary btn-sm"><i
                                        data-feather="arrow-left-circle"></i> Back</a>
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
                                                        <label class="form-label">Organization<span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select name="organization_id" id="organization_id"
                                                            class="form-select select2" onchange="changeOrg()">
                                                            <option value="">Select Organization</option>
                                                            @foreach ($allOrganizations as $organization)
                                                                <option value="{{ $organization->id }}"
                                                                    data-address='@json($organization->addresses->first())'>
                                                                    {{ $organization->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>


                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Location<span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select name="store_id" class="form-select select2" id = "store_id">
                                                            <option value="">Select Location</option>

                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Default Customer <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="hidden" name="customer_id" id="customer_id">
                                                        <input type="text" id="customer" placeholder="Select"
                                                            class="form-control mw-100 ledgerselecct" name="customer" />

                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">SO Series </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" id="so_book_id"
                                                            name="so_book_id">
                                                            <option value="">Select</option>
                                                            @foreach ($sobook as $sbook)
                                                                <option value="{{ $sbook->id }}">
                                                                    {{ ucfirst($sbook->book_code) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="so_book_code" id="so_book_code">
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Trip Series </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" id="trip_book_id"
                                                            name="trip_book_id">
                                                            <option value="">Select</option>
                                                            @foreach ($tripbook as $tbook)
                                                                <option value="{{ $tbook->id }}">
                                                                    {{ ucfirst($tbook->book_code) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="trip_book_code" id="trip_book_code">
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">DNote Series </label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" id="dnote_book_id"
                                                            name="dnote_book_id">
                                                            <option value="">Select</option>
                                                            @foreach ($dnbook as $dbook)
                                                                <option value="{{ $dbook->id }}">
                                                                    {{ ucfirst($dbook->book_code) }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" name="dn_book_code" id="dn_book_code">
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
                                                                    <input type="radio"
                                                                        id="status_{{ strtolower($option) }}"
                                                                        name="status" value="{{ $option }}"
                                                                        class="form-check-input"
                                                                        {{ $option == 'active' ? 'checked' : '' }}>
                                                                    <label class="form-check-label fw-bolder"
                                                                        for="status_{{ strtolower($option) }}">
                                                                        {{ ucfirst($option) }}
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
                                            <div class="col-md-12" id = "manual_entry_details">
                                                <div class="mt-2">
                                                    <div class="step-custhomapp bg-light">
                                                        <ul class="nav nav-tabs my-25 custapploannav" role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active" data-bs-toggle="tab"
                                                                    href="#Pattern">Stock Type Location</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tab-content ">
                                                <div class="tab-pane active transaction_service_tab" id="Pattern">
                                                    <div class="table-responsive-md">
                                                        <table
                                                            class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border">
                                                            <thead>
                                                                <tr>
                                                                    <th width = "20px">#</th>
                                                                    <th width="300px"> Stock Type<span
                                                                            class="text-danger">*</span></th>
                                                                    <th width="300px">Sub Location<span
                                                                            class="text-danger">*</span></th>
                                                                    <th width="150px" class="text-center">Is Primary<span
                                                                            class="text-danger">*</span></th>
                                                                    <th class = "center-align-content" width = "20px">
                                                                        Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="item-details-body">
                                                                <tr>
                                                                    <td class="serial-number">1</td>
                                                                    <td>
                                                                        <input type="text"
                                                                            placeholder="Enter Stock Type"
                                                                            class="form-control mw-100"
                                                                            name="data[1][stock_type]" id="stock_type_1"
                                                                            onkeyup="getfetchSubStore(event);" />
                                                                    </td>

                                                                    <td>
                                                                        <select
                                                                            class="form-select mw-100 select2 subLocationSelect"
                                                                            data-id="1" name="data[1][subLocation_id]"
                                                                            id="subLocation_id_1">
                                                                            <option disabled selected value="">Select
                                                                            </option>

                                                                        </select>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <input type="checkbox" name="data[1][is_primary]"
                                                                            id="is_primary_1" value="1">
                                                                    </td>

                                                                    <td class = "center-align-content">
                                                                        <a href="#"
                                                                            class="text-primary add_number_pattern">
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
                        url: '{{ route('external-integration.customer') }}',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: 'customer',
                            org_id: $("#organization_id").val()
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
                                   <input type="text" id="stock_type_` + rowCount +
                    `" placeholder="Enter Stock Type" class="form-control mw-100" name="data[` + rowCount + `][stock_type]" onkeyup="getfetchSubStore(event);" required />

                                </div>
                            </td>
                            <td>
                            <div class="position-relative">
                                    <select
                                        class="form-select mw-100 select2 subLocationSelect" data-id="1" name="data[` +
                    rowCount + `][subLocation_id]" id="subLocation_id_` + rowCount + `" required  >
                                        <option disabled selected value="">Select </option>

                                    </select>
                            </div>
                            </td>
                           
                            <td class="text-center">
                                <input type="checkbox" name="data[` + rowCount + `][is_primary]" id="is_primary_` +
                    rowCount + `" value="1">
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
            $('#item-details-body tr:visible').each(function(index) {
                $(this).find('.serial-number').text(index + 1);
            });
        }

        $(document).on('click', '.remove-item', function() {
            const $row = $(this).closest('tr');
            resetHiddenRow($row)
            updateSerialNumbers();
        });

        function resetHiddenRow($row) {
            $row.show();
            $row.find('input, select, textarea').prop('disabled', false);
            $row.find('input, select, textarea').each(function() {
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

        function changeOrg() {
            let org = $("#organization_id").val();
            if (org) {
                getStore(org);
            }
        }


        // get store data
        function getStore(org) {
            $.ajax({
                url: '{{ route('external-integration.getStore') }}',
                type: 'GET',
                data: {
                    org_id: org
                },
                dataType: 'json',
                success: function(response) {
                    $("#store_id").empty();
                    let $storeSelect = $("#store_id");
                    $storeSelect.append(
                        `<option value="">Select Location</option>`
                    );
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
            let store_id = $("#store_id").val();
            if (type) {
                getSubStore(store_id, type, $substoreSelect);
            }
        }

        function getSubStore(store_id, type, $select) {
            $.ajax({
                url: '{{ route('external-integration.getSubstore') }}',
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


        $(document).on("change", "input[type='checkbox'][id^='is_primary_']", function() {
            let currentRow = $(this).closest("tr");
            let stockType = currentRow.find("input[name*='[stock_type]']").val().trim();

            if (!stockType) {
                // Prevent checking if stock_type is empty
                $(this).prop("checked", false);
                alert("Please enter Stock Type first.");
                return;
            }

            if ($(this).is(":checked")) {
                // Uncheck all other checkboxes having the same stock_type
                $("tr").each(function() {
                    let otherStockType = $(this).find("input[name*='[stock_type]']").val();
                    console.log(otherStockType);
                    if (otherStockType === stockType && this !== currentRow[0]) {
                        $(this).find("input[type='checkbox'][id^='is_primary_']").prop("checked", false);
                    }
                });
            }
        });

        $(".ajax-input-form").on("submit", function(e) {
            let isValid = true;
            let stockGroups = {};

            $("tr").each(function() {
                let stockType = $(this).find("input[name*='[stock_type]']").val();
                let isChecked = $(this).find("input[type='checkbox'][id^='is_primary_']").is(":checked");

                if (stockType) {
                    if (!stockGroups[stockType]) {
                        stockGroups[stockType] = false; // default no primary
                    }
                    if (isChecked) {
                        stockGroups[stockType] = true; // found primary
                    }
                }
            });

            // Check if any stock type group has no primary
            $.each(stockGroups, function(stockType, hasPrimary) {
                if (!hasPrimary) {
                    Swal.fire({
                        title: 'Validation Error',
                        text: "Please select at least one primary checkbox for Stock Type: " +
                            stockType,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    isValid = false;
                    return false; // break loop
                }
            });

            if (!isValid) {
                e.stopImmediatePropagation();
                e.preventDefault();
                return false;
            }
        });
    </script>
@endsection

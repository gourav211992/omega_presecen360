@extends('layouts.app')

@section('content')
<form class="ajax-input-form" method="POST" action="{{ route('consignees.update', $consignee->id) }}" data-redirect="{{ url('/consignees') }}">
    @csrf
    @method('PUT')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
                <div class="row">
                    <div class="content-header-left col-md-6 mb-2">
                        <div class="row breadcrumbs-top">
                            <div class="col-12">
                                <h2 class="content-header-title float-start mb-0">Edit Consignee</h2>
                                <div class="breadcrumb-wrapper">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="{{ route('consignees.index') }}">Home</a></li>
                                        <li class="breadcrumb-item active">Edit</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="content-header-right text-end col-md-6 mb-2">
                        <a href="{{ route('consignees.index') }}" class="btn btn-secondary btn-sm"><i data-feather="arrow-left-circle"></i> Back</a>
                        <button type="button" class="btn btn-danger btn-sm waves-effect waves-float waves-light delete-btn"
                                data-url="{{ route('consignees.destroy', $consignee->id) }}" 
                                data-redirect="{{ route('consignees.index') }}"
                                data-message="Are you sure you want to delete this consignee?">
                            <i data-feather="trash-2" class="me-50"></i> Delete
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm"><i data-feather="check-circle"></i> Update</button>
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
                                    </div>

                                    <div class="row">
                                        <!-- Left Section -->
                                        <div class="col-md-9">
                                            <!-- Consignee Type -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Consignee Type <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <div class="demo-inline-spacing">
                                                        <div class="form-check form-check-primary mt-25">
                                                            <input type="hidden" name="is_vendor" value="0">
                                                            <input type="checkbox" id="is_vendor" name="is_vendor" value="1" class="form-check-input" {{ $consignee->is_vendor ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-bolder" for="is_vendor">
                                                                Vendor
                                                            </label>
                                                        </div>
                                                        <div class="form-check form-check-primary mt-25">
                                                            <input type="hidden" name="is_customer" value="0">
                                                            <input type="checkbox" id="is_customer" name="is_customer" value="1" class="form-check-input" {{ $consignee->is_customer ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-bolder" for="is_customer">
                                                               Customer
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div id="consignee-type-error" class="text-danger mt-1" style="display: none; font-size: 0.8rem;"></div>
                                                </div>
                                            </div>
                                            <!-- Consignee Name -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Consignee Name <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="consignee_name" class="form-control" value="{{ old('consignee_name', $consignee->consignee_name) }}" placeholder="Enter Consignee Name" />
                                                </div>
                                            </div>

                                            <!-- Consignee Code -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Consignee Code <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="consignee_code" class="form-control" value="{{ old('consignee_code', $consignee->consignee_code) }}" placeholder="Enter Consignee Code" />
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Email</label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="email" name="email" class="form-control" value="{{ old('email', $consignee->email) }}" placeholder="Enter Email" />
                                                </div>
                                            </div>

                                            <!-- Phone -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Phone</label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $consignee->phone) }}" placeholder="Enter Phone" />
                                                </div>
                                            </div>

                                            <!-- Mobile -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-3">
                                                    <label class="form-label">Mobile</label>
                                                </div>
                                                <div class="col-md-5">
                                                    <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $consignee->mobile) }}" placeholder="Enter Mobile" />
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Section -->
                                        <div class="col-md-3 border-start">

                                            <!-- Status -->
                                            <div class="row align-items-center mb-1">
                                                <div class="col-md-12">
                                                    <label class="form-label">Status</label>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="demo-inline-spacing">
                                                        @foreach ($status as $statusOption)
                                                            <div class="form-check form-check-primary mt-25">
                                                                <input
                                                                    type="radio"
                                                                    id="status_{{ $statusOption }}"
                                                                    name="status"
                                                                    value="{{ $statusOption }}"
                                                                    class="form-check-input"
                                                                    {{ old('status', $consignee->status) == $statusOption ? 'checked' : '' }}
                                                                >
                                                                <label class="form-check-label fw-bolder" for="status_{{ $statusOption }}">
                                                                    {{ ucfirst($statusOption) }}
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
                                    </div>
                                    <!-- Tabs -->
                                    <div class="mt-1">
										<ul class="nav nav-tabs border-bottom mt-25" role="tablist">
                                            <li class="nav-item" class="tab-pane">
                                                <a class="nav-link" data-bs-toggle="tab" href="#Shipping" role="tab">Addresses</a>
                                            </li>
                                        </ul>

                                        <div class="tab-content">
                                            <!-- Addresses Tab -->
                                            <div class="tab-pane active" id="Shipping" role="tabpanel">
                                                <div class="table-responsive">
                                                    <table class="mt-1 table table-striped po-order-detail custnewpo-detail border">
                                                        <thead>
                                                            <tr>
                                                                <th>S.NO</th>
                                                                <th style="width:150px;">Country<span class="text-danger">*</span></th>
                                                                <th style="width:150px;">State<span class="text-danger">*</span></th>
                                                                <th style="width:150px;">City<span class="text-danger">*</span></th>
                                                                <th>Pin Code<span class="text-danger">*</span></th>
                                                                <th>Address<span class="text-danger">*</span></th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="address-table-body">
                                                            @forelse($consignee->addresses as $index => $address)
                                                                <tr class="address-row" data-id="{{ $address->id }}" data-index="{{ $index }}">
                                                                    <input type="hidden" name="addresses[{{ $index }}][id]" value="{{ $address->id }}">
                                                                    <td class="index">{{ $index + 1 }}</td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 country-input" name="addresses[{{ $index }}][country]" 
                                                                            placeholder="Search Country" value="{{ $address->country->name ?? '' }}">
                                                                        <input type="hidden" name="addresses[{{ $index }}][country_id]" class="country-id" value="{{ $address->country_id ?? '' }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 state-input" name="addresses[{{ $index }}][state]" 
                                                                            placeholder="Search State" value="{{ $address->state->name ?? '' }}">
                                                                        <input type="hidden" name="addresses[{{ $index }}][state_id]" class="state-id" value="{{ $address->state_id ?? '' }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 city-input" name="addresses[{{ $index }}][city]" 
                                                                            placeholder="Search City" value="{{ $address->city->name ?? '' }}">
                                                                        <input type="hidden" name="addresses[{{ $index }}][city_id]" class="city-id" value="{{ $address->city_id ?? '' }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control numberonly mw-100" name="addresses[{{ $index }}][pincode]" 
                                                                            placeholder="Pincode" value="{{ $address->pincode ?? '' }}">
                                                                        <input type="hidden" name="addresses[{{ $index }}][pincode_master_id]" class="pincode-id" value="{{ $address->pincode_master_id ?? '' }}">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100" name="addresses[{{ $index }}][address]" value="{{ $address->address ?? '' }}">
                                                                    </td>
                                                                    <td>
                                                                        <a href="#" class="text-primary add-address"><i data-feather="plus-square" class="me-50"></i></a>
                                                                        <a href="#" class="text-danger delete-address"><i data-feather="trash-2" class="me-50"></i></a>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr class="address-row" data-index="0">
                                                                    <td class="index">1</td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 country-input" name="addresses[0][country]" placeholder="Search Country">
                                                                        <input type="hidden" name="addresses[0][country_id]" class="country-id">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 state-input" name="addresses[0][state]" placeholder="Search State">
                                                                        <input type="hidden" name="addresses[0][state_id]" class="state-id">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100 city-input" name="addresses[0][city]" placeholder="Search City">
                                                                        <input type="hidden" name="addresses[0][city_id]" class="city-id">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control numberonly mw-100" name="addresses[0][pincode]" placeholder="Pincode">
                                                                        <input type="hidden" name="addresses[0][pincode_master_id]" class="pincode-id">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text" class="form-control mw-100" name="addresses[0][address]">
                                                                    </td>
                                                                    <td>
                                                                        <a href="#" class="text-primary add-address"><i data-feather="plus-square" class="me-50"></i></a>
                                                                        <a href="#" class="text-danger delete-address"><i data-feather="trash-2" class="me-50"></i></a>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- End Tabs -->
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
<!-- END: Content-->
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function applyCapsLock() {
            $('input[type="text"], input[type="number"]').each(function() {
                $(this).val($(this).val().toUpperCase());
            });
            $('input[type="text"], input[type="number"]').on('input', function() {
                $(this).val($(this).val().toUpperCase());
            });
        }

        $('form.ajax-input-form').on('submit', function(e) {
            let isVendor = $('#is_vendor').is(':checked');
            let isCustomer = $('#is_customer').is(':checked');
            let errorDiv = $('#consignee-type-error');
            errorDiv.hide().text('');
            if (!isVendor && !isCustomer) {
                e.preventDefault(); 
                errorDiv.text('Consignee type is required.').show();
            }
        });

        function initializeAutocomplete($row) {
            // Country
            $row.find('.country-input').autocomplete({
                source: function(request, response) {
                    $.get('/countries', { term: request.term }, function(data) {
                        response(data.data.countries.map(c => ({ label: c.label, value: c.value, id: c.value })));
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.label);
                    $row.find('.country-id').val(ui.item.id);
                    $row.find('.state-input, .city-input, input[name*="[pincode]"]').val('');
                    $row.find('.state-id, .city-id, .pincode-id').val('');
                    return false;
                }
            }).focus(function(){ $(this).autocomplete("search", ""); });

            // State
            $row.find('.state-input').autocomplete({
                source: function(request, response) {
                    const countryId = $row.find('.country-id').val();
                    if (!countryId) return response([]);
                    $.get(`/states/${countryId}`, { term: request.term }, function(data) {
                        response(data.data.states.map(s => ({ label: s.label, value: s.value, id: s.value })));
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.label);
                    $row.find('.state-id').val(ui.item.id);
                    $row.find('.city-input, input[name*="[pincode]"]').val('');
                    $row.find('.city-id, .pincode-id').val('');
                    return false;
                }
            }).focus(function(){ $(this).autocomplete("search", ""); });

            // City
            $row.find('.city-input').autocomplete({
                source: function(request, response) {
                    const stateId = $row.find('.state-id').val();
                    if (!stateId) return response([]);
                    $.get(`/cities/${stateId}`, { term: request.term }, function(data) {
                        response(data.data.cities.map(c => ({ label: c.label, value: c.value, id: c.value })));
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.label);
                    $row.find('.city-id').val(ui.item.id);
                    return false;
                }
            }).focus(function(){ $(this).autocomplete("search", ""); });

            // Pincode
            $row.find('input[name*="[pincode]"]').autocomplete({
                source: function(request, response) {
                    const stateId = $row.find('.state-id').val();
                    if (!stateId) return response([]);
                    $.get(`/pincodes/${stateId}`, { term: request.term }, function(data) {
                        response(data.data.pincodes.map(p => ({ label: p.label, value: p.value, id: p.value })));
                    });
                },
                minLength: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.label);
                    $row.find('input[name*="[pincode_master_id]"]').val(ui.item.id);
                    return false;
                }
            }).focus(function(){ $(this).autocomplete("search", ""); });
        }

        // Initialize existing rows
        $('#address-table-body .address-row').each(function() {
            initializeAutocomplete($(this));
        });

        // Add row
       $(document).on('click', '.add-address', function(e) {
            e.preventDefault();
            const $lastRow = $('#address-table-body .address-row').last();
            const index = $lastRow.data('index') + 1;
            const $newRow = $lastRow.clone();
            $newRow.attr('data-index', index);
            $newRow.removeAttr('data-id');
            $newRow.find('input').val('');
            $newRow.find('.ajax-validation-error-span').remove();
            $newRow.find('.country-id, .state-id, .city-id, .pincode-id').val('');
            $('#address-table-body').append($newRow);
            initializeAutocomplete($newRow);
            updateRowIndexes();
            applyCapsLock();
        });

        // Delete row
        $(document).on('click', '.delete-address', function(e) {
            e.preventDefault();
            var $row = $(this).closest('.address-row');
            var addressId = $row.data('id');
            if (addressId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Are you sure you want to delete this record?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, keep it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/consignees/address/' + addressId,
                            type: 'DELETE',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                            },
                            success: function(response) {
                                if (response.status) {
                                    $row.remove();
                                    Swal.fire('Deleted!', response.message, 'success');
                                    updateRowIndexes();
                                } else {
                                    Swal.fire('Error!', response.message || 'Could not delete the address.', 'error');
                                }
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', xhr.responseJSON.message || 'An error occurred while deleting the address.', 'error');
                            }
                        });
                    }
                });
            } else {
                $row.remove();
                updateRowIndexes();
            }
    });

    function updateRowIndexes() {
            const $rows = $('#address-table-body .address-row');
            $rows.each(function(index) {
                $(this).find('.index').text(index + 1);
                $(this).find('input, select').each(function() {
                    $(this).attr('name', $(this).attr('name').replace(/\[\d+\]/, `[${index}]`));
                });
                if(index === 0){
                    $(this).find('.add-address').show();
                    $(this).find('.delete-address').hide();
                } else {
                    $(this).find('.add-address').hide();
                    $(this).find('.delete-address').show();
                }
            });
        }
        updateRowIndexes();
        applyCapsLock();
    });
</script>
@endsection

@extends('layouts.app')

@section('content')
<!-- BEGIN: Content-->
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">

        <div class="content-body">

            <section id="basic-datatable">
                <div class="card border  overflow-hidden">
                <div class="row">
                    <div class="col-md-12 bg-light border-bottom mb-1 po-reportfileterBox">
                        <div class="row pofilterhead action-button align-items-center">
                            <div class="col-md-4">
                                <h3>{{ Str::ucfirst($type) }} Vouchers</h3>
                                <p>{{$date2}}</p>
                            </div>
                            <div class="col-md-8 text-sm-end">
                                <button class="btn btn-warning btn-sm mb-50 mb-sm-0" data-bs-target="#filter" data-bs-toggle="modal"><i data-feather="filter"></i> Filter</button>
                                @if(isset($fyearLocked) && $fyearLocked)
                                <a class="btn btn-primary btn-sm mb-50 mb-sm-0" href="{{ $createRoute }}"><i data-feather="plus-circle"></i> Add New</a>
                                @endif

                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">

                            <div class="table-responsive">
                                <table class="datatables-basic table tableistlastcolumnfixed myrequesttablecbox ">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>Date</th>
                                            {{-- <th>Document Type</th> --}}
                                            <th>Series</th>
                                            <th>Document No.</th>
                                            <th>Bank/Ledger Name</th>
                                            <th class="text-end">Amount (INR)</th>
                                            <th>Location</th>
                                            <th>Cost Center</th>
                                            <th>Currency</th>
                                            <th>Document</th>
                                            <th class="text-end">Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                      
                                    </tbody>
                                </table>
                                {{-- {{ $data->links('vendor.pagination.custom') }} --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>
        </div>
    </div>
</div>
<!-- END: Content-->

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
                      <input type="text" id="fp-range" class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" value="{{$date}}" name="date"/>
                </div>




                <div class="mb-1">
                    <label class="form-label">Document No.</label>
                    <input class="form-control" type="text" name="document_no" id="document_no" value="{{ $document_no }}"/>
                </div>


                <div class="mb-1">
                    <label class="form-label">Bank</label>
                    <select class="form-select" name="bank_id">
                        <option value="">All Banks</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" @if($bank->id==$bank_id) selected @endif>{{ $bank->bank_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-1">
                    <label class="form-label">Ledger</label>
                    <select class="form-select" name="ledger_id">
                        <option value="">All Ledgers</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @if($ledger->id==$ledger_id) selected @endif>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- <div class="mb-1">
                    <label class="form-label">Cost Center</label>
                    <select id="cost_center_id" class="form-select select2"
                        name="cost_center_id">
                        <option value="" disabled>Select</option>
                        @foreach ($cost_centers as $key => $value)
                        <option value="{{ $value['id'] }}" @if(request('cost_center_id')==$value['id']) selected @endif>{{ $value['name'] }}</option>
                        @endforeach
                    </select>
                </div> --}}

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
                    <label class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select select2">
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label">Cost Group</label>
                    <select id="cost_group_id" name="cost_group_id" class="form-select select2">
                    </select>
                </div>
                <div class="mb-1">
                    <label class="form-label">Cost Center</label>
                    <select id="cost_center_id" class="form-select select2"
                        name="cost_center_id">
                    </select>
                </div>

            </div>
            <div class="modal-footer justify-content-start">
                <button type="submit" class="btn btn-primary data-submit mr-1">Apply</button>
                <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const locations = @json($locations);
    const costCenters = @json($cost_centers);
    const costGroups = @json($cost_groups);
</script>
<script type="text/javascript" src="{{asset('assets/js/modules/finance-table.js')}}"></script>
<script>
     function updateLocationsDropdown(selectedOrgIds) {
        const filteredLocations = locations.filter(loc =>
            selectedOrgIds.includes(String(loc.organization_id))
        );

        const $locationDropdown = $('#location_id');
        $locationDropdown.empty().append('<option value="">Select</option>');

        filteredLocations.forEach(loc => {
            $locationDropdown.append(`<option value="${loc.id}">${loc.store_name}</option>`);
        });

        $locationDropdown.trigger('change');
    }

    function loadCostGroupsByLocation(locationId) {
        const filteredCenters = costCenters.filter(center => {
            if (!center.location) return false;
            const locationArray = Array.isArray(center.location)
                ? center.location.flatMap(loc => loc.split(','))
                : [];
            return locationArray.includes(String(locationId));
        });

        const costGroupIds = [...new Set(filteredCenters.map(center => center.cost_group_id))];
        
        const filteredGroups = costGroups.filter(group => costGroupIds.includes(group.id));
        console.log(filteredCenters,costGroupIds,filteredGroups);

        const $groupDropdown = $('#cost_group_id');
        $groupDropdown.empty().append('<option value="">Select Cost Group</option>');

        filteredGroups.forEach(group => {
            $groupDropdown.append(`<option value="${group.id}">${group.name}</option>`);
        });

        $('#cost_group_id').trigger('change');
    }

    function loadCostCentersByGroup(locationId, groupId) {
        const costCenter = $('#cost_center_id');
        costCenter.empty();

        const filteredCenters = costCenters.filter(center => center.cost_group_id === groupId);

        if (filteredCenters.length === 0) {
            costCenter.prop('required', false);
            $('#cost_center_id').hide();
        } else {
            costCenter.append('<option value="">Select Cost Center</option>');
            $('#cost_center_id').show();

            filteredCenters.forEach(center => {
                costCenter.append(`<option value="${center.id}">${center.name}</option>`);
            });
        }
        costCenter.val(@json(request('cost_center_id')) || "");
        costCenter.trigger('change');
    }


    $(document).ready(function () {
        // On change of organization
        $('#filter-organization').on('change', function () {
            const selectedOrgIds = $(this).val() || [];
            updateLocationsDropdown(selectedOrgIds);
        });

        // On page load, check for preselected orgs
        const preselectedOrgIds = $('#filter-organization').val() || [];
        if (preselectedOrgIds.length > 0) {
            updateLocationsDropdown(preselectedOrgIds);
        }
        // On location change, load cost centers
        $('#location_id').on('change', function () {
            const locationId = $(this).val();
          if (!locationId) {
        // $('#cost_center_id').empty().append('<option value="">Select Cost Center</option>');
            // $('.cost_center').hide(); // Optional: hide the section if needed
                return;
            }
            loadCostGroupsByLocation(locationId);
        });
        $('#cost_group_id').on('change', function () {
                const locationId = $('#location_id').val();
                const groupId = parseInt($(this).val());

                if (!locationId || !groupId) {
                    $('#cost_center_id').empty().append('<option value="">Select Cost Center</option>');
                    return;
                }

                loadCostCentersByGroup(locationId, groupId);
            });
          handleRowSelection('.datatables-basic');

    // Optionally, you can add some custom logic or event listeners here
});


$('.datatables-basic').DataTable({
    processing: true,
    serverSide: true,
    scrollX: true,

    ajax: {
        url: "{{ route('payments.index') }}",
        data: function (d) {
            d.book_type = $('#book_type').val();
            d.location_id = $('#location_id').val();
            d.document_no = $('#document_no').val();
            d.bank_id = $('#bank_id').val();
            d.ledger_id = $('#ledger_id').val();
            d.cost_center_id = $('#cost_center_id').val();
            d.date = $('#date').val();
        }
    },

    dom:
        '<"d-flex justify-content-between align-items-center mx-2 row"' +
            '<"col-sm-12 col-md-6"l>' +
            '<"col-sm-12 col-md-3 withoutheadbuttin dt-action-buttons text-end"B>' +
            '<"col-sm-12 col-md-3"f>' +
        '>' +
        't' +
        '<"d-flex justify-content-between mx-2 row"' +
            '<"col-sm-12 col-md-6"i>' +
            '<"col-sm-12 col-md-6"p>' +
        '>',

    lengthMenu: [7, 10, 25, 50, 75, 100],

    buttons: [
        {
            extend: 'excel',
            text: feather.icons['file'].toSvg({ class: 'font-small-4 me-50' }) + 'Excel',
            className: 'btn btn-outline-secondary',
            exportOptions: { 
                columns: [0,1,2,3,4,5,6,7,8,9,10]
            },
            filename: function () {
                let path = window.location.pathname.toLowerCase();
                return path.includes('receipts')
                    ? 'Receipts Report'
                    : 'Payment Vouchers Report';
            },
            init: function (api, node) {
                $(node).removeClass('btn-secondary');
                $(node).parent().removeClass('btn-group');

                setTimeout(function () {
                    $(node)
                        .closest('.dt-buttons')
                        .removeClass('btn-group')
                        .addClass('d-inline-flex');
                }, 50);
            }
        }
    ],

    columns: [
        { data: "DT_RowIndex", name: "", orderable: false, searchable: false },
        { data: "date", name: "date",className: "text-nowrap" },
        { data: "series", name: "series" },
        { data: "document_no", name: "document_no" },
        { data: "bank_ledger", name: "bank_ledger" },
        { data: "amount", name: "amount", className: "text-end" },
        { data: "location", name: "location" },
        { data: "cost_center", name: "cost_center" },
        { data: "currency", name: "currency" },
        { data: "document", name: "document", orderable: false, searchable: false },
        { data: "status", name: "status", className: "text-end" },
        { data: "action", name: "action", orderable: false, searchable: false, className: "text-end" }
    ]
});





</script>


@endsection

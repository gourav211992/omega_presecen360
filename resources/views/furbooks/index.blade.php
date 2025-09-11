@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ url('/app-assets/js/jquery-ui.css') }}">
@endsection

@section('content')
 <div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">
        <div class="content-header pocreate-sticky">
            <div class="row">
                <div class="content-header-left col-md-6 mb-2">
                    <div class="row breadcrumbs-top">
                        <div class="col-12">
                            <h2 class="content-header-title float-start mb-0 border-0">Integration with Furbooks</h2> 
                        </div>
                    </div>
                </div>
                <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                    <div class="form-group breadcrumb-right">
                        <button id="deleteRowBtn" class="btn btn-outline-danger btn-sm mb-50 mb-sm-0">
                            <i data-feather="x-circle"></i> Delete
                        </button>
                        <button id="addRowBtn" class="btn btn-outline-primary btn-sm mb-50 mb-sm-0">
                            <i data-feather="plus"></i> Add New
                        </button> 
                        <button type="button" id="saveBtn" class="btn btn-primary btn-sm mb-50 mb-sm-0">
                            <i data-feather="check-circle"></i> Save
                        </button> 
                    </div>
                </div>
            </div>
        </div>
        <div class="content-body">
            <form id="furbookForm" action="{{ route('furbooks.store') }}" method="POST">
                @csrf
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
                                    <div class="col-md-12"> 
                                        <div class="row align-items-center mb-1">
                                            <div class="col-md-2"> 
                                                <label class="form-label">JV Series <span class="text-danger">*</span></label>  
                                            </div>  
                                            <div class="col-md-3">  
                                                <select class="form-select" id="book_id" name="book_type_id"></select>
                                            </div>
                                        </div>
                                        
                                        <div class="table-responsive-md">
                                            <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad" id="ledgerTable"> 
                                                <thead>
                                                    <tr>
                                                        <th width="62" class="customernewsection-form">
                                                            <div class="form-check form-check-primary custom-checkbox">
                                                                <input type="checkbox" class="form-check-input" id="checkAll">
                                                            </div> 
                                                        </th>
                                                        <th>Ledger Name <span class="text-danger">*</span></th>
                                                        <th>Ledger Group <span class="text-danger">*</span></th>
                                                        <th>Furbook Code <span class="text-danger">*</span></th>  
                                                    </tr>
                                                </thead>
                                                <tbody class="mrntableselectexcel" id="ledgerTableBody">
                                                    <tr id="row_1">
                                                        <td class="customernewsection-form">
                                                            <div class="form-check form-check-primary custom-checkbox">
                                                                <input type="checkbox" class="form-check-input row-check">
                                                            </div> 
                                                        </td>
                                                        <td>
                                                            <input type="text" placeholder="Select Ledger" class="form-control ledgerselect mw-100" 
                                                                   name="ledger_name_1" id="ledger_name_1" data-id="1" />
                                                            <input type="hidden" name="ledger_id[]" id="ledger_id_1" class="ledgers" />
                                                        </td>
                                                        <td>
                                                             <select name="ledger_group_id[]" id="groupSelect_1" data-id="1" 
                                                                     class="ledgerGroup form-select mw-100">
                                                                 <option value="">Select Group</option>
                                                             </select>
                                                         </td>
                                                         
                                                         <td>
                                                             <input type="text" placeholder="Enter Furbook Code" 
                                                                    class="form-control furbook-code mw-100" 
                                                                    name="furbook_code[]" id="furbook_code_1" />
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
                </section>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ url('/app-assets/js/jquery-ui.js') }}"></script>
    <script type="text/javascript" src="{{asset('assets/js/modules/common-attr-ui.js')}}"></script>
    <script>
        let initialBookId = @json($bookTypes[0]->id ?? null);
        let rowCounter = 1;

        // ✅ Get Series Function
        function getSeries(selectedBookId = null) {
            $('#book_id').empty();
            $.ajax({
                url: '{{ route('get_voucher_series', ['placeholder']) }}'.replace('placeholder', initialBookId),
                type: 'GET',
                success: function (books) {
                    if (books.length > 0) {
                        $('#book_id').append('<option disabled value="">Select Series</option>');
                        $.each(books, function (key, value) {
                            $("#book_id").append(`<option value="${value.id}">${value.book_code}</option>`);
                        });
                        $('#book_id').val(selectedBookId ?? books[0].id).trigger('change');
                    } else {
                        $('#book_id').append('<option disabled selected>No Books Found</option>');
                    }
                },
                error: function () {
                    $('#book_id').append('<option disabled selected>Error loading books</option>');
                }
            });
        }

        $(document).ready(function () {
            if (feather) feather.replace({ width: 14, height: 14 });

            // Load Series on page load
            getSeries();

            // Initialize ledger autocomplete
            initializeLedgerAutocomplete();

            // Load saved data into form rows
            loadSavedData();

            // Initialize furbook code validation for existing rows
            initializeFurbookCodeValidation();

            // ✅ Add Row
            $('#addRowBtn').on('click', function (e) {
                e.preventDefault();
                rowCounter++;
                let newRow = `
                    <tr id="row_${rowCounter}">
                        <td class="customernewsection-form">
                            <div class="form-check form-check-primary custom-checkbox">
                                <input type="checkbox" class="form-check-input row-check">
                            </div> 
                        </td>
                        <td>
                            <input type="text" placeholder="Select Ledger" class="form-control ledgerselect mw-100" 
                                   name="ledger_name_${rowCounter}" id="ledger_name_${rowCounter}" data-id="${rowCounter}" />
                            <input type="hidden" name="ledger_id[]" id="ledger_id_${rowCounter}" class="ledgers" />
                        </td>
                        <td>
                            <select name="ledger_group_id[]" id="groupSelect_${rowCounter}" data-id="${rowCounter}" 
                                    class="ledgerGroup form-select mw-100">
                                <option value="">Select Group</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" placeholder="Enter Furbook Code" 
                                   class="form-control furbook-code mw-100" 
                                   name="furbook_code[]" id="furbook_code_${rowCounter}" />
                        </td>
                    </tr>`;
                $('#ledgerTableBody').append(newRow);
                
                // Initialize autocomplete for new row
                initializeLedgerAutocomplete();
            });

            // ✅ Select/Deselect All
            $('#checkAll').on('change', function () {
                $('.row-check').prop('checked', $(this).is(':checked'));
            });

            // ✅ Delete Rows with SweetAlert
            $('#deleteRowBtn').on('click', function (e) {
                e.preventDefault();
                let selected = $('#ledgerTable tbody').find('.row-check:checked');
                let totalRows = $('#ledgerTable tbody tr').length;

                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please select at least one row to delete!'
                    });
                    return;
                }

                if (selected.length === totalRows) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Not Allowed',
                        text: 'At least one row must remain in the table.'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Selected rows will be deleted!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        selected.each(function () {
                            $(this).closest('tr').remove();
                        });
                        $('#checkAll').prop('checked', false);
                        Swal.fire('Deleted!', 'Selected rows have been removed.', 'success');
                        
                        // Update row counter after deletion
                        updateRowCounter();
                    }
                });
            });

            // Validate furbook codes on input
            $(document).on('input', '.furbook-code', function() {
                validateFurbookCode($(this));
            });

            // Save button functionality
            $('#saveBtn').on('click', function(e) {
                e.preventDefault();
                saveFurbookData();
            });
        });

        // ✅ Initialize Ledger Autocomplete
        function initializeLedgerAutocomplete() {
            $(".ledgerselect").autocomplete({
                source: function(request, response) {
                    // Get all pre-selected ledgers to exclude them
                    var preLedgers = [];
                    $('.ledgers').each(function() {
                        if ($(this).val() != "") {
                            preLedgers.push($(this).val());
                        }
                    });

                    if ($('#book_id').val() != null) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            url: '{{ route('furbook-ledger-search') }}',
                            type: "POST",
                            dataType: "json",
                            data: {
                                keyword: request.term,
                                series: $('#book_id').val(),
                                ids: preLedgers,
                                '_token': '{!! csrf_token() !!}'
                            },
                            success: function(data) {
                                response(data);
                            },
                            error: function() {
                                response([]);
                            }
                        });
                    }
                },
                minLength: 0,
                select: function(event, ui) {
                    $(this).val(ui.item.label);
                    
                    let ledgerId = ui.item.value;
                    let rowId = $(this).data('id');
                    
                    // Check if ledger is already selected in another row
                    if (isLedgerAlreadySelected(ledgerId, rowId)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Duplicate Ledger',
                            text: 'This ledger is already selected in another row!'
                        });
                        $(this).val('');
                        return false;
                    }

                    console.log(`Selected Ledger ID: ${ledgerId}, Row ID: ${rowId}`);
                    
                    // Set ledger ID in hidden field
                    $(`#ledger_id_${rowId}`).val(ledgerId);
                    
                    // Populate groups for this ledger
                    populateLedgerGroups(ledgerId, rowId);
                    
                    return false;
                }
            });

            // Show autocomplete list on focus/click
            $(document).on('focus click', '.ledgerselect', function() {
                if ($(this).val() === '') {
                    $(this).autocomplete('search', '');
                }
            });
        }

        // ✅ Populate Ledger Groups
        function populateLedgerGroups(ledgerId, rowId) {
            let groupDropdown = $(`#groupSelect_${rowId}`);
            
            // Get pre-selected groups to exclude them
            var preGroups = [];
            $('.ledgerGroup').each(function(index) {
                let ledgerGroup = $(this).val();
                let ledger_id = $(this).data('ledger');
                
                if (ledgerGroup !== "") {
                    preGroups.push({
                        ledger_id: ledger_id,
                        ledgerGroup: ledgerGroup
                    });
                }
            });

            if (ledgerId) {
                $.ajax({
                    url: '{{ route('voucher.getLedgerGroups') }}',
                    method: 'GET',
                    data: {
                        ledger_id: ledgerId,
                        ids: preGroups,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        groupDropdown.empty();
                        
                        if (response.length > 0) {
                            // Auto-populate with the first group (or primary group)
                            const firstGroup = response[0];
                            groupDropdown.append(`<option value="${firstGroup.id}" data-ledger="${ledgerId}" selected>${firstGroup.name}</option>`);
                            
                            // Add other groups as options if there are multiple
                            response.slice(1).forEach(item => {
                                groupDropdown.append(
                                    `<option value="${item.id}" data-ledger="${ledgerId}">${item.name}</option>`
                                );
                            });
                            
                            // Set the first group as selected
                            groupDropdown.val(firstGroup.id);
                        } else {
                            groupDropdown.append('<option value="">No Groups Available</option>');
                        }
                        
                        groupDropdown.data('ledger', ledgerId);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error fetching group items.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        }

        // ✅ Populate Series Dropdown
        function populateSeriesDropdown(selector) {
            // Get series data from books table or API
            $.ajax({
                url: '{{ route('furbooks.get-series') }}',
                method: 'GET',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    const dropdown = $(selector);
                    const currentValue = dropdown.val(); // Preserve current selection
                    dropdown.empty();
                    dropdown.append('<option value="">Select Series</option>');
                    
                    if (response.status === 'success' && response.data && response.data.length > 0) {
                        response.data.forEach(series => {
                            const selected = currentValue == series.id ? 'selected' : '';
                            dropdown.append(`<option value="${series.id}" ${selected}>${series.book_code} - ${series.book_name}</option>`);
                        });
                    }
                },
                error: function() {
                    console.error('Failed to load series data');
                }
            });
        }

        // ✅ Populate All Series Dropdowns
        function populateAllSeriesDropdowns() {
            $('.seriesSelect').each(function() {
                const selector = '#' + $(this).attr('id');
                populateSeriesDropdown(selector);
            });
        }

        // ✅ Check if ledger is already selected
        function isLedgerAlreadySelected(ledgerId, currentRowId) {
            let isSelected = false;
            $('.ledgers').each(function() {
                let rowId = $(this).attr('id').split('_')[2];
                if ($(this).val() == ledgerId && rowId != currentRowId) {
                    isSelected = true;
                    return false;
                }
            });
            return isSelected;
        }

        // ✅ Validate Furbook Code uniqueness per ledger
        function validateFurbookCode(input) {
            let currentCode = input.val().trim();
            let currentRow = input.closest('tr');
            let currentLedgerId = currentRow.find('.ledgers').val();
            
            if (currentCode === '' || currentLedgerId === '') {
                input.removeClass('is-invalid');
                return;
            }
            
            let isDuplicate = false;
            $('.furbook-code').each(function() {
                if ($(this)[0] !== input[0]) { // Not the same input
                    let otherRow = $(this).closest('tr');
                    let otherLedgerId = otherRow.find('.ledgers').val();
                    let otherCode = $(this).val().trim();
                    
                    if (currentLedgerId === otherLedgerId && currentCode === otherCode) {
                        isDuplicate = true;
                        return false;
                    }
                }
            });
            
            if (isDuplicate) {
                input.addClass('is-invalid');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Code',
                    text: 'This furbook code already exists for the selected ledger!'
                });
            } else {
                input.removeClass('is-invalid');
            }
        }

        // ✅ Update row counter after deletion
        function updateRowCounter() {
            let maxId = 0;
            $('#ledgerTableBody tr').each(function() {
                let rowId = parseInt($(this).attr('id').split('_')[1]);
                if (rowId > maxId) {
                    maxId = rowId;
                }
            });
            rowCounter = maxId;
        }

        // ✅ Save Furbook Data
        function saveFurbookData() {
            // Validate form before submission
            if (!validateForm()) {
                return;
            }

            const bookId = $('#book_id').val();
            const ledgers = [];
            
            // Collect only new rows (not saved ones)
            $('#ledgerTableBody tr').each(function() {
                // Skip rows that have saved data (they have data-saved-id attribute)
                if ($(this).attr('data-saved-id')) {
                    return;
                }
                
                const ledgerId = $(this).find('.ledgers').val();
                const groupId = $(this).find('.ledgerGroup').val();
                const furbookCode = $(this).find('.furbook-code').val();
                
                if (ledgerId && groupId && furbookCode) {
                    ledgers.push({
                        ledger_id: ledgerId,
                        ledger_group_id: groupId,
                        book_id: $('#book_id').val(), // Use main book_id from JV Series dropdown
                        furbook_code: furbookCode
                    });
                }
            });

            if (ledgers.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'No New Data',
                    text: 'Please add at least one new ledger mapping to save.'
                });
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Saving...',
                text: 'Please wait while we save your furbook data.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("furbooks.store") }}',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    book_id: bookId,
                    ledgers: ledgers
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Furbook data saved successfully!',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while saving.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join(', ');
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                }
            });
        }

        // ✅ Validate Form Before Save
        function validateForm() {
            const bookId = $('#book_id').val();
            if (!bookId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a JV Series!'
                });
                return false;
            }

            let hasValidRows = false;
            let hasErrors = false;

            $('#ledgerTableBody tr').each(function() {
                const row = $(this);
                const ledgerId = row.find('.ledgers').val();
                const groupId = row.find('.ledgerGroup').val();
                const furbookCode = row.find('.furbook-code').val().trim();

                if (ledgerId || groupId || furbookCode) {
                    if (!ledgerId) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please select a ledger for all rows!'
                        });
                        hasErrors = true;
                        return false;
                    }
                    if (!groupId) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please select a group for all rows!'
                        });
                        hasErrors = true;
                        return false;
                    }
                    if (!furbookCode) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please enter furbook code for all rows!'
                        });
                        hasErrors = true;
                        return false;
                    }
                    hasValidRows = true;
                }
            });

            if (hasErrors) {
                return false;
            }

            if (!hasValidRows) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please add at least one complete row with ledger, group, and furbook code!'
                });
                return false;
            }

            // Check for duplicate furbook codes
            if (!validateAllFurbookCodes()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Furbook Codes',
                    text: 'Please ensure all furbook codes are unique. Duplicate codes are not allowed.'
                });
                return false;
            }

            return true;
        }

        // ✅ Load Saved Data into Form Rows
        function loadSavedData() {
           
            const savedData = @json($processedFurbooks ?? []);
            console.log("check the savedData", savedData);
            
            if (savedData.length > 0) {
                // Clear existing rows first
                $('#ledgerTableBody').empty();
                
                savedData.forEach((item, index) => {
                    const rowId = index + 1;
                    rowCounter = rowId;
                    
                    const newRow = `
                        <tr id="row_${rowId}" data-saved-id="${item.id}">
                            <td class="customernewsection-form">
                                <div class="form-check form-check-primary custom-checkbox">
                                    <input type="checkbox" class="form-check-input row-check">
                                </div> 
                            </td>
                            <td>
                                <input type="text" placeholder="Select Ledger" class="form-control ledgerselect mw-100" 
                                       name="ledger_name_${rowId}" id="ledger_name_${rowId}" data-id="${rowId}" 
                                       value="${item.ledger_name}" readonly />
                                <input type="hidden" name="ledger_id[]" id="ledger_id_${rowId}" class="ledgers" 
                                       value="${item.ledger_id}" />
                            </td>
                            <td>
                                <select name="ledger_group_id[]" id="groupSelect_${rowId}" data-id="${rowId}" 
                                        class="ledgerGroup form-select mw-100" disabled>
                                    <option value="${item.ledger_group_id}" selected>${item.ledger_group_name}</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" placeholder="Enter Furbook Code" 
                                       class="form-control furbook-code mw-100" 
                                       name="furbook_code[]" id="furbook_code_${rowId}" 
                                       value="${item.furbook_code}" />

                            </td>
                        </tr>`;
                    
                    $('#ledgerTableBody').append(newRow);
                });
                
                // Add one empty row for new entries
                addEmptyRow();
                
                // Replace feather icons
                feather.replace();
            }
        }

        // ✅ Add Empty Row for New Entries
        function addEmptyRow() {
            rowCounter++;
            const newRow = `
                <tr id="row_${rowCounter}">
                    <td class="customernewsection-form">
                        <div class="form-check form-check-primary custom-checkbox">
                            <input type="checkbox" class="form-check-input row-check">
                        </div> 
                    </td>
                    <td>
                        <input type="text" placeholder="Select Ledger" class="form-control ledgerselect mw-100" 
                               name="ledger_name_${rowCounter}" id="ledger_name_${rowCounter}" data-id="${rowCounter}" />
                        <input type="hidden" name="ledger_id[]" id="ledger_id_${rowCounter}" class="ledgers" />
                    </td>
                    <td>
                        <select name="ledger_group_id[]" id="groupSelect_${rowCounter}" data-id="${rowCounter}" 
                                class="ledgerGroup form-select mw-100">
                            <option value="">Select Group</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" placeholder="Enter Furbook Code" 
                               class="form-control furbook-code mw-100" 
                               name="furbook_code[]" id="furbook_code_${rowCounter}" />
                    </td>
                </tr>`;
            
            $('#ledgerTableBody').append(newRow);
            
            // Initialize ledger autocomplete for new row
            initializeLedgerAutocomplete();
            
            // Initialize furbook code validation for new row
            initializeFurbookCodeValidation();
        }

        // ✅ Initialize Furbook Code Validation
        function initializeFurbookCodeValidation() {
            $('.furbook-code').off('input.furbookValidation').on('input.furbookValidation', function() {
                const currentInput = $(this);
                const currentValue = currentInput.val().trim();
                const currentRow = currentInput.closest('tr');
                
                if (currentValue === '') {
                    currentInput.removeClass('is-invalid is-valid');
                    currentInput.next('.invalid-feedback').remove();
                    return;
                }
                
                // Check for duplicates in other rows
                let isDuplicate = false;
                $('.furbook-code').each(function() {
                    const otherInput = $(this);
                    const otherRow = otherInput.closest('tr');
                    
                    // Skip if it's the same input field
                    if (otherInput[0] === currentInput[0]) {
                        return;
                    }
                    
                    const otherValue = otherInput.val().trim();
                    
                    if (currentValue !== '' && currentValue === otherValue) {
                        isDuplicate = true;
                        return false; // Break the loop
                    }
                });
                
                // Update UI based on validation result
                if (isDuplicate) {
                    currentInput.removeClass('is-valid').addClass('is-invalid');
                    
                    // Remove existing feedback and add new one
                    currentInput.next('.invalid-feedback').remove();
                    currentInput.after('<div class="invalid-feedback">This furbook code is already used in another row.</div>');
                } else {
                    currentInput.removeClass('is-invalid').addClass('is-valid');
                    currentInput.next('.invalid-feedback').remove();
                }
            });
        }

        // ✅ Check All Furbook Codes for Duplicates
        function validateAllFurbookCodes() {
            let hasDuplicates = false;
            const furbookCodes = [];
            
            $('.furbook-code').each(function() {
                const value = $(this).val().trim();
                if (value !== '') {
                    if (furbookCodes.includes(value)) {
                        hasDuplicates = true;
                        $(this).removeClass('is-valid').addClass('is-invalid');
                        $(this).next('.invalid-feedback').remove();
                        $(this).after('<div class="invalid-feedback">Duplicate furbook code found.</div>');
                    } else {
                        furbookCodes.push(value);
                        $(this).removeClass('is-invalid').addClass('is-valid');
                        $(this).next('.invalid-feedback').remove();
                    }
                }
            });
            
            return !hasDuplicates;
        }

        // ✅ Show Toast Notification
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
            Toast.fire({
                icon,
                title
            });
        }
    </script>
@endsection

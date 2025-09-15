@extends('p360::layouts.erp')

@section('content')
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
				<div class="row">
					<div class="content-header-left col-md-6 mb-2">
						<div class="row breadcrumbs-top">
							<div class="col-12">
								<h2 class="content-header-title border-0 float-start mb-0">RGR Defect Types</h2>
							</div>
						</div>
					</div>
					<div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
						<div class="form-group breadcrumb-right">
							<button class="btn btn-outline-danger btn-delete-row btn-sm mb-50 mb-sm-0"><i data-feather="x-circle"></i> Delete</button>
							<button class="btn btn-outline-primary btn-add-row btn-sm mb-50 mb-sm-0"><i data-feather="plus"></i> Add New</button> 
							<button class="btn btn-primary btn-save btn-sm mb-50 mb-sm-0"><i data-feather="check-circle"></i> Save</button> 
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
                                     
                                      
									 		<div>
                                                <h4 class="card-title text-theme">Defect Types</h4>
												 <div class="tab-content pb-1">
														<div class="tab-pane active" id="RGR">
															<div class="row">  
																 <div class="col-md-12"> 
																	 <div class="table-responsive pomrnheadtffotsticky">
																		<table id="defectTable" class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad"> 
                                                                            <thead>
                                                                                <tr>
                                                                                    <th class="customernewsection-form" width="10px">
                                                                                        <div class="form-check form-check-primary custom-checkbox">
                                                                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                                                                            <label class="form-check-label" for="selectAll"></label>
                                                                                        </div> 
                                                                                    </th>
                                                                                    <th width="200px">Category</th>
                                                                                    <th width="200px">Severity</th>
                                                                                    <th>Defect Type</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="mrntableselectexcel">
                                                                                @if(isset($data))
                                                                                    @foreach($data as $key => $value)
                                                                                        <tr>
                                                                                            <td class="customernewsection-form">
                                                                                                <div class="form-check form-check-primary custom-checkbox">
                                                                                                    <input type="checkbox" class="row-check form-check-input">
                                                                                                    <!-- Header ID -->
                                                                                                    <input type="hidden" class="rgr_defect_id" name="rgr_defect_id[]" value="{{ $value->id }}">
                                                                                                    <label class="form-check-label row-number">{{ $key + 1 }}</label>
                                                                                                </div>
                                                                                            </td>

                                                                                            <td class="poprod-decpt">
                                                                                                <!-- Category (read-only for existing header) -->
                                                                                                <input type="text" placeholder="Select Category" class="form-control mw-100 ledgerselecct mb-25 category-input" 
                                                                                                    value="{{ $value->category->name }}" data-selected-id="{{ $value->category_id }}" {{ $value->id ? 'readonly' : '' }} />
                                                                                            </td>

                                                                                            <td class="poprod-decpt">
                                                                                                <!-- Severity (read-only for existing header) -->
                                                                                                <input type="text" placeholder="Select Severity" class="form-control severity-select mw-100 ledgerselecct mb-25 severity-input"
                                                                                                    value="{{ $value->defect_severity }}" {{ $value->id ? 'readonly' : '' }} />
                                                                                            </td>

                                                                                            <td class="poprod-decpt">
                                                                                                <!-- Reasons with detail IDs -->
                                                                                                <select class="form-select reason-select2" multiple>
                                                                                                    @foreach($value->details as $detail)
                                                                                                        <option value="{{ $detail->type }}" data-detail-id="{{ $detail->id }}" selected>{{ $detail->type }}</option>
                                                                                                    @endforeach
                                                                                                </select>
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach

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
                    </div>
                    </div>
@endsection
@section('scripts')
<script>
let deletedIds = []; // Global array to store deleted header IDs

$(document).ready(function () {
    let rowCount = $(".mrntableselectexcel tr").length;

    // 🔹 Initialize Category autocomplete
    function initializeAutocompleteCategory(selector) {
        $(selector)
            .autocomplete({
                minLength: 0,
                source: function (request, response) {
                    $.ajax({
                        url: "/search",
                        method: "GET",
                        dataType: "json",
                        data: { q: request.term, type: "category" },
                        success: function (data) {
                            response($.map(data, function (item) {
                                return { id: item.id, label: item.name };
                            }));
                        }
                    });
                },
                select: function (event, ui) {
                    $(this).data("selected-id", ui.item.id);
                    checkDuplicateCombination($(this).closest("tr"));
                },
                change: function (event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $(this).removeData("selected-id");
                    }
                }
            })
            .focus(function () {
                if (this.value === "") $(this).autocomplete("search", "");
            });
    }

    // 🔹 Initialize Severity select
    function initializeSeveritySelect(selector) {
        let $select = $(selector);
        $select.html(`
            <select class="form-select mw-100 severity-select">
                <option value="" disabled selected>Select Severity</option>
                <option value="Major">Major</option>
                <option value="Minor">Minor</option>
                <option value="Scrap">Scrap</option>
            </select>
        `);
        $select.find(".severity-select").on("change", function () {
            checkDuplicateCombination($(this).closest("tr"));
        });
    }
    // Run check when Category changes
    $(document).on("change", ".category-input", function () {
        let currentRow = $(this).closest("tr");
        checkDuplicateCombination(currentRow);
    });

    // Run check when Severity changes
    $(document).on("change", ".severity-select", function () {
        let currentRow = $(this).closest("tr");
        checkDuplicateCombination(currentRow);
    });
    // 🔹 Check for duplicate Category + Severity
    function checkDuplicateCombination(currentRow) {
        let category = currentRow.find(".category-input").val()?.trim();
        // works for both <select> (new rows) and <input> (existing rows)
        let severity = currentRow.find(".severity-select").val() || currentRow.find(".severity-input").val();
        if (!category || !severity) return;

        let duplicate = false;
        $(".mrntableselectexcel tr").each(function () {
            let rowCategory = $(this).find(".category-input").val()?.trim();
            let rowSeverity = $(this).find(".severity-select").val() || $(this).find(".severity-input").val();

            if (this !== currentRow[0] && rowCategory === category && rowSeverity === severity) {
                duplicate = true;
                return false; // break out of .each()
            }
        });

        if (duplicate) {
            Swal.fire({
                icon: "error",
                title: "Duplicate Combination",
                text: `The combination of "${category}" and "${severity}" already exists.`,
            });
            currentRow.find(".category-input").val("");
            currentRow.find(".severity-select").val("").prop("disabled", false);
        }
    }

    // 🔹 Renumber rows
    function renumberRows() {
        $("#defectTable tbody tr").each(function (index) {
            $(this).find(".row-number").text(index + 1);
        });
    }

    // 🔹 Add new row
    $(".btn-add-row").click(function () {
        rowCount++;
        let newRow = `
            <tr>
                <td class="customernewsection-form">
                    <div class="form-check form-check-primary custom-checkbox">
                        <input type="checkbox" class="row-check form-check-input row-checkbox">
                        <label class="form-check-label">
                            <span class="row-number">${rowCount}</span>
                        </label>
                    </div>
                </td>
                <td class="poprod-decpt">
                    <input type="text" placeholder="Select Category" class="form-control mw-100 ledgerselecct mb-25 category-input" />
                </td>
                <td class="poprod-decpt severity-cell"></td>
                <td class="poprod-decpt">
                    <select class="form-select mw-100 reason-select2" multiple></select>
                </td>
            </tr>
        `;
        $(".mrntableselectexcel").append(newRow);

        let lastRow = $(".mrntableselectexcel tr:last");
        initializeAutocompleteCategory(lastRow.find(".category-input"));
        initializeSeveritySelect(lastRow.find(".severity-cell"));
        lastRow.find(".reason-select2").select2({
            tags: true,
            placeholder: "Type and press Enter",
            width: "100%"
        });

        renumberRows();
    });

    // 🔹 Delete selected rows
    $(".btn-delete-row, .btn-outline-danger").on("click", function(e) {
        e.preventDefault();

        $(".mrntableselectexcel .row-check:checked").closest("tr").each(function () {
            // Only push existing rows with an ID to deletedIds
            let defectId = $(this).find(".rgr_defect_id").val();
            if (defectId) deletedIds.push(defectId);
            $(this).remove();
        });

        renumberRows();
        $("#selectAll").prop("checked", false);
    });

    // 🔹 Initialize existing rows
    $(".mrntableselectexcel tr").each(function () {
        initializeAutocompleteCategory($(this).find(".category-input"));
        initializeSeveritySelect($(this).find(".severity-cell"));

        // Make category/severity readonly if header exists
        if ($(this).find(".rgr_defect_id").length) {
            $(this).find(".category-input, .severity-select").prop("readonly", true);
        }

        // Initialize select2 for existing reasons
        $(this).find(".reason-select2").select2({ tags: true, width: "100%" });
    });

    // 🔹 Select/Deselect All
    $("#selectAll").on("change", function () {
        $(".row-check").prop("checked", this.checked);
    });

    // 🔹 Save button click
    $(".btn-save").click(function (e) {
        e.preventDefault();

        let data = [];
        let valid = true;

        $(".mrntableselectexcel tr").each(function () {
            let defectId = $(this).find(".rgr_defect_id").val() || null;
            let categoryInput = $(this).find(".category-input").val()?.trim();
            let categoryId = $(this).find(".category-input").data("selected-id") || null;
            let severity = $(this).find(".severity-select").val()?.trim();
            let reasons = [];
            console.log("Severity:", severity);
            console.log("Category ID:", categoryId);
            console.log("Category Input:", categoryInput);
            console.log("Row Data:", {
                defectId,
                categoryInput,
                categoryId,
                severity
            });
            $(this).find(".reason-select2 option:selected").each(function () {
                reasons.push({
                    type: $(this).val(),
                    detail_id: $(this).data("detail-id") || null
                });
            });

            // Validation: category, severity, reasons
            if (!categoryInput || !severity || reasons.length === 0) {
                valid = false;
                $(this).addClass("table-danger"); // optional: highlight invalid row
            } else {
                $(this).removeClass("table-danger");
                data.push({
                    rgr_defect_id: defectId,
                    category_id: categoryId,
                    severity: severity,
                    reasons: reasons
                });
            }
        });

        if (!valid) {
            Swal.fire({
                icon: "error",
                title: "Validation Error",
                text: "Please fill Category, Severity, and at least one Reason for all rows."
            });
            return;
        }

        if (data.length === 0) {
            Swal.fire({
                icon: "error",
                title: "No Data",
                text: "Please fill at least one row before saving."
            });
            return;
        }

        $(this).prop("disabled", true);

        $.ajax({
            url: "{{ route('rgrd.store') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                rows: data,
                deleted_ids: deletedIds
            },
            success: function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Saved",
                    text: "Data has been saved successfully!"
                });
                window.location.reload();
                deletedIds = [];
            },
            error: function (xhr) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON?.message || "Something went wrong."
                });
            },
            complete: function () {
                $(".btn-save").prop("disabled", false);
            }
        });
    });

});
</script>


@endsection
	
@extends('layouts.app')
@section('content')

<div class="app-content content">
  <div class="content-overlay"></div>
  <div class="header-navbar-shadow"></div>
  <div class="content-wrapper container-xxl p-0">
    {{-- Header --}}
    <div class="content-header pocreate-sticky">
      <div class="row">
        <div class="content-header-left col-md-6 mb-2">
          <div class="row breadcrumbs-top">
            <div class="col-12">
              <h2 class="content-header-title float-start mb-0">Maintenance Order</h2>
              <div class="breadcrumb-wrapper">
                <ol class="breadcrumb">
                  <li class="breadcrumb-item"><a href="{{ route('/') }}">Home</a></li>
                  <li class="breadcrumb-item active">Add New</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
          <div class="form-group breadcrumb-right">
            <a href="{{ route('maint-wo.index') }}"><button class="btn btn-secondary btn-sm mb-50 mb-sm-0">
              <i data-feather="arrow-left-circle"></i> Back
            </button></a>	
            <button class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" id="save-draft-btn">
              <i data-feather="save"></i> Save as Draft
            </button>
            <button type="submit" form="maint-wo-form" class="btn btn-primary btn-sm" id="submit-btn">
              <i data-feather="check-circle"></i> Submit
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Body --}}
    <div class="content-body">
      <form id="maint-wo-form" method="POST" action="{{ route('maint-wo.store') }}" enctype="multipart/form-data">
        @csrf

        {{-- Hidden fields (single copy) --}}
        <input type="hidden" name="book_code" id="book_code_input">
        <input type="hidden" name="doc_number_type" id="doc_number_type">
        <input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern">
        <input type="hidden" name="doc_prefix" id="doc_prefix">
        <input type="hidden" name="doc_suffix" id="doc_suffix">
        <input type="hidden" name="doc_no" id="doc_no">
        <input type="hidden" name="document_status" id="document_status">

        <input type="hidden" name="spare_parts" id="spare_parts">
        <input type="hidden" name="selected_equipment_id" id="selected_equipment_id">
        <input type="hidden" name="selected_bom_id" id="selected_bom_id">
        <input type="hidden" name="maintenance_detail_id" id="maintenance_detail_id">
        <input type="hidden" name="equipment_due_date" id="equipment_due_date" value="">
        <input type="hidden" name="checklist_data" id="checklist_data">
        <input type="hidden" name="equipment_details" id="equipment_details">
        <input type="hidden" name="equipment_maintenance_type_name" id="equipment_maintenance_type_name">

        {{-- readonly/selection data --}}
        <input type="hidden" name="defect_notification_id" id="defect_notification_id_hidden" value="">
        <input type="hidden" name="equipment_category" id="equipment_category_hidden" value="Machinery">
        <input type="hidden" name="equipment_name" id="equipment_name_hidden" value="">
        <input type="hidden" name="defect_type" id="defect_type_hidden" value="">
        <input type="hidden" name="problem" id="problem_hidden" value="">
        <input type="hidden" name="report_date_time" id="report_date_time_hidden" value="">
        <input type="hidden" name="reported_by" id="reported_by_hidden" value="">

        <section id="basic-datatable">
          <div class="row">
            {{-- Basic Info --}}
            <div class="col-12">
              <div class="card">
                <div class="card-body customernewsection-form">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                        <div>
                          <h4 class="card-title text-theme">Basic Information</h4>
                          <p class="card-text">Fill the details</p>
                        </div>
                      </div>
                    </div>

                    <div class="col-md-8">
                      <div class="row align-items-center mb-1">
                        <div class="col-md-3">
                          <label class="form-label">Series <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-5">
                          <select class="form-select" id="book_id" name="book_id" required>
                            @if(isset($series) && count($series) > 0)
                              @foreach($series as $index => $book)
                                <option value="{{ $book->id }}" {{ $index === 0 ? 'selected' : '' }}>
                                  {{ $book->book_code }}
                                </option>
                              @endforeach
                            @else
                              <option value="">No series available</option>
                            @endif
                          </select>
                        </div>
                      </div>

                      <div class="row align-items-center mb-1">
                        <div class="col-md-3">
                          <label class="form-label">Doc No <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-5">
                          <input type="text" class="form-control" id="document_number" name="document_number" required>
                        </div>
                      </div>

                      <div class="row align-items-center mb-1">
                        <div class="col-md-3">
                          <label class="form-label">Doc Date <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-5">
                          <input type="date" value="{{ date('Y-m-d') }}" class="form-control" id="document_date" name="document_date" min="{{ date('Y-m-d') }}" required>
                        </div>
                      </div>

                      <div class="row align-items-center mb-1">
                        <div class="col-md-3">
                          <label class="form-label">Location <span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-5">
                          <select class="form-select" name="location_id" id="location_id" required>
                            @foreach($locations ?? [] as $location)
                              <option value="{{ $location->id }}">{{ $location->store_name }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>

                      {{-- Reference From --}}
                      <div class="row align-items-center mb-1 selection_section">
                        <div class="col-md-3">
                          <label class="form-label">Reference From</label>
                        </div>
                        <div class="col-md-5 action-button">
                          <input type="hidden" name="reference_type" id="reference_type" value="">
                          <button type="button" id="equipment_ref_btn" onclick="selectEquipmentReference()" data-bs-toggle="modal" data-bs-target="#reference" class="btn btn-outline-primary btn-sm mb-0 reference-btn">
                            <i data-feather="plus-square"></i> Equipment
                          </button>
                          <button type="button" id="defect_ref_btn" onclick="selectDefectNotificationReference()" data-bs-toggle="modal" data-bs-target="#defectlog" class="btn btn-outline-primary btn-sm mb-0 reference-btn">
                            <i data-feather="plus-square"></i> Defect Notification
                          </button>
                          <div id="reference_type_error" class="text-danger mt-1" style="display:none;">
                            Please select at least one reference type (Equipment or Defect Notification)
                          </div>
                        </div>
                      </div>

                    </div> {{-- /col-md-8 --}}
                  </div> {{-- /row --}}
                </div>
              </div>
            </div>

            {{-- Equipment Details --}}
            <div class="col-12">
              <div class="card quation-card">
                <div class="card-header newheader">
                  <h4 class="card-title">Equipment Details</h4>
                </div>
                <div class="card-body">
                  <div class="row">

                    <div class="col-md-3 basic-equipment-field">
                      <div class="mb-1">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" placeholder="Select" value="" class="form-control ledgerselecct" id="equipment_category" readonly />
                      </div>
                    </div>

                    <div class="col-md-3 basic-equipment-field">
                      <div class="mb-1">
                        <label class="form-label">Equipment <span class="text-danger">*</span></label>
                        <input type="hidden" name="equipment_id" id="equipment_id" value="">
                        <input type="text" placeholder="Select Equipment" class="form-control ledgerselecct" id="equipment_name" readonly required>
                        {{-- If you open a selector elsewhere, keep button here if needed
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" data-bs-toggle="modal" data-bs-target="#reference">
                          <i data-feather="search"></i> Select Equipment
                        </button>
                        --}}
                      </div>
                    </div>

                    <div class="col-md-3 basic-equipment-field">
                      <div class="mb-1">
                        <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="equipment_maintenance_type_id" id="maintenance_type" disabled required>
                          @php
                            $allMaintenanceTypes = [];
                            foreach(($maintenanceTypesByEquipment ?? []) as $equipmentId => $types) {
                              foreach($types as $type) {
                                $allMaintenanceTypes[$type['id']] = $type['name'];
                              }
                            }
                          @endphp
                          @foreach($allMaintenanceTypes as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field">
                      <div class="mb-1" id="defect_type_field">
                        <label class="form-label">Defect Type</label>
                        <select class="form-select" name="defect_type" id="defect_type_select">
                          <option value="">Select</option>
                          <option value="General Defect">General Defect</option>
                          <option value="Breakdown">Breakdown</option>
                          <option value="Quality-based">Quality-based</option>
                          <option value="Preventive">Preventive</option>
                          <option value="Corrective">Corrective</option>
                          <option value="Emergency">Emergency</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field">
                      <div class="mb-1" id="problem_field">
                        <label class="form-label">Problem <span class="text-danger">*</span></label>
                        <input type="text" value="Please resolve ASAP" disabled class="form-control" />
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field" id="priority_field">
                      <div class="mb-1">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                          <option value="">Select Priority</option>
                          <option value="Low">Low</option>
                          <option value="Medium" selected>Medium</option>
                          <option value="High">High</option>
                          <option value="Critical">Critical</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field">
                      <div class="mb-1" id="report_date_field">
                        <label class="form-label">Report Date & Time</label>
                        {{-- display-only, do not parse server-side --}}
                        <input type="text" value="22-07-2025 | 02:30 PM" disabled class="form-control" />
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field">
                      <div class="mb-1" id="report_by_field">
                        <label class="form-label">Reported by</label>
                        <input type="text" value="Aniket" disabled class="form-control" />
                      </div>
                    </div>

                    <div class="col-md-9 equipment-detail-field">
                      <div class="mb-1" id="detailed_observations_field">
                        <label class="form-label">Detailed observations</label>
                        <textarea name="detailed_observations" class="form-control" id="detailed_observations" rows="3" placeholder="Enter detailed observations"></textarea>
                      </div>
                    </div>

                    <div class="col-md-3 equipment-detail-field" id="supporting_documents_field">
                      <div class="mb-1">
                        <label class="form-label"><i data-feather="paperclip"></i> Supporting Documents <span class="text-danger">*</span></label><br/>
                        <div class="mt-50">
                          <input type="file" name="supporting_documents[]" id="supporting_documents" class="form-control" multiple 
                                 onchange="checkFileTypeandSize(event, '#supporting_preview')"
                                 accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf">
                          <span class="text-primary small">{{__("message.attachment_caption")}}</span>
                          <div id="supporting_preview" class="mt-2"></div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            {{-- Checklist & Spare Parts Tabs --}}
            <div class="col-12">
              <div class="card">
                <div class="card-body customernewsection-form">
                  <div class="border-bottom mb-2 pb-25">
                    <div class="row">
                      <div class="col-md-6">
                        <div class="newheader">
                          <h4 class="card-title text-theme">Checklist and Defect Detail</h4>
                          <p class="card-text">Fill the details</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="step-custhomapp bg-light">
                    <ul class="nav nav-tabs my-25 custapploannav" role="tablist" id="main-tabs">
                      <li class="nav-item" id="checklist-tab">
                        <a class="nav-link active" data-bs-toggle="tab" href="#payment">Checklist</a>
                      </li>
                      <li class="nav-item" id="spare-parts-tab">
                        <a class="nav-link" data-bs-toggle="tab" href="#attachment">Spare Parts</a>
                      </li>
                    </ul>
                  </div>

                  <div class="tab-content pb-1">
                    {{-- Checklist tab --}}
                    <div class="tab-pane active" id="payment">
                      <div class="row">
                        <div class="col-md-12">
                          <div class="table-responsive pomrnheadtffotsticky1">
                            <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                              <thead>
                                <tr>
                                  <th style="width:30px">#</th>
                                  <th width="250">Checklist</th>
                                  <th>Maintenance</th>
                                </tr>
                              </thead>
                              <tbody class="mrntableselectexcel1">
                                {{-- dynamically populated by JS (populateChecklistTable) --}}
                               
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                    {{-- Spare parts tab --}}
                      <div class="tab-pane" id="attachment">
                      <div class="border-bottom mb-2 pb-25">
                        <div class="row">
                          <div class="col-md-6">
                            <div class="newheader">
                              <h4 class="card-title text-theme">Spare Parts Detail</h4>
                              <p class="card-text">Fill the details</p>
                            </div>
                          </div>
                          <div class="col-md-6 text-sm-end">
                            <a href="#" class="btn btn-sm btn-outline-danger me-50" id="delete">
                              <i data-feather="x-circle"></i> Delete</a>
                            <a href="#" class="btn btn-sm btn-outline-primary" id="addNewRowBtn">
                              <i data-feather="plus"></i> Add New Item</a>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-12">
						<div class="table-responsive pomrnheadtffotsticky">
											<table id="itemTable"
												class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
												<thead>
													<tr>
														<th width="62" class="customernewsection-form">
															<div class="form-check form-check-primary custom-checkbox">
																<input type="checkbox" class="form-check-input"
																	id="checkAll">
																<label class="form-check-label" for="Email"></label>
															</div>
														</th>
														<th width="285">Item Code</th>
														<th width="208">Item Name</th>
														<th>Attributes</th>
														<th>UOM</th>
														<th>Qty</th>
														<th>Rate</th>
														<th>Available Stock</th>
													</tr>
												</thead>
												<tbody class="mrntableselectexcel">
													<tr class="trselected">
														<td class="customernewsection-form">
															<div class="form-check form-check-primary custom-checkbox">
																<input type="checkbox" class="form-check-input row-check"
																	id="Email">
																<label class="form-check-label" for="Email"></label>
															</div>
														</td>
														<td class="poprod-decpt">
															<input type="hidden" class="item_id">
															<input type="text" placeholder="Select" name="item[]"
																class="item_code form-control mw-100 ledgerselecct mb-25" />
														</td>
														<td class="poprod-decpt">
															<input type="text" placeholder="Select"
																class="item_name form-control mw-100 ledgerselecct mb-25" />
														</td>

														<td class="poprod-decpt">
															<input type="hidden" class="attribute">
															<div class="d-flex flex-wrap gap-1" id="attribute-badges">
																<!-- Attribute badges will be displayed here -->
															</div>
														</td>
														<td>
															<select class="uom form-select mw-100" name="uom[]">

															</select>
														</td>
														<td><input type="number" class="qty form-control mw-100"
																name="qty[]" /></td>
														<td><input type="number" class="rate form-control mw-100"
																name="rate[]" /></td>
														<td><input type="number" class="available_stock form-control mw-100"
																name="available_stock[]"  readonly /></td>
													</tr>
												</tbody>
												<tfoot>
													<tr>
														<td colspan="6" class="text-end">Total</td>
														<td class="fw-bolder text-dark text-end settleTotal">0</td>
														<td></td>
													</tr>
													
													<tr valign="top">
														<td colspan="4" rowspan="4">
															<table class="table border">
																<tr>
																	<td class="p-0">
																		<h6
																			class="text-dark mb-0 bg-light-primary py-1 px-50">
																			<strong>Part Details</strong>
																		</h6>
																	</td>
																</tr>
																<tr>
																	<td class="poprod-decpt">
																		<span
																			class="poitemtxt mw-100"><strong>Name</strong>:<span
																				id="part_name"></span></span>
																	</td>
																</tr>
																<tr>
																	<td class="poprod-decpt" id="attributes_badges">
																		
																	</td>
																</tr>
																<tr>
																	<td class="poprod-decpt">
																		<span
																			class="badge rounded-pill badge-light-primary"><strong>Inv.
																				UOM</strong>: <span id="uom"></span></span>
																		<span
																			class="badge rounded-pill badge-light-primary"><strong>Qty.</strong>:
																			<span id="qty"></span></span>
																		<span
																			class="badge rounded-pill badge-light-primary"><strong>Rate</strong>:
																			<span id="rate"></span></span>
																		<span
																			class="badge rounded-pill badge-light-primary"><strong>Available Stock</strong>:
																			<span id="available_stock"></span></span>
																	</td>
																</tr>
																<tr>
																	{{-- <td class="poprod-decpt">
																		<span
																			class="badge rounded-pill badge-light-secondary"><strong>Remarks</strong>:
																			<span id="remarks"></span></span>
																	</td> --}}
																</tr>
															</table>
														</td>

													</tr>

												</tfoot>
											</table>
										</div>
                        </div>
                      </div>

                    </div>{{-- /tab-pane --}}
                  </div>{{-- /tab-content --}}
                </div>
              </div>
            </div>

          </div>
        </section>

        {{-- Upload + Remarks --}}
        <div class="row mt-2">
          <div class="col-md-4">
            <div class="mb-1">
              <label class="form-label"><i data-feather="paperclip"></i> Upload Document</label>
              <input type="file" name="upload_file[]" id="upload_file" class="form-control" multiple
                     onchange="checkFileTypeandSize(event, '#upload_preview')"
                     accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf">
              <span class="text-primary small">{{__("message.attachment_caption")}}</span>
              <div id="upload_preview" class="mt-2"></div>
            </div>
          </div>
          <div class="col-md-12">
            <div class="mb-1">
              <label class="form-label">Final Remarks</label>
              <textarea rows="4" class="form-control" name="final_remark" placeholder="Enter Remarks here..."></textarea>
            </div>
          </div>
        </div>

        {{-- ===================== Modals (single copies) ===================== --}}

        {{-- Filter Modal --}}
        <div class="modal modal-slide-in fade filterpopuplabel" id="filter" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog sidebar-sm">
            <form class="add-new-record modal-content pt-0">
              <div class="modal-header mb-1">
                <h5 class="modal-title">Apply Filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
              </div>
              <div class="modal-body flex-grow-1">
                <div class="mb-1">
                  <label class="form-label" for="fp-range">Select Date</label>
                  <input type="text" id="fp-range" class="form-control flatpickr-range bg-white" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                </div>
                <div class="mb-1">
                  <label class="form-label">Series</label>
                  <select class="form-select"><option>Select</option></select>
                </div>
                <div class="mb-1">
                  <label class="form-label">BOM Name</label>
                  <select class="form-select select2"><option>Select</option></select>
                </div>
                <div class="mb-1">
                  <label class="form-label">Status</label>
                  <select class="form-select">
                    <option>Select</option>
                    <option>Active</option>
                    <option>Inactive</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer justify-content-start">
                <button type="button" class="btn btn-primary data-submit mr-1">Apply</button>
                <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        {{-- Approved/Close Maintenance Modal --}}
        <div class="modal fade" id="approved" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <div>
                  <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">Close the Maintenance</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body pb-2">
                <div class="row mt-1">
                  <div class="col-md-12">
                    <div class="mb-1">
                      <label class="form-label">Remarks <span class="text-danger">*</span></label>
                      <textarea class="form-control"></textarea>
                    </div>
                    <div class="mb-1">
                      <label class="form-label"><i data-feather="paperclip"></i> Upload Document</label>
                      <input type="file" class="form-control" accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf" />
                      <span class="text-primary small">{{__("message.attachment_caption")}}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="modal-footer justify-content-center">
                <button type="reset" class="btn btn-outline-secondary me-1">Cancel</button>
                <button type="reset" class="btn btn-primary">Submit</button>
              </div>
            </div>
          </div>
        </div>

        {{-- Select Equipment (Reference) Modal --}}
        <div class="modal fade text-start" id="reference" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:1000px">
            <div class="modal-content">
              <div class="modal-header">
                <div>
                  <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">Select Equipment</h4>
                  <p class="mb-0">Select from the below list</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <div class="col">
					<div class="mb-1">
						<label class="form-label">Equipment</label>
						<select class="form-control ledgerselecct" name="equipment_id">
							<option value="">Select Equipment</option>
							@foreach($equipments as $equipment)
								<option value="{{ $equipment->id }}">{{ $equipment->name }}</option>
							@endforeach
						</select>
					</div>
				</div>
				
                  <div class="col"><div class="mb-1"><label class="form-label">Maintenance Type</label>
					<select class="form-control ledgerselecct" name="maintenance_type_id">
						<option value="">Select Maintenance Type</option>
						@php
                            $allMaintenanceTypes = [];
                            foreach(($maintenanceTypesByEquipment ?? []) as $equipmentId => $types) {
                              foreach($types as $type) {
                                $allMaintenanceTypes[$type['id']] = $type['name'];
                              }
                            }
                          @endphp
                          @foreach($allMaintenanceTypes as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                          @endforeach
					</select>
					</div></div>
                  <div class="col"><div class="mb-1">
					<label class="form-label">Maint. BOM</label>
					<select class="form-control ledgerselecct" name="maintenance_bom_id">
						<option value="">Select Maint. BOM</option>
						@foreach($maintenanceBoms as $bomData)
							<option value="{{ $bomData['id'] }}">{{ $bomData['display_name'] }}</option>
						@endforeach
					</select>
					</div></div>
                  <div class="col mb-1"><label class="form-label">&nbsp;</label><br/><button type="button" id="equipmentSearchBtn" class="btn btn-warning btn-sm"><i data-feather="search"></i> Search</button></div>

                  <div class="col-md-12">
                    <div class="table-responsive">
                      <table class="mt-1 table table-striped po-order-detail">
                        <thead>
                          <tr>
                            <th width="62" class="customernewsection-form">
                              <div class="form-check form-check-primary custom-checkbox">
                                <input type="checkbox" class="form-check-input sp-select">
                                <label class="form-check-label" for="Email"></label>
                              </div>
                            </th>
                            <th>Equipment</th>
                            <th>Maintenance Type</th>
                            <th>BOM</th>
                            <th>Series</th>
                            <th>Doc No</th>
							<th>Frequency</th>
							<th>Due Date</th>
                          </tr>
                        </thead>
                        <tbody id="eqptTable">
                          {{-- populate via JS --}}
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>
              <div class="modal-footer text-end">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><i data-feather="x-circle"></i> Cancel</button>
                <button id="equipment_process_btn" onclick="processEquipmentSelection()" class="btn btn-primary btn-sm"><i data-feather="check-circle"></i> Process</button>
              </div>
            </div>
          </div>
        </div>

        {{-- Defect Log Modal --}}
        <div class="modal fade text-start" id="defectlog" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:1000px">
            <div class="modal-content">
              <div class="modal-header">
                <div>
                  <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal">Select Defect</h4>
                  <p class="mb-0">Select from the below list</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row">
                  <!-- Filters -->
                  <div class="col">
                    <div class="mb-1">
                      <label class="form-label">Equipment</label>
                      <select class="form-control ledgerselecct" name="defect_equipment_id">
                        <option value="">Select Equipment</option>
						@foreach($equipments as $equipment)
							<option value="{{ $equipment->id }}">{{ $equipment->name }}</option>
						@endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col">
                    <div class="mb-1">
                      <label class="form-label">Defect Type</label>
                      <select class="form-control ledgerselecct" name="defect_type_id">
                        <option value="">Select Defect Type</option>
						@foreach($defectTypes as $defectType)
							<option value="{{ $defectType->id }}">{{ $defectType->name }}</option>
						@endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col">
                    <div class="mb-1">
                      <label class="form-label">Priority</label>
                      <select class="form-select" name="defect_priority">
                        <option value="">Select Priority</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                      </select>
                    </div>
                  </div>
                  <div class="col">
                    <div class="mb-1">
                      <label class="form-label">Series</label>
                      <select class="form-select" id="series_filter" name="series">
                        <option value="">Select Series</option>
						@foreach($defectSeries as $book)
							<option value="{{ $book->id }}">{{ $book->book_code }}</option>
						@endforeach
                      </select>
                    </div>
                  </div>
                  <div class="col mb-1">
                    <label class="form-label">&nbsp;</label><br/>
                    <button class="btn btn-warning btn-sm" id="defect_search_btn">
                      <i data-feather="search"></i> Search
                    </button>
                  </div>

                  <!-- Table -->
                  <div class="col-md-12 mt-3">
                    <div class="table-responsive">
                      <table class="mt-1 table table-striped po-order-detail">
                        <thead>
                          <tr>
                            <th class="customernewsection-form">
                              <div class="form-check form-check-primary custom-radio">
                                <input type="radio" class="form-check-input defect-radio" name="defectRadio" id="defect_header" disabled>
                                <label class="form-check-label" for="defect_header"></label>
                              </div>
                            </th>
                            <th style="width: 100px;">Date</th>
                            <th>Series</th>
                            <th>Doc No</th>
                            <th>Equipment</th>
                            <th>Defect Type</th>
                            <th>Priority</th>
                            <th>Problem</th>
                            <th>Reported By</th>
                          </tr>
                        </thead>
                        <tbody id="defectTable">
                          <tr class="trail-bal-tabl-none">
                            <td colspan="9" class="text-center">No defect notifications found</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>
              <div class="modal-footer text-end">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                  <i data-feather="x-circle"></i> Cancel
                </button>
                <button id="defect_process_btn" onclick="processDefectSelection()" class="btn btn-primary btn-sm">
                  <i data-feather="check-circle"></i> Process
                </button>
              </div>
            </div>
          </div>
        </div>


        {{-- Attribute Modal --}}
		<div class="modal fade" id="attribute" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header p-0 bg-transparent">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body px-sm-2 mx-50 pb-2">
                <h1 class="text-center mb-1" id="shareProjectTitle">Select Attribute</h1>
                <p class="text-center">Enter the details below.</p>

                <div class="table-responsive-md customernewsection-form">
                  <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id="attributes_table_modal" item-index="">
                    <thead>
                      <tr>
                        <th>Attribute Name</th>
                        <th>Attribute Value</th>
                      </tr>
                    </thead>
                    <tbody id="attribute_table"><!-- populated by JS --></tbody>
                  </table>
                </div>
              </div>
              <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('attribute');">Cancel</button>
                <button type="button" class="btn btn-primary submitAttributeBtn" onclick="closeModal('attribute');">Select</button>
              </div>
            </div>
          </div>
        </div>

        {{-- Remarks Modal (kept single) --}}
        <div class="modal fade" id="Remarks" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header p-0 bg-transparent">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body px-sm-2 mx-50 pb-2">
                <h1 class="text-center mb-1">Add/Edit Remarks</h1>
                <p class="text-center">Enter the details below.</p>
                <div class="row mt-2">
                  <div class="col-md-12 mb-1">
                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                    <textarea class="form-control" placeholder="Enter Remarks"></textarea>
                  </div>
                </div>
              </div>
              <div class="modal-footer justify-content-center">
                <button type="reset" class="btn btn-outline-secondary me-1">Cancel</button>
                <button type="reset" class="btn btn-primary">Submit</button>
              </div>
            </div>
          </div>
        </div>



      </form>
    </div>

  </div>
</div>

@endsection

@section('styles')
<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    color: white;
    font-size: 16px;
}

.file-upload-preview {
    cursor: pointer !important;
}

.image-uplodasection {
    position: relative;
    display: inline-block;
}

.expenseadd-sign {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fa;
}
</style>
@endsection

@section('scripts')
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script type="text/javascript" src="{{asset('app-assets/js/file-uploader.js')}}"></script>
    @include('plant.maint_wo.common-js-route',["wo" => isset($wo) ? $wo : null, "route_prefix" => "maint-wo"])
    <script src="{{ asset('assets/js/modules/maint-wo/common-script.js')}}"></script>
        <script>
                const itemsData = @json($items); // ✅ ADDED BACK: Need this for showing first 10 items on click
		
		let rowCount = 1;

		// Function to clear spare parts table and show only single row for defect notifications
		// MUST be defined early to avoid "function not defined" errors
		function clearSparePartsTable() {
			// Find the spare parts table body
			const sparePartsTableBody = $('.mrntableselectexcel');
			
			if (sparePartsTableBody.length > 0) {
				// Clear all existing rows
				sparePartsTableBody.empty();
				
				// Add a single empty row for defect notification spare parts selection
				const singleRow = `
					<tr class="trselected">
						<td class="customernewsection-form">
							<div class="form-check form-check-primary custom-checkbox">
								<input type="checkbox" class="form-check-input row-check" id="Email">
								<label class="form-check-label" for="Email"></label>
							</div>
						</td>
						<td class="poprod-decpt">
							<input type="hidden" class="item_id">
							<input type="text" placeholder="Select" name="item[]"
								class="item_code form-control mw-100 ledgerselecct mb-25" />
						</td>
						<td class="poprod-decpt">
							<input type="text" placeholder="Select"
								class="item_name form-control mw-100 ledgerselecct mb-25" />
						</td>
						<td class="poprod-decpt">
							<input type="hidden" class="attribute">
							<div class="d-flex flex-wrap gap-1" id="attribute-badges">
								<!-- Attribute badges will be displayed here -->
							</div>
						</td>
						<td>
							<select class="uom form-select mw-100" name="uom[]">
							</select>
						</td>
						<td><input type="number" class="qty form-control mw-100" name="qty[]" /></td>
						<td><input type="number" class="rate form-control mw-100" name="rate[]" /></td>
						<td><input type="number" class="available_stock form-control mw-100" name="available_stock[]" readonly /></td>
					</tr>
				`;
				
				// Add the single row to the table
				sparePartsTableBody.html(singleRow);
				
				// Reinitialize autocomplete for the new row
				initAutoForItem('.item_code');
				
				// Clear the spare_parts hidden field
				$('#spare_parts').val('');
				
				// Clear any selected equipment and BOM IDs to prevent spare parts from being fetched
				$('#selected_equipment_id').val('');
				$('#selected_bom_id').val('');
				$('#selected_maintenance_type_id').val('');				
			}
		}
		$(window).on('load', function () {
			if (feather) {
				feather.replace({
					width: 14,
					height: 14
				});
			}
		})

		$(".mrntableselectexcel tr").click(function () {
			$(this).addClass('trselected').siblings().removeClass('trselected');
			value = $(this).find('td:first').html();
		});

		$(document).on('keydown', function (e) {
			// Only handle arrow keys and only when not typing in input/textarea/select
			if ((e.which == 38 || e.which == 40) && 
				!$(e.target).is('input, textarea, select') && 
				$('.trselected').length > 0) {
				
				e.preventDefault(); // Prevent default arrow key behavior
				
				if (e.which == 38) {
					$('.trselected').prev('tr').addClass('trselected').siblings().removeClass('trselected');
				} else if (e.which == 40) {
					$('.trselected').next('tr').addClass('trselected').siblings().removeClass('trselected');
				}
				$('html, body').scrollTop($('.trselected').offset().top - 200);
				updateFooterFromSelected();
			}
		});
		$(document).on('click', '.mrntableselectexcel tr', function () {
			$(this).addClass('trselected').siblings().removeClass('trselected');
			$('html, body').scrollTop($(this).offset().top - 200);
			updateFooterFromSelected();
		});
		function updateFooterFromSelected() {
			let $selected = $('.trselected');
			
			if ($selected.length) {
				
				// Get basic part details
				let partName = $selected.find('.item_name').val() || 'N/A';
				let uomText = $selected.find('.uom option:selected').text() || $selected.find('.uom').val() || 'N/A';
				let qty = $selected.find('.qty').val() || '0';
				let rate = $selected.find('.rate').val() || '0';
				let availableStock = $selected.find('.available_stock').val() || '0';
				
				
				// Update part details display
				$('#part_name').text(partName);
				$('#uom').text(uomText);
				$('#qty').text(qty);
				$('#rate').text(rate);
				$('#available_stock').text(availableStock);
				
				let $selectElement = $selected.find('.item_code');
				let $badgesContainer = $('#attributes_badges'); // container for badges

				// Handle attributes - check for both static and AJAX loaded data
				let attributesData = [];
				
				// First try to get from AJAX loaded data (attribute-enriched hidden field)
				let $enrichedInput = $selected.find('.attribute-enriched');
				if ($enrichedInput.length && $enrichedInput.val()) {
					try {
						attributesData = JSON.parse($enrichedInput.val());
					} catch (e) {
						console.log('Error parsing enriched attributes:', e);
					}
				}
				
				// If no AJAX data, try static data approach
				if (!attributesData.length && $selectElement.val() !== "") {
					let attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
					let $hiddenInput = $selected.find('.attribute');
					let existingAttributes = $hiddenInput.length && $hiddenInput.val()
						? JSON.parse($hiddenInput.val())
						: [];

					if (attributesJSON.length) {
						attributesData = attributesJSON.map(function(element) {
							// Find selected value from existingAttributes
							let selectedValObj = existingAttributes.find(attr => attr.item_attribute_id === element.id);
							let selectedVal = selectedValObj ? selectedValObj.value_id : '';

							// Find text for selected value
							let selectedText = '';
							if (selectedVal) {
								let valObj = element.values_data.find(v => v.id === selectedVal);
								selectedText = valObj ? valObj.value : '';
							}
							
							return {
								group_name: element.group_name,
								selected_value_name: selectedText,
								value: selectedText
							};
						}).filter(attr => attr.selected_value_name || attr.value);
					}
				}

				// Display attributes
				if (attributesData.length) {
					let badgesHtml = '';
					attributesData.forEach(function(attr) {
						let displayValue = attr.selected_value_name || attr.value || 'N/A';
						let groupName = attr.group_name || attr.group_short_name || 'Attribute';
						
						badgesHtml += `
							<span class="badge rounded-pill badge-light-primary" style="margin-right:5px;">
								<strong>${groupName}</strong>: <span>${displayValue}</span>
							</span>
						`;
					});
					$badgesContainer.html(badgesHtml);
				} else {
					$badgesContainer.html('<span class="text-muted">No attributes selected</span>');
				}
			}
		}
		$('#addNewRowBtn').on('click', function () {
			if (!validateExcelRows()) {
				return;
			}
			rowCount++;
			let newRow = `<tr>
															<td class="customernewsection-form">
																<div class="form-check form-check-primary custom-checkbox">
																	<input type="checkbox" class="form-check-input row-check"
																		id="Email">
																	<label class="form-check-label" for="Email"></label>
																</div>
															</td>
															<td class="poprod-decpt">
																<input type="hidden" class="item_id">
																<input type="text" placeholder="Select" name="item[]"
																	class="item_code form-control mw-100 ledgerselecct mb-25" />
															</td>
															<td class="poprod-decpt">
																<input type="text" placeholder="Select"
																	class="item_name form-control mw-100 ledgerselecct mb-25" />
															</td>

															<td class="poprod-decpt">
																<input type="hidden" class="attribute">
																<div class="d-flex flex-wrap gap-1" id="attribute-badges">
																	<!-- Attribute badges will be displayed here -->
																</div>
															</td>
															<td>
																<select class="uom form-select mw-100" name="uom[]">

																</select>
															</td>
															<td><input type="number" class="qty form-control mw-100"  name="qty[]" /></td>
															<td><input type="number" class="rate form-control mw-100"  name="rate[]" /></td>
															<td><input type="number" class="available_stock form-control mw-100"
																	name="available_stock[]"  readonly /></td>
														</tr>																  `;
			$('.mrntableselectexcel').append(newRow);
			initAutoForItem('.item_code');

		});
		$('#delete').on('click', function () {
			let $rows = $('.mrntableselectexcel tr');
			let $checked = $rows.find('.row-check:checked');
			$checked.closest('tr').remove();

		});
		$('#checkAll').on('change', function () {
			let isChecked = $(this).is(':checked');
			$('.mrntableselectexcel .row-check').prop('checked', isChecked);
		});
		
		// Document number generation on book_id change
		$('#book_id').on('change', function () {
			let currentDate = new Date().toISOString().split('T')[0];
			let document_date = $('#document_date').val() || currentDate;
			let bookId = $('#book_id').val();
			let actionUrl = '{{ route('book.get.doc_no_and_parameters') }}' + '?book_id=' + bookId +
				"&document_date=" + document_date;
			fetch(actionUrl).then(response => {
				return response.json().then(data => {
					if (data.status == 200) {
						$("#book_code_input").val(data.data.book_code);
						// Set document type and related fields
						$('#doc_number_type').val(data.data.doc.type || '');
						$('#doc_reset_pattern').val(data.data.doc.reset_pattern || '');
						$('#doc_prefix').val(data.data.doc.prefix || '');
						$('#doc_suffix').val(data.data.doc.suffix || '');
						$('#doc_no').val(data.data.doc.doc_no || '');

						if (data.data.doc.type == 'Manually') {
							// For manual type, only clear if no document number provided and field is empty
							if (!data.data.doc.document_number && !$("#document_number").val()) {
								$("#document_number").val('');
							}
							$("#document_number").attr('readonly', false);
							$("#document_number").attr('placeholder', 'Document Number');
						} else {
							// For automatic type, use the provided document number
							$("#document_number").val(data.data.doc.document_number || '');
							$("#document_number").attr('readonly', true);
							$("#document_number").attr('placeholder', '');
						}

					}
					if (data.status == 404) {
						$("#document_number").val('');
						$('#doc_number_type').val('');
						$('#doc_reset_pattern').val('');
						$('#doc_prefix').val('');
						$('#doc_suffix').val('');
						$('#doc_no').val('');
						showToast('error', data.message);
					}
				});
			});
		});

		$('#book_id').trigger('change');
		initAutoForItem('.item_code');
		function updateJsonData() {
			// Spare parts
			const allRows = [];

			$('.mrntableselectexcel tr').each(function () {
				const row = $(this);
				const itemId = row.find('.item_id').val();

				if (itemId) { // skip empty rows
					const rowData = {
						item_id: itemId,
						item_code: row.find('.item_code').val() || '',
						item_name: row.find('.item_name').val() || '',
						attribute: row.find('.attribute').val() || '',
						qty: row.find('.qty').val() || 0,
						rate: row.find('.rate').val() || 0,
						uom_id: row.find('.uom').val() || '',
						uom_name: row.find('.uom option:selected').text() || '',
						available_stock: row.find('.available_stock').val() || 0,
					};
					allRows.push(rowData);
				}
			});
			$('#spare_parts').val(JSON.stringify(allRows));

			// Checklist
			collectChecklistData();

			// Equipment details - matching edit blade structure
			const equipmentDetails = {
				reference_type: $('#reference_type').val() || '',
				equipment_id: $('#equipment_id').val() || '',
				equipment_name: $('#equipment_name_hidden').val() || $('#equipment_name').val() || '',
				equipment_category: $('#equipment_category_hidden').val() || $('#equipment_category').val() || '',
				equipment_maintenance_type_id: $('#maintenance_type').val() || '',
				equipment_maintenance_type_name: $('#equipment_maintenance_type_name').val() || $('#maintenance_type option:selected').text() || '',
				maintenance_detail_id: $('#maintenance_detail_id').val() || '',
				due_date: $('#equipment_due_date').val() || '',
				defect_notification_id: $('#defect_notification_id_hidden').val() || '',
				equipment_defect_type: $('#defect_type_hidden').val() || $('#defect_type_select').val() || '',
				equipment_problem: $('#problem_hidden').val() || $('#problem').val() || '',
				equipment_priority: $('#priority_field select').val() || '',
				equipment_report_date: $('#report_date_time_hidden').val() || $('#report_date_field input').val() || '',
				equipment_reported_by: $('#reported_by_hidden').val() || $('#report_by_field input').val() || '',
				equipment_detailed_observations: $('#detailed_observations_field textarea').val() || '',
				equipment_supporting_documents: $('#supporting_documents_field input')[0]?.files[0]?.name || ''
			};
			$('#equipment_details').val(JSON.stringify(equipmentDetails));
		}

		// AJAX validation function with visual feedback
		function validateWorkOrder(isDraft = false) {
			const documentNumber = $('#document_number').val().trim();
			const bookId = $('#book_id').val();
			const currentDate = $('#document_date').val();
			
			
			if (!documentNumber || documentNumber.length < 1) {
				return Promise.resolve(true); // No validation needed if document number is empty
			}

			// Clear any existing error state
			resetDocumentInputState();

			return $.ajax({
				url: '{{ route("maint-wo.validate") }}',
				method: 'POST',
				data: {
					_token: '{{ csrf_token() }}',
					document_number: documentNumber
				}
			}).then(function(response) {
				console.log('✅ Document number validation passed');
				return true; // Validation passed
			}).catch(function(xhr) {
				if (xhr.status === 422) {
					const errors = xhr.responseJSON.errors;
					let errorMessage = '';
					
					if (errors.document_number) {
						errorMessage = errors.document_number;
						
						// Add visual feedback - red border
						const documentInput = document.getElementById('document_number');
						if (documentInput) {
							documentInput.style.border = '1px solid red';
						}
					}
					
					Swal.fire({
						icon: 'error',
						title: 'Validation Error!',
						text: errorMessage,
						confirmButtonText: 'OK',
						confirmButtonColor: '#d33'
					}).then(() => {
						// Focus on document number field after closing alert
						if (document.getElementById('document_number')) {
							document.getElementById('document_number').focus();
						}
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Validation Error!',
						text: 'Unable to validate document number. Please try again.',
						confirmButtonText: 'OK',
						confirmButtonColor: '#d33'
					});
				}
				return false; // Validation failed
			});
		}

		// Function to reset document input visual state
		function resetDocumentInputState() {
			const documentInput = document.getElementById('document_number');
			if (documentInput) {
				documentInput.style.border = '';
			}
		}

		document.getElementById('save-draft-btn').addEventListener('click', function () {
			// Validate even for draft
			validateWorkOrder(true).then(function(isValid) {
				if (isValid) {
					// Clear any existing error states
					clearFieldErrors();
					
					$('.preloader').show();
					document.getElementById('document_status').value = 'draft';
					updateJsonData();
					
					// Submit via AJAX
					submitFormAjax();
				}
			});
		});


		$('#maint-wo-form').on('submit', function (e) {
			e.preventDefault(); // Always prevent default first

			console.log('🔍 Form submission started - beginning validation...');

			// Validate reference type selection
			let referenceType = $('#reference_type').val();
			if (!referenceType) {
				Swal.fire({
					icon: 'error',
					title: 'Reference Required!',
					text: 'Please select a reference type (Equipment or Defect Notification)',
					confirmButtonText: 'OK',
					confirmButtonColor: '#d33'
				});
				$('#reference_type_error').show();
				return false;
			}

			// Validate that an item is selected from the chosen reference
			if (referenceType === 'equipment') {
				const equipmentId = $('#equipment_id').val();
				if (!equipmentId) {
					Swal.fire({
						icon: 'error',
						title: 'Equipment Selection Required!',
						text: 'Please select an equipment from the equipment reference before submitting.',
						confirmButtonText: 'OK',
						confirmButtonColor: '#d33'
					});
					return false;
				}
			} else if (referenceType === 'defect_notification') {
				const defectNotificationId = $('#defect_notification_id_hidden').val();
				if (!defectNotificationId) {
					Swal.fire({
						icon: 'error',
						title: 'Defect Notification Selection Required!',
						text: 'Please select a defect notification from the defect reference before submitting.',
						confirmButtonText: 'OK',
						confirmButtonColor: '#d33'
					});
					return false;
				}
			}

			// Validate file upload
			const uploadFileInput = document.getElementById('upload_file');
			if (uploadFileInput.files.length > 0 && !validateFile(uploadFileInput)) {
				return false; // Stop submission if file validation fails
			}

			// Validate checklist items
			if (!validateChecklistItems()) {
				console.log('❌ Checklist validation failed - preventing form submission');
				return false;
			}

			// Validate items (spare parts) - check quantity > 0 and within stock limits
			console.log('🔍 Validating spare parts items...');
			if (!validateItemRows()) {
				console.log('❌ Item validation failed - quantity 0 or exceeds stock');
				return false;
			}

			// Validate document number and work order name
			const formElement = this;
			validateWorkOrder(false).then(function(isValid) {
				if (isValid) {
					console.log('✅ All validations passed - submitting form');
					// Clear any existing error states
					clearFieldErrors();
					
					$('.preloader').show();
					document.getElementById('document_status').value = 'submitted';
					updateJsonData();
					
					// Submit via AJAX
					submitFormAjax();
				}
			});
		});

		// AJAX form submission function
		function submitFormAjax() {
			// Create FormData for AJAX submission (handles file uploads)
			const formData = new FormData($('#maint-wo-form')[0]);
			
			$.ajax({
				url: $('#maint-wo-form').attr('action'),
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				success: function(response) {
					$('.preloader').hide();
					
					// Show success message
					Swal.fire({
						icon: 'success',
						title: 'Success!',
						text: response.message,
						confirmButtonText: 'OK',
						confirmButtonColor: '#28a745'
					}).then(() => {
						// Redirect to index page
						if (response.redirect_url) {
							window.location.href = response.redirect_url;
						}
					});
				},
				error: function(xhr) {
					$('.preloader').hide();
					
					if (xhr.status === 422) {
						// Validation errors - show field-specific errors
						const errors = xhr.responseJSON.errors;
						displayFieldErrors(errors);
						
						// Also show SweetAlert with summary
						let errorMessages = [];
						Object.keys(errors).forEach(field => {
							errorMessages.push(errors[field][0]);
						});
						
						Swal.fire({
							icon: 'error',
							title: 'Validation Error',
							html: errorMessages.join('<br>'),
							confirmButtonText: 'OK',
							confirmButtonColor: '#d33'
						});
					} else {
						// Other errors
						const errorMessage = xhr.responseJSON?.message || 'Something went wrong. Please try again.';
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: errorMessage,
							confirmButtonText: 'OK',
							confirmButtonColor: '#d33'
						});
					}
				}
			});
		}

		// Function to clear all field errors
		function clearFieldErrors() {
			// Remove error classes and messages
			$('.form-control, .form-select').removeClass('is-invalid');
			$('.invalid-feedback').remove();
		}

		// Function to display field-specific errors
		function displayFieldErrors(errors) {
			Object.keys(errors).forEach(fieldName => {
				const errorMessage = errors[fieldName][0];
				
				// Find the field element
				let fieldElement = $(`[name="${fieldName}"]`);
				
				// Handle specific field mappings
				if (fieldName === 'book_id') {
					fieldElement = $('#book_id');
				} else if (fieldName === 'document_number') {
					fieldElement = $('#document_number');
				} else if (fieldName === 'document_date') {
					fieldElement = $('#document_date');
				} else if (fieldName === 'location_id') {
					fieldElement = $('#location_id');
				} else if (fieldName === 'reference_type') {
					fieldElement = $('#reference_type');
				}
				
				if (fieldElement.length > 0) {
					// Add error class
					fieldElement.addClass('is-invalid');
					
					// Remove existing error message for this field
					fieldElement.siblings('.invalid-feedback').remove();
					
					// Add error message
					fieldElement.after(`<div class="invalid-feedback">${errorMessage}</div>`);
				}
			});
		}

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

		@if (session('success'))
			$('.preloader').hide();
			showToast("success", "{{ session('success') }}");
		@endif

		@if (session('error'))
			$('.preloader').hide();
			showToast("error", "{{ session('error') }}");
		@endif

		@if ($errors->any())
			$('.preloader').hide();
			showToast('error',
				"@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach"
			);
		@endif
		$(document).on('input change', '.qty, .rate, .uom, .item_name, .item_code, .attribute', function () {
			updateFooterFromSelected();
		});


		
		function initAutoForItem(selector, type) {
			
			$(selector).autocomplete({
				minLength: 0, // ✅ Allow showing items without typing
				source: function (request, response) {
					let term = request.term.toLowerCase();

					// Gather all already selected item IDs from other rows
					let selectedItemIds = [];
					$('.item_id').each(function () {
						let val = $(this).val();
						if (val) selectedItemIds.push(val);
					});

					// ✅ If no search term, return first 10 items from initial load
					if (!term.trim()) {
						let initialItems = itemsData.filter(item => {
							let currentItemId = $(selector).closest('tr').find('.item_id').val();
							return !selectedItemIds.includes(item.id.toString()) || item.id.toString() === currentItemId;
						}).slice(0, 10); // Show first 10 items

						let results = initialItems.map(item => ({
							id: item.id,
							label: `${item.item_code} - ${item.item_name}`,
							code: item.item_code,
							item_id: item.id,
							item_name: item.item_name,
							uom_name: item.uom_name,
							uom_id: item.uom_id,
							attr: item.item_attributes || [],
							available_stock: item.available_stock || 0
						}));

						response(results);
						return;
					}

					// ✅ NEW: Use server-side API for search
					$.ajax({
						url: "{{ route('maint-wo.search-items') }}",
						method: 'GET',
						data: {
							q: term,
							page: 1,
							per_page: 20
						},
						success: function(data) {
							// Filter out already selected items and format for autocomplete
							let filtered = data.items.filter(item => {
								let currentItemId = $(selector).closest('tr').find('.item_id').val();
								return !selectedItemIds.includes(item.id.toString()) || item.id.toString() === currentItemId;
							});

							let results = filtered.map(item => ({
								id: item.id,
								label: `${item.item_code} - ${item.item_name}`,
								code: item.item_code,
								item_id: item.id,
								item_name: item.item_name,
								uom_name: item.uom_name,
								uom_id: item.uom_id,
								attr: item.item_attributes || [],
								available_stock: item.available_stock || 0
							}));

							response(results);
						},
						error: function() {
							response([]);
						}
					});
				},
				select: function (event, ui) {
					let $input = $(this);
					let itemCode = ui.item.code;
					let attr = ui.item.attr;
					let itemName = ui.item.item_name;
					let itemId = ui.item.item_id;
					let uomId = ui.item.uom_id;
					let uomName = ui.item.uom_name;
					let availableStock = ui.item.available_stock || 0;

					$input.attr('data-name', itemName);
					$input.attr('data-code', itemCode);
					$input.attr('data-attr', JSON.stringify(attr));
					$input.attr('data-id', itemId);
					$input.closest('tr').find('.item_id').val(itemId);
					$input.closest('tr').find('.item_name').val(itemName);
					$input.val(itemCode);

					let uomOption = `<option value="${uomId}">${uomName}</option>`;
					$input.closest('tr').find('.uom').empty().append(uomOption);
					$input.closest('tr').find('.available_stock').val(availableStock);

					// Update attribute badges using BOM-style function
					let $currentRow = $input.closest('tr');
					updateAttributeBadges($currentRow);
					
					// Automatically open attribute modal if item has attributes
					if (attr && attr.length > 0) {
						setTimeout(() => {
							// Trigger attribute modal by simulating click on attribute button logic
							let $tr = $input.closest('tr');
							let $attributesTable = $('#attribute_table');
							$attributesTable.data('currentRow', $tr);
							
							// Populate modal with attributes
							let attributesJSON = attr;
							let $hiddenInput = $tr.find('.attribute');
							let existingAttributes = $hiddenInput.length && $hiddenInput.val()
								? JSON.parse($hiddenInput.val())
								: [];

							if (attributesJSON.length > 0) {
								let innerHtml = ``;
								$.each(attributesJSON, function (index, element) {
									let optionsHtml = ``;
									$.each(element.values_data, function (i, value) {
										let isSelected = existingAttributes.some(attr =>
											attr.item_attribute_id === element.id && attr.value_id === value.id
										);
										optionsHtml += `<option value='${value.id}' ${isSelected ? 'selected' : ''}>${value.value}</option>`;
									});

									innerHtml += `
										<tr>
											<td>
												${element.group_name}
												<input type="hidden" name="id" value="${element.id}">
											</td>
											<td>
												<select class="form-select select2" style="max-width:100% !important;">
													<option value="">Select</option>
													${optionsHtml}
												</select>
											</td>
										</tr>
									`;
								});

								$attributesTable.html(innerHtml);
								$attributesTable.find('select').off('change').on('change', function () {
									changeAttributeVal($tr);
								});
								$attributesTable.find('select').select2();
								
								// Open the modal
								$('#attribute').modal('show');
							}
						}, 100);
					} else {
						$input.closest('tr').find('#attribute-badges').html('');
						setTimeout(() => {
							$input.closest('tr').find('.qty').val('').focus();
						}, 100);
					}

					return false;
				},
				change: function (event, ui) {
					if (!ui.item) {
						$(this).val("");
						$(this).attr('data-name', '');
						$(this).attr('data-code', '');
						$(this).attr('data-attr', '');
						$(this).closest('tr').find('.item_id').val('');
						$(this).closest('tr').find('.item_name').val('');
						$(this).closest('tr').find('.uom').empty();
						$(this).closest('tr').find('.available_stock').val(0);
					}
				}
			}).focus(function () {
				// ✅ Show first 10 items when field is clicked (no typing required)
				if (!this.value.trim()) {
					$(this).autocomplete("search", "");
				}
			}).on("input", function () {
				if ($(this).val().trim() === "") {
					$(this).removeData("selected");
					$(this).closest('tr').find(".item_name").val('');
					$(this).closest('tr').find(".attribute").val('');
					$(this).closest('tr').find(".item_id").val('');
					$(this).closest('tr').find(".item_code").val('');
					$(this).closest('tr').find(".available_stock").val(0);
				}
			});

			$(selector).autocomplete("instance")._renderItem = function (ul, item) {
				return $("<li>")
					.append(`<div><strong>${item.code}</strong> - ${item.item_name}</div>`)
					.appendTo(ul);
			};
		}

		function changeAttributeVal($row) {
			let hiddenInput = $row.find('.attribute');


			if (!hiddenInput) return;

			// Find the attributes table and tbody
			const attributesTable = document.getElementById("attribute_table");
			const tbody = attributesTable;

			let selectedAttributes = [];

			Array.from(tbody.rows).forEach(row => {
				const hiddenInputAttr = row.querySelector('input[type="hidden"][name="id"]');
				const selectElement = row.querySelector("select");

				if (hiddenInputAttr && selectElement) {
					const attributeId = parseInt(hiddenInputAttr.value, 10);
					const selectedVal = parseInt(selectElement.value, 10);
					
					// Get the attribute name from the row
					const attributeNameCell = row.querySelector('td:first-child');
					const attributeName = attributeNameCell ? attributeNameCell.textContent.trim() : '';
					
					// Get the selected value text
					const selectedOption = selectElement.options[selectElement.selectedIndex];
					const selectedValueText = selectedOption ? selectedOption.textContent.trim() : '';

					if (!isNaN(attributeId) && !isNaN(selectedVal) && selectedVal > 0) {
						selectedAttributes.push({
							item_attribute_id: attributeId,
							value_id: selectedVal,
							attribute_name: attributeName,
							attribute_value: selectedValueText
						});
					}
				}
			});

			// Update hidden input with JSON
			hiddenInput.val(JSON.stringify(selectedAttributes));
		}

		$(document).on('click', '.attributeBtn', function (e) {
			let $tr = $(this).closest('tr');
			let $selectElement = $tr.find('.item_code');
			let $attributesTable = $('#attribute_table'); // modal table
			$attributesTable.data('currentRow', $tr);
			
			// Handle both scenarios: equipment selection and new row addition
			let attributesJSON = [];
			let $hiddenInput = $tr.find('.attribute');
			let existingAttributes = [];
			
			// Scenario 1: Equipment selection (uses .attribute-enriched hidden field with all_values structure)
			let $enrichedInput = $tr.find('.attribute-enriched');
			if ($enrichedInput.length && $enrichedInput.val() && $enrichedInput.val() !== '[]') {
				try {
					let attributeData = JSON.parse($enrichedInput.val());
					// Convert from all_values structure to values_data structure
					attributesJSON = attributeData.map(function(attr) {
						return {
							id: attr.item_attribute_id,
							group_name: attr.group_name,
							values_data: attr.all_values || []
						};
					});
					existingAttributes = attributeData.map(function(attr) {
						return {
							item_attribute_id: attr.item_attribute_id,
							value_id: attr.selected_value_id
						};
					});
				} catch (e) {
					console.error('Error parsing enriched attribute data:', e);
				}
			}
			
			// Scenario 2: New row addition (uses data-attr attribute with values_data structure)
			if (!attributesJSON.length && $selectElement.val() !== "") {
				try {
					attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
					existingAttributes = $hiddenInput.length && $hiddenInput.val()
						? JSON.parse($hiddenInput.val())
						: [];
				} catch (e) {
					console.error('Error parsing data-attr:', e);
				}
			}

			if (!attributesJSON.length) {
				$attributesTable.html(`
						<tr>
							<td colspan="2" class="text-center">No attributes available</td>
						</tr>
					`);
				return;
			}

			let innerHtml = ``;

			$.each(attributesJSON, function (index, element) {
				let optionsHtml = ``;

				$.each(element.values_data, function (i, value) {
					let isSelected = existingAttributes.some(attr =>
						attr.item_attribute_id === element.id && attr.value_id === value.id
					);

					optionsHtml += `
							<option value='${value.id}' ${isSelected ? 'selected' : ''}>${value.value}</option>
						`;
				});

				innerHtml += `
						<tr>
							<td>
								${element.group_name}
								<input type="hidden" name="id" value="${element.id}">
							</td>
							<td>
								<select class="form-select select2" style="max-width:100% !important;">
									<option value="">Select</option>
									${optionsHtml}
								</select>
							</td>
						</tr>
					`;
			});

			$attributesTable.html(innerHtml);

			$attributesTable.find('select').off('change').on('change', function () {
				changeAttributeVal($tr);
			});
			$attributesTable.find('select').select2();
		});
		function closeModal(id) {
			$('#' + id).modal('hide');
		}

		// Simple functions for equipment selection
		function selectEquipmentReference() {
			let locationId = $('#location_id').val();
			loadModal('eqpt',locationId);
			$('#reference_type').val('equipment');
			$('#reference_type_error').hide();
			$('#equipment_ref_btn').removeClass('btn-outline-primary').addClass('btn-primary');
			$('#defect_ref_btn').removeClass('btn-primary').addClass('btn-outline-primary');
			
			// Clear spare parts when switching to equipment selection to ensure clean state
			clearSparePartsTable();
			
			// Show only basic equipment fields, hide detail fields
			$('.basic-equipment-field').show();
			$('.equipment-detail-field').hide();
			
			// Make basic fields read-only immediately
			$('#equipment_category').prop('readonly', true);
			$('#equipment_name').prop('readonly', true);
			// $('#maintenance_type').prop('disabled', true);
			
			// Show checklist tab when equipment is selected
			$('#checklist-tab').show();
		}
		
		function selectDefectNotificationReference() {
			// Check if reference type is actually changing from equipment to defect_notification
			const currentReferenceType = $('#reference_type').val();
			let locationId = $('#location_id').val();
			loadModal('defect',locationId);
			$('#reference_type').val('defect_notification');
			$('#reference_type_error').hide();
			$('#defect_ref_btn').removeClass('btn-outline-primary').addClass('btn-primary');
			$('#equipment_ref_btn').removeClass('btn-primary').addClass('btn-outline-primary');
			
			// Clear due date and maintenance detail ID only when switching FROM equipment TO defect notification
			if (currentReferenceType === 'equipment') {
				$('#equipment_due_date').val('');
				$('#maintenance_detail_id').val('');
			}
			
			// Clear spare parts when switching to defect notification
			clearSparePartsTable();
			
			// Show all equipment detail fields but make them read-only
			$('.basic-equipment-field').show();
			$('.equipment-detail-field').show();
			
			// Make fields read-only for defect notification (they will be populated from selected defect)
			$('#equipment_category').prop('readonly', true);
			$('#equipment_name').prop('readonly', true);
			$('#maintenance_type').prop('disabled', false); // Enable maintenance type for user selection
			
			// Set first maintenance type as default selection if not already selected
			if ($('#maintenance_type option').length > 0 && !$('#maintenance_type').val()) {
				$('#maintenance_type option:first').prop('selected', true);
				$('#maintenance_type').trigger('change'); // Trigger change event to update related fields
			}
			
			// Also disable other equipment detail fields
			$('#defect_type_select').prop('disabled', true);
			$('#priority_field select').prop('disabled', true);
			$('#problem_field input').prop('readonly', true);
			$('#detailed_observations_field textarea').prop('readonly', true);
			$('#report_by_field input').prop('readonly', true);
			$('#supporting_documents_field input').prop('disabled', false);
			
			// Hide checklist tab and show only spare parts tab
			$('#checklist-tab').hide();
			$('#spare-parts-tab a').tab('show'); // Activate spare parts tab
		}

		function processEquipmentSelection() {
			var selectedEquipment = $('input[name="equipment_radio"]:checked');
			
			if (selectedEquipment.length === 0) {
				// Show toaster notification
				showToast('error', 'Please select at least one equipment');
				return false; // Don't close modal
			}
			
			// Get selected equipment data
			var equipmentRow = selectedEquipment.closest('tr');
			var equipmentName = equipmentRow.find('td').eq(0).find('strong').text().trim();
			if (!equipmentName) {
				equipmentName = equipmentRow.find('td').eq(0).text().trim();
			}
			var eqpt = selectedEquipment.data('eqpt');
			
			// Get due date from equipment table (last column)
			var equipmentDueDate = equipmentRow.find('td').last().text().trim();
			
			// Get equipment and BOM IDs for AJAX call
			const equipmentId = selectedEquipment.val();
			const bomId = selectedEquipment.data('bom-id');
			const maintenanceDetailId = selectedEquipment.data('maintenance-detail-id');
			
			// Populate equipment fields
			$('#equipment_name').val(selectedEquipment.data('equipment-name'));
			$('#equipment_id').val(selectedEquipment.data('equipment-id'));
			$('#selected_equipment_id').val(selectedEquipment.data('equipment-id')); // Store for maintenance type handler
			$('#maintenance_type').val(selectedEquipment.data('maintenance-type'));
			
			// Store maintenance detail ID and due date
			$('#maintenance_detail_id').val(maintenanceDetailId);
			$('#equipment_due_date').val(equipmentDueDate !== 'N/A' ? equipmentDueDate : '');
			
			// Keep only basic equipment fields visible and read-only for equipment selection
			$('.equipment-detail-field').hide();
			$('.basic-equipment-field').show();
			$('#equipment_category').prop('readonly', true);
			$('#equipment_name').prop('readonly', true);
			$('#maintenance_type').prop('disabled', true);
			
			// Fetch spare parts via AJAX only if reference type is equipment (not defect notification)
			const referenceType = $('#reference_type').val();
			const maintenanceTypeId = selectedEquipment.data('maintenance-type');
			if (equipmentId && maintenanceTypeId && referenceType === 'equipment') {
				fetchEquipmentSpareParts(equipmentId, maintenanceTypeId);
			} else if (referenceType === 'defect_notification') {
				// Ensure spare parts table is cleared for defect notifications
				clearSparePartsTable();
			}
			
			// Close modal manually
			$('#reference').modal('hide');
			
			return true;
		}

		function processDefectSelection() {
			
			// Set flag to prevent spare parts updates during defect notification processing
			window.processingDefectNotification = true;
			
			let selectedDefect = $('input.defect-radio:checked').attr('id');
			
			// Check if a defect is actually selected
			if (!selectedDefect) {
				showToast('error', 'Please select a defect notification');
				window.processingDefectNotification = false; // Reset flag
				return false;
			}
			
      		let onlyNumber = selectedDefect.replace("defect_row_", "");
  
			if (onlyNumber === "") {
				showToast('error', 'Please select a defect notification');
				return false;
			}

			var defectId = onlyNumber;

      

			$('#defect_process_btn')
				.prop('disabled', true)
				.html('<span class="spinner-border spinner-border-sm"></span> Loading...');

			$.ajax({
				url: "{{ route('defect-notification.get', 'PLACEHOLDER') }}".replace('PLACEHOLDER', defectId),
				type: 'GET',
				success: function(response) {
					if (response.status && response.data) {
						var defect = response.data;


						// Equipment - Set values without triggering events to avoid spare parts UI changes
						if (defect.equipment) {
							// Set equipment fields silently (without triggering change events)
							$('#equipment_id').prop('value', defect.equipment.id);
							$('#selected_equipment_id').prop('value', defect.equipment.id);
							$('#equipment_name').prop('value', defect.equipment.document_number || defect.equipment.name || '');
							
			
						}

						// Defect Type
						if (defect.defect_type) {
							var defectTypeSelect = $('#defect_type_select');
							if (defectTypeSelect.find('option[value="' + defect.defect_type.id + '"]').length === 0) {
								defectTypeSelect.append('<option value="' + defect.defect_type.id + '">' + defect.defect_type.name + '</option>');
							}
							defectTypeSelect.val(defect.defect_type.id).prop('disabled', true);
						}

						// Category
						if (defect.category) {
							$('#equipment_category').val(defect.category.name);
						}

						// Book
						if (defect.book) {
							$('#book_code').val(defect.book.book_code);
						}

						// Location
						if (defect.location) {
							$('#location_name').val(defect.location.name);
						}

						// Priority
						if (defect.priority) {
							$('#priority_field select').val(defect.priority).prop('disabled', true);
						}

						// Problem
						if (defect.problem) {
							$('#problem_field input').val(defect.problem).prop('disabled', true);
						}

						// Detailed Observation
						if (defect.detailed_oberservation) {
							$('#detailed_observation').val(defect.detailed_oberservation).prop('disabled', true);
						}

						// Report Date
						var reportDate = defect.report_date_time ? defect.report_date_time.replace('T', ' ').split('.')[0] : '';
						$('#report_date_field input').val(reportDate).prop('disabled', true);

						if (defect.detailed_oberservation) {
							$('#detailed_observations').val(defect.detailed_oberservation);
						} else {
							$('#detailed_observations').val('');
						}

						
						// Skip maintenance type population for defect notifications to avoid spare parts data
						// Defect notifications should not trigger spare parts or maintenance type functionality
		


						// Hidden fields
						$('#defect_notification_id_hidden').val(defect.id);
						$('#equipment_name_hidden').val(defect.equipment ? defect.equipment.document_number : '');
						$('#defect_type_hidden').val(defect.defect_type ? defect.defect_type.name : '');
						$('#problem_hidden').val(defect.problem);
						$('#report_date_time_hidden').val(reportDate);
						$('#reported_by_hidden').val(defect.created_by || '');

						// Populate equipment_details hidden field for defect notification

						
						var equipmentDetails = {
							equipment_id: defect.equipment ? defect.equipment.id : '',
							equipment_name: defect.equipment ? defect.equipment.name : '',
							equipment_category: defect.category ? defect.category.name : '',
							equipment_maintenance_type_id: $('#maintenance_type').val() || '',
							equipment_maintenance_type_name: $('#maintenance_type option:selected').text() || '',
							defect_notification_id: defect.id || '',
							equipment_defect_type: defect.defect_type ? defect.defect_type.name : '',
							equipment_priority: defect.priority || '',
							equipment_problem: defect.problem || '',
							equipment_report_date: defect.report_date_time || '',
							equipment_reported_by: defect.created_by || '',
							equipment_detailed_observations: $('#detailed_observations_field textarea').val() || '',
							equipment_supporting_documents: '',
							reference_type: 'defect_notification'
						};
						
						$('#equipment_details').val(JSON.stringify(equipmentDetails));

						// Close modal
						$('#defectlog').modal('hide');

						// Clear spare parts after defect notification processing to ensure clean state
						setTimeout(function() {
							clearSparePartsTable();

						}, 100);

						showToast('success', 'Defect notification selected successfully');
					} else {
						showToast('error', 'Invalid defect data received');
					}
				},
				error: function(err) {
					console.error(err);
					showToast('error', 'Failed to load defect details');
				},
				complete: function() {
					$('#defect_process_btn').prop('disabled', false).html('<i data-feather="check-circle"></i> Process');
					// Reset flag to allow normal spare parts functionality after defect processing
					window.processingDefectNotification = false;
	
				}
			});

			return true;
		}

		function showEquipmentFields() {
			
			// Hide all equipment detail fields first
			$('.basic-equipment-field').hide();
			$('.equipment-detail-field').hide();
			
			// Show only basic equipment fields (Category, Equipment, Maintenance Type)
			$('.basic-equipment-field').show();
			
			// Enable the fields for user interaction
			$('#equipment_category').prop('readonly', true); // Keep category readonly with default value
			$('#equipment_name').prop('readonly', true); // Keep equipment readonly until selected
			$('#maintenance_type').prop('disabled', false); // Enable maintenance type selection
			
			// Set first maintenance type as default selection if not already selected
			if ($('#maintenance_type option').length > 0 && !$('#maintenance_type').val()) {
				$('#maintenance_type option:first').prop('selected', true);
				$('#maintenance_type').trigger('change'); // Trigger change event to update related fields
			}
			
			// Clear any previous values from hidden inputs for defect-related fields
			$('#defect_type_hidden').val('');
			$('#problem_hidden').val('');
			$('#report_date_time_hidden').val('');
			$('#reported_by_hidden').val('');
			
		}

		// Maintenance Type change handler to update checklist
		$(document).on('change', '#maintenance_type', function() {
			// Skip maintenance type processing if defect notification is being processed
			if (window.processingDefectNotification) {
				return;
			}
			
			var maintenanceTypeId = $(this).val();
			var maintenanceTypeName = $(this).find('option:selected').data('name') || $(this).find('option:selected').text();
			var equipmentId = $('#selected_equipment_id').val();
			
			// Store maintenance type name in hidden field
			$('#equipment_maintenance_type_name').val(maintenanceTypeName);
			
			if (maintenanceTypeId && equipmentId) {
				// Clear existing checklist
				$('#checklistTableBody').empty();
				
				// Show loading state
				$('#checklistTableBody').html('<tr><td colspan="3" class="text-center">Loading checklists...</td></tr>');
				
				$.ajax({
					url: "{{ route('defect-notification.get-checklists') }}",
					type: 'POST',
					data: {
						_token: $('meta[name="csrf-token"]').attr('content'),
						equipment_id: equipmentId,
						maintenance_type_id: maintenanceTypeId
					},
					success: function(response) {
						$('#checklistTableBody').empty();
						
						if (response.status && response.checklists && response.checklists.length > 0) {
							$.each(response.checklists, function(index, checklist) {
								var inputField = '';
								if (checklist.type === 'boolean') {
									inputField = '<input type="checkbox" class="form-check-input" name="checklist[' + checklist.id + ']" value="1">';
								} else {
									inputField = '<input type="text" class="form-control" name="checklist[' + checklist.id + ']" placeholder="Enter value">';
								}
								
								var row = '<tr>' +
									'<td>' + (index + 1) + '</td>' +
									'<td>' + checklist.name + '</td>' +
									'<td>' + inputField + '</td>' +
									'</tr>';
								
								$('#checklistTableBody').append(row);
							}) 
						} 
					},
					error: function(xhr, status, error) {
						console.error('Error loading checklists:', error);
						$('#checklistTableBody').html('<tr><td colspan="3" class="text-center text-danger">Error loading checklists</td></tr>');
					}
				});
			} else {
				$('#checklistTableBody').html('<tr><td colspan="3" class="text-center text-muted">Please select equipment and maintenance type</td></tr>');
			}
		});

		//Search function for the defect modal 

		$(document).ready(function() {
		var itemsData = @json($items);
			$('#defect_search_btn').on('click', function(e) {
				e.preventDefault();

				var equipmentId = $('select[name="defect_equipment_id"]').val();
				var defectTypeId = $('select[name="defect_type_id"]').val();
				var priority = $('select[name="defect_priority"]').val();
				var series = $('select[name="series"]').val();

	

				$.ajax({
					url: '/plant/maint-wo/filter',
					method: 'POST',
					data: {
						type: 'defect',
						equipment_id: equipmentId,
						defect_type_id: defectTypeId,
						priority: priority,
						series_code: series,
						_token: $('meta[name="csrf-token"]').attr('content')
					},
					beforeSend: function() {
						
					},
					success: function(response) {
	
						
						if(response && response.length > 0) {
							var tbody = '';
							response.forEach(function(defect) {
								tbody += `<tr>
									<td class="customernewsection-form">
										<div class="form-check form-check-primary custom-radio">
											<input type="radio" class="form-check-input defect-radio" name="defect_selection" id="defect_row_${defect.id}"
												value="${defect.id}"
												data-defect-id="${defect.id}"
												data-equipment-id="${defect.equipment?.id ?? ''}"
												data-equipment-name="${defect.equipment?.name ?? 'N/A'}"
												data-defect-type="${defect.defect_type?.name ?? 'N/A'}"
												data-priority="${defect.priority ?? ''}"
												data-problem="${defect.problem ?? ''}"
												data-reported-by="${defect.created_by_user?.name ?? ''}">
											<label class="form-check-label" for="defect_row_${defect.id}"></label>
										</div>
									</td>
									<td><strong>${defect.document_date ? formatDate(defect.document_date) : 'N/A'}</strong></td>
									<td>${defect.book?.book_code ?? 'N/A'}</td>
									<td>${defect.document_number ?? 'N/A'}</td>
									<td>${defect.equipment?.name ?? 'N/A'}</td>
									<td>${defect.defect_type?.name ?? 'N/A'}</td>
									<td>${defect.priority ?? ''}</td>
									<td>${defect.problem ?? ''}</td>
									<td>${defect.created_by_user?.name ?? ''}</td>
								</tr>`;
							});
							$('#defectlog .po-order-detail tbody').html(tbody);
							feather.replace(); // re-render Feather icons
						} else {
							$('#defectlog .po-order-detail tbody').html('<tr><td colspan="9" class="text-center">No defect notifications found</td></tr>');
						}
					},
					error: function(xhr) {
						console.error(xhr);
						showToast('error', 'Failed to fetch filtered defects.');
					},
					complete: function() {
					}
				});
			});

			function formatDate(dateStr) {
				var date = new Date(dateStr);
				var day = ("0" + date.getDate()).slice(-2);
				var month = ("0" + (date.getMonth() + 1)).slice(-2);
				var year = date.getFullYear();
				return `${day}-${month}-${year}`;
			}

			// Reset defect modal filters when modal is closed
		$('#defectlog').on('hidden.bs.modal', function () {
			// Reset all filter fields to default values
			$('select[name="defect_equipment_id"]').val('');
			$('select[name="defect_type_id"]').val('');
			$('select[name="defect_priority"]').val('');
			$('select[name="series"]').val('');
			let locationId = $('#location_id').val();
			// Clear the modal table body to show original data
			loadModal('defect',locationId);
		});

		// Equipment Search Button Handler
		$('#equipmentSearchBtn').on('click', function() {
			const equipmentId = $('select[name="equipment_id"]').val();
			const maintenanceTypeId = $('select[name="maintenance_type_id"]').val();
			const bomId = $('select[name="maintenance_bom_id"]').val();

			if (!equipmentId) {
				Swal.fire({
					title: 'Missing Information',
					text: 'Please select Equipment before searching.',
					icon: 'warning'
				});
				return;
			}

			// Show loading state
			$(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Searching...');

			// Call filter method for equipment
			$.ajax({
				url: '{{ route("maint-wo.filter") }}',
				method: 'POST',
				data: {
					type: 'equipment',
					equipment_id: equipmentId,
					maintenance_type_id: maintenanceTypeId,
					bom_id: bomId,
					location_id: $('#location_id').val(),
					_token: $('meta[name="csrf-token"]').attr('content')
				},
				success: function(response) {
					if (response && response.length > 0) {
						$('#eqptTable').empty();
						
						response.forEach(function (eqpt, idx) {
							const isSelected = window.selectedEquipmentState && window.selectedEquipmentState.equipmentId == eqpt.id;
							const checkedAttribute = isSelected ? 'checked' : '';

							const dueDate = eqpt.next_due_date;

							let row = `
								<tr class="trail-bal-tabl-none">
									<th class="customernewsection-form">
										<div class="form-check form-check-primary custom-radio">
											<input type="radio" class="form-check-input equipment-radio" 
												name="equipment_radio" 
												id="equipment_${eqpt.id}" 
												value="${eqpt?.equipment?.id ?? eqpt.id}"
												data-index="${idx}"
												data-equipment-id="${eqpt?.equipment?.id ?? eqpt.id}" 
												data-equipment-name="${eqpt?.equipment?.name ?? ''}" 
												data-maintenance-type="${eqpt?.maintenance_type?.id ?? ''}"
												data-maintenance-detail-id="${eqpt?.maintenance_detail_id ?? ''}"
												data-bom-id="${eqpt?.bom?.id ?? ''}"
												${checkedAttribute}>
											<label class="form-check-label" for="equipment_${eqpt.id}"></label>
										</div> 
									</th>
									<td><strong>${eqpt?.equipment?.name ?? 'N/A'}</strong></td> 
									<td>${eqpt?.maintenance_type?.name ?? 'N/A'}</td>
									<td>${eqpt?.bom?.bom_name ?? 'N/A'}</td>
									<td>${eqpt?.bom?.book?.book_code ?? 'N/A'}</td>
									<td>${eqpt?.bom?.document_number ?? 'N/A'}</td>
									<td>${eqpt?.frequency ?? 'N/A'}</td>
									<td>${dueDate ?? 'N/A'}</td>
								</tr>`;
							$('#eqptTable').append(row);
						});
						
						window.equipmentModalData = response;
						$('#equipment-modal').modal('show');

						Swal.fire({
							title: 'Success!',
							text: `Found ${response.length} equipment configuration(s).`,
							icon: 'success',
							timer: 2000,
							showConfirmButton: false
						});

					} else {
						$('#eqptTable').html('<tr><td colspan="7" class="text-center">No equipment found for the selected criteria.</td></tr>');
						$('#equipment-modal').modal('show');
						
						Swal.fire({
							title: 'No Results',
							text: 'No equipment found matching the selected criteria.',
							icon: 'info'
						});
					}
				},
				error: function(xhr, status, error) {
					console.error('Equipment search error:', error);
					Swal.fire({
						title: 'Error!',
						text: 'An error occurred while searching for equipment data.',
						icon: 'error'
					});
				},
				complete: function() {
					$('#equipmentSearchBtn').prop('disabled', false).html('<i data-feather="search"></i> Search');
					feather.replace();
				}
			});
		});

		// Function to update attribute badges display (same as BOM)
		function updateAttributeBadges($row) {
			if (!$row) return;

			let $selectElement = $row.find('.item_code');
			let $badgesContainer = $row.find('#attribute-badges');

			if ($selectElement.val() !== "") {
				let $hiddenInput = $row.find('.attribute');
				let existingAttributes = $hiddenInput.length && $hiddenInput.val() ?
					JSON.parse($hiddenInput.val()) :
					[];

				let attr = JSON.parse($selectElement.attr('data-attr') || '[]');

				let badgesHtml = '';
				let selectedCount = 0;
				let displayedCount = 0;

				if (attr && attr.length > 0) {
					// First, count total selected attributes
					attr.forEach(function(attribute) {
						let selectedAttr = existingAttributes.find(selected =>
							selected.item_attribute_id === attribute.id
						);
						if (selectedAttr) {
							selectedCount++;
						}
					});

					// Then display badges (max 2)
					attr.forEach(function(attribute) {
						// Check if this attribute has been selected
						let selectedAttr = existingAttributes.find(selected =>
							selected.item_attribute_id === attribute.id
						);

						// Only show selected attributes
						if (selectedAttr && displayedCount < 2) {
							displayedCount++;
							// Find the selected value from the attribute's values
							let valuesData = attribute.values_data || attribute.values || [];

							let selectedValue = valuesData.find(val => val.id === selectedAttr.value_id);

							if (selectedValue) {
								badgesHtml +=
									`<span class="badge rounded-pill badge-light-primary" style="font-size:10px; margin-right:5px;cursor:pointer">
						<strong>${attribute.group_name}</strong>: ${selectedValue.value}
					</span>`;
							} 
						}
					});

					// Only show "..." if there are more than 2 SELECTED attributes
					if (selectedCount > 2) {
						badgesHtml +=
							'<span style="font-size:10px; color:black; margin-right:5px;cursor:pointer"><strong>...</strong></span>';
					}

					$badgesContainer.html(badgesHtml);

				} else {
					$badgesContainer.html('');
				}
			} else {
				$badgesContainer.html('');
			}
		}

		// Add click event for the entire attribute cell (4th column)
		$('.mrntableselectexcel').on('click', 'td:nth-child(4)', function() {
			var $this = $(this);
			var $tr = $this.closest('tr');
			var $selectElement = $tr.find('.item_code');
			var $attributesTable = $('#attributes_table_modal'); // correct modal table ID
			$attributesTable.data('currentRow', $tr);

			if ($selectElement.val() !== "") {
				let attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
				let $hiddenInput = $tr.find('.attribute');
				let existingAttributes = $hiddenInput.length && $hiddenInput.val()
					? JSON.parse($hiddenInput.val())
					: [];

				if (attributesJSON.length > 0) {
					// Open attribute modal using correct modal ID
					$('#attribute').modal('show');
					
					// Populate attribute modal with data
					populateAttributeModal(attributesJSON, existingAttributes, $tr);
				} else {
					showToast('info', 'No attributes available for this item.');
				}
			} else {
				showToast('warning', 'Please select an item first.');
			}
		});

		// Function to populate attribute modal with actual implementation
		function populateAttributeModal(attributesJSON, existingAttributes, $row) {
			let $modalTable = $('#attributes_table_modal tbody');
			$modalTable.empty();

			let innerHtml = '';
			$.each(attributesJSON, function (index, element) {
				let optionsHtml = '';
				$.each(element.values_data, function (i, value) {
					let isSelected = existingAttributes.some(attr =>
						attr.item_attribute_id === element.id && attr.value_id === value.id
					);
					optionsHtml += `<option value='${value.id}' ${isSelected ? 'selected' : ''}>${value.value}</option>`;
				});

				innerHtml += `
					<tr>
						<td>
							${element.group_name}
							<input type="hidden" name="id" value="${element.id}">
						</td>
						<td>
							<select class="form-select select2" style="max-width:100% !important;">
								<option value="">Select</option>
								${optionsHtml}
							</select>
						</td>
					</tr>
				`;
			});

			$modalTable.html(innerHtml);
			
			// Initialize select2 for the modal dropdowns
			$modalTable.find('.select2').select2({
				dropdownParent: $('#attribute')
			});
		}

		// Add click handler for the Submit Attribute button to save selections
		$(document).on('click', '.submitAttributeBtn', function() {
			let $currentRow = $('#attributes_table_modal').data('currentRow');
			if (!$currentRow) return;

			let selectedAttributes = [];
			
			// Collect selected attribute values from modal
			$('#attributes_table_modal tbody tr').each(function() {
				let $tr = $(this);
				let attributeId = $tr.find('input[name="id"]').val();
				let selectedValueId = $tr.find('select').val();
				
				if (selectedValueId && selectedValueId !== '') {
					selectedAttributes.push({
						item_attribute_id: parseInt(attributeId),
						value_id: parseInt(selectedValueId)
					});
				}
			});

			// Save selected attributes to hidden input
			$currentRow.find('.attribute').val(JSON.stringify(selectedAttributes));
			
			// Update attribute badges display
			updateAttributeBadges($currentRow);
			
			// Close modal
			$('#attribute').modal('hide');
		});

		// Reset equipment filter dropdowns when modal is closed
		$('#reference').on('hidden.bs.modal', function() {
			// Reset Equipment dropdown
			$(this).find('select[name="equipment_id"]').val('').trigger('change');
			
			// Reset Maintenance Type dropdown
			$(this).find('select[name="maintenance_type_id"]').val('').trigger('change');
			
			// Reset Maint. BOM dropdown
			$(this).find('select[name="maintenance_bom_id"]').val('').trigger('change');
			
			// Clear the equipment table results
			$('#eqptTable').html('');
			
			// Clear any selected radio buttons
			$(this).find('input[type="radio"].equipment-radio').prop('checked', false);
			
			// Clear selected equipment state
			window.selectedEquipmentState = null;
			

		});

		// Reset defect notification filter dropdowns when modal is closed
		$('#defectlog').on('hidden.bs.modal', function() {
			// Reset Defect Equipment dropdown
			$(this).find('select[name="defect_equipment_id"]').val('').trigger('change');
			
			// Reset Defect Type dropdown
			$(this).find('select[name="defect_type_id"]').val('').trigger('change');
			
			// Reset Priority dropdown
			$(this).find('select[name="defect_priority"]').val('').trigger('change');
			
			// Reset Series dropdown
			$(this).find('select[name="series"]').val('').trigger('change');
			
			// Clear any selected radio buttons
			$(this).find('input[type="radio"].defect-radio').prop('checked', false);
			

		});



		// Override the populateSparePartsTable function to prevent spare parts population in defect notification mode
		window.originalPopulateSparePartsTable = window.populateSparePartsTable;
		window.populateSparePartsTable = function(sparePartsData) {
			const referenceType = $('#reference_type').val();
			
			
			if (referenceType === 'defect_notification') {
				
				// Force clear and show single row for defect notifications
				clearSparePartsTable();
				return;
			}
			
			// Call original function for equipment mode
			
			if (window.originalPopulateSparePartsTable) {
				window.originalPopulateSparePartsTable(sparePartsData);
			}
		};

		// Function to calculate total for spare parts
		function calculateSparePartsTotal() {
			let total = 0;
			console.log('Starting calculation...');
			
			// Try different selectors
			let rows = $('.mrntableselectexcel tbody tr');
			if (rows.length === 0) {
				console.log('No tbody rows, trying direct tr...');
				rows = $('.mrntableselectexcel tr');
			}
			
			console.log('Using rows:', rows.length);
			
			rows.each(function(index) {
				console.log('Processing row ' + index);
				
				// Try different selectors to find the inputs
				let qtyInput = $(this).find('input[name="qty[]"]');
				let rateInput = $(this).find('input[name="rate[]"]');
				
				if (qtyInput.length === 0) {
					qtyInput = $(this).find('.qty');
				}
				if (rateInput.length === 0) {
					rateInput = $(this).find('.rate');
				}
				
				console.log('Qty input found:', qtyInput.length, 'Rate input found:', rateInput.length);
				
				if (qtyInput.length > 0 && rateInput.length > 0) {
					const qty = parseFloat(qtyInput.val()) || 0;
					const rate = parseFloat(rateInput.val()) || 0;
					const rowTotal = qty * rate;
					total += rowTotal;
					console.log('Row ' + index + ' calculation: qty=' + qty + ', rate=' + rate + ', rowTotal=' + rowTotal);
				} else {
					console.log('Row ' + index + ' skipped - inputs not found');
				}
			});
			console.log('Final total: ' + total);
			$('.settleTotal').text(total.toFixed(2));
		}

		// Event listeners for qty and rate changes - more specific selectors
		$(document).on('input change keyup', '.mrntableselectexcel input.qty, .mrntableselectexcel input.rate', function() {
			console.log('Input changed, calculating total...');
			calculateSparePartsTotal();
		});

		// Also trigger calculation when page loads
		$(document).ready(function() {
			calculateSparePartsTotal();
		});

		});

		function calculateDueDate(startDate, frequency) {
			if (!startDate || !frequency) return null;
			let base = new Date(startDate);
			switch (frequency.trim()) {
				case 'Daily': base.setDate(base.getDate() + 1); break;
				case 'Weekly': base.setDate(base.getDate() + 7); break;
				case 'Monthly': base.setMonth(base.getMonth() + 1); break;
				case 'Quarterly': base.setMonth(base.getMonth() + 3); break;
				case 'Semi-Annually':
				case 'Semi Annually':
				case 'Semi Annualy': base.setMonth(base.getMonth() + 6); break;
				case 'Annually':
				case 'Annualy':
				case 'Yearly': base.setFullYear(base.getFullYear() + 1); break;
			}
			let day = String(base.getDate()).padStart(2, '0');
			let month = String(base.getMonth() + 1).padStart(2, '0');
			let year = base.getFullYear();
			return `${day}-${month}-${year}`;
		}

		function validateExcelRows() {
			let allValid = true;

			$('.mrntableselectexcel tr').each(function () {
				const itemCode  = $(this).find('.item_code');
				const itemName  = $(this).find('.item_name');
				const attribute = $(this).find('.attribute');
				const uom       = $(this).find('.uom');
				const qty       = $(this).find('.qty');
				const attrTd    = $(this).find('td').eq(3);
				const availableStock = $(this).find('.available_stock');

				$(this).find('input, select').removeClass('is-invalid');
				attrTd.removeClass("border border-danger");

				if (!itemCode.val()) {
					itemCode.addClass('is-invalid');
					allValid = false;
					return false;
				}

				if (!itemName.val()) {
					itemName.addClass('is-invalid');
					allValid = false;
					return false;
				}

				// Only validate attributes if the item has attributes
				const currentItemId = $(this).find('.item_id').val();
				let itemHasAttributes = false;
				
				// Check if this item has attributes by looking for attribute data
				if (currentItemId && window.itemsWithAttributes && window.itemsWithAttributes[currentItemId]) {
					itemHasAttributes = window.itemsWithAttributes[currentItemId].length > 0;
				}
				
				// Alternative check: look for attribute-enriched data
				const attributeEnriched = $(this).find('.attribute-enriched').val();
				if (attributeEnriched) {
					try {
						const enrichedData = JSON.parse(attributeEnriched);
						itemHasAttributes = enrichedData && enrichedData.length > 0;
					} catch (e) {
						// Ignore parsing errors
					}
				}
				
				// Only validate attributes if the item actually has attributes
				if (itemHasAttributes) {
					let attrValue = attribute.val();
					if (attrValue === "{}" || !attrValue) {
						attrTd.addClass("border border-danger");
						
						// Show SweetAlert error for attribute selection
						Swal.fire({
							icon: 'error',
							title: 'Attribute Selection Required!',
							html: `Please select at least one attribute for item: <strong>${itemName || 'Unknown Item'}</strong><br><br>Click on the attribute column to select attributes.`,
							confirmButtonText: 'OK',
							confirmButtonColor: '#d33'
						});
						
						allValid = false;
						return false;
					}
				}

				if (!uom.val()) {
					uom.addClass('is-invalid');
					allValid = false;
					return false;
				}

				if (!qty.val() || parseFloat(qty.val()) <= 0) {
					qty.addClass('is-invalid');
					allValid = false;
					return false;
				}

				// Check available stock
				const itemId = $(this).find('.item_id').val();
				const requestedQty = parseFloat(qty.val());

				if (requestedQty > availableStock) {
					qty.addClass('is-invalid');
					Swal.fire({
						title: 'Insufficient Stock!',
						text: `Insufficient stock for ${itemName.val()}. Available: ${availableStock}`,
						icon: 'error',
						confirmButtonText: 'OK'
					});
					allValid = false;
					return false;
				}
			});

			return allValid;
		}

		// Real-time stock validation functions
		function validateStockForRow($row) {
			const itemId = $row.find('.item_id').val();
			const itemName = $row.find('.item_name').val();
			const qty = $row.find('.qty').val();
			const qtyField = $row.find('.qty');
			const availableStock = parseFloat($row.find('.available_stock').val()) || 0; // ✅ Use stored value instead of looking up in itemsData

			console.log('🧪 validateStockForRow called:', {
				itemId,
				itemName,
				qty,
				qtyField: qtyField.length,
				availableStock
			});

			// Clear previous validation
			qtyField.removeClass('is-invalid');

			// Only validate if we have item and quantity
			if (!itemId || !qty || parseFloat(qty) <= 0) {
				console.log('⏭️ Skipping validation - missing itemId, qty, or qty <= 0');
				return true;
			}

			const requestedQty = parseFloat(qty);

			console.log('📊 Stock check:', {
				availableStock,
				requestedQty,
				itemName: itemName,
				isInsufficient: requestedQty > availableStock
			});

			if (requestedQty > availableStock) {
				console.log('🚫 Insufficient stock - adding is-invalid class and showing error');
				qtyField.addClass('is-invalid');
				Swal.fire({
					title: 'Insufficient Stock!',
					text: `Insufficient stock for ${itemName}. Available: ${availableStock}`,
					icon: 'error',
					confirmButtonText: 'OK',
					timer: 3000, // Auto close after 3 seconds
					timerProgressBar: true
				});
				return false;
			}

			console.log('✅ Stock validation passed');
			return true;
		}

		// Real-time validation for quantity changes
		$(document).on('input change blur', '.qty', function() {
			const $row = $(this).closest('tr');
			validateStockForRow($row);
		});

		// File validation function
		function validateFile(input) {
			const file = input.files[0];
			if (!file) {
				console.log('No file selected');
				return true;
			}

			console.log('File details:', {
				name: file.name,
				size: file.size,
				type: file.type
			});

			// Check file size (5MB = 5 * 1024 * 1024 bytes)
			const maxSize = 5 * 1024 * 1024;
			const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
			
			if (file.size > maxSize) {
				console.log('File too large:', fileSizeMB + 'MB');
				Swal.fire({
					icon: 'error',
					title: 'File Too Large!',
					text: `File size is ${fileSizeMB}MB. Maximum allowed size is 5MB. Please select a smaller file.`,
					confirmButtonText: 'OK',
					confirmButtonColor: '#d33'
				});
				input.value = '';
				return false;
			}

			// Check file type
			const allowedExtensions = ['png', 'jpeg', 'jpg', 'xls', 'xlsx', 'docx', 'pdf'];
			const fileExtension = file.name.split('.').pop().toLowerCase();
			
			console.log('File extension:', fileExtension);

			if (!allowedExtensions.includes(fileExtension)) {
				console.log('Invalid file type:', fileExtension);
				Swal.fire({
					icon: 'error',
					title: 'Invalid File Type!',
					text: 'Only PNG, JPEG, JPG, XLS, XLSX, DOCX, and PDF files are allowed.',
					confirmButtonText: 'OK',
					confirmButtonColor: '#d33'
				});
				input.value = '';
				return false;
			}

			console.log('File validation passed');
			return true;
		}

		// Attach file validation to file input
		$('#upload_file').on('change', function() {
			console.log('File selected, validating...'); // Debug log
			validateFile(this);
		});

		// Supporting documents validation
		$('input[name="supporting_documents[]"]').on('change', function() {
			console.log('Supporting documents selected, validating...');
			validateFile(this);
		});

		// Modal file inputs validation
		$('.modal input[type="file"]').on('change', function() {
			console.log('Modal file selected, validating...');
			validateFile(this);
		});

		// Function to validate checklist items
		function validateChecklistItems() {
			const checklistRows = document.querySelectorAll('.mrntableselectexcel1 tr');
			let errorMessages = [];
			let hasIncompleteMandatory = false;

			console.log('🔍 Validating checklist items. Found rows:', checklistRows.length);

			// Skip if no checklist rows (empty table)
			if (checklistRows.length === 0) {
				console.log('⚠️ No checklist rows found');
				return true;
			}

			// Check if checklist exists but is not filled
			let hasChecklistData = false;
			let hasEmptyMandatoryFields = false;

			checklistRows.forEach((row, index) => {
				// Look for input/select/textarea fields in the checklist row
				const inputs = row.querySelectorAll('input:not([type="hidden"]), select, textarea');

				inputs.forEach((input) => {
					// Find the corresponding mandatory hidden field
					const fieldName = input.name;
					if (fieldName && fieldName.includes('[value]')) {
						hasChecklistData = true; // Mark that checklist data exists
						
						// Extract the group and item indices from the field name
						const matches = fieldName.match(/checklist_data\[(\d+)\]\[checklist\]\[(\d+)\]\[value\]/);
						if (matches) {
							const groupIndex = matches[1];
							const itemIndex = matches[2];
							const mandatoryField = row.querySelector(`input[name="checklist_data[${groupIndex}][checklist][${itemIndex}][mandatory]"]`);

							console.log(`🔍 Checking field ${fieldName}, mandatory field:`, mandatoryField, 'value:', mandatoryField ? mandatoryField.value : 'not found');

							// Check if this field is mandatory and empty
							if (mandatoryField && mandatoryField.value === '1') {
								if (input.value.trim() === '') {
									const nameField = row.querySelector(`input[name="checklist_data[${groupIndex}][checklist][${itemIndex}][name]"]`);
									const fieldLabel = nameField ? nameField.value : `Checklist Item ${index + 1}`;
									console.log(`❌ Found incomplete mandatory field: ${fieldLabel}`);
									errorMessages.push(`Mandatory checklist item "${fieldLabel}" is required.`);
									hasIncompleteMandatory = true;
									hasEmptyMandatoryFields = true;
								}
							}
							
							// Check if any field (mandatory or not) is empty when checklist exists
							if (input.value.trim() === '') {
								const nameField = row.querySelector(`input[name="checklist_data[${groupIndex}][checklist][${itemIndex}][name]"]`);
								const fieldLabel = nameField ? nameField.value : `Checklist Item ${index + 1}`;
								console.log(`⚠️ Found empty checklist field: ${fieldLabel}`);
								if (!mandatoryField || mandatoryField.value !== '1') {
									// Only add to error if not already added as mandatory
									errorMessages.push(`Checklist item "${fieldLabel}" should be filled.`);
								}
							}
						}
					}
				});
			});

			// Special validation: If checklist exists but no fields are filled
			if (hasChecklistData && errorMessages.length === 0) {
				// Check if all fields are empty
				let allFieldsEmpty = true;
				checklistRows.forEach((row) => {
					const inputs = row.querySelectorAll('input:not([type="hidden"]), select, textarea');
					inputs.forEach((input) => {
						if (input.name && input.name.includes('[value]') && input.value.trim() !== '') {
							allFieldsEmpty = false;
						}
					});
				});
				
				if (allFieldsEmpty) {
					console.log('❌ Checklist exists but no fields are filled');
					Swal.fire({
						icon: 'warning',
						title: 'Checklist Not Completed!',
						text: 'Checklist items are available but none are filled. Please complete the checklist before submitting.',
						confirmButtonText: 'OK',
						confirmButtonColor: '#f39c12'
					});
					return false;
				}
			}

			// Show validation errors if any mandatory fields are incomplete
			if (errorMessages.length > 0) {
				console.log('❌ Validation failed with errors:', errorMessages);
				
				// Separate mandatory and optional field errors
				const mandatoryErrors = errorMessages.filter(msg => msg.includes('required'));
				const optionalErrors = errorMessages.filter(msg => msg.includes('should be filled'));
				
				let title = 'Checklist Validation Error!';
				let icon = 'error';
				
				if (mandatoryErrors.length > 0 && optionalErrors.length === 0) {
					title = 'Mandatory Checklist Items Required!';
					icon = 'error';
				} else if (mandatoryErrors.length === 0 && optionalErrors.length > 0) {
					title = 'Checklist Items Incomplete!';
					icon = 'warning';
				}
				
				Swal.fire({
					icon: icon,
					title: title,
					html: errorMessages.join('<br>'),
					confirmButtonText: 'OK',
					confirmButtonColor: icon === 'error' ? '#d33' : '#f39c12'
				});
				return false;
			}

			console.log('✅ Checklist validation passed');
			return true;
		}
		function validateItemRows() {
			const itemRows = document.querySelectorAll('.mrntableselectexcel tr.trselected');
			let errorMessages = [];
			let hasValidItems = false;

			console.log(`🔍 Validating ${itemRows.length} item rows for quantity > 0 and stock limits`);

			// Check if at least one item row exists
			if (itemRows.length === 0) {
				errorMessages.push('At least one item is required.');
				Swal.fire({
					icon: 'error',
					title: 'Items Required!',
					text: 'Please add at least one item to the maintenance work order.',
					confirmButtonText: 'OK',
					confirmButtonColor: '#d33'
				});
				return false;
			}

			// Validate each item row
			itemRows.forEach((row, index) => {
				const itemInput = row.querySelector('input[name="item[]"]');
				const quantityInput = row.querySelector('input[name="qty[]"]');
				const availableStockInput = row.querySelector('input[name="available_stock[]"]');
				const uomSelect = row.querySelector('select[name="uom[]"]');
				const attributeInput = row.querySelector('input.attribute');
				const attributeEnrichedInput = row.querySelector('input.attribute-enriched');
				const itemDataAttr = itemInput ? itemInput.getAttribute('data-attr') : null;

				// Get item name for better error messages
				const itemNameInput = row.querySelector('input[name="item_name[]"]');
				const itemNameText = itemNameInput ? itemNameInput.value : '';
				const displayName = itemNameText || `Item ${index + 1}`;

				// Check if item is selected
				if (!itemInput || !itemInput.value.trim()) {
					errorMessages.push(`${displayName}: Please select an item.`);
				} else {
					// Check if item has attributes available but none selected
					let hasAttributesAvailable = false;
					let hasAttributesSelected = false;

					// Check if item has attributes data
					if (itemDataAttr && itemDataAttr !== '[]' && itemDataAttr !== 'null') {
						try {
							const attrData = JSON.parse(itemDataAttr);
							if (attrData && attrData.length > 0) {
								hasAttributesAvailable = true;
							}
						} catch (e) {
							console.log('Error parsing item attributes data:', e);
						}
					}

					// Check if attributes have been selected
					console.log(`🔍 Item ${index + 1} attribute check:`);
					console.log(`   - attributeInput exists:`, !!attributeInput);
					console.log(`   - attributeEnrichedInput exists:`, !!attributeEnrichedInput);

					// Debug what values are in the hidden inputs
					if (attributeInput) {
						console.log(`   - attributeInput raw value:`, attributeInput.value);
						if (attributeInput.value && attributeInput.value.trim() !== '[]') {
							try {
								const parsed = JSON.parse(attributeInput.value);
								console.log(`   - attributeInput parsed:`, parsed);
								console.log(`   - attributeInput has items:`, Array.isArray(parsed) && parsed.length > 0);
								if (Array.isArray(parsed) && parsed.length > 0) {
									console.log(`   - attributeInput first item:`, parsed[0]);
									console.log(`   - attributeInput has value_id:`, parsed.some(attr => attr.value_id && attr.value_id !== '' && attr.value_id !== null));
								}
							} catch (e) {
								console.log(`   - attributeInput parse error:`, e);
							}
						}
					}

					if (attributeEnrichedInput) {
						console.log(`   - attributeEnrichedInput raw value:`, attributeEnrichedInput.value);
						if (attributeEnrichedInput.value && attributeEnrichedInput.value.trim() !== '[]') {
							try {
								const parsed = JSON.parse(attributeEnrichedInput.value);
								console.log(`   - attributeEnrichedInput parsed:`, parsed);
								console.log(`   - attributeEnrichedInput has items:`, Array.isArray(parsed) && parsed.length > 0);
								if (Array.isArray(parsed) && parsed.length > 0) {
									console.log(`   - attributeEnrichedInput first item:`, parsed[0]);
									console.log(`   - attributeEnrichedInput has selected_value_id:`, parsed.some(attr => attr.selected_value_id && attr.selected_value_id !== '' && attr.selected_value_id !== null));
								}
							} catch (e) {
								console.log(`   - attributeEnrichedInput parse error:`, e);
							}
						}
					}

					if (attributeInput && attributeInput.value && attributeInput.value.trim() !== '[]' && attributeInput.value.trim() !== '') {
						try {
							const selectedAttrs = JSON.parse(attributeInput.value);
							if (selectedAttrs && Array.isArray(selectedAttrs) && selectedAttrs.length > 0) {
								// Check if any attribute has a value_id set
								const hasValidSelections = selectedAttrs.some(attr =>
									attr.value_id && attr.value_id !== '' && attr.value_id !== null
								);
								if (hasValidSelections) {
									hasAttributesSelected = true;
									console.log(`   ✅ Attributes selected via attributeInput`);
								}
							}
						} catch (e) {
							console.log('Error parsing selected attributes:', e);
						}
					} else if (attributeEnrichedInput && attributeEnrichedInput.value && attributeEnrichedInput.value.trim() !== '[]' && attributeEnrichedInput.value.trim() !== '') {
						try {
							const selectedAttrs = JSON.parse(attributeEnrichedInput.value);
							if (selectedAttrs && Array.isArray(selectedAttrs) && selectedAttrs.length > 0) {
								// Check if any attribute has a selected_value_id set
								const hasValidSelections = selectedAttrs.some(attr =>
									attr.selected_value_id && attr.selected_value_id !== '' && attr.selected_value_id !== null
								);
								if (hasValidSelections) {
									hasAttributesSelected = true;
									console.log(`   ✅ Attributes selected via attributeEnrichedInput`);
								}
							}
						} catch (e) {
							console.log('Error parsing enriched selected attributes:', e);
						}
					}

					console.log(`   - hasAttributesAvailable: ${hasAttributesAvailable}, hasAttributesSelected: ${hasAttributesSelected}`);

					// If item has attributes available but none selected, show error
					if (hasAttributesAvailable && !hasAttributesSelected) {
						console.log(`❌ ${displayName}: Has attributes available but none selected`);
						errorMessages.push(`${displayName}: Please select attributes for the selected item.`);
					}
				}

				// Check if quantity is entered and greater than 0
				if (!quantityInput || !quantityInput.value || quantityInput.value.trim() === '') {
					errorMessages.push(`${displayName}: Quantity is required.`);
				} else {
					const qtyValue = parseFloat(quantityInput.value);
					console.log(`🔍 ${displayName}: quantity = ${qtyValue}, available stock = ${availableStockInput ? availableStockInput.value : 'N/A'}`);

					if (isNaN(qtyValue)) {
						errorMessages.push(`${displayName}: Quantity must be a valid number.`);
					} else if (qtyValue <= 0) {
						console.log(`❌ ${displayName}: Quantity ${qtyValue} is <= 0 - blocking submission`);
						errorMessages.push(`${displayName}: Quantity must be greater than 0.`);
					} else if (availableStockInput && availableStockInput.value) {
						const availableStock = parseFloat(availableStockInput.value);
						if (!isNaN(availableStock) && qtyValue > availableStock) {
							errorMessages.push(`${displayName}: Quantity (${qtyValue}) cannot exceed available stock (${availableStock}).`);
						}
					}
				}

				// Check if UOM is selected
				if (!uomSelect || !uomSelect.value) {
					errorMessages.push(`${displayName}: Please select a unit of measurement.`);
				}

				// If all validations pass for this row, mark as having valid items
				if (itemInput?.value.trim() &&
					quantityInput?.value &&
					!isNaN(parseFloat(quantityInput.value)) &&
					parseFloat(quantityInput.value) > 0 &&
					(!availableStockInput?.value || parseFloat(quantityInput.value) <= parseFloat(availableStockInput.value)) &&
					uomSelect?.value) {
					hasValidItems = true;
				}
			});

			// Show validation errors if any
			if (errorMessages.length > 0) {
				console.log('❌ Item validation errors:', errorMessages);
				Swal.fire({
					icon: 'error',
					title: 'Item Validation Error!',
					html: errorMessages.join('<br>'),
					confirmButtonText: 'OK',
					confirmButtonColor: '#d33'
				});
				return false;
			}

			console.log('✅ Item validation passed - all quantities > 0 and within stock limits');
			return hasValidItems;
		}

		// Form submission validation is now integrated into the main submit handler above

		// Multiple file upload functionality
		function checkFileTypeandSize(event, previewSelector = '#preview') {
			$(previewSelector).empty();
			const files = event.target.files;

			if (files.length > 0) {
				// Validate each file
				for (let i = 0; i < files.length; i++) {
					const file = files[i];
					const maxSizeMB = 5;
					const fileSizeMB = file.size / (1024 * 1024);

					const videoExtensions = /(\.mp4|\.avi|\.mov|\.wmv|\.mkv)$/i;
					if (videoExtensions.exec(file.name)) {
						Swal.fire({
							icon: 'error',
							title: 'Invalid File Type',
							text: 'Video files are not allowed.'
						});
						event.target.value = "";
						return;
					}

					if (fileSizeMB > maxSizeMB) {
						Swal.fire({
							icon: 'error',
							title: 'File Too Large',
							text: `File "${file.name}" size should not exceed 5MB. Current size: ${fileSizeMB.toFixed(2)}MB`
						});
						event.target.value = "";
						return;
					}
				}

				// Show preview for all files
				handleFileUpload(event, previewSelector);
			}
		}

		function handleFileUpload(event, previewElement) {
			var files = event.target.files;
			var previewContainer = $(previewElement);
			previewContainer.empty();

			if (files.length > 0) {
				for (var i = 0; i < files.length; i++) {
					var fileName = files[i].name;
					var fileExtension = fileName.split('.').pop().toLowerCase();

					var fileIconType = 'file-text';

					switch (fileExtension) {
						case 'pdf':
							fileIconType = 'file';
							break;
						case 'doc':
						case 'docx':
							fileIconType = 'file';
							break;
						case 'xls':
						case 'xlsx':
							fileIconType = 'file';
							break;
						case 'png':
						case 'jpg':
						case 'jpeg':
						case 'gif':
							fileIconType = 'image';
							break;
						case 'zip':
						case 'rar':
							fileIconType = 'archive';
							break;
						default:
							fileIconType = 'file';
							break;
					}

					var fileIcon = `
						<div class="file-upload-preview" data-file-index="${i}" style="display: inline-block; margin: 5px; cursor: pointer; position: relative;">
							<div class="image-uplodasection expenseadd-sign">
								<i data-feather="file-text" class="fileuploadicon" style="font-size: 24px; color: #666;"></i>
								<div class="delete-img text-danger" data-file-index="${i}" style="position: absolute; top: -5px; right: -5px; cursor: pointer; background: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
									<i data-feather="x" style="font-size: 12px; color: #dc3545;"></i>
								</div>
							</div>
						</div>
					`;

					previewContainer.append(fileIcon);
				}
				feather.replace();
			}

			previewContainer.find('.delete-img').click(function() {
				var fileIndex = $(this).attr('data-file-index');
				removeFilePreview(fileIndex, previewContainer, event.target);
			});
		}

		function removeFilePreview(fileIndex, previewContainer, inputElement) {
			var dt = new DataTransfer();
			var files = inputElement.files;

			for (var i = 0; i < files.length; i++) {
				if (i != fileIndex) {
					dt.items.add(files[i]);
				}
			}

			inputElement.files = dt.files;
			previewContainer.children(`[data-file-index="${fileIndex}"]`).remove();

			var remainingPreviews = previewContainer.children();
			remainingPreviews.each(function(index) {
				$(this).attr('data-file-index', index);
				$(this).find('.delete-img').attr('data-file-index', index);
			});

			if (dt.files.length === 0) {
				inputElement.value = "";
			}
		}

		// Add event listener to clear error state when user starts typing in document number
		$(document).ready(function() {
			$('#document_number').on('input', function() {
				resetDocumentInputState();
			});
		});

	</script>
@endsection
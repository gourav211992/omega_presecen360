@extends('layouts.app')

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
								<h2 class="content-header-title float-start mb-0">Maintenance BOM</h2>
								<div class="breadcrumb-wrapper">
									<ol class="breadcrumb">
										<li class="breadcrumb-item"><a href="index.html">Home</a>
										</li>
										<li class="breadcrumb-item active">Add New</li>
									</ol>
								</div>
							</div>
						</div>
					</div>
					
					@php   $isAmendmentMode = intval(request('amendment') ?? 0) === 1; @endphp
					<div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
						<div class="form-group breadcrumb-right">
							<a href="{{ route('maint-bom.index') }}"> <button class="btn btn-secondary btn-sm"><i
										data-feather="arrow-left-circle"></i> Back</button>
							</a>
							@if ($bom->document_status == 'draft' || ($buttons['amend'] && request('amendment') == 1))
							   
								<button class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" type="button" id="save-draft-btn">
									<i data-feather="save"></i> Save as Draft
								</button>
							
								<button type="button" class="btn btn-primary btn-sm" id="submit-btn">
									<i data-feather="check-circle"></i> Submit
								</button>
							@endif

							@if ($bom->document_status=='rejected')
								<button class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" type="button" id="save-draft-btn">
									<i data-feather="save"></i> Save as Draft
								</button>
							
								<button type="button" class="btn btn-primary btn-sm" id="submit-btn">
									<i data-feather="check-circle"></i> Submit
								</button>
							@endif

						</div>
					</div>
				</div>
			</div>
			<div class="content-body">
				<form id="maint-bom-form" method="POST" action="{{ route('maint-bom.update', $bom->id) }}"
					enctype="multipart/form-data">

					@csrf
					@method('PUT')

					<section id="basic-datatable">
						<div class="row">
							<div class="col-12">

								{{-- BASIC INFORMATION --}}
								<div class="card">
									<div class="card-body customernewsection-form">
										<div class="border-bottom mb-2 pb-25">
											<div class="row">
											<div class="col-md-12">
												<div class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
													<div>
													<h4 class="card-title text-theme">Basic Information</h4>
													<p class="card-text">Edit the details</p>
													</div>
													<div class="header-right">
														@php
															use App\Helpers\Helper;
														@endphp
														<div class="col-md-6 text-sm-end">
															<span
																class="badge rounded-pill {{App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$bom->document_status] ?? ''}} forminnerstatus">
																<span class="text-dark">Status</span>
																: <span
																	class="{{App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS['CLOSED'] ?? ''}}">
																					@if ($bom->document_status == App\Helpers\ConstantHelper::APPROVAL_NOT_REQUIRED)
																		Approved
																	@else
																		{{ ucfirst($bom->document_status) }}
																	@endif
																</span>
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>
										@include('fixed-asset.partials.amendement-submit-modal', ['buttons' => $buttons, 'id' => $bom->id])

										<div class="row">
											{{-- Hidden Inputs --}}
											<input type="hidden" name="book_code" id="book_code_input"
												value="{{ old('book_code', $bom->book_code) }}">
											<input type="hidden" name="spare_parts" id="spare_parts"
												value="{{ old('spare_parts', $bom->spare_parts) }}">
											<input type="hidden" name="doc_number_type" id="doc_number_type"
												value="{{ old('doc_number_type', $bom->doc_number_type) }}">
											<input type="hidden" name="doc_reset_pattern" id="doc_reset_pattern"
												value="{{ old('doc_reset_pattern', $bom->doc_reset_pattern) }}">
											<input type="hidden" name="doc_prefix" id="doc_prefix"
												value="{{ old('doc_prefix', $bom->doc_prefix) }}">
											<input type="hidden" name="doc_suffix" id="doc_suffix"
												value="{{ old('doc_suffix', $bom->doc_suffix) }}">
											<input type="hidden" name="doc_no" id="doc_no"
												value="{{ old('doc_no', $bom->doc_no) }}">
											<input type="hidden" name="document_status" id="document_status"
												value="{{ old('document_status', $bom->document_status) }}">

											<div class="col-md-8">

												<div class="row align-items-center mb-1">
													<div class="col-md-3">
														<label class="form-label">Series <span
																class="text-danger">*</span></label>
													</div>
													<div class="col-md-5">
														<select class="form-select" id="book_id" name="book_id" disabled>
															@foreach ($series as $book)
																<option value="{{ $book->id }}" {{ old('book_id', $bom->book_id) == $book->id ? 'selected' : '' }}>
																	{{ $book->book_code }}
																</option>
															@endforeach
														</select>
													</div>
												</div>

												<div class="row align-items-center mb-1">
													<div class="col-md-3">
														<label class="form-label">Doc No <span
																class="text-danger">*</span></label>
													</div>
													<div class="col-md-5">
														<input type="text" class="form-control" id="document_number"
															disabled name="document_number"
															value="{{ old('document_number', $bom->document_number) }}">
													</div>
												</div>

												<div class="row align-items-center mb-1">
													<div class="col-md-3">
														<label class="form-label">Doc Date <span
																class="text-danger">*</span></label>
													</div>
													<div class="col-md-5">
														<input type="date" class="form-control" id="document_date" disabled
															name="document_date"
															value="{{ old('document_date', $bom->document_date) }}">
													</div>
												</div>

												<div class="row align-items-center mb-1">
													<div class="col-md-3">
														<label class="form-label">BOM Name <span
																class="text-danger">*</span></label>
													</div>
													<div class="col-md-5">
														<input type="text" name="bom_name" id="bom_name"
															class="form-control"
															value="{{ old('bom_name', $bom->bom_name) }}" />
													</div>
												</div>

											</div>
										</div>

									</div>
								</div>

								{{-- SPARE PARTS --}}
								<div class="card">
									<div class="card-body customernewsection-form">
										<div class="border-bottom mb-2 pb-25">
											<div class="row">
												<div class="col-md-6">
													<div class="newheader">
														<h4 class="card-title text-theme">Spare Parts Detail</h4>
														<p class="card-text">Edit spare parts</p>
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
													</tr>
												</thead>
												<tbody class="mrntableselectexcel">
													@foreach(json_decode($bom->spare_parts) as $index => $part)

														@php
															$itemId = $part->item_id;
															if (isset($itemId)) {
																$itemAttributes = App\Models\ItemAttribute::where('item_id', $itemId)->get();
															} else {
																$itemAttributes = [];
															}
															$processedData = [];
															foreach ($itemAttributes as $key => $attribute) {
																$attributesArray = array();
																$attribute_group_id = $attribute->attribute_group_id;
																$attribute->group_name = $attribute->group?->name;

																$attributeValueData = App\Models\ErpAttribute::whereIn('id', $attribute->attribute_id)->select('id', 'value')->where('status', 'active')->get();

																$attribute->values_data = $attributeValueData;
																$attribute = $attribute->only(['id', 'group_name', 'values_data', 'attribute_group_id']);

																array_push($processedData, ['id' => $attribute['id'], 'group_name' => $attribute['group_name'], 'values_data' => $attributeValueData, 'attribute_group_id' => $attribute['attribute_group_id']]);
															}
															$processedData = collect($processedData);
														@endphp
														<tr @if($index == 0) class="trselected" @endif>
															<td>
																<div class="form-check form-check-primary custom-checkbox">
																	<input type="checkbox" class="form-check-input row-check">
																</div>
															</td>
															<td>
																<input type="hidden" name="item_id[]" class="item_id"
																	value="{{ $part->item_id }}">
																<input type="text" name="item[]" value="{{ $part->item_code }}"
																	data-id="{{$part->item_id}}"
																	data-code="{{$part->item_code}}"
																	data-name="{{$part->item_name}}"
																	data-attr="{{ $processedData }}"
																	class="item_code form-control mw-100 ledgerselecct mb-25"
																	required />
															</td>
															<td>
																<input type="text" value="{{ $part->item_name }}"
																	class="item_name form-control mw-100 ledgerselecct mb-25"
																	required />
															</td>
															<td>
																<input type="hidden" class="attribute"
																	value="{{ $part->attribute}}">
																<div class="d-flex flex-wrap gap-1" id="attribute-badges">
																	@if($part->attribute)
																		@php
																			$selectedAttributes = json_decode($part->attribute, true);
																		@endphp
																		@if($selectedAttributes && count($selectedAttributes) > 0)
																			@php
																				$displayedCount = 0;
																				$totalSelectedCount = count($selectedAttributes);
																			@endphp
																			@foreach($selectedAttributes as $selectedAttr)
																				@php
																					// Find the attribute group name and value
																					$attrGroup = $processedData->firstWhere('id', $selectedAttr['item_attribute_id']);
																					$attrValue = null;
																					if($attrGroup) {
																						$attrValue = collect($attrGroup['values_data'])->firstWhere('id', $selectedAttr['value_id']);
																					}
																				@endphp
																				@if($attrGroup && $attrValue && $displayedCount < 2)
																					<span class="badge rounded-pill badge-light-primary" style="font-size:10px; margin-right:5px; cursor:pointer;">
																						<strong>{{ $attrGroup['group_name'] }}</strong>: {{ $attrValue['value'] }}
																					</span>
																					@php $displayedCount++; @endphp
																				@endif
																			@endforeach
																			@if($totalSelectedCount > 2)
																				<span style="font-size:10px; margin-right:5px; cursor:pointer; color:black;"><strong>...</strong></span>
																			@endif
																		@else
																			<span class="text-muted" style="font-size:10px;">No attributes selected</span>
																		@endif
																	@else
																		<span class="text-muted" style="font-size:10px;">No attributes available</span>
																	@endif
																</div>
															</td>
															<td>
																<select class="uom form-select mw-100" name="uom[]" required>
																	<option value="{{ $part->uom_id }}">{{ $part->uom_name }}
																	</option>
																</select>
															</td>
															<td>
																<input type="number" class="qty form-control mw-100"
																	name="qty[]" value="{{ $part->qty }}" required />
															</td>
														</tr>
													@endforeach
												</tbody>
												<tfoot>


													<tr valign="top" class="pomrnheadtffotsticky part-details-section">
														<td colspan="7" rowspan="10">
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

										<div class="row mt-2">
										<div class="col-md-4">
												<div class="mb-1">
													<label class="form-label"><i data-feather="paperclip"></i> Upload Document</label>
													<div class="d-flex align-items-center">
														<input type="file" multiple name="document[]" id="document" class="form-control" 
															   onchange="checkFileTypeandSize(event)"
															   accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf" style="flex: 1;" />
																@if($bom->document)
																	@php
																		$attachments = is_string($bom->document) ? 
																			(json_decode($bom->document, true) ?: [$bom->document]) : 
																			[$bom->document];
																	@endphp
																	@foreach($attachments as $index => $attachment)
																		@if($attachment)
																			<div class="file-upload-preview ms-2" style="cursor: pointer; position: relative;" data-file-path="{{ $attachment }}">
																				<div class="image-uplodasection expenseadd-sign">
																					<i onclick="window.open('{{ asset('storage/' . $attachment) }}', '_blank')" data-feather="file-text"></i>
																					<div class="delete-existing-file" style="position: absolute; top: -5px; right: -5px; cursor: pointer; background: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;" 
																						onclick="deleteExistingFile('{{ $attachment }}', this)" title="Delete file">
																						<i data-feather="x" style="font-size: 12px; color: #dc3545;"></i>
																					</div>
																				</div>
																			</div>
																		@endif
																	@endforeach
																@endif
													</div>
													<span class="text-primary small">{{__("message.attachment_caption")}}</span>
												</div>
											</div>
											
											<div class="col-md-4">
												<div class="mb-1">
													<label class="form-label"></label>
													<div id="preview"></div>
												</div>
											</div>

											<div class="col-md-12">
												<div class="mb-1">
													<label class="form-label">Final Remarks</label>
													<textarea type="text" name="remarks" rows="4" class="form-control"
														placeholder="Enter Remarks here...">{{ $bom->remarks }}</textarea>

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
	<!-- END: Content-->

			</div>
		</div>
	</div>
	<!-- END: Content-->

	<div class="sidenav-overlay"></div>
	<div class="drag-target"></div>

	<div class="sidenav-overlay"></div>
	<div class="drag-target"></div>


    <div class="modal fade" id="amendmentSubmitModal" tabindex="-1" aria-labelledby="amendmentSubmitModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="amendmentSubmitModalLabel">Amendment</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		</div>
		<div class="modal-body">
			<div class="mb-3">
			<label for="amend_remarks" class="form-label">Amendment Remarks <span class="text-danger">*</span></label>
			<textarea class="form-control" id="amend_remarks" name="amend_remarks" rows="4" placeholder="Please provide detailed remarks for this amendment..." required></textarea>
			</div>
			<div class="mb-3">
			<label for="amend_attachment" class="form-label">Supporting Document (Optional)</label>
			<input type="file" class="form-control" id="amend_attachment" name="amend_attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
			<span class="text-primary small">{{__("message.attachment_caption")}}</span>
			</div>
		</div>

		<div class="modal-footer">
			<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
			<button type="button" class="btn btn-primary" id="confirmAmendmentSubmit">
			<i data-feather="check-circle"></i> Submit
			</button>
		</div>
		</div>
	</div>
	</div>


	<div class="modal fade" id="attribute" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Select Attribute</h1>
					<p class="text-center">Enter the details below.</p>

					<div class="table-responsive-md customernewsection-form">
						<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail"
							id="attributes_table_modal" item-index="">
							<thead>
								<tr>
									<th>Attribute Name</th>
									<th>Attribute Value</th>
								</tr>
							</thead>
							<tbody id="attribute_table">

							</tbody>


						</table>
					</div>
				</div>

				<div class="modal-footer justify-content-center">
					<button type="button" class="btn btn-outline-secondary me-1"
						onclick="closeModal('attribute');">Cancel</button>
					<button type="button" class="btn btn-primary submitAttributeBtn">Select</button>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade text-start alertbackdropdisabled" id="amendmentconfirm" tabindex="-1"
		aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body alertmsg text-center warning">
					<i data-feather='alert-circle'></i>
					<h2>Are you sure?</h2>
					<p>Are you sure you want to <strong>Amendment</strong> this <strong>BOM</strong>? After Amendment this
						action cannot be undone.</p>
					<button type="button" class="btn btn-secondary me-25" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" data-bs-dismiss="modal">Confirm</button>
				</div>
			</div>
		</div>
	</div>
@endsection




@section('scripts')
<script type="text/javascript" src="{{asset('assets/js/modules/common-attr-ui.js')}}"></script>
<script src="{{asset('assets/js/fileshandler.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ==========================
// 🔧 Global Config (outside DOMContentLoaded)
// ==========================
const itemsData = @json($items ?? []);
let rowCount = 1;
const MAX_FILE_SIZE_MB = 10;

// Debug: Check if itemsData is loaded
console.log('Items Data:', itemsData);
console.log('Items Count:', itemsData.length);

document.addEventListener('DOMContentLoaded', function() {

	// ==========================
	// 🔧 DOM Config
	// ==========================
	const isAmendmentMode = {{ request('amendment') == 1 ? 'true' : 'false' }};
	const form = document.getElementById('maint-bom-form');

	// ==========================
	// 🔸 Feather refresh
	// ==========================
	$(window).on('load', function() {
		if (feather) feather.replace({ width: 14, height: 14 });
	});

	// ==========================
	// 🔸 Table Row Selection
	// ==========================
	$(".mrntableselectexcel tr").click(function () {
		$(this).addClass('trselected').siblings().removeClass('trselected');
		updateFooterFromSelected();
	});

	$(document).on('keydown', function (e) {
		if (e.which == 38) {
			$('.trselected').prev('tr').addClass('trselected').siblings().removeClass('trselected');
		} else if (e.which == 40) {
			$('.trselected').next('tr').addClass('trselected').siblings().removeClass('trselected');
		}
		$('html, body').scrollTop($('.trselected').offset().top - 200);
		updateFooterFromSelected();
	});

	$(document).on('click', 'tbody tr', function () {
		$(this).addClass('trselected').siblings().removeClass('trselected');
		$('html, body').scrollTop($(this).offset().top - 200);
		updateFooterFromSelected();
	});

	// Initialize footer
	updateFooterFromSelected();

	// Initialize autocomplete for existing rows
	initAutoForItem($('.item_code'));

	// ==========================
	// 🔸 Event Listeners for Footer Update
	// ==========================
	$(document).on('input change', '.qty, .uom, .item_name, .item_code, .attribute', function () {
		updateFooterFromSelected();
	});

	// ==========================
	// 🔸 Attribute Modal Functionality
	// ==========================
	$('.mrntableselectexcel').on('click', 'td:nth-child(4)', function() {
		let $tr = $(this).closest('tr');
		let $selectElement = $tr.find('.item_code');
		let $attributesTable = $('#attribute_table');
		$attributesTable.data('currentRow', $tr);
		
		if ($selectElement.val() !== "") {
			let attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
			let $hiddenInput = $tr.find('.attribute');
			let existingAttributes = $hiddenInput.length && $hiddenInput.val()
				? JSON.parse($hiddenInput.val())
				: [];

			if (!attributesJSON.length) {
				$attributesTable.html('<tr><td colspan="2" class="text-center">No attributes available</td></tr>');
				return;
			}

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
						<td>${element.group_name}<input type="hidden" name="id" value="${element.id}"></td>
						<td><select class="form-select select2"><option value="">Select</option>${optionsHtml}</select></td>
					</tr>
				`;
			});
			$attributesTable.html(innerHtml);
			$attributesTable.find('select').select2();
			$('#attribute').modal('show');
		} else {
			$attributesTable.html('<tr><td colspan="2" class="text-center">Please select an item first</td></tr>');
		}
	});

	// ==========================
	// 🔸 Session Messages
	// ==========================
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
		showToast('error', "@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach");
	@endif

	// ==========================
	// 🔸 Update Footer from Selected Row
	// ==========================
	function updateFooterFromSelected() {
		let $selected = $('.trselected');
		if ($selected.length) {
			$('#part_name').text($selected.find('.item_name').val());
			$('#uom').text($selected.find('.uom option:selected').text());
			$('#qty').text($selected.find('.qty').val());
			
			let $selectElement = $selected.find('.item_code');
			let $badgesContainer = $('#attributes_badges');

			if ($selectElement.val() !== "") {
				let attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
				let $hiddenInput = $selected.find('.attribute');
				let existingAttributes = $hiddenInput.length && $hiddenInput.val()
					? JSON.parse($hiddenInput.val())
					: [];

				if (!attributesJSON.length) {
					$badgesContainer.html('<span>No attributes available</span>');
					return;
				}

				let badgesHtml = '';
				$.each(attributesJSON, function (index, element) {
					let selectedValObj = existingAttributes.find(attr => attr.item_attribute_id === element.id);
					let selectedVal = selectedValObj ? selectedValObj.value_id : '';
					let selectedText = '';
					if (selectedVal) {
						let valObj = element.values_data.find(v => v.id === selectedVal);
						selectedText = valObj ? valObj.value : '';
					}
					badgesHtml += `<span class="badge rounded-pill badge-light-primary" style="margin-right:5px;"><strong>${element.group_name}</strong>: <span>${selectedText}</span></span>`;
				});
				$badgesContainer.html(badgesHtml);
			} else {
				$badgesContainer.html('');
			}
		}
	}

	// ==========================
	// 🔸 Toast helper
	// ==========================
	function showToast(icon, title) {
		const Toast = Swal.mixin({
			toast: true,
			position: "top-end",
			showConfirmButton: false,
			timer: 3000,
			timerProgressBar: true,
			didOpen: toast => {
				toast.onmouseenter = Swal.stopTimer;
				toast.onmouseleave = Swal.resumeTimer;
			}
		});
		Toast.fire({ icon, title });
	}

	// ==========================
	// 🔸 Quantity Validation
	// ==========================
	function validateQuantities() {
		let isValid = true;
		let errors = [];
		$('.qty').each(function() {
			let qty = parseFloat($(this).val() || 0);
			let item = $(this).closest('tr').find('.item_name').val() || 'Unknown Item';
			if (qty <= 0) {
				isValid = false;
				errors.push(`<span style="color:red;">${item}</span> quantity cannot be 0 or empty`);
			}
		});
		if (!isValid) {
			Swal.fire({ icon: 'error', title: 'Validation Error', html: errors.join('<br>') });
		}
		return isValid;
	}

	// ==========================
	// 🔸 File Validation (10MB)
	// ==========================
	function validateFileUpload(input) {
		const file = input?.files?.[0];
		if (!file) return true;
		const allowed = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
		if (!allowed.includes(file.type)) {
			Swal.fire({ icon: 'error', title: 'Invalid File Type', text: 'Only PDF, DOC, DOCX, JPG, PNG allowed.' });
			input.value = '';
			return false;
		}
		if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
			Swal.fire({ icon: 'error', title: 'File Too Large', text: `Max allowed size is ${MAX_FILE_SIZE_MB} MB.` });
			input.value = '';
			return false;
		}
		return true;
	}

	// ==========================
	// 🔸 Update JSON Data
	// ==========================
	function updateJsonData() {
		let rows = [];
		$('.mrntableselectexcel tr').each(function() {
			const id = $(this).find('.item_id').val();
			if (id) {
				rows.push({
					item_id: id,
					item_code: $(this).find('.item_code').val(),
					item_name: $(this).find('.item_name').val(),
					attribute: $(this).find('.attribute').val(),
					qty: $(this).find('.qty').val(),
					uom_id: $(this).find('.uom').val(),
					uom_name: $(this).find('.uom option:selected').text()
				});
			}
		});
		$('#spare_parts').val(JSON.stringify(rows));
	}

	// ==========================
// 🟢 Add New Row Logic
// ==========================
$('#addNewRowBtn').on('click', function () {
	let rowsValidationPassed = validateRowsCompletion();
	if (!rowsValidationPassed) return;

	rowCount++;
	let newRow = `
	<tr>
		<td class="customernewsection-form">
			<div class="form-check form-check-primary custom-checkbox">
				<input type="checkbox" class="form-check-input row-check">
				<label class="form-check-label"></label>
			</div>
		</td>
		<td>
			<input type="hidden" class="item_id">
			<input type="text" placeholder="Select" name="item[]" class="item_code form-control mw-100 ledgerselecct mb-25" />
		</td>
		<td>
			<input type="text" placeholder="Select" class="item_name form-control mw-100 ledgerselecct mb-25" />
		</td>
		<td>
			<input type="hidden" class="attribute">
			<div class="d-flex flex-wrap gap-1" id="attribute-badges">
				<span class="text-muted" style="font-size:10px;">No attributes available</span>
			</div>
		</td>
		<td>
			<select class="uom form-select mw-100" name="uom[]" required></select>
		</td>
		<td>
			<input type="number" class="qty form-control mw-100" name="qty[]" required />
		</td>
	</tr>
	`;

	$('.mrntableselectexcel').append(newRow);
	// Initialize autocomplete for the newly added row only
	let $newRow = $('.mrntableselectexcel tr:last');
	let $newItemCode = $newRow.find('.item_code');
	
	console.log('Initializing autocomplete for new row:', $newItemCode);
	
	// Wait a bit then initialize autocomplete for the new row
	setTimeout(() => {
		initAutoForItem($newItemCode);
	}, 100);
	
	// Make the new row selected
	$newRow.addClass('trselected').siblings().removeClass('trselected');
	// updateFooterFromSelected(); // Not needed in edit page
});

// ==========================
// 🔴 Delete Rows
// ==========================
$('#delete').on('click', function () {
	let $rows = $('.mrntableselectexcel tr');
	let $checked = $rows.find('.row-check:checked');

	// if ($rows.length <= 1) {
	// 	showToast('error', 'At least one row is required.');
	// 	return;
	// }

	// if ($rows.length - $checked.length < 1) {
	// 	showToast('error', 'You must keep at least one row.');
	// 	return;
	// }

	$checked.closest('tr').remove();
});

// ==========================
// 🔸 Check All Functionality
// ==========================
$('#checkAll').on('change', function () {
	let isChecked = $(this).is(':checked');
	$('.mrntableselectexcel .row-check').prop('checked', isChecked);
});

// ==========================
// 🔸 Row Validation Before Add
// ==========================
function validateRowsCompletion() {
	let isValid = true;
	$('.mrntableselectexcel tr').find('input, select').removeClass('is-invalid');

	$('.mrntableselectexcel tr').each(function () {
		let $row = $(this);
		let itemId = $row.find('.item_id').val();
		let itemName = $row.find('.item_name').val();
		let uomValue = $row.find('.uom').val();
		let qtyValue = $row.find('.qty').val();

		if (!itemId || !itemName || !uomValue || !qtyValue || parseFloat(qtyValue) <= 0) {
			isValid = false;
			$row.find('input, select').addClass('is-invalid');
		}
	});

	if (!isValid) {
		Swal.fire({
			icon: 'warning',
			title: 'Complete Current Row First',
			text: 'Please complete all required fields before adding a new row.',
		});
	}

	return isValid;
}


	// ==========================
	// 🔸 Save as Draft
	// ==========================
	const saveDraftBtn = document.getElementById('save-draft-btn');
	if (saveDraftBtn) {
		saveDraftBtn.addEventListener('click', function(e) {
			e.preventDefault();
			$('#document_status').val('draft');
			updateJsonData();
			$('.preloader').show();
			form.submit();
			setTimeout(() => {
				showToast('success', 'Draft saved successfully!');
			}, 700);
		});
	}

	// ==========================
	// 🔸 Validate UOM fields
	// ==========================
	function validateUOM() {
		console.log('UOM validation started'); // Debug log
		let isValid = true;
		let errors = [];
		
		$('.mrntableselectexcel tr').each(function(index) {
			let $row = $(this);
			let itemId = $row.find('.item_id').val();
			let uomValue = $row.find('.uom').val();
			let itemName = $row.find('.item_name').val() || 'Unknown Item';
			let rowNumber = index + 1;
			
			console.log(`Row ${rowNumber}: itemId=${itemId}, uomValue=${uomValue}, itemName=${itemName}`); // Debug log
			
			// If row has an item but no UOM selected
			if (itemId && itemId.trim() !== '') {
				if (!uomValue || uomValue.trim() === '' || uomValue === 'null') {
					isValid = false;
					errors.push(`<span style="color:red;">${itemName}</span> (Row ${rowNumber})`);
					console.log(`UOM missing for row ${rowNumber}: ${itemName}`); // Debug log
				}
			}
		});
		
		if (!isValid) {
			console.log('UOM validation failed, showing SweetAlert'); // Debug log
			Swal.fire({ 
				icon: 'warning',
				title: 'UOM Required', 
				html: `Please select UOM for the following items:<br><br>${errors.join('<br>')}`,
				confirmButtonText: 'OK',
				confirmButtonColor: '#7367f0'
			});
		} else {
			console.log('UOM validation passed'); // Debug log
		}
		
		return isValid;
	}

	// ==========================
	// 🔸 Submit Button
	// ==========================
	const submitBtn = document.getElementById('submit-btn');
	if (submitBtn) {
		submitBtn.addEventListener('click', function(e) {
			e.preventDefault();
			if (!validateQuantities()) return;
			
			// Validate UOM before submission
			if (!validateUOM()) return;
			
			// Validate attributes (same as create page)
			let attributeValidationPassed = validateAttributes();
			if (!attributeValidationPassed) {
				return; // Stop submission if validation fails
			}
			
			updateJsonData();

			if (isAmendmentMode) {
				$('#amendmentSubmitModal').modal('show');
			} else {
				$('.preloader').show();
				form.submit();
			}
		});
	}

	// ==========================
	// 🔸 Amendment Modal Submit
	// ==========================
	$(document).on('click', '#confirmAmendmentSubmit', function(e) {
		e.preventDefault();

		let remarks = $('#amend_remarks').val().trim();
		let fileInput = document.getElementById('amend_attachment');

		if (!remarks) {
			$('#amend_remarks').addClass('is-invalid');
			if (!$('#amend_remarks').next('.invalid-feedback').length) {
				$('#amend_remarks').after('<div class="invalid-feedback">Remarks are required.</div>');
			}
			return;
		}
		$('#amend_remarks').removeClass('is-invalid').next('.invalid-feedback').remove();

		if (fileInput && fileInput.files.length && !validateFileUpload(fileInput)) return;

		if (!$('#action_type').length) {
			$('#maint-bom-form').append('<input type="hidden" name="action_type" id="action_type" value="amendment">');
		} else {
			$('#action_type').val('amendment');
		}

		const formData = new FormData(form);
		formData.append('amend_remarks', remarks);
		if (fileInput && fileInput.files.length) {
			formData.append('amend_attachment', fileInput.files[0]);
		}

		$('#amendmentSubmitModal').modal('hide');
		$('.preloader').show();

		$.ajax({
			url: $(form).attr('action'),
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function() {
				$('.preloader').hide();
				Swal.fire({
					icon: 'success',
					title: 'Amendment Submitted!',
					text: 'Your amendment has been submitted successfully.',
					confirmButtonText: 'OK'
				}).then(() => {
					window.location.href = '{{ route("maint-bom.show", $bom->id) }}';
				});
			},
			error: function(xhr) {
				$('.preloader').hide();
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
				});
			}
		});
	});
});

// ==========================
// 🟣 Autocomplete Item Search
// ==========================
function initAutoForItem(selector) {
	// Safety check for itemsData
	if (!itemsData || !Array.isArray(itemsData)) {
		console.error('itemsData is not available or not an array:', itemsData);
		return;
	}
	
	// Safety check for selector
	if (!$(selector).length) {
		console.error('Selector not found:', selector);
		return;
	}
	
	// Safely destroy existing autocomplete if any
	let $elements = $(selector);
	$elements.each(function() {
		try {
			if ($(this).hasClass('ui-autocomplete-input')) {
				$(this).autocomplete('destroy');
			}
		} catch (e) {
			console.log('Autocomplete destroy error (safe to ignore):', e);
		}
	});

	$(selector).autocomplete({
		minLength: 0,
		source: function(request, response) {
			let term = request.term.toLowerCase();
			let selectedItemIds = [];
			$('.item_id').each(function() {
				let val = $(this).val();
				if (val) selectedItemIds.push(val);
			});

			if (term.trim() === "") {
				// When clicking on field (empty search), show the initially loaded items
				let filtered = itemsData.filter(item => {
					let isSelectedElsewhere = selectedItemIds.includes(item.id.toString());
					let currentItemId = $(selector).closest('tr').find('.item_id').val();
					return (!isSelectedElsewhere || item.id.toString() === currentItemId);
				});

				let results = filtered.map(item => ({
					id: item.id,
					label: `${item.item_code} - ${item.item_name}`,
					code: item.item_code,
					item_id: item.id,
					item_name: item.item_name,
					uom_name: item.uom_name,
					uom_id: item.uom_id,
					attr: item.item_attributes,
				}));

				response(results);
			} else {
				// When typing search terms, use server-side search (same as create page)
				$.ajax({
					url: "{{ route('maint-bom.search-items') }}",
					method: 'GET',
					data: {
						q: term,
						limit: 50
					},
					success: function(data) {
						// Filter out already selected items and format for autocomplete
						let filtered = data.filter(item => {
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
							attr: item.item_attributes,
						}));

						response(results);
					},
					error: function(xhr, status, error) {
						console.log('Search error:', error);
						// Fallback to local search on error
						let filtered = itemsData.filter(item => {
							let isSelectedElsewhere = selectedItemIds.includes(item.id.toString());
							let currentItemId = $(selector).closest('tr').find('.item_id').val();
							return (item.item_code.toLowerCase().includes(term) || item.item_name.toLowerCase().includes(term)) &&
								(!isSelectedElsewhere || item.id.toString() === currentItemId);
						});

						let results = filtered.map(item => ({
							id: item.id,
							label: `${item.item_code} - ${item.item_name}`,
							code: item.item_code,
							item_id: item.id,
							item_name: item.item_name,
							uom_name: item.uom_name,
							uom_id: item.uom_id,
							attr: item.item_attributes,
						}));

						response(results);
					}
				});
			}
		},
		select: function(event, ui) {
			let $input = $(this);
			let tr = $input.closest('tr'); // Fix: Define tr variable
			let attr = ui.item.attr;
			let itemCode = ui.item.code;
			let itemName = ui.item.item_name;
			let itemId = ui.item.id;
			let uomId = ui.item.uom_id;
			let uomName = ui.item.uom_name;

			// Set data attributes (same as create page)
			$input.attr('data-name', itemName);
			$input.attr('data-code', itemCode);
			$input.attr('data-attr', JSON.stringify(attr));
			$input.attr('data-id', itemId);

			// Set all the item details
			$input.val(itemCode);
			tr.find('.item_code').val(itemCode);
			tr.find('.item_name').val(itemName);
			tr.find('.item_id').val(itemId);
			
			// Set UOM with proper empty and append (same as create page)
			tr.find('.uom').empty().append(`<option value="${uomId}">${uomName}</option>`);

			// Attributes display
			let badgesHtml = '';
			if (attr && attr.length > 0) {
				attr.forEach(attribute => {
					badgesHtml += `<span class="badge rounded-pill badge-light-primary me-25" style="font-size:10px;">
						<strong>${attribute.group_name}</strong>: Not Selected
					</span>`;
				});
				tr.find('#attribute-badges').html(badgesHtml);
				
				// Automatically open attribute modal if item has attributes (same as create page)
				setTimeout(() => {
					let $attributesTable = $('#attribute_table');
					$attributesTable.data('currentRow', tr);
					
					// Populate modal with attributes
					let attributesJSON = attr;
					let $hiddenInput = tr.find('.attribute');
					let existingAttributes = $hiddenInput.length && $hiddenInput.val()
						? JSON.parse($hiddenInput.val())
						: [];

					if (attributesJSON.length > 0) {
						let innerHtml = ``;
						$.each(attributesJSON, function (index, element) {
							let optionsHtml = ``;
							
							// Check if element has values_data or use different structure
							let valuesData = element.values_data || element.values || [];
							
							// Debug log to see data structure
							console.log('Attribute element:', element);
							console.log('Values data:', valuesData);
							
							$.each(valuesData, function (i, value) {
								let isSelected = existingAttributes.some(attr =>
									attr.item_attribute_id === element.id && attr.value_id === value.id
								);
								// Use 'value' property as in create page
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
							changeAttributeVal(tr);
						});
						$attributesTable.find('select').select2();
						
						// Open the modal
						$('#attribute').modal('show');
					}
				}, 300);
			} else {
				tr.find('#attribute-badges').html('<span class="text-muted" style="font-size:10px;">No attributes available</span>');
				
				// Focus on quantity field if no attributes
				setTimeout(() => {
					tr.find('.qty').focus();
				}, 200);
			}
		},
		change: function(event, ui) {
			if (!ui.item) {
				let tr = $(this).closest('tr');
				tr.find('.item_code, .item_name, .item_id').val('');
				tr.find('.uom').empty();
				tr.find('#attribute-badges').html('');
			}
		}
	}).focus(function() {
		if (!this.value.trim()) $(this).autocomplete("search", "");
	});

	// Set custom render item with safety check and delay
	setTimeout(() => {
		let autocompleteInstance = $(selector).autocomplete("instance");
		if (autocompleteInstance) {
			autocompleteInstance._renderItem = function(ul, item) {
				return $("<li>").append(`<div><strong>${item.code}</strong> - ${item.item_name}</div>`).appendTo(ul);
			};
		}
	}, 100);
}

// ==========================
// 🔸 Attribute Modal Handlers (Same as Create Page)
// ==========================
$(document).on('click', '.submitAttributeBtn', (e) => {
	let $currentRow = $('#attribute_table').data('currentRow');
	if ($currentRow) {
		changeAttributeVal($currentRow);
		updateAttributeBadges($currentRow);
		// updateFooterFromSelected(); // Remove this call as it's not needed in edit page
	}
	$("#attribute").modal('hide');
});

function changeAttributeVal($row) {
	let hiddenInput = $row.find('.attribute');
	if (!hiddenInput) return;

	// Find the attributes table - it's the tbody with id="attribute_table"
	const tbody = document.getElementById("attribute_table");

	let selectedAttributes = [];

	Array.from(tbody.rows).forEach(row => {
		const hiddenInputAttr = row.querySelector('input[type="hidden"][name="id"]');
		const selectElement = row.querySelector("select");
		
		if (hiddenInputAttr && selectElement) {
			const attributeId = parseInt(hiddenInputAttr.value, 10);
			const selectedVal = parseInt(selectElement.value, 10);

			if (!isNaN(attributeId) && !isNaN(selectedVal) && selectedVal > 0) {
				selectedAttributes.push({
					item_attribute_id: attributeId,
					value_id: selectedVal
				});
			}
		}
	});

	// Update hidden input with JSON
	hiddenInput.val(JSON.stringify(selectedAttributes));
}

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

		if (attr && attr.length > 0) {
			attr.forEach(function(attribute) {
				// Check if this attribute has been selected (use item_attribute_id as in create page)
				let selectedAttr = existingAttributes.find(selected =>
					selected.item_attribute_id === attribute.id
				);

				// Only show selected attributes
				if (selectedAttr) {
					selectedCount++;
					if (selectedCount <= 2) {
						// Find the selected value from the attribute's values
						let valuesData = attribute.values_data || attribute.values || [];
						let selectedValue = valuesData.find(val => val.id === selectedAttr.value_id);

						if (selectedValue) {
							badgesHtml += `<span class="badge rounded-pill badge-light-primary" style="font-size:10px; margin-right:5px;cursor:pointer">
								<strong>${attribute.group_name}</strong>: ${selectedValue.value}
							</span>`;
						} else {
							// Handle case where selected value isn't found (optional)
							badgesHtml += `<span class="badge rounded-pill badge-light-warning" style="font-size:10px; margin-right:5px;cursor:pointer">
								<strong>${attribute.group_name}</strong>: N/A
							</span>`;
						}
					}
				}
			});

			// Show "+X more" if there are more than 2 selected attributes
			if (selectedCount > 2) {
				badgesHtml += `<span class="badge rounded-pill badge-light-info" style="font-size:10px; margin-right:5px;">
					+${selectedCount - 2} more
				</span>`;
			}
		}

		$badgesContainer.html(badgesHtml);
	}
}

// ==========================
// 🔸 Attribute Validation (Same as Create Page)
// ==========================
function validateAttributes() {
	let isValid = true;
	let errorMessages = [];

	// Loop through all rows to check for items with attributes
	$('.mrntableselectexcel tr').each(function(index) {
		let $row = $(this);
		let $selectElement = $row.find('.item_code');
		let itemName = $row.find('.item_name').val();
		
		// Skip empty rows
		if (!$selectElement.val() || !itemName) {
			return true; // continue to next iteration
		}

		// Check if item has attributes
		let attributesJSON = JSON.parse($selectElement.attr('data-attr') || '[]');
		
		if (attributesJSON && attributesJSON.length > 0) {
			// Item has attributes, check if at least one attribute is selected
			let $hiddenInput = $row.find('.attribute');
			let selectedAttributes = [];
			
			if ($hiddenInput.length && $hiddenInput.val()) {
				try {
					selectedAttributes = JSON.parse($hiddenInput.val());
				} catch (e) {
					selectedAttributes = [];
				}
			}

			// Check if at least one attribute is selected (minimum requirement)
			if (!selectedAttributes || selectedAttributes.length === 0) {
				errorMessages.push(`Please select at least one attribute for item: <span style="color: red;">${itemName}</span>`);
				isValid = false;
			}
		}
	});

	if (!isValid) {
		// Show SweetAlert error
		Swal.fire({
			icon: 'error',
			title: 'Attribute Selection Required',
			html: errorMessages.join('<br>'),
			confirmButtonText: 'OK'
		});
	}

	return isValid;
}

// ==========================
// 🔸 Multiple File Upload Functionality
// ==========================
function checkFileTypeandSize(event) {
    const files = event.target.files;
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/pdf'];
    const allowedExtensions = ['png', 'jpeg', 'jpg', 'xls', 'xlsx', 'docx', 'pdf'];
    
    let validFiles = [];
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        // Check file type
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: `File "${file.name}" is not allowed. Please select PNG, JPEG, JPG, XLS, XLSX, DOCX, or PDF files only.`,
            });
            event.target.value = '';
            return false;
        }
        
        // Check file size
        if (file.size > maxSize) {
            const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: `File "${file.name}" is ${fileSizeMB}MB. Maximum allowed size is 5MB.`,
            });
            event.target.value = '';
            return false;
        }
        
        validFiles.push(file);
    }
    
    // Show preview for valid files
    showFilePreview(validFiles);
    return true;
}

function showFilePreview(files) {
    const previewContainer = document.getElementById('preview');
    previewContainer.innerHTML = '';
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileDiv = document.createElement('div');
        fileDiv.className = 'file-upload-preview ms-2';
        fileDiv.style.cssText = 'cursor: pointer; position: relative; display: inline-block; margin-right: 10px; margin-bottom: 10px;';
        
        const iconDiv = document.createElement('div');
        iconDiv.className = 'image-uplodasection expenseadd-sign';
        
        const fileIcon = document.createElement('i');
        fileIcon.setAttribute('data-feather', 'file-text');
        fileIcon.title = file.name;
        
        const deleteBtn = document.createElement('div');
        deleteBtn.className = 'delete-new-file';
        deleteBtn.style.cssText = 'position: absolute; top: -5px; right: -5px; cursor: pointer; background: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;';
        deleteBtn.title = 'Remove file';
        deleteBtn.onclick = function() { removeFilePreview(fileDiv, i); };
        
        const deleteIcon = document.createElement('i');
        deleteIcon.setAttribute('data-feather', 'x');
        deleteIcon.style.cssText = 'font-size: 12px; color: #dc3545;';
        
        deleteBtn.appendChild(deleteIcon);
        iconDiv.appendChild(fileIcon);
        fileDiv.appendChild(iconDiv);
        fileDiv.appendChild(deleteBtn);
        previewContainer.appendChild(fileDiv);
    }
    
    // Refresh feather icons
    if (feather) feather.replace();
}

function removeFilePreview(fileDiv, index) {
    const fileInput = document.getElementById('document');
    const dt = new DataTransfer();
    
    // Add all files except the one being removed
    for (let i = 0; i < fileInput.files.length; i++) {
        if (i !== index) {
            dt.items.add(fileInput.files[i]);
        }
    }
    
    fileInput.files = dt.files;
    fileDiv.remove();
}

// Function to delete existing files
function deleteExistingFile(filePath, element) {
    // Add file to deletion list
    let deletedFiles = document.getElementById('deleted_files');
    if (!deletedFiles) {
        deletedFiles = document.createElement('input');
        deletedFiles.type = 'hidden';
        deletedFiles.name = 'deleted_files';
        deletedFiles.id = 'deleted_files';
        document.getElementById('maint-bom-form').appendChild(deletedFiles);
    }
    
    // Add to deletion array
    let currentDeleted = deletedFiles.value ? JSON.parse(deletedFiles.value) : [];
    currentDeleted.push(filePath);
    deletedFiles.value = JSON.stringify(currentDeleted);
    
    console.log('File marked for deletion:', filePath);
    console.log('Current deleted files:', deletedFiles.value);
    
    // Remove from UI immediately
    element.closest('.file-upload-preview').remove();
}

</script>
@endsection

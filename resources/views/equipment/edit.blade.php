
@extends('layouts.app')

@section('content')
    <style>
        .itemactive { position: absolute; left: 6px; font-size: 11px; top: 6px; color: #fff } 
        .iteminactive {  left: 24px; color: #999 } 
        .customernewsection-form .statusactiinactive .form-check-input { width: 80px; cursor: pointer}
        .customernewsection-form .statusactiinactive .form-check-input:checked + .itemactive { display: inline-block}
        .customernewsection-form .statusactiinactive .form-check-input:checked ~ .iteminactive { display: none }
        
        .customernewsection-form .statusactiinactive .form-check-input:not(:checked) + .itemactive { display: none}
        .customernewsection-form .statusactiinactive .form-check-input:not(:checked) ~ .iteminactive { display: inline-block }
    </style>
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <form id="equipmentForm" action="{{ route('equipment.update', $equipment->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="content-header pocreate-sticky">
                    <div class="row">
                        <div class="content-header-left col-md-6 mb-2">
                            <div class="row breadcrumbs-top">
                                <div class="col-12">
                                    <h2 class="content-header-title float-start mb-0">Equipment</h2>
                                    <div class="breadcrumb-wrapper">
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"><a href="{{ route('equipment.index') }}">Home</a>
                                            </li>
                                            <li class="breadcrumb-item active">Edit</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
                            <div class="form-group breadcrumb-right">
                                    <a href="{{ route('equipment.index') }}" class="btn btn-secondary btn-sm" id="back">
                                        <i data-feather="arrow-left-circle"></i> Back
                                    </a>
                                    @if ($equipment->document_status == 'draft' || ($buttons['amend'] && request('amendment') == 1))
                                    <button type="button" onclick="submitForm('draft');" id="draft"
                                        class="btn btn-outline-primary btn-sm mb-50 mb-sm-0"><i data-feather='save'></i> Save as
                                        Draft</button>
                                   
                                        <button type="button" onclick="submitForm('submitted');"
                                        class="btn btn-primary btn-sm mb-50 mb-sm-0" id="submitted"><i
                                            data-feather="check-circle"></i>
                                        Submit</button>
                                    @endif

                                    @if ($equipment->document_status=='rejected' && $equipment->created_by == App\Helpers\Helper::getAuthenticatedUser()->auth_user_id)
                                        <button type="button" onclick="submitForm('draft');" id="draft"
                                            class="btn btn-outline-primary btn-sm mb-50 mb-sm-0"><i data-feather='save'></i> Save as
                                            Draft</button>
                                    
                                            <button type="button" onclick="submitForm('submitted');"
                                            class="btn btn-primary btn-sm mb-50 mb-sm-0" id="submitted"><i
                                                data-feather="check-circle"></i>
                                            Submit</button>
                                    @endif
                                  
                                    <input id="submitButton" type="submit" value="Submit" class="hidden" />
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="status" id="status">
                <input type="hidden" name="document_status" id="document_status">
                <input type="hidden" name="action_type" id="action_type" value="{{ request('amendment') == 1 ? 'amendment' : 'submit' }}">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                <div class="content-body">
                    <section id="basic-datatable">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                        <div class="row">
                                            <div class="col-md-12">
                                                 <div
                                                    class="newheader d-flex justify-content-between border-bottom mb-2 pb-25">
                                                    <div>
                                                        <h4 class="card-title text-theme">Basic Information</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                    <div class="header-right d-flex align-items-center gap-3">
                                                        @php
                                                            use App\Helpers\Helper;
                                                        @endphp
                                                        <div class="text-sm-end">
                                                            <span class="badge rounded-pill {{App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS_LIST[$equipment->document_status] ?? ''}} forminnerstatus">
                                                                <span class="text-dark">Status</span>
                                                                 : <span class="{{App\Helpers\ConstantHelper::DOCUMENT_STATUS_CSS[$equipment->document_status] ?? ''}}">
                                                                    @if ($equipment->document_status == App\Helpers\ConstantHelper::APPROVAL_NOT_REQUIRED)
                                                                    Approved
                                                                @else
                                                                    {{ ucfirst($equipment->document_status) }}
                                                                @endif
                                                            </span>
                                                            </span>        
                                                        </div>
                                                        
                                                        <div class="form-check form-check-primary form-switch statusactiinactive">
                                                            <input type="checkbox" name="active_status" {{ old('active_status', $equipment->status) == 1 ? 'checked' : '' }} class="form-check-input" id="customSwitch3" />
                                                            <span class="itemactive">Active</span>
                                                            <span class="itemactive iteminactive">Inactive</span>
                                                        </div>
                                                    </div>
                                             </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                
                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Organization <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" id="organization_id" name="organization_id" disabled>
                                                            <option value="">Select</option>

                                                            @foreach ($userOrganizations as $organization)
                                                                <option value="{{ $organization->organization->id }}" @selected (old('organization_id', $equipment->organization_id) == $organization->organization->id) >
                                                                    {{ $organization->organization->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Location <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" id="location_id" name="location_id" disabled>
                                                            <option value="">Select</option>
                                                            @foreach ($locations as $loc)
                                                                @if ($loc->organization_id == $equipment->organization_id)
                                                                    <option value="{{ $loc->id }}" {{ $equipment->location_id == $loc->id ? 'selected' : '' }}>
                                                                        {{ $loc->store_name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Sub Asset Code</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select select2" id="asset_code_id" name="asset_code_id">
                                                            <option value="">Select</option>
                                                            @if(isset($fixedAssetRegistration))
                                                                @foreach($fixedAssetRegistration as $assetCode)
                                                                    <option value="{{ $assetCode->id }}" {{ (old('asset_code_id', $equipment->asset_code_id ?? '') == $assetCode->id) ? 'selected' : '' }}>
                                                                        {{ $assetCode->asset_code }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Category <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <select class="form-select" id="category_id" name="category_id">
                                                            <option value="">Select</option>
                                                            @foreach($categories as $category)
                                                                @if ($category->organization_id == $equipment->organization_id)
                                                                    <option value="{{ $category->id}}" {{ $equipment->category_id == $category->id ? 'selected' : '' }}>
                                                                        {{ $category->name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>

                                               

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Name <span
                                                                class="text-danger">*</span></label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" name="name" value="{{ old('name', $equipment->name) }}">
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Alias</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" name="alias" value="{{ old('alias', $equipment->alias) }}">
                                                    </div>
                                                </div>

                                                <div class="row align-items-center mb-1">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Description</label>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" name="description" value="{{ old('description', $equipment->description) }}">
                                                    </div>
                                                </div>

                                            </div>
                                            
                                            
                                 
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body customernewsection-form">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card quation-card">
                                                <div class="card-header newheader">
                                                    <div>
                                                        <h4 class="card-title">General Information</h4>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">

                                                        <div class="col-md-3">
                                                            <div class="mb-1">
                                                                <label class="form-label" for="model_name">Model Name </label>
                                                                <input type="text" class="form-control" id="model_name" name="model_name" placeholder="Enter Model Name" value="{{ old('model_name', $equipment->model_name) }}" />
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="mb-1">
                                                                <label class="form-label" for="manufacturer_name">Manufacturer Name</label>
                                                                <input type="text" class="form-control" id="manufacturer_name" name="manufacturer_name" placeholder="Enter Manufacturer Name" value="{{ old('manufacturer_name', $equipment->manufacturer_name) }}" />
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="mb-1">
                                                                <label class="form-label" for="yom">YOM</label>
                                                                <input type="number" class="form-control" id="yom" name="yom" placeholder="Enter YOM" value="{{ old('yom', $equipment->yom) }}" />
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="mb-1">
                                                                <label class="form-label" for="commission_date">Machine Commission Date</label>
                                                                <input type="date" class="form-control" id="commission_date" name="commission_date" value="{{ old('commission_date', $equipment->commission_date) }}" />
                                                            </div>
                                                        </div>

                                                       

                                                        <div class="col-md-3">
                                                            <div class="mb-1">
                                                                <label class="form-label" for="purchase_cost">Machine Purchase Cost (₹)</label>
                                                                <input type="number" class="form-control" id="purchase_cost" name="purchase_cost" placeholder="Enter Machine Purchase Cost" value="{{ old('purchase_cost', $equipment->purchase_cost) }}" min="1" step="0.01" />
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body customernewsection-form">

                                        <div class="border-bottom mb-2 pb-25">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="newheader ">
                                                        <h4 class="card-title text-theme">Maintenance Detail
                                                        </h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 text-sm-end">
                                                    <a href="javascript:void(0);" id="deleteRowBtn"
                                                        class="btn btn-sm btn-outline-danger me-50">
                                                        <i data-feather="x-circle"></i> Delete</a>
                                                    <a href="javascript:void(0);" id="addRowBtn"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i data-feather="plus"></i> Add New Item</a>

                                                </div>
                                            </div>
                                        </div>

                                        

                                        <div class="tab-content pb-1">
                                            <div class="tab-pane active" id="Maintenance">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="table-responsive pomrnheadtffotsticky">
                                                            <table
                                                                class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                                               <thead>
                                                                    <tr>
                                                                        <th width="62" class="customernewsection-form">
                                                                            <div
                                                                                class="form-check form-check-primary custom-checkbox">
                                                                                <input type="checkbox"
                                                                                    class="form-check-input" id="Email">
                                                                                <label class="form-check-label"
                                                                                    for="Email"></label>
                                                                            </div>
                                                                        </th>
                                                                        <th width="285">Maint Type</th>
                                                                        <th width="208">Frequency</th>
                                                                        <th width="208">Start Date</th>
                                                                        <th width="269">Time</th>
                                                                        <th width="208">Maintenance BOM</th>
                                                                        <th width="329">Checklist</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="mrntableselectexcel" id="maintenanceRows">

                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Spare Part Tab -->
                                            <div class="tab-pane" id="Spare">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="table-responsive pomrnheadtffotsticky">
                                                            <table
                                                                class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="62" class="customernewsection-form">
                                                                            <div
                                                                                class="form-check form-check-primary custom-checkbox">
                                                                                <input type="checkbox"
                                                                                    class="form-check-input" id="Email">
                                                                                <label class="form-check-label"
                                                                                    for="Email"></label>
                                                                            </div>
                                                                        </th>
                                                                        <th width="285">Item Code</th>
                                                                        <th width="208">Item Name</th>
                                                                        <th width="269">Attributes</th>
                                                                        <th width="329">UOM</th>
                                                                        <th>Qty</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="mrntableselectexcel" id="spareRows">
                                                                </tbody>

                                                                <tfoot>
                                                                    <tr class="totalsubheadpodetail">
                                                                        <td colspan="6"></td>
                                                                    </tr>
                                                                    <tr valign="top">
                                                                        <td colspan="6">
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
                                                                                        <div
                                                                                            class=" mw-100">
                                                                                            <strong>Name</strong>:
                                                                                            <span id="part-detail-name"></span>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="poprod-decpt">
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-primary"><strong>HSN</strong>:
                                                                                            <span id="part-detail-hsn"></span>
                                                                                        </div>
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-primary">
                                                                                            <strong>Color</strong>:
                                                                                            <span id="part-detail-color"></span>
                                                                                        </div>
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-primary"><strong>Size</strong>:
                                                                                            <span id="part-detail-size"></span>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="poprod-decpt">
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-primary"><strong>Inv.
                                                                                                UOM</strong>:
                                                                                            <span id="part-detail-uom"></span>
                                                                                        </div>
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-primary"><strong>Qty.</strong>:
                                                                                            <span id="part-detail-qty"></span>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td class="poprod-decpt">
                                                                                        <div
                                                                                            class="badge rounded-pill badge-light-secondary">
                                                                                            <strong>Remarks</strong>:
                                                                                            <span id="part-detail-remarks"></span>
                                                                                        </div>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </tfoot>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- End Spare Part Tab -->
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 row">
                                                <div class="col-md-4">
                                                    <div class="mb-1">
                                                        <label class="form-label"><i data-feather="paperclip"></i> Upload Document</label>
                                                        <div class="d-flex align-items-center">
                                                            <input type="file" multiple name="upload_document[]" id="upload_document" class="form-control" 
                                                                   onchange="checkFileTypeandSize(event)"
                                                                   accept=".png,.jpeg,.jpg,.xls,.xlsx,.docx,.pdf" style="flex: 1;" />
                                                            
                                                            {{-- Hidden input to track deleted files --}}
                                                            <input type="hidden" name="deleted_files" id="deleted_files" value="" />
                                                            
                                                            {{-- Current documents display inline --}}
                                                            @if($equipment->upload_document)
                                                                @php
                                                                    $documents = is_string($equipment->upload_document) ? json_decode($equipment->upload_document, true) : $equipment->upload_document;
                                                                    if (!is_array($documents)) $documents = [$equipment->upload_document];
                                                                @endphp
                                                                @foreach($documents as $index => $document)
                                                                    @if($document)
                                                                        <div class="file-upload-preview ms-2" style="cursor: pointer; position: relative;" data-file-path="{{ $document }}">
                                                                            <div class="image-uplodasection expenseadd-sign">
                                                                                <i onclick="window.open('{{ asset('storage/' . $document) }}', '_blank')" data-feather="file-text"></i>
                                                                                <div class="delete-existing-file" style="position: absolute; top: -5px; right: -5px; cursor: pointer; background: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;" 
                                                                                     onclick="deleteExistingFile('{{ $document }}', this)" title="Delete file">
                                                                                    <i data-feather="x" style="font-size: 12px; color: #dc3545;"></i>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                        <span class="text-primary small">Accept only .PNG, .JPEG, .JPG, XLS, .XLSX, .DOCX, and .PDF files and not more than 5MB in size</span>
                                                        <div id="preview" class="mt-2"></div>
                                                    </div>
                                                </div>
                                             
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-1">
                                                    <label class="form-label">Final Remarks</label>
                                                    <textarea name="final_remarks" type="text" rows="4" class="form-control" placeholder="Enter Remarks here...">{{ old('final_remarks', $equipment->final_remarks) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Modal to add new record -->
                    </section>
            </div>
        </div>
    </div>

    {{-- Amendment Modal --}}
    <div class="modal fade" id="amendmentconfirm" tabindex="-1" aria-labelledby="amendmentconfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel17">Amend Equipment</h4>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pb-2">
                    <div class="row mt-1">
                        <div class="col-md-12">
                            <div class="mb-1">
                                <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                <textarea name="amend_remarks" class="form-control cannot_disable"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-1">
                                        <label class="form-label">Upload Document</label>
                                        <input name="amend_attachment" onchange="addFiles(this, 'amend_files_preview')" type="file" class="form-control cannot_disable" accept=".png,.jpeg,.jpg,.xls,.xlsx,.doc,.docx,.pdf"/>
                                    </div>
                                </div>
                                <div class="col-md-4" style="margin-top:19px;">
                                    <div class="row" id="amend_files_preview">
                                    </div>
                                </div>
                            </div>
                            <span class="text-primary small">Accept only .PNG, .JPEG, .JPG, XLS, .DOCX, and .PDF files and not more than 5MB in size</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('amendmentconfirm')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAmend('equipmentForm');">Submit</button>
                </div>
            </div>
        </div>
    </div>
        <!-- END: Content-->
                </div>
            </form>
        </div>
    </div>
    <!-- END: Content-->
    <!-- Modal for Attributes -->
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
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail"> 
									<thead>
										 <tr>  
											<th>Attribute Name</th>
											<th>Attribute Value</th>
										  </tr>
										</thead>
										<tbody>
                                        <tr>
                                            <td>Color</td>
                                            <td>
                                                <select class="form-select select2 attribute-select" data-attribute="color">
                                                    <option>Select</option>
                                                    <option>Black</option>
                                                    <option>White</option>
                                                    <option>Red</option>
                                                    <option>Golden</option>
                                                    <option>Silver</option>
                                                </select>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td>Size</td>
                                            <td>
                                                <select class="form-select select2 attribute-select" data-attribute="size">
                                                    <option>Select</option>
                                                    <option>5.11"</option>
                                                    <option>5.10"</option>
                                                    <option>5.09"</option>
                                                    <option>5.00"</option>
                                                    <option>6.20"</option>
                                                </select>
                                            </td>
                                        </tr>
											
											
											 
											 

									   </tbody>


								</table>
							</div>
				</div>
				
				<div class="modal-footer justify-content-center">  
                    <button type="button" class="btn btn-outline-secondary me-1" data-bs-dismiss="modal" >Cancel</button>
					<button type="button" class="btn btn-primary" data-bs-dismiss="modal" >Select</button>
				</div>
			</div>
		</div>
	</div>
    
    {{-- <div class="modal fade" id="attribute" tabindex="-1" aria-labelledby="attributeModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title fw-bolder text-dark" id="attributeModalTitle">Item Attributes</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">Enter the attribute details for this item.</p>
                    <input type="hidden" id="attribute-row-id" value="">
                    
                    <div class="table-responsive customernewsection-form">
                        <table class="mt-1 table myrequesttablecbox table-striped">
                            <thead>
                                <tr>
                                    <th>Attribute Name</th>
                                    <th>Attribute Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Color</td>
                                    <td>
                                        <select class="form-select attribute-select" data-attribute="color">
                                            <option value="">Select</option>
                                            <option value="Black">Black</option>
                                            <option value="White">White</option>
                                            <option value="Red">Red</option>
                                            <option value="Blue">Blue</option>
                                            <option value="Green">Green</option>
                                            <option value="Golden">Golden</option>
                                            <option value="Silver">Silver</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Size</td>
                                    <td>
                                        <select class="form-select attribute-select" data-attribute="size">
                                            <option value="">Select</option>
                                            <option value="5.11 Inch">5.11 Inch</option>
                                            <option value="5.10 Inch">5.10 Inch</option>
                                            <option value="5.9 Inch">5.9 Inch</option>
                                            <option value="5.8 Inch">5.8 Inch</option>
                                            <option value="5.7 Inch">5.7 Inch</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Weight</td>
                                    <td>
                                        <select class="form-select attribute-select" data-attribute="weight">
                                            <option value="">Select</option>
                                            <option value="100 gm">100 gm</option>
                                            <option value="200 gm">200 gm</option>
                                            <option value="300 gm">300 gm</option>
                                            <option value="400 gm">400 gm</option>
                                            <option value="500 gm">500 gm</option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <td>Material</td>
                                    <td>
                                        <select class="form-select attribute-select" data-attribute="material">
                                            <option value="">Select</option>
                                            <option value="Metal">Metal</option>
                                            <option value="Plastic">Plastic</option>
                                            <option value="Wood">Wood</option>
                                            <option value="Glass">Glass</option>
                                            <option value="Ceramic">Ceramic</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex mt-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary me-1" data-bs-dismiss="modal"><i data-feather="x-circle"></i> Cancel</button>
                        <button type="button" class="btn btn-primary" id="save-attributes"><i data-feather="check-circle"></i> Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <!-- END: Modal for Attributes -->
    <!-- Modal for Checklist -->
    <div class="modal fade text-start" id="checklist" tabindex="-1" aria-labelledby="myModalLabel17"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel17">Select Checklist</h4>
                        <p class="mb-0">Select from the below list</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="checkListPortion">
                    <div class="row checklist-portion">

                        <div class="col-md-4">
                            <div class="mb-1">
                                <label class="form-label">Checklist <span class="text-danger">*</span></label>
                                <select class="form-select select2">
                                    <option>Select</option>
                                    @foreach($checklists as $checklist)
                                    <option value="{{ $checklist->id }}">{{ $checklist->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3  mb-1">
                            <label class="form-label">&nbsp;</label><br />
                            <button class="btn btn-warning btn-sm"><i data-feather="search"></i> Search</button>
                        </div>

                        <div class="col-md-12">
                            <div class="table-responsive">
                                Select Checklist
                                <div class="text-end" style="margin-top: -30px"><a href="#" class="text-primary add-contactpeontxt mt-50" onclick="addPortion()"><i data-feather='plus'></i> Add Checklist</a></div>
                                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail">
                                    <thead>
                                        <tr>
                                            <th width="40px" class="customernewsection-form">
                                                <div class="form-check form-check-primary custom-checkbox">
                                                    <input type="checkbox" class="form-check-input" id="Email">
                                                    <label class="form-check-label" for="Email"></label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-end">
                    
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><i
                            data-feather="x-circle"></i> Cancel</button>
                    <button class="btn btn-primary btn-sm" data-bs-dismiss="modal"><i data-feather="check-circle"></i>
                        Submit</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-start" id="viewChecklist" tabindex="-1" aria-labelledby="myModalLabel18"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel18">Select
                            Checklist</h4>
                        <p class="mb-0">Select from the below list</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewCheckListPortion">
                    <div class="row checklist-portion">

                        <div class="col-md-4">
                            <div class="mb-1">
                                <label class="form-label">Checklist <span class="text-danger">*</span></label>
                                <select class="form-select select2">
                                    <option>Select</option>
                                    @foreach($checklists as $checklist)
                                    <option value="{{ $checklist->id }}">{{ $checklist->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3  mb-1">
                            <label class="form-label">&nbsp;</label><br />
                            <button class="btn btn-warning btn-sm"><i data-feather="search"></i> Search</button>
                        </div>

                        <div class="col-md-12">
                            <div class="table-responsive">
                                Select Checklist
                                <div class="text-end" style="margin-top: -30px"><a href="#" class="text-primary add-contactpeontxt mt-50" onclick="addPortion()"><i data-feather='plus'></i> Add Checklist</a></div>
                                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail">
                                    <thead>
                                        <tr>
                                            <th width="40px" class="customernewsection-form">
                                                <div class="form-check form-check-primary custom-checkbox">
                                                    <input type="checkbox" class="form-check-input" id="Email">
                                                    <label class="form-check-label" for="Email"></label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer text-end">
                    
                </div>
            </div>
        </div>
    </div>
      <!-- END: Content-->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form class="ajax-input-form" method="POST" action="{{ route('equipment.approval') }}"
                    data-redirect="{{ route('equipment.index') }}" enctype='multipart/form-data'>
                    @csrf
                    <input type="hidden" name="action_type" id="approval_action_type">
                    <input type="hidden" name="id" value="{{ $equipment->id }}">
                    <div class="modal-header">
                        <div>
                            <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel178"></h4>
                            <p class="mb-0 fw-bold voucehrinvocetxt mt-0">{{ Carbon\Carbon::now()->format('d-m-Y') }}
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-2">
                        <div class="row mt-1">
                            <div class="col-md-12">
                                <div class="mb-1">
                                    <label class="form-label">Remarks <span class="text-danger">*</span></label>
                                    <textarea name="remarks" class="form-control"></textarea>
                                </div>
                                <div class="mb-1">
                                    <label class="form-label">Upload Document</label>
                                    <input type="file" multiple class="form-control" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="reset" class="btn btn-outline-secondary me-1">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submit-button">Submit</button>
                    </div>
                </form>

    <!-- Modal for Checklist -->
    <div class="modal fade text-start" id="checklist" tabindex="-1" aria-labelledby="myModalLabel17" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel17">Select
                            Checklist</h4>
                        <p class="mb-0">Select from the below list</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="checkListPortion">
                    <!-- Initial portion will be added here dynamically -->
                </div>
                <div class="modal-footer text-end">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><i data-feather="x-circle"></i>
                        Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="submitChecklistBtn"><i data-feather="check-circle"></i>
                        Submit</button>
                </div>
            </div>
        </div>
    </div>
    <!-- END: Modal for Checklist -->
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .is-invalid {
            border-color: #ea5455 !important;
            padding-right: calc(1.45em + 0.876rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ea5455'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ea5455' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.3625em + 0.219rem) center;
            background-size: calc(0.725em + 0.438rem) calc(0.725em + 0.438rem);
        }
        
        .hidden {
            display: none;
        }
        
        .file-size-info {
            color: #28a745;
            font-size: 0.875rem;
            margin-top: 0.25rem;
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
    <script>
        $(document).ready(function () {
            // Ensure add/delete buttons are always enabled in amendment mode
            @if(request('amendment') == 1)
                $('#addRowBtn').prop('disabled', false).removeClass('d-none');
                $('#deleteRowBtn').prop('disabled', false).removeClass('d-none');
                $('#addRowBtn').parent().removeClass('d-none');
            @endif
            
            // File size validation (5MB = 5 * 1024 * 1024 bytes)
            // Allowed file types: PNG, JPEG, JPG, XLS, DOCX, PDF
            const MAX_FILE_SIZE = 5 * 1024 * 1024;
            const ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/jpg', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/pdf'];
            
            $('input[name="upload_document"]').on('change', function() {
                const file = this.files[0];
                const fileInput = $(this);
                
                // Remove previous messages
                fileInput.siblings('.file-size-info').remove();
                fileInput.removeClass('is-invalid');
                
                if (file) {
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    
                    // Check file type
                    if (!ALLOWED_TYPES.includes(file.type)) {
                        fileInput.addClass('is-invalid');
                        this.value = ''; // Clear the file selection
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid File Type',
                            text: `Accept only .PNG, .JPEG, .JPG , XLS, .DOCX, and .PDF files.`,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#ea5455'
                        });
                        return;
                    }
                    
                    // Check file size
                    if (file.size > MAX_FILE_SIZE) {
                        // File too large - show error and clear selection
                        fileInput.addClass('is-invalid');
                        this.value = ''; // Clear the file selection
                        
                        // Show SweetAlert error
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: `File size must not exceed 5MB. Selected file "${file.name}" is ${fileSizeMB}MB.`,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#ea5455'
                        });
                    }
                }
            });

            var allLocations = @json($locations);
            // var allCategories = @json($categories);
            var maintenanceTypes = @json($maintenanceTypes);
            var maintenanceBOM = @json($maintenanceBOM);
            let items = @json($items);
            let checkListIds = @json($checkListIds);
            let mainChecklistNames = @json($mainChecklistNames ?? []);

            var existingMaintenanceDetails = @json($equipment->maintenanceDetails)??{};
            var existingSpareParts = @json($equipment->spareParts)??{};


            if (existingMaintenanceDetails.length > 0) {
                existingMaintenanceDetails.forEach((m) => {
                    $('#maintenanceRows').append(getMaintenanceRow(m));
                });
            } else {
                $('#maintenanceRows').append(getMaintenanceRow());
            }

            if (existingSpareParts.length > 0) {
                existingSpareParts.forEach((p) => {
                    $('#spareRows').append(getSparePartRow(p));
                });
            } else {
                $('#spareRows').append(getSparePartRow());
            }

           
            
         $(function() {
            $("#revisionNumber").change(function() {
                const fullUrl = "{{ route('equipment.edit', $equipment->id) }}?revisionNumber=" +
                    $(this)
                    .val();
                window.open(fullUrl, "_blank");
            });
        });

            // On organization change, filter locations
            $('#organization_id').on('change', function () {
                var orgId = $(this).val();
                var locationSelect = $('#location_id');
                locationSelect.html('<option value="">Select</option>');
                // $('#category_id').html('<option value="">Select</option>');

                if (orgId) {

                    allLocations.forEach(function (loc) {
                        if (loc.organization_id == orgId) {
                            locationSelect.append('<option value="' + loc.id + '">' + loc
                                            .store_name +
                                    '</option>');
                        }
                    });
                }
            });

            $('#location_id').on('change', function () {
                var locationId = $(this).val();
                var categorySelect = $('#category_id');
                categorySelect.html('<option value="">Select</option>');
                var categories = [
                    {
                        id: 1,
                        store_name: 'Category 1'
                    },
                    {
                        id: 2,
                        store_name: 'Category 2'
                    },
                    {
                        id: 3,
                        store_name: 'Category 3'
                    }
                ];

                if (locationId) {
                    categories.forEach(function (cat) {
                        // if (cat.id == locationId) {
                        categorySelect.append('<option value="' + cat.id + '">' + cat
                                        .store_name +
                                '</option>');
                        // }
                    });
                }
            });


            function getMaintenanceRow(data = {}) {

                const rowId = 'row-' + Math.random().toString(36).substring(2, 10);

                // Build options from maintenanceTypes
                let typeOptions = `<option value="">Select</option>`;
                maintenanceTypes.forEach(function (type) {
                    typeOptions += `<option value="${type.id}" ${data.maintenance_type_id == type.id ? 'selected' : ''}>${type.name}</option>`;
                });
            let bomOptions = `<option value="">Select</option>`;
            maintenanceBOM.forEach(function (type) {
                bomOptions += `<option value="${type.id}" ${data.maintenance_bom_id == type.id ? 'selected' : ''}>${type.name}</option>`;
            });
                
                // Extract checklist data from JSON column (checklist_data)
                let checklistData = [];
                let selectedNames = [];
                let selectedIds = [];
                
                // New approach: Get checklist data from JSON column
                if (data.checklist_data && Array.isArray(data.checklist_data)) {
                    data.checklist_data.forEach(checklistItem => {
                        checklistData.push({
                            checklist_id: checklistItem.checklist_id,
                            checklist_detail_id: checklistItem.checklist_detail_id,
                            name: checklistItem.main_checklist_name,
                            description: checklistItem.description,
                            type: checklistItem.data_type
                        });
                        selectedNames.push(checklistItem.main_checklist_name);
                        selectedIds.push(checklistItem.checklist_detail_id);
                    });
                }
                // Fallback: Legacy support for old checklist relationship (for backward compatibility)
                else if (data.checklists && Array.isArray(data.checklists)) {
                    data.checklists.forEach(checklist => {
                        let checklistDetail = null;
                        
                        // Parse checklist_detail JSON if it exists
                        if (checklist.checklist_detail) {
                            try {
                                checklistDetail = JSON.parse(checklist.checklist_detail);
                            } catch (e) {
                                console.error('Error parsing checklist_detail:', e);
                            }
                        }
                        
                        if (checklistDetail) {
                            checklistData.push({
                                checklist_id: checklistDetail.checklist_id,
                                checklist_detail_id: checklistDetail.checklist_detail_id,
                                name: checklistDetail.main_checklist_name || checklist.name,
                                description: checklistDetail.description,
                                type: checklistDetail.data_type
                            });
                            selectedNames.push(checklistDetail.main_checklist_name || checklist.name);
                            selectedIds.push(checklistDetail.checklist_detail_id);
                        }
                    });
                }
                
                let selectedIdsString = selectedIds.join(',');

                // Create badges from unique main checklist names
                let uniqueNames = [...new Set(selectedNames.filter(name => name))];
                let badgesHtml = '';
                
                if (uniqueNames.length > 0) {
                    badgesHtml = `<span class="badge rounded-pill badge-light-primary">${uniqueNames[0]}</span>`;
                    if (selectedNames.length > 1) {
                        badgesHtml += ` <span class="badge rounded-pill badge-light-primary">+${selectedNames.length - 1}</span>`;
                    }
                }
                let row = `<tr data-row-id="${rowId}">
                            <td class="customernewsection-form">
                                <div class="form-check form-check-primary custom-checkbox">
                                    <input type="checkbox" class="form-check-input row-checkbox">
                                    <label class="form-check-label"></label>
                                </div>
                                ${data.id ? `<input type="hidden" name="maintenance[${rowId}][id]" value="${data.id}">` : ''}
                            </td>
                            <td class="poprod-decpt">
                                <select name="maintenance[${rowId}][type]" class="form-select mw-100 maintenance-type">
                                    ${typeOptions}
                                </select>
                            </td>
                            <td class="poprod-decpt">
                                <select name="maintenance[${rowId}][frequency]" class="form-select mw-100 frequency">
                                    <option value="">Select</option>
                                    <option value="Daily" ${data.frequency == 'Daily' ? 'selected' : ''}>Daily</option>
                                    <option value="Weekly" ${data.frequency == 'Weekly' ? 'selected' : ''}>Weekly</option>
                                    <option value="Monthly" ${data.frequency == 'Monthly' ? 'selected' : ''}>Monthly</option>
                                    <option value="Quarterly" ${data.frequency == 'Quarterly' ? 'selected' : ''}>Quarterly</option>
                                    <option value="Semi-Annually" ${data.frequency == 'Semi-Annually' ? 'selected' : ''}>Semi-Annually</option>
                                    <option value="Annually" ${data.frequency == 'Annually' ? 'selected' : ''}>Annually</option>
                                </select>
                            </td>
                            <td class="poprod-decpt">
                                    <input type="date" name="maintenance[${rowId}][date]" value="${data.start_date || ''}" class="form-control mw-100 mb-25 date-field" />
                                </td>
                            <td class="poprod-decpt">
                                <input type="time" name="maintenance[${rowId}][time]" value="${data.time || ''}" placeholder="Enter Time" class="form-control mw-100 mb-25 time-field" />
                            </td>
                             <td class="poprod-decpt">
                                    <select name="maintenance[${rowId}][bom]" class="form-select mw-100 maintenance-bom">
                                        ${bomOptions}
                                    </select>
                                </td>
                            <td class="poprod-decpt checklist-cell">
                                <span class="checklist-badges">${badgesHtml}</span>
                                <button type="button" class="btn p-25 btn-sm btn-outline-secondary open-checklist-modal" style="font-size: 10px">Add Checklist</button>
                                <input type="hidden" class="selected-checklists" value="${selectedIdsString}" />
                                <!-- Store checklist data for form submission -->
                                <div class="existing-checklist-data" style="display: none;">
                                    ${checklistData.map((item, index) => `
                                        <input type="hidden" name="maintenance[${rowId}][checklists][${index}][checklist_id]" value="${item.checklist_id || ''}">
                                        <input type="hidden" name="maintenance[${rowId}][checklists][${index}][checklist_detail_id]" value="${item.checklist_detail_id}">
                                    `).join('')}
                                </div>
                                <!-- Legacy hidden inputs removed - only modal-driven inputs will be used -->
                            </td>
                        </tr>`;

                $(function () {
                    $(".ledgerselecct").autocomplete({
                        source: maintenanceTypes.map(item => item.name),
                        minLength: 0
                    }).focus(function () {
                        if (this.value == "") {
                            $(this).autocomplete("search");
                        }
                    });
                });

                return row;
            }

            // Global variables for checklist management
            let currentRowId = null;
            let checklistContexts = {};
            let selectedChecklistIds = [];
            let portionChecklistData = {}; // Store checklist data per portion
            let checklistRowRef = null; // Add this missing variable

            $('.open-checklist-modal').prop('disabled', false);
            $('.view-checklist-modal').prop('disabled', false);


            $(document).on('click', '.open-checklist-modal', function () {
                console.log('Checklist button clicked!'); // Debug log
                
                checklistRowRef = $(this).closest('tr');
                
                // Get row ID from input name or data attribute
                currentRowId = getRowId(checklistRowRef);
                console.log('Current Row ID:', currentRowId); // Debug log
                
                if (!currentRowId) {
                    console.error('Could not determine row ID');
                    return;
                }
                
                // Initialize context for this row if not exists
                if (!checklistContexts[currentRowId]) {
                    checklistContexts[currentRowId] = {
                        mainChecklistId: null,
                        mainChecklistName: null,
                        selectedItems: []
                    };
                }
                
                // Reset modal state
                resetChecklistModal();
                
                // Load existing selections if this row has saved checklist data
                const hasExistingData = checklistRowRef.find('.selected-checklists').val();
                if (hasExistingData && hasExistingData.trim() !== '') {
                    loadExistingSelections(currentRowId);
                }
                
                // Auto-load all checklists in the modal for edit mode
                console.log('Loading checklists for modal...'); // Debug log
                loadAllChecklistsForModal();
                
                console.log('Showing modal...'); // Debug log
                $('#checklist').modal('show');
            });
            
            /**
             * Get row ID from maintenance row element
             */
            function getRowId(rowElement) {
                // Try to get from input name first
                const inputName = rowElement.find('input, select').first().attr('name');
                if (inputName) {
                    const match = inputName.match(/maintenance\[(.*?)\]/);
                    if (match) return match[1];
                }
                
                // Fallback to data attribute
                const dataRowId = rowElement.attr('data-row-id');
                if (dataRowId) return dataRowId;
                
                // Generate temporary ID as last resort
                return 'temp-' + Date.now();
            }
            
            /**
             * Reset checklist modal to clean state
             */
            function resetChecklistModal() {
                $('#checklist .modal-body select').val('');
                $('#checklist .modal-body tbody').empty();
                $('#checklist .modal-body input[type="checkbox"]').prop('checked', false);
                selectedChecklistIds = [];
                
                // Clear portion checklist data for current row
                portionChecklistData = {};

            }
            
            /**
             * Load all available checklists in the modal for edit mode
             */
            function loadAllChecklistsForModal() {
                // Clear any existing portions first
                $("#checkListPortion").empty();

                // Get all checklists from the PHP data
                const allChecklists = @json($checklists);

                if (allChecklists && allChecklists.length > 0) {
                    // Create portions for each checklist
                    allChecklists.forEach(function(checklist, index) {
                        const portionId = `portion_${checklist.id}`;

                        // Load checklist details for this checklist
                        loadChecklistDetailsForModal(checklist.id, checklist.name, portionId);
                    });

                    // Check after a delay if any portions were loaded
                    setTimeout(() => {
                        if ($("#checkListPortion").children().length === 0) {
                            $("#checkListPortion").html('<p class="text-center text-muted">Loading checklists...</p>');
                        }
                    }, 500);
                } else {
                    $("#checkListPortion").html('<p class="text-center text-muted">No checklists available</p>');
                }
            }

            /**
             * Load checklist details for modal display
             */
            function loadChecklistDetailsForModal(checklistId, checklistName, portionId) {
                $.ajax({
                    url: '{{ route("equipment.get-checklist-details") }}',
                    method: 'POST',
                    data: {
                        checklist_id: checklistId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success && response.data.checklist) {
                            createChecklistPortionForModal(response.data, checklistId, checklistName, portionId);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading checklist details for modal:', error);
                    }
                });
            }

            /**
             * Create checklist portion in modal with all items and selected ones checked
             */
            function createChecklistPortionForModal(data, checklistId, checklistName, portionId) {
                // Store checklist data for this portion
                portionChecklistData[portionId] = {
                    checklistId: checklistId,
                    checklistName: checklistName
                };

                let tableRows = "";
                const checklists = data.checklist || [];
                
                // Get existing selected IDs to mark as checked (only from current row)
                let existingSelectedIds = [];
                if (checklistRowRef) {
                    const selectedIdsString = checklistRowRef.find('.selected-checklists').val();
                    if (selectedIdsString) {
                        existingSelectedIds = selectedIdsString.split(',').filter(Boolean);
                    }
                }

                if (checklists.length > 0) {
                    checklists.forEach(function (checklist) {
                        const isChecked = existingSelectedIds.includes(checklist.id.toString()) ? 'checked' : '';
                        
                        tableRows += `
                            <tr>
                                <td class="customernewsection-form">
                                    <div class="form-check form-check-primary custom-checkbox">
                                        <input type="checkbox" class="form-check-input" value="${checklist.id}" ${isChecked}>
                                        <label class="form-check-label"></label>
                                    </div>
                                </td>
                                <td>${checklist.name || ''}</td>
                                <td>${checklist.description || ''}</td>
                                <td>
                                    <span class="badge rounded-pill badge-light-info">
                                        ${checklist.data_type || ''}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });

                    // Only create portion if there are items
                    if (tableRows.trim() !== '') {
                        // Create the portion HTML
                        const portionHtml = `
                            <div class="row checklist-portion mb-2" id="${portionId}">
                                <div class="col-md-12">
                                    <h5 class="mb-1">${checklistName}</h5>
                                    <div class="table-responsive">
                                        <table class="mt-1 table myrequesttablecbox table-striped po-order-detail">
                                            <thead>
                                                <tr>
                                                    <th width="40px" class="customernewsection-form">
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox" class="form-check-input select-all">
                                                        </div>
                                                    </th>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${tableRows}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;

                        // Append to modal
                        $("#checkListPortion").append(portionHtml);
                    }
                }
            }

          

            /**
             * Load existing checklist selections for the current row
             */
            function loadExistingSelections(rowId) {
                if (!checklistRowRef) return;
                
                const selectedIds = checklistRowRef.find('.selected-checklists').val();
                if (selectedIds) {
                    const ids = selectedIds.split(',').filter(Boolean);
                    
                    // Pre-check existing selections when modal content loads
                    setTimeout(() => {
                        $('#checklist .modal-body input[type="checkbox"]').each(function () {
                            if (ids.includes($(this).val())) {
                                $(this).prop('checked', true);
                            }
                        });
                    }, 100);
                }
            }

            /**
             * Handle checklist modal close - save selections
             */
            $('#checklist').on('hide.bs.modal', function (e) {
                // Only proceed if submit button was clicked
                if ($(document.activeElement).hasClass('btn-primary')) {
                    saveChecklistSelections();
                }
            });
            
            /**
             * Save checklist selections to the maintenance row
             */
            function saveChecklistSelections() {
                if (!currentRowId || !checklistRowRef) {
                    console.error('No active row for checklist selection');
                    return;
                }
                
                const selectedData = collectSelectedChecklistData();
                
                // Skip update if no selections made
                if (selectedData.length === 0) {
                    return;
                }
                
                // Update the row with selected checklist data
                updateRowWithChecklistData(selectedData);
            }
            
            /**
             * Collect selected checklist data from modal
             * Only collect essential IDs - backend will fetch details
             */
            function collectSelectedChecklistData() {
                const selectedData = [];
                
                // Loop through all checklist portions
                $('#checklist .modal-body .checklist-portion').each(function() {
                    const portion = $(this);
                    const portionId = portion.attr('id') || 'portion_default';
                    const portionData = portionChecklistData[portionId];
                    
                    if (!portionData) {

                        return;
                    }
                    

                    
                    // Find checked checkboxes in this portion
                    portion.find('input[type="checkbox"]:checked').each(function () {
                        const checkbox = $(this);
                        
                        const item = {
                            checklist_id: portionData.checklistId,
                            checklist_detail_id: checkbox.val()
                        };
                        

                        selectedData.push(item);
                    });
                });
                

                return selectedData;
            }
            
            function updateRowWithChecklistData(selectedData) {
                const context = checklistContexts[currentRowId] || {};
                const selectedIds = selectedData.map(item => item.checklist_detail_id);

                // Collect unique main checklist IDs and names
                const mainChecklists = new Map();
                selectedData.forEach(item => {
                    // Find the checklist name from portion data
                    let checklistName = null;
                    const portionData = Object.values(portionChecklistData).find(p => p.checklistId == item.checklist_id);
                    if (portionData && portionData.checklistName) {
                        checklistName = portionData.checklistName;
                    } else {
                        // Fallback: try to find from any portion that might have loaded this checklist
                        const anyPortion = Object.values(portionChecklistData).find(p => p.checklistId == item.checklist_id);
                        if (anyPortion) {
                            checklistName = anyPortion.checklistName;
                        }
                    }

                    if (checklistName) {
                        // Use checklist_id as key to ensure uniqueness
                        mainChecklists.set(parseInt(item.checklist_id), checklistName);
                    }
                });

                // Convert to array of unique main checklist names
                const uniqueNames = Array.from(mainChecklists.values());

                console.log('Selected data:', selectedData);
                console.log('Portion checklist data:', portionChecklistData);
                console.log('Main checklists map:', mainChecklists);
                console.log('Unique names:', uniqueNames);

                // Create badge display
                let badgesHtml = '';
                if (uniqueNames.length === 1) {
                    // Show only the checklist name when 1 main checklist is selected
                    badgesHtml = `<span class="badge rounded-pill badge-light-primary">${uniqueNames[0]}</span>`;
                } else if (uniqueNames.length > 1) {
                    // Show first name + count when multiple main checklists are selected
                    badgesHtml = `<span class="badge rounded-pill badge-light-primary">${uniqueNames[0]}</span>`;
                    badgesHtml += ` <span class="badge rounded-pill badge-light-primary">+${uniqueNames.length - 1}</span>`;
                }

                // Create hidden inputs for form submission - only essential IDs
                let hiddenInputs = '';
                selectedData.forEach((item, index) => {
                    hiddenInputs += `
                        <input type="hidden" name="maintenance[${currentRowId}][checklists][${index}][checklist_id]" value="${item.checklist_id || ''}">
                        <input type="hidden" name="maintenance[${currentRowId}][checklists][${index}][checklist_detail_id]" value="${item.checklist_detail_id}">
                    `;
                });

                // Update row HTML
                const buttonClass = @json($buttons['submit']) ? 'open-checklist-modal' : 'view-checklist-modal';
                const newHtml = `
                    <span class="checklist-badges">${badgesHtml}</span>
                    <button type="button" class="btn p-25 btn-sm btn-outline-secondary ${buttonClass}" style="font-size: 10px">Add Checklist</button>
                    <input type="hidden" class="selected-checklists" value="${selectedIds.join(',')}" />
                    ${hiddenInputs}
                `;

                checklistRowRef.find('.checklist-cell').html(newHtml);
            }

            /**
             * Handle search button click for checklist details
             */
          
            
            /**
             * Load checklist details from server
             */
            function loadChecklistDetails(checklistId, buttonElement) {
                $.ajax({
                    url: '{{ route("equipment.get-checklist-details") }}',
                    method: 'POST',
                    data: {
                        checklist_id: checklistId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (response) {
                        if (response.success && response.data.checklist) {
                            populateChecklistTable(response.data, buttonElement);
                        } else {
                            showChecklistError(response.message || 'No checklist data found', buttonElement);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        showChecklistError('Error loading checklist details', buttonElement);
                    }
                });
            }
            
            /**
             * Populate checklist table with data
             */
            function populateChecklistTable(data, buttonElement) {
                const tbody = buttonElement.closest('.row').find('tbody');
                tbody.empty();
                
                // Get existing selected IDs
                let existingSelectedIds = checkListIds || [];
                $('#maintenanceRows .selected-checklists').each(function() {
                    const val = $(this).val();
                    if (val) {
                        existingSelectedIds = existingSelectedIds.concat(val.split(',').filter(Boolean));
                    }
                });
                
                const checklists = data.checklist || [];
                
                if (checklists.length > 0) {
                    checklists.forEach(function (checklist) {
                        const isPreSelected = existingSelectedIds.includes(checklist.id.toString());
                        
                        const tableRow = `
                            <tr>
                                <td class="customernewsection-form">
                                    <div class="form-check form-check-primary custom-checkbox">
                                        <input type="checkbox" class="form-check-input" value="${checklist.id}" ${isPreSelected ? 'checked' : ''}>
                                        <label class="form-check-label"></label>
                                    </div>
                                </td>
                                <td>${checklist.name || ''}</td>
                                <td>${checklist.description || ''}</td>
                                <td>
                                    <span class="badge rounded-pill badge-light-info">
                                        ${checklist.data_type || ''}
                                    </span>
                                </td>
                            </tr>
                        `;
                        tbody.append(tableRow);
                    });
                    
                    updateAllDropdowns();
                } else {
                    tbody.html('<tr><td colspan="4" class="text-center text-muted">No checklist data found</td></tr>');
                }
            }
            
            /**
             * Show error message in checklist table
             */
            function showChecklistError(message, buttonElement) {
                const tbody = buttonElement.closest('.row').find('tbody');
                tbody.html(`<tr><td colspan="4" class="text-center text-danger">${message}</td></tr>`);
            }

            /**
             * Update all dropdowns by removing selected options
             */
            function updateAllDropdowns() {
                let allSelectedIds = [];
                
                // Get currently selected checklist IDs from all portions
                $('.checklist-portion select').each(function() {
                    const val = $(this).val();
                    if (val && val !== 'Select') {
                        allSelectedIds.push(val);
                    }
                });
                
                // Remove duplicates
                allSelectedIds = [...new Set(allSelectedIds)];
                
                // Update each dropdown
                $('.form-select.select2').each(function() {
                    const currentSelect = $(this);
                    const currentValue = currentSelect.val();
                    
                    // Remove all options except the first one (Select)
                    currentSelect.find('option:not(:first)').remove();
                    
                    let addedCount = 0;
                    // Add back all checklists that are not selected in other portions
                    @foreach($checklists as $checklist)
                        // Allow current selection but prevent selection in other portions
                        if (!allSelectedIds.includes('{{ $checklist->id }}') || currentValue === '{{ $checklist->id }}') {
                            currentSelect.append(`<option value="{{ $checklist->id }}">{{ $checklist->name }}</option>`);
                            addedCount++;
                        }
                    @endforeach
                    

                    
                    // Restore current value if it's still valid
                    if (currentValue && currentValue !== 'Select') {
                        currentSelect.val(currentValue);
                    }
                });
            }

            // Add Portion function (same as create page) - Add new checklist sections in modal
            window.addPortion = function() {
                // First get currently selected checklist IDs to exclude them
                let selectedIds = [];
                


                
                $('.checklist-portion select').each(function(index) {
                    const val = $(this).val();

                    if (val && val !== 'Select') {
                        selectedIds.push(val);
                    }
                });
                

                
                const portionId = 'portion_' + Date.now(); // Unique ID for each portion
                
                // Build available checklists array from PHP
                const allChecklists = [
                    @foreach($checklists as $index => $checklist)
                    {
                        id: '{{ $checklist->id }}',
                        name: '{{ addslashes($checklist->name) }}'
                    }@if(!$loop->last),@endif
                    @endforeach
                ];
                

                
                // Build options dynamically excluding already selected ones
                let optionsHtml = '<option>Select</option>';
                allChecklists.forEach(function(checklist) {
                    if (!selectedIds.includes(checklist.id)) {
                        optionsHtml += `<option value="${checklist.id}">${checklist.name}</option>`;
                    }
                });
                

                
                const newPortionHtml = `
                    <div class="row checklist-portion" id="${portionId}">
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label class="form-label">Checklist <span class="text-danger">*</span></label>
                                <select class="form-select select2">
                                    ${optionsHtml}
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 mb-1">
                            <label class="form-label">&nbsp;</label><br />
                            <button class="btn btn-warning btn-sm waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> Search</button>
                        </div>

                        <div class="col-md-12">
                            <div class="table-responsive">
                                Select Checklist
                                <div class="text-end" style="margin-top: -30px">
                                    <a href="#" class="text-primary add-contactpeontxt mt-50 me-2" onclick="addPortion()"><i data-feather='plus'></i> Add Checklist</a>
                                    <a href="#" class="text-danger remove-contactpeontxt mt-50" onclick="removePortion('${portionId}')"><i data-feather='minus'></i> Remove Checklist</a>
                                </div>
                                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail">
                                    <thead>
                                        <tr>
                                            <th width="40px" class="customernewsection-form">
                                                <div class="form-check form-check-primary custom-checkbox">
                                                    <input type="checkbox" class="form-check-input" id="Email_${portionId}">
                                                    <label class="form-check-label" for="Email_${portionId}"></label>
                                                </div>
                                            </th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                       
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;

                $('#checkListPortion').append(newPortionHtml);
                
                // Re-initialize Select2 for new dropdowns
                $('.select2').select2();
                
                // Don't call updateAllDropdowns() here as we already built filtered options

            }

            // Remove Portion function
            window.removePortion = function(portionId) {
                $('#' + portionId).remove();
                // Update all dropdowns after removing a portion
                updateAllDropdowns();

            }

            // Handle modal select all checkbox
            $('.myrequesttablecbox thead input[type="checkbox"]').on('change', function () {
                var tbody = $(this).closest('table').find('tbody');
                var checked = $(this).is(':checked');
                tbody.find('input.form-check-input').prop('checked', checked);
            });

            // Handle dropdown change to update other dropdowns (prevent duplicates)
            $(document).on('change', '.checklist-portion select', function() {
                updateAllDropdowns();
            });

            // Collect checklist data and associate with maintenance rows (same as create page)
            function collectChecklistData() {
                let checklistData = [];
                let detailIdCounter = 1;
                

                
                // Loop through all checklist portions
                $('#checkListPortion .row').each(function(index) {
                    const portion = $(this);
                    const checklistId = portion.find('select').val();
                    const checklistName = portion.find('select option:selected').text();
                    

                    
                    // Only include if a checklist is selected
                    if (checklistId && checklistId !== 'Select') {
                        // Add all items from this checklist (only checked items)
                        portion.find('tbody tr').each(function(itemIndex) {
                            const row = $(this);
                            const checkbox = row.find('input[type="checkbox"]');
                            
                            // Only collect checked items
                            if (checkbox.is(':checked')) {
                                const itemChecklistId = row.find('td:nth-child(6)').text().trim();// This is the actual checklist detail ID
                                const itemName = row.find('td:nth-child(5)').text().trim();
                                const itemDescription = row.find('td:nth-child(3)').text().trim();
                                const itemType = row.find('td:nth-child(4)').text().trim();
                                

                                
                                checklistData.push({
                                    checklist_id: checklistId, // Header checklist ID from dropdown
                                    checklist_detail_id: itemChecklistId, // Actual detail ID from checkbox
                                    name: itemName,
                                    description: itemDescription,
                                    type: itemType
                                });
                                detailIdCounter++;
                            }
                        });
                    }
                });
                


                
                return checklistData;
            }

            // Update maintenance rows with checklist data (same as create page)
            /**
             * Update checklist inputs for form submission
             * This function ensures all checklist data is properly included in the form
             */
            function updateChecklistInputs() {
                // This function is now handled by the modal close handler
                // which directly updates the row with hidden inputs
                // No additional processing needed here as data is already in the form

            }

            /**
             * Handle form submission - ensure checklist data is preserved
             */
            $('form').on('submit', function(e) {
                // Always collect and update checklist data before form submission
                updateChecklistInputs();
            });

            // Template row for Spare Part
            // function getSparePartRow() {
            //     return `<tr>
            //                 <td class="customernewsection-form">
            //                     <div class="form-check form-check-primary custom-checkbox">
            //                         <input type="checkbox" class="form-check-input row-checkbox">
            //                         <label class="form-check-label"></label>
            //                     </div>
            //                 </td>
            //                 <td class="poprod-decpt"><input type="text" placeholder="Select" class="form-control mw-100 ledgerselecct mb-25" /></td>
            //                 <td class="poprod-decpt"><input type="text" placeholder="Select" class="form-control mw-100 ledgerselecct mb-25" /></td>
            //                 <td class="poprod-decpt">
            //                     <button data-bs-toggle="modal" data-bs-target="#attribute" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
            //                 </td>
            //                 <td><select class="form-select"><option>Select</option><option selected>KG</option></select></td>
            //                 <td><input type="text" value="10" class="form-control mw-100" /></td>
            //             </tr>`;
            // }

            function getSparePartRow(data = {}) {
               let itemOptions = `<option value="">Select</option>`;
                items.forEach(function (item) {
                    itemOptions += `<option value="${item.id}" data-name="${item.item_name}" data-code="${item.item_code}" ${data.item_code == item.id ? 'selected' : ''}>${item.item_code}</option>`;
                });

                const rowId = 'spare-' + Math.random().toString(36).substring(2, 10);
                let badgeHtml = '';


                let attributes = JSON.parse(data.attributes??null);
                if(attributes==null) return;
               
                Object.entries(attributes).forEach(([key, value]) => {
                    badgeHtml += `<span class="badge rounded-pill badge-light-primary">${key}: ${value}</span> `;
                });
                return `<tr data-row-id="${rowId}">
                    <td class="customernewsection-form">
                        <div class="form-check form-check-primary custom-checkbox">
                            <input type="checkbox" class="form-check-input row-checkbox">
                            <label class="form-check-label"></label>
                        </div>
                    </td>
                    <td class="poprod-decpt">
                        <select class="form-select mw-100 item-code-dropdown" name="spareparts[${rowId}][item_code]">
                            ${itemOptions}
                        </select>
                    </td>
                    <td class="poprod-decpt">
                        <input type="text" class="form-control mw-100 item-name-input" name="spareparts[${rowId}][item_name]" value="${data.item_name}" />
                    </td>
                    <td class="poprod-decpt">
                        ${badgeHtml}
                        <button type="button" data-row-id="${rowId}" class="btn p-25 btn-sm btn-outline-secondary open-attribute-modal" style="font-size: 10px">Attributes</button>
                        <input type="hidden" name="spareparts[${rowId}][attributes]" class="attributes-input" value="${data.attributes}" />
                    </td>
                    <td>
                        <select class="form-select" name="spareparts[${rowId}][uom]">
                            <option value="">Select</option>
                            <option value="KG" ${data.uom == 'KG' ? 'selected' : ''}>KG</option>
                            <option value="PCS" ${data.uom == 'PCS' ? 'selected' : ''}>PCS</option>
                            <option value="BOX" ${data.uom == 'BOX' ? 'selected' : ''}>BOX</option>
                            <option value="UNIT" ${data.uom == 'UNIT' ? 'selected' : ''}>UNIT</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="spareparts[${rowId}][qty]" value="1" min="0" step="0.01" class="form-control mw-100" value="${data.qty}" />
                    </td>
                </tr>`;
            }

            let attributeRowRef = null;
            $(document).on('click', '.open-attribute-modal', function () {
                attributeRowRef = $(this).closest('tr');

                const rowId = attributeRowRef.data('row-id');

                $('.attribute-select').val('');

                // Load existing attributes if any
                const input = $(`input[name="spareparts[${rowId}][attributes]"]`);
                if (input.length && input.val()) {
                    try {
                        const attributes = JSON.parse(input.val());
                        Object.entries(attributes).forEach(([key, value]) => {
                            $(`.attribute-select[data-attribute="${key}"]`).val(value);
                        });
                    } catch (e) {
                        console.error('Invalid JSON in attributes input:', e);
                    }
                }


                $('#attribute').modal('show');

            });

            $('#attribute').on('hide.bs.modal', function (e) {
                if ($(document.activeElement).hasClass('btn-primary')) {
                    if (!attributeRowRef) return;

                    const attributes = {};

                    // Collect all selected attributes
                    $('.attribute-select').each(function () {
                        const attrName = $(this).data('attribute');
                        const attrValue = $(this).val();

                        if (attrValue) {
                            attributes[attrName] = attrValue;
                        }
                    });

                    const rowId = attributeRowRef.data('row-id');

                    // Store as JSON in the hidden input
                    const input = $(`input[name="spareparts[${rowId}][attributes]"]`);
                    input.val(JSON.stringify(attributes));

                    // Display selected attributes in the same row (column 4)
                    let badgeHtml = '';
                    Object.entries(attributes).forEach(([key, value]) => {
                        badgeHtml += `<span class="badge rounded-pill badge-light-primary">${key}: ${value}</span> `;
                    });

                    const cellHtml = `
                        ${badgeHtml}
                        <button type="button" data-row-id="${rowId}" class="btn p-25 btn-sm btn-outline-primary open-attribute-modal" style="font-size: 10px">Attributes </button>
                        <input type="hidden" name="spareparts[${rowId}][attributes]" class="attributes-input" value='${JSON.stringify(attributes)}' />
                    `;

                    $(`tr[data-row-id="${rowId}"]`).find('td').eq(3).html(cellHtml);

                }
            });

            $(document).on('change', '.item-code-dropdown', function () {
                let selectedOption = $(this).find('option:selected');
                let itemName = selectedOption.data('name') || '';
                $(this).closest('tr').find('.item-name-input').val(itemName);

                let selectedItem = items.find(item => item.id === parseInt(selectedOption.val()));

                if (selectedItem) {
                    $('#part-detail-name').html(selectedItem.item_name);
                    $('#part-detail-hsn').html(selectedItem.hsn_id);
                    $('#part-detail-color').html(selectedItem.color ?? 'N/A');
                    $('#part-detail-size').html(selectedItem.size ?? 'N/A');
                    $('#part-detail-uom').html(selectedItem.uom_id ?? 'N/A');
                    $('#part-detail-qty').html(selectedItem.qty ?? 'N/A');
                    $('#part-detail-remarks').html(selectedItem.item_remark ?? 'N/A');
                }
            });

            $(document).on('click', '.open-attribute-modal', function () {
                const rowId = $(this).data('row-id');
                $('#attribute-row-id').val(rowId);

                // Reset all attribute selects
                $('.attribute-select').val('');

                // Load existing attributes if any
                const attributesInput = $(`input[name="spareparts[${rowId}][attributes]"]`);
                if (attributesInput.length && attributesInput.val()) {
                    try {
                        const attributes = JSON.parse(attributesInput.val());

                        // Set values in the modal
                        for (const [key, value] of Object.entries(attributes)) {
                            $(`.attribute-select[data-attribute="${key}"]`).val(value);
                        }
                    } catch (e) {
                        console.error('Error parsing attributes:', e);
                    }
                }

                $('#attribute').modal('show');
            });

            $('#save-attributes').on('click', function () {
                const rowId = $('#attribute-row-id').val();
                if (!rowId) return;

                const attributes = {};

                // Collect all selected attributes
                $('.attribute-select').each(function () {
                    const attrName = $(this).data('attribute');
                    const attrValue = $(this).val();

                    if (attrValue) {
                        attributes[attrName] = attrValue;
                    }
                });

                // Store as JSON in the hidden input
                $(`input[name="spareparts[${rowId}][attributes]"]`).val(JSON.stringify(attributes));

                // Show a visual indicator that attributes are set
                const attributeCount = Object.keys(attributes).length;
                const attributeBtn = $(`.open-attribute-modal[data-row-id="${rowId}"]`);

                if (attributeCount > 0) {
                    attributeBtn.removeClass('btn-outline-secondary').addClass('btn-outline-primary');
                    attributeBtn.html(`Attributes (${attributeCount})`);
                } else {
                    attributeBtn.removeClass('btn-outline-primary').addClass('btn-outline-secondary');
                    attributeBtn.html('Attributes');
                }

                // Close the modal
                $('#attribute').modal('hide');
            });


            // File upload validation
        $(document).on('change', 'input[name="upload_document"]', function() {
            const fileInput = $(this);
            const file = fileInput[0].files[0];

            if (file) {
                // Check file size (5MB = 5 * 1024 * 1024 bytes)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'File size must be less than 5MB. Please select a smaller file.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#7367F0'
                    });
                    fileInput.val(''); // Clear the file input
                    return;
                }

                // Check file type
                const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/pdf'];
                const allowedExtensions = ['png', 'jpeg', 'jpg', 'xls', 'xlsx', 'doc', 'docx', 'pdf'];

                const fileType = file.type;
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.split('.').pop();

                // Check if file type is allowed or extension is allowed
                const isTypeAllowed = allowedTypes.includes(fileType);
                const isExtensionAllowed = allowedExtensions.includes(fileExtension);

                if (!isTypeAllowed && !isExtensionAllowed) {
                    Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Only PNG, JPEG, JPG, XLS, DOCX, and PDF files are allowed.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#7367F0'
                    });
                    fileInput.val(''); // Clear the file input
                    return;
                }

                console.log('File validation passed:', {
                    name: file.name,
                    size: (file.size / 1024 / 1024).toFixed(2) + 'MB',
                    type: file.type
                });
            }
        });

        // Add row based on active tab with validation
        $('#addRowBtn').on('click', function (e) {
            e.preventDefault();
            var activeTab = $('.tab-pane.active').attr('id');

            // For maintenance tab, validate all existing rows are complete before adding new row
            if (activeTab === 'Maintenance') {
                const validationResult = validateAllMaintenanceRowsComplete();
                if (!validationResult.isValid) {
                    Swal.fire({
                        title: 'Complete All Rows First',
                        html: validationResult.message,
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#7367F0'
                    });
                    return false;
                }
                $('#maintenanceRows').append(getMaintenanceRow());
            } else if (activeTab === 'Spare') {
                $('#spareRows').append(getSparePartRow());
            }
        });

        // Function to validate that ALL maintenance rows are completely filled
        function validateAllMaintenanceRowsComplete() {
            let allValid = true;
            let errorMessages = [];

            $('#maintenanceRows tr').each(function (index) {
                const row = $(this);
                const rowNumber = index + 1;
                let rowErrors = [];

                const typeSelect = row.find('select[name^="maintenance"][name$="[type]"]');
                const frequencySelect = row.find('select[name^="maintenance"][name$="[frequency]"]');
                const dateInput = row.find('input[name^="maintenance"][name$="[date]"]');
                const timeInput = row.find('input[name^="maintenance"][name$="[time]"]');
                const bomSelect = row.find('select[name^="maintenance"][name$="[bom]"]');
                const checklistHidden = row.find('.selected-checklists');

                // Validate each field - ALL fields must be filled
                if (typeSelect.val() === '') {
                    rowErrors.push('Maintenance Type');
                    allValid = false;
                }

                if (frequencySelect.val() === '') {
                    rowErrors.push('Frequency');
                    allValid = false;
                }

                if (dateInput.val() === '') {
                    rowErrors.push('Start Date');
                    allValid = false;
                }

                if (timeInput.val() === '') {
                    rowErrors.push('Time');
                    allValid = false;
                }

                if (bomSelect.val() === '') {
                    rowErrors.push('Maintenance BOM');
                    allValid = false;
                }

                if (!checklistHidden.val() || checklistHidden.val() === '') {
                    rowErrors.push('Checklist');
                    allValid = false;
                }

                // Add row-specific error message if there are errors
                if (rowErrors.length > 0) {
                    errorMessages.push(`<strong>Row ${rowNumber}:</strong> ${rowErrors.join(', ')}`);
                }
            });

            return {
                isValid: allValid,
                message: errorMessages.length > 0
                    ? `<div style="text-align: left;"><strong>Please complete all fields in the following rows before adding a new row:</strong><br><br>${errorMessages.join('<br>')}</div>`
                    : ''
            };
        }

            // Delete selected rows from active tab
            $('#deleteRowBtn').on('click', function (e) {
                e.preventDefault();

                let activeTab = $('.tab-pane.active').attr('id');
                let checkboxes, table;

                if (activeTab === 'Maintenance') {
                    checkboxes = $('#maintenanceRows').find('input.row-checkbox:checked');
                    table = $('#maintenanceRows').closest('table');
                } else if (activeTab === 'Spare') {
                    checkboxes = $('#spareRows').find('input.row-checkbox:checked');
                    table = $('#spareRows').closest('table');
                }

                if (checkboxes.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No rows selected',
                        text: 'Please select at least one row to delete.',
                    });
                    return;
                }

                checkboxes.closest('tr').remove();
                table.find('thead input[type="checkbox"]').prop('checked', false);
            });

            // (Optional) "Select All" checkbox per table
            $('.myrequesttablecbox thead input[type="checkbox"]').on('change', function () {
                var tbody = $(this).closest('table').find('tbody');
                var checked = $(this).is(':checked');
                tbody.find('input.row-checkbox').prop('checked', checked);
            });
        });

        function submitForm(status) {
            console.log('submitForm called with status:', status);

            // Check if we're in amendment mode (amendment=1 in URL)
            const urlParams = new URLSearchParams(window.location.search);
            const isAmendment = urlParams.get('amendment') === '1';

            if (isAmendment && (status === 'submitted')) {
                $('#status').val(status);
                $('#document_status').val(status); // Fix: Set document_status in amendment mode too
                $('#action_type').val('amendment'); // Ensure action_type is set to amendment
                $('#amendmentconfirm').modal('show');
                return;
            }

            $('#status').val(status);
            $('#document_status').val(status);
            // Set action_type based on amendment mode and status
            if (isAmendment && status === 'submitted') {
                $('#action_type').val('amendment');
            } else {
                $('#action_type').val(status);
            }

            let isValid = true;
            let errorMessage = '';

            // Basic Information validation
            console.log('Validating basic information...');
            if ($('#organization_id').val() === '') {
                console.log('Organization is empty');
                isValid = false;
                errorMessage += 'Organization is required.<br>';
            }

            if ($('#location_id').val() === '' && isValid) {
                console.log('Location is empty');
                isValid = false;
                errorMessage += 'Location is required.<br>';
            }

            if ($('#category_id').val() === '' && isValid) {
                console.log('Category is empty');
                isValid = false;
                errorMessage += 'Category is required.<br>';
            }

            if ($('input[name="name"]').val() === '' && isValid) {
                console.log('Name is empty');
                isValid = false;
                errorMessage += 'Name is required.<br>';
            }

            // Validate maintenance rows if any exist
            console.log('Validating maintenance rows...');
            let hasCompleteMaintenanceRow = false;
            let maintenanceRowCount = 0;

            $('#maintenanceRows tr').each(function (index) {
            const row = $(this);
            const rowNumber = index + 1;
            maintenanceRowCount++;
            console.log(`Validating maintenance row ${rowNumber}`);

            const typeSelect = row.find('select[name^="maintenance"][name$="[type]"]');
            const frequencySelect = row.find('select[name^="maintenance"][name$="[frequency]"]');
            const dateInput = row.find('input[name^="maintenance"][name$="[date]"]');
            const timeInput = row.find('input[name^="maintenance"][name$="[time]"]');
            const bomSelect = row.find('select[name^="maintenance"][name$="[bom]"]');
            const checklistHidden = row.find('.selected-checklists');

            console.log(`Row ${rowNumber} field values:`, {
                type: typeSelect.val(),
                frequency: frequencySelect.val(),
                date: dateInput.val(),
                time: timeInput.val(),
                bom: bomSelect.val(),
                checklist: checklistHidden.val()
            });

            // Check if this row is completely filled
            const isRowComplete = typeSelect.val() !== '' && frequencySelect.val() !== '' &&
                                dateInput.val() !== '' && timeInput.val() !== '' &&
                                bomSelect.val() !== '' && checklistHidden.val() !== '';

            if (isRowComplete) {
                hasCompleteMaintenanceRow = true;
                console.log(`Row ${rowNumber} is completely filled`);
            }

            // For final submission (not draft), ALL rows must be completely filled
            if (status === 'submitted') {
                console.log(`Final submission - validating ALL fields in row ${rowNumber}`);

                if (typeSelect.val() === '') {
                    console.log(`Row ${rowNumber}: Maintenance type is empty`);
                    isValid = false;
                    errorMessage += `Maintenance type is required for row ${rowNumber}.<br>`;
                }

                if (frequencySelect.val() === '') {
                    console.log(`Row ${rowNumber}: Frequency is empty`);
                    isValid = false;
                    errorMessage += `Frequency is required for row ${rowNumber}.<br>`;
                }

                if (dateInput.val() === '') {
                    console.log(`Row ${rowNumber}: Date is empty`);
                    isValid = false;
                    errorMessage += `Start date is required for row ${rowNumber}.<br>`;
                }

                if (timeInput.val() === '') {
                    console.log(`Row ${rowNumber}: Time is empty`);
                    isValid = false;
                    errorMessage += `Time is required for row ${rowNumber}.<br>`;
                }

                if (bomSelect.val() === '') {
                    console.log(`Row ${rowNumber}: BOM is empty`);
                    isValid = false;
                    errorMessage += `Maintenance BOM is required for row ${rowNumber}.<br>`;
                }

                if (!checklistHidden.val() || checklistHidden.val() === '') {
                    console.log(`Row ${rowNumber}: Checklist is empty`);
                    isValid = false;
                    errorMessage += `Checklist is required for row ${rowNumber}.<br>`;
                }
            } else {
                // For draft submission, only validate rows that have some data
                const hasAnyValue = typeSelect.val() !== '' || frequencySelect.val() !== '' ||
                                    dateInput.val() !== '' || timeInput.val() !== '' ||
                                    bomSelect.val() !== '' || checklistHidden.val() !== '';

                if (hasAnyValue && isValid) {
                    console.log(`Draft submission - validating partial row ${rowNumber}`);

                    if (typeSelect.val() === '') {
                        console.log(`Row ${rowNumber}: Maintenance type is empty`);
                        isValid = false;
                        errorMessage += `Maintenance type is required for row ${rowNumber}.<br>`;
                    }

                    if (frequencySelect.val() === '') {
                        console.log(`Row ${rowNumber}: Frequency is empty`);
                        isValid = false;
                        errorMessage += `Frequency is required for row ${rowNumber}.<br>`;
                    }

                    if (dateInput.val() === '') {
                        console.log(`Row ${rowNumber}: Date is empty`);
                        isValid = false;
                        errorMessage += `Start date is required for row ${rowNumber}.<br>`;
                    }

                    if (timeInput.val() === '') {
                        console.log(`Row ${rowNumber}: Time is empty`);
                        isValid = false;
                        errorMessage += `Time is required for row ${rowNumber}.<br>`;
                    }

                    if (bomSelect.val() === '') {
                        console.log(`Row ${rowNumber}: BOM is empty`);
                        isValid = false;
                        errorMessage += `Maintenance BOM is required for row ${rowNumber}.<br>`;
                    }

                    if (!checklistHidden.val() || checklistHidden.val() === '') {
                        console.log(`Row ${rowNumber}: Checklist is empty`);
                        isValid = false;
                        errorMessage += `Checklist is required for row ${rowNumber}.<br>`;
                    }
                } else if (!hasAnyValue) {
                    console.log(`Row ${rowNumber} is completely empty, skipping validation for draft`);
                }
            }
        });

                // For final submission (not draft), require at least one complete maintenance row
                if (status === 'submitted' && !hasCompleteMaintenanceRow) {
                    console.log('No complete maintenance rows found for final submission');
                    isValid = false;
                    errorMessage += 'At least one complete maintenance row is required for final submission.<br>';
                }

                console.log(`Validation summary:`, {
                    totalRows: maintenanceRowCount,
                    hasCompleteMaintenanceRow: hasCompleteMaintenanceRow,
                    status: status,
                    isValid: isValid
                });

                // Validate spare parts rows if any exist
                console.log('Validating spare parts rows...');
                $('#spareRows tr').each(function () {
                    const itemCodeSelect = $(this).find('select[name^="spareparts"][name$="[item_code]"]');
                    const itemNameInput = $(this).find('input[name^="spareparts"][name$="[item_name]"]');
                    const uomInput = $(this).find('select[name^="spareparts"][name$="[uom]"]');
                    const qtyInput = $(this).find('input[name^="spareparts"][name$="[qty]"]');

                    if (itemCodeSelect.val() !== '' || itemNameInput.val() !== '' && isValid) {
                        if (itemCodeSelect.val() === '') {
                            isValid = false;
                            errorMessage += 'Item code is required for all spare part rows.<br>';
                        }

                        if (itemNameInput.val() === '') {
                            isValid = false;
                            errorMessage += 'Item name is required for all spare part rows.<br>';
                        }

                        if (uomInput.val() === '') {
                            isValid = false;
                            errorMessage += 'UOM is required for all spare part rows.<br>';
                        }

                        if (qtyInput.val() === '' || parseFloat(qtyInput.val()) < 0) {
                            isValid = false;
                            errorMessage += 'Valid quantity is required for all spare part rows.<br>';
                        }
                    }
                });

                if (!isValid) {
                    console.log('Form validation failed, showing SweetAlert error');
                    Swal.fire({
                        title: 'Validation Error',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#7367F0'
                    });
                    return false;
                }

                // If draft, confirm with user
                if (status === 'draft') {
                    Swal.fire({
                        title: 'Save as Draft',
                        text: 'Are you sure you want to save this equipment as draft?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, save it!',
                        cancelButtonText: 'No, cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#submitButton').click();
                        }
                    });
                } else {
                    // If submitting, confirm with user
                    Swal.fire({
                        title: 'Submit Equipment',
                        text: 'Are you sure you want to submit this equipment?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, submit it!',
                        cancelButtonText: 'No, cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#submitButton').click();
                        }
                    });
                }
            }

            function check_amount() {

                $('#draft').attr('disabled', true);
                $('#submitted').attr('disabled', true);
                $('.preloader').show();
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
            showToast("success", "{{ session('success') }}");
            @endif

            @if (session('error'))
            showToast("error", "{{ session('error') }}");
            @endif

            @if ($errors->any())
            showToast('error', "@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach");
            @endif

            function setApproval() {
            document.getElementById('approval_action_type').value = "approve";
            $('#myModalLabel178').text('Approve Equipment');

        }

        function setReject() {
            document.getElementById('approval_action_type').value = "reject";
            $('#myModalLabel178').text('Reject Equipment');

        }

        // Function to initiate amendment
        function initiateAmendment() {
            const actionUrl = "{{ route('equipment.amendment', ['id' => $equipment->id]) }}";

            fetch(actionUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            }).then(async response => {
                const data = await response.json();

                if (response.ok && data.status === 200) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    });

                    const url = new URL(window.location.href);
                    url.search = '';
                    url.searchParams.set('amendment', 1);
                    const amendmentUrl = url.toString();

                    setTimeout(() => {
                        window.location.replace(amendmentUrl);
                    }, 1500);
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'An unexpected error occurred.',
                        icon: 'error'
                    });
                }
            }).catch(error => {
                console.error('Amendment request failed:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Unable to process amendment. Please try again.',
                    icon: 'error'
                });
            });
        }

        // Amendment modal functions
        function closeModal(modalId) {
            $('#' + modalId).modal('hide');
        }

        function submitAmend(formId) {
            // Validate amend_remarks
            const amendRemarks = $('textarea[name="amend_remarks"]').val().trim();
            if (!amendRemarks || amendRemarks.length < 3) {
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Amendment remarks are required and must be at least 3 characters.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#7367F0'
                });
                return;
            }

            // Set action type to amendment
            $('#action_type').val('amendment');
            
            // Use AJAX for amendment submission
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Amendment Done Successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Redirect to index page
                        window.location.href = '{{ route("equipment.index") }}';
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Amendment failed. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Amendment error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }

        // File preview function for amendment attachment
        function addFiles(input, previewId) {
            const previewContainer = $('#' + previewId);
            previewContainer.empty();

            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB'; 
            }
        }

        function removeFile(button) {
            $(button).closest('.file-preview-item').parent().remove();
            // Clear the file input
            $('input[name="amend_attachment"]').val('');
        }



   
        function getPopupChecklistData(equipId, mainTypeId){
            if(equipId && mainTypeId){
                $.ajax({
                    url: "{{ route('equipment.popup-checklist-data') }}",
                    type: "post",
                    data: {
                        equipment_id: equipId,
                        id: mainTypeId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function (response) {


                        $("#viewCheckListPortion").empty();

                        if(response.success && response.data.length > 0){
                            // parse maintenanceChecklist for easier lookup
                            let selectedDetails = [];
                            if(response.maintenanceChecklist && response.maintenanceChecklist.length > 0){
                                response.maintenanceChecklist.forEach(mc => {
                                    try {
                                        let parsed = JSON.parse(mc.checklist_detail);
                                        if(parsed && parsed.checklist_detail_id){
                                            selectedDetails.push(parsed.checklist_detail_id.toString());
                                        }
                                    } catch(e){
                                        console.error("Invalid checklist_detail JSON", e);
                                    }
                                });
                            }



                            response.data.forEach(function(checklist){
                                let portionId = `portion_${checklist.id}`;
                                let tableRows = "";

                                if(checklist.details && checklist.details.length > 0){
                                    checklist.details.forEach(function(detail){
                                        let isChecked = selectedDetails.includes(detail.id.toString());

                                        // Only show selected/checked items
                                        if (isChecked) {
                                            tableRows += `
                                                <tr>
                                                    <td>
                                                        <div class="form-check form-check-primary custom-checkbox">
                                                            <input type="checkbox"
                                                                class="form-check-input"
                                                                value="${detail.id}" checked>
                                                        </div>
                                                    </td>
                                                    <td>${detail.name || ''}</td>
                                                    <td>${detail.description || ''}</td>
                                                    <td><span class="badge rounded-pill badge-light-info">${detail.data_type || ''}</span></td>
                                                </tr>
                                            `;
                                        }
                                    });

                                    // Only show this portion if it has selected items
                                    if (tableRows) {
                                        let portionHtml = `
                                            <div class="row checklist-portion mb-2" id="${portionId}">
                                                <div class="col-md-12">
                                                    <h5 class="mb-1">${checklist.name}</h5>
                                                    <div class="table-responsive">
                                                        <table class="mt-1 table table-striped border">
                                                            <thead>
                                                                <tr>
                                                                    <th width="40px">
                                                                        <div class="form-check form-check-primary custom-checkbox">
                                                                            <input type="checkbox" class="form-check-input select-all">
                                                                        </div>
                                                                    </th>
                                                                    <th>Name</th>
                                                                    <th>Description</th>
                                                                    <th>Type</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                ${tableRows}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        `;

                                        $("#viewCheckListPortion").append(portionHtml);
                                    }
                                }
                            });

                            // Check if any portions were added
                            if ($("#viewCheckListPortion").children().length === 0) {
                                $("#viewCheckListPortion").html(`<p class="text-center text-warning">No selected checklist items found</p>`);
                            }
                        } else {
                            $("#viewCheckListPortion").html(`<p class="text-center text-danger">No checklist data found</p>`);
                        }

                        $("#viewChecklist").modal("show");
                    }
                });
            }
        }

        function validateMaintenanceRowsWithMessages() {
            let allValid = true;
            let errorMessages = [];

            $('#maintenanceRows tr').each(function (index) {
                const row = $(this);
                const rowNumber = index + 1;
                let rowErrors = [];

                const type = row.find('.maintenance-type');
                const freq = row.find('.frequency');
                const date = row.find('.date-field');
                const time = row.find('.time-field');
                const bom = row.find('.maintenance-bom');
                const checklist = row.find('.selected-checklists');

                // Reset previous errors
                row.find('select, input').removeClass('is-invalid');
                row.removeClass('table-danger');

                // Validate each field
                if (type.length && !type.val()) {
                    type.addClass('is-invalid');
                    rowErrors.push('Maintenance Type');
                    allValid = false;
                }

                if (freq.length && !freq.val()) {
                    freq.addClass('is-invalid');
                    rowErrors.push('Frequency');
                    allValid = false;
                }

                if (date.length && !date.val()) {
                    date.addClass('is-invalid');
                    rowErrors.push('Start Date');
                    allValid = false;
                }

                if (time.length && !time.val()) {
                    time.addClass('is-invalid');
                    rowErrors.push('Time');
                    allValid = false;
                }

                if (bom.length && !bom.val()) {
                    bom.addClass('is-invalid');
                    rowErrors.push('Maintenance BOM');
                    allValid = false;
                }

                if (checklist.length && !checklist.val()) {
                    row.addClass('table-danger');
                    setTimeout(() => row.removeClass('table-danger'), 2000);
                    rowErrors.push('Checklist');
                    allValid = false;
                }

                // Add row-specific error message if there are errors
                if (rowErrors.length > 0) {
                    errorMessages.push(`<strong>Row ${rowNumber}:</strong> ${rowErrors.join(', ')}`);
                }
            });

            return {
                isValid: allValid,
                message: errorMessages.length > 0
                    ? `<div style="text-align: left;"><strong>Please complete the following fields before adding a new row:</strong><br><br>${errorMessages.join('<br>')}</div>`
                    : ''
            };
        }

        // Multiple file upload functionality
        function checkFileTypeandSize(event) {
            $('#preview').empty();
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
                handleFileUpload(event, '#preview');
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

        // Function to delete existing files
        function deleteExistingFile(filePath, element) {
            // Add the file to the deleted files list
            addToDeletedFiles(filePath);
            
            // Remove the preview element
            $(element).closest('.file-upload-preview').remove();
            
        }

        function addToDeletedFiles(fileToDelete) {
            var deletedFiles = $('#deleted_files').val();
            var deletedFilesArray = [];
            
            if (deletedFiles) {
                try {
                    deletedFilesArray = JSON.parse(deletedFiles);
                    if (!Array.isArray(deletedFilesArray)) {
                        deletedFilesArray = [];
                    }
                } catch (e) {
                    deletedFilesArray = [];
                }
            }
            
            // Add the file to deleted list if not already there
            if (!deletedFilesArray.includes(fileToDelete)) {
                deletedFilesArray.push(fileToDelete);
                $('#deleted_files').val(JSON.stringify(deletedFilesArray));
            }
        }

        // Reset checklist modal state
        function resetChecklistModal() {
            $('#checkListPortion').empty();
            selectedChecklistIds = [];
        }

        // Load existing selections for current row
        function loadExistingSelections(rowId) {
            if (!checklistRowRef) return;
            
            const selectedIds = checklistRowRef.find('.selected-checklists').val();
            if (selectedIds) {
                selectedChecklistIds = selectedIds.split(',').filter(Boolean);
            }
        }

        // Load all available checklists in the modal for edit mode (from show blade)
        function loadAllChecklistsForModal() {
            console.log('loadAllChecklistsForModal called'); // Debug log
            
            // Clear any existing portions first
            $("#checkListPortion").empty();

            // Get all checklists from the PHP data
            const allChecklists = @json($checklists);
            console.log('All checklists:', allChecklists); // Debug log

            if (allChecklists && allChecklists.length > 0) {
                console.log('Found', allChecklists.length, 'checklists'); // Debug log
                
                // Create portions for each checklist
                allChecklists.forEach(function(checklist, index) {
                    const portionId = `portion_${checklist.id}`;
                    console.log('Loading checklist:', checklist.name, 'with ID:', checklist.id); // Debug log

                    // Load checklist details for this checklist
                    loadChecklistDetailsForModal(checklist.id, checklist.name, portionId);
                });

                // Check after a delay if any portions were loaded
                setTimeout(() => {
                    if ($("#checkListPortion").children().length === 0) {
                        console.log('No portions loaded, showing loading message'); // Debug log
                        $("#checkListPortion").html('<p class="text-center text-muted">Loading checklists...</p>');
                    } else {
                        console.log('Portions loaded successfully:', $("#checkListPortion").children().length); // Debug log
                    }
                }, 500);
            } else {
                console.log('No checklists available'); // Debug log
                $("#checkListPortion").html('<p class="text-center text-muted">No checklists available</p>');
            }
        }

        // Load checklist details for modal display (from show blade)
        function loadChecklistDetailsForModal(checklistId, checklistName, portionId) {
            $.ajax({
                url: '{{ route("equipment.popup-checklist-data") }}',
                method: 'POST',
                data: {
                    equipment_id: currentRowId,
                    id: checklistId,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success && response.data) {
                        createChecklistPortionForModal(response.data, checklistId, checklistName, portionId);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading checklist details for modal:', error);
                }
            });
        }

        // Create checklist portion in modal (from show blade)
        function createChecklistPortionForModal(data, checklistId, checklistName, portionId) {
            // Store checklist data for this portion
            portionChecklistData[portionId] = {
                checklistId: checklistId,
                checklistName: checklistName
            };

            let tableRows = "";
            const checklists = data || [];
            
            // Get existing selected IDs to mark as checked (only from current row)
            let existingSelectedIds = [];
            if (checklistRowRef) {
                const selectedIdsString = checklistRowRef.find('.selected-checklists').val();
                if (selectedIdsString) {
                    existingSelectedIds = selectedIdsString.split(',').filter(Boolean);
                }
            }

            if (checklists.length > 0) {
                checklists.forEach(function (checklist) {
                    if (checklist.details && checklist.details.length > 0) {
                        checklist.details.forEach(function(detail) {
                            const isChecked = existingSelectedIds.includes(detail.id.toString()) ? 'checked' : '';
                            
                            tableRows += `
                                <tr>
                                    <td class="customernewsection-form">
                                        <div class="form-check form-check-primary custom-checkbox">
                                            <input type="checkbox" class="form-check-input" value="${detail.id}" ${isChecked}>
                                            <label class="form-check-label"></label>
                                        </div>
                                    </td>
                                    <td>${detail.name || ''}</td>
                                    <td>${detail.description || ''}</td>
                                    <td>
                                        <span class="badge rounded-pill badge-light-info">
                                            ${detail.type || ''}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                });

                // Only create portion if there are items
                if (tableRows.trim() !== '') {
                    // Create the portion HTML
                    const portionHtml = `
                        <div class="row checklist-portion mb-2" id="${portionId}">
                            <div class="col-md-12">
                                <h6 class="text-primary">${checklistName}</h6>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="40px">
                                                    <div class="form-check form-check-primary custom-checkbox">
                                                        <input type="checkbox" class="form-check-input select-all-${portionId}">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${tableRows}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                    $("#checkListPortion").append(portionHtml);

                    // Add select all functionality for this portion
                    $(document).off('change', `.select-all-${portionId}`).on('change', `.select-all-${portionId}`, function() {
                        const isChecked = $(this).is(':checked');
                        $(`#${portionId} tbody input[type="checkbox"]`).prop('checked', isChecked);
                    });
                }
            }
        }

        // Add new checklist portion function (same as create blade)
        function addPortion() {
            const portionId = 'portion_' + Date.now();
            const newPortionHtml = `
                <div class="row checklist-portion" id="${portionId}">
                    <div class="col-md-4">
                        <div class="mb-1">
                            <label class="form-label">Checklist <span class="text-danger">*</span></label>
                            <select class="form-select select2">
                                <option>Select</option>
                                @foreach($checklists as $checklist)
                                <option value="{{ $checklist->id }}">{{ $checklist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 mb-1">
                        <label class="form-label">&nbsp;</label><br />
                        <button type="button" class="btn btn-warning btn-sm search-checklist-btn"><i data-feather="search"></i> Search</button>
                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            Select Checklist
                            <div class="text-end" style="margin-top: -30px">
                                <a href="#" class="text-primary add-contactpeontxt mt-50 me-2" onclick="addPortion()"><i data-feather='plus'></i> Add Checklist</a>
                                <a href="#" class="text-danger remove-contactpeontxt mt-50" onclick="removePortion('${portionId}')"><i data-feather='minus'></i> Remove Checklist</a>
                            </div>
                            <table class="mt-1 table myrequesttablecbox table-striped po-order-detail">
                                <thead>
                                    <tr>
                                        <th width="40px" class="customernewsection-form">
                                            <div class="form-check form-check-primary custom-checkbox">
                                                <input type="checkbox" class="form-check-input select-all-checkbox">
                                                <label class="form-check-label"></label>
                                            </div>
                                        </th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody class="checklist-tbody">
                                   
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            $('#checkListPortion').append(newPortionHtml);
            feather.replace();
            updateAllDropdowns();
        }

        // Remove checklist portion function
        function removePortion(portionId) {
            const portion = $('#' + portionId);
            const selectedValue = portion.find('select').val();
            
            if (selectedValue && selectedValue !== 'Select') {
                const index = selectedChecklistIds.indexOf(selectedValue);
                if (index > -1) {
                    selectedChecklistIds.splice(index, 1);
                }
            }
            
            portion.remove();
            updateAllDropdowns();
        }

        // Update all dropdowns to hide selected checklists
        function updateAllDropdowns() {
            const allDropdowns = $('#checkListPortion select');
            
            allDropdowns.each(function() {
                const currentDropdown = $(this);
                const currentValue = currentDropdown.val();
                
                currentDropdown.find('option').show();
                
                selectedChecklistIds.forEach(function(selectedId) {
                    if (selectedId !== currentValue) {
                        currentDropdown.find(`option[value="${selectedId}"]`).hide();
                    }
                });
            });
        }

        // Load existing checklist data for current row
        function loadExistingChecklistData() {
            if (!checklistRowRef) return;
            
            const existingData = checklistRowRef.find('.selected-checklists').val();
            if (existingData) {
                try {
                    const selectedIds = JSON.parse(existingData);
                    selectedIds.forEach(function(checklistId, index) {
                        if (index > 0) {
                            addPortion(); // Add new portion for each additional checklist
                        }
                        
                        const portion = $('#checkListPortion .checklist-portion').eq(index);
                        const dropdown = portion.find('select');
                        dropdown.val(checklistId);
                        
                        // Load checklist details for this portion
                        loadChecklistDetailsForPortion(checklistId, portion);
                    });
                    
                    selectedChecklistIds = [...selectedIds];
                    updateAllDropdowns();
                } catch (e) {
                    console.error('Error parsing existing checklist data:', e);
                }
            }
        }

        // Load checklist details for a specific portion
        function loadChecklistDetailsForPortion(checklistId, portion) {
            const tbody = portion.find('.checklist-tbody');
            tbody.empty();
            
            $.ajax({
                url: '{{ route("equipment.popup-checklist-data") }}',
                method: 'POST',
                data: {
                    equipment_id: currentRowId,
                    id: checklistId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const checklists = response.data;
                        checklists.forEach(function(checklist) {
                            if (checklist.details && checklist.details.length > 0) {
                                checklist.details.forEach(function(detail) {
                                    const row = `
                                        <tr class="trail-bal-tabl-none">
                                            <td class="customernewsection-form">
                                                <div class="form-check form-check-primary custom-checkbox">
                                                    <input type="checkbox" value="${detail.id}" class="form-check-input checklist-item-checkbox" data-name="${detail.name}" data-type="${detail.type}">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                            <td>${detail.name}</td>
                                            <td>${detail.description || ''}</td>
                                            <td><span class="badge rounded-pill badge-light-secondary">${detail.type}</span></td>
                                        </tr>
                                    `;
                                    tbody.append(row);
                                });
                            }
                        });
                    }
                },
                error: function() {
                    console.error('Error loading checklist details');
                }
            });
        }

        // Handle search button click
        $(document).on('click', '.search-checklist-btn', function() {
            const portion = $(this).closest('.checklist-portion');
            const dropdown = portion.find('select');
            const checklistId = dropdown.val();
            
            if (!checklistId || checklistId === 'Select') {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select a checklist first!'
                });
                return;
            }
            
            // Add to selected IDs if not already present
            if (!selectedChecklistIds.includes(checklistId)) {
                selectedChecklistIds.push(checklistId);
                updateAllDropdowns();
            }
            
            loadChecklistDetailsForPortion(checklistId, portion);
        });

        // Handle select all checkbox
        $(document).on('change', '.select-all-checkbox', function() {
            const portion = $(this).closest('.checklist-portion');
            const isChecked = $(this).is(':checked');
            portion.find('.checklist-item-checkbox').prop('checked', isChecked);
        });

        // Handle select-all checkbox for view modal
        $(document).on('change', '.select-all', function() {
            const table = $(this).closest('table');
            const isChecked = $(this).is(':checked');
            table.find('tbody input[type="checkbox"]').prop('checked', isChecked);
        });

        // Handle submit button (updated for show blade approach)
        $('#submitChecklistBtn').on('click', function() {
            const selectedChecklists = [];
            
            // Collect all checked items from all portions
            $('#checkListPortion input[type="checkbox"]:checked').each(function() {
                const row = $(this).closest('tr');
                selectedChecklists.push({
                    id: $(this).val(),
                    name: row.find('td:nth-child(2)').text(),
                    type: row.find('td:nth-child(4)').text().trim()
                });
            });
            
            if (selectedChecklists.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please select at least one checklist item!'
                });
                return;
            }
            
            updateMaintenanceRowWithChecklists(selectedChecklists);
            $('#checklist').modal('hide');
        });

        // Update maintenance row with selected checklists
        function updateMaintenanceRowWithChecklists(selectedChecklists) {
            if (!checklistRowRef) return;
            
            let badgesHtml = '';
            const selectedIds = [];
            
            selectedChecklists.forEach(function(checklist) {
                badgesHtml += `<span class="badge rounded-pill badge-light-info me-50 mb-50">${checklist.name}</span>`;
                selectedIds.push(checklist.id);
            });
            
            const checklistCell = checklistRowRef.find('.checklist-cell');
            checklistCell.find('.checklist-badges').html(badgesHtml);
            checklistCell.find('.selected-checklists').val(JSON.stringify(selectedIds));
            
            // Store checklist data for form submission
            const checklistData = checklistCell.find('.existing-checklist-data');
            checklistData.html('');
            selectedChecklists.forEach(function(checklist, index) {
                checklistData.append(`
                    <input type="hidden" name="maintenance_details[${currentRowId}][checklists][${index}][id]" value="${checklist.id}">
                    <input type="hidden" name="maintenance_details[${currentRowId}][checklists][${index}][name]" value="${checklist.name}">
                    <input type="hidden" name="maintenance_details[${currentRowId}][checklists][${index}][type]" value="${checklist.type}">
                `);
            });
        }

        // YOM Validation (up to 50 years old)
        function validateYOM() {
            const yomInput = document.getElementById('yom');
            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 50; // 50 years old
            const maxYear = currentYear; // Current year
            
            // Only validate on blur (when user finishes typing)
            yomInput.addEventListener('blur', function() {
                const yomValue = parseInt(this.value);
                
                if (yomValue && (yomValue < minYear || yomValue > maxYear)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid YOM!',
                        text: `YOM must be between ${minYear} and ${maxYear} (up to 50 years old)`,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                    this.value = '';
                    this.focus();
                }
            });
        }

        // Purchase Cost Validation (cannot be 00000 or zero)
        function validatePurchaseCost() {
            const costInput = document.getElementById('purchase_cost');
            
            costInput.addEventListener('input', function() {
                const costValue = parseFloat(this.value);
                
                // Check for zero or invalid values
                if (this.value === '00000' || this.value === '0' || costValue <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Purchase Cost!',
                        text: 'Purchase cost cannot be zero or 00000. Please enter a valid amount.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                    this.value = '';
                    this.focus();
                }
            });
            
            // Prevent typing zeros at the beginning
            costInput.addEventListener('keydown', function(e) {
                if (this.value === '' && e.key === '0') {
                    e.preventDefault();
                }
            });
        }

        // Prevent form submission on Enter key
        function preventEnterSubmission() {
            $('form').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    // Show validation message
                    Swal.fire({
                        icon: 'warning',
                        title: 'Form Submission Prevented!',
                        text: 'Please use the Submit button to submit the form after completing all required fields.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    });
                    
                    return false;
                }
            });
        }

        // Initialize validations
        validateYOM();
        validatePurchaseCost();
        preventEnterSubmission();

        // Make functions globally accessible
        window.addPortion = addPortion;
        window.removePortion = removePortion;

    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
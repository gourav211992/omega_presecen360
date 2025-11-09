@extends('layouts.app')

@section('content')

    <!-- BEGIN: Content-->
    <form method="POST" data-completionFunction = "disableHeader" class="ajax-input-form sales_module_form" action = "{{route('sale.return.store')}}" data-redirect="{{ route('sale.return.index', ['type' => request() -> type]) }}" id = "sale_invoice_form" enctype='multipart/form-data'>

    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper container-xxl p-0">
            <div class="content-header pocreate-sticky">
				<div class="row">
                    @php
                        $title = $typeName;
                    @endphp
                    @include('layouts.partials.breadcrumb-add-edit', [
                        'title' => $title,
                        'menu' => 'Home',
                        'menu_url' => url('home'),
                        'sub_menu' => 'Add New'
                    ])
                    <input type = "hidden" value = "draft" name = "document_status" id = "document_status" />
					<div class="content-header-right text-sm-end col-md-6 mb-50 mb-sm-0">
						<div class="form-group breadcrumb-right" id="buttonsDiv">
                        @if(!isset(request() -> revisionNumber))
                        <button type = "button" onclick="javascript: history.go(-1)" class="btn btn-secondary btn-sm mb-50 mb-sm-0"><i data-feather="arrow-left-circle"></i> Back</button>

                            @if (isset($order))
                            @if( $buttons['print'])
                                <button class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                    Print  <i class="fa-regular fa-circle-down"></i>
                                </button>
                                @if(isset($einvoice) && !$einvoice->ewb_no && $order -> total_amount > 50000 && false)
                                    <a type="button" class="btn btn-primary btn-sm" id="eWayBillBtn" href="#" onclick = "generateEwayBill();">
                                        <i data-feather="check-circle"></i> Generate Eway Bill
                                    </a>
                                @endif
                                <button type = "button" data-target = "#sendMail" onclick = "sendMailTo();" data-toggle = "modal" class="btn btn-primary btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><i data-feather="mail"></i> E-Mail</button>
                                @endif
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @php
                                        $options = [
                                            'Sales Return' => 'Sales Return',
                                            'Credit Note' => 'Credit Note',
                                        ];
                                    @endphp
                                    @foreach ($options as $key => $label)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('sale.return.generate-pdf', ['id' => $order->id ?? 0, 'pattern' => $key ?? 'Sales Return']) }}" target="_blank">{{ $label }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($buttons['draft'])
                                    <button type="button" onclick = "submitForm('draft');" name="action" value="draft" class="btn btn-outline-primary btn-sm mb-50 mb-sm-0" id="save-draft-button" name="action" value="draft"><i data-feather='save'></i> Save as Draft</button>
                                @endif
                                @if($buttons['submit'])
                                    <button type="button" onclick = "submitForm('submitted');" name="action" value="submitted" class="btn btn-primary btn-sm" id="submit-button" name="action" value="submitted"><i data-feather="check-circle"></i> Submit</button>
                                @endif
                                @if($buttons['approve'])
                                    <button type="button" id="reject-button" data-bs-toggle="modal" data-bs-target="#approveModal" onclick = "setReject();" class="btn btn-danger btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg> Reject</button>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal" onclick = "setApproval();" ><i data-feather="check-circle"></i> Approve</button>
                                @endif
                                @if($buttons['amend'])
                                    <button id = "amendShowButton" type="button" onclick = "openModal('amendmentconfirm')" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='edit'></i> Amendment</button>
                                @endif
                                @if($buttons['post'])
                                <button onclick = "onPostVoucherOpen();" id="postButton" type = "button" class="btn btn-warning btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Post</button>
                                @endif
                                @if($buttons['voucher'])
                                <button type = "button" onclick = "onPostVoucherOpen('posted');" class="btn btn-dark btn-sm mb-50 mb-sm-0 waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> Voucher</button>
                                @endif
                                @if($buttons['revoke'])
                                    <button id = "revokeButton" type="button" onclick = "revokeDocument();" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather='rotate-ccw'></i> Revoke</button>
                                @endif
                                @if($enableEinvoice && !$einvoice && in_array($order -> document_status, ['approved', 'approval_not_required', 'posted']) && $order -> einvoice_check())
                                <a type="button" class="btn btn-primary btn-sm" id="eEInvoiceBtn" onclick = "generateEInvoice('{{$order -> id}}')">
                                    <i data-feather="check-circle"></i> Generate E-CRN
                                </a>

                                @endif

                            @else
                                <button type = "button" name="action" value="draft" id = "save-draft-button" onclick = "submitForm('draft');" class="btn btn-outline-primary btn-sm mb-50 mb-sm-0"><i data-feather='save'></i> Save as Draft</button>
                                <button type = "button" name="action" value="submitted"  id = "submit-button" onclick = "submitForm('submitted');" class="btn btn-primary btn-sm mb-50 mb-sm-0"><i data-feather="check-circle"></i> Submit</button>
                            @endif
                            @endif
						</div>
					</div>
				</div>
			</div>
            <div class="content-body">
				<section id="basic-datatable">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								 <div class="card-body customernewsection-form" id ="main_so_form">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="newheader border-bottom mb-2 pb-25 d-flex flex-wrap justify-content-between">
                                                    <div>
                                                        <h4 class="card-title text-theme">Basic Information</h4>
                                                        <p class="card-text">Fill the details</p>
                                                    </div>
                                                </div>
                                            </div>
                                            @if (isset($order) && isset($docStatusClass))
                                            <div class="col-md-6 text-sm-end">
                                                <span class="badge rounded-pill badge-light-{{$order->display_status === 'Posted' ? 'info' : 'secondary'}} forminnerstatus">
                                                    <span class = "text-dark" >Status</span> : <span class="{{$docStatusClass}}">{{$display_status}}</span>
                                                </span>
                                            </div>

                                            @endif
                                            <div class="col-md-8">
                                                <input type = "hidden" name = "type" id = "type_hidden_input"></input>
                                           @if (isset($order))
                                                <input type = "hidden" value = "{{$order -> id}}" name = "sale_return_id" id="sale_return_id_input"></input>
                                            @endif
                                            <div class="row align-items-center d-none mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <select class="form-select disable_on_edit" disabled id = "service_id_input" {{isset($order) ? 'disabled' : ''}} onchange = "onSeriesChange(this);">
                                                                @foreach ($services as $currentService)
                                                                    <option value = "{{$currentService -> alias}}" {{isset($selectedService) ? ($selectedService == $currentService -> alias ? 'selected' : '') : ''}}>{{$currentService -> name}}</option>
                                                                @endforeach
                                                            </select>
                                                            <input type = "hidden" value = "yes" id = "invoice_to_follow_input" />
                                                        </div>

                                                    </div>


                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Series <span class="text-danger">*</span></label>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <select class="form-select disable_on_edit" onChange = "getDocNumberByBookId(this);" name = "book_id" id = "series_id_input">
                                                                @foreach ($series as $currentSeries)
                                                                    <option value = "{{$currentSeries -> id}}" {{isset($order) ? ($order -> book_id == $currentSeries -> id ? 'selected' : '') : ''}}>{{$currentSeries -> book_code}}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <input type = "hidden" name = "book_code" id = "book_code_input" value = "{{isset($order) ? $order -> book_code : ''}}"></input>
                                                     </div>

                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Document No <span class="text-danger">*</span></label>
                                                        </div>

                                                        <div class="col-md-5">
                                                            <input type="text" value = "{{isset($order) ? $order -> document_number : ''}}" class="form-control disable_on_edit" readonly id = "order_no_input" name = "document_no">
                                                        </div>
                                                     </div>

                                                     <div class="row align-items-center mb-1">
                                                         <div class="col-md-3">
                                                             <label class="form-label">Document Date <span class="text-danger">*</span></label>
                                                         </div>

                                                         <div class="col-md-5">
                                                             <input type="date" value = "{{isset($order) ? $order -> document_date : Carbon\Carbon::now() -> format('Y-m-d')}}" class="form-control" name = "document_date" id = "order_date_input" oninput = "onDocDateChange();">
                                                         </div>
                                                     </div>

                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Location<span class="text-danger">*</span></label>
                                                        </div>

                                                        <div class="col-md-5">
                                                            <select class="disable_on_edit form-select" {{isset($order) && $order -> store_id ? 'readonly' : ''}} name = "store_id" id = "store_id_input">
                                                                @if(isset($order) && $order->store_id)
                                                                    <option  display-address = "{{$order -> store -> address ?-> display_address}}" value = "{{$order -> store_id}}">{{$order -> store -> store_name}}</option>
                                                                @else
                                                                    @foreach ($stores as $store)
                                                                        <option  display-address = "{{$store -> address ?-> display_address}}" value = "{{$store -> id}}" {{isset($order) ? ($order -> store_id == $store -> id ? 'selected' : '') : ''}}>{{$store -> store_name}}</option>
                                                                    @endforeach
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row align-items-center mb-1 sub_store">
                                                        <div class="col-md-3"> 
                                                            <label class="form-label">Store<span class="text-danger">*</span></label>  
                                                        </div>  
                                                        <div class="col-md-5">  
                                                            <select class="form-select subStoreSelect disable_on_edit" name="sub_store_id" id="sub_store_id_input">
                                                                @if(isset($order) && $order->sub_store_id)
                                                                    <option value="{{ $order->sub_store_id }}" selected> {{ $order->sub_store->name }}</option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row align-items-center mb-1 d-none">
                                                        <div class="col-md-3"> 
                                                            <label class="form-label">Type<span class="text-danger">*</span></label>  
                                                        </div>  
                                                        <div class="col-md-5">  
                                                            <select class="form-select subStoreSelect disable_on_edit" name="type_input" id="type_input">
                                                                <option value="0" {{ isset($order) && $order->return_type == 0 ? "selected" : '' }}>Reversal</option>
                                                                <option value="1" {{ isset($order) && $order->return_type == 1 ? "selected" : '' }}>Customer Return</option>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    {{--<div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Department<span class="text-danger">*</span></label>
                                                        </div>

                                                        <div class="col-md-5">
                                                            <select class="form-select" name = "department_id" id = "department_id_input">
                                                                @foreach ($departments as $department)
                                                                    <option value = "{{$department -> id}}" {{isset($order) ? ($order -> department_id == $department -> id ? 'selected' : '') : ''}}>{{$department -> name}}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                     </>
                                                    --}}
                                                    <div class="row align-items-center mb-1">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Customer PO No </label>
                                                        </div>

                                                        <div class="col-md-5">
                                                            <input type="text" value = "{{isset($order) ? $order -> reference_number : ''}}" name = "reference_no" class="form-control" readonly id = "reference_no_input">
                                                            <input type="hidden" value = "{{ isset($order) ? $order -> reference_id : ''}}" name = "reference_id" id = "reference_id_input" >
                                                            <input type="hidden" value = "{{ isset($order) ? $order -> reference_doc_type : ''}}" name = "reference_doc_type" id = "reference_doc_type" >
                                                        </div>
                                                     </div>
                                                     @if($einvoice && $enableEinvoice)
                                                            <div class="row align-items-center mb-1 lease-hidden">
                                                                <div class="col-md-3">
                                                                    <label class="form-label">E-CRN Ref.</label>
                                                                </div>

                                                                <div class="col-md-5">
                                                                <label class="form-label">{{ $einvoice->irn_number }}</label>
                                                                </div>
                                                            </div>
                                                            <div class="row align-items-center mb-1 lease-hidden">
                                                                <div class="col-md-3">
                                                                    <label class="form-label">Acknowledgement No.</label>
                                                                </div>

                                                                <div class="col-md-5">
                                                                <label class="form-label">{{ $einvoice->ack_no }}</label>
                                                                </div>
                                                            </div>
                                                        @endif

                                                    @if (!isset($order))

                                                    <div class="row align-items-center mb-1" id = "selection_section" style = "display:none;">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Reference From</label>
                                                        </div>
                                                        <div class="col-md-9 action-button" >
                                                                <button id = "sales_invoice_selection" onclick = "openHeaderPullModal('{{App\Helpers\ConstantHelper::SI_SERVICE_ALIAS}}');" type = "button" id = "select_inv_button"  data-bs-toggle="modal" data-bs-target="#resceduleinv" class="btn btn-outline-primary btn-sm mb-0"><i data-feather="plus-square"></i>
                                                                    Invoice
                                                                </button>
                                                                    <button id = "delivery_note_selection" onclick = "openHeaderPullModal('{{App\Helpers\ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS}}');" type = "button" id = "select_dn_button"  data-bs-toggle="modal" data-bs-target="#rescedulednote" class="btn btn-outline-primary btn-sm mb-0"><i data-feather="plus-square"></i>
                                                                        DNote
                                                                    </button>
                                                                    <button id = "dnote_inv_selection" onclick = "openHeaderPullModal('{{App\Helpers\ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS}}');" type = "button" id = "select_si_dn_button"  data-bs-toggle="modal" data-bs-target="#rescedulesinv" class="btn btn-outline-primary btn-sm mb-0"><i data-feather="plus-square"></i>
                                                                        DN-cum-Inv
                                                                    </button>
                                                            </div>
                                                        </div>
                                                        
                                                    </div>
                                                    @endif
                                        </div>
                                        @if(isset($order) && ($order -> document_status !== "draft"))
                            @if((isset($approvalHistory) && count($approvalHistory) > 0) || isset($revision_number))
                           <div class="col-md-4">
                               <div class="step-custhomapp bg-light p-1 customerapptimelines customerapptimelinesapprovalpo">
                                   <h5 class="mb-2 text-dark border-bottom pb-50 d-flex align-items-center justify-content-between">
                                       <strong><i data-feather="arrow-right-circle"></i> Approval History</strong>
                                       @if(!isset(request() -> revisionNumber))
                                       <strong class="badge rounded-pill badge-light-secondary amendmentselect">Rev. No.
                                           <select class="form-select" id="revisionNumber">
                                            @for($i=$revision_number; $i >= 0; $i--)
                                               <option value="{{$i}}" {{request('revisionNumber',$order->revision_number) == $i ? 'selected' : ''}}>{{$i}}</option>
                                            @endfor
                                           </select>
                                       </strong>
                                       @else
                                       <strong class="badge rounded-pill badge-light-secondary amendmentselect">Rev. No. {{request('revisionNumber',$order->revision_number)}}
                                       </strong>
                                       @endif

                                   </h5>
                                   <ul class="timeline ms-50 newdashtimline ">
                                        @foreach($approvalHistory as $approvalHist)
                                        <li class="timeline-item">
                                           <span class="timeline-point timeline-point-indicator"></span>
                                           <div class="timeline-event">
                                               <div class="d-flex justify-content-between flex-sm-row flex-column mb-sm-0 mb-1">
                                                   <h6>{{ucfirst($approvalHist->name ?? $approvalHist?->user?->name ?? 'NA')}}</h6>
                                                   @if($approvalHist->approval_type == 'approve')
                                                   <span class="badge rounded-pill badge-light-success">{{ucfirst($approvalHist->approval_type)}}</span>
                                                   @elseif($approvalHist->approval_type == 'submit')
                                                   <span class="badge rounded-pill badge-light-primary">{{ucfirst($approvalHist->approval_type)}}</span>
                                                   @elseif($approvalHist->approval_type == 'reject')
                                                   <span class="badge rounded-pill badge-light-danger">{{ucfirst($approvalHist->approval_type)}}</span>
                                                   @elseif($approvalHist->approval_type == 'posted')
                                                   <span class="badge rounded-pill badge-light-info">{{ucfirst($approvalHist->approval_type)}}</span>
                                                   @else
                                                   <span class="badge rounded-pill badge-light-danger">{{ucfirst($approvalHist->approval_type)}}</span>
                                                   @endif
                                               </div>
                                                @if($approvalHist->created_at)
                                                    <h6>
                                                        {{ \Carbon\Carbon::parse($approvalHist->created_at)->timezone('Asia/Kolkata')->format('d/m/Y | h.iA') }}
                                                    </h6>
                                                @endif

                                                @if($approvalHist->remarks)
                                                <p>{!! $approvalHist->remarks !!}</p>
                                                @endif
                                                @if ($approvalHist -> media && count($approvalHist -> media) > 0)
                                                    @foreach ($approvalHist -> media as $mediaFile)
                                                        <p><a href="{{$mediaFile -> file_url}}" target = "_blank"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg></a></p>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </li>
                                       @endforeach
                                   </ul>
                               </div>
                           </div>
                           @endif
                           @endif
                                        </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card quation-card">
                                        <div class="card-header newheader">
                                            <div>
                                                <h4 class="card-title">Customer Details</h4>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">

                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Customer <span class="text-danger">*</span></label>
                                                    <input type="text" id = "customer_code_input" disabled placeholder="Select" class="form-control mw-100 ledgerselecct ui-autocomplete-input disable_on_edit" autocomplete="off" value = "{{isset($order) ? $order -> cust ?-> company_name : ''}}" onblur = "onChangeCustomer('customer_code_input', true)" >
                                                    <input type = "hidden" name = "customer_id" id = "customer_id_input" value = "{{isset($order) ? $order -> customer_id : ''}}"></input>
                                                    <input type = "hidden" name = "customer_code" id = "customer_code_input_hidden" value = "{{isset($order) ? $order -> customer_code : ''}}"></input>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Phone No.<span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control ledgerselecct ui-autocomplete-input" autocomplete="off" id = "customer_phone_no_input" name = "customer_phone_no" value = "{{isset($order) ? $order -> cust -> phone : ''}}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Email<span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control ledgerselecct ui-autocomplete-input" autocomplete="off"  id = "customer_email_input" name = "customer_email" value = "{{isset($order) ? $order -> customer-> email : ''}}" />
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Consignee Name</label>
                                                        <input type="text" class="form-control ledgerselecct ui-autocomplete-input" autocomplete="off"  id = "consignee_name_input" name = "consignee_name" value = "{{isset($order) ? $order -> consignee_name : ''}}" onblur = "onChangeConsignee('consignee_name_input', true)"  />
                                                        <input type = "hidden" name = "consignee_id" id = "consignee_id_input" value = "{{isset($order) ? $order -> consignee_id : ''}}"></input>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">GSTIN No.</label>
                                                        <input type="text" class="form-control ledgerselecct ui-autocomplete-input" autocomplete="off"  id = "customer_gstin_input" name = "customer_gstin" value = "{{isset($order) ? $order ?-> cust -> compliances -> gstin_no : ''}}" />
                                                    </div>
                                                </div>



                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                                                            <select class="form-select disable_on_edit" id = "currency_dropdown" name = "currency_id" readonly>
                                                            @if (isset($order) && isset($order -> customer))
                                                                <option value = "{{$order -> cust -> currency_id}}">{{$order -> cust ?-> currency ?-> name}}</option>
                                                            @else
                                                                <option value = "">Select</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <input type = "hidden" name = "currency_code" value = "{{isset($order) ? $order -> currency_code : ''}}" id = "currency_code_input"></input>
                                                </div>


                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Payment Terms <span class="text-danger">*</span></label>
                                                        <select class="form-select disable_on_edit" id = "payment_terms_dropdown" name = "payment_terms_id" readonly>
                                                            @if (isset($order) && isset($order -> customer))
                                                                <option value = "{{$order -> cust -> payment_terms_id}}">{{$order -> cust ?-> payment_terms ?-> name}}</option>
                                                            @else
                                                                <option value = "">Select</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <input type = "hidden" name = "payment_terms_code" value = "{{isset($order) ? $order -> payment_terms_code : ''}}" id = "payment_terms_code_input"></input>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">Credit Days <span class="text-danger"></span></label>
                                                        <input type="number" value = "{{isset($order) ? $order -> credit_days : (isset($order->reference->credit_days) ? $order->reference->credit_days : 0)}}" name = "credit_days" class="form-control disable_on_edit" id = "credit_days_input" readonly>
                                                    </div>
                                                </div>

                                            </div>

                                                </div>


                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="customer-billing-section h-100">
                                                        <p>Billing Address&nbsp;<span class="text-danger">*</span>
                                                        @if (!isset($order))
                                                        <a href="javascript:;" id="billAddressEditBtn" class="float-end"><i data-feather='edit-3'></i></a>
                                                        @endif
                                                    </p>
                                                        <div class="bilnbody">
                                                            <div class="genertedvariables genertedvariablesnone">
                                                                <div class="mrnaddedd-prim" id = "current_billing_address">{{isset($order) ? $order -> billing_address_details ?-> display_address : ''}}</div>
                                                                <input type = "hidden" id = "current_billing_address_id"></input>
                                                                <input type = "hidden" id = "current_billing_country_id" name = "billing_country_id" value = "{{isset($order) && isset($order -> billing_address_details) ? $order -> billing_address_details -> country_id : ''}}"></input>
                                                                <input type = "hidden" id = "current_billing_state_id" name = "billing_state_id" value = "{{isset($order) && isset($order -> billing_address_details) ? $order -> billing_address_details -> state_id : ''}}"></input>
                                                                <input type="hidden" name="new_billing_country_id" id="new_billing_country_id" value="">
                                                                <input type="hidden" name="new_billing_state_id" id="new_billing_state_id" value="">
                                                                <input type="hidden" name="new_billing_city_id" id="new_billing_city_id" value="">
                                                                <input type="hidden" name="new_billing_address" id="new_billing_address" value="">
                                                                <input type="hidden" name="new_billing_type" id="new_billing_type" value="">
                                                                <input type="hidden" name="new_billing_pincode" id="new_billing_pincode" value="">
                                                                <input type="hidden" name="new_billing_phone" id="new_billing_phone" value="">
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="customer-billing-section">
                                                        <p>Shipping Address&nbsp;<span class="text-danger">*</span><span id = "same_checkbox_as_billing" style = "margin-left:120px; font-weight:100;"></span>
                                                            <a href="javascript:;" id="shipAddressEditBtn" data-bs-toggle="modal" class="float-end"><i data-feather='edit-3'></i></a>
                                                        </p>
                                                        <div class="bilnbody">

                                                            <div class="genertedvariables genertedvariablesnone">
                                                                <div class="mrnaddedd-prim" id = "current_shipping_address">{{isset($order) ? $order -> shipping_address_details ?-> display_address : ''}}</div>
                                                                <input type = "hidden" id = "current_shipping_address_id"></input>
                                                                <input type = "hidden" id = "current_shipping_country_id" name = "shipping_country_id" value = "{{isset($order) && isset($order -> shipping_address_details) ? $order -> shipping_address_details -> country_id : ''}}"></input>
                                                                <input type = "hidden" id = "current_shipping_state_id" name = "shipping_state_id" value = "{{isset($order) && isset($order -> shipping_address_details) ? $order -> shipping_address_details -> state_id : ''}}"></input>
                                                                <input type="hidden" name="new_shipping_country_id" id="new_shipping_country_id" value="">
                                                                <input type="hidden" name="new_shipping_state_id" id="new_shipping_state_id" value="">
                                                                <input type="hidden" name="new_shipping_city_id" id="new_shipping_city_id" value="">
                                                                <input type="hidden" name="new_shipping_address" id="new_shipping_address" value="">
                                                                <input type="hidden" name="new_shipping_type" id="new_shipping_type" value="">
                                                                <input type="hidden" name="new_shipping_pincode" id="new_shipping_pincode" value="">
                                                                <input type="hidden" name="new_shipping_phone" id="new_shipping_phone" value="">
                                                            </div>
                                                        </div>
                                                    </div>
                                            </div>

                                            <div class="col-md-4">
                                                    <div class="customer-billing-section">
                                                        <p>Dispatched From&nbsp;<span class="text-danger">*</span>
                                                        </p>
                                                        <div class="bilnbody">
                                                            <div class="genertedvariables genertedvariablesnone">
                                                                <div class="mrnaddedd-prim" id = "current_pickup_address">{{isset($order) ? $order -> location_address_details ?-> display_address : ''}}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                        

                            <div class="col-md-12"  id = "general_information_tab">
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
                                                            <label class="form-label">Transporter Name<span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control {{isset($editTransporterFields) && $editTransporterFields ? 'cannot_disable' : ''}}" id = "transporter_name_input" name = "transporter_name" value = "{{isset($order) ? $order -> transporter_name : ''}}" />
                                                        </div>
                                                </div>

                                                <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">Transport Mode<span class="text-danger">*</span></label>
                                                            <select class="form-control {{isset($editTransporterFields) && $editTransporterFields ? 'cannot_disable' : ''}}" id = "transporter_mode_input" name = "transporter_mode" value = "{{isset($order) ? $order -> vehicle_no : ''}}" >
                                                                @foreach($transportationModes as $transportationMode)
                                                                    <option {{isset($order) && $order -> eway_bill_master_id == $transportationMode -> id ? 'selected' : ''}} value="{{$transportationMode->id}}">
                                                                        {{ucfirst($transportationMode->description)}}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                <div class="col-md-3">
                                                    <div class="mb-1">
                                                        <label class="form-label">
                                                                Vehicle No.
                                                                <span class="text-danger">*</span>
                                                                <i class="ml-2 fas fa-info-circle text-primary"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-html="true"
                                                                title="Format:<br>[A-Z]{2} – 2 uppercase letters (e.g., 'MH')<br>[0-9]{2} – 2 digits (e.g., '12')<br>[A-Z]{0,3} – 0 to 3 uppercase letters (e.g., 'AB', 'ABZ')<br>[0-9]{4} – 4 digits (e.g., '1234')"></i>
                                                            </label>
                                                        <input type="text" class="form-control {{isset($editTransporterFields) && $editTransporterFields ? 'cannot_disable' : ''}}" id = "vehicle_no_input" name = "vehicle_no" value = "{{isset($order) ? $order -> vehicle_no : ''}}" />
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <label class="form-label">E-Way Bill No.</label>
                                                            <input type="text" class="form-control" id = "eway_bill_no_input" disabled value = "{{isset($order) && isset($einvoice) ? $einvoice -> ewb_no : ''}}" />
                                                        </div>
                                                </div>
										</div>
									</div>

								</div>
                                 <div class="col-md-12 {{(isset($order) && count($order -> dynamic_fields)) > 0 ? '' : 'd-none'}}" id = "dynamic_fields_section">
                                        @if (isset($dynamicFieldsUi))
                                            {!! $dynamicFieldsUi !!}
                                        @endif
                                    </div>
                            </div>

                            <div class="card">
								 <div class="card-body customernewsection-form">
                                            <div class="border-bottom mb-2 pb-25">
                                                     <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="newheader ">
                                                                <h4 class="card-title text-theme">Item Detail</h4>
                                                                <p class="card-text">Fill the details</p>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 text-sm-end" id="add_delete_item_section">
                                                            <a href="#" onclick = "deleteItemRows();" class="btn btn-sm btn-outline-danger me-50">
                                                                <i data-feather="x-circle"></i> Delete</a>
                                                            <a href="#" onclick = "addItemRow();" id = "add_item_section" class="btn btn-sm btn-outline-primary">
                                                                <i data-feather="plus"></i> Add Item</a>
                                                         </div>
                                                    </div>
                                             </div>

											<div class="row">

                                                 <div class="col-md-12">


                                                 <div class="table-responsive pomrnheadtffotsticky">
                                                         <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
                                                            <thead>
                                                                 <tr>
                                                                    <th class="customernewsection-form">
                                                                        <div class="form-check form-check-primary custom-checkbox">
                                                                            <input type="checkbox" class="form-check-input" id="select_all_items_checkbox" oninput = "checkOrRecheckAllItems(this);">
                                                                            <label class="form-check-label" for="select_all_items_checkbox"></label>
                                                                        </div>
                                                                    </th>
                                                                    <th width="150px">Item Code</th>
                                                                    <th width="240px">Item Name</th>
                                                                    <th>Attributes</th>
                                                                    <th>UOM</th>
                                                                    <!-- <th>Location</th> -->
                                                                    <th class = "numeric-alignment">Qty</th>
                                                                    <th class = "numeric-alignment">Rate</th>
                                                                    <th class = "numeric-alignment">Value</th>
                                                                    <th class = "numeric-alignment">Discount</th>
                                                                    <th class = "numeric-alignment" width = "150px">Total</th>
                                                                    <th width="50px">Action</th>
                                                                  </tr>
                                                                </thead>
                                                                <tbody class="mrntableselectexcel" id = "item_header">
                                                                @if (isset($order))
                                                                @foreach ($order -> items as $orderItemIndex => $orderItem)

                                                                <tr id = "item_row_{{$orderItemIndex}}" class = "item_header_rows" onclick = "onItemClick('{{$orderItemIndex}}');" data-detail-id = "{{$orderItem -> id}}" data-id = "{{$orderItem -> id}}">
                                                                    <input type = 'hidden' name = "si_id[]" value = "{{$orderItem -> header -> id}}">


                                                                        <input type = 'hidden' name = "si_item_id[]" value = "{{$orderItem -> id}}" {{$orderItem -> is_editable ? '' : 'readonly'}}>
                                                                         <td class="customernewsection-form">
                                                                            <div class="form-check form-check-primary custom-checkbox">
                                                                                <input type="checkbox" class="form-check-input item_row_checks" id="item_checkbox_{{$orderItemIndex}}" del-index = "{{$orderItemIndex}}">
                                                                                <label class="form-check-label" for="item_checkbox_{{$orderItemIndex}}"></label>
                                                                            </div>
                                                                        </td>
                                                                         <td class="poprod-decpt">
                                                                         @if (isset($orderItem -> si_item_id))
                                                                            <input type = "hidden" id = "qt_book_id_{{$orderItemIndex}}" value = "{{$orderItem ?-> sale_invoice ?-> book_id}}" />
                                                                            <input type = "hidden" id = "qt_book_code_{{$orderItemIndex}}" value = "{{$orderItem ?-> invoice_item ?->header ?-> book_code}}" />

                                                                            <input type = "hidden" id = "qt_document_no_{{$orderItemIndex}}" value = "{{$orderItem ?-> invoice_item ?->header ?-> document_number}}" />
                                                                            <input type = "hidden" id = "qt_document_date_{{$orderItemIndex}}" value = "{{$orderItem ?->invoice_item ?->header ?-> document_date}}" />

                                                                            <input type = "hidden" id = "qt_id_{{$orderItemIndex}}" value = "{{$orderItem -> sale_invoice ?-> id}}" />
                                                                            <input type = "hidden" id = "qts_id_{{$orderItemIndex}}" value = "{{$orderItem -> si_item_id}}" name = "quotation_item_ids[]"/>

                                                                            <input type = "hidden" id = "qt_id_header_{{$orderItemIndex}}" value = "{{$orderItem ?-> header ?-> id}}" name = "quotation_item_ids_header[]"/>
                                                                            <input type = "hidden" id = "qt_type_id_{{$orderItemIndex}}" value = "{{$orderItem?->invoice_item?-> header ?->document_type}}" name = "quotation_item_type[]"/>
                                                                            @endif

                                                                            <input type="text" id = "items_dropdown_{{$orderItemIndex}}" name="item_code[]" placeholder="Select" class="form-control mw-100 ledgerselecct comp_item_code readonly ui-autocomplete-input" autocomplete="off" data-name="{{$orderItem -> item ?-> item_name}}" data-code="{{$orderItem -> item ?-> item_code}}" data-id="{{$orderItem -> item ?-> id}}" hsn_code = "{{$orderItem -> item ?-> hsn ?-> code}}" item-name = "{{$orderItem -> item ?-> item_name}}" specs = "{{$orderItem -> item ?-> specifications}}" attribute-array = "{{$orderItem -> item_attributes_array()}}" readonly  value = "{{$orderItem -> item ?-> item_code}}">
                                                                            <input type = "hidden" name = "item_id[]" id = "items_dropdown_{{$orderItemIndex}}_value" value = "{{$orderItem -> item_id}}"></input>
                                                                        </td>
                                                                        <td class="poprod-decpt">
                                                                            <input type="text" id = "items_name_{{$orderItemIndex}}" class="form-control mw-100"   value = "{{$orderItem -> item ?-> item_name}}" readonly>
                                                                        </td>
                                                                        <td class="poprod-decpt" id='attribute_section_{{$orderItemIndex}}'>
                                                                            <button id = "attribute_button_{{$orderItemIndex}}" {{count($orderItem -> item_attributes_array()) > 0 ? '' : 'disabled'}} type = "button" data-bs-toggle="modal" onclick = "setItemAttributes('items_dropdown_{{$orderItemIndex}}', '{{$orderItemIndex}}', {{ json_encode(!$orderItem->is_editable) }});" data-bs-target="#attribute" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
                                                                            <input type = "hidden" name = "attribute_value_{{$orderItemIndex}}" />
                                                                        </td>
                                                                        <td>
                                                                            <select class="form-select" name = "uom_id[]" id = "uom_dropdown_{{$orderItemIndex}}">
                                                                                </select>
                                                                        </td>
                                                                        <td class="d-flex d-none align-items-center form-select">
                                                                            <select class="form-select" name="item_store[{{$orderItemIndex}}]" id="item_store_{{$orderItemIndex}}" onclick="getStoresData({{$orderItemIndex}});">
                                                                                @foreach ($stores as $store)
                                                                                    <option value="{{$store->id}}" {{$store->id == $orderItem->store_id ? 'selected' : ""}}>{{$store->store_name}}</option>
                                                                                @endforeach
                                                                            </select>

                                                                            <div class="me-50 d-none cursor-pointer addDeliveryScheduleBtn" style="display:none;" data-row-count="{{$orderItemIndex}}" onclick="getStoresData('{{$orderItemIndex}}',null,null,true)" data-stores='[]' id="data_stores_{{$orderItemIndex}}">
                                                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="Store Location" class="text-primary">
                                                                                    <i data-feather="map-pin"></i>
                                                                                </span>
                                                                            </div>
                                                                        </td>
                                                                        <td><input type="text" id = "item_qty_{{$orderItemIndex}}" name = "item_qty[]" oninput = "changeItemQty(this, '{{$orderItemIndex}}');" value = "{{$orderItem -> order_qty}}" data-stores = "{{$orderItem->item_locations}}"  class="form-control item_store_locations mw-100 text-end" onblur = "setFormattedNumericValue(this);"  max = "{{($orderItem -> max_attribute)}}"/></td>
                                                                       <td><input type="text" id = "item_rate_{{$orderItemIndex}}" name = "item_rate[]" {{$type == 'dn' ? 'readonly' : ''}} oninput = "changeItemRate(this, '{{$orderItemIndex}}');" value = "{{$orderItem -> rate}}" class="form-control mw-100 text-end" onblur = "setFormattedNumericValue(this);" /></td>
                                                                        <td><input type="text" id = "item_value_{{$orderItemIndex}}" disabled class="form-control mw-100 text-end item_values_input" value = "{{$orderItem -> order_qty * $orderItem -> rate}}" /></td>
                                                                        <input type = "hidden" id = "header_discount_{{$orderItemIndex}}" value = "{{$orderItem -> header_discount_amount}}" ></input>
                                                                        <input type = "hidden" id = "header_expense_{{$orderItemIndex}}" value = "{{$orderItem -> header_expense_amount}}"></input>
                                                                        <td>
                                                                            <div class="position-relative d-flex align-items-center">
                                                                            <input type="text" id="item_discount_{{$orderItemIndex}}" disabled class="form-control mw-100 text-end item_discounts_input"    style="width: 70px" value="{{ $orderItem->item_discount_amount ?? 0 }}"/>
                                                                                <div class="ms-50">
                                                                                    <button type = "button" {{$type == 'dn' ? 'disabled' : ''}} onclick = "onDiscountClick('item_value_{{$orderItemIndex}}', '{{$orderItemIndex}}')" data-bs-toggle="modal" data-bs-target="#discount" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Add</button>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                                <input type="hidden" id = "item_tax_{{$orderItemIndex}}" value = "{{$orderItem -> tax_amount}}" class="form-control mw-100 text-end item_taxes_input" style="width: 70px" />


                                                                        <td><input type="text" id ="value_after_discount_{{$orderItemIndex}}" value="{{ (int)(($orderItem->order_qty * $orderItem->rate) - $orderItem->item_discount_amount) }}"  disabled class="form-control mw-100 text-end item_val_after_discounts_input" /></td>
                                                                        <input type = "hidden" id = "value_after_header_discount_{{$orderItemIndex}}" class = "item_val_after_header_discounts_input" value = "{{($orderItem -> order_qty * $orderItem -> rate) - $orderItem -> item_discount_amount - $orderItem -> header_discount_amount}}" ></input>
                                                                        <input type="hidden" id = "item_total_{{$orderItemIndex}}" value = "{{($orderItem -> order_qty * $orderItem -> rate) - $orderItem -> item_discount_amount - $orderItem -> header_discount_amount + ($orderItem -> tax_amount)}}" disabled class="form-control mw-100 text-end item_totals_input" />
                                                                         <td>
                                                                            <div class="d-flex">
                                                                                <div class="me-50 cursor-pointer" data-bs-toggle="modal" data-bs-target="#Remarks" onclick = "setItemRemarks('item_remarks_{{$orderItemIndex}}');"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary"><i data-feather="file-text"></i></span></div>
                                                                                    @if(isset($orderItem) && $orderItem->si_item_id && isset($orderItem->erpSrItemLot) && count($orderItem->erpSrItemLot))
                                                                                    <!-- Hidden Batch Input -->
                                                                                    <input type="hidden" id="batches_{{$orderItemIndex}}" name="batch_details[{{$orderItemIndex}}]" value='{{ isset($orderItem) ? json_encode($orderItem->erpSrItemLot->toArray()) : '' }}' />

                                                                                    <!-- Batch Button -->
                                                                                    <div class="me-50 cursor-pointer addBatchBtn"
                                                                                        data-row-count="{{$orderItemIndex}}"
                                                                                        data-is-batch-number="{{ $orderItem->item?->is_batch_no ? 1 : 0 }}"
                                                                                        data-is-expiry="{{ $orderItem->item?->is_expiry ? 1 : 0 }}"
                                                                                        data-bs-toggle="modal"
                                                                                        data-bs-target="#item-batch-modal">
                                                                                        <span data-bs-toggle="tooltip" data-bs-placement="top" class="text-primary"
                                                                                            data-bs-original-title="Item Batch" aria-label="Item Batch">
                                                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                                                                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                                                                stroke-linejoin="round" class="feather feather-map-pin">
                                                                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                                                                <circle cx="12" cy="10" r="3"></circle>
                                                                                            </svg>
                                                                                        </span>
                                                                                    </div>
                                                                                    @endif
                                                                            </div>
                                                                         </td>
                                                                         <input type="hidden" id = "item_remarks_{{$orderItemIndex}}" name = "item_remarks[]" value = "{{$orderItem -> remarks}}"/>
                                                                         <input type="hidden" id = "item_lot_qty_{{$orderItemIndex}}" name = "item_lot[]" lot-data = '{{ $orderItem->erpSrItemLot }}' value = ""/>

                                                                      </tr>
                                                                    @endforeach
                                                                @else
                                                                @endif
                                                             </tbody>

                                                             <tfoot>

                                                                 <tr class="totalsubheadpodetail">
                                                                    <td colspan="7"></td>
                                                                    <td class="text-end" id = "all_items_total_value">00.00</td>
                                                                    <td class="text-end" id = "all_items_total_discount">00.00</td>
                                                                    <input type = "hidden" id = "all_items_total_tax"></input>
                                                                    <td class="text-end all_tems_total_common" id = "all_items_total_total">00.00</td>
                                                                    <td></td>
                                                                </tr>

                                                                 <tr valign="top">
                                                                    <td id='item_details_td' colspan="7" rowspan="10">
                                                                        <table class="table border">
                                                                            <tr>
                                                                                <td class="p-0">
                                                                                    <h6 class="text-dark mb-0 bg-light-primary py-1 px-50"><strong>Item Details</strong></h6>
                                                                                </td>
                                                                            </tr>


                                                                            <tr>
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_cat_hsn">

                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr id = "current_item_specs_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_specs">

                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr id = "current_item_attribute_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_attributes">

                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr id = "current_item_stocks_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_stocks">

                                                                                    </div>
                                                                                </td>
                                                                            </tr>


                                                                            <tr id = "current_item_lot_no_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_lot_no">

                                                                                    </div>
                                                                                 </td>
                                                                            </tr>
                                                                            <tr id = "current_item_so_no_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_so_no">

                                                                                    </div>
                                                                                 </td>
                                                                            </tr>

                                                                            <tr id = "current_item_qt_no_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_qt_no">

                                                                                    </div>
                                                                                </td>
                                                                            </tr>

                                                                            <!-- <tr id = "current_item_store_location_row">
                                                                                <td class="poprod-decpt">
                                                                                    <div id ="current_item_store_location">

                                                                                    </div>
                                                                                </td>
                                                                            </tr> -->

                                                                            <tr id = "current_item_description_row" style="display:none">
                                                                                <td class="poprod-decpt">
                                                                                    <span class="badge rounded-pill badge-light-secondary"><strong>Remarks</strong>: <span id = "current_item_description"></span></span>
                                                                                 </td>
                                                                            </tr>

                                                                        </table>
                                                                    </td>

                                                                    <td colspan="5" style = "{{$type == 'dnote' ? 'display : none;' : ''}}" id="invoice_summary_td">
                                                                        <table class="table border mrnsummarynewsty" id = "summary_table">
                                                                            <tr>
                                                                                <td colspan="3" class="p-0">
                                                                                    <h6 class="text-dark mb-0 bg-light-primary py-1 px-50 d-flex justify-content-between"><strong>Return Summary</strong>
                                                                                    <div class="addmendisexpbtn">
                                                                                            <button type = "button" id = "taxes_button" data-bs-toggle="modal" data-bs-target="#orderTaxes" class="btn p-25 btn-sm btn-outline-secondary" onclick = "onOrderTaxClick();" >Taxes</button>
                                                                                            <input class = "item_taxes_input" type = "hidden" id = "order_tcs_tax" value = "" />
                                                                                            <button type = "button" id = "order_discount_button" data-bs-toggle="modal" data-bs-target="#discountOrder" class="btn p-25 btn-sm btn-outline-secondary" onclick = "onOrderDiscountModalOpen();"><i data-feather="plus"></i> Discount</button>
                                                                                            <button type = "button" id = "order_expense_button" data-bs-toggle="modal" data-bs-target="#expenses" class="btn p-25 btn-sm btn-outline-secondary" onclick = "onOrderExpenseModalOpen();"><i data-feather="plus"></i> Expenses</button>
                                                                                        </div>
                                                                                    </h6>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="totalsubheadpodetail">
                                                                                <td width="55%"><strong>Item Total</strong></td>
                                                                                <td class="text-end" id = "all_items_total_value_summary">00.00</td>
                                                                            </tr>
                                                                            <tr class="">
                                                                                <td width="55%">Item Discount</td>
                                                                                <td class="text-end" id = "all_items_total_discount_summary">00.00</td>
                                                                            </tr>
                                                                            <tr class="totalsubheadpodetail">
                                                                                <td width="55%"><strong>Taxable Value</strong></td>
                                                                                <td class="text-end" id = "all_items_total_total_summary">00.00</td>
                                                                            </tr>
                                                                            <tr class="">
                                                                                <td width="55%">Taxes</td>
                                                                                <td class="text-end" id = "all_items_total_tax_summary">00.00</td>
                                                                            </tr>
                                                                            <tr class="totalsubheadpodetail">
                                                                                <td width="55%"><strong>Total After Tax</strong></td>
                                                                                <td class="text-end" id = "all_items_total_after_tax_summary">00.00</td>
                                                                            </tr>
                                                                            <tr class="">
                                                                                <td width="55%">Expenses</td>
                                                                                <td class="text-end" id = "all_items_total_expenses_summary">00.00</td>
                                                                            </tr>
                                                                            <input type = "hidden" name = "sub_total" value = "0.00"></input>
                                                                            <input type = "hidden" name = "discount" value = "0.00"></input>
                                                                            <input type = "hidden" name = "discount_amount" value = "0.00"></input>
                                                                            <input type = "hidden" name = "other_expenses" value = "0.00"></input>
                                                                            <input type = "hidden" name = "total_amount" value = "0.00"></input>
                                                                            <!-- <tr>
                                                                                <td><strong>Discount 1</strong></td>
                                                                                <td class="text-end">1,000.00</td>
                                                                            </tr> -->
                                                                            <!-- <tr class="totalsubheadpodetail">
                                                                                <td><strong>Taxable Value</strong></td>
                                                                                <td class="text-end">38,000.00</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>6% SGST</strong></td>
                                                                                <td class="text-end">2,280.00</td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><strong>6% CGST</strong></td>
                                                                                <td class="text-end">2,280.00</td>
                                                                            </tr> -->

                                                                            <!-- <tr class="totalsubheadpodetail">
                                                                                <td><strong>Total After Tax</strong></td>
                                                                                <td class="text-end">42,560.00</td>
                                                                            </tr> -->

                                                                            <!-- <tr>
                                                                                <td><strong>Parking Exp.</strong></td>
                                                                                <td class="text-end">240.00</td>
                                                                            </tr> -->
                                                                            <tr class="voucher-tab-foot">
                                                                                <td class="text-primary"><strong>Grand Total</strong></td>
                                                                                <td>
                                                                                    <div class="quottotal-bg justify-content-end">
                                                                                        <h5 id = "grand_total">00.00</h5>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                 </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                    <div class="row mt-2">
                                                     <div class="col-md-12">
                                                            <div class = "row">
                                                             <div class="col-md-4">
                                                                <div class="mb-1">
                                                                    <label class="form-label">Upload Document</label>
                                                                    <input type="file" class="form-control" name = "attachments[]" onchange = "addFiles(this,'main_order_file_preview')" max_file_count = "{{isset($maxFileCount) ? $maxFileCount : 10}}" multiple >
                                                                    <span class = "text-primary small">{{__("message.attachment_caption")}}</span>
                                                                </div>
                                                            </div>
                                                            <div class = "col-md-6" style = "margin-top:19px;">
                                                                <div class = "row" id = "main_order_file_preview">
                                                                </div>
                                                            </div>
                                                            </div>
                                                     </div>
                                                        <div class="col-md-12">
                                                            <div class="mb-1">
                                                                <label class="form-label">Final Remarks</label>
                                                                <textarea type="text" rows="4" class="form-control" placeholder="Enter Remarks here..." name = "final_remarks">{{isset($order) ? $order -> remarks : '' }}</textarea>
                                                            </div>
                                                        </div>

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
<!-- Item Batch Modal -->
<div class="modal" id="item-batch-modal" tabindex="-1" aria-labelledby="itemBatchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="text-center modal-title mb-1" id="itemBatchLabel">Item Batches</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body item-batch-modal-body">
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary add-batch-row-header">
                            <i data-feather="plus"></i> Add
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-batch-row-header">
                            <i data-feather="trash"></i> Delete
                        </button>
                    </div>
                </div>
                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id="itemBatchTable">
                    <thead>
                        <tr>
                            <th width="30px">#</th>
                            <th width="100px">Batch Number</th>
                            <th width="100px">Manufacturing Year</th>
                            <th width="120px">Expiry Date</th>
                            <th width="80px">Quantity</th>
                            <th width="50px">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total Quantity:</th>
                            <th id="totalBatchQty">0</th>
                            <th></th>
                        </tr>
                </table>

                <!-- Context for JS -->
                <input type="hidden" id="itemBatchRowIndex" />
                <input type="hidden" id="itemBatchIsExpiry" value="0" />
                <input type="hidden" id="itemBatchIsEdit" value="0" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveItemBatchBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<!-- Item Batch Modal End -->

<!-- Asset Detail Modal -->
<div class="modal fade" id="assetDetailModal" tabindex="-1" aria-labelledby="assetDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="text-center modal-title mb-1" id="assetDetailLabel">Asset Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body asset-detail-modal-body">
                <!-- Populated dynamically via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary submitAssetBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<!-- Asset Detail Modal End -->
    <div class="modal fade" id="tax" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Taxes</h1>
					<p class="text-center">Enter the details below.</p>


                    <!-- <div class="text-end"><a href="#" class="text-primary add-contactpeontxt mt-50"><i data-feather='plus'></i> Add Discount</a></div> -->

					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "tax_main_table">
									<thead>
										 <tr>
                                            <th>#</th>
											<th width="150px">Tax Name</th>
											<th>Tax %</th>
											<th>Tax Value</th>
										  </tr>
										</thead>
										<tbody>

									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick = "closeModal('tax');">Cancel</button>
					    <button type="button" class="btn btn-primary" onclick = "closeModal('tax');">Submit</button>
				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="discount" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Add Discount</h1>
					<p class="text-center">Enter the details below.</p>

                    <div class = "row">
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Type<span class="text-danger">*</span></label>
                                <input type="text" id="new_discount_name" placeholder="Select" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value=""  onblur = "resetDiscountOrExpense(this,'new_discount_percentage')">
                                <input type = "hidden" id = "new_discount_id" />
                            </div>
                        </div>
                        <div class="col-md-2" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Percentage <span class="text-danger">*</span></label>
                                <input id = "new_discount_percentage" oninput = "onChangeDiscountPercentage(this);" type="text" class="form-control mw-100 text-end" placeholder = "Discount Percentage"/>
                            </div>
                        </div>
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Value <span class="text-danger">*</span></label>
                                <input id = "new_discount_value" type="text" class="form-control mw-100 text-end" oninput = "onChangeDiscountValue(this);" placeholder = "Discount Value"/>
                            </div>
                        </div>
                        <div class="col-md-auto mt-1 d-flex align-items-center justify-content-center" style = "padding-right:0px">
                            <div>
                            <a href="#" onclick = "addDiscount();" class="text-primary can_hide"><i data-feather="plus-square"></i></a>
                            </div>
                        </div>
                    </div>


                    <!-- <div class="text-end"><a href="#" class="text-primary add-contactpeontxt mt-50"><i data-feather='plus'></i> Add Discount</a></div> -->

					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "discount_main_table" total-value = "0">
									<thead>
										 <tr>
                                            <th>#</th>
											<th width="150px">Discount Name</th>
											<th>Discount %</th>
											<th>Discount Value</th>
											<th>Action</th>
										  </tr>
										</thead>
										<tbody >
											 <tr>

											</tr>

                                            <tr>
                                                 <td colspan="2"></td>
                                                 <td class="text-dark"><strong>Total</strong></td>
                                                 <td class="text-dark" id = "total_item_discount"><strong>0.00</strong></td>
                                                 <td></td>
											</tr>


									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick = "closeModal('discount');">Cancel</button>
					    <button type="button" class="btn btn-primary" onclick = "closeModal('discount');">Submit</button>
				</div>
			</div>
		</div>
	</div>


    <div class="modal fade" id="discountOrder" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Discount</h1>

                    <div class = "row">
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Type<span class="text-danger">*</span></label>
                                <input type="text" id="new_order_discount_name" placeholder="Select" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value=""  onblur = "resetDiscountOrExpense(this, 'new_order_discount_percentage')">
                                <input type = "hidden" id = "new_order_discount_id" />
                            </div>
                        </div>
                        <div class="col-md-2" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Percentage <span class="text-danger">*</span></label>
                                <input id = "new_order_discount_percentage" oninput = "onChangeOrderDiscountPercentage(this);" type="text" class="form-control mw-100 text-end" />
                            </div>
                        </div>
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Value <span class="text-danger">*</span></label>
                                <input id = "new_order_discount_value" type="text" class="form-control mw-100 text-end" oninput = "onChangeOrderDiscountValue(this);"/>
                            </div>
                        </div>
                        <div class="col-md-auto mt-1 d-flex align-items-center justify-content-center" style = "padding-right:0px">
                            <div>
                                <a  href="#" onclick = "addOrderDiscount();" class="text-primary can_hide"><i data-feather="plus-square"></i></a>
                            </div>
                        </div>
                    </div>


					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "order_discount_main_table">
									<thead>
										 <tr>
                                            <th>S.No.</th>
											<th width="150px">Discount Name</th>
											<th>Discount %</th>
											<th>Discount Value</th>
											<th>Action</th>
										  </tr>
										</thead>
										<tbody >
											 <tr>

											</tr>

                                            <tr>
                                                 <td colspan="2"></td>
                                                 <td class="text-dark"><strong>Total</strong></td>
                                                 <td class="text-dark" id = "total_order_discount"><strong>0.00</strong></td>
                                                 <td></td>
											</tr>
									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1">Cancel</button>
					<button type="button" class="btn btn-primary" onclick = "closeModal('discountOrder');">Submit</button>
				</div>
			</div>
		</div>
	</div>
    <div class="modal fade" id="orderTaxes" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Taxes</h1>
					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "order_tax_main_table">
									<thead>
										 <tr>
                                            <th>#</th>
											<th width="150px">Tax</th>
											<th>Taxable Amount</th>
                                            <th>Tax %</th>
											<th>Tax Value</th>
										  </tr>
										</thead>
										<tbody id = "order_tax_details_table">
									   </tbody>


								</table>
							</div>

				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="edit-address-shipping" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Edit Address</h1>
					<p class="text-center">Enter the details below.</p>


                     <div class="row mt-2">
                        <div class = "col-md-12 mb-1">
                        <select class="select2 form-select vendor_dependent" id = "shipping_address_dropdown" name = "shipping_address" oninput = "onShippingAddressChange(this);">
                                                                        @if (isset($order) && isset($shipping_addresses))
                                                                            @foreach ($shipping_addresses as $shipping_address)
                                                                                <option value = "{{$shipping_address -> value}}" {{$order -> shipping_to === $shipping_address -> id}}>{{$shipping_address -> label}}</option>
                                                                            @endforeach
                                                                        @else
                                                                            <option value = "">Select</option>
                                                                        @endif
                                                                    </select>
                        </div>
                       <div class="col-md-6 mb-1">
							<label class="form-label">Country <span class="text-danger">*</span></label>
							<select class="select2 form-select" id = "shipping_country_id_input"  onchange = "changeDropdownOptions(this, ['shipping_state_id_input'], ['states'], '/states/', null, ['shipping_city_id_input'])">
								@foreach ($countries as $country)
                                    <option value = "{{$country -> value}}">{{$country -> label}}</option>
                                @endforeach
							</select>
						</div>


						<div class="col-md-6 mb-1">
							<label class="form-label">State <span class="text-danger">*</span></label>
							<select class="select2 form-select" id = "shipping_state_id_input"  onchange = "changeDropdownOptions(this, ['shipping_city_id_input'], ['cities'], '/cities/', null, [])">
							</select>
						</div>

                         <div class="col-md-6 mb-1">
							<label class="form-label">City <span class="text-danger">*</span></label>
							<select class="select2 form-select" name = "shipping_city_id" id = "shipping_city_id_input">
							</select>
						</div>


						<div class="col-md-6 mb-1">
							<label class="form-label w-100">Pincode <span class="text-danger">*</span></label>
							<input type="text" class="form-control" value="" placeholder="Enter Pincode" name ="shipping_pincode" id = "shipping_pincode_input"/>
						</div>

						<div class="col-md-12 mb-1">
							<label class="form-label">Address <span class="text-danger">*</span></label>
							<textarea class="form-control" placeholder="Enter Address" name = "shipping_address_text" id = "shipping_address_input"></textarea>
						</div>

                    </div>



				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1">Cancel</button>
                        <button type="button" onclick = "saveAddressShipping();" class="btn btn-primary">Submit</button>
				</div>
			</div>
		</div>
	</div>
    <div class="modal fade" id="edit-address-billing" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Edit Address</h1>
					<p class="text-center">Enter the details below.</p>


                     <div class="row mt-2">
                     <div class = "col-md-12 mb-1">
                     <select class="select2 form-select vendor_dependent" id = "billing_address_dropdown" name = "billing_address" oninput = "onBillingAddressChange(this);">
                                                                        @if (isset($order) && isset($billing_addresses))
                                                                            @foreach ($billing_addresses as $billing_address)
                                                                                <option value = "{{$billing_address -> value}}" {{$order -> billing_to === $billing_address -> id}}>{{$billing_address -> label}}</option>
                                                                            @endforeach
                                                                        @else
                                                                            <option value = "">Select</option>
                                                                        @endif
                                                                    </select>
                        </div>

                        <div class="col-md-6 mb-1">
							<label class="form-label">Country <span class="text-danger">*</span></label>
							<select class="select2 form-select" name = "billing_country_id" id = "billing_country_id_input" onchange = "changeDropdownOptions(this, ['billing_state_id_input'], ['states'], '/states/', null, ['billing_city_id_input'])">
								@foreach ($countries as $country)
                                    <option value = "{{$country -> value}}">{{$country -> label}}</option>
                                @endforeach
							</select>
						</div>


						<div class="col-md-6 mb-1">
							<label class="form-label">State <span class="text-danger">*</span></label>
							<select class="select2 form-select" name = "billing_state_id" id = "billing_state_id_input" onchange = "changeDropdownOptions(this, ['billing_city_id_input'], ['cities'], '/cities/', null, [])">
							</select>
						</div>

                         <div class="col-md-6 mb-1">
							<label class="form-label">City <span class="text-danger">*</span></label>
							<select class="select2 form-select" name = "billing_city_id" id = "billing_city_id_input">
							</select>
						</div>


						<div class="col-md-6 mb-1">
							<label class="form-label w-100">Pincode <span class="text-danger">*</span></label>
							<input type="text" class="form-control" value="" placeholder="Enter Pincode" name ="billing_pincode" id = "billing_pincode_input"/>
						</div>

						<div class="col-md-12 mb-1">
							<label class="form-label">Address <span class="text-danger">*</span></label>
							<textarea class="form-control" placeholder="Enter Address" name = "billing_address_text" id = "billing_address_input"></textarea>
						</div>

                    </div>



				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1">Cancel</button>
                        <button type="button" onclick = "saveAddressBilling();" class="btn btn-primary">Submit</button>
				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="Remarks" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" >
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Add/Edit Remarks</h1>
					<p class="text-center">Enter the details below.</p>


                     <div class="row mt-2">


						<div class="col-md-12 mb-1">
							<label class="form-label">Remarks <span class="text-danger">*</span></label>
							<textarea class="form-control" current-item = "item_remarks_0" onchange = "changeItemRemarks(this);" id ="current_item_remarks_input" placeholder="Enter Remarks"></textarea>
						</div>

                    </div>



				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('Remarks');">Cancel</button>
					<button type="button" class="btn btn-primary" onclick="closeModal('Remarks');">Submit</button>
				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="expenses" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Expenses</h1>

                    <div class = "row">
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Type<span class="text-danger">*</span></label>
                                <input type="text" id="order_expense_name" placeholder="Select" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off" value=""  onblur = "resetDiscountOrExpense(this, 'order_expense_percentage')">
                                <input type = "hidden" id = "order_expense_id" />
                            </div>
                        </div>
                        <div class="col-md-2" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Percentage <span class="text-danger">*</span></label>
                                <input type="text" id = "order_expense_percentage" oninput = "onChangeOrderExpensePercentage(this);" class="form-control mw-100 text-end" />
                            </div>
                        </div>
                        <div class="col-md-4" style = "padding-right:0px">
                            <div class="">
                                <label class="form-label">Value <span class="text-danger">*</span></label>
                                <input type="text" id = "order_expense_value" oninput = "onChangeOrderExpenseValue(this);" class="form-control mw-100 text-end" />
                            </div>
                        </div>
                        <div class="col-md-auto mt-1 d-flex align-items-center justify-content-center" style = "padding-right:0px">
                            <div>
                            <a href="#" onclick = "addOrderExpense();" class="text-primary can_hide"><i data-feather="plus-square"></i></a>
                            </div>
                        </div>
                    </div>

					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "order_expense_main_table">
									<thead>
										 <tr>
                                            <th>S.No.</th>
											<th width="150px">Expense Name</th>
											<th>Expense %</th>
											<th>Expense Value</th>
											<th>Action</th>
										  </tr>
										</thead>
										<tbody>
											 <tr>

											</tr>


                                            <tr>
                                                 <td colspan="2"></td>
                                                 <td class="text-dark"><strong>Total</strong></td>
                                                 <td class="text-dark" id = "total_order_expense" ><strong>00.00</strong></td>
                                                 <td></td>
											</tr>


									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('expenses');">Cancel</button>
					<button type="button" class="btn btn-primary" onclick="closeModal('expenses');">Submit</button>
				</div>
			</div>
		</div>
	</div>
    <!-- Store Item Modal Start -->
    <div class="modal fade" id="deliveryScheduleModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
        <div class="modal-dialog  modal-dialog-centered modal-lg" >
            <input type="hidden" name="store-row-id" id="store-row-id">
            <div class="modal-content">
                <div class="modal-header p-0 bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-2 mx-50 pb-2">
                    <h1 class="text-center mb-1" id="shareProjectTitle">Store Location</h1>
                    {{-- <p class="text-center">Enter the details below.</p> --}}

                    <div class="text-end">
                        <a type = "button" class="text-primary add-contactpeontxt mt-50 " onclick="addStore()"><i data-feather="plus"></i> Add</a>
                    </div>

                    <div class="table-responsive-md customernewsection-form">
                        <table id="deliveryScheduleTable" class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
                            <thead>
                                <tr>
                                    <th width="50px">S.No.</th>
                                    <th>Rack</th>
                                    <th>Shelf</th>
                                    <th>Bin</th>
                                    <th>Qty</th>
                                    <th width="50px">Action</th>
                                </tr>
                        </thead>
                        <tbody id ='item_location_table'>

                        </tbody>
                        <tfoot>
                            <tr id="deliveryFooter">
                                <td colspan="3"></td>
                                <td class="text-dark"><strong>Total</strong></td>
                                <td id="total" class="text-dark"><strong >0.00</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button>
                    <button type="button" class="btn btn-primary itemDeliveryScheduleSubmit" onclick='SubmitLocation()'>Submit</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Store Item Modal End -->
    <div class="modal fade" id="location" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" style="max-width: 900px">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Store Location</h1>
					<p class="text-center">Enter the details below.</p>


                    <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" style = "display:none;">
						<tbody>
                            <tr>
                                <td></td>
                                <td>
                                    <input type="text" id = "new_store_code_input" placeholder="Select Store" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off">
                                    <input type = "hidden" id = "new_store_id_input"></input>
                                </td>
                                <td>
                                    <input type="text" id = "new_rack_code_input" placeholder="Select Rack" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off">
                                    <input type = "hidden" id = "new_rack_id_input"></input>
                                </td>
                                <td>
                                    <input type="text" id = "new_shelf_code_input" placeholder="Select Shelf" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off">
                                    <input type = "hidden" id = "new_shelf_id_input"></input>
                                </td>
                                <td>
                                    <input type="text" id = "new_bin_code_input" placeholder="Select Bin" class="form-control mw-100 ledgerselecct ui-autocomplete-input" autocomplete="off">
                                    <input type = "hidden" id = "new_bin_id_input"></input>
                                </td>
                                <td><input type="text" id = "new_location_qty" class="form-control mw-100" /></td>
                                <td>
                                    <a href="#" class="text-primary" onclick = "addItemStore();"><i data-feather="plus-square"></i></a>
                                </td>
                            </tr>
                        </tbody>
                    </table>

					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
									<thead>
										 <tr>
                                            <th width="80px">#</th>
											<th>Store</th>
											<th>Rack</th>
											<th>Shelf</th>
											<th>Bin</th>
                                            <th width="50px">Qty</th>
											<th>Action</th>
										  </tr>
										</thead>
										<tbody id = "item_location_tablae" current-item-index = '0'>


									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('location');">Cancel</button>
					<button type="button" class="btn btn-primary" onclick="closeModal('location');">Submit</button>
				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="delivery" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered" >
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Delivery Schedule</h1>
					<p class="text-center">Enter the details below.</p>

                    <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
									<thead>
										 <tr>
                                         <td>#</td>
                                                <td><input type="text" id = "new_item_delivery_qty_input" class="form-control mw-100" /></td>
                                                <td><input type="date" id = "new_item_delivery_date_input" value="{{Carbon\Carbon::now() -> format('Y-m-d')}}" class="form-control mw-100" /></td>
                                                <td>
                                                    <a href="#" onclick = "addDeliveryScheduleRow();" class="text-primary"><i data-feather="plus-square"></i></a>
                                                </td>
										  </tr>
										</thead>
										<tbody >

									   </tbody>


								</table>
					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "delivery_schedule_main_table">
									<thead>
										 <tr>
                                            <th>#</th>
											<th width="150px">Quantity</th>
											<th>Date</th>
											<th>Action</th>
										  </tr>
										</thead>
										<tbody>
											 <tr>

											</tr>
                                            <tr>
                                                 <td class="text-dark"><strong>Total</strong></td>
                                                 <td class="text-dark"><strong id = "item_delivery_qty"></strong></td>
                                                 <td></td>
											</tr>


									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('delivery');">Cancel</button>
					<button type="button" class="btn btn-primary" onclick="closeModal('delivery');">Submit</button>
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
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail" id = "attributes_table_modal" item-index = "">
									<thead>
										 <tr>
											<th>Attribute Name</th>
											<th>Attribute Value</th>
										  </tr>
										</thead>
										<tbody id = "attribute_table">

									   </tbody>


								</table>
							</div>
				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick = "closeModal('attribute');">Cancel</button>
					    <button type="button" class="btn btn-primary" onclick = "submitAttr('attribute');">Select</button>
				</div>
			</div>
		</div>
	</div>

    <div class="modal fade" id="amendConfirmPopup" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <div>
               <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="myModalLabel17">Amend
               {{request() -> type == "srdn" ? "Delivery Note CUM Return" : (request() -> type == "dn" ? 'Delivery Note' : 'Sales Return')}}
               </h4>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <input type="hidden" name="action_type" id="action_type_main">

         </div>
         <div class="modal-body pb-2">
            <div class="row mt-1">
               <div class="col-md-12">
                  <div class="mb-1">
                     <label class="form-label">Remarks</label>
                     <textarea name="amend_remarks" class="form-control cannot_disable"></textarea>
                  </div>
                  <div class = "row">
                    <div class = "col-md-8">
                        <div class="mb-1">
                            <label class="form-label">Upload Document</label>
                            <input name = "amend_attachments[]" onchange = "addFiles(this, 'amend_files_preview')" type="file" class="form-control cannot_disable" max_file_count = "2" multiple/>
                        </div>
                    </div>
                    <div class = "col-md-4" style = "margin-top:19px;">
                        <div class="row" id = "amend_files_preview">
                        </div>
                    </div>
                  </div>
                  <span class = "text-primary small">{{__("message.attachment_caption")}}</span>
               </div>
            </div>
         </div>
         <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-outline-secondary me-1">Cancel</button>
            <button type="button" class="btn btn-primary" onclick = "submitAmend();">Submit</button>
         </div>
      </div>
      
   </div>


<div class="modal fade text-start alertbackdropdisabled" id="amendmentconfirm" tabindex="-1" aria-labelledby="myModalLabel1" aria-hidden="true" data-bs-backdrop="false">
  <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header p-0 bg-transparent">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body alertmsg text-center warning">
              <i data-feather='alert-circle'></i>
              <h2>Are you sure?</h2>
              <p>Are you sure you want to <strong>Amend</strong> this <strong>{{request() -> type == "srdn" ? "Delivery Note CUM Return" : (request() -> type == "dn" ? 'Delivery Note' : 'Sales Return')}}</strong>?</p>
              <button type="button" class="btn btn-secondary me-25" data-bs-dismiss="modal">Cancel</button>
              <button type="button" data-bs-dismiss="modal" onclick = "amendConfirm();" class="btn btn-primary">Confirm</button>
          </div>
      </div>
  </div>
</div>

<div class="modal fade" id="sendMail" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form class="ajax-submit-2" method="POST" action="{{ route('sale.return.creditNoteMail') }}" data-redirect="{{ $redirect_url }}" enctype='multipart/form-data'>
          @csrf
          <input type="hidden" name="action_type" id="action_type_mail">
          <input type="hidden" name="id" value="{{isset($order) ? $order -> id : ''}}">
         <div class="modal-header">
            <div>
               <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="send_mail_heading_label">
               </h4>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body pb-2">
            <div class="row mt-1">
                {{--<div class="col-md-12">
                    <div class="mb-1">
                        <label class="form-label">Email From</label>
                        <input type="text" id='cust_mail' name="email_from" class="form-control cannot_disable">
                    </div>
                </div>--}}
                <div class="col-md-12">
                    <div class="mb-1 ">
                        <label class="form-label">Email To</label>
                        <input type="text" id='cust_mail' name="email_to" class="form-control reset_mail cannot_disable">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="mb-1 ">
                        <label class="form-label">CC To</label>
                        <select name="cc_to[]" id="cc_to" class="select2 reset_mail mail_user form-control cannot_disable" multiple>
                                <option value="">Select</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="mb-1 ">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="mail_remarks" class="form-control cannot_disable reset_mail" placeholder = "Please Enter Required Remarks"></textarea>
                    </div>
                </div>
            </div>
         <div class="modal-footer justify-content-center">
            <button type="reset" class="btn btn-outline-secondary me-1" onclick = "closeModal('sendMail');">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
         </div>
       </form>
      </div>
   </div>
</div>
                                    </div>

                                    </div>
<div class="modal fade text-start show" id="postvoucher" tabindex="-1" aria-labelledby="postVoucherModal" aria-modal="true" role="dialog">
		<div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 1000px">
			<div class="modal-content">
				<div class="modal-header">
					<div>
                        <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="postVoucherModal"> Voucher Details</h4>
                    </div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					 <div class="row">

                         <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Series <span class="text-danger">*</span></label>
                                <input id = "voucher_book_code" class="form-control" disabled="" >
                            </div>
                        </div>

                         <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Voucher No <span class="text-danger">*</span></label>
                                <input id = "voucher_doc_no" class="form-control" disabled="" value="">
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Voucher Date <span class="text-danger">*</span></label>
                                <input id = "voucher_date" class="form-control" disabled="" value="">
                            </div>
                        </div>
                         <div class="col-md-3">
                            <div class="mb-1">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <input id = "voucher_currency" class="form-control" disabled="" value="">
                            </div>
                        </div>

						 <div class="col-md-12">


							<div class="table-responsive">
								<table class="mt-1 table table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad">
									<thead>
										 <tr>
											<th>Type</th>
											<th>Group</th>
											<th>Leadger Code</th>
											<th>Leadger Name</th>
                                            <th class="text-end">Debit</th>
                                            <th class="text-end">Credit</th>
										  </tr>
										</thead>
										<tbody id = "posting-table">


									   </tbody>


								</table>
							</div>
						</div>


					 </div>
				</div>
				<div class="modal-footer text-end">
					<button onclick = "postVoucher(this);" id = "posting_button" type = "button" class="btn btn-primary btn-sm waves-effect waves-float waves-light"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Submit</button>
				</div>
			</div>
		</div>
	</div>
    <div class="modal fade" id="lot" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
		<div class="modal-dialog  modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header p-0 bg-transparent">
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body px-sm-2 mx-50 pb-2">
					<h1 class="text-center mb-1" id="shareProjectTitle">Lot Info</h1>

					<div class="table-responsive-md customernewsection-form">
								<table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail">
									<thead>
										 <tr>
                                            <th width="50px">S.No</th>
											<th>Lot No</th>
											<th>Manufacturing Year</th>
											<th>Expiry Date</th>
											<th>Lot Qty</th>
											<th width="50px">Return Qty</th>
										  </tr>
										</thead>
										<tbody id = "bundle_schedule_table" current-item-index = '0'>


									   </tbody>


								</table>
							</div>

				</div>

				<div class="modal-footer justify-content-center">
						<button type="button" class="btn btn-outline-secondary me-1" onclick="closeModal('lot');">Cancel</button>
					<button type="button" class="btn btn-primary"  onclick="saveLotData();">Submit</button>
				</div>
			</div>
		</div>
	</div>

    
</div>
</form>
                                    </div>
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form class="ajax-submit-2" method="POST" action="{{ route('document.approval.saleReturn') }}" data-redirect="{{ route('sale.return.index', ['type' => $type]) }}" enctype='multipart/form-data'>
          @csrf
          <input type="hidden" name="action_type" id="action_type">
          <input type="hidden" name="id" value="{{isset($order) ? $order -> id : ''}}">
         <div class="modal-header">
            <div>
               <h4 class="modal-title fw-bolder text-dark namefont-sizenewmodal" id="approve_reject_heading_label">
               </h4>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body pb-2">
            <div class="row mt-1">
               <div class="col-md-12">
                  <div class="mb-1">
                     <label class="form-label">Remarks</label>
                     <textarea name="remarks" class="form-control cannot_disable"></textarea>
                  </div>
                  <div class="row">
                    <div class = "col-md-8">
                        <div class="mb-1">
                            <label class="form-label">Upload Document</label>
                            <input type="file" name = "attachments[]" multiple class="form-control cannot_disable" onchange = "addFiles(this, 'approval_files_preview');" max_file_count = "2"/>
                        </div>
                    </div>
                    <div class = "col-md-4" style = "margin-top:19px;">
                        <div class = "row" id = "approval_files_preview">

                        </div>
                    </div>
                  </div>
                  <span class = "text-primary small">{{__("message.attachment_caption")}}</span>

               </div>
            </div>
         </div>
         <div class="modal-footer justify-content-center">
            <button type="reset" class="btn btn-outline-secondary me-1">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit</button>
         </div>
       </form>
      </div>
   </div>
</div>
 @include('salesReturn.invoice-pull-modal')
 @include('salesReturn.dnote-pull-modal')
 @include('salesReturn.si-dnote-pull-modal')

@include('PL.common-js-route',["order" => isset($order) ? $order : null, "route_prefix" => "sale.return"])
@section('scripts')
<script type="text/javascript" src="{{asset('assets/js/modules/pull-popup-datatable.js')}}"></script>
<script src="{{ asset("assets\\js\\modules\\pl\\common-script.js") }}"></script>
<script type="text/javascript" src="{{asset('app-assets/js/scripts/sales/common.js')}}"></script>
<script type="text/javascript" src="{{asset('app-assets/js/file-uploader.js')}}"></script>
<script>
    var currentfy = JSON.stringify({!! isset($order) ? $order : " " !!});
    let requesterTypeParam = "{{isset($order) ? $order -> requester_type : 'Department'}}";
    let redirect = "{{$redirect_url}}";
</script>
<script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
            if (!order || order.document_status == "{{ \App\Helpers\ConstantHelper::DRAFT }}") {
                $("#store_id_input").trigger('change');
            }

        });
        function addItemRow()
        {
            var docType = $("#service_id_input").val();
            var invoiceToFollow = $("#service_id_input").val() == "yes";
            const tableElementBody = document.getElementById('item_header');
            const previousElements = document.getElementsByClassName('item_header_rows');
            const newIndex = previousElements.length ? previousElements.length : 0;
            if (newIndex == 0) {
                let addRow = $('#series_id_input').val() && $('#sub_store_id_input').val() && $('#store_id_input').val() && $("#order_no_input").val() &&  $('#order_no_input').val() && $('#order_date_input').val() && $('#customer_code_input').val();
                if (!addRow) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please fill all the header details first',
                    icon: 'error',
                });
                return;
                }
            } else {
                let addRow = $('#items_dropdown_' + (newIndex - 1)).val() &&  parseFloat($('#item_qty_' + (newIndex - 1)).val()) > 0;
                if (!addRow) {
                    Swal.fire({
                    title: 'Error!',
                    text: 'Please fill all the previous item details first',
                    icon: 'error',
                });
                return;
                }
            }
            const newItemRow = document.createElement('tr');
            newItemRow.className = 'item_header_rows';
            newItemRow.id = "item_row_" + newIndex;
            newItemRow.onclick = function () {
                onItemClick(newIndex);
            };
            var headerStoreId = $("#store_id_input").val();
            var headerStoreCode = $("#store_id_input").attr("data-name");
            var stores = @json($stores);
            var storesHTML = ``;
            stores.forEach(store => {
                if (store.id == headerStoreId) {
                    storesHTML += `<option value = "${store.id}" selected>${store.store_name}</option>`
                } else {
                    storesHTML += `<option value = "${store.id}">${store.store_name}</option>`
                }
            });
            newItemRow.innerHTML = `
            <tr id = "item_row_${newIndex}">
                <td class="customernewsection-form">
                   <div class="form-check form-check-primary custom-checkbox">
                       <input type="checkbox" class="form-check-input item_row_checks" id="item_row_check_${newIndex}" del-index = "${newIndex}">
                       <label class="form-check-label" for="Email"></label>
                   </div>
                </td>
                <td class="poprod-decpt">

                   <input type="text" id = "items_dropdown_${newIndex}" name="item_code[${newIndex}]" placeholder="Select" class="form-control mw-100 ledgerselecct comp_item_code ui-autocomplete-input" autocomplete="off" data-name="" data-code="" data-id="" hsn_code = "" item_name = "" attribute-array = "[]" specs = "[]" item-locations = "[]">
                   <input type = "hidden" name = "item_id[]" id = "items_dropdown_${newIndex}_value"></input>

                </td>

                <td class="poprod-decpt">
                    <input type="text" id = "items_name_${newIndex}" name = "item_name[${newIndex}]" class="form-control mw-100"   value = "" readonly>
                </td>
                <td class="poprod-decpt" id='attribute_section_${newIndex}'>
                   <button id = "attribute_button_${newIndex}" type = "button" data-bs-toggle="modal" onclick = "setItemAttributes('items_dropdown_${newIndex}', ${newIndex});" data-bs-target="#attribute" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
                   <input type = "hidden" name = "attribute_value_${newIndex}" />
                </td>
                <td>
                   <select class="form-select" name = "uom_id[]" id = "uom_dropdown_${newIndex}">

                   </select>
                </td>
                <td class="d-flex d-none align-items-center"><select class="form-select" name="item_store[${newIndex}]" id="item_store_${newIndex}" oninput="getStoresData(${newIndex})">${storesHTML}</select>
                <div class="me-50 d-none cursor-pointer" onclick="getStoresData(${newIndex},null,null,true)" data-stores='[]' id="data_stores_${newIndex}"><span data-bs-toggle="tooltip" data-bs-placement="top" title="Store Location" class="text-primary"><i data-feather="map-pin"></i></span></div></td>

               <td><input type="text" id = "item_qty_${newIndex}" name = "item_qty[${newIndex}]" oninput = "changeItemQty(this, ${newIndex});" class="form-control item_store_locations mw-100 text-end" onblur = "setFormattedNumericValue(this);"/></td>
              <td><input type="text" id = "item_rate_${newIndex}" name = "item_rate[]" ${docType == 'dnote' ? 'readonly' : ''} oninput = "changeItemRate(this, ${newIndex});" class="form-control mw-100 text-end" onblur = "setFormattedNumericValue(this);"/></td>
               <td><input type="text" id = "item_value_${newIndex}" disabled class="form-control mw-100 text-end item_values_input" /></td>
               <input type = "hidden" id = "header_discount_${newIndex}" value = "0" ></input>
               <input type = "hidden" id = "header_expense_${newIndex}" ></input>
                <td>
                   <div class="position-relative d-flex align-items-center">
                       <input type="text" id = "item_discount_${newIndex}" disabled class="form-control mw-100 text-end item_discounts_input" style="width: 70px" />
                       <div class="ms-50">
                           <button type = "button"  onclick = "onDiscountClick('item_value_${newIndex}', ${newIndex})" data-bs-toggle="modal" data-bs-target="#discount" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Add</button>
                       </div>
                   </div>
               </td>
               <input type="hidden" id = "item_tax_${newIndex}" class="form-control mw-100 text-end item_taxes_input" style="width: 70px" />
               <td><input type="text" id = "value_after_discount_${newIndex}"  disabled class="form-control mw-100 text-end item_val_after_discounts_input" /></td>
               <input type = "hidden" id = "value_after_header_discount_${newIndex}" class = "item_val_after_header_discounts_input" ></input>

                    <input type="hidden" id = "item_total_${newIndex}"  disabled class="form-control mw-100 text-end item_totals_input" />
                    <td>
                    <div class="d-flex">
                        <!-- Hidden Batch Input -->
                        <input type="hidden" id="batches_${newIndex}" name="batch_details[${newIndex}]" value="" />

                        <!-- Batch Button -->
                        <div class="me-50 cursor-pointer addBatchBtn"
                            data-row-count="${newIndex}"
                            data-is-batch-number=""
                            data-is-expiry=""
                            data-bs-toggle="modal"
                            data-bs-target="#item-batch-modal">
                            <span data-bs-toggle="tooltip" data-bs-placement="top" class="text-primary"
                                data-bs-original-title="Item Batch" aria-label="Item Batch">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="feather feather-map-pin">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                            </span>
                        </div>

                        <div class="me-50 cursor-pointer" data-bs-toggle="modal" data-bs-target="#Remarks" onclick = "setItemRemarks('item_remarks_${newIndex}');">        <span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary"><i data-feather="file-text"></i></span></div>
                   </div>
                   </td>
                <input type="hidden" id = "item_remarks_${newIndex}" name = "item_remarks[]"/>
             </tr>
            `;
            tableElementBody.appendChild(newItemRow);
            initializeAutocomplete1("items_dropdown_" + newIndex, newIndex);
            renderIcons();
            disableHeader();

            const rateInput = document.getElementById('item_rate_' + newIndex);
            const qtyInput = document.getElementById('item_qty_' + newIndex);

            rateInput.addEventListener('input', function() {
                getStoresData(newIndex);
            });
            // qtyInput.addEventListener('input', function() {
            //     const newQty = this.value;
            //     getStoresData(newIndex, newQty);
            // });
        }

        function deleteItemRows()
        {
            var deletedItemIds = JSON.parse(localStorage.getItem('deletedSiItemIds'));
            const allRowsCheck = document.getElementsByClassName('item_row_checks');
            let deleteableElementsId = [];
            for (let index = allRowsCheck.length - 1; index >= 0; index--) {  // Loop in reverse order
                if (allRowsCheck[index].checked) {
                    const currentRowIndex = allRowsCheck[index].getAttribute('del-index');
                    const currentRow = document.getElementById('item_row_' + index);
                    if (currentRow) {

                        if (currentRow.getAttribute('data-id')) {
                            deletedItemIds.push(currentRow.getAttribute('data-id'));
                        }
                        deleteableElementsId.push('item_row_' + currentRowIndex);
                    }
                }
            }
            for (let index = 0; index < deleteableElementsId.length; index++) {
                document.getElementById(deleteableElementsId[index])?.remove();
            }
            localStorage.setItem('deletedSiItemIds', JSON.stringify(deletedItemIds));
            const allRowsNew = document.getElementsByClassName('item_row_checks');
            if (allRowsNew.length > 0) {
                for (let idx = 0; idx < allRowsNew.length; idx++) {
                    const currentRowIndex = allRowsCheck[idx].getAttribute('del-index');
                    if (document.getElementById('item_row_' + currentRowIndex)) {
                        itemRowCalculation(currentRowIndex);
                        updateHeaderDiscounts();
                        updateHeaderExpenses();
                    }
                }
                disableHeader();
            } else {
                const allItemsHeaderDiscount = document.getElementsByClassName('order_discount_hidden_fields');
                const allItemsHeaderExpense = document.getElementsByClassName('order_expense_hidden_fields');
                document.querySelectorAll('.order_discount_hidden_fields, .order_expense_hidden_fields, .order_expenses, .order_discounts').forEach(el => el.remove());
                $('#order_discount_row').remove();
                $('#all_items_total_expenses_summary').val('0.00');
                current_doc_id = 0;
                $("#refernece_id_input").val('');
                $("#reference_doc_type").val('');
                document.getElementById('all_items_total_value').innerText = '0.00';
                document.getElementById('total_order_discount').innerText = '0.00';
                document.getElementById('total_order_expense').innerText = '0.00';
                document.getElementById('all_items_total_discount').innerText = '0.00';
                document.getElementById('all_items_total_expenses_summary').innerText = '0.00';

                // for (let i = 0; i < allItemsHeaderDiscount.length; i++) {
                //     allItemsHeaderDiscount[i].remove();
                // }
                // for (let j = 0; j < allItemsHeaderExpense.length; j++) {
                //     allItemsHeaderExpense[j].remove();
                // }
                onItemClick(-1);
                enableHeader();
                setAllTotalFields();
            }

        }


        function changeItemValue(index) // Single Item Value
        {
            const currentElement = document.getElementById('item_value_' + index);
            if (currentElement) {
                const currentQty = document.getElementById('item_qty_' + index).value;
                const currentRate = document.getElementById('item_rate_' + index).value;
                currentElement.value = (parseFloat(currentRate ? currentRate : 0) * parseFloat(currentQty ? currentQty : 0)).toFixed(2);
            }
            getItemTax(index);
            changeItemTotal(index);
            changeAllItemsTotal();
            changeAllItemsTotalTotal();
        }

        function changeItemTotal(index) //Single Item Total
        {
            const currentElementValue = document.getElementById('item_value_' + index).value;
            const currentElementDiscount = document.getElementById('item_discount_' + index).value;
            const newItemTotal = (parseFloat(currentElementValue ? currentElementValue : 0) - parseFloat(currentElementDiscount ? currentElementDiscount : 0)).toFixed(2);
            document.getElementById('item_total_' + index).value = newItemTotal;

        }

        function changeAllItemsValue()
        {

        }

        function addDiscount(render = true)
        {
            const discountName = document.getElementById('new_discount_name').value;
            const discountId = document.getElementById('new_discount_id').value;
            // const discountType = document.getElementById('new_discount_type').value;
            const discountPercentage = document.getElementById('new_discount_percentage').value;
            const discountValue = document.getElementById('new_discount_value').value;

            const itemRowIndex = document.getElementById('discount_main_table').getAttribute('item-row-index');

            //Check if newly added discount is greater than actual item value
            var existingItemDiscount = document.getElementById('item_discount_' + itemRowIndex).value;
            existingItemDiscount = parseFloat(existingItemDiscount ? existingItemDiscount : 0);
            var newDiscountVal = parseFloat(discountValue ? discountValue : 0);
            const newItemDiscountTotal = existingItemDiscount + newDiscountVal;
            var itemValueTotal = document.getElementById('item_value_' + itemRowIndex).value;
            itemValueTotal = parseFloat(itemValueTotal ? itemValueTotal : 0);
            if (newItemDiscountTotal > itemValueTotal) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Discount cannot be greater than Item Value',
                    icon: 'error',
                });
                return;
            }

            if (discountName && discountId && (discountPercentage || discountValue)) //All fields filled
            {
                // const previousElements = document.getElementsByClassName('item_discounts');
                // const newIndex = previousElements.length ? previousElements.length: 0;
                const ItemRowIndexVal = document.getElementById('discount_main_table').getAttribute('item-row-index');

                const previousHiddenFields = document.getElementsByClassName('discount_names_hidden_' + ItemRowIndexVal);
                addDiscountHiddenInput(document.getElementById('discount_main_table').getAttribute('item-row'), ItemRowIndexVal, previousHiddenFields.length ? previousHiddenFields.length : 0, render);
            }
            else
            {
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please enter all the discount details',
                    icon: 'warning',
                });
            }

        }

        function addOrderDiscount(dataId = null, enableExceedCheck = true)
        {
            const discountName = document.getElementById('new_order_discount_name').value;
            const discountId = document.getElementById('new_order_discount_id').value;
            const discountPercentage = document.getElementById('new_order_discount_percentage').value;
            const discountValue = document.getElementById('new_order_discount_value').value;
            if (discountName && discountId && (discountPercentage || discountValue)) //All fields filled
            {
                var existingOrderDiscount = document.getElementById('order_discount_summary') ? document.getElementById('order_discount_summary').textContent : 0;
                existingOrderDiscount = parseFloat(existingOrderDiscount ? existingOrderDiscount.replace(/,/g, '') : 0);
                var newOrderDiscountVal = parseFloat(discountValue ? discountValue : 0);

                var actualNewOrderDiscount = existingOrderDiscount + newOrderDiscountVal;
                var totalItemsValue = document.getElementById('all_items_total_total') ? document.getElementById('all_items_total_total').textContent : 0;
                totalItemsValue = parseFloat(totalItemsValue ? totalItemsValue.replace(/,/g, '') : 0);
                if (actualNewOrderDiscount > totalItemsValue && enableExceedCheck) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Discount cannot be greater than Total Item Value',
                        icon: 'error',
                    });
                    return;
                }
                const previousHiddenFields = document.getElementsByClassName('order_discount_value_hidden');
                addOrderDiscountHiddenInput(previousHiddenFields.length ? previousHiddenFields.length : 0, dataId);
            } else {
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please enter all the discount details',
                    icon: 'warning',
                });
                return;
            }

        }
        function addOrderExpense(dataId = null, enableExceedCheck = true)
        {
            const expenseName = document.getElementById('order_expense_name').value;
            const expenseId = document.getElementById('order_expense_id').value;
            const expensePercentage = document.getElementById('order_expense_percentage').value;
            const expenseValue = document.getElementById('order_expense_value').value;


            if (expenseName && expenseId && (expensePercentage || expenseValue)) //All fields filled
            {
                var existingOrderExpense = document.getElementById('all_items_total_expenses_summary') ? document.getElementById('all_items_total_expenses_summary').textContent : 0;
                existingOrderExpense = parseFloat(existingOrderExpense ? existingOrderExpense : 0);
                var newOrderExpenseVal = parseFloat(expenseValue ? expenseValue : 0);

                var actualNewOrderExpense = existingOrderExpense + newOrderExpenseVal;
                var totalItemsValueAfterTax = document.getElementById('all_items_total_after_tax_summary') ? document.getElementById('all_items_total_after_tax_summary').textContent : 0;
                totalItemsValueAfterTax = parseFloat(totalItemsValueAfterTax.replace(/,/g, ''));
                // if (actualNewOrderExpense > totalItemsValueAfterTax && enableExceedCheck) {
                //     Swal.fire({
                //         title: 'Error!',
                //         text: 'Expense cannot exceed 100%',
                //         icon: 'error',
                //     });
                //     return;
                // }

                const previousHiddenFields = document.getElementsByClassName('order_expense_value_hidden');
                addOrderExpenseHiddenInput(previousHiddenFields.length ? previousHiddenFields.length : 0, dataId);
            } else {
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please enter all the expense details',
                    icon: 'warning',
                });
                return;
            }

        }
        function addDiscountInTable(ItemRowIndexVal, render = true)
        {
                const previousHiddenNameFields = document.getElementsByClassName('discount_names_hidden_' + ItemRowIndexVal);
                const previousHiddenPercentageFields = document.getElementsByClassName('discount_percentages_hidden_' + ItemRowIndexVal)?document.getElementsByClassName('discount_percentages_hidden_' + ItemRowIndexVal):" ";
                const previousHiddenValuesFields = document.getElementsByClassName('discount_values_hidden_' + ItemRowIndexVal);


                const newIndex = previousHiddenNameFields.length ? previousHiddenNameFields.length : 0;
                var newData = ``;
                for (let index = newIndex- 1; index < previousHiddenNameFields.length; index++) {
                    const newHTML = document.getElementById('discount_main_table').insertRow(index + 2);
                    newHTML.className = "item_discounts";
                    newHTML.id = "item_discount_modal_" + index;
                    newData = `
                        <td>${index+1}</td>
                        <td>${previousHiddenNameFields[index].value}</td>
                        <td>${previousHiddenPercentageFields[index] ?previousHiddenPercentageFields[index].value:""}</td>
                        <td class = "dynamic_discount_val_${ItemRowIndexVal}">${previousHiddenValuesFields[index].value}</td>
                        <td>
                            <a href="#" class="text-danger" onclick = "removeDiscount(${index}, ${ItemRowIndexVal});"><i data-feather="trash-2"></i></a>
                        </td>
                    `;
                    newHTML.innerHTML = newData;
                }

                document.getElementById('new_discount_name').value = "";
                // document.getElementById('new_discount_type').value = "";
                document.getElementById('new_discount_percentage').value = "";
                document.getElementById('new_discount_percentage').disabled = false;
                document.getElementById('new_discount_value').value = "";
                document.getElementById('new_discount_value').disabled = false;
                if (render) {
                    // setModalDiscountTotal('item_discount_' + ItemRowIndexVal, ItemRowIndexVal);
                    itemRowCalculation(ItemRowIndexVal);
                }



                renderIcons();
        }

        function getTotalorderDiscounts(itemCalculation = true)
        {
            const values = document.getElementsByClassName('order_discount_value_hidden');
            let discount = 0;
            for (let index = 0; index < values.length; index++) {
                discount += parseFloat(values[index].value ? values[index].value : 0);
            }
            const summaryTable = document.getElementById('summary_table');
            if (discount > 0) { //Add in summary
                const discountRow = document.getElementById('order_discount_row');
                if (discountRow) {
                    discountRow.innerHTML = `
                        <td width="55%">Header Discount</td>
                        <td class="text-end" id = "order_discount_summary" >${discount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    `
                } else {
                    const newRow = summaryTable.insertRow(3);
                    newRow.id = "order_discount_row";
                    newRow.innerHTML = `
                    <td width="55%">Header Discount</td>
                        <td class="text-end" id = "order_discount_summary" >${discount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    `;
                }
            } else {// Remove from summary
                let lastDiscountRow = document.getElementById('order_discount_row');
                if (lastDiscountRow) {
                    lastDiscountRow.remove();
                }
            }
            document.getElementById('total_order_discount').textContent = parseFloat(discount ? discount : 0);
            if (itemCalculation)
            {
                const itemData = document.getElementsByClassName('item_header_rows');
                for (let ix = 0; ix < itemData.length; ix++) {
                    itemRowCalculation(ix);
                }
            }
        }

        function getTotalOrderExpenses()
        {
            const values = document.getElementsByClassName('order_expense_value_hidden');
            let expense = 0;
            for (let index = 0; index < values.length; index++) {
                expense += parseFloat(values[index].value ? values[index].value : 0);
            }
            document.getElementById('all_items_total_expenses_summary').textContent = parseFloat(expense ? expense : 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('total_order_expense').textContent = parseFloat(expense ? expense : 0);
            setAllTotalFields();
        }

        function addOrderDiscountInTable(index)
        {
            const previousHiddenNameFields = document.getElementsByClassName('order_discount_name_hidden');
            const previousHiddenPercentageFields = document.getElementsByClassName('order_discount_percentage_hidden')?document.getElementsByClassName('order_discount_percentage_hidden'):" ";
            const previousHiddenValuesFields = document.getElementsByClassName('order_discount_value_hidden');
            const previousHiddenIdsFields = document.getElementsByClassName('order_discount_id_hidden');


            const newIndex = previousHiddenNameFields.length ? previousHiddenNameFields.length : 0;

            var newData = ``;
            var totalSummaryDiscount = 0;
            var total = parseFloat(document.getElementById('total_order_discount').textContent ? document.getElementById('total_order_discount').textContent : 0);
            for (let index = newIndex - 1; index < previousHiddenNameFields.length; index++) {
                const newHTML = document.getElementById('order_discount_main_table').insertRow(index+2);
                newHTML.className = "order_discounts";
                newHTML.id = "order_discount_modal_" + (parseInt(newIndex - 1));
                if(previousHiddenIdsFields[index]){
                    newHTML.setAttribute('data-id',previousHiddenIdsFields[index].value);
                }
                newData = `
                <td>${index+1}</td>
                <td>${previousHiddenNameFields[index].value}</td>
                <td>${previousHiddenPercentageFields[index] ?previousHiddenPercentageFields[index].value:''}</td>
                <td id = "order_discount_input_val_${index}">${previousHiddenValuesFields[index].value}</td>
                <td>
                        <a href="#" class="text-danger" onclick = "removeOrderDiscount(${newIndex - 1});"><i data-feather="trash-2"></i></a>
                    </td>
                    `;
                newHTML.innerHTML = newData;
                total+= parseFloat(previousHiddenValuesFields[index].value ? previousHiddenValuesFields[index].value : 0);
                totalSummaryDiscount += parseFloat(previousHiddenValuesFields[index].value ? previousHiddenValuesFields[index].value : 0);
            }

            document.getElementById('new_order_discount_name').value = "";
            document.getElementById('new_order_discount_percentage').value = "";
            document.getElementById('new_order_discount_percentage').disabled = false;
            document.getElementById('new_order_discount_value').value = "";
            document.getElementById('new_order_discount_value').disabled = false;
            document.getElementById('total_order_discount').textContent = total;
            renderIcons();

            getTotalorderDiscounts();
        }


        function addOrderExpenseInTable(index)
        {
            const previousHiddenNameFields = document.getElementsByClassName('order_expense_name_hidden');
            const previousHiddenPercentageFields = document.getElementsByClassName('order_expense_percentage_hidden');
            const previousHiddenValuesFields = document.getElementsByClassName('order_expense_value_hidden');

            const previousHiddenIdsFields = document.getElementsByClassName('order_expense_id_hidden');
            const newIndex = previousHiddenNameFields.length ? previousHiddenNameFields.length : 0;

            var newData = ``;
            var totalSummaryExpense = 0;
            var total = parseFloat(document.getElementById('total_order_expense').textContent ? document.getElementById('total_order_expense').textContent : 0);
            for (let index = newIndex - 1; index < previousHiddenNameFields.length; index++) {
                const newHTML = document.getElementById('order_expense_main_table').insertRow(index+2);
                newHTML.className = "order_expenses";
                newHTML.id = "order_expense_modal_" + (parseInt(newIndex - 1));
                if(previousHiddenIdsFields[index]){
                    newHTML.setAttribute('data-id',previousHiddenIdsFields[index].value);
                }
                newData = `
                    <td>${index+1}</td>
                    <td>${previousHiddenNameFields[index].value}</td>
                    <td>${previousHiddenPercentageFields[index] ? previousHiddenPercentageFields[index].value:''}</td>
                    <td>${previousHiddenValuesFields[index].value}</td>
                    <td>
                        <a href="#" class="text-danger" onclick = "removeOrderExpense(${newIndex - 1});"><i data-feather="trash-2"></i></a>
                    </td>
                `;
                total+= parseFloat(previousHiddenValuesFields[index].value ? previousHiddenValuesFields[index].value : 0);
                newHTML.innerHTML = newData;
                totalSummaryExpense += parseFloat(previousHiddenValuesFields[index].value ? previousHiddenValuesFields[index].value : 0);
            }


            document.getElementById('order_expense_name').value = "";
            document.getElementById('order_expense_percentage').value = "";
            document.getElementById('order_expense_percentage').disabled = false;
            document.getElementById('order_expense_value').value = "";
            document.getElementById('order_expense_value').disabled = false;
            document.getElementById('total_order_expense').textContent = total;

            renderIcons();

            getTotalOrderExpenses();
        }

        function removeDiscount(index, itemIndex)
        {
            let deletedDiscountTedIds = JSON.parse(localStorage.getItem('deletedItemDiscTedIds'));
            const removableElement = document.getElementById('item_discount_modal_' + index);
            if (removableElement) {
                if (removableElement.getAttribute('data-id')) {
                    deletedDiscountTedIds.push(element.getAttribute('data-id'));
                }
                removableElement.remove();
            }
            document.getElementById("item_discount_name_" + itemIndex + "_" + index)?.remove();
            document.getElementById("item_discount_percentage_" + itemIndex + "_" + index)?.remove();
            document.getElementById("item_discount_value_" + itemIndex + "_" + index)?.remove();
            localStorage.setItem('deletedItemDiscTedIds', JSON.stringify(deletedDiscountTedIds));
            renderPreviousDiscount(itemIndex);
            // setModalDiscountTotal('item_discount_' + itemIndex, itemIndex);
            itemRowCalculation(itemIndex);
        }
        function removeOrderDiscount(index)
        {
            let deletedHeaderDiscTedIds = JSON.parse(localStorage.getItem('deletedHeaderDiscTedIds'));
            const removableElement = document.getElementById('order_discount_modal_' + index);
            if (removableElement) {
                if (removableElement.getAttribute('data-id')) {
                    deletedHeaderDiscTedIds.push(element.getAttribute('data-id'));
                }
                removableElement.remove();
            }
            document.getElementById("order_discount_name_" + index).remove();
            document.getElementById("order_discount_percentage_" + index).remove();
            document.getElementById("order_discount_value_" + index).remove();
            localStorage.setItem('deletedHeaderDiscTedIds', JSON.stringify(deletedHeaderDiscTedIds));
            // renderPreviousDiscount(itemIndex);
            // setModalDiscountTotal('item_discount_' + itemIndex, itemIndex);
            getTotalorderDiscounts();

        }
        function removeOrderExpense(index)
        {
            let deletedHeaderExpTedIds = JSON.parse(localStorage.getItem('deletedHeaderExpTedIds'));
            const removableElement = document.getElementById('order_expense_modal_' + index);
            if (removableElement) {
                removableElement.remove();
                if (removableElement.getAttribute('data-id')) {
                    deletedHeaderExpTedIds.push(removableElement.getAttribute('data-id'));
                }
            }
            document.getElementById("order_expense_name_" + index)?.remove();
            document.getElementById("order_expense_percentage_" + index)?.remove();
            document.getElementById("order_expense_value_" + index)?.remove();
            localStorage.setItem('deletedHeaderExpTedIds', JSON.stringify(deletedHeaderExpTedIds));
            getTotalOrderExpenses();
            // renderPreviousDiscount(itemIndex);
            // setModalDiscountTotal('item_discount_' + itemIndex, itemIndex);
        }

        function changeDiscountType(element)
        {
            if (element.value === "Fixed") {
                document.getElementById('new_discount_percentage').disabled = true;
                document.getElementById('new_discount_percentage').value = "";
                document.getElementById('new_discount_value').disabled = false;
                document.getElementById('new_discount_value').value = "";
            } else {
                document.getElementById('new_discount_percentage').disabled = false;
                document.getElementById('new_discount_percentage').value = "";
                document.getElementById('new_discount_value').disabled = true;
                document.getElementById('new_discount_value').value = "";
            }
        }

        function onDiscountClick(elementId, itemRowIndex)
        {
            const totalValue = document.getElementById(elementId).value;
            document.getElementById('discount_main_table').setAttribute('total-value', totalValue);
            document.getElementById('discount_main_table').setAttribute('item-row', elementId);
            document.getElementById('discount_main_table').setAttribute('item-row-index', itemRowIndex);
            initializeAutocompleteTed("new_discount_name", "new_discount_id", 'sales_module_discount', 'new_discount_percentage');

            renderPreviousDiscount(itemRowIndex);
        }

        function renderPreviousDiscount(ItemRowIndexVal)
        {
            const previousHiddenNameFields = document.getElementsByClassName('discount_names_hidden_' + ItemRowIndexVal);
            const previousHiddenPercentageFields = document.getElementsByClassName('discount_percentages_hidden_' + ItemRowIndexVal)?document.getElementsByClassName('discount_percentages_hidden_' + ItemRowIndexVal):" ";
            const previousHiddenValuesFields = document.getElementsByClassName('discount_values_hidden_' + ItemRowIndexVal);

                const oldDiscounts = document.getElementsByClassName('item_discounts');
                if (oldDiscounts && oldDiscounts.length > 0)
                {
                    while (oldDiscounts.length > 0) {
                        oldDiscounts[0].remove();
                    }
                }

            var newData = ``;
            for (let index = 0; index < previousHiddenNameFields.length; index++) {
                const newHTML = document.getElementById('discount_main_table').insertRow(index + 2);
                newHTML.id = "item_discount_modal_" + index;
                newHTML.className = "item_discounts";
                newData = `
                    <td>${index+1}</td>
                    <td>${previousHiddenNameFields[index].value}</td>
                    <td>${previousHiddenPercentageFields[index] ? previousHiddenPercentageFields[index].value:''}</td>
                    <td class = "dynamic_discount_val_${ItemRowIndexVal}">${previousHiddenValuesFields[index].value}</td>
                    <td>
                        <a href="#" class="text-danger" onclick = "removeDiscount(${index}, ${ItemRowIndexVal});"><i data-feather="trash-2"></i></a>
                    </td>
                `;
                newHTML.innerHTML = newData;
            }

            document.getElementById('new_discount_name').value = "";
            // document.getElementById('new_discount_type').value = "";
            document.getElementById('new_discount_percentage').value = "";
            document.getElementById('new_discount_percentage').disabled = false;
            document.getElementById('new_discount_value').value = "";
            document.getElementById('new_discount_value').disabled = false;
            setModalDiscountTotal('item_discount_' + ItemRowIndexVal, ItemRowIndexVal);
            itemRowCalculation(ItemRowIndexVal);

            renderIcons();
        }

        function onChangeDiscountPercentage(element)
        {
            document.getElementById('new_discount_value').disabled = element.value ? true : false;
            const totalValue = document.getElementById('item_value_' + document.getElementById('discount_main_table').getAttribute('item-row-index')).value;
            if (totalValue) {
                document.getElementById('new_discount_value').value = (parseFloat(totalValue) * parseFloat(element.value/100)).toFixed(2);
            }
        }
        function onChangeOrderDiscountPercentage(element)
        {
            document.getElementById('new_order_discount_value').disabled = element.value ? true : false;
            const totalValue = document.getElementById('all_items_total_value_summary').textContent;
            const totalItemDiscount =document.getElementById('all_items_total_discount_summary').textContent;

            let totalAfterItemDiscount = parseFloat(totalValue ? totalValue.replace(/,/g, '') : 0) - parseFloat(totalItemDiscount ? totalItemDiscount.replace(/,/g, '') : 0);
            if (totalAfterItemDiscount) {
                document.getElementById('new_order_discount_value').value = (parseFloat(totalAfterItemDiscount ? totalAfterItemDiscount : 0) * parseFloat(element.value/100)).toFixed(2);
            }
        }
        function onChangeOrderExpensePercentage(element)
        {
            document.getElementById('order_expense_value').disabled = element.value ? true : false;
            const totalValue = document.getElementById('all_items_total_after_tax_summary').textContent.replace(/,/g, '');
            if (totalValue) {
                document.getElementById('order_expense_value').value = (parseFloat(totalValue ? totalValue : 0) * parseFloat(element.value/100)).toFixed(2);
            }
        }
        function onChangeDiscountValue(element)
        {
            document.getElementById('new_discount_percentage').disabled = element.value ? true : false;
        }

        function onChangeOrderDiscountValue(element)
        {
            document.getElementById('new_order_discount_percentage').disabled = element.value ? true : false;
        }
        function onChangeOrderExpenseValue(element)
        {
            document.getElementById('order_expense_percentage').disabled = element.value ? true : false;
        }

        function setModalDiscountTotal(elementId, index)
        {
            var totalDiscountModalVal = 0;
            const docs = document.getElementsByClassName('discount_values_hidden_' + index);
            for (let index = 0; index < docs.length; index++) {
                totalDiscountModalVal += parseFloat(docs[index].value ? docs[index].value : 0);
            }
            document.getElementById('total_item_discount').textContent = totalDiscountModalVal.toFixed(2);
            document.getElementById(elementId).value = totalDiscountModalVal.toFixed(2);
            // changeItemTotal(index);
            // changeAllItemsDiscount();
            // itemRowCalculation(index);
        }

        function addDiscountHiddenInput(itemRow, index, discountIndex, render = true)
        {
            addHiddenInput("item_discount_name_" + index + "_" + discountIndex, document.getElementById('new_discount_name').value, `item_discount_name[${index}][${discountIndex}]`, 'discount_names_hidden_' + index, itemRow);
            addHiddenInput("item_discount_master_id_" + index + "_" + discountIndex, document.getElementById('new_discount_id').value, `item_discount_master_id[${index}][${discountIndex}]`, 'discount_master_id_hidden_' + index, itemRow);
            // addHiddenInput("item_discount_type_" + index + "_" + discountIndex, document.getElementById('new_discount_type').value, `item_discount_type[${index}][${discountIndex}]`, 'discount_types_hidden_' + index, itemRow);
            addHiddenInput("item_discount_percentage_" + index + "_" + discountIndex, document.getElementById('new_discount_percentage').value, `item_discount_percentage[${index}][${discountIndex}]`, 'discount_percentages_hidden_' + index,  itemRow);
            addHiddenInput("item_discount_value_" + index + "_" + discountIndex, document.getElementById('new_discount_value').value, `item_discount_value[${index}][${discountIndex}]`, 'discount_values_hidden_' + index, itemRow);
            addDiscountInTable(index, render);
        }

        function addOrderDiscountHiddenInput(index, dataId = null)
        {
            addHiddenInput("order_discount_name_" + index, document.getElementById('new_order_discount_name').value, `order_discount_name[${index}]`, 'order_discount_hidden_fields order_discount_name_hidden', 'main_so_form');
            addHiddenInput("order_discount_master_id_" + index, document.getElementById('new_order_discount_id').value, `order_discount_master_id[${index}]`, 'order_discount_hidden_fields order_discount_master_id_hidden', 'main_so_form', dataId);
            addHiddenInput("order_discount_percentage_" + index, document.getElementById('new_order_discount_percentage').value, `order_discount_percentage[${index}]`, 'order_discount_hidden_fields order_discount_percentage_hidden', 'main_so_form');
            addHiddenInput("order_discount_value_" + index, document.getElementById('new_order_discount_value').value, `order_discount_value[${index}]`, 'order_discount_hidden_fields order_discount_value_hidden', 'main_so_form');
            if (dataId) {
                addHiddenInput("order_discount_id_" + index, dataId, `order_discount_id[${index}]`, 'order_discount_hidden_fields order_discount_id_hidden', 'main_so_form');
            }
            addOrderDiscountInTable(index);
        }

        function addOrderExpenseHiddenInput(index, dataId = null)
        {
            addHiddenInput("order_expense_name_" + index, document.getElementById('order_expense_name').value, `order_expense_name[${index}]`, 'order_expense_hidden_fields order_expense_name_hidden', 'main_so_form');
            addHiddenInput("order_expense_master_id_" + index, document.getElementById('order_expense_id').value, `order_expense_master_id[${index}]`, 'order_expense_hidden_fields order_expense_master_id_hidden', 'main_so_form', dataId);
            addHiddenInput("order_expense_percentage_" + index, document.getElementById('order_expense_percentage').value, `order_expense_percentage[${index}]`, 'order_expense_hidden_fields order_expense_percentage_hidden', 'main_so_form');
            addHiddenInput("order_expense_value_" + index, document.getElementById('order_expense_value').value, `order_expense_value[${index}]`, 'order_expense_hidden_fields order_expense_value_hidden', 'main_so_form');
            if (dataId) {
                addHiddenInput("order_expense_id_" + index, dataId, `order_expense_id[${index}]`, 'order_expense_hidden_fields order_expense_id_hidden', 'main_so_form');
            }
            addOrderExpenseInTable(index);
        }
        function initializeAutocomplete1(selector, index) {
            $("#" + selector).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: 'batch_items',
                            customer_id: null,
                            header_book_id: $("#series_id_input").val()
                        },
                        success: function (data) {
                            response($.map(data, function (item) {
                                return {
                                    id: item.id,
                                    label: `${item.item_name} (${item.item_code})`,
                                    code: item.item_code || '',
                                    rate: item.rate || null,
                                    item_id: item.id,
                                    uom: item.uom,
                                    alternateUoms: item.alternate_u_o_ms,
                                    specifications: item.specifications,
                                    is_asset: item.is_asset,
                                    asset_name: item.item_name,
                                    asset_category_id: item.asset_category_id,
                                    asset_category_name: item.asset_category?.name,
                                    brand_name: item.brand_name,
                                    model_no: item.model_no,
                                    estimated_life: item.expected_life,
                                    salvage_percentage: item.salvage_percentage ?? 0,
                                    salvage_value: 0,
                                    procurement_type: 'BUY',
                                    is_batch_number: item.is_batch_no,
                                    is_expiry: item.is_expiry,
                                    is_attr: item.is_attr,
                                    hsn_id: item.hsn_id,
                                    hsn_code: item.hsn_code,
                                    is_inspection: item.is_inspection
                                };
                            }));
                        },
                        error: function (xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                minLength: 0,
                select: function (event, ui) {
                    let $input = $(this);
                    let closestTr = $input.closest("tr");

                    // --- Fill inputs ---
                    $input.attr("data-name", ui.item.label);
                    $input.attr("data-code", ui.item.code);
                    $input.attr("data-id", ui.item.item_id);
                    $input.attr("specs", JSON.stringify(ui.item.specifications));
                    $input.val(ui.item.code);

                    // Hidden item_id
                    closestTr.find(`#${selector}_value`).val(ui.item.item_id);

                    // Item name field
                    closestTr.find(`#items_name_${index}`).val(ui.item.label);
                    closestTr.find(`#item_rate_${index}`).val(ui.item.rate ?? 0);

                    // HSN & Inspection (if you have hidden fields for them, adapt here)
                    closestTr.find('[name*=hsn_id]').val(ui.item.hsn_id || '');
                    closestTr.find('[name*=hsn_code]').val(ui.item.hsn_code || '');

                    // --- UOM Dropdown ---
                    const uomDropdown = document.getElementById('uom_dropdown_' + index);
                    let uomId = ui.item.uom?.id || ui.item.uom_id;
                    let uomName = ui.item.uom?.alias || ui.item.uom_name;
                    let uomOption = `<option value="${uomId}" selected>${uomName}</option>`;

                    if (ui.item.alternateUoms) {
                        ui.item.alternateUoms.forEach(alterItem => {
                            uomOption += `<option value="${alterItem.uom_id}" ${alterItem.is_purchasing ? 'selected' : ''}>${alterItem.uom?.name}</option>`;
                        });
                    }
                    if (uomDropdown) {
                        uomDropdown.innerHTML = uomOption;
                    }
                    // --- Batch setup ---
                    closestTr.find('.addBatchBtn')
                        .attr("data-is-batch-number", ui.item.is_batch_number)
                        .attr("data-is-expiry", ui.item.is_expiry);

                    // --- Asset setup ---
                    if (ui.item.is_asset === 1) {
                        const assetPayload = {
                            asset_id: null,
                            asset_name: ui.item.asset_name ?? '',
                            asset_category_id: ui.item.asset_category_id ?? null,
                            asset_category_name: ui.item.asset_category_name ?? '',
                            asset_code: null,
                            brand_name: ui.item.brand_name ?? '',
                            model_no: ui.item.model_no ?? '',
                            estimated_life: ui.item.estimated_life ?? '',
                            salvage_percentage: ui.item.salvage_percentage ?? 0,
                            procurement_type: ui.item.procurement_type ?? null,
                            capitalization_date: new Date().toISOString().split('T')[0]
                        };
                        closestTr.find(`#assetDetailData_${index}`).val(JSON.stringify(assetPayload));
                        closestTr.find('.assetDetailBtn')
                            .removeClass('d-none')
                            .attr('data-asset', JSON.stringify(assetPayload));
                    } else {
                        closestTr.find(`#assetDetailData_${index}`).val('');
                        closestTr.find('.assetDetailBtn')
                            .addClass('d-none')
                            .removeAttr('data-asset');
                    }

                    // --- Attributes ---
                    itemOnChange(selector, index, '/item/attributes/');

                    setTimeout(() => {
                        if (ui.item.is_attr) {
                            $(`#attribute_button_${index}`).trigger("click");
                        } else {
                            $(`#attribute_button_${index}`).trigger("click");
                            $(`#item_qty_${index}`).val('').focus();
                        }
                    }, 100);

                    return false;
                },
                change: function (event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $(this).attr("data-name", "");
                        $(this).attr("data-code", "");
                        $(this).attr("data-id", "");
                    }
                }
            }).focus(function () {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
        }

    initializeAutocomplete1("items_dropdown_0", 0);

    function initializeAutocompleteCustomer(selector) {
        $("#" + selector).autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: '/search',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        q: request.term,
                        type:'customer_list'
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                label: `${item.company_name} (${item.customer_code})`,
                                code: item.customer_code || '',
                                item_id: item.id,
                                payment_terms_id : item?.payment_terms?.id,
                                payment_terms : item?.payment_terms?.name,
                                payment_terms_code : item?.payment_terms?.name,
                                currency_id : item?.currency?.id,
                                currency : item?.currency?.name,
                                currency_code : item?.currency?.short_name,
                                type : item?.customer_type,
                                phone_no : item?.mobile ?? (item?.phone ?? null),
                                email : item?.email,
                                gstin : item?.compliances?.gstin_no,
                                credit_days : item?.credit_days,
                                credit_days_editable : item?.credit_days_editable
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
                var paymentTermsId = ui.item.payment_terms_id;
                var paymentTerms = ui.item.payment_terms;
                var paymentTermsCode = ui.item.payment_terms_code;
                var currencyId = ui.item.currency_id;
                var currency = ui.item.currency;
                var currencyCode = ui.item.currency_code;
                var customerType = ui.item.type;
                var phoneNo = ui.item.phone_no;
                var email = ui.item.email;
                var gstIn = ui.item.gstin;
                var creditDays = ui.item.credit_days ? ui.item.credit_days : 0;
                var creditDaysEdit = ui.item.credit_days_editable;
                $input.attr('customer_type', customerType);
                $input.attr('phone_no', phoneNo);
                $input.attr('email', email);
                $input.attr('gstin', gstIn);
                $input.attr('payment_terms_id', paymentTermsId);
                $input.attr('payment_terms', paymentTerms);
                $input.attr('payment_terms_code', paymentTermsCode);
                $input.attr('currency_id', currencyId);
                $input.attr('currency', currency);
                $input.attr('currency_code', currencyCode);
                //Set Credit Days
                $("#credit_days_input").val(creditDays);
                if (creditDaysEdit) {
                    $("#credit_days_input").removeAttr("readonly");
                } else {
                    $("#credit_days_input").attr("readonly", true);
                }
                $input.val(ui.item.label);
                $("#customer_code_input_hidden").val(ui.item.code);
                document.getElementById('customer_id_input').value = ui.item.id;
                onChangeCustomer(selector);
                if (customerType === 'Cash') {
                    initializeCashCustomerPhoneDropdown();
                } else {
                    deInitializeCashCustomerFlow();
                }
                return false;
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#customer_code_input_hidden").val("");
                }
            }
        }).focus(function() {
            if (this.value === "") {
                $(this).autocomplete("search", "");
            }
        });
    }

    function initializeAutocompleteConsignee(selector) {
        $("#" + selector).autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "{{ route('sales.autoComplete.customer.consignee') }}",
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        q: request.term,
                    },
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                label: `${item.consignee_name}`,
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
                
                $input.val(ui.item.label);
                document.getElementById('consignee_id_input').value = ui.item.id;
                onChangeConsignee('consignee_name_input');
                return false;
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $(this).val("");
                    $("#consignee_name_input").val("");
                }
            }
        }).focus(function() {
            if (this.value === "") {
                $(this).autocomplete("search", "");
            }
        });
    }

    initializeAutocompleteCustomer('customer_code_input');
    initializeAutocompleteConsignee('consignee_name_input');

    
    function checkItemAddValidation()
    {
        let addRow = $('#series_id_input').val &&  $('#order_no_input').val && $('#order_date_input').val && $('#customer_code_input').val;
        return addRow;
    }

    var taxInputs = [];

    function itemRowCalculation(itemRowIndex)
    {
        const itemQtyInput = document.getElementById('item_qty_' + itemRowIndex);
        const itemRateInput = document.getElementById('item_rate_' + itemRowIndex);
        const itemValueInput = document.getElementById('item_value_' + itemRowIndex);
        const itemDiscountInput = document.getElementById('item_discount_' + itemRowIndex);
        const itemTotalInput = document.getElementById('item_total_' + itemRowIndex);
        //ItemValue
        const itemValue = parseFloat(itemQtyInput.value ? itemQtyInput.value : 0) * parseFloat(itemRateInput.value ? itemRateInput.value : 0);
        itemValueInput.value = (itemValue).toFixed(2);
        //Discount
        let discountAmount = 0;
        const discountHiddenPercentageFields = document.getElementsByClassName('discount_percentages_hidden_' + itemRowIndex);
        const discountHiddenValuesFields = document.getElementsByClassName('discount_values_hidden_' + itemRowIndex);
        const mainDiscountInput = document.getElementsByClassName('item_discount_' + itemRowIndex);
        //Multiple Discount
        for (let index = 0; index < discountHiddenPercentageFields.length; index++) {
            if (discountHiddenPercentageFields[index].value)
            {
                let currentDiscountVal = parseFloat(itemValue ? itemValue : 0) * (parseFloat(discountHiddenPercentageFields[index].value ? discountHiddenPercentageFields[index].value : 0)/100);
                discountHiddenValuesFields[index].value = currentDiscountVal.toFixed(2);
                discountAmount+= currentDiscountVal;
            }
            else
            {
                discountAmount+= parseFloat(discountHiddenValuesFields[index].value ? discountHiddenValuesFields[index].value : 0);
            }
        }
        mainDiscountInput.value = discountAmount;
        //Value after discount
        const valueAfterDiscount = document.getElementById('value_after_discount_' + itemRowIndex);
        const valueAfterDiscountValue = (itemValue - mainDiscountInput.value).toFixed(2);
        valueAfterDiscount.value = valueAfterDiscountValue;
        //Get exact discount amount from order
        // let totalHeaderDiscountAmount = 0;
        // if (orderDiscountSummary) {
        // totalHeaderDiscountAmount = parseFloat(orderDiscountSummary.textContent ? orderDiscountSummary.textContent : 0);
        // }

        //Get total for calculating header discount for each item
        const itemTotalValueAfterDiscount = document.getElementsByClassName('item_val_after_discounts_input');
        let totalValueAfterDiscount = 0;
        for (let index = 0; index < itemTotalValueAfterDiscount.length; index++) {
            totalValueAfterDiscount += parseFloat(itemTotalValueAfterDiscount[index].value ? itemTotalValueAfterDiscount[index].value : 0);
        }

        setModalDiscountTotal('item_discount_' + itemRowIndex, itemRowIndex);

        //Set Header Discount
        updateHeaderDiscounts();
        updateHeaderExpenses();

        //Get exact discount amount from order
        totalHeaderDiscountAmount = 0;
        const orderDiscountSummary = document.getElementById('order_discount_summary');
        if (orderDiscountSummary) {
            totalHeaderDiscountAmount = parseFloat(orderDiscountSummary.textContent ? orderDiscountSummary.textContent : 0);
        }
        let itemHeaderDiscount = (parseFloat(valueAfterDiscountValue ? valueAfterDiscountValue : 0)/ totalValueAfterDiscount) * totalHeaderDiscountAmount;
        itemHeaderDiscount = (parseFloat(itemHeaderDiscount ? itemHeaderDiscount : 0)).toFixed(2);
        //Done
        const headerDiscountInput = document.getElementById('header_discount_' + itemRowIndex);
        headerDiscountInput.value = itemHeaderDiscount;

        const valueAfterHeaderDiscount = document.getElementById('value_after_header_discount_' + itemRowIndex);
        valueAfterHeaderDiscount.value = parseFloat(valueAfterDiscountValue ? valueAfterDiscountValue : 0) - itemHeaderDiscount;

        setModalDiscountTotal('item_discount_' + itemRowIndex, itemRowIndex);

        //Set Header Discount
        updateHeaderDiscounts();

        //Tax
        getItemTax(itemRowIndex);

    }

    function updateHeaderDiscounts()
    {
        const headerPercentages = document.getElementsByClassName('order_discount_percentage_hidden');
        const headerValues = document.getElementsByClassName('order_discount_value_hidden');
        var allItemTotalValue = 0;
        var allItemTotalValueInputs = document.getElementsByClassName('item_values_input');
        for (let idx1 = 0; idx1 < allItemTotalValueInputs.length; idx1++) {
            allItemTotalValue += parseFloat(allItemTotalValueInputs[idx1].value ? allItemTotalValueInputs[idx1].value : 0);
        }
        var totalItemDiscount = 0;
        var totalItemDiscountInputs = document.getElementsByClassName('item_discounts_input');
        for (let idx1 = 0; idx1 < totalItemDiscountInputs.length; idx1++) {
            totalItemDiscount += parseFloat(totalItemDiscountInputs[idx1].value ? totalItemDiscountInputs[idx1].value : 0);
        }
        let totalAfterItemDiscount = parseFloat(allItemTotalValue ? allItemTotalValue : 0) - parseFloat(totalItemDiscount ? totalItemDiscount : 0);

        let discountAmount = 0;

        for (let index = 0; index < headerValues.length; index++) {
            if (headerPercentages[index].value) {
                let currentDiscountVal = totalAfterItemDiscount * (parseFloat(headerPercentages[index].value ? headerPercentages[index].value : 0)/100);
                headerValues[index].value = currentDiscountVal.toFixed(2);
                const tableOrderDiscountValue = document.getElementById('order_discount_input_val_' + index);
                if (tableOrderDiscountValue) {
                    tableOrderDiscountValue.textContent = currentDiscountVal;
                }
                discountAmount+= currentDiscountVal;
            } else {
                discountAmount+= parseFloat(headerValues[index].value ? headerValues[index].value : 0);
            }
        }
        getTotalorderDiscounts(false);

    }
    function updateHeaderExpenses()
    {
        const headerPercentages = document.getElementsByClassName('order_expense_percentage_hidden');
        const headerValues = document.getElementsByClassName('order_expense_value_hidden');
        var totalAfterTax = parseFloat(document.getElementById('all_items_total_after_tax_summary').textContent.replace(/,/g, ''));

        let expenseAmount = 0;

        for (let index = 0; index < headerValues.length; index++) {
            if (headerPercentages[index].value) {
                let currentExpenseVal = totalAfterTax * (parseFloat(headerPercentages[index].value ? headerPercentages[index].value : 0)/100);
                headerValues[index].value = currentExpenseVal.toFixed(2);
                const tableOrderExpenseValue = document.getElementById('order_expense_input_val_' + index);
                if (tableOrderExpenseValue) {
                    tableOrderExpenseValue.textContent = (currentExpenseVal).toFixed(2);
                }
                expenseAmount+= currentExpenseVal;
            } else {
                expenseAmount+= parseFloat(headerValues[index].value ? headerValues[index].value : 0);
            }
        }
        getTotalOrderExpenses();

    }



    function setAllTotalFields()
    {
        //Item value
        const itemTotalInputs = document.getElementsByClassName('item_values_input');
        let totalValue = 0;
        for (let index = 0; index < itemTotalInputs.length; index++) {
            totalValue += parseFloat(itemTotalInputs[index].value ? itemTotalInputs[index].value : 0);
        }
        document.getElementById('all_items_total_value').textContent = (totalValue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('all_items_total_value_summary').textContent = totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (totalValue < 0) {
            document.getElementById('all_items_total_value_summary').setAttribute('style', 'color : red !important;');
        } else {
            document.getElementById('all_items_total_value_summary').setAttribute('style', '');
        }
        //Item Discount
        const itemTotalDiscounts = document.getElementsByClassName('item_discounts_input');
        let totalDiscount = 0;
        for (let index = 0; index < itemTotalDiscounts.length; index++) {
            totalDiscount += parseFloat(itemTotalDiscounts[index].value ? itemTotalDiscounts[index].value : 0);
        }
        document.getElementById('all_items_total_discount').textContent = (totalDiscount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('all_items_total_discount_summary').textContent = (totalDiscount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (totalDiscount < 0) {
            document.getElementById('all_items_total_discount_summary').setAttribute('style', 'color : red !important;');
        } else {
            document.getElementById('all_items_total_discount_summary').setAttribute('style', '');
        }
        //Item Tax
        const itemTotalTaxes = document.getElementsByClassName('item_taxes_input');
        let totalTaxes = 0;
        for (let index = 0; index < itemTotalTaxes.length; index++) {
            let tax_detail = itemTotalTaxes[index].getAttribute('tax_details') ? JSON.parse(itemTotalTaxes[index].getAttribute('tax_details')) : null;
            if(tax_detail)
            {
                for(let i = 0; i < tax_detail.length; i++)
                {
                    if(tax_detail[i].tax_applicability_type == "collection")
                    {
                        totalTaxes += parseFloat(tax_detail[i].tax_value ? tax_detail[i].tax_value : 0);
                    }
                    else
                    {
                        totalTaxes -= parseFloat(tax_detail[i].tax_value ? tax_detail[i].tax_value : 0);
                    }
                }
            }
            else{
                totalTaxes += parseFloat(itemTotalTaxes[index].value ? itemTotalTaxes[index].value : 0);
            }
        }
        document.getElementById('all_items_total_tax').value = (totalTaxes).toFixed(2);
        document.getElementById('all_items_total_tax_summary').textContent = Math.abs((totalTaxes).toFixed(2));
// if (totalTaxes < 0) {
        //     document.getElementById('all_items_total_tax_summary').setAttribute('style', 'color : red !important;')
        // } else {
            document.getElementById('all_items_total_tax_summary').setAttribute('style', '');
        // }
        //Item Total Value After Discount
        const itemDiscountTotal = document.getElementsByClassName('item_val_after_header_discounts_input');
        let itemDiscountTotalValue = 0;
        for (let index = 0; index < itemDiscountTotal.length; index++) {
            itemDiscountTotalValue += parseFloat(itemDiscountTotal[index].value ? itemDiscountTotal[index].value : 0);
        }
        //Item Total Value
        const itemValueAfterDiscount = document.getElementsByClassName('item_val_after_discounts_input');
        let itemValueAfterDiscountValue = 0;
        for (let index = 0; index < itemValueAfterDiscount.length; index++) {
            itemValueAfterDiscountValue += parseFloat(itemValueAfterDiscount[index].value ? itemValueAfterDiscount[index].value : 0);
        }
        //Order Discount
        const orderDiscountContainer = document.getElementById('order_discount_summary');
        let orderDiscount = orderDiscountContainer ? orderDiscountContainer.textContent : null;
        orderDiscount = parseFloat(orderDiscount ? orderDiscount.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,}).replace(/,/g, '') : 0);
        let taxableValue = itemValueAfterDiscountValue - orderDiscount;
        document.getElementById('all_items_total_total').textContent = (itemValueAfterDiscountValue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('all_items_total_total_summary').textContent = taxableValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (taxableValue < 0) {
            document.getElementById('all_items_total_total_summary').setAttribute('style', 'color : red !important;')
        } else {
            document.getElementById('all_items_total_total_summary').setAttribute('style', '');
        }
        //Taxable total value
        const totalAfterTax = (totalTaxes + itemDiscountTotalValue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('all_items_total_after_tax_summary').textContent = totalAfterTax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (totalAfterTax < 0) {
            document.getElementById('all_items_total_after_tax_summary').setAttribute('style', 'color : red !important;')
        } else {
            document.getElementById('all_items_total_after_tax_summary').setAttribute('style', '')
        }
        //Expenses
        const expensesInput = document.getElementById('all_items_total_expenses_summary');
        const expense = parseFloat(expensesInput.textContent ? expensesInput.textContent.replace(/,/g, '') : 0);
        //Grand Total
        const grandTotalContainer = document.getElementById('grand_total');
        grandTotalContainer.textContent = (parseFloat(totalAfterTax.replace(/,/g, '')) + parseFloat(expense)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (grandTotalContainer.textContent < 0) {
            document.getElementById('grand_total').setAttribute('style', 'color : red !important;')
        } else {
            document.getElementById('grand_total').setAttribute('style', '')
        }
    }


    function onTaxClick(itemIndex)
    {
        const taxInput = document.getElementById('item_tax_' + itemIndex);
        const taxDetails = JSON.parse(taxInput.getAttribute('tax_details'));
        const taxTable = document.getElementById('tax_main_table');
        //Remove previous Taxes
        const oldTaxes = document.getElementsByClassName('item_taxes');
        if (oldTaxes && oldTaxes.length > 0)
        {
            while (oldTaxes.length > 0) {
                oldTaxes[0].remove();
            }
        }
        //Add New Tax
        let newHtml = ``;
        taxDetails.forEach((element, index) => {
            let newDoc = taxTable.insertRow(index + 1);
            newDoc.id = "item_tax_modal_" + itemIndex;
            newDoc.className = "item_taxes";
            newHtml = `
                <td>${index+1}</td>
                <td>${element.tax_name}</td>
                <td>${element.tax_percentage}</td>
                <td class = "dynamic_tax_val_${itemIndex}">${element.tax_value}</td>
            `;
            newDoc.innerHTML = newHtml;
        });

    }

    function onOrderTaxClick()
    {
        const taxesHiddenFields = document.getElementsByClassName('item_taxes_input');
        orderTaxes = [];
        for (let index = 0; index < taxesHiddenFields.length; index++) {
            let itemLevelTaxes = JSON.parse(taxesHiddenFields[index].getAttribute('tax_details'));
            if (Array.isArray(itemLevelTaxes) && itemLevelTaxes.length > 0) {
                itemLevelTaxes.forEach(itemLevelTax => {
                    let existingIndex = orderTaxes.findIndex(tax => tax.tax_type == itemLevelTax.tax_type && tax.tax_percentage == itemLevelTax.tax_percentage);
                    if (existingIndex > -1) { //Exists
                        orderTaxes[existingIndex]['taxable_amount'] = parseFloat(orderTaxes[existingIndex]['taxable_amount']) + parseFloat(itemLevelTax.taxable_value);
                        orderTaxes[existingIndex]['tax_value'] = parseFloat(orderTaxes[existingIndex]['tax_value']) + parseFloat(itemLevelTax.tax_value);
                    } else { //Push
                        orderTaxes.push({
                            'index' : orderTaxes.length ? orderTaxes.length : 0,
                            'tax_type' : itemLevelTax.tax_type,
                            'taxable_amount' : itemLevelTax.taxable_value,
                            'tax_percentage' : itemLevelTax.tax_percentage,
                            'tax_value' : itemLevelTax.tax_value
                        });
                    }
                });
            }
        }
        const mainTableBody = document.getElementById('order_tax_details_table');
        let newTaxesHtml = ``;
        orderTaxes.forEach(taxDetail => {
            newTaxesHtml += `
                        <tr>
                        <td>${taxDetail.index+1}</td>
                        <td>${taxDetail.tax_type}</td>
                        <td>${taxDetail.taxable_amount}</td>
                        <td>${taxDetail.tax_percentage}</td>
                        <td>${taxDetail.tax_value}</td>
                        </tr>
            `
        });
        mainTableBody.innerHTML = newTaxesHtml;
    }
    function processOrder(landLease = '')
    {
        const allCheckBoxes = document.getElementsByClassName('po_checkbox');
        const docType = "{{$type ? $type : 'si'}}";
        const apiUrl = "{{route('sale.return.process.items')}}";
        let docId = [];
        let soItemsId = [];
        for (let index = 0; index < allCheckBoxes.length; index++) {
            if (allCheckBoxes[index].checked) {
                docId.push(allCheckBoxes[index].getAttribute('document-id'));
                soItemsId.push(allCheckBoxes[index].getAttribute('so-item-id'));
            }
        }
        if (docId && soItemsId.length > 0) {
            $.ajax({
                url: apiUrl,
                method: 'GET',
                dataType: 'json',
                 data: {
                    order_id: docId,
                    items_id: soItemsId,
                    store_id : $("store_id_input").val(),
                    sub_store_id : $("sub_store_id_input").val(),
                    doc_type: 'si'
                },
                success: function(data) {
                    const currentOrders = data.data;
                    let currentOrderIndexVal = 0;
                    currentOrders.forEach((currentOrder) => {
                        if (currentOrder) { //Set all data
                        //Disable Header
                            disableHeader();

                            let subStoreId = currentOrder?.sub_store_id ? currentOrder?.sub_store_id : '';
                        //Disable Header
                            //Basic Details
                            //Disable Header
                        //Basic Details
                        $("#customer_code_input").val(currentOrder.customer?.company_name);
                        $("#customer_id_input").val(currentOrder.customer_id);
                        $("#customer_code_input_hidden").val(currentOrder.customer?.company_name);
                        $("#consignee_name_input").val(currentOrder.consignee_name);
                        $("#consignee_id_input").val(currentOrder?.consignee_id);
                        $("#customer_phone_no_input").val(currentOrder.customer_phone_no);
                        $("#customer_email_input").val(currentOrder.customer_email);
                        $("#customer_gstin_input").val(currentOrder.customer_gstin);
                        //First add options also

                        $("#currency_dropdown").empty(); // Clear existing options
                        $("#currency_dropdown").append(new Option(
                            currentOrder.customer ? currentOrder.customer.currency?.name || 'Default Currency Name' : 'Default Currency Name',
                            currentOrder.currency_id || 0
                        ));
                        $("#currency_code_input").val(currentOrder.currency_code);
                        //First add options also
                        $("#payment_terms_dropdown").empty(); // Clear existing options
                        $("#payment_terms_dropdown").append(new Option(
                            currentOrder.customer ? currentOrder.customer.payment_terms?.name || 'Default Payment Terms' : 'Default Payment Name',
                            currentOrder.payment_term_id || 0
                        ));
                        $("#payment_terms_code_input").val(currentOrder.payment_term_code);
                        //Address
                        $("#current_billing_address").text(currentOrder.billing_address_details?.display_address);
                        $("#current_shipping_address").text(currentOrder.shipping_address_details?.display_address);
                        $("#current_shipping_country_id").val(currentOrder.shipping_address_details?.country_id);
                        $("#current_billing_country_id").val(currentOrder.billing_address_details?.country_id);
                        $("#current_shipping_state_id").val(currentOrder.shipping_address_details?.state_id);
                        $("#current_billing_state_id").val(currentOrder.billing_address_details?.state_id);
                        
                        $("#reference_id_input").val(currentOrder.id);
                        $("#reference_doc_type").val(currentOrder.document_type);
                        $("#reference_no_input").val(currentOrder.reference_number);

                        // if (currentOrder?.customer_terms) {
                        //     $('#summernote1').summernote('code', currentOrder?.customer_terms);
                        // }
                        // if (currentOrder?.customer_terms_id) {
                        //     $("#customer_terms_id").val(currentOrder?.customer_terms_id);
                        // }
                        // if (currentOrder?.customer_terms_name) {
                        //     $("#terms").val(currentOrder?.customer_terms_name);
                        // }
                        //Credit Days
                        if (currentOrder?.credit_days) {
                            $("#credit_days_input").val(currentOrder.credit_days);
                        } else {
                            $("#credit_days_input").val(0);
                        }
                        //General Detail
                        $("#transporter_name_input").val(currentOrder?.transporter_name);
                        // $("#transporter_mode_input").val(currentOrder?.transportation_mode);
                        $("#vehicle_no_input").val(currentOrder.vehicle_no);
                        $("#lr_number_input").val(currentOrder?.lr_number);
                        let transporterModeInput = currentOrder?.eway_bill_master_id;
                        if (transporterModeInput) {
                            $("#transporter_mode_input").val(transporterModeInput)
                        }

                        $("#current_shipping_address_id").val(currentOrder.shipping_address_details?.id);
                        $("#current_billing_address_id").val(currentOrder.billing_address_details?.id);
                        //Main IDs
                        var newOptionBilling = new Option(currentOrder.billing_address_details?.address, currentOrder.billing_address_details?.id, false, false);
                        $('#billing_address_dropdown').append(newOptionBilling);
                        $("#billing_address_dropdown").val(currentOrder.billing_address_details?.id);

                        var newOptionShipping = new Option(currentOrder.shipping_address_details?.address, currentOrder.shipping_address_details?.id, false, false);
                        $('#shipping_address_dropdown').append(newOptionShipping);
                        $("#shipping_address_dropdown").val(currentOrder.shipping_address_details?.id);


                        const locationElement = document.getElementById('store_id_input');
                        if (locationElement) {
                            const displayAddress = locationElement.options[locationElement.selectedIndex].getAttribute('display-address');
                            $("#current_pickup_address").text(displayAddress);
                        }
                        const mainTableItem = document.getElementById('item_header');
                        //Remove previous items if any
                        // const allRowsCheck = document.getElementsByClassName('item_row_checks');
                        // for (let index = 0; index < allRowsCheck.length; index++) {
                        //     allRowsCheck[index].checked = true;
                        // }
                        // deleteItemRows();
                            currentOrder.items.forEach((item, itemIndex) => {
                                const avl_qty = item.return_balance_qty;
                                item.balance_qty = avl_qty;``
                                const itemRemarks = item.remarks ? item.remarks : '';
                                let amountMax = ``;
                                if (landLease) {
                                    amountMax = `max = '${item.rate}'`;
                                }
                                let agreement_no = '';
                                let lease_end_date = '';
                                let due_date = '';
                                let repayment_period = '';

                                let land_parcel = '';
                                let land_plots = '';

                                let landLeasePullHtml = '';

                                if (landLease) {
                                    agreement_no = currentOrder?.agreement_no;
                                    lease_end_date = moment(currentOrder?.lease_end_date).format('D/M/Y');
                                    due_date = moment(item?.due_date).format('D/M/Y');
                                    repayment_period = currentOrder.repayment_period_type;
                                    land_parcel = item?.land_parcel_display;
                                    land_plots = item?.land_plots_display;

                                    landLeasePullHtml = `
                                        <input type = "hidden" value = ${agreement_no} id = "land_lease_agreement_no_${itemIndex}" />
                                        <input type = "hidden" value = ${lease_end_date} id = "land_lease_end_date_${itemIndex}" />
                                        <input type = "hidden" value = ${due_date} id = "land_lease_due_date_${itemIndex}" />
                                        <input type = "hidden" value = ${repayment_period} id = "land_lease_repayment_period_${itemIndex}" />
                                        <input type = "hidden" value = ${land_parcel} id = "land_lease_land_parcel_${itemIndex}" />
                                        <input type = "hidden" value = ${land_plots} id = "land_lease_land_plots_${itemIndex}" />
                                    `;
                                } else {
                                    landLeasePullHtml = '';
                                }
                                var discountAmtPrev = 0;
                                item.discount_ted.forEach((ted, tedIndex) => {

                                    var percentage = ted.ted_percentage;
                                    var itemValue = (item.rate * item.balance_qty).toFixed(2);
                                    if (!percentage) {
                                        percentage = ted.ted_amount/(ted.assessment_amount ? ted.assessment_amount : itemValue) * 100;
                                    }

                                    var itemDiscountValuePrev = ((itemValue * percentage)/100).toFixed(2);

                                    discountAmtPrev += parseFloat(itemDiscountValuePrev ? itemDiscountValuePrev : 0);
                                });



                            mainTableItem.innerHTML += `
                            <tr id = "item_row_${itemIndex}" class = "item_header_rows" onclick = "onItemClick('${itemIndex}');">
                                <td class="customernewsection-form">
                                <div class="form-check form-check-primary custom-checkbox">
                                    <input type="checkbox" class="form-check-input item_row_checks"  id="item_checkbox_${itemIndex}" del-index = "${itemIndex}">
                                    <label class="form-check-label" for="Email"></label>
                                </div>
                                                                        </td>
                                <td class="poprod-decpt">

                                    <input type = "hidden" id = "qt_id_${itemIndex}" value = "${item?.id}" name = "quotation_item_ids[]"/>

                                    <input type = "hidden" id = "qt_type_id_${itemIndex}" value = "${currentOrder.document_type}" name = "quotation_item_type[]"/>

                                    <input type = "hidden" id = "qt_book_id_${itemIndex}" value = "${currentOrder?.book_id}" />
                                    <input type = "hidden" id = "qt_book_code_${itemIndex}" value = "${currentOrder?.book_code}" />

                                    <input type = "hidden" id = "qt_document_no_${itemIndex}" value = "${currentOrder?.document_number}" />
                                    <input type = "hidden" id = "qt_document_date_${itemIndex}" value = "${currentOrder?.document_date}" />

                                    <input type = "hidden" id = "qts_id_${itemIndex}" value = "${currentOrder?.document_number}" />

                                    ${landLeasePullHtml}

                                    <input type="text" readonly id = "items_dropdown_${itemIndex}" name="item_code[]" placeholder="Select" class="form-control mw-100 ledgerselecct comp_item_code ui-autocomplete-input" autocomplete="off" data-name="${item?.item?.item_name}" data-code="${item?.item?.item_code}" data-id="${item?.item?.id}" hsn_code = "${item?.item?.hsn?.code}" item-name = "${item?.item?.item_name}" specs = '${JSON.stringify(item?.item?.specifications)}' attribute-array = '${JSON.stringify(item?.item_attributes_array)}'  value = "${item?.item?.item_code}">
                                    <input type = "hidden" name = "item_id[]" id = "items_dropdown_${itemIndex}_value" value = "${item?.item_id}"></input>
                                </td>
                                <td class="poprod-decpt">
                                    <input type="text" id = "items_name_${itemIndex}" class="form-control mw-100"   value = "${item?.item?.item_name}" readonly>
                                </td>
                                <td class="poprod-decpt" id='attribute_section_${itemIndex}'>
                                    <button id = "attribute_button_${itemIndex}" ${item?.item_attributes_array?.length > 0 ? '' : 'disabled'} type = "button" data-bs-toggle="modal" onclick = "setItemAttributes('items_dropdown_${itemIndex}', '${itemIndex}','${true}');" data-bs-target="#attribute" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
                                </td>
                                <td>
                                    <select class="form-select" disabled name = "uom_id[]" id = "uom_dropdown_${itemIndex}">

                                    </select>
                                    <td class="d-none d-flex align-items-center">
                                        <select class="form-select" name="item_store[${itemIndex}]" id="item_store_${itemIndex}" onclick="getStoresData(${itemIndex});">
                                            @foreach ($stores as $store)
                                                <option value="{{$store->id}}">{{$store->store_name}}</option>
                                            @endforeach
                                        </select>
                                        <div class="me-50 d-none cursor-pointer addDeliveryScheduleBtn" style="display:none;" data-row-count="${itemIndex}" onclick="getStoresData('${itemIndex}',null,null,true)" data-stores='[]' id="data_stores_${itemIndex}">
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="Store Location" class="text-primary">
                                                <i data-feather="map-pin"></i>
                                            </span>
                                        </div>
                                    </td>
                                        </td>
                                        <td><input type="text" id = "item_qty_${itemIndex}" name = "item_qty[]" oninput = "changeItemQty(this, '${itemIndex}');" value = "${item?.balance_qty}" class="form-control item_store_locations mw-100 text-end" onblur = "setFormattedNumericValue(this);" max = "${item?.balance_qty}"/></td>
                                        <td><input type="text" disabled id = "item_rate_${itemIndex}" name = "item_rate[]" oninput = "changeItemRate(this, '${itemIndex}');" ${item.balance_qty} value = "${item?.rate}" class="form-control mw-100 text-end" onblur = "setFormattedNumericValue(this);" /></td>
                                        <td><input type="text" id = "item_value_${itemIndex}" disabled class="form-control mw-100 text-end item_values_input" value = "${(item?.balance_qty ? item?.balance_qty : 0) * (item?.rate ? item?.rate : 0)}" /></td>
                                        <input type = "hidden" id = "header_discount_${itemIndex}" value = "${item?.header_discount_amount}" ></input>
                                        <input type = "hidden" id = "header_expense_${itemIndex}" value = "${item?.header_expense_amount}"></input>
                                    <td>
                                    <div class="position-relative d-flex align-items-center">
                                        <input type="text" id = "item_discount_${itemIndex}" disabled class="form-control mw-100 text-end item_discounts_input" style="width: 70px" value = "${discountAmtPrev}"/>
                                        <div class="ms-50">
                                            <button type = "button" disabled onclick = "onDiscountClick('item_value_${itemIndex}', '${itemIndex}')" data-bs-toggle="modal" data-bs-target="#discount" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Add</button>
                                        </div>
                                    </div>
                                    </td>
                                        <input type="hidden" id = "item_tax_${itemIndex}" value = "${item?.tax_amount}" class="form-control mw-100 text-end item_taxes_input" style="width: 70px" />
                                        <td><input type="text" id = "value_after_discount_${itemIndex}" value = "${(item?.balance_qty * item?.rate) - item?.item_discount_amount}" disabled class="form-control mw-100 text-end item_val_after_discounts_input" /></td>
                                        <input type = "hidden" id = "value_after_header_discount_${itemIndex}" class = "item_val_after_header_discounts_input" value = "${(item?.balance_qty * item?.rate) - item?.item_discount_amount - item?.header_discount_amount}" ></input>
                                        <input type="hidden" id = "item_total_${itemIndex}" value = "${(item?.balance_qty * item?.rate) - item?.item_discount_amount - item?.header_discount_amount + (item?.tax_amount)}" disabled class="form-control mw-100 text-end item_totals_input" />
                                    <td>
                                        <div class="d-flex">
                                            <!-- Remarks -->
                                            <div class="me-50 cursor-pointer" data-bs-toggle="modal" data-bs-target="#Remarks" 
                                                onclick="setItemRemarks('item_remarks_${itemIndex}');">
                                                <span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary">
                                                    <i data-feather="file-text"></i>
                                                </span>
                                            </div>

                                            <input type="hidden" id="item_remarks_${itemIndex}" name="item_remarks[${itemIndex}]" />
                                            ${item.lotdata && item.lotdata.length > 0 ? `
                                                <!-- Hidden Batch Input -->
                                                <input type="hidden" id="batches_${itemIndex}" name="batch_details[${itemIndex}]" value='${JSON.stringify(item.lotdata)}' />

                                                <!-- Batch Button (only if lotdata has items) -->
                                                <div class="me-50 cursor-pointer addBatchBtn"
                                                    data-row-count="${itemIndex}"
                                                    data-is-batch-number="${item.item.is_batch_no}"
                                                    data-is-expiry="${item.item.is_expiry}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#item-batch-modal">
                                                    <span data-bs-toggle="tooltip" data-bs-placement="top" class="text-primary"
                                                        data-bs-original-title="Item Batch" aria-label="Item Batch">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round" class="feather feather-map-pin">
                                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                            <circle cx="12" cy="10" r="3"></circle>
                                                        </svg>
                                                    </span>
                                                </div>
                                            ` : ''}

                                            <input type="hidden" id="item_lots_${itemIndex}" name="item_lots[${itemIndex}]" />
                                        </div>
                                    </td>

                                </tr>
                                `;
                                initializeAutocomplete1("items_dropdown_" + itemIndex, itemIndex);
                                renderIcons();
                                const totalValue = item.item_discount_amount;
                                document.getElementById('discount_main_table').setAttribute('total-value', totalValue);
                                document.getElementById('discount_main_table').setAttribute('item-row', 'item_value_' + itemIndex);
                                document.getElementById('discount_main_table').setAttribute('item-row-index', itemIndex);

                                item.discount_ted.forEach((ted, tedIndex) => {

                                    addHiddenInput("item_discount_name_" + itemIndex + "_" + tedIndex, ted.ted_name, `item_discount_name[${itemIndex}][${tedIndex}]`, 'discount_names_hidden_' + itemIndex, 'item_row_' + itemIndex);
                                    var percentage = ted.ted_percentage;
                                    var itemValue = document.getElementById('item_value_' + itemIndex).value;
                                    if (!percentage) {
                                        percentage = ted.ted_amount/itemValue*100;
                                    }
                                    addHiddenInput("item_discount_percentage_" + itemIndex + "_" + tedIndex, percentage, `item_discount_percentage[${itemIndex}][${tedIndex}]`, 'discount_percentages_hidden_' + itemIndex,  'item_row_' + itemIndex);
                                    var itemDiscountValue = ((itemValue * percentage)/100).toFixed(2);

                                    addHiddenInput("item_discount_value_" + itemIndex + "_" + tedIndex, itemDiscountValue, `item_discount_value[${itemIndex}][${tedIndex}]`, 'discount_values_hidden_' + itemIndex, 'item_row_' + itemIndex);

                                });
                                //Item Delivery Schedule
                                if (item.item_deliveries) {
                                    item.item_deliveries.forEach((delivery, deliveryIndex) => {
                                        addHiddenInput("item_delivery_schedule_qty_" + itemIndex + "_" + deliveryIndex, delivery.qty, `item_delivery_schedule_qty[${itemIndex}][${deliveryIndex}]`, 'delivery_schedule_qties_hidden_' + itemIndex, "item_row_" + itemIndex);
                                        addHiddenInput("item_delivery_schedule_date" + itemIndex + "_" + deliveryIndex, delivery.delivery_date, `item_delivery_schedule_date[${itemIndex}][${deliveryIndex}]`, 'delivery_schedule_dates_hidden_' + itemIndex, "item_row_" + itemIndex);
                                    });
                                }

                                var itemUomsHTML = ``;
                                if (item.item.uom && item.item.uom.id) {
                                    itemUomsHTML += `<option value = '${item.item.uom.id}' ${item.item.uom.id == item.uom_id ? "selected" : ""}>${item.item.uom.alias}</option>`;
                                }
                                item.item.alternate_uoms.forEach(singleUom => {
                                    if (singleUom.is_selling) {
                                        itemUomsHTML += `<option value = '${singleUom.uom.id}' ${singleUom.uom.id == item.uom_id ? "selected" : ""} >${singleUom.uom?.alias}</option>`;
                                    }
                                });
                                document.getElementById('uom_dropdown_' + itemIndex).innerHTML = itemUomsHTML;
                                // getStoresData(itemIndex,null,false);
                                getItemTax(itemIndex);
                                setAttributesUI(itemIndex,true);
                                $(`#item_qty_${itemIndex}`).trigger("change");
                                });

                            if (docType !== "dn") {
                                 //Order Discount
                        currentOrder.discount_ted.forEach((orderDiscount, orderDiscountIndex) => {
                            document.getElementById('new_order_discount_name').value = orderDiscount.ted_name;
                            document.getElementById('new_order_discount_id').value = orderDiscount.ted_id;
                            var currentOrderDiscountPercentage = orderDiscount.ted_percentage;
                            if (!currentOrderDiscountPercentage) {
                                currentOrderDiscountPercentage = orderDiscount.ted_amount/ orderDiscount.assessment_amount * 100;
                            }
                            document.getElementById('new_order_discount_percentage').value = currentOrderDiscountPercentage;
                            document.getElementById('new_order_discount_value').value = orderDiscount.ted_amount;
                            addOrderDiscount(null, false);
                        });
                        //Order Expense
                        currentOrder.expense_ted.forEach((orderExpense, orderExpenseIndex) => {
                            document.getElementById('order_expense_name').value = orderExpense.ted_name;
                            document.getElementById('order_expense_id').value = orderExpense.ted_id;
                            var currentOrderExpensePercentage = orderExpense.ted_percentage;
                            if (!currentOrderExpensePercentage) {
                                currentOrderExpensePercentage = orderExpense.ted_amount/ orderExpense.assessment_amount * 100;
                            }
                            document.getElementById('order_expense_percentage').value = currentOrderExpensePercentage;
                            document.getElementById('order_expense_value').value = orderExpense.ted_amount;
                            addOrderExpense(null, false);
                        });

                            }

                            setAllTotalFields();

                            changeDropdownOptions(document.getElementById('customer_id_input'), ['billing_address_dropdown','shipping_address_dropdown'], ['billing_addresses', 'shipping_addresses'], '/customer/addresses/', 'vendor_dependent');

                            // $("#shipping_address_dropdown").select2();
                            // $("#shipping_address_dropdown").prop('disabled', false);
                            // $("#shipping_address_dropdown").val(currentOrder.shipping_address_details.id).trigger('change');
                            // $("#billing_address_dropdown").select2();
                            // $("#billing_address_dropdown").prop('disabled', false);
                            // $("#billing_address_dropdown").val(currentOrder.billing_address_details.id).trigger('change');

                        }
                        currentOrderIndexVal += 1;
                        $("#order_discount_button").prop('disabled', true);
                        $("#order_expense_button").prop('disabled', true);
                        $("#order_expense_button").prop('disabled', true);
                    });

                    // for (let index = 0; index < itemIndex; index++) {
                    //     getStoresData(index,null,false);
                    // }
                },
                error: function(xhr) {
                    console.error('Error fetching customer data:', xhr.responseText);
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Please select at least one invoice',
                icon: 'error',
            });
        }
    }
    function getOrders(type = 'si') {
        let apiUrl = "{{ route('sale.return.pull.items') }}";

        // ✅ Checkbox column definition
        const checkboxColumn = () => ({
            data: null,
            name: 'checkbox',
            orderable: false,
            searchable: false,
            render: (row, _, __, meta) => {
                const docId = row?.header?.id;
                const soItemId = row?.id;
                return `<div class="form-check form-check-inline me-0">
                    <input class="form-check-input pull_checkbox po_checkbox" type="checkbox"
                        name="po_check"
                        id="po_checkbox_${meta.row}"
                        oninput="checkQuotation(this, '', '${type}');"
                        doc-id="${docId}"
                        current-doc-id="0"
                        document-id="${docId}"
                        so-item-id="${soItemId}">
                </div>`;
            }
        });

        // ✅ Columns for Sale Return Items
        const getColumns = () => {
            return [
                checkboxColumn(),
                { data: 'book_code', name: 'book_code' },
                { data: 'document_number', name: 'document_number' },
                { data: 'document_date', name: 'document_date' },
                {
                    data: 'customer_code',
                    name: 'customer_code',
                    render: d => `<span class="fw-bolder text-dark">${d || ''}</span>`
                },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'item_name', name: 'item_name' },
                { data: 'attributes_data', name: 'attributes_data' },
                { data: 'order_qty', name: 'order_qty' },
                { data: 'return_balance_qty', name: 'return_balance_qty' },
                { data: 'rate', name: 'rate' }
            ];
        };
        let tableSelector = "#invoice_table";
        // ✅ Table selector for this type
        if(type == "{{ App\Helpers\ConstantHelper::SI_SERVICE_ALIAS }}")
        {
            tableSelector = "#invoice_table";
        }
        else if(type == "{{ App\Helpers\ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS }}")
        {
            tableSelector = "#dnote_table";
        }
        else if(type == "{{ App\Helpers\ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS }}")
        {
            tableSelector = "#si_dnote_table";
        }

        // ✅ Collect selectedIds
        const selectedIds = Array.from(document.getElementsByClassName("item_header_rows"))
            .map((_, i) => document.getElementById('qt_id_' + i))
            .filter(Boolean)
            .map(el => el.value);

        // ✅ Filters
        const filters = {
            customer_id: $("#customer_id_qt_val"),
            book_id: $("#book_id_qt_val"),
            document_id: $("#document_id_qt_val"),
            item_id: $("#item_id_qt_val"),
            store_id: $("#store_id_input"),
            sub_store_id: $("#sub_store_id_input"),
            doc_type: $(tableSelector+"_value"),
            header_book_id: $("#series_id_input"),
            selected_ids: selectedIds
        };

        // ✅ Validation
        if (!filters.store_id.val()) {
            Swal.fire({ title: 'Error!', text: 'Please select Location', icon: 'error' });
            return;
        }
        if (!filters.sub_store_id.val()) {
            Swal.fire({ title: 'Error!', text: 'Please select Store', icon: 'error' });
            return;
        }

        // ✅ Destroy old DataTable
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }

        // ✅ Initialize with your reusable function
        initializeDataTable(
            tableSelector,
            apiUrl,
            getColumns(),
            filters,
            "Sales Return Items",
            [],        // Buttons
            [],        // Order
            'landscape',
            'GET',
            false,     // serverSide
            false      // processing
        );
    }

    async function handleTcsCalculation(itemIndex = null) {
        let invoiceItemId = $('#qt_id_'+itemIndex).val();
        let taxableValue = $("#all_items_total_total_summary").text();
        // Remove commas and convert to number
        let totalSummary = parseFloat(taxableValue.replace(/,/g, ''));
        let saleReturnId = $('#sale_return_id_input').val() || null;

        const taxInput = document.getElementById('order_tcs_tax');
        // console.log('Calculating TCS for Invoice Item ID:', invoiceItemId, 'Taxable Value:', totalSummary, 'Sale Return ID:', saleReturnId);
        // Call your existing fetch function
        const tcsData = await fetchTcsTax(invoiceItemId, totalSummary, saleReturnId);

        // Prepare tax attributes in the same style as getTcsTax()
        let taxAttrs = [];

        if (tcsData) {
            taxAttrs.push({
                tax_index: taxAttrs.length,
                tax_name: tcsData.ted_name ?? "TCS",
                tax_group: "TCS",
                tax_type: tcsData.ted_name,
                taxable_value: tcsData.assessment_amount ?? 0,
                tax_percentage: (tcsData.ted_percentage).toFixed(2) ?? 0,
                tax_value: (tcsData.tax_amount ?? 0).toFixed(2),
                tax_applicability_type: tcsData.applicable_type ?? "Collection",
            });
        }

        // Store tax details in the element
        taxInput.setAttribute('tax_details', JSON.stringify(taxAttrs));

        // Trigger your summary / header updates
        setAllTotalFields();
        updateHeaderExpenses();
    }



    function initializeAutocompleteQt(selector, selectorSibling, typeVal, labelKey1, labelKey2 = "") {
        let modalId = '#'+$("#" + selector).closest('.modal').attr('id');
            $("#" + selector).autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type: typeVal,
                            customer_id : $("#customer_id_qt_val").val(),
                            header_book_id : $("#series_id_input").val()
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    label: `${item[labelKey1]} (${item[labelKey2] ? item[labelKey2] : ''})`,
                                    code: item[labelKey1] || '',
                                };
                            }));
                        },
                        error: function(xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                minLength: 0,
                appendTo : modalId,
                select: function(event, ui) {
                    var $input = $(this);
                    $input.val(ui.item.label);
                    $("#" + selectorSibling).val(ui.item.id);
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $("#" + selectorSibling).val("");
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
    }
    var openPullType = "si";

    function openHeaderPullModal(type = null)
    {
        document.getElementById('qts_data_table').innerHTML = '';
        // document.getElementById('qts_data_table_land').innerHTML = '';
        if(type != openPullType)
        {
            current_doc_id = 0;
        }
        if (type == 'si') {
            openPullType = "si";
            initializeAutocompleteQt("book_code_input_qt_i", "book_id_qt_i_val", "book_si", "book_code", "book_name");
            initializeAutocompleteQt("document_no_input_qt_i", "document_id_qt_i_val", "si_document", "document_number", "document_number");
            initializeAutocompleteQt("customer_code_input_qt_i", "customer_id_qt_i_val", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("customer_code_input_qt_i_land", "customer_id_qt_i_val_land", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("item_name_input_qt_i", "item_id_qt_i_val", "sale_module_items", "item_code", "item_name");
        } else if(type == 'dnote'){
            openPullType = "dnote";
            initializeAutocompleteQt("book_code_input_qt_d", "book_id_qt_d_val", "book_din", "book_code", "book_name");
            initializeAutocompleteQt("document_no_input_qt_d", "document_id_qt_d_val", "din_document", "document_number", "document_number");
            initializeAutocompleteQt("customer_code_input_qt_d", "customer_id_qt_d_val", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("customer_code_input_qt_d_land", "customer_id_qt_d_val_land", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("item_name_input_qt_d", "item_id_qt_d_val", "sale_module_items", "item_code", "item_name");
            
        }
        else{
            initializeAutocompleteQt("book_code_input_qt_di", "book_id_qt_di_val", "book_si_dn", "book_code", "book_name");
            initializeAutocompleteQt("document_no_input_qt_di", "document_id_qt_di_val", "si_dn_document", "document_number", "document_number");
            initializeAutocompleteQt("customer_code_input_qt_di", "customer_id_qt_di_val", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("customer_code_input_qt_di_land", "customer_id_qt_di_val_land", "customer", "customer_code", "company_name");
            initializeAutocompleteQt("item_name_input_qt_di", "item_id_qt_di_val", "sale_module_items", "item_code", "item_name");
            
        }
        initializeAutocompleteQt("customer_code_input_qt", "customer_id_qt_val", "customer", "customer_code", "company_name");
        initializeAutocompleteQt("customer_code_input_qt_land", "customer_id_qt_val_land", "customer", "customer_code", "company_name");
        initializeAutocompleteQt("item_name_input_qt", "item_id_qt_val", "sale_module_items", "item_code", "item_name");
        getOrders(type);
    }

    let current_doc_id = 0;

    function checkQuotation(element)
    {
        const docId = element.getAttribute('doc-id');
        if (current_doc_id != 0) {
            if (element.checked == true) {
                if (current_doc_id != docId) {
                    element.checked = false;
                }
            } else {
                const otherElementsSameDoc = document.getElementsByClassName('po_checkbox');
                let resetFlag = true;
                for (let index = 0; index < otherElementsSameDoc.length; index++) {
                    if (otherElementsSameDoc[index].getAttribute('doc-id') == current_doc_id && otherElementsSameDoc[index].checked) {
                        resetFlag = false;
                        break;
                    }
                }
                if (resetFlag) {
                    current_doc_id = 0;
                }
            }
        } else {
            current_doc_id = element.getAttribute('doc-id');
        }

    }

    //Disable form submit on enter button
    document.querySelector("form").addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();  // Prevent form submission
        }
    });
    $("input[type='text']").on("keydown", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();  // Prevent form submission
        }
    });

        initializeAutocompleteStores("new_store_code_input", "new_store_id_input", 'store', 'store_code');
        initializeAutocompleteStores("new_rack_code_input", "new_rack_id_input", 'store_rack', 'rack_code');
        initializeAutocompleteStores("new_shelf_code_input", "new_shelf_id_input", 'rack_shelf', 'shelf_code');
        initializeAutocompleteStores("new_bin_code_input", "new_bin_id_input", 'shelf_bin', 'bin_code');

    function openStoreLocationModal(index) {

        const storeElement = document.getElementById('item_store_' + index);
        const item_name = document.getElementById('items_dropdown_'+index);
        if(!item_name.value){
            closeModal('deliveryScheduleModal');
            Swal.fire({
                title: 'Error!',
                text: 'Select an Item First.',
                icon: 'error'
            });
            return;
        }


        const item_qty = document.getElementById('item_qty_' + index);

        if(!item_qty.value){
            closeModal('deliveryScheduleModal');
            Swal.fire({
                title: 'Error!',
                text: 'Please Enter Item Quantity First.',
                icon: 'error'
            });
            return;
        }
        if(item_qty.value<=0){
            closeModal('deliveryScheduleModal');
            Swal.fire({
                title: 'Error!',
                text: 'Invalid Item Quantity.',
                icon: 'error'
            });
            return;

        }
        openModal('deliveryScheduleModal');
        const storeData = document.getElementById('data_stores_' + index);
        const storeTable = document.getElementById('item_location_table');
        const storeFoot = document.getElementById('item_location_foot');
        let data = item_qty.getAttribute('data-stores') ? JSON.parse(item_qty.getAttribute('data-stores')) : null;

        let numberOfRows = $('#item_location_table tr').length;
        let storesInnerHtml = '';
        let totalStoreQty = 0;
        let storeFooter = `
            <tr>
                <td colspan="4"></td>
                <td class="text-dark"><strong>Total</strong></td>
                <td class="text-dark" id="total"><strong>0.00</strong></td>
            </tr>
        `;

        if (storeElement) {
            const storesData = JSON.parse(decodeURIComponent(storeData.getAttribute('data-stores')));
            if (storesData && storesData.length > 0) {
                storesData.forEach((store, storeIndex) => {
                    // Loop through the `data` if it exists, otherwise use just 1 iteration
                    for (let i = 0; i < (data ? data.length : 1); i++) {
                        // Rack options with selected condition
                        const rackOptions = store.rack_data
                            ? store.rack_data.map(rack => {
                                const isSelected = data ? data[i].rack_id === rack.id ? 'selected' : '' : '';
                                return `<option value="${rack.id}" ${isSelected}>${rack.rack_code}</option>`;
                            }).join('')
                            : '<option value="">No Racks Available</option>';
                        let shelfOptions='';
                        // AJAX call to fetch shelf data if required
                        if (data && data.length) {
                            $.ajax({
                                url: "{{route('get_shelfs')}}",
                                method: 'GET',
                                data: {
                                    rack_id: data[i].rack_id,
                                },
                                success: function(datas) {
                                    if (datas && datas.shelfs) {
                                        shelfOptions = datas.shelfs
                                            ? datas.shelfs.map(shelf => {
                                                const isSelected = data[i].shelf_id === shelf.id ? 'selected' : '';
                                                return `<option value="${shelf.id}" ${isSelected}>${shelf.shelf_code}</option>`;
                                            }).join('')
                                            : '<option value="">Select Shelf</option>';

                                        $(`#shelf_data_${index}_${i}`).empty(); // Clear existing options
                                        $(`#shelf_data_${index}_${i}`).append(shelfOptions); // Append new options
                                    }
                                }
                            });
                        } else {
                            // If no AJAX required, use shelf options from store data directly
                            shelfOptions = store.shelf_data
                            ? store.shelf_data.map(shelf => {
                                const isSelected = data ? data[i].shelf_id === shelf.id ? 'selected' : '' : '';
                                return `<option value="${shelf.id}" ${isSelected}>${shelf.shelf_code}</option>`;
                            }).join('')
                            : '<option value="">Select Shelf</option>';
                        }

                        // Bin options with selected condition
                        const binOptions = store.bin_data
                            ? store.bin_data.map(bin => {
                                const isSelected = data ? data[i].bin_id === bin.id ? 'selected' : '' : '';
                                return `<option value="${bin.id}" ${isSelected}>${bin.bin_code}</option>`;
                            }).join('')
                            : '<option value="">No Bins Available</option>';

                        // Add the row HTML for each store
                        storesInnerHtml += `
                            <tr row-index=${i} item-index=${index} id="store_row_${index}_${i}">
                                <td>${i+1}</td>
                                <td>
                                    <select class="form-control mw-100 disable_on_edit" id="rack_data_${index}_${i}" oninput="getShelf(${index},${i})">
                                        <option value ="">Select Rack</option>
                                        ${rackOptions}
                                    </select>
                                    <input type="hidden" id="hidden_rack_data_${index}_${i}" name="hidden_rack_data_${index}_${i}">
                                </td>
                                <td>
                                    <select class="form-control mw-100 disable_on_edit" id="shelf_data_${index}_${i}">
                                        <option value ="">Select Shelf</option>
                                        ${shelfOptions}
                                    </select>
                                    <input type="hidden" id="hidden_shelf_data_${index}_${i}" name="hidden_shelf_data_${index}_${i}">
                                </td>
                                <td>
                                    <select class="form-control mw-100 disable_on_edit" id="bin_data_${index}_${i}">
                                        <option value ="">Select Bin</option>
                                        ${binOptions}
                                    </select>
                                    <input type="hidden" id="hidden_bin_data_${index}_${i}" name="hidden_bin_data_${index}_${i}">
                                </td>
                                <td>
                                    <input type="text" class="form-control disable_on_edit" id="item_qty_${index}_${i}" value="${data ? data[i].store_qty ? Number(data[i].store_qty) : Number(data[i].returned_qty) : Number(store.qty) || Number(item_qty.value)}" oninput="totalQuantityCheck(${index},${i})">
                                    <input type="hidden" id="hidden_item_qty_${index}_${i}" name="hidden_item_qty_${index}_${i}">
                                </td>
                                <td><i class='fas fa-trash text-danger disable_on_edit' onclick=removeStoreRow(${index},${i}) data-feather='trash'></i></td>
                            </tr>
                        `;

                        totalStoreQty += parseFloat(data ? data[i].store_qty ? Number(data[i].store_qty) : Number(data[i].returned_qty) : Number(store.qty) || 0);
                    }
                });

                storeTable.innerHTML = storesInnerHtml;
                document.getElementById('total').textContent = totalStoreQty.toFixed(2);
            } else {
                storeTable.innerHTML = storesInnerHtml;
                document.getElementById('total').textContent = "0.00";
            }
        }

        renderIcons();
    }
function removeStoreRow(index,storeIndex){
        const numberOfRows = $('#item_location_table tr').length;

        if(numberOfRows>1){
            $(`#store_row_${index}_${storeIndex}`).remove();
        }
        else{

        }
    }
    function totalQuantityCheck(index, storeIndex) {
        const item_qty = document.getElementById(`item_qty_${index}`);
        let totalStoreQty = 0;

        // Iterate over all rows with the given index
        $(`#item_location_table tr[item-index="${index}"]`).each(function () {
            const rowQty = parseInt($(this).find(`#item_qty_${index}_${$(this).attr('row-index')}`).val() || 0, 10);
            totalStoreQty += isNaN(rowQty) ? 0 : rowQty;
        });

        const remainingQty = parseInt(item_qty.value, 10) - totalStoreQty;

        // Update total value in an input element
        $('#total').text(totalStoreQty);

        // Check if remaining quantity is negative
        if (remainingQty < 0) {
            const currentStoreQty = parseInt($(`#item_qty_${index}_${storeIndex}`).val() || 0, 10);
            const adjustedQty = totalStoreQty - currentStoreQty;
            // Update total and reset the offending row's quantity
            $('#total').text(adjustedQty);
            $(`#item_qty_${index}_${storeIndex}`).val(0);
            Swal.fire({
                title: 'Error!',
                text: 'Quantity exceeds the total allowed quantity.',
                icon: 'error'
            });
            return; // Exit the function
        }
    }
        function setItemLot(element) {
            const lotData = JSON.parse(element.getAttribute('lot-data'));
            const lotTable = document.getElementById('bundle_schedule_table');
            const itemIndex = element.closest('tr').getAttribute('id').split('_')[2];
            const itemQtyInput = document.getElementById(`item_qty_${itemIndex}`);
            const itemQty = parseFloat(itemQtyInput.value);
            let lotHTML = '';
            let totalLotQty = 0;
            let totalSrLotQty = 0;
            lotData.forEach((lot, index) => {
                const remainingQty = Math.min(Number(lot.lot_qty), Number(itemQty) - Number(totalLotQty));
                totalMrLotQty = lot.total_lot_qty ?? lot.lot_qty;
                totalLotQty += remainingQty;
                lotHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${lot.lot_number}</td>
                        <td>${lot.manufacturing_year ?? " "}</td>
                        <td>${lot.expiry_date ?? " "}</td>
                        <td>${totalMrLotQty}</td>
                        <td class='d-none'>${lot.original_receipt_date}</td>
                        <td>
                            <input type="number" class="form-control disable_on_edit lot-quantity-input" id="lot_quantity_${index}" max="${Number(totalMrLotQty)}" value="${Number(remainingQty)}" oninput="validateLotQuantity(this, ${Number(totalMrLotQty)})" />
                        </td>
                    </tr>
                `;
            });

        lotTable.innerHTML = lotHTML;
        feather.replace();

        lotTable.setAttribute('data-item-index', itemIndex);
        $('#lot').modal('show');
        viewModeScript();

    }

    function validateLotQuantity(input, maxQuantity) {
        const lotTable = document.getElementById('bundle_schedule_table');
        const itemIndex = lotTable.getAttribute('data-item-index');
        const itemQtyInput = document.getElementById(`item_qty_${itemIndex}`);
        const itemQty = parseFloat(itemQtyInput.value);

        if (parseFloat(input.value) > maxQuantity) {
            input.value = maxQuantity;
            Swal.fire({
                title: 'Error!',
                text: `Quantity cannot exceed ${maxQuantity} for this lot.`,
                icon: 'error',
            });
        }

        const lotRows = lotTable.querySelectorAll('tr');
        let totalLotQty = 0;
        lotRows.forEach(row => {
            const lotQtyInput = row.querySelector('input[type="number"]');
            totalLotQty += parseFloat(lotQtyInput.value || 0);
        });

        if (totalLotQty > itemQty) {
            input.value = Math.max(0, parseFloat(input.value) - (totalLotQty - itemQty));
            Swal.fire({
                title: 'Error!',
                text: `Total lot quantity cannot exceed ${itemQty}.`,
                icon: 'error',
            });
        }
    }

    function saveLotData() {
        const lotTable = document.getElementById('bundle_schedule_table');
        const itemIndex = lotTable.getAttribute('data-item-index');
        const itemQtyInput = document.getElementById(`item_qty_${itemIndex}`);
        const itemQty = parseFloat(itemQtyInput.value);
        const lotRows = lotTable.querySelectorAll('tr');
        const lotData = [];
        let totalLotQty = 0;

        lotRows.forEach((row,index) => {
            const lotNumber = row.children[1].textContent;
            const manuf_year = row.children[2].textContent ?? null;
            const expiry = row.children[3].textContent ?? null;
            const lot_qty = row.children[4].textContent ?? null;
            const ret_qty = row.children[5].textContent ?? null;
            const receiptDate = row.children[6].textContent ?? null;
            const quantity = parseFloat(row.querySelector(`#lot_quantity_${index}`).value || 0);
            if (quantity <= 0) {
                Swal.fire({
                    title: 'Error!',
                    text: `Lot quantity must be greater than zero.`,
                    icon: 'error',
                });
                return;
            }
            totalMrLotQty = row.querySelector(`#lot_quantity_${index}`).getAttribute('max');
            totalLotQty += quantity;

            lotData.push({
                lot_number: lotNumber,
                manufacturing_year : manuf_year,
                expiry_date : expiry,
                original_receipt_date: receiptDate,
                lot_qty: quantity,
                total_lot_qty : totalMrLotQty
            });
        });
        if (totalLotQty !== itemQty) {
            Swal.fire({
                title: 'Error!',
                text: `Total lot quantity (${totalLotQty}) must equal the item quantity (${itemQty}).`,
                icon: 'error',
            });
            return;
        }

        const lotInput = document.getElementById(`item_lots_${itemIndex}`);
        if (lotInput) {
            lotInput.value = JSON.stringify(lotData);
        }

        $('#lot').modal('hide');
    }

    // function removeLotRow(button) {
    //     const row = button.closest('tr');
    //     row.remove();
    // }

    function addStore(){
        const index = $('#item_location_table tr').last().attr('item-index');
        const row_no = $('#item_location_table tr').last().attr('row-index');
        const storeElement = document.getElementById('item_store_' + index);
        const storeData = document.getElementById('data_stores_' + index);
        const item_qty = document.getElementById('item_qty_'+index);
        const storeTable = document.getElementById('item_location_table');
        let totalStoreQty=0;
        let storesInnerHtml='';
        if (storeElement) {
            // Calculate the sum of all existing quantities
            $(`#item_location_table tr[item-index="${index}"]`).each(function () {
                const rowQty = parseInt($(this).find(`#item_qty_${index}_${$(this).attr('row-index')}`).val() || 0, 10);
                totalStoreQty += isNaN(rowQty) ? 0 : rowQty;
            });
            // Check if adding a new store exceeds the total item quantity
            const remainingQty = parseInt(item_qty.value, 10) - totalStoreQty;
            if (remainingQty <= 0) {
                Swal.fire({
                    title: 'Error!',
                    text: ' No further quantity left to add.',
                    icon: 'error'
                });
                return; // Exit the function
            }

            else{
                const total_qty=0;
                const storesData = JSON.parse(decodeURIComponent(storeData.getAttribute('data-stores')));
                if (storesData && storesData.length > 0) {
                    storesData.forEach((store, storeIndex) => {
                        const rackOptions = store.rack_data
                            ? store.rack_data.map(rack => `<option value="${rack.id}">${rack.rack_code}</option>`).join('')
                            : '<option value="">No Racks Available</option>';

                        const binOptions = store.bin_data
                            ? store.bin_data.map(bin => `<option value="${bin.id}">${bin.bin_code}</option>`).join('')
                            : '<option value="">No Bins Available</option>';

                        const shelfOptions = store.shelf_data
                            ? store.shelf_data.map(shelf => `<option value="${shelf.id}">${shelf.shelf_code}</option>`).join('')
                            : '<option value="">Select Shelf</option>';

                        storesInnerHtml += `
                        <tr row-index=${row_no+1} item-index=${index}  id="store_row_${index}_${Number(row_no)+1}">
                        <td>${Number(row_no)+2}</td>
                        <td>
                                <select class="form-control disable_on_edit mw-100" id="rack_data_${index}_${Number(row_no)+1}" oninput="getShelf(${index},${Number(row_no)+1})">
                                    <option value ="">Select Rack</option>
                                    ${rackOptions}
                                </select>
                                <input type="hidden" id="hidden_rack_data_${index}_${Number(row_no)+1}" name="hidden_rack_data_${index}_${Number(row_no)+1}">
                            </td>
                            <td>
                            <select class="form-control disable_on_edit mw-100" id="shelf_data_${index}_${Number(row_no)+1}">
                                <option value ="">Select Shelf</option>
                                ${shelfOptions}
                                </select>
                                <input type="hidden" id="hidden_shelf_data_${index}_${Number(row_no)+1}" name="hidden_shelf_data_${index}_${Number(row_no)+1}">
                            </td>
                            <td>
                                <select class="form-control disable_on_edit mw-100" id="bin_data_${index}_${Number(row_no)+1}">
                                <option value ="">Select Bin</option>
                                ${binOptions}
                                </select>
                                <input type="hidden" id="hidden_bin_data_${index}_${Number(row_no)+1}" name="hidden_bin_data_${index}_${Number(row_no)+1}">
                                </td>
                                <td>
                                <input type="text" class="form-control disable_on_edit" id="item_qty_${index}_${Number(row_no)+1}" value="${store.qty || 0}" oninput="totalQuantityCheck(${index},${Number(row_no)+1})">
                                <input type="hidden" id="hidden_item_qty_${index}_${Number(row_no)+1}" name="hidden_item_qty_${index}_${Number(row_no)+1}">
                            </td>
                            <td><i class='fas fa-trash text-danger disable_on_edit' onclick=removeStoreRow(${index},${Number(row_no)+1}) data-feather='trash'></i></td>
                        </tr>
                        `;
                        totalStoreQty += parseFloat(store.qty || 0);
                    });
                }
                const newRow = storeTable.insertRow(); // Add a new row at the end of the table
                newRow.innerHTML = storesInnerHtml;
                // Set attributes correctly
                newRow.setAttribute('id', `store_row_${index}_${Number(row_no) + 1}`);
                newRow.setAttribute('item-index', `${index}`);
                newRow.setAttribute('row-index', `${Number(row_no) + 1}`);

                // storeTable.innerHTML += storesInnerHtml;
                return;
            }
        }
    }
    function removeItemStore(index, itemIndex)
    {
        const storeElement = document.getElementById('data_stores_' + itemIndex);
        if (storeElement) {
            const storesData = JSON.parse(decodeURIComponent(storeElement.getAttribute('data-stores')));
            if (storesData && storesData.length > 0) {
                storesData.splice(index, 1);
                storeElement.setAttribute('data-stores', encodeURIComponent(JSON.stringify(storesData)));
                openStoreLocationModal(itemIndex);
            }
        }
    }
    
    function SubmitLocation() {
        const rows = $('#item_location_table tr'); // Select all table rows
        const index = $('#item_location_table tr').last().attr('item-index'); // Get item index
        const item_qty = document.getElementById('item_qty_'+index);

        let totalStoreQty = 0;
        $(`#item_location_table tr[item-index="${index}"]`).each(function () {
            const rowQty = parseInt($(this).find(`#item_qty_${index}_${$(this).attr('row-index')}`).val() || 0, 10);
            totalStoreQty += isNaN(rowQty) ? 0 : rowQty;
        });
        // Check if adding a new store exceeds the total item quantity
        const remainingQty = parseInt(item_qty.value, 10) - totalStoreQty;
        if (remainingQty != 0) {
            Swal.fire({
                title: 'Error!',
                text: 'Quantity not Equal to total Quantity .',
                icon: 'error'
            });
            return; // Exit the function
        }
        let storeData = [];
        rows.each(function (rowIndex) {
            // Use jQuery's .each() for iteration

            // Extract and validate values
            const store_id = $(`#item_store_${index}`).val();
            const store_code = $(`#item_store_${index} option:selected`).text();
            const rack_id = $(`#rack_data_${index}_${rowIndex}`).val();
            const rack_code = $(`#rack_data_${index}_${rowIndex} option:selected`).text();
            const shelf_id = $(`#shelf_data_${index}_${rowIndex}`).val();
            const shelf_code = $(`#shelf_data_${index}_${rowIndex} option:selected`).text();
            const bin_id = $(`#bin_data_${index}_${rowIndex}`).val();
            const bin_code = $(`#bin_data_${index}_${rowIndex} option:selected`).text();
            const store_qty = $(`#item_qty_${index}_${rowIndex}`).val();

            // Validation checks
            const errors = [];
            if (!store_id || isNaN(Number(store_id))) errors.push(`Invalid store_id: ${store_id}`);
            if (!store_code || typeof store_code !== 'string') errors.push(`Invalid store_code: ${store_code}`);
            // If there are errors, log them and skip this row
            if (errors.length > 0) {
                alert(`Please enter all valid fields in Row ${rowIndex}`);
                return; // Skip this row and continue to the next
            }

            // Push valid data into storeData array
            storeData.push({
                store_id: Number(store_id),
                store_code,
                rack_id: Number(rack_id),
                rack_code,
                shelf_id: Number(shelf_id),
                shelf_code,
                bin_id: Number(bin_id),
                bin_code,
                store_qty: Number(store_qty),
            });
        });
        item_qty.setAttribute('data-stores', JSON.stringify(storeData));
        closeModal('deliveryScheduleModal');
    }


    function addItemStore()
    {
        const itemIndex = document.getElementById('item_location_table').getAttribute('current-item-index');

        const itemStoreId = $("#new_store_id_input").val();
        const itemStoreCode = $("#new_store_code_input").val();

        const itemRackId = $("#new_rack_id_input").val();
        const itemRackCode = $("#new_rack_code_input").val();

        const itemShelfId = $("#new_shelf_id_input").val();
        const itemShelfCode = $("#new_shelf_code_input").val();

        const itemBinId = $("#new_bin_id_input").val();
        const itemBinCode = $("#new_bin_code_input").val();

        const itemStoreQty = $("#new_location_qty").val();

        if (itemStoreId && itemStoreCode && itemRackId && itemRackCode && itemShelfId && itemShelfCode && itemBinId && itemBinCode && itemStoreQty) {
            const newStoreItem = {
                store_id : itemStoreId,
                store_code : itemStoreCode,
                rack_id : itemRackId,
                rack_code : itemRackCode,
                shelf_id : itemShelfId,
                shelf_code : itemShelfCode,
                bin_id : itemBinId,
                bin_code : itemBinCode,
                qty : itemStoreQty
            };
            const storeElement = document.getElementById('data_stores_' + itemIndex);
            if (storeElement) {
                const storesData = JSON.parse(decodeURIComponent(storeElement.getAttribute('data-stores')));
                if (storesData) {
                    storesData.push(newStoreItem);
                    storeElement.setAttribute('data-stores', encodeURIComponent(JSON.stringify(storesData)));
                    openStoreLocationModal(itemIndex);
                    resetStoreFields();
                }
            }
        }
    }

    function initializeAutocompleteStores(selector, siblingSelector, type, labelField) {
        let modalId = '#'+$("#" + selector).closest('.modal').attr('id');
        $("#" + selector).autocomplete({
            source: function(request, response) {
                let dataPayload = {
                    q:request.term,
                    type : type
                };
                if (type == "store_rack") {
                    dataPayload.store_id = $("#new_store_id_input").val()
                }
                if (type == "rack_shelf") {
                    dataPayload.rack_id = $("#new_rack_id_input").val()
                }
                if (type == "shelf_bin") {
                    dataPayload.shelf_id = $("#new_shelf_id_input").val()
                }
                $.ajax({
                    url: '/search',
                    method: 'GET',
                    dataType: 'json',
                    data: dataPayload,
                    success: function(data) {
                        response($.map(data, function(item) {
                            return {
                                id: item.id,
                                label: item[labelField],
                            };
                        }));
                    },
                    error: function(xhr) {
                        console.error('Error fetching customer data:', xhr.responseText);
                    }
                });
            },
            minLength: 0,
            appendTo : modalId,
            select: function(event, ui) {
                var $input = $(this);
                var itemCode = ui.item.label;
                var itemId = ui.item.id;
                $input.val(itemCode);
                $("#" + siblingSelector).val(itemId);
                return false;
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $(this).val("");
                }
            }
        }).focus(function() {
            if (this.value === "") {
                $(this).autocomplete("search", "");
            }
        });
    }

    function resetStoreFields()
    {
        $("#new_store_id_input").val("")
        $("#new_store_code_input").val("")

        $("#new_rack_id_input").val("")
        $("#new_rack_code_input").val("")

        $("#new_shelf_id_input").val("")
        $("#new_shelf_code_input").val("")

        $("#new_bin_id_input").val("")
        $("#new_bin_code_input").val("")

        $("#new_location_qty").val("")
    }

    function saveAddressShipping()
    {
        $.ajax({
            url: "{{route('sales_order.add.address')}}",
            method: 'POST',
            dataType: 'json',
            data: {
                type: 'shipping',
                country_id: $("#shipping_country_id_input").val(),
                state_id: $("#shipping_state_id_input").val(),
                city_id: $("#shipping_city_id_input").val(),
                address: $("#shipping_address_input").val(),
                pincode: $("#shipping_pincode_input").val(),
                phone: '',
                fax: '',
                customer_id : $("#customer_id_input").val()
            },
            success: function(data) {
                if (data && data.data) {
                    $("#edit-address-shipping").modal("hide");
                    $("#current_shipping_address_id").val(data.data.id);
                    $("#current_shipping_country_id").val(data.data.country_id);
                    $("#current_shipping_state_id").val(data.data.state_id);
                    $("#current_shipping_address").text(data.data.display_address);
                    var newOption = new Option(data.data.display_address, data.data.id, false, false);
                    $('#shipping_address_dropdown').append(newOption).trigger('change');
                    $("#shipping_address_dropdown").val(data.data.id).trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error fetching customer data:', xhr.responseText);
            }
        });
    }

    function saveAddressBilling(type)
    {
        $.ajax({
            url: "{{route('sales_order.add.address')}}",
            method: 'POST',
            dataType: 'json',
            data: {
                type: 'billing',
                country_id: $("#billing_country_id_input").val(),
                state_id: $("#billing_state_id_input").val(),
                city_id: $("#billing_city_id_input").val(),
                address: $("#billing_address_input").val(),
                pincode: $("#billing_pincode_input").val(),
                phone: '',
                fax: '',
                customer_id : $("#customer_id_input").val()
            },
            success: function(data) {
                if (data && data.data) {
                    $("#edit-address-billing").modal("hide");
                    $("#current_billing_address_id").val(data.data.id);
                    $("#current_billing_country_id").val(data.data.country_id);
                    $("#current_billing_state_id").val(data.data.state_id);
                    $("#current_billing_address").text(data.data.display_address);
                    var newOption = new Option(data.data.display_address, data.data.id, false, false);
                    $('#billing_address_dropdown').append(newOption).trigger('change');
                    $("#billing_address_dropdown").val(data.data.id).trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error fetching customer data:', xhr.responseText);
            }
        });
    }

    function onBillingAddressChange(element)
    {
        $("#current_billing_address_id").val(element.value);
        $('#billAddressEditBtn').click();
    }

    function onShippingAddressChange(element)
    {
        $("#current_shipping_address_id").val(element.value);
        $('#shipAddressEditBtn').click();
    }
    $(document).on('click', '#amendmentSubmit', (e) => {
    let actionUrl = "{{ route('sale.return.amend', isset($order) ? $order -> id : 0) }}";
    fetch(actionUrl).then(response => {
        return response.json().then(data => {
            if (data.status == 200) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success'
                });
                location.reload();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error'
                });
            }
            });
        });
    });
    function getShelf(item_index,store_index){
        const rack_id = $('#rack_data_' + item_index + '_' + store_index).val();
        $.ajax({
            url: "{{route('get_shelfs')}}",
            method: 'GET',
            data: {
                rack_id: rack_id,
            },
            success: function(data) {
                if(data && data.shelfs){
                    const shelfOptions = `<option value="">Select Shelf</option>` +
                        (data.shelfs.map(shelf => `<option value="${shelf.id}">${shelf.shelf_code}</option>`).join('') ||
                            '<option value="">Select Shelf</option>');
                    $(`#shelf_data_${item_index}_${store_index}`).empty(); // Clear existing options
                    $(`#shelf_data_${item_index}_${store_index}`).append(shelfOptions); // Append new options

                }
            },
            error: function(xhr) {
                console.error('Error fetching data:', xhr.responseText);
                $(`#shelf_data_${item_index}_${store_index}`).empty(); // Clear existing options
                const shelfOptions = '<option value="">Select Shelf</option>';
                $(`#shelf_data_${item_index}_${store_index}`).append(shelfOptions); // Append new options

            }
        });
    }



function resetPostVoucher()
{
    document.getElementById('voucher_doc_no').value = '';
    document.getElementById('voucher_date').value = '';
    document.getElementById('voucher_book_code').value = '';
    document.getElementById('voucher_currency').value = '';
    document.getElementById('posting-table').innerHTML = '';
    document.getElementById('posting_button').style.display = 'none';
}

function onPostVoucherOpen(type = "not_posted")
{
    document.getElementById('erp-overlay-loader').style.display = "flex";
    resetPostVoucher();
    const apiURL = "{{route('sale.return.posting.get')}}";
    $.ajax({
        url: apiURL + "?book_id=" + $("#series_id_input").val() + "&document_id=" + "{{isset($order) ? $order -> id : ''}}",
        type: "GET",
        dataType: "json",
        success: function(data) {
            document.getElementById('erp-overlay-loader').style.display = "none";

            if (!data.data.status) {
                Swal.fire({
                    title: 'Error!',
                    text: data.data.message,
                    icon: 'error',
                });
                return;
            }
            const voucherEntries = data.data.data;
            var voucherEntriesHTML = ``;
            Object.keys(voucherEntries.ledgers).forEach((voucher) => {
                voucherEntries.ledgers[voucher].forEach((voucherDetail, index) => {
                    voucherEntriesHTML += `
                    <tr>
                    <td>${voucher}</td>
                    <td class="fw-bolder text-dark">${voucherDetail.ledger_group_code ? voucherDetail.ledger_group_code : ''}</td>
                    <td>${voucherDetail.ledger_code ? voucherDetail.ledger_code : ''}</td>
                    <td>${voucherDetail.ledger_name ? voucherDetail.ledger_name : ''}</td>
                    <td class="text-end">${voucherDetail.debit_amount > 0 ? parseFloat(voucherDetail.debit_amount).toFixed(2) : ''}</td>
                    <td class="text-end">${voucherDetail.credit_amount > 0 ? parseFloat(voucherDetail.credit_amount).toFixed(2) : ''}</td>
					</tr>
                    `
                });
            });
            voucherEntriesHTML+= `
            <tr>
                <td colspan="4" class="fw-bolder text-dark text-end">Total</td>
                <td class="fw-bolder text-dark text-end">${voucherEntries.total_debit.toFixed(2)}</td>
                <td class="fw-bolder text-dark text-end">${voucherEntries.total_credit.toFixed(2)}</td>
			</tr>
            `;
            document.getElementById('posting-table').innerHTML = voucherEntriesHTML;
            document.getElementById('voucher_doc_no').value = voucherEntries.document_number;
            document.getElementById('voucher_date').value = moment(voucherEntries.document_date).format('D/M/Y');
            document.getElementById('voucher_book_code').value = voucherEntries.book_code;
            document.getElementById('voucher_currency').value = voucherEntries.currency_code;
            if (type === "posted") {
                document.getElementById('posting_button').style.display = 'none';
            } else {
                document.getElementById('posting_button').style.removeProperty('display');
            }
            $('#postvoucher').modal('show');
        }
    });

}

function postVoucher(element)
{
    const bookId = "{{isset($order) ? $order -> book_id : ''}}";
    const documentId = "{{isset($order) ? $order -> id : ''}}";
    const postingApiUrl = "{{route('sale.return.post')}}"
    if (bookId && documentId) {
        $.ajax({
            url: postingApiUrl,
            type: "POST",
            dataType: "json",
            contentType: "application/json", // Specifies the request payload type
            data: JSON.stringify({
                // Your JSON request data here
                book_id: bookId,
                document_id: documentId,
            }),
            success: function(data) {
                const response = data.data;
                if (response.status) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success',
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,
                        icon: 'error',
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                let errorReponse = jqXHR.responseJSON;
                console.log(errorReponse);
                if (errorReponse?.message) {
                    Swal.fire({
                        title: 'Error!',
                        text: errorReponse?.message,
                        icon: 'error',
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Some internal error occured',
                        icon: 'error',
                    });
                }

            }
        });

    }
}
function initializeAutocompleteTed(selector, idSelector, type, percentageVal) {
    let modalId = '#'+$("#" + selector).closest('.modal').attr('id');
            $("#" + selector).autocomplete({
                source: function(request, response) {
                    var selectedDiscountIds = [];
                    if (type === "sales_module_discount") {
                        if (selector == "new_order_discount_name") {
                            var salesDiscountIdElement = document.getElementsByClassName('order_discount_master_id_hidden');
                            for (let index = 0; index < salesDiscountIdElement.length; index++) {
                                selectedDiscountIds.push(salesDiscountIdElement[index].value);
                            }
                        } else if (selector == "new_discount_name") {
                            var itemIndex = document.getElementById('discount_main_table').getAttribute('item-row-index');
                            var salesDiscountIdElement = document.getElementsByClassName('discount_master_ids_hidden_' + itemIndex);
                            for (let index = 0; index < salesDiscountIdElement.length; index++) {
                                selectedDiscountIds.push(salesDiscountIdElement[index].value);
                            }
                        }
                    }
                    $.ajax({
                        url: '/search',
                        method: 'GET',
                        dataType: 'json',
                        data: {
                            q: request.term,
                            type:type,
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    id: item.id,
                                    label: `${item.name}`,
                                    percentage: `${item.percentage}`,
                                };
                            }));
                        },
                        error: function(xhr) {
                            console.error('Error fetching customer data:', xhr.responseText);
                        }
                    });
                },
                minLength: 0,
                appendTo : modalId,
                select: function(event, ui) {
                    var $input = $(this);
                    var itemName = ui.item.label;
                    var itemId = ui.item.id;
                    var itemPercentage = ui.item.percentage;

                    $input.val(itemName);
                    $("#" + idSelector).val(itemId);
                    $("#" + percentageVal).val(itemPercentage).trigger('input');
                    return false;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $(this).val("");
                        $("#" + idSelector).val("");
                    }
                }
            }).focus(function() {
                if (this.value === "") {
                    $(this).autocomplete("search", "");
                }
            });
    }

    function resetDiscountOrExpense(element, percentageFieldId)
    {
        if(!element.value) {
            $("#" + percentageFieldId).val('').trigger('input');
        }
    }

    function onOrderDiscountModalOpen()
    {
        initializeAutocompleteTed("new_order_discount_name", "new_order_discount_id", 'sales_module_discount', 'new_order_discount_percentage');
    }

    function onOrderExpenseModalOpen()
    {
        initializeAutocompleteTed("order_expense_name", "order_expense_id", 'sales_module_expense', 'order_expense_percentage');
    }
    function checkOrRecheckAllItems(element)
    {
        const allRowsCheck = document.getElementsByClassName('item_row_checks');
        const checkedStatus = element.checked;
        for (let index = 0; index < allRowsCheck.length; index++) {
            allRowsCheck[index].checked = checkedStatus;
        }
    }

    function checkAllQt(element)
    {
        const selectableElements = document.getElementsByClassName('pull_checkbox');
        for (let index = 0; index < selectableElements.length; index++) {
            if (!selectableElements[index].disabled) {
                selectableElements[index].checked = element.checked;
                // if (openPull)
                // if (element.checked) {
                //     checkQuotation(selectableElements[index]);
                // }
            }
        }
    }

    function implementSeriesChange(val)
    {
        //COMMON CHANGES
        document.getElementById("type_hidden_input").value = val;
        // const generalInfoTab = document.getElementById('general_information_tab');
        const itemDetailTd = document.getElementById('item_details_td');
        const invoiceSummaryTd = document.getElementById('invoice_summary_td');
        const breadCrumbHeading = document.getElementById('breadcrumb-document-heading');

        // generalInfoTab.style.display = 'none';
        itemDetailTd.setAttribute('colspan', 7);
        // invoiceSummaryTd.style.removeProperty('display');
        breadCrumbHeading.textContent = "Sales Return";
    }



    function generateEInvoice(id)
    {
        document.getElementById('erp-overlay-loader').style.display = "flex";
        $.ajax({
            url: "{{route('sale.return.generate.einvoice')}}",
            method: 'POST',
            dataType: 'json',
            data: {
                id : id,
                transporter_mode : $("#transporter_mode_input").val(),
                transporter_name : $("#transporter_name_input").val(),
                vehicle_no : $("#vehicle_no_input").val(),
            },
            success: function(data) {
                document.getElementById('erp-overlay-loader').style.display = "none";
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                });
                setTimeout(() => {
                    window.location.reload();
            }, 1500);
            },
            error: function(xhr) {
                document.getElementById('erp-overlay-loader').style.display = "flex";
                console.error('Error fetching customer data:', xhr);
                Swal.fire({
                    title: 'Warning!',
                    text: xhr?.responseJSON?.message,
                    icon: xhr?.responseJSON?.redirect ? 'warning' : 'error',
                }).then((result) => {
                    if (xhr?.responseJSON?.redirect) {
                        window.location.reload();
                    }
                });
            }
        });
    }

    function generateEwayBill()
    {
        document.getElementById('erp-overlay-loader').style.display = "flex";
        let orderId = "{{isset($order) ? $order -> id : ''}}";
        $.ajax({
            url: "{{route('sale.return.generate.ewayBill')}}",
            method: 'POST',
            dataType: 'json',
            data: {
                id : orderId,
                transporter_mode : $("#transporter_mode_input").val(),
                transporter_name : $("#transporter_name_input").val(),
                vehicle_no : $("#vehicle_no_input").val(),
            },
            success: function(data) {
                document.getElementById('erp-overlay-loader').style.display = "none";
                if(data.status == 'error') {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message,
                        icon: 'error',
                    });
                    return false;
                } else {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                    });
                    location.reload();
                }
            },
            error: function(xhr) {
                document.getElementById('erp-overlay-loader').style.display = "none";
                console.error('Error fetching customer data:', xhr);
                Swal.fire({
                    title: 'Error!',
                    text: xhr?.responseJSON?.message,
                    icon: 'error',
                })
            }
        });
    }
    $("#store_id_input").on('change', function() {
        const storeId = $(this).val();
        // $("#item_header").html('');
        const sub_store_id = "{{ isset($order) && $order->sub_store_id ? $order->sub_store_id : '' }}";
        if (storeId) {
            $.ajax({
                url: "{{ route('subStore.get.from.stores') }}",
                method: 'GET',
                dataType: 'json',
                data: {
                store_id: storeId,
                types : ['Stock', 'Shop floor','Vendor','Other'],
                },
                success: function(data) {
                if (data.data && data.data.length > 0) {
                    let options = '';
                    data.data.forEach(function(subStore) {
                        options += `<option value="${subStore.id}" consumption="${subStore.station_wise_consumption}" ${subStore.id == sub_store_id ? 'selected' : ''}>${subStore.name}</option>`;
                    });
                    $('#sub_store_id_input').empty().html(options);
                }
                else{
                    $('#sub_store_id_input').empty();
                    Swal.fire({
                        title: 'Error!',
                        text: 'No Store Found On this Location.',
                        icon: 'warning',
                    });
                }
                // Handle the response data as needed
                },
                error: function(xhr) {
                console.error('Error fetching sub-stores:', xhr.responseText);
                }
            });
        }
    });
    // Open batch modal
    $(document).on("click", ".addBatchBtn", function (e) {
        e.preventDefault();
        const rowIndex = $(this).data("row-count");
        const isExpiry = $(this).data("is-expiry") || 0;
        const batchset = $(this).data("is-batch-number") == 1 ? false : true;
        currentBatchRowIndex = rowIndex;

        $("#itemBatchRowIndex").val(rowIndex);
        $("#itemBatchIsExpiry").val(isExpiry);  
        $("#itemBatchIsEdit").val($(this).data('is-batch-number') || '0');
        const item = $(`#items_dropdown_${rowIndex}`).val();
        if (!item) {
            $("#item-batch-modal").modal("hide");
            Swal.fire({
                title: 'Error!',
                text: 'Please select an Item first.',
                icon: 'error',
            });
            return;
        }
        const total_qty = $(`#item_qty_${rowIndex}`).val();
        if(!total_qty || total_qty <= 0) {
            $("#item-batch-modal").modal("hide");
            Swal.fire({
                title: 'Error!',
                text: 'Please enter a positive Return Quantity before adding batches.',
                icon: 'error',
            });
            return;
        }
        // Clear table
        $("#itemBatchTable tbody").empty();

        // Load existing batches if any
        let batchVal = $("#batches_" + rowIndex).val();
        let existing = [];
        try {
            if (batchVal && /^[\[\{]/.test(batchVal)) {
                existing = JSON.parse(batchVal);
            }
        } catch (e) {
            existing = [];
        }
        existing.forEach((batch, idx) => {
            $("#itemBatchTable tbody").append(generateBatchRow(idx, batch , batchset) );
        });

        // Ensure one row exists
        if (existing.length === 0) {
            batchData = generateNewBatch(rowIndex);

            $("#itemBatchTable tbody").append(generateBatchRow(0 , batchData , batchset) );
        }
        $("#totalBatchQty").text(total_qty);
        $("#item-batch-modal").modal("show");
        renderIcons();
    });

    // Add new batch row
    $(document).on("click", ".add-batch-row-header", function () {
        const tbody = $("#itemBatchTable tbody");
        const row = tbody.find("tr");
        const index = row.length;
        const totalQty = parseFloat($("#totalBatchQty").text()) || 0;

        // Calculate current total batch qty
        let batchQtySum = 0;
        tbody.find(".lot_qty").each(function () {
            batchQtySum += parseFloat($(this).val()) || 0;
        });

        // Only allow adding if batchQtySum < totalQty
        if (batchQtySum >= totalQty) {
            Swal.fire({
                title: 'Error!',
                text: 'Total batch quantity already matches Returned Quantity. Cannot add more batches.',
                icon: 'error',
            });
            return;
        }

        tbody.append(generateBatchRow(index));
    });

    // Delete selected batch rows
    $(document).on("click", ".delete-batch-row-header", function () {
        const tbody = $("#itemBatchTable tbody");
        const checkedRows = tbody.find(".batch-row-check:checked");
        const totalRows = tbody.find("tr").length;

        // Prevent deleting if only one row remains
        if (checkedRows.length >= totalRows) {
            Swal.fire({
                title: 'Error!',
                text: 'At least one batch row must remain.',
                icon: 'error',
            });
            return;
        }

        checkedRows.each(function () {
            // Only remove if more than one row remains
            if (tbody.find("tr").length > 1) {
                const currentQty = parseFloat($(this).closest("tr").find(".lot_qty").val()) || 0;
                const allRows = tbody.find("tr");
                // Find the last row that is NOT being deleted
                let lastAvailableRow = null;
                allRows.each(function () {
                const checkbox = $(this).find(".batch-row-check");
                if (!checkbox.is(":checked")) {
                    lastAvailableRow = $(this);
                }
                });
                // If found, move all qty to the last available row
                if (lastAvailableRow && currentQty > 0) {
                const lastQtyInput = lastAvailableRow.find(".lot_qty");
                let lastQty = parseFloat(lastQtyInput.val()) || 0;
                lastQtyInput.val(lastQty + currentQty);
                }
                $(this).closest("tr").remove();
            }
            });
            // If only one row remains after deletion, ensure its qty equals totalBatchQty
            if (tbody.find("tr").length === 1) {
            const totalQty = parseFloat($("#totalBatchQty").text()) || 0;
            tbody.find(".lot_qty").val(totalQty);
            }

        // re-index
        tbody.find("tr").each(function (i) {
            $(this).find("td:first").text(i + 1);
        });
    });

    // Save batch modal
    // Ensure click is bound only once
$("#saveItemBatchBtn").off("click").on("click", function () {
    const rowIndex = $("#itemBatchRowIndex").val();
    let batches = [];
    let errorMsg = "";
    let totalBatchQty = 0;
    let allFilled = true;

    const isBatchEditable = $(`.addBatchBtn[data-row-count="${rowIndex}"]`).data("is-batch-number") == 1;
    const currentYear = new Date().getFullYear();
    const today = new Date().toISOString().split('T')[0];

    // Collect all batch numbers first for duplicate check
    let batchNumbers = [];
    $("#itemBatchTable tbody .lot-number").each(function () {
        batchNumbers.push($(this).val().trim());
    });

        $("#itemBatchTable tbody tr").each(function (index) {
            const batchNumber = $(this).find(".lot-number").val().trim();
            const mfgYear = $(this).find(".mfg-year").val();
            const expiryDate = $(this).find(".expiry-date").val();
            const qty = parseFloat($(this).find(".lot_qty").val()) || 0;
            const receiptDate = $(this).find(".batch-receipt_date").val();

            if (isBatchEditable) {
                // Validate batch number
                if (!batchNumber) {
                    allFilled = false;
                    errorMsg = "Batch number is required.";
                } else {
                    const duplicateCount = batchNumbers.filter(bn => bn === batchNumber).length;
                    if (duplicateCount > 1) {
                        allFilled = false;
                        errorMsg = "Lot number '" + batchNumber + "' is repeated.";
                    }
                }

                // Validate manufacturing year
                if (!mfgYear || mfgYear < 2000 || mfgYear > currentYear) {
                    allFilled = false;
                    errorMsg = "Manufacturing year must be between 2000 and " + currentYear + ".";
                }

                // Validate expiry date if required
                if ($("#itemBatchIsExpiry").val() == "1") {
                    if (!expiryDate) {
                        allFilled = false;
                        errorMsg = "Expiry date is required.";
                    } else if (expiryDate <= today) {
                        allFilled = false;
                        errorMsg = "Expiry date must be greater than today.";
                    }
                }

                // Validate quantity
                if (qty <= 0) {
                    allFilled = false;
                    errorMsg = "Batch quantity must be greater than zero.";
                }
            }

            if (batchNumber || qty) {
                batches.push({
                    lot_number: batchNumber,
                    manufacturing_year: mfgYear,
                    expiry_date: expiryDate,
                    lot_qty: qty,
                    receipt_date: receiptDate
                });

                // Accumulate total batch quantity correctly
                totalBatchQty += qty;
            }
            console.log(totalBatchQty,qty,this);
        });

        const expectedQty = parseFloat($("#totalBatchQty").text()) || 0;

        // Validation for allFilled and quantity
        if (!allFilled && isBatchEditable) {
            errorMsg = errorMsg || "Please fill all batch details before saving.";
        } else if (totalBatchQty !== expectedQty) {
            errorMsg = "Total batch quantity must match Returned Quantity.";
        }

        if (errorMsg) {
            Swal.fire({
                title: 'Error!',
                text: errorMsg,
                icon: 'error',
            });
            return;
        }

        // Save batches to global object and hidden input
        $(`#batches_${rowIndex}`).val(JSON.stringify(batches));

        // Close modal
        $("#item-batch-modal").modal("hide");

        console.log("Batches saved for row", rowIndex, batches);
        console.log("Total batch quantity:", totalBatchQty);
    });

    // Generate batch row
    function generateBatchRow(index, data = {} ,disabled = false) {
        const batchNumber = data.lot_number || "";
        const mfgYear = data.manufacturing_year || 0;
        const receiptDate = data.original_receipt_date;
        // Parse only the date part from expiry_date
        let expiryDate = "";
        if (data.expiry_date) {
            expiryDate = data.expiry_date.split('T')[0];
        }

        // Calculate qty: if not present, set to (totalBatchQty - sum of previous rows' qty)
        let qty = data.lot_qty;
        if (qty === undefined || qty === null || qty === "") {
            const totalBatchQty = parseFloat($("#totalBatchQty").text()) || 0;

            let prevQtySum = 0;
            $("#itemBatchTable tbody tr").each(function (i) {
                if (i < index) {
                    prevQtySum += parseFloat($(this).find(".lot_qty").val()) || 0;
                }
            });

            qty = (totalBatchQty - prevQtySum) || 0;
        }

        // always keep qty to 6 decimals if numeric
        qty = qty !== "" ? parseFloat(qty).toFixed(6) : "";

        if (disabled || (order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}")) {
            setTimeout(() => {
                $(".add-batch-row-header, .delete-batch-row-header").hide();
            }, 0);
        } else {
            setTimeout(() => {
                $(".add-batch-row-header, .delete-batch-row-header").show();
            }, 0);
        }
        if (order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") {
            $("#saveItemBatchBtn").hide();
        }

        return `
            <tr>
                <td>${index + 1}</td>
                <td><input name='lot-number[]' type="text" ${disabled || (order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") ? "disabled" : ""} class="form-control mw-100 lot-number" value="${batchNumber}"></td>
                <td>
                    <input name='batch-year[]' 
                        type="number" 
                        min="2000" 
                        max="${new Date().getFullYear()}" 
                        ${disabled || (order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") ? "disabled" : ""} 
                        class="form-control mw-100 mfg-year" 
                        value="${mfgYear == 0 ? '' : mfgYear}" 
                        placeholder="YYYY"
                        oninput="if(this.value == '0'){this.value='';}"
                    >
                </td>
                <td>
                    <input name='batch-expiry[]' 
                        type="date" 
                        min="${new Date().toISOString().split('T')[0]}" 
                        ${disabled || Number($("#itemBatchIsExpiry").val()) == 0 || (order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") ? "disabled" : ""} 
                        class="form-control mw-100 expiry-date" 
                        value="${expiryDate}" 
                        oninput="if(this.value && this.value <= '${new Date().toISOString().split('T')[0]}'){Swal.fire('Error','Expiry date must be greater than today','error');this.value='';}"
                    >
                </td>
                <td>
                    <input type="hidden" class="batch-receipt-date" value="${receiptDate ?? null}">
                    <input name='lot_qty[]'
                        type = 'text' 
                        id = 'lot_qty_${index}'
                        type="number" 
                        step="0.000001"
                        ${(order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") ? "disabled" : ""} 
                        class="form-control mw-100 lot_qty" 
                        value="${qty}"
                    >
                </td>
                <td class="text-center">
                    <input type="checkbox" ${(order && order.document_status!="{{ app\Helpers\ConstantHelper::DRAFT }}") ? "disabled" : ""} class="form-check-input batch-row-check">
                </td>
            </tr>
        `;
    }
    function generateNewBatch(rowIndex) {
        return {
            lot_number:  $("#order_date_input").val()+"/"+$("#book_code_input").val()+"/"+$("#order_no_input").val(),
            manufacturing_year: "0000",
            expiry_date: "0000-00-00",
            quantity: $("#item_qty_"+rowIndex).val() || 0,
        };
    }
    $(document).on('input', '[id^="item_qty_"]', function () {
        const $input = $(this);
         // Extract index from id if not passed
        
        const matches = $input.attr('id').match(/item_qty_(\d+)/);
        let index = matches ? matches[1] : 0;
        const itemQty = parseFloat($input.val()) || 0;
        console.log($input,index,itemQty);
        const $batchInput = $("#batches_" + index);
        const prevItemQty = $input.data('prev') || 0; // store previous value
        let batches = JSON.parse($batchInput.val());
        const totalLotQty = batches.reduce((sum, b) => sum + (b.total_lot_qty || 0), 0);
        console.log($input,index,itemQty,$batchInput,totalLotQty);

        if (itemQty > totalLotQty) {
            Swal.fire({
                title: 'Error!',
                text: 'Entered quantity exceeds total available lot quantity!',
                icon: 'error',
            });
            $input.val(prevItemQty); // reset to previous
            return;
        }

        let remainingQty = itemQty;

        // Allocate new lot_qty
        batches = batches.map(batch => {
            if (remainingQty <= 0) {
                batch.lot_qty = 0;
            } else if (remainingQty >= batch.total_lot_qty) {
                batch.lot_qty = batch.total_lot_qty;
                remainingQty -= batch.total_lot_qty;
            } else {
                batch.lot_qty = remainingQty;
                remainingQty = 0;
            }
            return batch;
        });

        // Update hidden input
        $batchInput.val(JSON.stringify(batches));

        // Store current as previous for next change
        $input.data('prev', itemQty);
    });
    $("#type_input").on('change', function () {
        const val = $(this).val(); // or this.value
        if (val === "0") {
            $("#delivery_note_selection").removeClass('d-none');
        } else {
            $("#delivery_note_selection").addClass('d-none');
        }
    });
    // If your inputs are like <input id="lot_qty_0">, <input id="lot_qty_1"> ...
    $(document).on('input', '[id^="lot_qty_"]', function() {
        let value = $(this).val(); // ✅ correct way
        this.setAttribute('value', value); // keep attribute in sync
    });

    </script>
@endsection
@endsection

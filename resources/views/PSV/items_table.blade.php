<div class="table-responsive pomrnheadtffotsticky">
    <table class="table myrequesttablecbox table-striped po-order-detail custnewpo-detail border newdesignerptable newdesignpomrnpad"> 
        <thead>
            <tr>
                <th class="customernewsection-form">
                    <div class="form-check form-check-primary custom-checkbox">
                        <input type="checkbox" class="form-check-input" id="select_all_items_checkbox" oninput="checkOrRecheckAllItems(this);">
                        <label class="form-check-label" for="select_all_items_checkbox"></label>
                    </div>
                </th>
                <th width="150px">Item Code</th>
                <th width="240px">Item Name</th>
                <th>Attributes</th>
                <th>UOM</th>
                <th class="numeric-alignment">Physical Stock</th>
                <th class="numeric-alignment">Book Stk(Confirmed)</th>
                <th class="numeric-alignment">Book Stk(Unconfirmed)</th>
                <th class="numeric-alignment">Variance</th>
                <th class="numeric-alignment">Rate</th>
                <th class="numeric-alignment">Value</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody class="mrntableselectexcel" id="item_header">
            @if (isset($items) && count($items) > 0)
                @php
                    $docType = $order->document_type;
                @endphp
                @foreach ($items as $orderItemIndex => $orderItem)
                    @php
                        $batchData = $orderItem->batch_details ?? [];
                        $batches = collect($batchData ?? [])
                            ->map(function ($b) {
                                return [
                                    'id' => (int) $b->id,
                                    'batch_number' => (string) $b->batch_number,
                                    'manufacturing_year' => $b->manufacturing_year ? (int) $b->manufacturing_year : null,
                                    'expiry_date' => $b->expiry_date?->toDateString(), // Y-m-d
                                    'quantity' => (float) $b->quantity,
                                ];
                            })
                            ->values();
                    @endphp
                    <tr id="item_row_{{$orderItemIndex}}" class="item_header_rows" onclick="onItemClick('{{$orderItemIndex}}');" data-detail-id="{{$orderItem->id}}" data-id="{{$orderItem->id}}">
                        <input type="hidden" id="psv_item_id_{{$orderItemIndex}}" name="psv_item_id[]" value="{{$orderItem->id}}" {{$orderItem->is_editable ? '' : 'readonly'}}>
                        <td class="customernewsection-form">
                            <div class="form-check form-check-primary custom-checkbox">
                                <input type="checkbox" class="form-check-input item_row_checks" id="item_checkbox_{{$orderItemIndex}}" del-index="{{$orderItemIndex}}">
                                <label class="form-check-label" for="item_checkbox_{{$orderItemIndex}}"></label>
                            </div>
                        </td>
                        <td class="poprod-decpt">
                            <input type="text" id="items_dropdown_{{$orderItemIndex}}" name="item_code[{{$orderItemIndex}}]" placeholder="Select" class="form-control mw-100 ledgerselecct comp_item_code ui-autocomplete-input {{$orderItem->is_editable ? '' : 'restrict'}}" autocomplete="off" data-name="{{$orderItem->item?->item_name}}" data-code="{{$orderItem->item?->item_code}}" data-id="{{$orderItem->item?->id}}" hsn_code="{{$orderItem->item?->hsn?->code}}" item-name="{{$orderItem->item?->item_name}}" specs="{{$orderItem->item?->specifications}}" attribute-array="{{$orderItem->attributes_array}}" value="{{$orderItem->item?->item_code}}" {{$orderItem->is_editable ? '' : 'readonly'}} item-location="[]">
                            <input type="hidden" name="item_id[]" id="items_dropdown_{{$orderItemIndex}}_value" value="{{$orderItem->item_id}}">
                            {{-- @if ($orderItem->mo_item_id)
                                <input type="hidden" name="mo_item_id[{{$orderItemIndex}}]" id="mo_id_{{$orderItemIndex}}" value="{{$orderItem->mo_item_id}}">
                            @endif
                            @if ($orderItem->pwo_item_id)
                                <input type="hidden" name="pwo_item_id[{{$orderItemIndex}}]" id="pwo_id_{{$orderItemIndex}}" value="{{$orderItem->pwo_item_id}}">
                            @endif --}}
                        </td>
                        <td class="poprod-decpt">
                            <input type="text" id="items_name_{{$orderItemIndex}}" class="form-control mw-100" value="{{$orderItem->item?->item_name}}" name="item_name[{{$orderItemIndex}}]" readonly>
                        </td>
                        <td class="poprod-decpt" id="attribute_section_{{$orderItemIndex}}">
                            <button id="attribute_button_{{$orderItemIndex}}" {{count($orderItem->item_attributes_array()) > 0 ? '' : 'disabled'}} type="button" data-bs-toggle="modal" onclick="setItemAttributes('items_dropdown_{{$orderItemIndex}}', '{{$orderItemIndex}}', {{ json_encode(!$orderItem->is_editable) }});" data-bs-target="#attribute" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
                            <input type="hidden" name="attribute_value_{{$orderItemIndex}}">
                        </td>
                        <td>
                            <select class="form-select" name="uom_id[]" id="uom_dropdown_{{$orderItemIndex}}">
                            </select>
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_physical_qty_{{$orderItemIndex}}" value="{{number_format($orderItem->verified_qty, 6, '.', '') }}" name="item_physical_qty[{{$orderItemIndex}}]" oninput='setVariance(this,{{$orderItemIndex}});setValue({{$orderItemIndex}});' class="form-control mw-100 text-end" >
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_confirmed_qty_{{$orderItemIndex}}" 
                            value="{{ $order->document_status !== 'approved' && $order->document_status !== 'approval_not_required' ? 0.00 : $orderItem->confirmed_qty }}" 
                            name="item_confirmed_qty[{{$orderItemIndex}}]" 
                            class="form-control mw-100 text-end" 
                            readonly>
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_unconfirmed_qty_{{$orderItemIndex}}" 
                                value="{{ $order->document_status !== 'approved' && $order->document_status !== 'approval_not_required' ? 0.00 : $orderItem->unconfirmed_qty }}" 
                                name="item_unconfirmed_qty[{{$orderItemIndex}}]" 
                                class="form-control mw-100 text-end" 
                                readonly>
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_variance_qty_{{$orderItemIndex}}" value="{{ number_format($orderItem->verified_qty - $orderItem->confirmed_qty,6,'.','') }}" name="item_balance_qty[{{$orderItemIndex}}]" class="form-control mw-100 text-end" readonly>
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_rate_{{$orderItemIndex}}" value="{{ number_format($orderItem->rate, 6, '.', '')  }}" name="item_rate[${newIndex}]" class="form-control mw-100 text-end" oninput="setValue({{$orderItemIndex}});" >
                        </td>
                        <td class="numeric-alignment">
                            <input type="text" id="item_value_{{$orderItemIndex}}" value="{{ number_format($orderItem->total_amount, 6, '.', '')  }}" name="item_value[${newIndex}]" class="form-control mw-100 text-end" readonly>
                        </td>
                        <td>
                            <div class="d-flex">
                                    <!-- Hidden Asset Detail Input -->
                                <input type="hidden" name="assetDetailData[{{$orderItemIndex}}]" id="assetDetailData_{{$orderItemIndex}}" />

                                <!-- Asset Detail Button -->
                                <div class="cursor-pointer ms-50 text-success assetDetailBtn d-none"
                                    data-row-count="{{$orderItemIndex}}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#assetDetailModal"
                                    title="Asset Detail">
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" class="text-primary"
                                        data-bs-original-title="Asset Detail" aria-label="Asset Detail">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                            class="bi bi-clipboard-check" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M10.854 6.146a.5.5 0 0 0-.708.708L11.293 8l-1.147 1.146a.5.5 0 0 0 .708.708L12 8.707l1.146 1.147a.5.5 0 0 0 .708-.708L12.707 8l1.147-1.146a.5.5 0 0 0-.708-.708L12 7.293 10.854 6.146z" />
                                            <path
                                                d="M10 1.5v1h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 0-2-2v-9a2 2 0 0 1 2-2h1v-1a1 1 0 1 1 2 0v1h2v-1a1 1 0 1 1 2 0zM5 4a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H5z" />
                                        </svg>
                                    </span>
                                </div>

                       <input type="hidden"
                       id="batches_{{$orderItemIndex}}"
                       name = "psv_batch_data"
                       data-pqty = "{{ number_format($orderItem->verified_qty - $orderItem->confirmed_qty,6,'.','') }}"
                            data-batch='{{json_encode($batches)}}' >
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
                                <div class="me-50 cursor-pointer" data-bs-toggle="modal" data-bs-target="#Remarks" onclick="setItemRemarks('item_remarks_{{$orderItemIndex}}');">
                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="Remarks" class="text-primary"><i data-feather="file-text"></i></span>
                                </div>
                            </div>
                            <input type="hidden" id="item_remarks_{{$orderItemIndex}}" name="item_remarks[{{$orderItemIndex}}]">
                        </td>
                    </tr>
                    <script>

                        </script>
                @endforeach
            @endif
            
        </tbody>
        
        <tfoot>
            <tr class="totalsubheadpodetail"> 
                <td colspan="12"></td>
            </tr>  
            <tr valign="top">
                <td id = "item_details_td" colspan="12" rowspan="10">
                    <table class="table border">
                        <tr>
                            <td class="p-0">
                            <h6 class="text-dark mb-0 bg-light-primary py-1 px-50 d-flex justify-content-between align-items-center">
                                <strong>Item Details</strong>
                                @if(isset($items) && count($items) > 0)
                                <span class="pagination laravel-paginate pagination-sm mb-0">
                                    <div id="pagination-wrapper btn-sm">
                                    
                                    {{ $items->onEachSide(1)->links('pagination::bootstrap-4') }}
                                    </div>
                                </span>
                                @endif
                            </h6>


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
                        
                        
                        <tr id = "current_item_qt_no_row"> 
                            <td class="poprod-decpt">
                                <div id ="current_item_qt_no">

                                </div>
                            </td> 
                        </tr>

                        <tr id = "current_item_description_row">
                            <td class="poprod-decpt">
                                <span class="badge rounded-pill badge-light-secondary"><strong>Remarks</strong>: <span style = "text-wrap:auto;" id = "current_item_description"></span></span>
                            </td>
                        </tr>
                    </table> 
                </td>
            </tr>
        </tfoot>
</table>
</div>

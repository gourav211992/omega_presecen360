<tr id="row_{{$rowCount}}" data-index="{{$rowCount}}">
    <td class="customernewsection-form">
        <div class="form-check form-check-primary custom-checkbox">
            <input type="checkbox" class="form-check-input" id="Email_{{$rowCount}}" value="{{$rowCount}}" data-id="">
            <label class="form-check-label" for="Email_{{$rowCount}}"></label>
        </div>
    </td>
    <td class="poprod-decpt">
        <input type="text" name="component_item_name[{{$rowCount}}]" placeholder="Select" class="form-control mw-100 mb-25 ledgerselecct comp_item_code " />
        <input type="hidden" name="components[{{$rowCount}}][item_id]"/>
        <input type="hidden" name="components[{{$rowCount}}][item_code]"/>
        <input type="hidden" name="components[{{$rowCount}}][item_name]"/>
        <input type="hidden" name="components[{{$rowCount}}][hsn_id]"/>
        <input type="hidden" name="components[{{$rowCount}}][hsn_code]"/>
    </td>
    <td>
        <input type="text" name="components[{{$rowCount}}][item_name]" class="form-control mw-100 mb-25" readonly/>
    </td>
    <td class="poprod-decpt attributeBtn" id="itemAttribute_{{$rowCount}}" data-count="{{$rowCount}}" attribute-array="">
        <button type="button" class="btn p-25 btn-sm btn-outline-secondary" style="font-size: 10px">Attributes</button>
    </td>
    <td>
        <select class="form-select mw-100 " name="components[{{$rowCount}}][uom_id]">
        </select>
    </td>
    <td>
        <input type="hidden" class="form-control mw-100 order_qty" name="components[{{$rowCount}}][order_qty]" value="0" />
        <input type="hidden" class="form-control mw-100 rejected_qty" name="components[{{$rowCount}}][rejected_qty]" value="0" />
        <input type="number" step="any" class="form-control mw-100 accepted_qty text-end"  name="components[{{$rowCount}}][accepted_qty]"/>
    </td>
    <td>
        <input type="number" step="any" name="components[{{$rowCount}}][rate]" class="form-control mw-100 text-end checkNegativeVal" />
        <input type="hidden" name="components[{{$rowCount}}][po_val]" class="form-control mw-100 text-end po-rate checkNegativeVal" step="any" value="0" />
    </td>
    <td>
        <input type="number" step="any" readonly name="components[{{$rowCount}}][basic_value]" class="form-control mw-100 text-end" />
        <input type="hidden" name="components[{{$rowCount}}][po_b_value]"  class="form-control text-end mw-100 basic_value checkNegativeVal" readonly step="any" value="0" />
    </td>
    <td>
        <div class="position-relative d-flex align-items-center">
            <input type="number" step="any" readonly name="components[{{$rowCount}}][discount_amount]" class="form-control mw-100 text-end" style="width: 70px" />
            <input type="hidden" name="components[{{$rowCount}}][discount_amount_header]"/>
            <input type="hidden" name="components[{{$rowCount}}][exp_amount_header]"/>
            <div class="ms-50">
                <button type="button" data-row-count="{{$rowCount}}" class="btn p-25 btn-sm btn-outline-secondary addDiscountBtn" style="font-size: 10px">Add</button>
            </div>
        </div>
    </td>
    <td>
        <input type="number" step="any" name="components[{{$rowCount}}][item_total_cost]" readonly class="form-control mw-100 text-end" />
        <input type="hidden" id="item_total_po_cost_{{$rowCount}}" name="components[{{$rowCount}}][po_total_cost]" readonly class="form-control mw-100 text-end item_total_cost" step="any" value="0" />
    </td>
    <td>
        <input type="number" name="components[{{$rowCount}}][item_variance]" value="" readonly class="form-control mw-100 text-end item_variance" step="any" value="0" />
    </td>
    <td>
        <div class="d-flex">
            <div class="me-50 cursor-pointer addRemarkBtn" data-row-count="{{$rowCount}}" {{-- data-bs-toggle="modal" data-bs-target="#Remarks" --}}>        <span data-bs-toggle="tooltip" data-bs-placement="top" title="" class="text-primary" data-bs-original-title="Remarks" aria-label="Remarks"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span></div>
        </div>
    </td>
</tr>

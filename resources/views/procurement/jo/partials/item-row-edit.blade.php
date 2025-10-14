@foreach ($po->joProducts as $key => $joProduct)
    @php
        $rowCount = $key + 1;
    @endphp
    <tr id="row_{{ $rowCount }}" data-index="{{ $rowCount }}" @if ($rowCount < 2) class="trselected" @endif>
        <td class="customernewsection-form">
            <div class="form-check form-check-primary custom-checkbox">
                <input type="checkbox" class="form-check-input" id="Email_{{ $rowCount }}" value="{{ $rowCount }}" data-id="{{ $joProduct->id }}">
                <label class="form-check-label" for="Email_{{ $rowCount }}"></label>
            </div>
        </td>
        <td class="poprod-decpt">
            <input type="text" name="component_item_name[{{ $rowCount }}]" placeholder="Select" class="form-control mw-100 mb-25 ledgerselecct comp_item_code " value="{{ $joProduct->item_code }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_id]" value="{{ $joProduct->item_id }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_code]" value="{{ $joProduct->item_code }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_name]" value="{{ $joProduct?->item?->name }}" />
            @php
                $selectedAttr = $joProduct->attributes ? $joProduct->attributes()->whereNotNull('attribute_value')->pluck('attribute_value')->all() : [];
            @endphp
            @foreach ($joProduct->attributes as $attributeHidden)
                <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $attributeHidden->attribute_name }}][attr_id]" value="{{ $attributeHidden->id }}">
            @endforeach
            @foreach ($joProduct?->item?->itemAttributes as $itemAttribute)
                @if (count($selectedAttr))
                    @foreach ($itemAttribute->attributes() as $value)
                        @if (in_array($value->id, $selectedAttr))
                            <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $itemAttribute->attribute_group_id }}][item_attr_id]" value="{{ $itemAttribute->id }}">
                            <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $itemAttribute->attribute_group_id }}][attr_name]" value="{{ $value->id }}">
                        @endif
                    @endforeach
                @else
                    <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $itemAttribute->attribute_group_id }}][attr_name]" value="">
                @endif
            @endforeach
        </td>
        <td>
            <input type="text" name="components[{{ $rowCount }}][item_name]" value="{{ $joProduct?->item?->item_name }}" class="form-control mw-100 mb-25" readonly />
        </td>
        <td class="poprod-decpt attributeBtn" id="itemAttribute_{{ $rowCount }}" data-count="{{ $rowCount }}" attribute-array="{{ $joProduct->item_attributes_array() }}">
        </td>
        <td>
            <select class="form-select mw-100 " name="components[{{ $rowCount }}][uom_id]">
                <option value="{{ $joProduct?->item?->uom_id }}">{{ ucfirst($joProduct?->item?->uom?->name) }}</option>
                @foreach ($joProduct?->item?->alternateUOMs as $alternateUOM)
                    <option value="{{ $alternateUOM?->uom?->id }}" {{ $alternateUOM?->uom?->id == $joProduct->inventory_uom_id ? 'selected' : '' }}>{{ $alternateUOM?->uom?->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control mw-100 text-end" value="{{ $joProduct->order_qty }}" name="components[{{ $rowCount }}][qty]" step="any"></td>
        <td><input type="text" name="components[{{ $rowCount }}][sow]" value="{{ $joProduct->sow ? $joProduct->sow->item_name . ' ( ' . $joProduct->sow->item_code . ' )' : '' }}" value="sow" class="form-control mw-100" /><input type="hidden"
                   name="components[{{ $rowCount }}][sow_id]" value="{{ $joProduct->sow->id ?? '' }}" class="form-control" /></td>
        <td><input type="number" name="components[{{ $rowCount }}][rate]" value="{{ $joProduct->rate }}" class="form-control mw-100 text-end" /></td>
        <td><input type="number" readonly value="{{ $joProduct->order_qty * $joProduct->rate }}" name="components[{{ $rowCount }}][item_value]" class="form-control mw-100 text-end" step="any" /></td>
        <td class="d-none">
            <div class="position-relative d-flex align-items-center">
                @foreach ($joProduct->itemDiscount as $itemDis_key => $itemDiscount)
                    <input type="hidden" value="{{ $itemDiscount->id }}" name="components[{{ $rowCount }}][discounts][{{ $itemDis_key + 1 }}][id]">
                    <input type="hidden" value="{{ $itemDiscount->ted_id }}" name="components[{{ $rowCount }}][discounts][{{ $itemDis_key + 1 }}][ted_id]">
                    <input type="hidden" value="{{ $itemDiscount->ted_name }}" name="components[{{ $rowCount }}][discounts][{{ $itemDis_key + 1 }}][dis_name]">
                    <input type="hidden" value="{{ $itemDiscount->ted_perc }}" name="components[{{ $rowCount }}][discounts][{{ $itemDis_key + 1 }}][dis_perc]">
                    <input type="hidden" value="{{ $itemDiscount->ted_amount }}" name="components[{{ $rowCount }}][discounts][{{ $itemDis_key + 1 }}][dis_amount]">
                @endforeach
                <input type="number" readonly name="components[{{ $rowCount }}][discount_amount]" class="form-control mw-100 text-end" style="width: 70px" value="{{ $joProduct->item_discount_amount }}" step="any" />
                <input type="hidden" name="components[{{ $rowCount }}][discount_amount_header]" value="{{ $joProduct->header_discount_amount }}" />
                <input type="hidden" name="components[{{ $rowCount }}][exp_amount_header]" value="{{ $joProduct->expense_amount }}" />
                <div class="ms-50">
                    <button type="button" data-row-count="{{ $rowCount }}" class="btn p-25 btn-sm btn-outline-secondary addDiscountBtn" style="font-size: 10px">Add</button>
                </div>
            </div>
        </td>
        <td class="d-none"><input type="hidden" value="{{ $joProduct->order_qty * $joProduct->rate - $joProduct->item_discount_amount }}" name="components[{{ $rowCount }}][item_total_cost]" readonly class="form-control mw-100 text-end" step="any" />
            @foreach ($joProduct->taxes as $tax_key => $joProduct_tax)
                <input type="hidden" value="{{ $joProduct_tax->id }}" name="components[{{ $rowCount }}][taxes][{{ $tax_key + 1 }}][id]">
                <input type="hidden" value="{{ $joProduct_tax->ted_id }}" name="components[{{ $rowCount }}][taxes][{{ $tax_key + 1 }}][t_d_id]">
                <input type="hidden" value="{{ $joProduct_tax->applicable_type }}" name="components[1][taxes][{{ $tax_key + 1 }}][applicability_type]">
                <input type="hidden" value="{{ $joProduct_tax->ted_name }}" name="components[{{ $rowCount }}][taxes][{{ $tax_key + 1 }}][t_type]">
                <input type="hidden" value="{{ $joProduct_tax->ted_perc }}" name="components[{{ $rowCount }}][taxes][{{ $tax_key + 1 }}][t_perc]">
                <input type="hidden" value="{{ $joProduct_tax->ted_amount }}" name="components[{{ $rowCount }}][taxes][{{ $tax_key + 1 }}][t_value]">
            @endforeach
        </td>
        <td>
            <input type="date" value="{{ $joProduct?->delivery_date }}" name="components[{{ $rowCount }}][delivery_date]" class="form-control mw-100" />
        </td>
        <td>
            <div class="d-flex">
                @foreach ($joProduct->productDelivery as $itemDeli_key => $itemDelivery)
                    <input type="hidden" value="{{ $itemDelivery->id }}" name="components[{{ $rowCount }}][delivery][{{ $itemDeli_key + 1 }}][id]">
                    <input type="hidden" value="{{ $itemDelivery->qty }}" name="components[{{ $rowCount }}][delivery][{{ $itemDeli_key + 1 }}][d_qty]">
                    <input type="hidden" value="{{ $itemDelivery->delivery_date }}" name="components[{{ $rowCount }}][delivery][{{ $itemDeli_key + 1 }}][d_date]">
                @endforeach
                <div class="me-50 cursor-pointer addDeliveryScheduleBtn" data-row-count="{{ $rowCount }}">
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="" class="text-primary" data-bs-original-title="Delivery Schedule" aria-label="Delivery Schedule"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg></span>
                </div>
                <div class="me-50 cursor-pointer addRemarkBtn" data-row-count="{{ $rowCount }}">
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="" class="text-primary" data-bs-original-title="Remarks" aria-label="Remarks"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg></span>
                </div>
                <input type="hidden" value="{{ $joProduct->remarks }}" name="components[{{ $rowCount }}][remark]">
            </div>
        </td>
        <input type="hidden" name="components[{{ $rowCount }}][so_id]" value="{{ $joProduct?->so_id }}">
        <input type="hidden" name="components[{{ $rowCount }}][jo_product_id]" value="{{ $joProduct?->id }}">
        <input type="hidden" name="components[{{ $rowCount }}][pwo_id]" value="{{ $joProduct?->pwoSoMapping?->pwo_id }}">
        <input type="hidden" name="components[{{ $rowCount }}][pwo_so_mapping_id]" value="{{ $joProduct?->pwoSoMapping?->id }}">
    </tr>
@endforeach

@foreach ($mrnItems as $key => $item)
    @php
        $rowCount = $tableRowCount + $key + 1;
        $qty = $item->accepted_qty ?? 0.0;
        $itemTotalValue = $qty * $item->rate - ($item->discount_amount + $item->header_discount_amount);
        $itemRate = $itemTotalValue / $qty;
        $grnNumber = ($item?->mrnHeader?->book_code ?? '') . ' ' . ($item?->mrnHeader?->document_number ?? '');
        $totalWeight = ($item?->item?->storage_weight ?? 0.0) * $qty;
        $totalVolume = ($item?->item?->storage_volume ?? 0.0) * $qty;
        $exchangeRate = $item?->mrnHeader?->currencyConversion()['data']['org_currency_exg_rate'] ?? 1;
        $totalValue = $itemTotalValue * $exchangeRate;
    @endphp
    <tr id="row_{{ $rowCount }}" data-index="{{ $rowCount }}" data-id="{{ $rowCount }}"
        class="row_{{ $rowCount }} grn_row_{{ $rowCount }} grn-row grn-tab {{ $rowCount < 2 ? 'trselected' : '' }}">
        <input type="hidden" name="components[grn][{{ $rowCount }}][grn_header_id]"
            value="{{ $item->mrn_header_id }}">
        <input type="hidden" name="components[grn][{{ $rowCount }}][grn_detail_id]" value="{{ $item->id }}">
        <input type="hidden" name="components[grn][{{ $rowCount }}][vendor_id]" value="{{ $item->vendor_id }}">
        <td class="customernewsection-form">
            <div class="form-check form-check-primary custom-checkbox">
                <input type="checkbox" class="form-check-input" id="Email_{{ $rowCount }}"
                    data-id="{{ $item->id }}" value="{{ $rowCount }}">
                <label class="form-check-label" for="Email_{{ $rowCount }}"></label>
            </div>
        </td>
        <td>
            <input type="text" name="components[grn][{{ $rowCount }}][vendor_name]"
                value="{{ $item?->vendor_name }}" class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[grn][{{ $rowCount }}][grn_number]" value="{{ $grnNumber }}"
                class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[grn][{{ $rowCount }}][grn_date]"
                value="{{ $item?->mrnHeader?->getFormattedDate('document_date') }}" class="form-control mw-100 mb-25"
                readonly />
        </td>
        <td>
            <input type="text" name="component_item_name[{{ $rowCount }}]" placeholder="Select"
                class="form-control mw-100 ledgerselecct comp_item_code" value="{{ $item->item_code }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][item_id]"
                value="{{ @$item->item_id }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][item_code]"
                value="{{ @$item->item_code }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][item_name]"
                value="{{ @$item->item->name }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][hsn_id]" value="{{ @$item->hsn_id }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][hsn_code]"
                value="{{ $item?->hsn_code }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][uom_id]" value="{{ @$item->uom_id }}" />
            <input type="hidden" name="components[grn][{{ $rowCount }}][uom_code]"
                value="{{ $item?->uom_code }}" />
            @php
                $selectedAttr = @$item->attributes
                    ? @$item->attributes()->whereNotNull('attr_value')->pluck('attr_value')->all()
                    : [];
            @endphp
            @foreach (@$item->attributes as $attributeHidden)
                <input type="hidden"
                    name="components[grn][{{ $rowCount }}][attr_group_id][{{ $attributeHidden->attr_name }}][attr_id]"
                    value="{{ $attributeHidden->id }}">
            @endforeach
            @foreach (@$item->item->itemAttributes as $itemAttribute)
                @if (count($selectedAttr))
                    @foreach ($itemAttribute->attributes() as $value)
                        @if (in_array($value->id, $selectedAttr))
                            <input type="hidden"
                                name="components[grn][{{ $rowCount }}][attr_group_id][{{ $itemAttribute->attribute_group_id }}][attr_name]"
                                value="{{ $value->id }}">
                        @endif
                    @endforeach
                @else
                    <input type="hidden"
                        name="components[grn][{{ $rowCount }}][attr_group_id][{{ $itemAttribute->attribute_group_id }}][attr_name]"
                        value="">
                @endif
            @endforeach
        </td>
        <td>
            <input type="text" name="components[grn][{{ $rowCount }}][item_name]"
                value="{{ $item?->item?->item_name }}" class="form-control mw-100 mb-25 item-name" readonly />
        </td>
        <td class="poprod-decpt attributeBtn" id="itemAttribute_{{ $rowCount }}" data-count="{{ $rowCount }}"
            attribute-array="{{ $item->item_attributes_array() }}">
        </td>
        <td>
            <input type="hidden" name="components[grn][{{ $rowCount }}][inventory_uom_id]"
                value="{{ $item->inventory_uom_id }}">
            <select class="form-select mw-100">
                <option value="{{ $item?->uom?->id }}">
                    {{ ucfirst($item?->uom?->name) }}
                </option>
            </select>
        </td>
        <td>
            <input type="hidden" name="components[grn][{{ $rowCount }}][currency_id]"
                value="{{ $item?->mrnHeader?->currency_id }}">
            <input type="text" class="form-control mw-100 currency_code"
                value="{{ $item?->mrnHeader?->currency?->short_name ?? '' }}"
                name="components[grn][{{ $rowCount }}][currency_code]" readonly />
        </td>
        <td>
            <input type="hidden" name="components[grn][{{ $rowCount }}][org_currency_id]"
                value="{{ $currency?->id }}">
            <input type="hidden" name="components[grn][{{ $rowCount }}][exchange_rate]"
                value="{{ $exchangeRate }}">
            <input type="text" class="form-control mw-100 org_currency_code"
                value="{{ $currency?->short_name ?? '' }}"
                name="components[grn][{{ $rowCount }}][org_currency_code]" readonly />
        </td>
        <td>
            <input type="number" class="form-control mw-100 accepted_qty text-end checkNegativeVal grn-qty"
                name="components[grn][{{ $rowCount }}][grn_qty]" value="{{ $qty }}" readonly
                step="any" />
        </td>
        <td>
            <input type="number" id="old_grn_value_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][old_grn_value]" value="{{ $itemTotalValue }}" readonly
                class="form-control mw-100 text-end grn_item_value old-grn-value" step="any" />
        </td>
        <td>
            <input type="number" id="grn_value_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][grn_value]" value="{{ $totalValue }}" readonly
                class="form-control mw-100 text-end item_value grn-value" step="any" />
        </td>
        <td>
            <input type="number" id="grn_weight_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][grn_weight]" value="{{ $totalWeight }}" readonly
                class="form-control mw-100 text-end grn_weight grn-weight" step="any" />
        </td>
        <td>
            <input type="number" id="grn_volume_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][grn_volume]" value="{{ $totalVolume }}" readonly
                class="form-control mw-100 text-end grn_volume grn-volume" step="any" />
        </td>
        <td class="position-relative d-flex align-items-center">
            <input type="number" id="allocation_cost_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][allocation_cost]" value="" readonly
                class="form-control mw-100 allocated-exp" style="width:100px" step="any" />
            <input type="hidden" class="ea-alloc" name="components[grn][{{ $rowCount }}][allocations][]"
                value=''>
            <div class="ms-50">
                <a href="javascript:;" class="btn btn-xs btn-outline-primary showDistBreakup">
                    Show
                </a>
            </div>
        </td>
        <td>
            <input type="number" id="landed_cost_{{ $rowCount }}"
                name="components[grn][{{ $rowCount }}][landed_cost]" value="" readonly
                class="form-control mw-100 text-end landed-cost" step="any" />
        </td>
        <input type="hidden" name="components[grn][{{ $rowCount }}][mrn_item_hidden_ids]"
            value="{{ $item->id }}">
        <input type="hidden" name="components[grn][{{ $rowCount }}][mrn_hidden_ids]"
            value="{{ $item->mrnHeader->id }}">
    </tr>
@endforeach

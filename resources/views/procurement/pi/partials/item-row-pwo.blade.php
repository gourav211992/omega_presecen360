@foreach ($pwoBomMappings as $key => $pwoBomMapping)
    @php
        $item = $pwoBomMapping->item;
        $so = $pwoBomMapping->so;
        $vendor = $pwoBomMapping->vendor ?? null;
        $uom = $pwoBomMapping->uom ?? $item?->uom;
        $selectedAttr = collect($pwoBomMapping->attributes ?? [])
            ->flatMap(function ($attr) {
                return collect($attr['values_data'] ?? [])
                    ->filter(fn($val) => $val['selected'] ?? false)
                    ->pluck('id');
            })
            ->values()
            ->all();
        $stocks = \App\Helpers\InventoryHelper::totalInventoryAndStock($item?->id, $selectedAttr, $uom?->id, $storeId);
    @endphp

    <tr id="row_{{ $rowCount }}" data-index="{{ $rowCount }}">
        <td>
            <div class="form-check form-check-primary custom-checkbox">
                <input type="checkbox" class="form-check-input" id="Email_{{ $rowCount }}" value="{{ $rowCount }}" data-id="{{ $item?->id }}">
                <label class="form-check-label" for="Email_{{ $rowCount }}"></label>
            </div>
        </td>
        <td class="poprod-decpt">
            <input readonly type="text" name="component_item_name[{{ $rowCount }}]" class="form-control mw-100 mb-25 comp_item_code" value="{{ $item?->item_code }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_id]" value="{{ $item?->id }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_code]" value="{{ $item?->item_code }}" />
            <input type="hidden" name="components[{{ $rowCount }}][item_name]" value="{{ $item?->item_name }}" />
            <input type="hidden" name="components[{{ $rowCount }}][hsn_id]" value="{{ $item?->hsn?->id }}" />
            <input type="hidden" name="components[{{ $rowCount }}][hsn_code]" value="{{ $item?->hsn?->hsn_code }}" />
            @foreach ($item?->itemAttributes ?? [] as $itemAttribute)
                @if (count($selectedAttr))
                    @foreach ($itemAttribute->attributes() as $value)
                        @if (in_array($value->id, $selectedAttr))
                            <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $itemAttribute?->attribute_group_id }}][attr_name]" value="{{ $value?->id }}">
                        @endif
                    @endforeach
                @else
                    <input type="hidden" name="components[{{ $rowCount }}][attr_group_id][{{ $itemAttribute?->attribute_group_id }}][attr_name]" value="">
                @endif
            @endforeach
        </td>

        <td><input readonly type="text" class="form-control mw-100 mb-25" value="{{ $item?->item_name }}"></td>
        <td class="poprod-decpt attributeBtn" id="itemAttribute_{{ $rowCount }}" data-count="{{ $rowCount }}" attribute-array="{{ json_encode($pwoBomMapping->attributes, true) ?? [] }}"></td>
        <td>
            <select class="form-select mw-100" name="components[{{ $rowCount }}][uom_id]">
                <option value="{{ $uom?->id }}">{{ ucfirst($uom?->name) }}</option>
            </select>
        </td>

        <td><input type="number" step="any" class="form-control text-end" name="components[{{ $rowCount }}][qty]" value="{{ $pwoBomMapping->qty ?? $pwoBomMapping->total_qty }}"></td>

        <td><input type="number" step="any" class="form-control text-end" name="components[{{ $rowCount }}][avl_stock]" readonly value="{{ $stocks['confirmedStocks'] ?? 0 }}"></td>

        <td><input type="number" step="any" class="form-control text-end" name="components[{{ $rowCount }}][pending_po]"></td>

        <td><input type="number" step="any" class="form-control text-end" name="components[{{ $rowCount }}][adj_qty]"></td>

        <td><input type="number" step="any" class="form-control text-end" name="components[{{ $rowCount }}][indent_qty]" readonly value="{{ $pwoBomMapping->qty ?? $pwoBomMapping->total_qty }}"></td>

        <td>
            <input type="text" class="form-control" name="components[{{ $rowCount }}][vendor_code]" value="{{ $vendor?->company_name ?? '' }}">
            <input type="hidden" name="components[{{ $rowCount }}][vendor_id]" value="{{ $vendor?->id ?? '' }}">
        </td>

        @if (isset($soTrackingRequired) && $soTrackingRequired)
            <td>
                <input readonly type="text" class="form-control" name="components[{{ $rowCount }}][so_no]" value="{{ $so?->full_document_number }}">
            </td>
        @endif

        <td>
            <input type="text" name="components[{{ $rowCount }}][remark]" class="form-control" value="{{ $pwoBomMapping->remark ?? '' }}">
        </td>

        <input type="hidden" name="components[{{ $rowCount }}][pwo_id]" value="{{ $pwoBomMapping->pwo_id }}">
        <input type="hidden" name="components[{{ $rowCount }}][pwo_mapping_id]" value="{{ $pwoBomMapping->id }}">
    </tr>
    @php
        $rowCount++;
    @endphp
@endforeach

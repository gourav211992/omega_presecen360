<tr id="row_{{ $rowCount }}" data-index="{{ $rowCount }}">
    <td class="customernewsection-form">
        <div class="form-check form-check-primary custom-checkbox">
            <input type="checkbox" class="form-check-input" id="Email_{{ $rowCount }}" value="{{ $rowCount }}"
                data-id="">
            <label class="form-check-label" for="Email_{{ $rowCount }}"></label>
        </div>
    </td>
    <td class="poprod-decpt">
        <input type="text" name="component_item_name[po][{{ $rowCount }}]" placeholder="Select"
            class="form-control mw-100 mb-25 ledgerselecct comp_item_code " />
        <input type="hidden" name="components[po][{{ $rowCount }}][item_id]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][item_code]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][item_name]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][hsn_id]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][hsn_code]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][uom_id]" />
        <input type="hidden" name="components[po][{{ $rowCount }}][uom_code]" />
    </td>
    <td>
        <input type="text" name="components[po][{{ $rowCount }}][item_name]" value=""
            class="form-control mw-100 mb-25" readonly />
    </td>
    <td>
        <input type="number" class="form-control mw-100 po_qty text-end checkNegativeVal expense-qty"
            name="components[po][{{ $rowCount }}][po_qty]" value="1" step="any" />
    </td>
    <td>
        <input type="number" name="components[po][{{ $rowCount }}][po_rate]"
            class="form-control po_rate mw-100 text-end" step="any" />
    </td>
    <td>
        <input type="text" id="po_value_{{ $rowCount }}" name="components[po][{{ $rowCount }}][po_value]"
            readonly class="form-control mw-100 text-end po_value expense-amount" step="any" />
    </td>
    <td>
        <select class="form-select mw-100 alloc-type" name="components[po][{{ $rowCount }}][dist_type]">
            <option value="">Select Type</option>
            @foreach ($distributionTypes as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" name="components[po][{{ $rowCount }}][vendor_name]" class="form-control mw-100 mb-25"
            readonly />
    </td>
    <td>
        <input type="text" name="components[po][{{ $rowCount }}][po_number]" class="form-control mw-100 mb-25"
            readonly />
    </td>
    <td>
        <input type="text" name="components[po][{{ $rowCount }}][po_date]" class="form-control mw-100 mb-25"
            readonly />
    </td>
</tr>

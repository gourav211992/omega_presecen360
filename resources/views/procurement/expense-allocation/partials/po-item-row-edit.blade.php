@foreach ($expense->poDetails as $key => $item)
    @php
        $rowCount = $key + 1;
        $poQty = $item->receipt_qty ?? $item->po_qty;
        $readOnly = '';
        $poNumber = ($item?->poHeader?->book_code ?? '') . ' ' . ($item?->poHeader?->document_number ?? '');
    @endphp
    <tr data-group-item="{{ json_encode($item) }}" id="row_{{ $rowCount }}" data-index="{{ $rowCount }}"
        class="row_{{ $rowCount }} po_row_{{ $rowCount }} po-row expense-row {{ $rowCount < 2 ? 'trselected' : '' }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][header_id]" value="{{ $item->header_id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][detail_id]" value="{{ $item->id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][po_header_id]"
            value="{{ $item->po_header_id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][po_detail_id]" value="{{ $item->id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][vendor_id]" value="{{ $item?->vendor_id }}">
        <td class="customernewsection-form">
            <div class="form-check form-check-primary custom-checkbox">
                <input type="checkbox" class="form-check-input" id="Email_{{ $rowCount }}"
                    data-id="{{ $item->id }}" value="{{ $rowCount }}">
                <label class="form-check-label" for="Email_{{ $rowCount }}"></label>
            </div>
        </td>
        <td>
            <input type="text" name="component_item_name[{{ $rowCount }}]" placeholder="Select"
                class="form-control mw-100 ledgerselecct comp_item_code" value="{{ $item->item_code }}" readonly />
            <input type="hidden" name="components[po][{{ $rowCount }}][item_id]" value="{{ @$item->item_id }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][item_code]"
                value="{{ @$item->item_code }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][item_name]"
                value="{{ @$item->item_name }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][hsn_id]" value="{{ @$item->hsn_id }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][hsn_code]"
                value="{{ $item?->hsn_code }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][uom_id]" value="{{ @$item->uom_id }}" />
            <input type="hidden" name="components[po][{{ $rowCount }}][uom_code]"
                value="{{ $item?->uom_code }}" />
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][item_name]"
                value="{{ $item?->item?->item_name }}" class="form-control mw-100 mb-25 expense-name" readonly />
        </td>
        <td>
            <input type="hidden" name="components[po][{{ $rowCount }}][inventory_uom_id]"
                value="{{ $item->inventory_uom_id }}">
            <select class="form-select mw-100">
                <option value="{{ $item?->uom_id }}">
                    {{ ucfirst($item?->uom_code) }}
                </option>
            </select>
        </td>
        <td>
            <input type="hidden" name="components[po][{{ $rowCount }}][currency_id]"
                value="{{ $item?->currency_id }}">
            <input type="text" class="form-control mw-100 currency_code" value="{{ $item?->currency_code ?? '' }}"
                name="components[po][{{ $rowCount }}][currency_code]" readonly />
        </td>
        <td>
            <input type="hidden" name="components[po][{{ $rowCount }}][org_currency_id]"
                value="{{ $item?->org_currency_id }}">
            <input type="text" class="form-control mw-100 org_currency_code"
                value="{{ $item?->org_currency_code ?? '' }}"
                name="components[po][{{ $rowCount }}][org_currency_code]" readonly />
        </td>
        <td>
            <input type="number" class="form-control mw-100 po_qty text-end checkNegativeVal expense-qty"
                name="components[po][{{ $rowCount }}][po_qty]" readonly value="{{ $poQty }}"
                step="any" />
        </td>
        <td>
            <input type="number" name="components[po][{{ $rowCount }}][po_rate]" value="{{ $item->rate }}"
                readonly class="form-control po_rate mw-100 text-end" step="any" />
        </td>
        <td>
            <input type="text" id="old_amt_po_{{ $rowCount }}"
                name="components[po][{{ $rowCount }}][old_amt_po]" value="{{ $item->value }}" readonly
                class="form-control mw-100 text-end old_amt_po" step="any" />
        </td>
        <td>
            <input type="text" id="po_value_{{ $rowCount }}"
                name="components[po][{{ $rowCount }}][po_value]" value="{{ $item->value }}" readonly
                class="form-control mw-100 text-end po_value expense-amount" step="any" />
        </td>
        <td>
            <select class="form-select mw-100 alloc-type" name="components[po][{{ $rowCount }}][dist_type]">
                @foreach ($distributionTypes as $key => $value)
                    <option value="{{ $key }}" @if ($item->allocation_type == $key) selected @endif>
                        {{ $value }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][vendor_name]"
                value="{{ $item?->vendor_name }}" class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][po_number]" value="{{ $poNumber }}"
                class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][po_date]"
                value="{{ $item?->poHeader?->getFormattedDate('document_date') }}" class="form-control mw-100 mb-25"
                readonly />
        </td>
    </tr>
@endforeach

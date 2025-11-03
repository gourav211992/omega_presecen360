@foreach ($poItems as $key => $item)
    @php
        $rowCount = $tableRowCount + $key + 1;
        $poQty = ($item->order_qty ?? 0.0) - ($item->short_close_qty ?? 0.0);
        $readOnly = '';
        $itemValue = $poQty * $item->rate;
        $itemDisc = $item->item_discount_amount;
        $headerDisc = $item->header_discount_amount;
        $poValue = $itemValue - ($itemDisc + $headerDisc);
        $newRate = $itemValue / $poQty;
        $poNumber = ($item?->po?->book_code ?? '') . ' ' . ($item?->po?->document_number ?? '');
        $exchangeRate = $item?->po?->currencyConversion()['data']['org_currency_exg_rate'] ?? 1;
        $totalValue = $poValue * $exchangeRate;
    @endphp
    <tr data-group-item="{{ json_encode($item) }}" id="row_{{ $rowCount }}" data-index="{{ $rowCount }}"
        class="row_{{ $rowCount }} po_row_{{ $rowCount }} po-row expense-row {{ $rowCount < 2 ? 'trselected' : '' }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][po_header_id]"
            value="{{ $item->purchase_order_id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][po_detail_id]" value="{{ $item->id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][vendor_id]"
            value="{{ $item?->po?->vendor_id }}">
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
                <option value="{{ $item?->uom?->id }}">
                    {{ ucfirst($item?->uom?->name) }}
                </option>
            </select>
        </td>
        <td>
            <input type="hidden" name="components[po][{{ $rowCount }}][currency_id]"
                value="{{ $item?->po?->currency_id }}">
            <input type="hidden" name="components[po][{{ $rowCount }}][org_currency_id]"
                value="{{ $currency?->id }}">
            <input type="hidden" name="components[po][{{ $rowCount }}][exchange_rate]"
                value="{{ $exchangeRate }}">
            <input type="text" class="form-control mw-100 currency_code"
                value="{{ $item?->po?->currency?->short_name ?? '' }}"
                name="components[po][{{ $rowCount }}][currency_code]" readonly />
        </td>
        <td>
            <input type="number" class="form-control mw-100 po_qty text-end checkNegativeVal expense-qty"
                name="components[po][{{ $rowCount }}][po_qty]" readonly value="{{ $poQty }}"
                step="any" />
        </td>
        <td>
            <input type="number" name="components[po][{{ $rowCount }}][po_rate]" value="{{ $newRate }}"
                readonly class="form-control po_rate mw-100 text-end" step="any" />
        </td>
        <td>
            <input type="number" id="po_value_{{ $rowCount }}"
                name="components[po][{{ $rowCount }}][po_value]" value="{{ $totalValue }}" readonly
                class="form-control mw-100 text-end po_value expense-amount" step="any" />
        </td>
        <td>
            <input type="number" id="old_amt_po_{{ $rowCount }}"
                name="components[po][{{ $rowCount }}][old_amt_po]" value="{{ $poValue }}" readonly
                class="form-control mw-100 text-end" step="any" />
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
            <input type="text" name="components[po][{{ $rowCount }}][vendor_name]"
                value="{{ $item?->po?->vendor?->company_name }}" class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][po_number]" value="{{ $poNumber }}"
                class="form-control mw-100 mb-25" readonly />
        </td>
        <td>
            <input type="text" name="components[po][{{ $rowCount }}][po_date]"
                value="{{ $item?->po?->getFormattedDate('document_date') }}" class="form-control mw-100 mb-25"
                readonly />
        </td>
        <input type="hidden" name="components[po][{{ $rowCount }}][po_item_hidden_ids]"
            value="{{ $item->id }}">
        <input type="hidden" name="components[po][{{ $rowCount }}][po_hidden_ids]"
            value="{{ $item->purchase_order_id }}">
    </tr>
@endforeach

@use(App\Helpers\InventoryHelper)
@forelse($groupedDatas as $groupedData)
    @php
        $html = '';
        $selectedAttr = [];
        $attributes = $groupedData->attributes ?? [];
        foreach ($attributes as $attribute) {
            $attr = \App\Models\ItemAttribute::where('id', @$attribute['attribute_id'])->first();
            $attrValue = \App\Models\Attribute::where('id', @$attribute['attribute_value'])->first();
            if ($attr && $attrValue) {
                $html .= "<span class='badge rounded-pill badge-light-primary'><strong>{$attr?->attributeGroup?->name}</strong>: {$attrValue->value}</span>";
            } else {
                $html .= "<span class='badge rounded-pill badge-light-secondary'><strong>Attribute not found</strong></span>";
            }
            if ($attrValue) {
                $selectedAttr[] = $attrValue?->id;
            }
        }
        $inventoryStock = InventoryHelper::totalInventoryAndStock($groupedData->item_id, $selectedAttr, $groupedData->uom_id ?? $groupedData?->item?->uom_id, null);
        $groupedData->attributes = $groupedData->item_attributes_array();
    @endphp
    <tr>
        <td>
            <div class="form-check form-check-inline me-0">
                <input class="form-check-input pi_item_checkbox" type="checkbox" name="pi_item_check" value="{{ $groupedData->id }}" data-item="{{ json_encode($groupedData) }}">
            </div>
        </td>
        <td>{{ $groupedData?->item?->item_code ?? '' }}</td>
        <td>{{ $groupedData?->item?->item_name ?? '' }}</td>
        <td>{!! $html ?? '' !!}</td>
        <td>{{ $groupedData?->uom?->name ?? '' }}</td>
        <td class="text-end">{{ number_format($groupedData->total_qty, 2) }}</td>
        <td class="text-end">{{ number_format($inventoryStock['confirmedStocks'], 2) }}</td>
        <td class="text-end">{{ number_format($inventoryStock['pendingStocks'], 2) }}</td>
        <td>{{ $groupedData?->vendor?->company_name ?? '' }}</td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="text-center">No record found!</td>
    </tr>
@endforelse

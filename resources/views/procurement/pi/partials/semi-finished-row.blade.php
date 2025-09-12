@props(['node', 'parentSoId', 'rowIndex' => 1, 'parentKey' => '', 'level' => 0])
@php
    $hideClass = '';
    $indentPx = 15 * $level;
    $effectiveSoId = $node['so_id'] ?? ($parentSoId ?? null);
    $rowClass = $level === 0 ? 'trail-bal-tabl-none' : 'trail-sub-list-open';
    $attributeCount = intval($node['so_id'] ?? 0) > 0 ? intval($node['so_id']) : 0;
    $uniquePart = $node['item_id'] . '-' . $node['uom_id'] . '-' . $attributeCount . '-' . $rowIndex;
    $rowKey = $parentKey === '' ? 'fg-' . $uniquePart : $parentKey . '-' . $uniquePart;
    $hasChildren = !empty($node['children']);

    $html = '';
    if (count($node['attribute'] ?? 0) > 0) {
        $selectedAttrValues = collect($node['attribute'] ?? [])
            ->map(function ($group) {
                $selected = collect($group['values_data'])->first(function ($attr) {
                    return ($attr['selected'] ?? $attr?->selected) === true;
                });
                if ($selected) {
                    return [
                        'attribute_group_id' => $group['attribute_group_id'],
                        'attribute_group_name' => $group['group_name'],
                        'attribute_id' => $selected['id'] ?? $selected->id,
                        'attribute_value' => $selected['value'] ?? $selected->value,
                    ];
                }
                return null;
            })
            ->filter()
            ->values();

        foreach ($selectedAttrValues as $attribute) {
            $attN = $attribute['attribute_group_name'] ?? '';
            $attV = $attribute['attribute_value'] ?? '';
            if ($attN && $attV) {
                $html .= "<span class='badge rounded-pill badge-light-primary'><strong>{$attN}</strong>: {$attV}</span>";
            }
        }
    }
@endphp
<tr class="{{ $rowClass }} {{ $hideClass }}" data-level="{{ $level }}" data-row-key="{{ $rowKey }}" data-parent-key="{{ $parentKey }}">
    <td>
        <div class="form-check form-check-inline me-0">
            <input class="form-check-input analyze_row" type="checkbox" name="bom_id" value="{{ $node['bom_id'] }}" data-so-id="{{ $effectiveSoId }}" data-so-item-id="{{ isset($node['so_item_id']) && $node['so_item_id'] ? $node['so_item_id'] : '' }}"
                   data-so-item-ids="{{ isset($node['so_item_ids']) && $node['so_item_ids'] ? json_encode($node['so_item_ids']) : '' }}" data-level="{{ $node['level'] }}" data-parent-bom-id="{{ $node['parent_bom_id'] }}" data-bom-id="{{ $node['bom_id'] }}"
                   data-item-name="{{ $node['item_name'] }}" data-item-id="{{ $node['item_id'] }}" data-item-code="{{ $node['item_code'] }}" data-uom-id="{{ $node['uom_id'] }}" data-uom-name="{{ $node['uom_name'] }}" data-attribute="{{ json_encode($node['attribute'] ?? []) }}"
                   data-total-qty="{{ $node['total_qty'] }}" data-store-name="{{ $node['store_name'] }}" data-store-id="{{ $node['store_id'] }}" data-doc-no="{{ $node['doc_no'] }}" data-doc-date="{{ $node['doc_date'] }}" data-main-so-item="{{ $node['main_so_item'] }}"
                   data-required-qty="{{ $node['total_qty'] }}" data-remaining-qty="0">
        </div>
    </td>
    <td style="padding-left: {{ $indentPx }}px;">
        @if ($hasChildren)
            <a href="#" class="toggle-expand d-none" data-target="{{ $rowKey }}"><i data-feather="plus-circle"></i></a>
            <a href="#" class="toggle-collapse" data-target="{{ $rowKey }}"><i data-feather="minus-circle"></i></a>
        @else
            <i data-feather="arrow-right"></i>
        @endif
        {{ $node['doc_no'] }}
    </td>
    <td>{{ $node['doc_date'] }}</td>
    <td>{{ $node['item_name'] }}</td>
    <td>{{ $node['item_code'] }}</td>
    <td>{{ $node['uom_name'] }}</td>
    <td>{!! $html !!}</td>
    <td>{{ $node['store_name'] ?? '' }}</td>
    <td class="avl_stock">{{ $node['avl_qty'] }}</td>
    <td><input type="number" step="any" class="form-control text-end mw-100 analyse_total_qty" data-parent-key="{{ $parentKey }}" data-current-key="{{ $rowKey }}" id="analyse_total_qty_{{ $rowIndex }}" value="{{ $node['total_qty'] }}" disabled></td>
    <td><input type="number" step="any" class="form-control text-end mw-100 analyse_required_qty" data-parent-key="{{ $parentKey }}" data-current-key="{{ $rowKey }}" id="analyse_required_qty_{{ $rowIndex }}" value="{{ $node['total_qty'] }}"></td>
    <td><input type="number" step="any" class="form-control text-end mw-100 analyse_remaining_qty" data-parent-key="{{ $parentKey }}" data-current-key="{{ $rowKey }}" id="analyse_remaining_qty_{{ $rowIndex }}" value="0" disabled></td>
</tr>
@if ($hasChildren)
    @php $childIndex = 1; @endphp
    @foreach ($node['children'] as $childNode)
        @include('procurement.pi.partials.semi-finished-row', ['node' => $childNode, 'parentSoId' => $effectiveSoId, 'rowIndex' => $childIndex++, 'parentKey' => $rowKey, 'level' => $level + 1])
    @endforeach
@endif

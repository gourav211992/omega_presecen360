<tbody id="rcaItemBody">
    @foreach($rca->items as $index => $item)
        @php
            $rgrSegregation = $item->rgrSegregation ?? null;
            $rgrImages = $rgrSegregation && $rgrSegregation->media
                ? $rgrSegregation->media->map(fn($m) => asset("storage/".$m->file_name))->values()
                : collect([]);
        @endphp

        <tr id="row_{{ $index }}" data-index="{{ $index }}" class="item_detail_row">
            <td>
                <input type="hidden" name="rca_items[{{ $index }}][id]" value="{{ $item->id }}">
                {{ $index + 1 }}
            </td>

            {{-- Item Code --}}
            <td class="poprod-decpt">
                {{ $item->item_code ?? '' }}
                <input type="hidden" name="rca_items[{{ $index }}][item_id]" value="{{ $item->item_id }}">
                <input type="hidden" name="rca_items[{{ $index }}][item_code]" value="{{ $item->item_code }}">
                <input type="hidden" name="rca_items[{{ $index }}][item_name]" value="{{ $item->item_name }}">
                <input type="hidden" name="rca_items[{{ $index }}][hsn_code]" value="{{ $item->item->hsn->code ?? '' }}">
                <input type="hidden" name="rca_items[{{ $index }}][remark]" value="{{ $item->remark?? '' }}">
                <input type="hidden" name="rca_items[{{ $index }}][rgr_job_detail_id]" value="{{ $item->rgr_job_detail_id ?? '' }}">
                <input type="hidden" name="rca_items[{{ $index }}][rgr_item_segregation_id]" value="{{ $item->rgr_item_segregation_id ?? '' }}">
            </td>

            {{-- Item Name --}}
            <td class="poprod-decpt">{{ $item->item_name ?? '' }}</td>

            {{-- Attributes --}}
            <td class="poprod-decpt">
                @php
                    $selectedAttrValues = $item->attributes->map(fn($attr) => [
                        'id' => $attr->id,
                        'item_attribute_id' => $attr->item_attribute_id,
                        'attribute_group_id' => $attr->attr_name,
                        'attribute_group_name' => $attr->attribute_name,
                        'attribute_value' => $attr->attribute_value,
                        'attribute_id' => $attr->attr_value,
                    ]);
                @endphp

                @foreach ($selectedAttrValues as $attr)
                    <span class="badge rounded-pill badge-light-primary">
                        <strong>{{ $attr['attribute_group_name'] }}</strong>: {{ $attr['attribute_value'] }}
                    </span>
                @endforeach

                @foreach ($selectedAttrValues as $attrIndex => $attr)
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][id]" value="{{ $attr['id'] }}">
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][item_attribute_id]" value="{{ $attr['item_attribute_id'] }}">
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][attr_name]" value="{{ $attr['attribute_group_id'] }}">
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][attribute_name]" value="{{ $attr['attribute_group_name'] }}">
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][attribute_value]" value="{{ $attr['attribute_value'] }}">
                    <input type="hidden" name="rca_items[{{ $index }}][rca_item_attributes][{{ $attrIndex }}][attr_value]" value="{{ $attr['attribute_id'] }}">
                @endforeach
            </td>

            {{-- UOM --}}
            <td class="poprod-decpt">
                {{ $item->uom_code ?? '' }}
                <input type="hidden" name="rca_items[{{ $index }}][uom_id]" value="{{ $item->uom_id }}">
                <input type="hidden" name="rca_items[{{ $index }}][uom_code]" value="{{ $item->uom_code }}">
            </td>
            
            {{-- UID --}}
            <td class="poprod-decpt">
                {{ $item->item_uid ?? '' }}
                <input type="hidden" name="rca_items[{{ $index }}][item_uid]" value="{{ $item->item_uid ?? '' }}">
            </td>

            {{-- Scheduled Qty --}}
            <td class="poprod-decpt">
                {{ $item->scheduled_qty ?? 0 }}
                <input type="hidden" name="rca_items[{{ $index }}][scheduled_qty]" value="{{ $item->scheduled_qty ?? 0 }}">
            </td>

            {{-- Missing Qty --}}
            <td class="poprod-decpt">
                {{ $item->missing_qty ?? 0 }}
                <input type="hidden" name="rca_items[{{ $index }}][missing_qty]" value="{{ $item->missing_qty ?? 0 }}">
            </td>

          
            {{-- RGR Detail --}}
            <td class="poprod-decpt">
                        <a href="javascript:void(0)" class="open-rgr-modal"
                        data-rgr-segregation='{{ json_encode([
                                "id"                    => $rgrSegregation->id ?? 0,
                                "packing_status"        => $rgrSegregation->packing_status ?? 0,
                                "label_status"          => $rgrSegregation->label_status ?? 0,
                                "delivery_cancel"       => $rgrSegregation->delivery_cancel ?? 0,
                                "new_item_id"           => $rgrSegregation->new_item_id ?? null,
                                "new_item_code"         => $rgrSegregation->new_item_code ?? '',
                                "new_item_name"         => $rgrSegregation->new_item_name ?? '',
                                "new_item_attributes"   => $rgrSegregation->new_item_attributes ?? '',
                                "segregation_category"  => $rgrSegregation->defect_severity ?? '',
                                "defect_type"           => $rgrSegregation->defect_type ?? '',
                                "damage_nature"         => $rgrSegregation->damage_nature ?? '',
                                "remarks"               => $rgrSegregation->remarks ?? ''
                        ]) }}'
                        data-rgr-images='{{ json_encode($rgrImages) }}'>
                    <i data-feather="eye"></i>
                </a>
            </td>

           {{-- Button to open RCA modal --}}
            <td class="poprod-decpt">
                <button type="button"data-bs-toggle="modal"data-bs-target="#addRcaModal"class="btn p-25 btn-sm btn-outline-secondary open-rca-modal"style="font-size: 10px"data-item-id="{{ $item->id }}"data-rca-remarks="{{ $item->remark ?? '' }}"data-rca-images='@json($item->media ?? [])'>
                    <i data-feather="edit-3"></i> Add RCA
                </button>
            </td>
        </tr>
    @endforeach
</tbody>

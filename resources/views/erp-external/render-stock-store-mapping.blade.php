@forelse ($data as $index => $mapping)
    @php $rowNumber = $index + 1; @endphp
    <tr>
        <td class="serial-number">{{ $rowNumber }}</td>
        <td>
            <input type="text" placeholder="Enter Stock Type" class="form-control mw-100"
                value="{{ $mapping->stock_type }}" disabled />
        </td>

        <td>
            <input type="text" placeholder="Enter Stock Type" class="form-control mw-100"
                value="{{ $mapping->substore->name }}" disabled />
        </td>

        <td class="text-center">
            <input type="checkbox" value="1" {{ $mapping->is_primary == 1 ? 'checked' : '' }} disabled>
        </td>

        <td class="center-align-content">
            <a href="javascript:;" class="text-danger remove_row"
                data-url="{{ route('external-integration.remove-stock-store-mapping', ['id' => $mapping->id]) }}"
                data-request="remove">
                <i data-feather="trash-2"></i>
            </a>
        </td>
    </tr>
@empty
@endforelse

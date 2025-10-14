<div class="vendor-autocomplete-wrapper">
    <input type="text" class="form-control vendor-autocomplete vendor-select" placeholder="Select Vendor" data-ajax-url="{{ $ajaxSearchUrl ?? '#' }}" data-hidden-name="components[{{ $rowCount }}][vendor_id]" value="{{ $vendor?->company_name ?? '' }}">
    <input type="hidden" name="components[{{ $rowCount }}][vendor_id]" value="{{ $vendor?->id ?? '' }}">
</div>

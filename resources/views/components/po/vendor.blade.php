<div class="vendor-autocomplete-wrapper">
    <input type="text" class="form-control vendor-autocomplete vendor-select" placeholder="Select Vendor" data-ajax-url="{{ $ajaxSearchUrl ?? '#' }}" data-hidden-name="vend_name" value="{{ $vendor?->company_name ?? '' }}" style="max-width: 300px;">
    <input type="hidden" name="vend_name" value="{{ $vendor?->id ?? '' }}">
</div>

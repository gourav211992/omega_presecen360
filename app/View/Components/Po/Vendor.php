<?php

namespace App\View\Components\Po;

use App\Helpers\ConstantHelper;
use App\Helpers\ItemHelper;
use App\Models\Vendor as ModelsVendor;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Vendor extends Component
{
    public $row;
    public $vendor;
    public $ajaxSearchUrl;

    public function __construct($row, $documentDate, bool $defaultOption = false)
    {
        $this->row = $row;
        $approvedVendorIds = ItemHelper::getItemApprovedVendors($row?->item_id, $documentDate) ?? [];
        $this->vendor = ModelsVendor::select('id', 'vendor_code', 'company_name')->withDefaultGroupCompanyOrg()->where('id', $row?->vendor_id)->first();
        if (!$this->vendor && count($approvedVendorIds)) {
            $this->vendor = ModelsVendor::select('id', 'vendor_code', 'company_name')->withDefaultGroupCompanyOrg()
                ->whereIn('id', $approvedVendorIds)->first();
        }

        $this->ajaxSearchUrl = route('po.vendors.search', ['type' => 'purchase-order']) . '?' . http_build_query(['item_id' => $row?->item_id, 'document_date' => $documentDate]);
    }

    public function render(): View|Closure|string
    {
        return view(
            'components.po.vendor',
            [
                'row' => $this->row,
                'vendor' => $this->vendor,
                'ajaxSearchUrl' => $this->ajaxSearchUrl,
            ]
        );
    }
}

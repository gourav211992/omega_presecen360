<?php

namespace App\View\Components\PoBulk;

use Closure;
use App\Helpers\ItemHelper;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use App\Models\Vendor as ModelsVendor;

class Vendor extends Component
{
    public $row;
    public $vendor;
    public $rowCount;
    public $ajaxSearchUrl;
    public $firstVendorId;

    public function __construct($row, $documentDate, $rowCount)
    {
        $this->row = $row;
        $this->rowCount = $rowCount;
        $approvedVendorIds = ItemHelper::getItemApprovedVendors($row?->item_id, $documentDate) ?? [];
        $this->vendor = ModelsVendor::select('id', 'vendor_code', 'company_name')->withDefaultGroupCompanyOrg()->where('id', $row?->vendor_id)->first();
        if (!$this->vendor && count($approvedVendorIds)) {
            $this->vendor = ModelsVendor::select('id', 'vendor_code', 'company_name')->withDefaultGroupCompanyOrg()
                ->whereIn('id', $approvedVendorIds)->first();
        }

        $this->firstVendorId = $this->vendor?->id;
        $this->ajaxSearchUrl = route('po.vendors.search', ['type' => 'purchase-order']) . '?' . http_build_query(['item_id' => $row?->item_id, 'document_date' => $documentDate]);
    }

    public function render(): View|Closure|string
    {
        return view(
            'components.po-bulk.vendor',
            [
                'row' => $this->row,
                'vendor' => $this->vendor,
                'rowCount' => $this->rowCount,
                'ajaxSearchUrl' => $this->ajaxSearchUrl,
            ]
        );
    }
}

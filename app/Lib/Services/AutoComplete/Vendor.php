<?php

namespace App\Lib\Services\AutoComplete;

use App\Models\Vendor as ErpVendor;
use Illuminate\Database\Eloquent\Collection;

class Vendor
{
    private $searchTerm = "";
    private $resultsLimit = 10;

    public function __construct(String|null $search = null)
    {
        $this->searchTerm = $search;
    }

    public function transporterList() : Collection
    {
        $results = [];
        $term = $this->searchTerm;
        $results = ErpVendor::select('id', 'company_name') -> with(['compliances' => function ($subQuery) {
            $subQuery -> select('id', 'morphable_type', 'morphable_id', 'gstin_no');
        }]) ->where('vendor_sub_type', 'Transporter')->when($this->searchTerm, function ($query) use($term) {
                $query -> where('vendor_code', 'LIKE', '%' . $term . '%')
                ->orWhere('company_name', 'LIKE', '%'. $term . '%');
            }) -> limit($this->resultsLimit) -> get();
        return $results;
    }
}
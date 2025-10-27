<?php

namespace App\Lib\Services\AutoComplete;

use App\Helpers\ConstantHelper;
use App\Models\ERP\ErpConsignee;
use Illuminate\Database\Eloquent\Collection;

class Consignee
{
    private $searchTerm = "";
    private $resultsLimit = 10;

    public function __construct(String|null $search = null)
    {
        $this->searchTerm = $search;
    }

    public function customerList() : Collection
    {
        $results = [];
        $term = $this->searchTerm;
        $results = ErpConsignee::select('id', 'consignee_name')->where('status',ConstantHelper::ACTIVE)
            ->where('is_customer', 1)->when($this->searchTerm, function ($query) use($term) {
                $query -> where('consignee_code', 'LIKE', '%' . $term . '%')
                ->orWhere('consignee_name', 'LIKE', '%'. $term . '%');
            }) -> limit($this->resultsLimit) -> get();
        return $results;
    }
}
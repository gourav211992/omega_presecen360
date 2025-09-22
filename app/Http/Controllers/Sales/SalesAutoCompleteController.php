<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Lib\Services\AutoComplete\Consignee;
use Exception;
use Illuminate\Http\Request;

class SalesAutoCompleteController extends Controller
{
    public function customerConsigneeList(Request $request)
    {
        $term = $request->input('q', ''); //Search Input       
        try {
            $consigneeAutoComplete = new Consignee($term);
            $results = $consigneeAutoComplete -> customerList();
            return response()->json($results);
        } catch (Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}

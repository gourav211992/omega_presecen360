<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Lib\Services\AutoComplete\Consignee;
use App\Models\ERP\ErpConsignee;
use App\Models\ErpAddress;
use Exception;
use Illuminate\Http\Request;

class CustomerConsigneeController extends Controller
{
    public function getCustomerConsigneeAddresses(String $id)
    {
        $consigneeId = $id; //Consignee Id      
        try {
            $addresses = ErpAddress::where('addressable_type', ErpConsignee::class)
                ->where('addressable_id', $consigneeId)->orderByDesc('id')->get();
            if (count($addresses) == 0) {
                return response()->json([
                    'data' => array(
                        'error_message' => 'No addresses found for the consignee'
                    )
                ]);
            }
            foreach ($addresses as $shippingAddress) {
                $shippingAddress->value = $shippingAddress->id;
                $shippingAddress->label = $shippingAddress->display_address;
            }
            return response()->json([
                'data' => array(
                    'shipping_addresses' => $addresses,
                )
            ]); 
        } catch (Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}

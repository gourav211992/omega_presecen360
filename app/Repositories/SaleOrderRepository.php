<?php

namespace App\Repositories;

use App\Models\ErpSaleOrder;
use Exception;

class SaleOrderRepository
{
    public function getDetail($request)
    {
        if (!$request->trip_number) throw new Exception('Trip number is required.');
        if (!$request->ref_order_number) throw new Exception('Reference order number is required.');
        if (!$request->organization_id) throw new Exception('Organization id is required.');

        $data = ErpSaleOrder::with('items')
            ->where('trip_number',$request->trip_number)
            ->where('organization_id',$request->organization_id)
            ->where('ref_order_number',$request->ref_order_number)
            ->first();

        if (!$data) {
            return [
                'message' => 'No data found.',
                'status' => false
            ];
        }
        

        return [
            'message' => 'Data found.',
            'status' => true,
            'data' => $data
        ];

    }
}
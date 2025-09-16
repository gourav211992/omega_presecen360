<?php
namespace App\Services\Sales\Order;
use App\Helpers\Helper;

class SoAmend
{
    const MODEL_HEIRARCHY = [
        ['model_type' => 'header', 'model_name' => 'ErpSaleOrder', 'relation_column' => ''],
        ['model_type' => 'detail', 'model_name' => 'ErpSoDynamicField', 'relation_column' => 'header_id'],
        ['model_type' => 'detail', 'model_name' => 'ErpSoItem', 'relation_column' => 'sale_order_id'],
        ['model_type' => 'sub_detail', 'model_name' => 'ErpSoItemAttribute', 'relation_column' => 'so_item_id'],
        ['model_type' => 'sub_detail', 'model_name' => 'ErpSoItemDelivery', 'relation_column' => 'so_item_id'],
        ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleOrderTed', 'relation_column' => 'so_item_id'],
        ['model_type' => 'sub_detail', 'model_name' => 'ErpSoItemBom', 'relation_column' => 'so_item_id'],
        ['model_type' => 'sub_detail', 'model_name' => 'ErpSoJobWorkItem', 'relation_column' => 'so_item_id'],
    ];

    public function amend(int $saleOrderHeaderId)
    {
        Helper::documentAmendment(self::MODEL_HEIRARCHY, $saleOrderHeaderId);
    }
}

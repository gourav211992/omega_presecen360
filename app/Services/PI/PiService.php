<?php

namespace App\Services\PI;

use App\Models\Bom;
use App\Models\Item;
use App\Models\BomDetail;
use App\Models\ErpSoItem;
use App\Helpers\ItemHelper;
use App\Models\PiSoMapping;
use App\Models\ErpSaleOrder;
use App\Models\ErpSoItemBom;

class PiService
{

    public function syncPiSoMapping($soId, $soItemId, $itemId, $attr, $soQty, $createdBy, $soItemOrderQty)
    {
        $so   = ErpSaleOrder::find($soId);
        $item = Item::find($itemId);
        $checkBomExist = ItemHelper::checkItemBomExists($itemId, $attr);

        if (!$checkBomExist['bom_id']) {
            return ['status' => 200, 'message' => 'No BOM found.'];
        }

        $bom = Bom::find($checkBomExist['bom_id']);
        $bufferPerc = ItemHelper::getBomSafetyBufferPerc($bom->id);

        $bomDetails = (strtolower($bom->customizable) === 'no')
            ? BomDetail::where('bom_id', $checkBomExist['bom_id'])->get()
            : ErpSoItemBom::where('bom_id', $checkBomExist['bom_id'])
            ->where('sale_order_id', $soId)
            ->where('so_item_id', $soItemId)
            ->get();

        if (strtolower($bom->customizable) === 'yes' && $bomDetails->isEmpty()) {
            $bomDetails = BomDetail::where('bom_id', $checkBomExist['bom_id'])->get();
        }

        foreach ($bomDetails as $bomDetail) {
            $bomDetailId = null;
            $vendorId    = null;
            $attributes  = [];

            if ($bomDetail instanceof \App\Models\BomDetail) {
                $attributes = $bomDetail->attributes->map(fn($attribute) => [
                    'attribute_id'   => intval($attribute->item_attribute_id),
                    'attribute_value' => intval($attribute->attribute_value),
                ])->toArray();
                $bomDetailId = $bomDetail->id;
                $vendorId    = $bomDetail?->vendor_id;
            } elseif ($bomDetail instanceof \App\Models\ErpSoItemBom) {
                $attributes = array_map(fn($attribute) => [
                    'attribute_id'   => intval($attribute['attribute_id']),
                    'attribute_value' => intval($attribute['attribute_value_id']),
                ], $bomDetail->item_attributes ?? []);
                $bomDetailId = $bomDetail->bom_detail_id;
                $vendorId    = $bomDetail?->bomDetail?->vendor_id;
            }

            $checkBomExist = ItemHelper::checkItemBomExists($bomDetail->item_id, $attributes);

            if (in_array($checkBomExist['sub_type'], ['Finished Goods', 'WIP/Semi Finished'])) {
                if (!$checkBomExist['bom_id']) {
                    $name       = $bomDetail?->item?->item_name;
                    $parentName = $item?->item_name;
                    return ['status' => 422, 'message' => "Child BOM missing for $name under $parentName"];
                }
            }

            $requiredQty = floatval($soQty) * floatval($bomDetail->qty);
            if ($bufferPerc > 0) {
                $requiredQty += $requiredQty * $bufferPerc / 100;
            }

            if (!in_array($checkBomExist['sub_type'], ['Expense'])) {
                $mappingData = [
                    'so_id'        => $soId,
                    'so_item_id'   => $soItemId,
                    'item_id'      => $bomDetail->item_id,
                    'created_by'   => $createdBy,
                    'bom_id'       => $bomDetail->bom_id ?? null,
                    'bom_detail_id' => $bomDetailId ?? null,
                    'vendor_id'    => $vendorId ?? null,
                    'item_code'    => $bomDetail->item_code,
                    'order_qty'    => floatval($soItemOrderQty),
                    'bom_qty'      => floatval($bomDetail->qty),
                    'qty'          => $requiredQty,
                    'attributes'   => json_encode($attributes),
                    'child_bom_id' => $checkBomExist['bom_id']
                ];

                $mapping = PiSoMapping::where([
                    ['so_id', $mappingData['so_id']],
                    ['so_item_id', $mappingData['so_item_id']],
                    ['item_id', $mappingData['item_id']]
                ])
                    ->whereJsonContains('attributes', $attributes)
                    ->first() ?? new PiSoMapping();

                $mapping->fill($mappingData);
                $mapping->save();
            }
        }

        return ['status' => 200, 'message' => 'Saved!'];
    }
}

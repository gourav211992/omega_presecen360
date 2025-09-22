<?php
namespace App\Services;

use App\Models\MoItem;
use App\Models\MoProduct;
use App\Models\MoBomMapping;
use App\Models\MfgOrderHistory;
use App\Models\PwoStationConsumption;
use App\Models\ErpMoDynamicFieldHistory;
use App\Models\MoBomMappingHistory;

class MoDeleteService
{
    /**
     * Delete MOProduct for a MO.
     *
     * @param array  $deletedData Data containing deleted consumption item IDs.
     * @param object $mo          The MO instance.
     * @return array              Success or error response.
     */
   public function deleteMoHistory($mo)
    {
        $mfgHistories = MfgOrderHistory::where('source_id', $mo->id)->get();

        foreach ($mfgHistories as $mfgHistory) {
            // Delete product attributes
            foreach ($mfgHistory->moProducts as $moProduct) {
                $moProduct->attributes()->delete();
            }

            // Delete item attributes
            foreach ($mfgHistory->moItems as $moItem) {
                $moItem->attributes()->delete();
            }

            // Delete products and items
            $mfgHistory->moProducts()->delete();
            $mfgHistory->moItems()->delete();

            // Delete related dynamic fields
            ErpMoDynamicFieldHistory::where('header_id', $mfgHistory->id)->delete();

            // Fixed:
            MoBomMappingHistory::where('mo_id', $mfgHistory->id)->delete();
            $mfgHistory->delete();
        }
    }


    public function deleteMoItems(array $deletedData, $mo)
    {
        // Early exit if no production items are marked for deletion
        if (empty($deletedData['deletedPiItemIds'])) {
            return self::successResponse("No MO items found to delete.");
        }

        // Fetch MO product items for deletion
        $moProductItems = MoProduct::whereIn('id', $deletedData['deletedPiItemIds'])->get();

        if ($moProductItems->isEmpty()) {
            return self::errorResponse("No matching MO product items found to delete.");
        }

        foreach ($moProductItems as $moProductItem) {
            // Clean related BOM items
            if($moProductItem->pslip_qty>0){
                return self::errorResponse("{$moProductItem->item_code} item code use in Production Slip.");
            }
            $cleanupResult = $this->cleanupMoBomItems($moProductItem, $mo);
            if ($cleanupResult['status'] === 'error') {
                return $cleanupResult;
            }

            // Update station consumption
            $result = $this->updatePwoStationConsumption($moProductItem);
            if ($result['status'] === 'error') {
                return $result;
            }

            // Delete attributes and product
            try {
                $moProductItem->attributes()->delete();
                $moProductItem->delete();
            } catch (\Throwable $e) {
                return self::errorResponse("Failed to delete MO product item ID {$moProductItem->id}: " . $e->getMessage());
            }
        }

        return self::successResponse("Manufacturing Order deleted successfully.");
    }

    /**
     * Back update MO Product and PWO Station consumption.
     */
    private function updatePwoStationConsumption($moProductItem)
    {
        if (!$moProductItem?->pwoMapping) {
            // Not an error: just no mapping to update
            return self::successResponse("No PWO mapping found for this MO product item.");
        }

        try {
            $pwoStation = PwoStationConsumption::where('pwo_mapping_id', $moProductItem->pwoMapping->id)
                ->where('station_id', $moProductItem->mo->station_id)
                ->where('mo_id', $moProductItem->mo_id)
                ->first();

            if ($pwoStation) {
                $pwoStation->mo_product_qty -= $moProductItem->qty;
                $pwoStation->mo_id = null;
                $pwoStation->save();
            }
                $minQty = PwoStationConsumption::where('pwo_mapping_id', $moProductItem->pwoMapping->id)->min('mo_product_qty');
                // $deductQty = $minQty?? $moProductItem->qty;
                if($minQty&&$minQty>=0){
                    $moProductItem->pwoMapping->mo_product_qty = $minQty;
                }else{
                    $moProductItem->pwoMapping->mo_product_qty = $moProductItem->qty;
                }
                $moProductItem->pwoMapping->mo_id = null;
                $moProductItem->pwoMapping->save();
           
            return self::successResponse("PWO Station consumption updated successfully.");
        } catch (\Throwable $e) {
            return self::errorResponse("Failed to update PWO Station consumption: " . $e->getMessage());
        }
    }

    /**
     * Clean up related records and delete MoBomMapping.
     */
    private function cleanupMoBomItems($moProductItem, $mo)
    {
        try {
            $mappings = MoBomMapping::where('mo_product_id', $moProductItem->id)->get();

            $mappings->each(function ($mapping) use ($mo) {
                $moItems = MoItem::where([
                    ['bom_detail_id', $mapping->bom_detail_id],
                    ['station_id', $mapping->station_id],
                    ['item_id', $mapping->item_id],
                    ['uom_id', $mapping->uom_id],
                    ['mo_id', $mo->id],
                ])->get();

                $moItems->each(function ($item) {
                    $item->attributes()->delete();
                    $item->delete();
                });

                $mapping->delete();
            });

            return self::successResponse("MO BOM items cleaned successfully.");
        } catch (\Throwable $e) {
            return self::errorResponse("Failed to clean MO BOM items: " . $e->getMessage());
        }
    }

    /**
     * Standardized error response.
     */
    private static function errorResponse($message)
    {
        return [
            "status"  => "error",
            "code"    => 500,
            "message" => $message,
            "data"    => null,
        ];
    }

    /**
     * Standardized success response.
     */
    private static function successResponse($message)
    {
        return [
            "status"  => "success",
            "code"    => 200,
            "message" => $message,
        ];
    }
}

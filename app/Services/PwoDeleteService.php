<?php
namespace App\Services;

use App\Models\PwoSoMapping;
use App\Models\ErpPwoItem;
use App\Models\ErpProductionWorkOrderHistory;


class PwoDeleteService
{
    /**
     * Delete MOProduct for a MO.
     *
     * @param array  $deletedData Data containing deleted consumption item IDs.
     * @param object pwo          The MO instance.
     * @return array              Success or error response.
     */
    public function deletePwoHistory($pwo)
    {
        $pwoHistories = ErpProductionWorkOrderHistory::where('source_id', $pwo->id)->get();

        foreach ($pwoHistories as $pwoHistory) {
            // Delete product attributes
            foreach ($pwoHistory->mapping as $pwoMapping) {
                $pwoMapping->pwoBomMapping()->delete();
                $pwoMapping->pwoStationConsumption()->delete();
            }

            // Delete item attributes
            foreach ($pwoHistory->items as $pwoItem) {
                $pwoItem->attributes()->delete();
            }

            // Delete products and items
            $pwoHistory->mapping()->delete();
            $pwoHistory->items()->delete();

            // Delete related dynamic fields
            // ErpPwoDynamicFieldHistory::where('header_id', $pwoHistory->id)->delete();
            $pwoHistory->delete();
        }
    }


    public function deletePwoItems(array $deletedData, $pwo)
    {
        // Early exit if no production items are marked for deletion
        if (empty($deletedData['deletedPiItemIds'])) {
            return self::successResponse("No PWO items found to delete.");
        }

        // Fetch PWO product items for deletion
        $pwoSoMappings = PwoSoMapping::whereIn('id', $deletedData['deletedPiItemIds'])->get();

        if ($pwoSoMappings->isEmpty()) {
            return self::errorResponse("No matching PWO product items found to delete.");
        }

        foreach ($pwoSoMappings as $pwoSoMapping) {
            // Clean related BOM items
            if($pwoSoMapping->mo_product_qty>0){
                return self::errorResponse("{$pwoSoMapping->item_code} item code use in MO.");
            }
            $cleanupResult = $this->cleanupPwoBomItems($pwoSoMapping);
            if ($cleanupResult['status'] === 'error') {
                return $cleanupResult;
            }  

            // Update station consumption
            $result = $this->updatePwoStationConsumption($pwoSoMapping);
            if ($result['status'] === 'error') {
                return $result;
            }
            // Delete  product
        
                 if($pwoSoMapping->soItem) {
                        $pwoSoMapping->soItem->pwo_qty -= $pwoSoMapping->inventory_uom_qty; 
                        $pwoSoMapping->soItem->save();
                    }
                $pwoSoMapping->delete();
         
        }

        return self::successResponse("Production Work Order deleted successfully.");
    }

    /**
     * Back update PWO Product and PWO Station consumption.
     */
    private function updatePwoStationConsumption($pwoSoMapping)
    {
        if (!$pwoSoMapping?->pwoStationConsumption) {
            // Not an error: just no mapping to update
            return self::successResponse("No PWO Consumption found for this PWO product item.");
        }
     
            $pwoSoMapping?->pwoStationConsumption()->delete();
            return self::successResponse("PWO Station consumption updated successfully.");
       
    }

    /**
     * Clean up related records and delete MoBomMapping.
     */
    private function cleanupPwoBomItems($pwoSoMapping)
    {
       

            $groupedItems = $pwoSoMapping->pwoBomMapping()->get();
          
            foreach($groupedItems as $groupedItem) {
                $pwoItem = ErpPwoItem::where('pwo_id', $groupedItem->pwo_id)
                    ->where('item_id', $groupedItem->item_id)
                    ->where('uom_id', $groupedItem->uom_id)
                    ->when(count($groupedItem->attributes), function ($query) use ($groupedItem) {
                        foreach ($groupedItem->attributes as $attribute) {
                            $query->whereHas('attributes', function ($pwoItemAttrQuery) use ($attribute) {
                                $pwoItemAttrQuery->where('item_attribute_id', $attribute['attribute_id'])
                                                ->where('attribute_id', $attribute['attribute_value']);
                            });
                        }
                    })
                    ->get();
                 
                    foreach($pwoItem as $pwoItem) {
                        $pwoItem?->attributes()?->delete();
                        $pwoItem->delete();
                    }
                    $groupedItem->delete();
            }

            return self::successResponse("PWO BOM cleaned successfully.");
       
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

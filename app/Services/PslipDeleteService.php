<?php
namespace App\Services;

use App\Models\MoItem;
use App\Models\ErpSoItem;
use App\Models\PwoSoMapping;
use App\Models\ErpPslipItem;
use App\Models\ErpPslipMedia;
use App\Models\ErpPslipItemDetail;
use App\Models\PslipBomConsumption;
use App\Models\ErpPslipItemLocation;
use App\Models\PwoStationConsumption;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ConstantHelper;
use App\Helpers\InventoryHelperV2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class PslipDeleteService
{
     /**
     * Delete production item for a production slip.
     *
     * @param array $deletedData      Data containing deleted consumption item IDs
     * @param object $productionSlip  The production slip instance
     * @return array                  Success or error response
     */
    public function deleteProductionItems(array $deletedData, $productionSlip)
    {
        // ✅ Early exit if no production items are marked for deletion
        if (empty($deletedData['deletedSiItemIds'])) {
            return self::successResponse("No production items found to delete.");
        }

        // ✅ Fetch all Pslip Items with related item & MO product in one go

        $psItems = ErpPslipItem::whereIn('id', $deletedData['deletedSiItemIds'])->get();

        # all ted remove item level
        foreach($psItems as $psItem) {

            $pslipBomMappings = PslipBomConsumption::where('pslip_id', $productionSlip?->id)
                    ->where('pslip_item_id', $psItem?->id)
                    ->get();

            // Process Delete Pslip Bom Consumptions
            $result = $this->processDeletePslipBomConsumptions($pslipBomMappings, $productionSlip);
          
            // 3: Check stock for receipt reversal
            $pslipItemattributes = $psItem->attributes ?? [];
            $ItemselectedAttr = collect($pslipItemattributes)->pluck('attribute_value')->filter()->values()->toArray();

            $receiptCheck = $this->checkReceiptStock($psItem, $productionSlip, $ItemselectedAttr);

            if ($receiptCheck !== true) return $receiptCheck;
            
            if($psItem->subprime_qty > 0) {
                    $subReceiptCheck = $this->checkReceiptStock($psItem, $productionSlip, $ItemselectedAttr); 
                    if ($subReceiptCheck !== true) return $subReceiptCheck;
            }
            if($productionSlip->rg_sub_store_id && $psItem->rejected_qty > 0) {
                $rjReceiptCheck = $this->checkReceiptRejectStock($psItem, $productionSlip, $ItemselectedAttr);
                if ($rjReceiptCheck !== true) return $rjReceiptCheck;
            }
               // 4: Update MO product & station consumption
            $this->updatePwoStationConsumption($psItem);

            // 5: Update Sales Order & Mapping if applicable
            $this->updateSalesOrderAndMapping($psItem, $productionSlip);

            // 6: Clean up related records and delete psItem
            $this->cleanupPsItem($psItem, $productionSlip);
            // Remove attributes
            $psItem->attributes()->delete();

            // Delete the item itself
            $psItem->delete();
            return $result['status'] === 'error'
                ? self::errorResponse($result['message'])
                :$result;

        }

        return self::successResponse("Pslip production items deleted successfully.");
    }

    /**
     * Delete consumption items for a production slip.
     *
     * @param array $deletedData      Data containing deleted consumption item IDs
     * @param object $productionSlip  The production slip instance
     * @return array                  Success or error response
     */
    public function deleteMedia(array $deletedData, $productionSlip)
    {
            if (empty($deletedData['deletedAttachmentIds'])) {
                return self::successResponse("No documents found to delete.");
            }

            $medias = ErpPslipMedia::whereIn('id', $deletedData['deletedAttachmentIds'])
                        ->get();
            foreach ($medias as $media) {
                if ($productionSlip->document_status == ConstantHelper::DRAFT) {
                    Storage::delete($media->file_name);
                }
                $media->delete();
            }

        return self::successResponse("Pslip documents deleted successfully.");
    }
    
    /**
     * Delete consumption items for a production slip.
     *
     * @param array $deletedData      Data containing deleted consumption item IDs
     * @param object $productionSlip  The production slip instance
     * @return array                  Success or error response
     */
    public function deleteConsumptionItems(array $deletedData, $productionSlip)
    {
        // ✅ Early exit if no consumption items are marked for deletion
        if (empty($deletedData['deletedConsItemIds'])) {
            return self::successResponse("No consumption items found to delete.");
        }

        // ✅ Fetch all BOM consumptions with related item & MO product in one go
        $pslipBomConsumptions = PslipBomConsumption::whereIn('id', $deletedData['deletedConsItemIds'])
            ->get();

        // Process Delete Pslip Bom Consumptions
        $this->processDeletePslipBomConsumptions($pslipBomConsumptions, $productionSlip);

        return self::successResponse("Pslip consumption items deleted successfully.");
    }

    /**
     * Process delete Pslip Bom Consumption
     */

    private function processDeletePslipBomConsumptions($pslipBomMappings, $productionSlip)
    {

        foreach($pslipBomMappings as $keys=>$pslipBomMapping) {  
         
            $attributes = $pslipBomMapping->attributes ?? [];
            $psItem = $pslipBomMapping->pslip_item;

            // 1: Update MO Item consumed quantity
            $this->updateMoItemConsumption($pslipBomMapping, $psItem, $attributes);

            // Prepare attributes for stock checks
            $selectedAttr = collect($attributes)->pluck('attribute_value')->filter()->values()->toArray();

            // 2: Check stock for issue reversal
            $issueCheck = $this->checkIssueStock($pslipBomMapping, $psItem, $productionSlip, $selectedAttr);

            if ($issueCheck !== true) return $issueCheck;

        }

        return self::successResponse("Success.");

    }

    /**
     * Update consumed quantity in MO Item
     */
    private function updateMoItemConsumption($pslipBomMapping, $psItem, $moProductAttributes)
    {
        $moItem = MoItem::where('mo_id', $psItem?->mo_product?->mo_id)
            ->when($psItem->so_id, fn($q) => $q->where('so_id', $psItem->so_id))
            ->where('item_id', $pslipBomMapping?->item_id)
            ->when(!empty($moProductAttributes), function ($query) use ($moProductAttributes) {
                $query->whereHas('attributes', function ($attrQuery) use ($moProductAttributes) {
                    $attrQuery->where(function ($subQuery) use ($moProductAttributes) {
                        foreach ($moProductAttributes as $poAttribute) {
                            $subQuery->orWhere(fn($q) =>
                                $q->where('item_attribute_id', $poAttribute['item_attribute_id'] ?? $poAttribute['attribute_id'])
                                ->where('attribute_value', $poAttribute['attribute_value'])
                            );
                        }
                    });
                }, '=', count($moProductAttributes));
            })
            ->first();

        if ($moItem) {
            $moItem->consumed_qty -= $pslipBomMapping->consumption_qty;
            $moItem->save();
        }
    }

    /**
     * Validate stock for issue reversal
     */
    private function checkIssueStock($pslipBomMapping, $psItem, $productionSlip, array $selectedAttr)
    {
        $pslipData = [
            'document_header_id' => $productionSlip->id,
            'document_detail_id' => $pslipBomMapping->id,
            'item_id'            => $pslipBomMapping->item_id,
            'store_id'           => $psItem->store_id,
            'document_type'      => ConstantHelper::PRODUCTION_SLIP_SERVICE_ALIAS,
            'attributes'         => $selectedAttr,
            'sub_store_id'       => $psItem->sub_store_id,
            'transaction_type'   => 'issue',
            'document_status'    => $productionSlip->document_status,
            'book_type'          => ConstantHelper::PRODUCTION_SLIP_SERVICE_ALIAS,
        ];

        $check = InventoryHelperV2::checkStockForIssueDelete($pslipData, true);
        return $check['status'] === 'error'
            ? self::errorResponse($check['message'])
            : true;
    }

    /**
     * Validate stock for receipt reversal
     */
    private function checkReceiptStock($psItem, $productionSlip, array $selectedAttr)
    {
        $pslipItemData = [
            'qty' => $psItem->accepted_qty,
            'document_header_id' => $productionSlip->id,
            'document_detail_id' => $psItem->id,
            'item_id'            => $psItem->item_id,
            'store_id'           => $psItem->store_id,
            'document_type'      => ConstantHelper::PRODUCTION_SLIP_SERVICE_ALIAS,
            'attributes'         => $selectedAttr,
            'sub_store_id'       => $productionSlip->fg_sub_store_id,
            'transaction_type'   => 'receipt',
            'document_status'    => $productionSlip->document_status,
        ];

        $check = InventoryHelperV2::checkStockForDelete($pslipItemData, true);


        return $check['status'] === 'error'
            ? self::errorResponse($check['message'])
            : true;
    }


    private function checkReceiptRejectStock($psItem, $productionSlip, array $selectedAttr)
    {
        $pslipItemData = [
            'qty' => $psItem->rejected_qty,
            'document_header_id' => $productionSlip->id,
            'document_detail_id' => $psItem->id,
            'item_id'            => $psItem->item_id,
            'store_id'           => $psItem->store_id,
            'document_type'      => ConstantHelper::PRODUCTION_SLIP_SERVICE_ALIAS,
            'attributes'         => $selectedAttr,
            'sub_store_id'       => $productionSlip->rg_sub_store_id,
            'transaction_type'   => 'receipt',
            'document_status'    => $productionSlip->document_status,
        ];

        $check = InventoryHelperV2::checkStockForDelete($pslipItemData, true);


        return $check['status'] === 'error'
            ? self::errorResponse($check['message'])
            : true;
    }

    /**
     * Back update MO Product and PWO Station consumption
     */
    private function updatePwoStationConsumption($psItem)
    {
        if (!$psItem?->mo_product) return;

        $deductQty = $psItem->accepted_qty + $psItem->subprime_qty;

        // Update MO Product
        $psItem->mo_product->pslip_qty -= $deductQty;
        $psItem->mo_product->save();

        // Update PWO Station
        $pwoStationConsumption = PwoStationConsumption::where('pwo_mapping_id', $psItem->mo_product?->pwoMapping?->id)
            ->where('mo_id', $psItem->mo_product->mo_id)
            ->where('station_id', $psItem->mo_product?->mo?->station_id)
            ->first();

        if ($pwoStationConsumption) {
            $pwoStationConsumption->pslip_qty -= $deductQty;
            $pwoStationConsumption->save();
        }
    }

    /**
     * Back update Sales Order Item and Mapping if last station + approved amendment
     */
    private function updateSalesOrderAndMapping($psItem, $productionSlip)
    {
        $so_item=$psItem->so_item_id;

        $deductQty = $psItem->accepted_qty + $psItem->subprime_qty;
        if ($psItem->mo_product?->mo?->is_last_station && in_array($productionSlip->document_status ,ConstantHelper::DOCUMENT_STATUS_APPROVED)) {
            // Reduce duplicate database queries by using 'find' only if ID exists
           
            $so_item=ErpSoItem::where('id', $so_item)->update([
                'pslip_qty' => DB::raw("pslip_qty - {$deductQty}")
            ]);

            $pwoMappingId = $psItem->mo_product->pwo_mapping_id ?? null;
            if (!empty($pwoMappingId)) {
                PwoSoMapping::whereKey($pwoMappingId)->decrement('pslip_qty', $deductQty);
            }
        }

    }

    /**
     * Clean up related records and delete psItem
     */
    private function cleanupPsItem($psItem, $productionSlip)
    {
        ErpPslipItemLocation::where('pslip_item_id', $psItem->id)->delete();
        PslipBomConsumption::where("pslip_id", $productionSlip->id)
            ->where("pslip_item_id", $psItem->id)
            ->delete();
        ErpPslipItemDetail::where('pslip_item_id', $psItem->id)->delete();

    }

    private static function errorResponse($message)
    {
        return [
            "status" => "error",
            "code" => "500",
            "message" => $message,
            "data" => null,
        ];

    }

    private static function successResponse($response)
    {
        return [
            "status" => "success",
            "code" => "200",
            "message" => $response
        ];
    }
}

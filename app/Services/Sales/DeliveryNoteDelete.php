<?php
namespace App\Services\Sales;
use App\Helpers\ConstantHelper;
use App\Helpers\InventoryHelperV2;
use App\Models\ErpInvoiceItem;
use App\Models\ErpPlItemDetail;
use App\Models\ErpPslipItemDetail;
use App\Models\ErpSaleInvoice;
use App\Models\ErpSoItem;

class DeliveryNoteDelete
{
    public function deleteByRequest(array $deletedItemIds, ErpSaleInvoice $dn)
    {
        // Delete Dn items
        if (!empty($deletedItemIds)) {
            $dnItems = ErpInvoiceItem::whereIn('id', $deletedItemIds)->get();
            //Loop through all the items
            foreach ($dnItems as $dnItem) {  
                // Check Stock and delete
                $selectedAttr = $dnItem->attributes->pluck('attr_value')->filter()->values()->toArray();
                //Issue Stock Delete
                $issueCheck = $this->checkIssueStock($dnItem, $dnItem->sale_invoice_id, $dn->document_status, $selectedAttr);
                if ($issueCheck !== true) return $issueCheck;

                $dnQty = $dnItem->issue_qty;
                //Back Update in SO
                if ($dnItem -> so_item_id) {
                    $soItem = ErpSoItem::find($dnItem -> so_item_id);
                    if (isset($soItem)) {
                        $soItem -> dnote_qty -= $dnQty;
                        $soItem -> save();
                    }
                }
                //Back Update in Pick List (PL)
                if ($dnItem -> pl_item_id) {
                    $plItem = ErpPlItemDetail::find($dnItem -> pl_item_id);
                    if (isset($plItem)) {
                        $plItem -> dnote_qty -= $dnQty;
                        $plItem -> save();
                    }
                }
                //Packing List (Pack List) - TODO
                
                //Free up all the bundles
                ErpPslipItemDetail::where('dn_item_id', $dnItem -> id) -> update([
                    'dn_item_id' => null
                ]);
                //Remove Taxes/Discount/Expenses
                $dnItem->teds()->delete();
                //Remove Attributes
                $dnItem->attributes()->delete();
                $dnItem->delete();
            }
        }
        //Success
        $data = self::successResponse("DN Item deleted successfully.");
        return $data;
    }

    /**
     * Validate stock for issue reversal
     */
    private function checkIssueStock(ErpInvoiceItem $dnItem, int $headerId, string $documentStatus, array $selectedAttr)
    {
        $dnData = [
            'document_header_id' => $headerId,
            'document_detail_id' => $dnItem->id,
            'item_id'            => $dnItem->item_id,
            'store_id'           => $dnItem->store_id,
            'document_type'      => ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS,
            'attributes'         => $selectedAttr,
            'sub_store_id'       => $dnItem->sub_store_id,
            'transaction_type'   => 'issue',
            'document_status'    => $documentStatus,
            'book_type'          => ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS,
            'qty'                => $dnItem->inventory_uom_qty
        ];

        $check = InventoryHelperV2::checkStockForIssueDelete($dnData, true);
        return $check['status'] === 'error'
            ? self::errorResponse($check['message'])
            : true;
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

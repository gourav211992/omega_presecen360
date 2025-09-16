<?php
namespace App\Services\Sales\Order;
use App\Helpers\SuccessErrorArrayResponse;
use App\Models\ErpSoItem;
use App\Models\JobOrder\JoProduct;
use App\Models\PoItem;

class SoItemDelete
{
    public function deleteItem(array $deletedSoItemIds) : array
    {
        //Retrieve Items
        $soItems = ErpSoItem::whereIn('id',$deletedSoItemIds)->get();
        foreach($soItems as $soItem) {
            $itemName = $soItem -> item_name;
            //Check if PWO qty is there
            if ($soItem -> pwo_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Production Work Order of Item - $itemName has been generated");
            }
            //Check if Pslip qty is there
            if ($soItem -> pslip_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Production Slip of Item - $itemName has been generated");
            }
            //Check if Pslip qty is there
            if ($soItem -> plist_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Packing List of Item - $itemName has been generated");
            }
            //Check if Dnote Qty is there 
            if ($soItem -> dnote_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Delivery Note of Item - $itemName has been generated");
            }
            //Check if Invoice Qty is there 
            if ($soItem -> invoice_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Invoice of Item - $itemName has been generated");
            }
            //Check if Sales Return Qty is there
            if ($soItem -> srn_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Sales Return of Item - $itemName has been generated");
            }
            //Check if Expense Advice Qty is there
            if ($soItem -> expense_advise_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Sales Return of Item - $itemName has been generated");
            }
            //Check if Picked Qty is there
            if ($soItem -> picked_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Pick List of Item - $itemName has been generated");
            }
            //Check if Planned Qty is there
            if ($soItem -> planned_qty > 0) {
                return SuccessErrorArrayResponse::errorResponse("Trip Plan of Item - $itemName has been generated");
            }
            //Pulled from Quotation (SQ)
            if ($soItem -> sq_item_id) {
                $qtItem = ErpSoItem::find($soItem -> sq_item_id);
                if (isset($qtItem)) {
                    $qtItem -> quotation_order_qty -= $soItem -> order_qty;
                    $qtItem -> save();
                }
            }
            //Pulled From Purchase Order (PO)
            if ($soItem -> po_item_id) {
                $poItem = PoItem::find($soItem -> po_item_id);
                if (isset($poItem)) {
                    $poItem -> inter_org_so_qty -= $soItem -> order_qty;
                    $poItem -> save();
                }
            }
            //Pulled From Job Order (JO)
            if ($soItem -> jo_product_id) {
                $joProduct = JoProduct::find($soItem -> jo_product_id);
                if (isset($joProduct)) {
                    $joProduct -> inter_org_so_qty -= $soItem -> order_qty;
                    $joProduct -> save();
                }
            }
            //Delete all sub details data
            $soItem->jobWorkItems()->delete();
            $soItem->custom_bom_details()->delete();
            $soItem->teds()->delete();
            $soItem->item_deliveries()->delete();
            $soItem->attributes()->delete();
            $soItem->delete();
        }
        return SuccessErrorArrayResponse::successResponse("Items Deleted successfully");
    }
}

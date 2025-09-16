<?php
namespace App\Services\Sales\Order;

use App\Helpers\ItemHelper;
use App\Helpers\SaleModuleHelper;
use App\Helpers\SuccessErrorArrayResponse;
use App\Models\Attribute;
use App\Models\AttributeGroup;
use App\Models\ErpSaleOrder;
use App\Models\ErpSoItem;
use App\Models\ErpSoJobWorkItem;
use App\Models\ErpSoJobWorkItemAttribute;
use App\Models\JobOrder\JoBomMapping;
use App\Models\JobOrder\JoProduct;
use App\Models\PoItem;

class SoItemUpdate
{
    public function updateItem(ErpSoItem|null $oldSoItem, ErpSoItem $soItem, ErpSaleOrder $saleOrder, float $currentQty, array $request, int $currentItemIndex) : array
    {
        if (isset($oldSoItem)) {
            //Check if PWO qty is there
            if ($oldSoItem -> pwo_qty > $currentQty) {
                $pulledQty = $oldSoItem -> pwo_qty;
                return SuccessErrorArrayResponse::errorResponse("Production Work Order of qty - $pulledQty has been generated");
            }
            //Check if Pslip qty is there
            if ($oldSoItem -> pslip_qty > $currentQty) {
                $pulledQty = $oldSoItem -> pslip_qty;
                return SuccessErrorArrayResponse::errorResponse("Production Slip of qty - $pulledQty has been generated");
            }
            //Check if Pslip qty is there
            if ($oldSoItem -> plist_qty > $currentQty) {
                $pulledQty = $oldSoItem -> plist_qty;
                return SuccessErrorArrayResponse::errorResponse("Packing List of qty - $pulledQty has been generated");
            }
            //Check if Dnote Qty is there 
            if ($oldSoItem -> dnote_qty > $currentQty) {
                $pulledQty = $oldSoItem -> dnote_qty;
                return SuccessErrorArrayResponse::errorResponse("Delivery Note of qty - $pulledQty has been generated");
            }
            //Check if Invoice Qty is there 
            if ($oldSoItem -> invoice_qty > $currentQty) {
                $pulledQty = $oldSoItem -> invoice_qty;
                return SuccessErrorArrayResponse::errorResponse("Invoice of qty - $pulledQty has been generated");
            }
            //Check if Sales Return Qty is there
            if ($oldSoItem -> srn_qty > $currentQty) {
                $pulledQty = $oldSoItem -> srn_qty;
                return SuccessErrorArrayResponse::errorResponse("Sales Return of qty - $pulledQty has been generated");
            }
            //Check if Expense Advice Qty is there
            if ($oldSoItem -> expense_advise_qty > $currentQty) {
                $pulledQty = $oldSoItem -> expense_advise_qty;
                return SuccessErrorArrayResponse::errorResponse("Sales Return of qty - $pulledQty has been generated");
            }
            //Check if Picked Qty is there
            if ($oldSoItem -> picked_qty > $currentQty) {
                $pulledQty = $oldSoItem -> picked_qty;
                return SuccessErrorArrayResponse::errorResponse("Pick List of qty - $pulledQty has been generated");
            }
            //Check if Planned Qty is there
            if ($oldSoItem -> planned_qty > $currentQty) {
                $pulledQty = $oldSoItem -> planned_qty;
                return SuccessErrorArrayResponse::errorResponse("Trip Plan of Item - $pulledQty has been generated");
            }
        }
        //Quotation
        if ($request['quotation_item_ids'] && isset($request['quotation_item_ids'][$currentItemIndex])) {
            $qtItem = ErpSoItem::find($request['quotation_item_ids'][$currentItemIndex]);
            if (isset($qtItem)) {
                //Enough balance qty is available or not
                if (($qtItem -> quotation_balance_qty + (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) < $currentQty) {
                    return SuccessErrorArrayResponse::errorResponse('Not Enough Quotation Qty Available');
                }
                $qtItem -> quotation_order_qty = ($qtItem -> quotation_order_qty - (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) + $currentQty;
                $qtItem -> save();
                $soItem -> order_quotation_id = $qtItem -> header ?-> id;
                $soItem -> sq_item_id = $qtItem -> id;
                $soItem -> save();
            }
        }
        //Purchase Order
        if ($request['po_item_ids'] && isset($request['po_item_ids'][$currentItemIndex])) {
            $poItem = PoItem::find($request['po_item_ids'][$currentItemIndex]);
            if (isset($poItem)) {
                //Enough balance qty is available or not
                if (($qtItem -> inter_org_so_bal_qty + (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) < $currentQty) {
                    return SuccessErrorArrayResponse::errorResponse('Not Enough Purchase Order Qty Available');
                }
                $poItem -> inter_org_so_qty = ($poItem -> inter_org_so_qty - (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) + $currentQty;
                $poItem -> save();
                $soItem -> po_item_id = $poItem -> id;
                $soItem -> save();
            }
        }
        //Job Order
        if ($request['jo_product_ids'] && isset($request['jo_product_ids'][$currentItemIndex])) {
            $joProduct = JoProduct::find($request['jo_product_ids'][$currentItemIndex]);
            if (isset($joProduct)) {
                //Enough balance qty is available or not
                if (($qtItem -> inter_org_so_bal_qty + (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) < $currentQty) {
                    return SuccessErrorArrayResponse::errorResponse('Not Enough Job Order Qty Available');
                }
                $joProduct -> inter_org_so_qty = ($joProduct -> inter_org_so_qty - (isset($oldSoItem) ? $oldSoItem -> order_qty : 0)) + $currentQty;
                $joProduct -> save();
                $soItem -> jo_product_id = $joProduct -> id;
                $soItem -> save();
                //Save the item
                //Only Save in case of create
                if (!$request['sale_order_id'] && $saleOrder -> order_type === SaleModuleHelper::ORDER_TYPE_SUB_CONTRACTING) {
                    $joBomMapping = JoBomMapping::where('jo_product_id', $joProduct -> id) -> get();
                    foreach ($joBomMapping as $joBomMapping) {
                        //Update Or Create Mapping Data
                        $jobWorkItem = ErpSoJobWorkItem::updateOrCreate([
                            'sale_order_id' => $saleOrder -> id,
                            'so_item_id' => $soItem -> id,
                            'jo_id' => $joBomMapping -> jo_id,
                            'bom_detail_id' => $joBomMapping -> bom_detail_id,
                            'station_id' => $joBomMapping -> station_id,
                            'rm_type' => $joBomMapping -> rm_type,
                            'item_id' => $joBomMapping -> item_id,
                            'item_code' => $joBomMapping -> item_code,
                            'uom_id' => $joBomMapping -> uom_id,
                            'qty' => $joBomMapping -> qty,
                            'inventory_uom_id' => $joBomMapping ?-> item ?-> uom_id,
                            'inventory_uom_code' => $joBomMapping ?-> item ?-> uom ?-> name,
                            'inventory_uom_qty' => ItemHelper::convertToBaseUom($joBomMapping -> item_id, $joBomMapping -> uom_id, $joBomMapping -> qty)
                        ]);
                        //Update Or Create Attributes Data
                        foreach ($joBomMapping -> attributes as $joBomMappingAttribute) {
                            $attribute = AttributeGroup::find($joBomMappingAttribute['attribute_name']);
                            $attributeValue = Attribute::find($joBomMappingAttribute['attribute_value']);
                            ErpSoJobWorkItemAttribute::updateOrCreate([
                                'sale_order_id' => $saleOrder -> id,
                                'job_work_item_id' => $jobWorkItem -> id,
                                'item_id' => $jobWorkItem -> item_id,
                                'item_code' => $jobWorkItem -> item_code,
                                'item_attribute_id' => $joBomMappingAttribute['attribute_id'],
                                'attribute_name' => $attribute ?-> name,
                                'attr_name' => $attribute ?-> id,
                                'attribute_value' => $attributeValue ?-> value,
                                'attr_value' => $attributeValue ?-> id
                            ]);
                        }
                    }
                }
            }
        }
        return SuccessErrorArrayResponse::successResponse("Items Updated successfully");
    }
}

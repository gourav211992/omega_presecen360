<?php
namespace App\Services\GRN;
use DB;
use Illuminate\Http\Request;

use App\Models\MrnHeader;
use App\Models\MrnDetail;

use App\Models\Item;
use App\Models\PoItem;
use App\Models\VendorAsnItem;
use App\Models\GateEntryDetail;
use App\Models\JobOrder\JoProduct;

use App\Helpers\Helper;
use App\Helpers\ConstantHelper;
use App\Models\ErpInvoiceItem;
use App\Models\Scopes\DefaultGroupCompanyOrgScope;

class BackUpdateService
{
    // Validate the quantity of items in MRN against various conditions.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    public function updateQuantity($component, $order_qty)
    {
        if (isset($component['po_detail_id']) && $component['po_detail_id']) {
            if (isset($component['gate_entry_detail_id']) && $component['gate_entry_detail_id']) {
                $geDetail = GateEntryDetail::find($component['gate_entry_detail_id']);
                if ($geDetail) {
                    $orderQty = floatval($order_qty);
                    $componentQty = floatval($component['order_qty']);
                    $qtyDifference = $componentQty - $orderQty;
                    $geDetail->mrn_qty += (float) $qtyDifference;
                    $geDetail->save();

                    $asnDetail = VendorAsnItem::find($geDetail->vendor_asn_item_id);
                    if ($asnDetail) {
                        $asnDetail->ge_qty += (float) $qtyDifference;
                        $asnDetail->save();
                    }

                    $poDetail = PoItem::find($geDetail->purchase_order_item_id);
                    if ($poDetail) {
                        $poDetail->ge_qty += (float) $qtyDifference;
                        $poDetail->save();
                    }
                }
            }

            if (isset($component['vendor_asn_dtl_id']) && $component['vendor_asn_dtl_id']) {
                $asnDetail = VendorAsnItem::find($component['vendor_asn_dtl_id']);
                if ($asnDetail) {
                    $orderQty = floatval($order_qty);
                    $componentQty = floatval($component['order_qty']);
                    $qtyDifference = $componentQty - $orderQty;
                    $asnDetail->grn_qty += (float) $qtyDifference;
                    $asnDetail->save();
                }
            }

            $poItem = PoItem::find($component['po_detail_id']);
            if ($poItem) {
                $orderQty = floatval($order_qty);
                $componentQty = floatval($component['order_qty']);
                $qtyDifference = $componentQty - $orderQty;
                $poItem->grn_qty += (float) $qtyDifference;
                $poItem->save();
            }
        } else if (isset($component['jo_detail_id']) && $component['jo_detail_id']) {
            if (isset($component['gate_entry_detail_id']) && $component['gate_entry_detail_id']) {
                $geDetail = GateEntryDetail::find($component['gate_entry_detail_id']);
                if ($geDetail) {
                    $orderQty = floatval($order_qty);
                    $componentQty = floatval($component['order_qty']);
                    $qtyDifference = $componentQty - $orderQty;
                    $geDetail->mrn_qty += (float) $qtyDifference;
                    $geDetail->save();

                    $asnDetail = VendorAsnItem::find($geDetail->vendor_asn_item_id);
                    if ($asnDetail) {
                        $asnDetail->ge_qty += (float) $qtyDifference;
                        $asnDetail->save();
                    }

                    $joDetail = JoProduct::find($geDetail->purchase_order_item_id);
                    if ($joDetail) {
                        $joDetail->ge_qty += (float) $qtyDifference;
                        $joDetail->save();
                    }
                }
            }

            if (isset($component['vendor_asn_dtl_id']) && $component['vendor_asn_dtl_id']) {
                $asnDetail = VendorAsnItem::find($component['vendor_asn_dtl_id']);
                if ($asnDetail) {
                    $orderQty = floatval($order_qty);
                    $componentQty = floatval($component['order_qty']);
                    $qtyDifference = $componentQty - $orderQty;
                    $asnDetail->grn_qty += (float) $qtyDifference;
                    $asnDetail->save();
                }
            }

            $joItem = JoProduct::find($component['jo_detail_id']);
            if ($joItem) {
                $orderQty = floatval($order_qty);
                $componentQty = floatval($component['order_qty']);
                $qtyDifference = $componentQty - $orderQty;
                $joItem->grn_qty += (float) $qtyDifference;
                $joItem->save();
            }
        } else if (isset($component['invoice_itm_id']) && $component['invoice_itm_id']) {
            $dnoteItem = ErpInvoiceItem::where('id', @$component['invoice_itm_id'])
                ->withoutGlobalScope(DefaultGroupCompanyOrgScope::class)->first();
            $dnoteQty = floatval($dnoteItem->dnote_qty);
            if (isset($component['gate_entry_detail_id']) && $component['gate_entry_detail_id']) {
                $geDetail = GateEntryDetail::find($component['gate_entry_detail_id']);
                if ($geDetail) {
                    $componentQty = floatval($component['order_qty'] ?? $component['accepted_qty']);
                    $qtyDifference = $componentQty - $dnoteQty;
                    $geDetail->mrn_qty += (float) $qtyDifference;
                    $geDetail->save();

                    if (isset($dnoteItem?->saleOrderItem?->po_item_id) && $dnoteItem?->saleOrderItem?->po_item_id) {
                        $poDetail = PoItem::find($dnoteItem?->saleOrderItem?->po_item_id);
                        if ($poDetail) {
                            $poDetail->ge_qty += (float) $qtyDifference;
                            $poDetail->save();
                        }
                    }

                    if (isset($dnoteItem?->saleOrderItem?->jo_product_id) && $dnoteItem?->saleOrderItem?->jo_product_id) {
                        $joDetail = JoProduct::find($dnoteItem?->saleOrderItem?->jo_product_id);
                        if ($joDetail) {
                            $joDetail->ge_qty += (float) $qtyDifference;
                            $joDetail->save();
                        }
                    }
                }
            }

            if (isset($dnoteItem) && $dnoteItem) {
                $componentQty = floatval($component['order_qty'] ?? $component['accepted_qty']);
                $qtyDifference = $componentQty - $dnoteQty;
                if (floatval($qtyDifference) != 0) {
                    \DB::rollBack();
                    return self::errorResponse("Qty cannot be greater or less than Dnote Qunatity", [
                        "message" => 'Qty cannot be greater or less than Dnote Qunatity'
                    ]);
                }

                if ($qtyDifference) {
                    $dnoteItem->ge_qty += $qtyDifference;
                }
                if (isset($dnoteItem?->saleOrderItem?->po_item_id) && $dnoteItem?->saleOrderItem?->po_item_id) {
                    $poDetail = PoItem::find($dnoteItem?->saleOrderItem?->po_item_id);
                    if ($poDetail) {
                        $poDetail->ge_qty += (float) $qtyDifference;
                        $poDetail->save();
                    }
                }

                if (isset($dnoteItem?->saleOrderItem?->jo_product_id) && $dnoteItem?->saleOrderItem?->jo_product_id) {
                    $joDetail = JoProduct::find($dnoteItem?->saleOrderItem?->jo_product_id);
                    if ($joDetail) {
                        $joDetail->ge_qty += (float) $qtyDifference;
                        $joDetail->save();
                    }
                }
                $dnoteItem->save();
            }
        } else {
        }

        // === All Good ===
        return self::successResponse("Quantity Back Updated", [
            'order_qty' => $order_qty
        ]);
    }

    private static function errorResponse($message, $inputQty)
    {
        return [
            "code" => "500",
            "status" => "error",
            "order_qty" => $inputQty,
            "message" => $message,
        ];

    }

    private static function successResponse($response, $inputQty)
    {
        return [
            "code" => "200",
            "status" => "success",
            "order_qty" => $inputQty,
            "message" => $response,
        ];
    }
}

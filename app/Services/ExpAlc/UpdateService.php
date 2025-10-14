<?php
namespace App\Services\ExpAlc;

use DB;
use PDF;
use Auth;
use View;
use Session;
use stdClass;
use DateTime;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\ExpenseAllocation\Header;
use App\Models\ExpenseAllocation\PoDetail;
use App\Models\ExpenseAllocation\GrnDetail;
use App\Models\ExpenseAllocation\PoAttribute;
use App\Models\ExpenseAllocation\GrnAttribute;
use App\Models\ExpenseAllocation\Allocation;
use App\Models\ExpenseAllocation\Media;
use App\Models\ExpenseAllocation\DynamicField;

use App\Models\ExpenseAllocation\HeaderHistory;
use App\Models\ExpenseAllocation\PoDetailHistory;
use App\Models\ExpenseAllocation\GrnDetailHistory;
use App\Models\ExpenseAllocation\PoAttributeHistory;
use App\Models\ExpenseAllocation\GrnAttributeHistory;
use App\Models\ExpenseAllocation\AllocationHistory;
use App\Models\ExpenseAllocation\MediaHistory;
use App\Models\ExpenseAllocation\DynamicFieldHistory;

use App\Models\Item;
use App\Models\Unit;
use App\Models\PoItem;
use App\Models\MrnDetail;


use App\Helpers\Helper;
use App\Helpers\TaxHelper;
use App\Helpers\BookHelper;
use App\Helpers\NumberHelper;
use App\Helpers\ConstantHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\InventoryHelper;
use App\Helpers\DynamicFieldHelper;
use App\Helpers\FinancialPostingHelper;
use App\Helpers\ServiceParametersHelper;
use App\Models\AlternateUOM;

class UpdateService
{
    // Validate the quantity of items in MRN against various conditions.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    public function update(array $inputData, $expense, $user, $organization): array
    {
        // -------- helpers --------
        $toF = static function ($v, int $scale = 2): float {
            if ($v === null || $v === '')
                return 0.0;
            $n = (float) str_replace(',', '', $v);
            return round($n, $scale);
        };

        // allocations arrive as array of JSON strings OR arrays:
        //  {"id":"<po_detail_id>","amout":"6000.00"}
        $parseAllocations = static function ($allocArr) use ($toF): array {
            $out = [];
            if (!is_array($allocArr))
                return $out;

            foreach ($allocArr as $raw) {
                if (is_array($raw)) {
                    $out[] = [
                        'id' => (string) ($raw['id'] ?? ''),
                        'amout' => $toF($raw['amout'] ?? 0),
                    ];
                    continue;
                }
                $obj = json_decode((string) $raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($obj)) {
                    $out[] = [
                        'id' => (string) ($obj['id'] ?? ''),
                        'amout' => $toF($obj['amout'] ?? 0),
                    ];
                }
            }
            return $out;
        };

        // -------- shape & guards --------
        if (empty($inputData['components']) || !is_array($inputData['components'])) {
            return self::errorResponse("Please add atleast one row in component table.");
        }

        $poLines = $inputData['components']['po'] ?? [];
        $grnLines = $inputData['components']['grn'] ?? [];

        if (empty($poLines) && empty($grnLines)) {
            return self::errorResponse("No PO/GRN rows found in request.");
        }

        // -------- preflight caches --------
        $cacheItems = []; // item_id => Item
        $cachePoItems = []; // original PO rows (optional)
        $cacheMrn = []; // original GRN rows (optional)

        $totPo = 0.0;
        $totGrn = 0.0;
        $totAlloc = 0.0;
        $totLanded = 0.0;

        // ---- Gather totals and validate foreigns (lightweight) ----
        foreach ($poLines as $idx => $row) {
            $rowNo = (string) $idx;
            $itemId = $row['item_id'] ?? null;
            if (!$itemId)
                return self::errorResponse("Item id missing for PO row {$rowNo}.");

            if (!isset($cacheItems[$itemId])) {
                $it = Item::find($itemId);
                if (!$it)
                    return self::errorResponse("Item not found for PO row {$rowNo}.");
                $cacheItems[$itemId] = $it;
            }

            if (!empty($row['po_detail_id'])) {
                $poDetId = (int) $row['po_detail_id'];
                if (!isset($cachePoItems[$poDetId])) {
                    $poDet = PoItem::find($poDetId);
                    if ($poDet)
                        $cachePoItems[$poDetId] = $poDet; // optional
                }
            }
            $totPo += $toF($row['po_value'] ?? 0);
        }

        foreach ($grnLines as $idx => $row) {
            $rowNo = (string) $idx;
            $itemId = $row['item_id'] ?? null;
            if (!$itemId)
                return self::errorResponse("Item id missing for GRN row {$rowNo}.");

            if (!isset($cacheItems[$itemId])) {
                $it = Item::find($itemId);
                if (!$it)
                    return self::errorResponse("Item not found for GRN row {$rowNo}.");
                $cacheItems[$itemId] = $it;
            }

            if (!empty($row['mrn_detail_id']) && class_exists(MrnDetail::class)) {
                $mrnDetId = (int) $row['mrn_detail_id'];
                if (!isset($cacheMrn[$mrnDetId])) {
                    $mrn = MrnDetail::find($mrnDetId);
                    if ($mrn)
                        $cacheMrn[$mrnDetId] = $mrn; // optional
                }
            }

            $grnVal = $toF($row['grn_value'] ?? 0);
            $alloc = $toF($row['allocation_cost'] ?? 0);
            $landed = $toF($row['landed_cost'] ?? ($grnVal + $alloc));

            $totGrn += $grnVal;
            $totAlloc += $alloc;
            $totLanded += $landed;
        }

        // -------- persist: upsert PO details, GRN details, attributes & mappings --------
        $keptPoDetailIds = []; // ids we keep/update in this request
        $keptGrnDetailIds = [];

        try {
            // --- PO details (exp_po_details) ---
            foreach ($poLines as $idx => $row) {
                $item = $cacheItems[$row['item_id']];

                // upsert by incoming exp_po_details.id in "detail_id"
                /** @var PoDetail $poDetail */
                if (!empty($row['detail_id'])) {
                    $poDetail = PoDetail::where('header_id', $expense->id)
                        ->where('id', (int) $row['detail_id'])
                        ->first();
                    if (!$poDetail)
                        $poDetail = new PoDetail;
                } else {
                    $poDetail = new PoDetail;
                }

                $poDetail->header_id = $expense->id;
                $poDetail->po_header_id = $row['po_header_id'] ?? ($row['po_hidden_ids'] ?? null);
                $poDetail->po_detail_id = $row['po_detail_id'] ?? null;
                $poDetail->vendor_id = $row['vendor_id'] ?? null;
                $poDetail->vendor_name = $row['vendor_name'] ?? null;

                $poDetail->item_id = $row['item_id'] ?? $item->id;
                $poDetail->item_code = $row['item_code'] ?? ($item->code ?? null);
                $poDetail->item_name = $row['item_name'] ?? ($item->item_name ?? null);
                $poDetail->hsn_id = $row['hsn_id'] ?? null;
                $poDetail->hsn_code = $row['hsn_code'] ?? null;

                $poDetail->uom_id = $row['uom_id'] ?? null;
                $poDetail->uom_code = $row['uom_code'] ?? null;

                $poDetail->currency_id = $row['currency_id'] ?? null;
                $poDetail->currency_code = $row['currency_code'] ?? null;
                $poDetail->org_currency_id = $row['org_currency_id'] ?? null;
                $poDetail->org_currency_code = $row['org_currency_code'] ?? null;

                $poDetail->po_qty = $toF($row['po_qty'] ?? 0, 6);
                $poDetail->receipt_qty = $toF($row['po_qty'] ?? 0, 6);
                $poDetail->rate = $toF($row['po_rate'] ?? 0, 6);
                $poDetail->po_value = $toF($row['old_amt_po'] ?? 0);
                $poDetail->value = $toF($row['po_value'] ?? 0);

                // inventory uom
                $inventoryUom = Unit::find($item->uom_id ?? null);
                $poDetail->receipt_inv_uom_id = $inventoryUom->id ?? null;
                $poDetail->receipt_inv_uom_code = $inventoryUom->name ?? null;
                if (($row['uom_id'] ?? null) == ($item->uom_id ?? null)) {
                    $poDetail->receipt_inv_uom_qty = (float) ($row['po_qty'] ?? 0);
                } else {
                    $alUom = AlternateUOM::where('item_id', $row['item_id'] ?? null)
                        ->where('uom_id', $row['uom_id'] ?? null)->first();
                    $poDetail->receipt_inv_uom_qty = $alUom
                        ? ((float) ($row['po_qty'] ?? 0)) * (float) $alUom->conversion_to_inventory
                        : 0.0;
                }

                // allocation type from dist_type (qty|value|weight|volume)
                $poDetail->allocation_type = strtolower($row['dist_type'] ?? '');

                $poDetail->save();
                $poItem = PoItem::find($poDetail->po_detail_id ?? null);
                if ($poItem) {
                    $poItem->exp_allocation_id = $poDetail->id;
                    $poItem->save();
                }
                $keptPoDetailIds[] = $poDetail->id;

                // refresh PO attributes (simple approach)
                if (isset($row['attr_group_id']) && is_array($row['attr_group_id'])) {
                    PoAttribute::where('header_id', $expense->id)
                        ->where('detail_id', $poDetail->id)->delete();
                    foreach ($row['attr_group_id'] as $groupId => $attr) {
                        self::saveAttribute($expense, 'po', $poDetail, $groupId, $attr);
                    }
                }
            }

            // --- GRN details (exp_grn_details) ---
            foreach ($grnLines as $idx => $row) {
                $item = $cacheItems[$row['item_id']];

                /** @var GrnDetail $grnDetail */
                if (!empty($row['detail_id'])) {
                    $grnDetail = GrnDetail::where('header_id', $expense->id)
                        ->where('id', (int) $row['detail_id'])
                        ->first();
                    if (!$grnDetail)
                        $grnDetail = new GrnDetail;
                } else {
                    $grnDetail = new GrnDetail;
                }

                $grnDetail->header_id = $expense->id;
                $grnDetail->grn_header_id = $row['grn_header_id'] ?? ($row['mrn_hidden_ids'] ?? null);
                $grnDetail->grn_detail_id = $row['grn_detail_id'] ?? null;

                $grnDetail->vendor_id = $row['vendor_id'] ?? null;
                $grnDetail->vendor_name = $row['vendor_name'] ?? null;

                $grnDetail->item_id = $row['item_id'] ?? $item->id;
                $grnDetail->item_code = $row['item_code'] ?? ($item->code ?? null);
                $grnDetail->item_name = $row['item_name'] ?? ($item->item_name ?? null);
                $grnDetail->hsn_id = $row['hsn_id'] ?? null;
                $grnDetail->hsn_code = $row['hsn_code'] ?? null;

                $grnDetail->uom_id = $row['uom_id'] ?? null;
                $grnDetail->uom_code = $row['uom_code'] ?? null;

                $grnDetail->currency_id = $row['currency_id'] ?? null;
                $grnDetail->currency_code = $row['currency_code'] ?? null;
                $grnDetail->org_currency_id = $row['org_currency_id'] ?? null;
                $grnDetail->org_currency_code = $row['org_currency_code'] ?? null;

                $grnDetail->grn_qty = $toF($row['grn_qty'] ?? 0, 6);
                $grnDetail->receipt_qty = $toF($row['grn_qty'] ?? 0, 6);
                $grnDetail->grn_value = $toF($row['old_grn_value'] ?? 0);
                $grnDetail->value = $toF($row['grn_value'] ?? 0);
                $grnDetail->weight = $toF($row['grn_weight'] ?? 0, 6);
                $grnDetail->volume = $toF($row['grn_volume'] ?? 0, 6);

                // inventory uom
                $inventoryUom = Unit::find($item->uom_id ?? null);
                $grnDetail->receipt_inv_uom_id = $inventoryUom->id ?? null;
                $grnDetail->receipt_inv_uom_code = $inventoryUom->name ?? null;
                if (($row['uom_id'] ?? null) == ($item->uom_id ?? null)) {
                    $grnDetail->receipt_inv_uom_qty = (float) ($row['grn_qty'] ?? 0);
                } else {
                    $alUom = AlternateUOM::where('item_id', $row['item_id'] ?? null)
                        ->where('uom_id', $row['uom_id'] ?? null)->first();
                    $grnDetail->receipt_inv_uom_qty = $alUom
                        ? ((float) ($row['grn_qty'] ?? 0)) * (float) $alUom->conversion_to_inventory
                        : 0.0;
                }

                $alloc = $toF($row['allocation_cost'] ?? 0);
                $landed = $toF($row['landed_cost'] ?? ($grnDetail->value + $alloc)); // <-- fixed: use value
                $grnDetail->allocated_cost = $alloc;
                $grnDetail->landed_cost = $landed;

                $grnDetail->save();
                $keptGrnDetailIds[] = $grnDetail->id;

                // refresh GRN attributes (simple approach)
                if (isset($row['attr_group_id']) && is_array($row['attr_group_id'])) {
                    GrnAttribute::where('header_id', $expense->id)
                        ->where('detail_id', $grnDetail->id)->delete();
                    foreach ($row['attr_group_id'] as $groupId => $attr) {
                        self::saveAttribute($expense, 'grn', $grnDetail, $groupId, $attr);
                    }
                }

                // --- MAPPING: rebuild for this GRN detail from posted allocations ---
                Allocation::where('header_id', $expense->id)
                    ->where('grn_detail_id', $grnDetail->id)
                    ->delete();

                $allocPairs = $parseAllocations($row['allocations'] ?? []);
                if (!empty($allocPairs)) {
                    $sumAlloc = 0.0;
                    foreach ($allocPairs as $p)
                        $sumAlloc += $toF($p['amout'] ?? 0);
                    $rowAlloc = $toF($row['allocation_cost'] ?? 0);
                    $drift = round($rowAlloc - $sumAlloc, 2);

                    $count = count($allocPairs);
                    foreach ($allocPairs as $i => $p) {
                        $poDetailId = (int) ($p['id'] ?? 0);
                        if ($poDetailId <= 0)
                            continue;

                        $amt = $toF($p['amout'] ?? 0);
                        if ($i === $count - 1 && abs($drift) > 0) {
                            $amt = round($amt + $drift, 2); // push drift to last
                        }
                        if ($amt == 0.0)
                            continue;

                        $map = new Allocation; // pivot
                        $map->header_id = $expense->id;
                        $map->po_detail_id = $poDetailId;      // exp_po_details.id
                        $map->grn_detail_id = $grnDetail->id;   // exp_grn_details.id
                        $map->amount = $amt;
                        $map->save();
                    }
                }
            }

            // (Optional) clean up removed details (if you want hard sync)
            // PoDetail::where('header_id', $expense->id)->whereNotIn('id', $keptPoDetailIds)->delete();
            // GrnDetail::where('header_id', $expense->id)->whereNotIn('id', $keptGrnDetailIds)->delete();

            // -------- header totals --------
            $expense->total_po_value = round($totPo, 2);
            $expense->total_grn_value = round($totGrn, 2);
            $expense->total_allocated_value = round($totAlloc, 2);
            $expense->total_landed_cost_value = round($totLanded, 2);
            $expense->document_status = $inputData['document_status'] ?? $expense->document_status;
            $expense->save();

        } catch (\Throwable $e) {
            report($e);
            return self::errorResponse($e->getMessage());
        }

        return self::successResponse("Expense allocation updated.", [
            'header_id' => $expense->id,
            'total_po_value' => $expense->total_po_value,
            'total_grn_value' => $expense->total_grn_value,
            'total_allocated_value' => $expense->total_allocated_value,
            'total_landed_cost_value' => $expense->total_landed_cost_value,
            'kept_po_detail_ids' => $keptPoDetailIds,
            'kept_grn_detail_ids' => $keptGrnDetailIds,
        ]);
    }


    // Save Attributes (unchanged except: pass correct detail model)
    private static function saveAttribute($expense, $type, $detail, $groupId, $attr)
    {
        if ($type === 'po') {
            $attribute = new PoAttribute;
        } elseif ($type === 'grn') {
            $attribute = new GrnAttribute;
        } else {
            $attribute = new PoAttribute;
        }
        $attribute->header_id = $expense->id;
        $attribute->detail_id = $detail->id;
        $attribute->item_id = $detail->item_id;
        $attribute->item_attribute_id = $groupId;
        $attribute->item_code = $detail->item_code;
        $attribute->attr_name = $attr['attr_name'] ?? null;
        $attribute->attr_value = $attr['attr_id'] ?? null;
        $attribute->save();
        return $attribute;
    }

    private static function errorResponse($message)
    {
        return ["code" => "500", "status" => "error", "message" => $message];
    }
    private static function successResponse($response, $data)
    {
        return ["code" => "200", "status" => "success", "message" => $response, "data" => $data];
    }

}

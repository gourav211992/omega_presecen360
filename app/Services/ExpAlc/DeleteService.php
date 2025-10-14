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

class DeleteService
{
    // Validate the quantity of items in MRN against various conditions.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    public function store(array $inputData, $expense, $user, $organization): array
    {
        // -------- helpers --------
        $toF = static function ($v, int $scale = 2): float {
            if ($v === null || $v === '')
                return 0.0;
            $n = (float) str_replace(',', '', $v);
            return round($n, $scale);
        };
        $parseAllocations = static function ($allocArr) use ($toF): array {
            // allocations arrive as array of JSON strings: {"id":"","amout":"6000.00"}
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

        // -------- preflight: validate & pre-compute totals --------
        $cacheItems = []; // item_id => Item
        $cachePoItems = []; // po_detail_id => PoItem (optional)
        $cacheMrn = []; // mrn_detail_id => Grn/Mrn (optional)

        $totPo = 0.0;
        $totGrn = 0.0;
        $totAlloc = 0.0;
        $totLanded = 0.0;

        // PO rows
        foreach ($poLines as $idx => $row) {
            $rowNo = (string) $idx;
            $itemId = $row['item_id'] ?? null;
            if (!$itemId)
                return self::errorResponse("Item id missing for PO row {$rowNo}.");
            if (!isset($cacheItems[$itemId])) {
                $item = Item::find($itemId);
                if (!$item)
                    return self::errorResponse("Item not found for PO row {$rowNo}.");
                $cacheItems[$itemId] = $item;
            }
            if (!empty($row['po_detail_id'])) {
                $poDetId = (int) $row['po_detail_id'];
                if (!isset($cachePoItems[$poDetId])) {
                    $poDet = PoItem::find($poDetId);
                    if (!$poDet)
                        return self::errorResponse("PO detail not found for PO row {$rowNo}.");
                    $cachePoItems[$poDetId] = $poDet;
                }
            }
            $totPo += $toF($row['po_value'] ?? 0);
        }

        // GRN rows
        foreach ($grnLines as $idx => $row) {
            $rowNo = (string) $idx;
            $itemId = $row['item_id'] ?? null;
            if (!$itemId)
                return self::errorResponse("Item id missing for GRN row {$rowNo}.");
            if (!isset($cacheItems[$itemId])) {
                $item = Item::find($itemId);
                if (!$item)
                    return self::errorResponse("Item not found for GRN row {$rowNo}.");
                $cacheItems[$itemId] = $item;
            }
            if (!empty($row['mrn_detail_id']) && class_exists(GrnDetail::class)) {
                $mrnDetId = (int) $row['mrn_detail_id'];
                if (!isset($cacheMrn[$mrnDetId])) {
                    $mrn = MrnDetail::find($mrnDetId);
                    if (!$mrn)
                        return self::errorResponse("GRN detail not found for GRN row {$rowNo}.");
                    $cacheMrn[$mrnDetId] = $mrn;
                }
            }

            $grnVal = $toF($row['grn_value'] ?? 0);
            $alloc = $toF($row['allocation_cost'] ?? 0);
            $landed = $toF($row['landed_cost'] ?? ($grnVal + $alloc));

            $totGrn += $grnVal;
            $totAlloc += $alloc;
            $totLanded += $landed;
        }

        // -------- persist: PO details, GRN details, attributes & MAPPINGS --------
        $createdPoDetailIds = []; // in the same visual order as $poLines
        $createdGrnDetailIds = []; // map GRN idx => id
        $createdPoAttrIds = [];
        $createdGrnAttrIds = [];
        $createdMapIds = [];

        try {
            // --- PO details (exp_po_details) ---
            foreach ($poLines as $idx => $row) {
                $item = $cacheItems[$row['item_id']];
                $uom = Unit::find($item->uom_id ?? null);

                /** @var PoDetail $poDetail */
                $poDetail = new PoDetail; // <-- your model for exp_po_details
                $poDetail->header_id = $expense->id;

                $poDetail->po_header_id = $row['po_hidden_ids'] ?? ($row['purchase_order_id'] ?? null);
                $poDetail->po_detail_id = $row['po_detail_id'] ?? null;
                $poDetail->vendor_id = $row['vendor_id'] ?? null;
                $poDetail->vendor_name = $row['vendor_name'] ?? null;

                $poDetail->item_id = $row['item_id'] ?? ($item->id ?? null);
                $poDetail->item_code = $row['item_code'] ?? ($item->code ?? null);
                $poDetail->item_name = $row['item_name'] ?? ($item->item_name ?? null); // fixed (was wrongly item_code)
                $poDetail->hsn_id = $row['hsn_id'] ?? null;
                $poDetail->hsn_code = $row['hsn_code'] ?? null;

                $poDetail->uom_id = $row['uom_id'] ?? null;
                $poDetail->uom_code = $row['uom_code'] ?? null;

                $poDetail->po_qty = $toF($row['po_qty'] ?? 0, 6);
                $poDetail->receipt_qty = $toF($row['po_qty'] ?? 0, 6);
                $poDetail->rate = $toF($row['po_rate'] ?? 0, 6);
                $poDetail->value = $toF($row['po_value'] ?? 0);

                $inventory_uom_id = null;
                $inventory_uom_code = null;
                $inventory_uom_qty = 0.00;
                $inventoryUom = Unit::find($item->uom_id ?? null);
                $inventory_uom_id = $inventoryUom->id ?? null;
                $inventory_uom_code = $inventoryUom->name ?? null;
                if ($row['uom_id'] == $item->uom_id) {
                    $inventory_uom_qty = floatval($row['po_qty']) ?? 0.00;
                } else {
                    $alUom = AlternateUOM::where('item_id', $row['item_id'])->where('uom_id', $row['uom_id'])->first();
                    if ($alUom) {
                        $inventory_uom_qty = floatval($row['po_qty']) * $alUom->conversion_to_inventory;
                    }
                }

                $poDetail->receipt_inv_uom_id = $inventory_uom_id;
                $poDetail->receipt_inv_uom_code = $inventory_uom_code;
                $poDetail->receipt_inv_uom_qty = $inventory_uom_qty;
                $poDetail->allocation_type = strtolower($row['dist_type'] ?? ''); // qty|value|weight|volume

                $poDetail->save();

                $createdPoDetailIds[] = $poDetail->id;

                // PO attributes
                if (!empty($row['attr_group_id']) && is_array($row['attr_group_id'])) {
                    foreach ($row['attr_group_id'] as $groupId => $attr) {
                        /** @var PoAttribute $poAttr */
                        $poAttr = self::saveAttribute($expense, 'po', $poDetail, $groupId, $attr);
                        $createdPoAttrIds[] = $poAttr->id;
                    }
                }
            }

            // --- GRN details (exp_grn_details) ---
            // keep a sequential list of PO detail ids to map allocations by index
            $poIdxToId = array_values($createdPoDetailIds);

            foreach ($grnLines as $idx => $row) {
                $item = $cacheItems[$row['item_id']];
                $uom = Unit::find($item->uom_id ?? null);

                /** @var GrnDetail $grnDetail */
                $grnDetail = new GrnDetail; // <-- your model for exp_grn_details
                $grnDetail->header_id = $expense->id;

                $grnDetail->grn_header_id = $row['mrn_hidden_ids'] ?? ($row['grn_header_id'] ?? null);
                $grnDetail->grn_detail_id = $row['grn_detail_id'] ?? null;

                $grnDetail->vendor_id = $row['vendor_id'] ?? null;
                $grnDetail->vendor_name = $row['vendor_name'] ?? null;

                $grnDetail->item_id = $row['item_id'] ?? ($item->id ?? null);
                $grnDetail->item_code = $row['item_code'] ?? ($item->code ?? null);
                $grnDetail->item_name = $row['item_name'] ?? ($item->item_name ?? null);
                $grnDetail->hsn_id = $row['hsn_id'] ?? null;
                $grnDetail->hsn_code = $row['hsn_code'] ?? null;

                $grnDetail->uom_id = $row['uom_id'] ?? null;
                $grnDetail->uom_code = $row['uom_code'] ?? null;

                $grnDetail->grn_qty = $toF($row['grn_qty'] ?? 0, 6);
                $grnDetail->receipt_qty = $toF($row['grn_qty'] ?? 0, 6);
                $grnDetail->value = $toF($row['grn_value'] ?? 0);
                $grnDetail->weight = $toF($row['grn_weight'] ?? 0, 6);
                $grnDetail->volume = $toF($row['grn_volume'] ?? 0, 6);

                $inventory_uom_id = null;
                $inventory_uom_code = null;
                $inventory_uom_qty = 0.00;
                $inventoryUom = Unit::find($item->uom_id ?? null);
                $inventory_uom_id = $inventoryUom->id ?? null;
                $inventory_uom_code = $inventoryUom->name ?? null;
                if ($row['uom_id'] == $item->uom_id) {
                    $inventory_uom_qty = floatval($row['grn_qty']) ?? 0.00;
                } else {
                    $alUom = AlternateUOM::where('item_id', $row['item_id'])->where('uom_id', $row['uom_id'])->first();
                    if ($alUom) {
                        $inventory_uom_qty = floatval($row['grn_qty']) * $alUom->conversion_to_inventory;
                    }
                }
                $grnDetail->receipt_inv_uom_id = $inventory_uom_id;
                $grnDetail->receipt_inv_uom_code = $inventory_uom_code;
                $grnDetail->receipt_inv_uom_qty = $inventory_uom_qty;

                $alloc = $toF($row['allocation_cost'] ?? 0);
                $landed = $toF($row['landed_cost'] ?? ($grnDetail->grn_value + $alloc));
                $grnDetail->allocated_cost = $alloc;
                $grnDetail->landed_cost = $landed;

                $grnDetail->save();
                $createdGrnDetailIds[$idx] = $grnDetail->id;

                // GRN attributes
                if (!empty($row['attr_group_id']) && is_array($row['attr_group_id'])) {
                    foreach ($row['attr_group_id'] as $groupId => $attr) {
                        /** @var GrnAttribute $grnAttr */
                        $grnAttr = self::saveAttribute($expense, 'grn', $grnDetail, $groupId, $attr); // fixed: pass $grnDetail
                        $createdGrnAttrIds[] = $grnAttr->id;
                    }
                }

                // --- MAPPING: po_detail_id ↔ grn_detail_id with amount ---
                // allocations come as array of json strings
                $allocPairs = $parseAllocations($row['allocations'] ?? []);
                if (!empty($allocPairs) && !empty($poIdxToId)) {
                    // drift correction vs allocation_cost on grn row
                    $sumAlloc = 0.0;
                    foreach ($allocPairs as $p)
                        $sumAlloc += $toF($p['amout'] ?? 0);
                    $rowAlloc = $toF($row['allocation_cost'] ?? 0);
                    $drift = round($rowAlloc - $sumAlloc, 2);

                    $nPO = count($poIdxToId);
                    $nAL = count($allocPairs);
                    $n = max($nPO, $nAL);

                    for ($i = 0; $i < $n; $i++) {
                        $poDetailId = $poIdxToId[min($i, $nPO - 1)] ?? null; // clamp to last if more allocations than PO rows
                        if (!$poDetailId)
                            continue;

                        $amt = $toF($allocPairs[min($i, $nAL - 1)]['amout'] ?? 0);

                        // push drift into last link to keep exact equality with row allocation_cost
                        if ($i === $n - 1 && abs($drift) > 0) {
                            $amt = round($amt + $drift, 2);
                        }

                        if ($amt == 0.0)
                            continue;

                        /** @var Allocation $map */
                        $map = new Allocation; // <-- REPLACE with your pivot model/table
                        $map->header_id = $expense->id;
                        $map->po_detail_id = $poDetailId;          // from exp_po_details
                        $map->grn_detail_id = $grnDetail->id;       // from exp_grn_details
                        $map->amount = $amt;
                        $map->save();

                        $createdMapIds[] = $map->id;
                    }
                }
            }

            // -------- header totals --------
            $expense->total_po_value = round($totPo, 2);
            $expense->total_grn_value = round($totGrn, 2);
            $expense->total_allocated_value = round($totAlloc, 2);
            $expense->total_landed_cost_value = round($totLanded, 2);
            $expense->save();

        } catch (\Throwable $e) {
            report($e);
            return self::errorResponse($e->getMessage());
        }

        // === OK ===
        return self::successResponse("Expense allocation saved.", [
            'header_id' => $expense->id,
            'total_po_value' => $expense->total_po_value,
            'total_grn_value' => $expense->total_grn_value,
            'total_allocated_value' => $expense->total_allocated_value,
            'total_landed_cost_value' => $expense->total_landed_cost_value,
            'po_detail_ids' => $createdPoDetailIds,
            'grn_detail_ids' => array_values($createdGrnDetailIds),
            'map_ids' => $createdMapIds,
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

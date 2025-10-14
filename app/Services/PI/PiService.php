<?php

namespace App\Services\PI;

use App\Models\Bom;
use App\Models\Item;
use App\Models\BomDetail;
use App\Helpers\ItemHelper;
use App\Models\PiSoMapping;
use App\Models\ErpSoItemBom;
use App\Helpers\ConstantHelper;

class PiService
{

    public function syncPiSoMapping($soId, $soItemId, $itemId, $attr, $soQty, $createdBy, $soItemOrderQty)
    {
        $item = Item::find($itemId);
        // $so   = ErpSaleOrder::find($soId);
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

            $uomId = $bomDetail->uom_id;
            if ($altUomId =  ItemHelper::getItemUom($bomDetail->item_id, strtolower(ConstantHelper::DEFAULT_PURCHASE))) {
                $uomId = $altUomId;
                $qty =  ItemHelper::convertToAltUom($bomDetail->item_id, $uomId, $bomDetail->qty);
            }

            $requiredQty = floatval($soQty) * floatval($qty);
            if ($bufferPerc > 0) {
                $requiredQty += $requiredQty * $bufferPerc / 100;
            }

            // $requiredQty = ceil($requiredQty);

            if (!in_array($checkBomExist['sub_type'], ['Expense'])) {
                $mappingData = [
                    'so_id'        => $soId,
                    'so_item_id'   => $soItemId,
                    'item_id'      => $bomDetail->item_id,
                    'uom_id'       => $uomId,
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
                    ['item_id', $mappingData['item_id']],
                    ['bom_detail_id', $checkBomExist['bom_id']]
                ])
                    ->whereJsonContains('attributes', $attributes)
                    ->first() ?? new PiSoMapping();

                $mapping->fill($mappingData);
                $mapping->save();
            }
        }

        return ['status' => 200, 'message' => 'Saved!'];
    }

    /**
     * Iterative expansion and upsert of PiSoMapping for a top-level SO item.
     * Uses a queue to avoid recursion, normalizes attributes, uses updateOrCreate.
     */
    public function expandAndUpsertMappingsIterative($soId, $soItemId, $itemId, array $attributes = [], $qty, $createdBy, $soItemOrderQty)
    {
        $normalizedAttrs = $this->normalizeAttributes($attributes);

        $queue = [];
        $queue[] = [
            'item_id' => $itemId,
            'attributes' => $normalizedAttrs,
            'qty' => $qty,
            'bom_parent_id' => null,
        ];

        $bomCheckCache = [];
        try {
            while (!empty($queue)) {
                $node = array_shift($queue);
                $currentItemId = $node['item_id'];
                $nodeAttrs = $node['attributes'];
                $nodeQty = floatval($node['qty']);
                $attrsJson = json_encode($nodeAttrs, JSON_UNESCAPED_UNICODE);

                $bomCacheKey = $currentItemId . '|' . $attrsJson;
                if (!array_key_exists($bomCacheKey, $bomCheckCache)) {
                    $bomCheckCache[$bomCacheKey] = ItemHelper::checkItemBomExists($currentItemId, $nodeAttrs);
                }
                $bomCheck = $bomCheckCache[$bomCacheKey];

                if (empty($bomCheck['bom_id'])) {
                    $mappingData = [
                        'so_id' => $soId,
                        'so_item_id' => $soItemId,
                        'item_id' => $currentItemId,
                        'created_by' => $createdBy,
                        'bom_id' => null,
                        'bom_detail_id' => null,
                        'vendor_id' => null,
                        'item_code' => null,
                        'order_qty' => $soItemOrderQty,
                        'bom_qty' => 0,
                        'qty' => $nodeQty,
                        'attributes' => $nodeAttrs,
                        'child_bom_id' => null
                    ];
                    PiSoMapping::updateOrCreate(
                        [
                            'so_id' => $soId,
                            'so_item_id' => $soItemId,
                            'item_id' => $currentItemId,
                            'bom_detail_id' => null,
                            'attributes' => $attrsJson,
                        ],
                        $this->prepareMappingSavePayload($mappingData)
                    );

                    continue;
                }

                $bom = Bom::find($bomCheck['bom_id']);
                if (!$bom) {
                    return ['status' => 422, 'message' => "BOM not found for item {$currentItemId}"];
                }

                $bufferPerc = ItemHelper::getBomSafetyBufferPerc($bom->id);

                $bomDetails = $this->getBomDetailsForSo($bom->id, $soId, $soItemId);

                foreach ($bomDetails as $bomDetail) {
                    if ($bomDetail instanceof BomDetail) {
                        $detailAttrs = $bomDetail->attributes->map(fn($a) => [
                            'attribute_id' => (int)$a->item_attribute_id,
                            'attribute_value' => (int)$a->attribute_value,
                        ])->toArray();

                        $bomDetailId = $bomDetail->id;
                        $vendorId = $bomDetail->vendor_id ?? null;
                        $item_code = $bomDetail->item_code ?? null;
                        $bomDetailItemId = $bomDetail->item_id;
                        $bomDetailQty = floatval($bomDetail->qty);
                    } else {
                        $detailAttrs = collect($bomDetail->item_attributes ?? [])->map(fn($a) => [
                            'attribute_id' => (int)($a['attribute_id'] ?? 0),
                            'attribute_value' => (int)($a['attribute_value_id'] ?? 0),
                        ])->filter(fn($a) => $a['attribute_id'] > 0 && $a['attribute_value'] > 0)->values()->all();

                        $bomDetailId = $bomDetail->bom_detail_id ?? null;
                        $vendorId = $bomDetail->bomDetail->vendor_id ?? null;
                        $item_code = $bomDetail->item_code ?? null;
                        $bomDetailItemId = $bomDetail->item_id;
                        $bomDetailQty = floatval($bomDetail->qty);
                    }

                    $detailAttrsNormalized = $this->normalizeAttributes($detailAttrs);

                    $childBomCacheKey = $bomDetailItemId . '|' . json_encode($detailAttrsNormalized, JSON_UNESCAPED_UNICODE);
                    if (!array_key_exists($childBomCacheKey, $bomCheckCache)) {
                        $bomCheckCache[$childBomCacheKey] = ItemHelper::checkItemBomExists($bomDetailItemId, $detailAttrsNormalized);
                    }
                    $childBomCheck = $bomCheckCache[$childBomCacheKey];

                    if (in_array($childBomCheck['sub_type'] ?? '', ['Finished Goods', 'WIP/Semi Finished']) && empty($childBomCheck['bom_id'])) {
                        $name = optional($bomDetail->item)->item_name ?? "Item #{$bomDetailItemId}";
                        $parentName = optional(Item::find($currentItemId))->item_name ?? 'Parent';
                        $message = "Child Bom doesn't exist for $name used under $parentName";

                        return ['status' => 422, 'message' => $message];
                    }

                    $uomId = $bomDetail->uom_id;
                    if ($altUomId =  ItemHelper::getItemUom($bomDetail->item_id, strtolower(ConstantHelper::DEFAULT_PURCHASE))) {
                        $uomId = $altUomId;
                        $bomDetailQty =  ItemHelper::convertToAltUom($bomDetail->item_id, $uomId, $bomDetail->qty);
                    }

                    $requiredQty = floatval($nodeQty) * floatval($bomDetailQty);
                    if ($bufferPerc > 0) {
                        $requiredQty += ($requiredQty * $bufferPerc / 100);
                    }

                    if (!in_array($childBomCheck['sub_type'] ?? '', ['Expense'])) {
                        $mappingData = [
                            'so_id' => $soId,
                            'so_item_id' => $soItemId,
                            'item_id' => $bomDetailItemId,
                            'uom_id' => $uomId,
                            'created_by' => $createdBy,
                            'bom_id' => $bom->id,
                            'bom_detail_id' => $bomDetailId,
                            'vendor_id' => $vendorId,
                            'item_code' => $item_code,
                            'order_qty' => $soItemOrderQty,
                            'bom_qty' => $bomDetailQty,
                            'qty' => $requiredQty,
                            'attributes' => $detailAttrsNormalized,
                            'child_bom_id' => $childBomCheck['bom_id'] ?? null,
                        ];

                        PiSoMapping::updateOrCreate(
                            [
                                'so_id' => $soId,
                                'so_item_id' => $soItemId,
                                'item_id' => $bomDetailItemId,
                                'bom_detail_id' => $bomDetailId,
                                'attributes' => json_encode($detailAttrsNormalized, JSON_UNESCAPED_UNICODE),
                            ],
                            $this->prepareMappingSavePayload($mappingData)
                        );

                        if (!empty($childBomCheck['bom_id'])) {
                            $queue[] = [
                                'item_id' => $bomDetailItemId,
                                'attributes' => $detailAttrsNormalized,
                                'qty' => $requiredQty,
                                'bom_parent_id' => $childBomCheck['bom_id'],
                            ];
                        }
                    }
                }
            }

            return ['status' => 200, 'message' => 'Saved!'];
        } catch (\Throwable $e) {
            return ['status' => 422, 'message' => $e];
        }
    }

    private function normalizeAttributes(array $attributes): array
    {
        $attrs = array_map(fn($a) => [
            'attribute_id' => (int) ($a['attribute_id'] ?? 0),
            'attribute_value' => (int) ($a['attribute_value'] ?? 0),
        ], $attributes);

        usort($attrs, fn($a, $b) => $a['attribute_id'] <=> $b['attribute_id']);
        return array_values(array_filter($attrs, fn($a) => $a['attribute_id'] > 0));
    }

    /**
     * Helper to load bom details for given bom id and SO context.
     * Prefers ErpSoItemBom for customizable BOMs if any records exist.
     */
    private function getBomDetailsForSo(int $bomId, $soId, $soItemId)
    {
        $soItemBomQuery = ErpSoItemBom::where('bom_id', $bomId)
            ->where('sale_order_id', $soId)
            ->where('so_item_id', $soItemId);

        if ($soItemBomQuery->exists()) {
            return $soItemBomQuery->get();
        }

        return BomDetail::where('bom_id', $bomId)->get();
    }

    /**
     * Prepare mapping payload ensuring attributes saved as JSON string for exact matching.
     * Returns array ready for save/update (types normalized).
     */
    private function prepareMappingSavePayload(array $payload): array
    {
        $attrs = $payload['attributes'] ?? [];
        $payload['attributes'] = json_encode($attrs, JSON_UNESCAPED_UNICODE);
        $payload['order_qty'] = isset($payload['order_qty']) ? floatval($payload['order_qty']) : 0;
        $payload['bom_qty'] = isset($payload['bom_qty']) ? floatval($payload['bom_qty']) : 0;
        $payload['qty'] = isset($payload['qty']) ? floatval($payload['qty']) : 0;
        return $payload;
    }
}

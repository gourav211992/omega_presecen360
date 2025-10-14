<?php

namespace App\Services\PO;

use App\Models\PoItem;
use App\Models\PiPoMapping;
use App\Models\PurchaseOrder;
use App\Models\PoItemDelivery;
use App\Helpers\ConstantHelper;
use App\Models\PurchaseOrderTed;
use App\Models\PurchaseOrderMedia;
use Illuminate\Support\Facades\Storage;

class PoDeleteService
{
    /**
     * Delete the entire PO header with dependencies
     */
    public function deletePoHeader(PurchaseOrder $po): array
    {
        if (!$po) {
            return SELF::errorResponse("No PO found to delete.");
        }

        if ($po->document_status !== ConstantHelper::DRAFT) {
            return SELF::errorResponse("Cannot delete PO, status is not draft.");
        }

        foreach ($po->po_items as $item) {
            $validation = SELF::preValidatePoItemDependencies($item);
            if ($validation['status'] == 'error') {
                return $validation;
            }
        }

        try {
            foreach ($po->po_items as $item) {
                SELF::deleteSinglePoItem($item);
            }

            $po->po_ted()->delete();
            $po->addresses()->delete();
            $po->dynamic_fields()->delete();
            $po->terms()->delete();
            $po->po_items_attribute()->delete();
            $po->po_items_delivery()->delete();
            $po->media()->delete();
            \DB::afterCommit(function () use ($po) {
                $po->media()->each(fn($m) => Storage::delete($m->file_name));
            });
            $po->delete();
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting PO header: " . $e->getMessage());
        }
        return SELF::successResponse("PO deleted successfully.");
    }

    public function deletePoItems(array $ids): array
    {
        if (empty($ids)) {
            return SELF::successResponse("No PO items to delete.");
        }

        $poItems = PoItem::whereIn('id', $ids)->get();
        try {
            foreach ($poItems as $item) {
                $validation = SELF::preValidatePoItemDependencies($item);
                if ($validation['status'] == 'error') {
                    return $validation;
                }
                SELF::deleteSinglePoItem($item);
            }
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting PO Item: " . $e->getMessage());
        }
        return SELF::successResponse("PO items deleted successfully.");
    }

    public function deleteAttachments(array $ids, sring $document_status): array
    {
        if (empty($ids)) {
            return SELF::successResponse("No attachments to delete.");
        }

        if ($document_status !== ConstantHelper::DRAFT) {
            return SELF::errorResponse("Cannot delete attachments, status is not draft.");
        }

        try {
            PurchaseOrderMedia::whereIn('id', $ids)->each(function ($media) {
                \DB::afterCommit(function () use ($media) {
                    Storage::delete($media->file_name);
                });
                $media->delete();
            });
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
        }
        return SELF::successResponse("Attachments deleted successfully.");
    }

    public function deleteHeaderExpTeds(array $ids)
    {
        if (!empty($ids)) {
            try {
                PurchaseOrderTed::whereIn('id', $ids)->delete();
            } catch (\Throwable $e) {
                return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
            }
        }
        return SELF::successResponse("Attachments deleted successfully.");
    }

    public function deleteHeaderDiscTeds(array $ids)
    {
        if (!empty($ids)) {
            try {
                PurchaseOrderTed::whereIn('id', $ids)->delete();
            } catch (\Throwable $e) {
                return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
            }
        }

        return SELF::successResponse("Attachments deleted successfully.");
    }

    public function deleteItemDiscTeds(array $ids)
    {
        if (!empty($ids)) {
            try {
                PurchaseOrderTed::whereIn('id', $ids)->delete();
            } catch (\Throwable $e) {
                return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
            }
        }
        return SELF::successResponse("Attachments deleted successfully.");
    }

    public function deleteDeliveries(array $ids)
    {
        if (!empty($ids)) {
            try {
                PoItemDelivery::whereIn('id', $ids)->delete();
            } catch (\Throwable $e) {
                return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
            }
        }
        return SELF::successResponse("Attachments deleted successfully.");
    }

    private function preValidatePoItemDependencies(PoItem $poItem): array|bool
    {
        if (floatval($poItem->grn_qty) > 0) {
            return SELF::errorResponse("Cannot delete PO item [{$poItem->id}], used in GRN.");
        }
        if (floatval($poItem->ge_qty) > 0) {
            return SELF::errorResponse("Cannot delete PO item [{$poItem->id}], used in Gate Entry.");
        }
        if (floatval($poItem->asn_qty) > 0) {
            return SELF::errorResponse("Cannot delete PO item [{$poItem->id}], used in ASN.");
        }

        return SELF::successResponse("Item Validated & does not have dependencies.");
    }

    private function deleteSinglePoItem(PoItem $poItem): void
    {
        $poItem->teds()->delete();
        $poItem->itemDelivery()->delete();
        $poItem->attributes()->delete();

        $updatedQty = $poItem->order_qty;
        $piPoMappings = PiPoMapping::where('po_item_id', $poItem->id)->orderBy('id', 'desc')->get();

        foreach ($piPoMappings as $mapping) {
            $piItem = $mapping->pi_item;
            $balQty = $piItem->order_qty;
            $utlQty = min($updatedQty, $balQty);

            $piItem->order_qty -= $utlQty;
            $piItem->save();

            if ($mapping->po_qty == $utlQty) {
                $mapping->delete();
            } else {
                $mapping->po_qty -= $utlQty;
                $mapping->save();
            }

            $updatedQty -= $utlQty;
            if ($updatedQty <= 0) {
                break;
            }
        }

        $poItem->delete();
    }

    private static function errorResponse(string $message): array
    {
        return ['status' => 'error', 'code' => 500, 'message' => $message];
    }

    private static function successResponse(string $message): array
    {
        return ['status' => 'success', 'code' => 200, 'message' => $message];
    }
}

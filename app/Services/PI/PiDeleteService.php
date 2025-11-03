<?php

namespace App\Services\pi;

use App\Models\PiItem;
use App\Helpers\Helper;
use App\Models\PiPoMapping;
use App\Models\PiSoMapping;
use App\Helpers\ConstantHelper;
use App\Models\PiSoMappingItem;
use App\Models\PurchaseIndentMedia;
use Illuminate\Support\Facades\Storage;

class PiDeleteService
{
    /**
     * Delete entire PI header with dependencies
     */
    public function deletePiHeader($pi): array
    {
        if (empty($pi)) {
            return SELF::errorResponse("No PI header found to delete.");
        }

        if ($pi->document_status !== ConstantHelper::DRAFT) {
            return SELF::errorResponse("Cannot delete PI, status is not draft.");
        }

        try {
            foreach ($pi->pi_items as $item) {
                $check = SELF::preValidatePiItemDependencies($item);
                if ($check !== true) {
                    return SELF::successResponse($check['message']);
                }
            }

            foreach ($pi->pi_items as $item) {
                SELF::deleteSinglePiItem($item);
            }

            $pi->dynamic_fields()->delete();
            $pi->media()->each(fn($m) => Storage::delete($m->file_name));
            $pi->media()->delete();
            $pi->clearExistingDocuments('pi');
            $pi->delete();
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting PI header: " . $e->getMessage());
        }
        return SELF::successResponse("PI header and dependencies deleted successfully.");
    }

    /**
     * Cancel entire purchase indent header with dependencies
     */
    public function cancelPiHeader($pi, $remark = '')
    {
        if (empty($pi)) {
            return SELF::errorResponse("No PI header found to delete.");
        }

        try {

            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'PurchaseIndent', 'relation_column' => ''],
                ['model_type' => 'detail', 'model_name' => 'PiItem', 'relation_column' => 'pi_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'PiItemAttribute', 'relation_column' => 'pi_item_id']
            ];

            if (!Helper::documentAmendment($revisionData, $pi->id)) {
                DB::rollBack();
                return SELF::errorResponse("Error occured during document revision.");
            }

            foreach ($pi->pi_items as $item) {
                $check = SELF::preValidatePiItemDependencies($item);
                if ($check !== true) {
                    return SELF::successResponse($check['message']);
                }
            }

            foreach ($pi->pi_items as $item) {
                SELF::deleteSinglePiItem($item);
            }

            $pi->dynamic_fields()->delete();
            $pi->media()->each(fn($m) => Storage::delete($m->file_name));
            $pi->media()->delete();
            $pi->clearExistingDocuments('pi');

            $bookId = $pi->book_id;
            $docId = $pi->document_id ?? $pi->id;
            $revision = (int) $pi->revision_number + 1;
            $currentLevel = (int) $pi->approval_level;

            if (class_exists(Helper::class) && method_exists(Helper::class, 'approveDocument')) {
                Helper::approveDocument($bookId, $docId, $revision, $remark, [], $currentLevel, 'cancel', $pi->total_amount, get_class($pi));
            }

            $pi->revision_number = $revision;
            $pi->revision_date = now();
            $pi->remarks = $remark;
            $pi->document_status = ConstantHelper::CANCELLED;
            $pi->save();

            return SELF::successResponse("Scrap header and all dependencies deleted successfully.");
        } catch (\Exception $e) {
            return SELF::errorResponse("Error deleting scrap header: " . $e->getMessage()  . " \n Trace:" . $e->getTraceAsString());
        }
    }

    /**
     * Delete selected PI items
     */
    public function deletePiItems(array $piItemIds, $pi): array
    {
        if (empty($piItemIds)) {
            return SELF::successResponse("No PI items to delete.");
        }

        try {
            PiItem::whereIn('id', $piItemIds)
                ->where('pi_id', $pi->id)
                ->each(function ($piItem) {
                    $check = SELF::preValidatePiItemDependencies($piItem);
                    if ($check !== true) {
                        throw new \Exception($check['message']);
                    }
                    SELF::deleteSinglePiItem($piItem);
                });
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting PI items: " . $e->getMessage());
        }
        return SELF::successResponse("PI items deleted successfully.");
    }

    /**
     * Delete PI attachments
     */
    public function deleteAttachments(array $mediaIds, $pi): array
    {
        if (empty($mediaIds)) {
            return SELF::successResponse("No attachments to delete.");
        }

        if ($pi->document_status !== ConstantHelper::DRAFT) {
            return SELF::errorResponse("Cannot delete attachments, status is not draft.");
        }

        try {
            PurchaseIndentMedia::whereIn('id', $mediaIds)->each(function ($media) {
                Storage::delete($media->file_name);
                $media->delete();
            });
        } catch (\Throwable $e) {
            return SELF::errorResponse("Error deleting attachments: " . $e->getMessage());
        }
        return SELF::successResponse("Attachments deleted successfully.");
    }

    /**
     * Validate PI item dependencies before deletion
     */
    private function preValidatePiItemDependencies($piItem): bool|array
    {
        $piItemId = $piItem->id;
        $utilizedQty = PiPoMapping::where('pi_item_id', $piItemId)
            ->whereHas('po', fn($q) => $q->whereIn('document_status', [
                ConstantHelper::APPROVED,
                ConstantHelper::APPROVAL_NOT_REQUIRED,
            ]))
            ->sum('po_qty');

        if ($utilizedQty > 0) {
            return SELF::errorResponse("Cannot delete PI item [{$piItemId}], utilized in PO(s) with qty: {$utilizedQty}");
        }

        $poSiMappingIds = PiSoMappingItem::where('pi_item_id', $piItemId)->pluck('pi_so_mapping_id');
        if ($poSiMappingIds->isNotEmpty()) {
            $mappedQty = PiSoMapping::whereIn('id', $poSiMappingIds)->sum('qty');
            if ($mappedQty > 0) {
                return SELF::errorResponse("Cannot delete PI item [{$piItemId}], already mapped in SO with qty: {$mappedQty}");
            }
        }

        return true;
    }

    /**
     * Safely delete a single PI item with mappings
     */
    private function deleteSinglePiItem($piItem): void
    {
        if ($piItem->so_pi_mapping_item()->exists()) {
            $piItem->so_pi_mapping_item->each(function ($mappingItem) {
                $mappingItem->pi_so_mapping->decrement('pi_item_qty', $mappingItem->qty);
                $mappingItem->delete();
            });
        }

        $piItem->piPwoMapping()->delete();
        $piItem->attributes()->delete();
        $piItem->delete();
    }

    private static function errorResponse(string $message): array
    {
        return ["status" => "error", "code" => 500, "message" => $message];
    }

    private static function successResponse(string $message): array
    {
        return ["status" => "success", "code" => 200, "message" => $message];
    }
}

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

use App\Helpers\ConstantHelper;
use App\Helpers\CurrencyHelper;
use App\Helpers\InventoryHelperV2;

class DeleteService
{
    // Validate the expenses of grn items in expense allocation against various conditions.
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    public function deleteByRequest(array $deletedData, $expenseAllocation)
    {
        // Delete PO details
        if (!empty($deletedData['deleted_po_item_ids'])) {
            $poDetails = PoDetail::whereIn('id', $deletedData['deleted_po_item_ids'])->get();
            foreach ($poDetails as $poDetail) {
                $poItem = $poDetail->poDetail;
                if ($poItem->exp_allocation_id) {
                    $poItem->exp_allocation_id = null;
                    $poItem->save();
                }

                $poDetail->attributes()->delete();
                $poDetail->delete();
            }
        }

        // Delete GRN details
        if (!empty($deletedData['deleted_grn_item_ids'])) {
            $grnItems = GrnDetail::whereIn('id', $deletedData['deleted_grn_item_ids'])->get();
            foreach ($grnItems as $grnItem) {
                $itemName = $grnItem->item->item_name;
                // Check Stock and delete
                $documentHeaderId = $grnItem->grn_header_id;
                $documentDetailId = $grnItem->grn_detail_id;
                $qty = $grnItem->receipt_qty;
                $itemId = $grnItem->item_id;
                $storeId = $expenseAllocation->store_id;
                $documentStatus = $expenseAllocation->document_status;
                $selectedAttr = collect($grnItem->attributes)->pluck('attr_value')->filter()->values()->toArray();
                $stockRequestData = [
                    'item_id' => $itemId,
                    'store_id' => $storeId,
                    'document_type' => 'mrn',
                    'attributes' => $selectedAttr,
                    'transaction_type' => 'receipt',
                    'document_status' => $documentStatus,
                    'document_header_id' => $documentHeaderId,
                    'document_detail_id' => $documentDetailId,
                ];
                $checkStockAvailable = InventoryHelperV2::checkMrnExpenseForDelete($stockRequestData, 'true');
                if ($checkStockAvailable['status'] === 'error') {
                    $data = self::errorResponse($checkStockAvailable['message']);
                    return $data;
                }

                $grnItem->allocations()->delete();
                $grnItem->attributes()->delete();
                $grnItem->delete();
            }
        }

        $data = self::successResponse($response = "Allocation deleted successfully.");
        return $data;
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

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

use App\Helpers\Helper;
use App\Helpers\ConstantHelper;

class AmendmentService
{
    // Validate and Submit Amendment
    //  *
    //  * @param Request $request
    //  * @return array
    //  */
    public function submit($inputData, $expenseAlc, $attachments, $attachment)
    {
        try {
            // Header History
            $expenseAlcData = $expenseAlc->toArray();
            unset($expenseAlcData['id']); // You might want to remove the primary key, 'id'
            $expenseAlcData['source_id'] = $expenseAlc->id;
            $headerHistory = HeaderHistory::create($expenseAlcData);
            $headerHistoryId = $headerHistory->id;
            if ($attachments) {
                $mediaFiles = $headerHistory->uploadDocuments($attachments, 'exp-alc', false);
            }
            $headerHistory->save();
            // Po Detail History
            $poDetails = PoDetail::where('header_id', $expenseAlc->id)->get();
            if (!empty($poDetails)) {
                foreach ($poDetails as $key => $detail) {
                    $poDetailData = $detail->toArray();
                    unset($poDetailData['id']); // You might want to remove the primary key, 'id'
                    $poDetailData['header_id'] = $headerHistoryId;
                    $poDetailData['source_id'] = $detail->id;
                    $detailHistory = PoDetailHistory::create($poDetailData);
                    $detailHistoryId = $detailHistory->id;

                    // Po Attribute History
                    $poAttributes = PoAttribute::where('header_id', $expenseAlc->id)
                        ->where('detail_id', $detail->id)
                        ->get();
                    if (!empty($poAttributes)) {
                        foreach ($poAttributes as $key1 => $attribute) {
                            $poAttributeData = $attribute->toArray();
                            unset($poAttributeData['id']); // You might want to remove the primary key, 'id'
                            $poAttributeData['header_id'] = $headerHistoryId;
                            $poAttributeData['detail_id'] = $detailHistoryId;
                            $poAttributeData['source_id'] = $attribute->id;
                            $attributeHistory = PoAttributeHistory::create($poAttributeData);
                            $attributeHistoryId = $attributeHistory->id;
                        }
                    }
                }
            }

            // Grn Detail History
            $grnDetails = GrnDetail::where('header_id', $expenseAlc->id)->get();
            if (!empty($grnDetails)) {
                foreach ($grnDetails as $key => $detail) {
                    $grnDetailData = $detail->toArray();
                    unset($grnDetailData['id']); // You might want to remove the primary key, 'id'
                    $grnDetailData['header_id'] = $headerHistoryId;
                    $grnDetailData['source_id'] = $detail->id;
                    $detailHistory = GrnDetailHistory::create($grnDetailData);
                    $detailHistoryId = $detailHistory->id;

                    // Grn Attribute History
                    $grnAttributes = GrnAttribute::where('header_id', $expenseAlc->id)
                        ->where('detail_id', $detail->id)
                        ->get();
                    if (!empty($grnAttributes)) {
                        foreach ($grnAttributes as $key1 => $attribute) {
                            $grnAttributeData = $attribute->toArray();
                            unset($grnAttributeData['id']); // You might want to remove the primary key, 'id'
                            $grnAttributeData['header_id'] = $headerHistoryId;
                            $grnAttributeData['detail_id'] = $detailHistoryId;
                            $grnAttributeData['source_id'] = $attribute->id;
                            $attributeHistory = GrnAttributeHistory::create($grnAttributeData);
                            $attributeHistoryId = $attributeHistory->id;
                        }
                    }

                    // Allocation History
                    $allocations = Allocation::where('header_id', $expenseAlc->id)
                        ->where('grn_detail_id', $detail->id)
                        ->get();
                    if (!empty($allocations)) {
                        foreach ($allocations as $key1 => $allocation) {
                            $allocationData = $allocation->toArray();
                            unset($allocationData['id']); // You might want to remove the primary key, 'id'
                            $allocationData['header_id'] = $headerHistoryId;
                            $allocationData['grn_detail_id'] = $detailHistoryId;
                            $allocationData['source_id'] = $allocation->id;
                            $allocationHistory = AllocationHistory::create($grnAttributeData);
                            $allocationHistoryId = $allocationHistory->id;
                        }
                    }
                }
            }

            $randNo = rand(10000, 99999);

            $revisionNumber = "Exp-Alc" . $randNo;
            $expenseAlc->revision_number += 1;

            /*Create document submit log*/
            if ($expenseAlc->document_status == ConstantHelper::SUBMITTED) {
                $bookId = $expenseAlc->series_id;
                $docId = $expenseAlc->id;
                $remarks = $expenseAlc->remarks;
                $attachments = $attachment;
                $currentLevel = $expenseAlc->approval_level ?? 1;
                $revisionNumber = $expenseAlc->revision_number ?? 0;
                $actionType = 'submit'; // Approve // reject // submit
                $approveDocument = Helper::approveDocument($bookId, $docId, $revisionNumber, $remarks, $attachments, $currentLevel, $actionType);
                $expenseAlc->document_status = $approveDocument['approvalStatus'];
            }
            $expenseAlc->save();

            // === OK ===
            return self::successResponse("Amendment saved.", [
                'expenseAlc' => $expenseAlc
            ]);

        } catch (\Exception $e) {
            return self::errorResponse($e->getMessage());
        }
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

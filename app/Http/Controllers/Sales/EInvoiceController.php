<?php

namespace App\Http\Controllers\Sales;

use App\Exceptions\ApiGenericException;
use App\Helpers\Common\OrganizationHelper;
use App\Http\Controllers\Controller;
use App\Helpers\MasterIndiaHelper;
use App\Models\ErpSaleInvoice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EInvoiceController extends Controller
{
    /*Cancel E Invoice functionality for Sales Invoice*/
    public function cancelEInvoice(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => [
                    'required',
                ],
                'cancel_reason' => 'required|numeric',
                "cancel_remarks" => 'required|string'
            ]
            ,
             [
                'id.required' => 'The ID field is required.',
                'cancel_reason.required' => 'Please provide a reason for cancellation.',
                'cancel_reason.numeric' => 'The cancellation reason must be a valid numeric code.',
                'cancel_remarks.required' => 'Please enter your cancellation remarks.',
                'cancel_remarks.string' => 'Cancellation remarks must be text.',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->messages()->first(),
            ], 422);
        }
        try{
            $documentHeader = ErpSaleInvoice::withWhereHas('irnDetail')->find($request->id);
            if (!$documentHeader) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice not found',
                ], 422);
            }
            $irn = $documentHeader -> irnDetail;
            if (!$irn -> irn_number) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'IRN not found',
                ], 422);
            }
            if ($irn -> cancel_date) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'IRN already cancelled',
                ], 422);
            }
            $currentOrg = OrganizationHelper::getAuthenticatedOrganization();
            if (!$irn -> ewb_cancel_date && $irn -> ewb_no) {
                //First cancel E-Way Bill
                $cancelEwbData = [];
                $cancelEwbData['user_gstin'] = $currentOrg ?-> gst_number;
                $cancelEwbData['eway_bill_number'] = $irn ?-> ewb_no;
                $cancelEwbData['cancel_reason'] = $request ?-> cancel_reason;
                $cancelEwbData['cancel_remarks'] = $request ?-> cancel_remarks;
                DB::beginTransaction();
                $cancelEwb = MasterIndiaHelper::cancelEWayBill($cancelEwbData, $documentHeader);
                if ($cancelEwb['status'] == 'error') {
                    DB::rollback();
                    return response()->json([
                        'status' => 'error',
                        'message' => $cancelEwb['message'],
                    ], 422); 
                }
            }
            $cancelData = [];
            $cancelData['user_gstin'] = $currentOrg ?-> gst_number;
            $cancelData['irn'] = $irn ?-> irn_number;
            $cancelData['cancel_reason'] = $request ?-> cancel_reason;
            $cancelData['cancel_remarks'] = $request ?-> cancel_remarks;
            DB::beginTransaction();
            $cancelInvoice = MasterIndiaHelper::cancelEInvoice($cancelData, $documentHeader);
            if ($cancelInvoice['status'] == 'error') {
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => $cancelInvoice['message'],
                ], 422); 
            }
            DB::commit();
            return response()->json([
                'status' => 'error',
                'message' => 'IRN cancelled successfully',
            ], 200);
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }

    /*Cancel E Way Bill functionality for Sales Invoice*/
    public function cancelEWayBill(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'id' => [
                    'required',
                ],
                'cancel_reason' => 'required|numeric',
                "cancel_remarks" => 'required|string'
            ]
            ,
             [
                'id.required' => 'The ID field is required.',
                'cancel_reason.required' => 'Please provide a reason for cancellation.',
                'cancel_reason.numeric' => 'The cancellation reason must be a valid numeric code.',
                'cancel_remarks.required' => 'Please enter your cancellation remarks.',
                'cancel_remarks.string' => 'Cancellation remarks must be text.',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->messages()->first(),
            ], 422);
        }
        try{
            $documentHeader = ErpSaleInvoice::withWhereHas('irnDetail')->find($request->id);
            if (!$documentHeader) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice not found',
                ], 422);
            }
            $irn = $documentHeader -> irnDetail;
            if (!$irn -> ewb_no) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'E Way bill not found',
                ], 422);
            }
            if ($irn -> ewb_cancel_date) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'E-Way Bill already cancelled',
                ], 422);
            }
            $currentOrg = OrganizationHelper::getAuthenticatedOrganization();
            //First cancel E-Way Bill
            $cancelEwbData = [];
            $cancelEwbData['user_gstin'] = $currentOrg ?-> gst_number;
            $cancelEwbData['eway_bill_number'] = $irn ?-> ewb_no;
            $cancelEwbData['cancel_reason'] = $request ?-> cancel_reason;
            $cancelEwbData['cancel_remarks'] = $request ?-> cancel_remarks;
            DB::beginTransaction();
            $cancelEwb = MasterIndiaHelper::cancelEWayBill($cancelEwbData, $documentHeader);
            if ($cancelEwb['status'] == 'error') {
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => $cancelEwb['message'],
                ], 422); 
            }
            DB::commit();
            return response()->json([
                'status' => 'error',
                'message' => 'E-Way bill cancelled successfully',
            ], 200);
        } catch(Exception $ex) {
            throw new ApiGenericException($ex -> getMessage());
        }
    }
}

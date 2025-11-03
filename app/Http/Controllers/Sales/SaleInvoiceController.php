<?php

namespace App\Http\Controllers\Sales;

use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ErpAddress;
use App\Models\ErpSaleInvoice;
use App\Models\ErpSaleInvoiceTed;
use App\Models\ErpSiDynamicField;
use App\Models\ItemDetail;
use App\Models\Voucher;
use App\Services\Sales\DeliveryNoteDelete;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleInvoiceController extends Controller
{
    public function cancelDocument(Request $request)
    {
        try {
            $saleInvoiceId = $request -> input('id', '');
            $cancelRemarks = $request -> input('cancel_doc_remarks', '');
            $saleInvoice = ErpSaleInvoice::find($saleInvoiceId);
            if (!$saleInvoice) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Document not found',
                ], 422);
            }
            DB::beginTransaction();
            $revisionData = [
                ['model_type' => 'header', 'model_name' => 'ErpSaleInvoice', 'relation_column' => ''],
                ['model_type' => 'detail', 'model_name' => 'ErpInvoiceItem', 'relation_column' => 'sale_invoice_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpInvoiceItemAttribute', 'relation_column' => 'invoice_item_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpInvoiceItemLocation', 'relation_column' => 'invoice_item_id'],
                ['model_type' => 'sub_detail', 'model_name' => 'ErpSaleInvoiceTed', 'relation_column' => 'invoice_item_id'],
            ];
            Helper::documentAmendment($revisionData, $saleInvoice->id);

            //Revision success 
            $voucher = $saleInvoice -> voucher;
            if ($voucher) {
                //Create history for voucher also 
                 $revisionData = [
                    ['model_type' => 'header', 'model_name' => 'Voucher', 'relation_column' => ''],
                    ['model_type' => 'detail', 'model_name' => 'ItemDetail', 'relation_column' => 'voucher_id'],
                ];
                Helper::documentAmendment($revisionData, $voucher->id);

                //Now first delete voucher
                ItemDetail::where('voucher_id', $voucher -> id) -> delete();
                Voucher::where('id', $voucher -> id) -> delete();
            }

            //Now delete Document
            $itemIds = $saleInvoice -> items -> pluck('id') -> toArray();
            $deleteService = new DeliveryNoteDelete();
            $status = $deleteService -> deleteByRequest($itemIds, $saleInvoice);

            if ($status['status'] == 'error') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $status['message']
                ], 422);
            }

            //Remove Header Level relations
            ErpSaleInvoiceTed::where('sale_invoice_id', $saleInvoice -> id) -> delete();
            ErpAddress::where('addressable_type', ErpSaleInvoice::class) -> where('addressable_id', $saleInvoice->id) -> delete();
            ErpSiDynamicField::where('header_id', $saleInvoice -> id) -> delete();
            //Reset header values
            $saleInvoice->customer_id = null;
            $saleInvoice->customer_code = null;
            $saleInvoice->customer_email = null;
            $saleInvoice->customer_phone_no = null;
            $saleInvoice->customer_gstin = null;
            $saleInvoice->consignee_name = null;
            $saleInvoice->consignee_id = null;
            $saleInvoice->consignment_no = null;

            $saleInvoice->eway_bill_no = null;
            $saleInvoice->eway_bill_master_id = null;
            $saleInvoice->transportation_mode = null;
            $saleInvoice->transporter_name = null;
            $saleInvoice->transporter_id = null;
            $saleInvoice->transporter_gstin = null;
            $saleInvoice->vehicle_no = null;
            $saleInvoice->lr_number = null;

            $saleInvoice->billing_address = null;
            $saleInvoice->shipping_address = null;
            $saleInvoice->currency_id = null;
            $saleInvoice->currency_code = null;
            $saleInvoice->payment_term_id = null;
            $saleInvoice->payment_term_code = null;
            $saleInvoice->credit_days = 0;

            $saleInvoice->delivery_status = 0;
            $saleInvoice->e_invoice_status = 0;
            $saleInvoice->is_ewb_generated = 0;
            $saleInvoice->remarks = null;

            $saleInvoice->total_item_value = 0;
            $saleInvoice->total_discount_value = 0;
            $saleInvoice->total_tax_value = 0;
            $saleInvoice->total_expense_value = 0;
            $saleInvoice->total_amount = 0;

            $saleInvoice->book_terms = null;
            $saleInvoice->book_terms_id = null;

            $saleInvoice->customer_terms = null;
            $saleInvoice->customer_terms_id = null;

            $saleInvoice->revision_number += 1;
            $saleInvoice->revision_date = Carbon::today();

            $saleInvoice->save();

            //Cancelled log - 
            $log = Helper::approveDocument($saleInvoice->book_id, $saleInvoice->id, $saleInvoice->revision_number, $cancelRemarks, [], 
                $saleInvoice->approval_level, 'cancel', $saleInvoice->total_amount, get_class($saleInvoice));
            
            if ($log['message']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $log['message']
                ], 422);
            }

            $saleInvoice -> document_status = ConstantHelper::CANCELLED;
            $saleInvoice -> save();

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Document Cancelled successfully'
            ], 200);

        } catch (Exception $ex) {
            DB::rollback();
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}

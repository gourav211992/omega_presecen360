<?php
namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Voucher;
use App\Models\ItemDetail;
use App\Models\PaymentVoucher;
use App\Models\DocumentApproval;

use App\Models\Scopes\DefaultGroupCompanyOrgScope;

class VoucherHelper
{
    public static function postVoucher(array $details)
    {
        //Post Voucher
        $exitingVoucher = Voucher::where('reference_service', $details['voucher_header']['reference_service'])->where('reference_doc_id', $details['voucher_header']['reference_doc_id'])->first();
        if ($exitingVoucher) {
            return array(
                'message' => $exitingVoucher->voucher_no . ' Voucher already posted',
                'status' => false
            );
        }
        $totalCreditAmount = 0;
        $totalDebitAmount = 0;
        $details['voucher_header']['approvalStatus'] = $details['voucher_header']['document_status'];
        $voucher = Voucher::create($details['voucher_header']);
        foreach ($details['voucher_details'] as &$voucherDetail) {
            $voucherDetail['voucher_id'] = $voucher->id;
            $totalCreditAmount += $voucherDetail['credit_amt'];
            $totalDebitAmount += $voucherDetail['debit_amt'];
            ItemDetail::create($voucherDetail);
        }
        if (round($totalDebitAmount, 6) !== round($totalCreditAmount, 6)) {
            return array(
                'message' => 'Credit Amount does not match Debit Amount',
                'status' => false
            );
        }
        //Create log
        $userData = Helper::getAuthenticatedUser();

        $referenceModelName = isset(ConstantHelper::SERVICE_ALIAS_MODELS[$voucher->reference_service]) ? ConstantHelper::SERVICE_ALIAS_MODELS[$voucher->reference_service] : null;
        if ($referenceModelName) {
            $referenceModel = resolve("App\\Models\\" . $referenceModelName);
            $referenceDoc = $referenceModel::find($voucher->reference_doc_id);
            if (isset($referenceDoc)) {
                //Post the original document
                $referenceDoc->document_status = ConstantHelper::POSTED;
                $referenceDoc->save();
                $docApproval = new DocumentApproval;
                $docApproval->document_type = $voucher->reference_service;
                $docApproval->document_id = $voucher->reference_doc_id;
                $docApproval->document_name = $referenceModel::class;
                $docApproval->approval_type = ConstantHelper::POSTED;
                $docApproval->approval_date = now();
                $docApproval->revision_number = $referenceDoc->revision_number ?? 0;
                $docApproval->remarks = null;
                $docApproval->user_id = $userData->auth_user_id;
                $user_type = $userData->authenticable_type;
                $docApproval->user_type = $user_type;
                $docApproval->save();
            }
        }
        //Push data in GSTR tables
        $gstrData = GstrHelper::pushVoucherDataToGstrTable($details['voucher_header']['reference_service'], $details['voucher_header']['reference_doc_id']);
        return $gstrData;
    }

    This code is for post voucher details, Now I am going to cancel voucher details.
    In this case All voucher and item details dara should be copied to histories table, and delete from main table.
    As well as DocumentApproval table also need to update for cancel status(means It should create new entry).

    Histry tablels Modals are VoucherHistory, ItemDetailHistory, PaymentVoucherHistory


}

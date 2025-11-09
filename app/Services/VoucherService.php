<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Helpers\Helper;
use App\Helpers\ConstantHelper;
use App\Helpers\ServiceParametersHelper;
use App\Models\Book;
use App\Models\Service;

use App\Models\Voucher;
use App\Models\ItemDetail;
use App\Models\PaymentVoucher;
use App\Models\DocumentApproval;

use App\Models\VoucherHistory;
use App\Models\ItemDetailHistory;
use App\Models\OrganizationBookParameter;
use App\Models\Scopes\DefaultGroupCompanyOrgScope;

class VoucherService
{
    /**
     * Cancel a posted voucher:
     * - copies header/details/payment rows to histories
     * - deletes live rows
     * - appends DocumentApproval (CANCELLED)
     *
     * @param array $payload [
     *   'voucher_id'            => int|null,
     *   'reference_service'     => string|null,
     *   'reference_doc_id'      => int|null,
     *   'cancel_remarks'        => string|null,
     *   'cancel_attachments'    => array|null,
     * ]
     */
    public static function cancelVoucher($user, $voucher, array $payload = [])
    {
        // $voucherId = $payload['voucher_id'] ?? null;
        // $referenceService = $payload['reference_service'] ?? null;
        // $referenceDocId = $payload['reference_doc_id'] ?? null;
        $remarks = trim((string) ($payload['cancel_remarks'] ?? ''));
        $attachments = $payload['cancel_attachments'] ?? [];

        // // --- fetch voucher header (by id OR by (ref_service, ref_doc_id)) ---
        // /** @var \App\Models\Voucher|null $voucher */
        // $voucher = null;

        // if ($voucherId) {
        //     $voucher = Voucher::with(['items'])  // items() -> hasMany(ItemDetail::class, 'voucher_id')
        //         ->whereKey($voucherId)
        //         ->first();
        // } elseif ($referenceService && $referenceDocId) {
        //     $voucher = Voucher::with(['items'])
        //         ->where('reference_service', $referenceService)
        //         ->where('reference_doc_id', $referenceDocId)
        //         ->first();
        // }

        if (!$voucher) {
            return [
                'status' => false,
                'message' => 'Voucher not found for cancellation.',
            ];
        }

        try {
            // ---- copy header to VoucherHistory ----
            /** @var \App\Models\VoucherHistory $vh */
            $vh = new VoucherHistory();
            $vh->fill($voucher->getAttributes());          // copies columns with same names
            // Add meta to history (if your history table has these)
            if (Schema::hasColumn($vh->getTable(), 'source_id')) {
                $vh->source_id = $voucher->id;
            }
            // if (Schema::hasColumn($vh->getTable(), 'cancelled_at')) {
            //     $vh->cancelled_at = now();
            // }
            // if (Schema::hasColumn($vh->getTable(), 'cancelled_by')) {
            //     $vh->cancelled_by = $user->auth_user_id ?? null;
            // }
            // if (Schema::hasColumn($vh->getTable(), 'cancel_remarks')) {
            //     $vh->cancel_remarks = $remarks;
            // }
            // if (Schema::hasColumn($vh->getTable(), 'document_status')) {
            //     $vh->document_status = ConstantHelper::CANCELLED;
            // }
            $vh->save();
            // ---- copy details to ItemDetailHistory ----
            $details = $voucher->items()->get(); // \App\Models\ItemDetail
            foreach ($details as $d) {
                /** @var \App\Models\ItemDetailHistory $dh */
                $dh = new ItemDetailHistory();
                $dh->fill($d->getAttributes());
                // keep link to original voucher id (usual pattern in history tables)
                if (Schema::hasColumn($dh->getTable(), 'source_id')) {
                    $dh->source_id = $d->id;
                }
                // link to history header if you have such column
                if (Schema::hasColumn($dh->getTable(), 'voucher_id')) {
                    $dh->voucher_id = $vh->id;
                }
                $dh->save();
            }

            // ---- copy payment rows to PaymentVoucherHistory (if any + model exists) ----
            // if (
            //     class_exists(\App\Models\PaymentVoucher::class) &&
            //     class_exists(\App\Models\PaymentVoucherHistory::class)
            // ) {

            //     /** @var \App\Models\PaymentVoucher[] $payments */
            //     $payments = \App\Models\PaymentVoucher::where('voucher_id', $voucher->id)->get();

            //     foreach ($payments as $p) {
            //         $ph = new \App\Models\PaymentVoucherHistory();
            //         $ph->fill($p->getAttributes());
            //         if (Schema::hasColumn($ph->getTable(), 'source_id')) {
            //             $ph->source_id = $p->id;
            //         }
            //         if (Schema::hasColumn($ph->getTable(), 'voucher_history_id')) {
            //             $ph->voucher_history_id = $vh->id;
            //         }
            //         if (Schema::hasColumn($ph->getTable(), 'cancelled_at')) {
            //             $ph->cancelled_at = now();
            //         }
            //         $ph->save();
            //     }
            // }

            // ---- DocumentApproval: add CANCELLED entry ----
            // $userId = $user->auth_user_id ?? null;
            // $userType = $user->authenticable_type ?? null;

            // $docApproval = new DocumentApproval();
            // $docApproval->document_type = $voucher->reference_service;           // service alias
            // $docApproval->document_id = $voucher->reference_doc_id;            // the source document id
            // $docApproval->document_name = get_class($voucher);                   // or the referenced model if you prefer
            // $docApproval->approval_type = ConstantHelper::VOUCHER_CANCELLED;             // status
            // $docApproval->approval_date = now();
            // $docApproval->revision_number = ($voucher->revision_number ?? 0) + 1;  // or keep same, up to you
            // $docApproval->remarks = $remarks;
            // $docApproval->user_id = $userId;
            // $docApproval->user_type = $userType;
            // $docApproval->save();

            // ---- Optional: revert voucher in GSTR tables if you pushed earlier ----
            // if (
            //     class_exists(\App\Helpers\GstrHelper::class) &&
            //     method_exists(\App\Helpers\GstrHelper::class, 'revertVoucherDataFromGstrTables')
            // ) {
            //     \App\Helpers\GstrHelper::revertVoucherDataFromGstrTables(
            //         $voucher->reference_service,
            //         $voucher->reference_doc_id
            //     );
            // }

            // ---- delete live rows (children first, then header) ----
            ItemDetail::where('voucher_id', $voucher->id)->delete();

            // if (class_exists(\App\Models\PaymentVoucher::class)) {
            //     \App\Models\PaymentVoucher::where('voucher_id', $voucher->id)->delete();
            // }

            $voucher->delete();

            return [
                'status' => 'success',
                'message' => 'Voucher cancelled successfully.',
                'data' => [
                    'voucher_id' => $voucherId ?? $voucher->id,
                    'reference_service' => $voucher->reference_service,
                    'reference_doc_id' => $voucher->reference_doc_id,
                    'history_header_id' => $vh->id,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to cancel voucher.',
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function financeVoucherPostingHistory(int $bookId, int $documentId, string $type, bool $onApproval = false)
    {

        $contra_entries = [];
        //Check Book
        $book = Book::find($bookId);
        if (!isset($book)) {
            return array(
                'status' => false,
                'message' => 'Book not found',
                'data' => []
            );
        }
        //Check Service
        $service = Service::find($book->service_id);
        if (!isset($service)) {
            return array(
                'status' => false,
                'message' => 'Service not found',
                'data' => []
            );
        }
        $isFinanceVoucherDefined = ServiceParametersHelper::getFinancialServiceAlias($service->alias);
        if (!isset($isFinanceVoucherDefined)) {
            return array(
                'status' => false,
                'message' => '',
                'data' => []
            );
        }
        //Check Posting parameters
        $financialPostParam = OrganizationBookParameter::where('book_id', $book->id)->where('parameter_name', ServiceParametersHelper::GL_POSTING_REQUIRED_PARAM)->first();
        if (!isset($financialPostParam)) {
            return array(
                'status' => false,
                'message' => 'GL Posting Parameter not specified',
                'data' => []
            );
        }
        $isPostingRequired = (($financialPostParam->parameter_value[0] ?? '') === 'yes' ? true : false);
        if (!$isPostingRequired) {
            return array(
                'status' => false,
                'message' => '',
                'data' => []
            );
        }
        //Check if this helper is called upon approval
        if ($onApproval) {
            $postOnApproveParam = OrganizationBookParameter::where('book_id', $book->id)->where('parameter_name', ServiceParametersHelper::POST_ON_ARROVE_PARAM)->first();
            if (!isset($postOnApproveParam)) {
                return array(
                    'status' => false,
                    'message' => 'Post on Approval Parameter not found',
                    'data' => []
                );
            }
            $isPostOnApprovalRequired = (($postOnApproveParam->parameter_value[0] ?? '') === "yes" ? true : false);
            if (!$isPostOnApprovalRequired) {
                return array(
                    'status' => false,
                    'message' => '',
                    'data' => []
                );
            }
        }
        $serviceAlias = $service->alias;

        return self::getDocumentPostedVoucher($documentId, $service->alias);
    }

    public static function getDocumentPostedVoucher(int $documentId, string $serviceAlias)
    {
        $voucher = VoucherHistory::with('items')->where('reference_service', $serviceAlias)->where('reference_doc_id', $documentId)->first();
        if (!isset($voucher)) {
            return array(
                'status' => false,
                'message' => 'No posted voucher found',
                'data' => []
            );
        }
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($voucher->ledger_items as $ledgerItem) {
            $totalDebit += $ledgerItem->debit_amount;
            $totalCredit += $ledgerItem->credit_amount;
        }
        $entries = $voucher->ledger_items->groupBy('entry_type');
        return array(
            'status' => true,
            'message' => 'Voucher found',
            'data' => array(
                'book_code' => $voucher->series?->book_code,
                'currency_code' => $voucher->currency_code,
                'document_date' => $voucher->document_date,
                'document_number' => $voucher->voucher_no,
                'ledgers' => $entries,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit
            )
        );
    }
}

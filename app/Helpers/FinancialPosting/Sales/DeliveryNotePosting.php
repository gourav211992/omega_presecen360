<?php

namespace App\Helpers\FinancialPosting\Sales;

use App\Helpers\AccountHelper;
use App\Helpers\ConstantHelper;
use App\Models\Book;
use App\Models\StockLedger;
use App\Models\Currency;
use App\Models\ErpInvoiceItem;
use App\Models\ErpSaleInvoice;
use App\Models\Group;
use App\Models\Ledger;
use App\Models\OrganizationBookParameter;
use Illuminate\Support\Collection;
use App\Helpers\FinancialPostingHelper;
use App\Helpers\Helper;
use App\Helpers\ServiceParametersHelper;

class DeliveryNotePosting
{
    public static function voucherDetails(int $documentId, string $type)
    {
        $document = ErpSaleInvoice::find($documentId);
        if (!isset($document)) {
            return array(
                'status' => false,
                'message' => 'Document not found',
                'data' => []
            );
        }

        //Invoice to follow
        $postingArray = array(
            FinancialPostingHelper::COGS_ACCOUNT => [],
            FinancialPostingHelper::STOCK_ACCOUNT => []
        );
        //Assign Credit and Debit amount for tally check
        $totalCreditAmount = 0;
        $totalDebitAmount = 0;

        //Status to check if all ledger entries were properly set
        $ledgerErrorStatus = null;
        //COGS SETUP
        foreach ($document->items as $docItemKey => $docItem) {
            $itemValue = 0;
            $orgCurrencyCost = 0;
            $dnDetailId = $docItem->id;
            $deliveryNote = ErpInvoiceItem::find($dnDetailId);
            $stockLedger = StockLedger::whereIn('book_type', [ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS, ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS])->where('document_header_id', $deliveryNote?->sale_invoice_id)->where('document_detail_id', $dnDetailId)->first();
            if (isset($stockLedger)) {
                $orgCurrencyCost = StockLedger::where('utilized_id', $stockLedger->id)->get()->sum('org_currency_cost');
                $itemValue = $orgCurrencyCost / $document->org_currency_exg_rate;
            }
            $stockCreditAmount = round($itemValue, 2);
            $cogsDebitAmount = round($itemValue, 2);

            $cogsLedgerDetails = AccountHelper::getCogsLedgerGroupAndLedgerId($document->organization_id, $docItem->item_id, $document->book_id);
            $cogsLedgerId = is_a($cogsLedgerDetails, Collection::class) ? $cogsLedgerDetails->first()['ledger_id'] : null;
            $cogsLedgerGroupId = is_a($cogsLedgerDetails, Collection::class) ? $cogsLedgerDetails->first()['ledger_group'] : null;
            $cogsLedger = Ledger::find($cogsLedgerId);
            $cogsLedgerGroup = Group::find($cogsLedgerGroupId);
            //LEDGER NOT FOUND
            if (!isset($cogsLedger) || !isset($cogsLedgerGroup)) {
                $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'COGS Account not setup';
                break;
            }
            //Check for same ledger and group in SALES ACCOUNT
            $existingCogsLedger = array_filter($postingArray[FinancialPostingHelper::COGS_ACCOUNT], function ($posting) use ($cogsLedgerId, $cogsLedgerGroupId) {
                return $posting['ledger_id'] == $cogsLedgerId && $posting['ledger_group_id'] == $cogsLedgerGroupId;
            });
            //Ledger found
            if (count($existingCogsLedger) > 0) {
                $postingArray[FinancialPostingHelper::COGS_ACCOUNT][0]['debit_amount'] += $cogsDebitAmount;
                $postingArray[FinancialPostingHelper::COGS_ACCOUNT][0]['debit_amount_org'] += $orgCurrencyCost;
            } else { //Assign a new ledger
                array_push($postingArray[FinancialPostingHelper::COGS_ACCOUNT], [
                    'ledger_id' => $cogsLedgerId,
                    'ledger_group_id' => $cogsLedgerGroupId,
                    'ledger_code' => $cogsLedger?->code,
                    'ledger_name' => $cogsLedger?->name,
                    'ledger_group_code' => $cogsLedgerGroup?->name,
                    'credit_amount' => 0,
                    'credit_amount_org' => 0,
                    'debit_amount' => $cogsDebitAmount,
                    'debit_amount_org' => $orgCurrencyCost
                ]);
            }

            $stockLedgerDetails = AccountHelper::getStockLedgerGroupAndLedgerId($document->organization_id, $docItem->item_id, $document->book_id);
            $stockLedgerId = is_a($stockLedgerDetails, Collection::class) ? $stockLedgerDetails->first()['ledger_id'] : null;
            $stockLedgerGroupId = is_a($stockLedgerDetails, Collection::class) ? $stockLedgerDetails->first()['ledger_group'] : null;
            $stockLedger = Ledger::find($stockLedgerId);
            $stockLedgerGroup = Group::find($stockLedgerGroupId);
            //LEDGER NOT FOUND
            if (!isset($stockLedger) || !isset($stockLedgerGroup)) {
                $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'Stock Account not setup';
                break;
            }

            //Check for same ledger and group in STOCK ACCOUNT
            $existingstockLedger = array_filter($postingArray[FinancialPostingHelper::STOCK_ACCOUNT], function ($posting) use ($stockLedgerId, $stockLedgerGroupId) {
                return $posting['ledger_id'] == $stockLedgerId && $posting['ledger_group_id'] == $stockLedgerGroupId;
            });
            //Ledger found
            if (count($existingstockLedger) > 0) {
                $postingArray[FinancialPostingHelper::STOCK_ACCOUNT][0]['credit_amount'] += $stockCreditAmount;
                $postingArray[FinancialPostingHelper::STOCK_ACCOUNT][0]['credit_amount_org'] += $orgCurrencyCost;
            } else { //Assign a new ledger
                array_push($postingArray[FinancialPostingHelper::STOCK_ACCOUNT], [
                    'ledger_id' => $stockLedgerId,
                    'ledger_group_id' => $stockLedgerGroupId,
                    'ledger_code' => $stockLedger?->code,
                    'ledger_name' => $stockLedger?->name,
                    'ledger_group_code' => $stockLedgerGroup?->name,
                    'credit_amount' => $stockCreditAmount,
                    'credit_amount_org' => $orgCurrencyCost,
                    'debit_amount' => 0,
                    'debit_amount_org' => 0,
                ]);
            }
        }
        //Check if All Legders exists and posting is properly set
        if ($ledgerErrorStatus) {
            return array(
                'status' => false,
                'message' => $ledgerErrorStatus,
                'data' => []
            );
        }
        //Check debit and credit tally
        foreach ($postingArray as $postAccount) {
            foreach ($postAccount as $postingValue) {
                $totalCreditAmount += $postingValue['credit_amount'];
                $totalDebitAmount += $postingValue['debit_amount'];
            }
        }
        //Get Header Details
        $book = Book::find($document->book_id);
        $glPostingBookParam = OrganizationBookParameter::where('book_id', $book->id)->where('parameter_name', ServiceParametersHelper::GL_POSTING_SERIES_PARAM)->first();
        if (isset($glPostingBookParam) && isset($glPostingBookParam->parameter_value[0])) {
            $glPostingBookId = $glPostingBookParam->parameter_value[0];
        } else {
            return array(
                'status' => false,
                'message' => FinancialPostingHelper::ERROR_PREFIX . 'Financial Book Code is not specified',
                'data' => []
            );
        }
        $currency = Currency::find($document->currency_id);
        $userData = Helper::userCheck();
        $voucherHeader = [
            'voucher_no' => $document->document_number,
            'document_date' => $document->document_date,
            'book_id' => $glPostingBookId,
            'date' => $document->document_date,
            'amount' => $totalCreditAmount,
            'currency_id' => $document->currency_id,
            'currency_code' => $document->currency_code,
            'org_currency_id' => $document->org_currency_id,
            'org_currency_code' => $document->org_currency_code,
            'org_currency_exg_rate' => $document->org_currency_exg_rate,
            'comp_currency_id' => $document->comp_currency_id,
            'comp_currency_code' => $document->comp_currency_code,
            'comp_currency_exg_rate' => $document->comp_currency_exg_rate,
            'group_currency_id' => $document->group_currency_id,
            'group_currency_code' => $document->group_currency_code,
            'group_currency_exg_rate' => $document->group_currency_exg_rate,
            'reference_service' => $book?->service?->alias,
            'reference_doc_id' => $document->id,
            'group_id' => $document->group_id,
            'company_id' => $document->company_id,
            'organization_id' => $document->organization_id,
            'voucherable_type' => $userData['user_type'],
            'voucherable_id' => $userData['user_id'],
            'document_status' => ConstantHelper::APPROVED,
            'approvalLevel' => $document->approval_level,
            'location' => $document?->store_id
        ];
        $voucherDetails = FinancialPostingHelper::generateVoucherDetailsArray($postingArray, $voucherHeader, $document, 'currency_id', 'document_date', true);
        return array(
            'status' => true,
            'message' => 'Posting Details found',
            'data' => [
                'voucher_header' => $voucherHeader,
                'voucher_details' => $voucherDetails,
                'document_date' => $document->document_date,
                'ledgers' => $postingArray,
                'total_debit' => $totalDebitAmount,
                'total_credit' => $totalCreditAmount,
                'book_code' => $book?->book_code,
                'document_number' => $document->document_number,
                'currency_code' => $currency?->short_name
            ]
        );
    }
}

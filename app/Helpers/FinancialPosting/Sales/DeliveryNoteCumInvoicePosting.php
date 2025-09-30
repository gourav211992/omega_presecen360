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
use App\Models\Customer;
use App\Models\DiscountMaster;
use App\Models\ErpSaleInvoiceTed;
use App\Models\ExpenseMaster;
use App\Models\TaxDetail;
use Illuminate\Support\Facades\DB;

class DeliveryNoteCumInvoicePosting
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
        $invoiceToFollow = 0;
        $postingArray = array(
            FinancialPostingHelper::CUSTOMER_ACCOUNT => [],
            FinancialPostingHelper::DISCOUNT_ACCOUNT => [],
            FinancialPostingHelper::SALES_ACCOUNT => [],
            FinancialPostingHelper::TAX_ACCOUNT => [],
            FinancialPostingHelper::EXPENSE_ACCOUNT => [],
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
            $stockLedger = StockLedger::where('book_type',ConstantHelper::DELIVERY_CHALLAN_CUM_SI_SERVICE_ALIAS)->where('document_header_id', $deliveryNote?->sale_invoice_id)->where('document_detail_id', $dnDetailId)->first();
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

            //Check for same ledger and group in SALES ACCOUNT
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
        $customerAccountDebit = 0;
        //Customer Account initialize
        if (!$invoiceToFollow) {

            $customer = Customer::find($document->customer_id);
            $customerLedgerId = $customer->ledger_id;
            $customerLedgerGroupId = $customer->ledger_group_id;
            $customerLedger = Ledger::find($customerLedgerId);
            $customerLedgerGroup = Group::find($customerLedgerGroupId);
            //Customer Ledger account not found
            if (!isset($customerLedger) || !isset($customerLedgerGroup)) {
                return array(
                    'status' => false,
                    'message' => FinancialPostingHelper::ERROR_PREFIX . 'Customer Account not setup',
                    'data' => []
                );
            }
            $discountSeperatePosting = false;
            foreach ($document->items as $docItemKey => $docItem) {
                //Assign Item values
                $itemValue = $docItem->rate * $docItem->order_qty;
                $itemTotalDiscount = $docItem->header_discount_amount + $docItem->item_discount_amount;
                $itemValueAfterDiscount = $itemValue - $itemTotalDiscount;
                //SALES ACCOUNT
                $salesAccountLedgerDetails = AccountHelper::getLedgerGroupAndLedgerIdForSalesAccount($document->organization_id, $document->customer_id, $docItem->item_id, $document->book_id);
                $salesAccountLedgerId = is_a($salesAccountLedgerDetails, Collection::class) ? $salesAccountLedgerDetails->first()['ledger_id'] : null;
                $salesAccountLedgerGroupId = is_a($salesAccountLedgerDetails, Collection::class) ? $salesAccountLedgerDetails->first()['ledger_group'] : null;
                $salesAccountLedger = Ledger::find($salesAccountLedgerId);
                $salesAccountLedgerGroup = Group::find($salesAccountLedgerGroupId);
                //LEDGER NOT FOUND
                if (!isset($salesAccountLedger) || !isset($salesAccountLedgerGroup)) {
                    $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'Sales Account not setup';
                    break;
                }
                $salesCreditAmount = $discountSeperatePosting ? $itemValue : $itemValueAfterDiscount;
                //Check for same ledger and group in SALES ACCOUNT
                $existingSalesLedger = array_filter($postingArray[FinancialPostingHelper::SALES_ACCOUNT], function ($posting) use ($salesAccountLedgerId, $salesAccountLedgerGroupId) {
                    return $posting['ledger_id'] == $salesAccountLedgerId && $posting['ledger_group_id'] == $salesAccountLedgerGroupId;
                });
                //Ledger found
                if (count($existingSalesLedger) > 0) {
                    $postingArray[FinancialPostingHelper::SALES_ACCOUNT][0]['credit_amount'] += $salesCreditAmount;
                } else { //Assign a new ledger
                    array_push($postingArray[FinancialPostingHelper::SALES_ACCOUNT], [
                        'ledger_id' => $salesAccountLedgerId,
                        'ledger_group_id' => $salesAccountLedgerGroupId,
                        'ledger_code' => $salesAccountLedger?->code,
                        'ledger_name' => $salesAccountLedger?->name,
                        'ledger_group_code' => $salesAccountLedgerGroup?->name,
                        'credit_amount' => $salesCreditAmount,
                        'debit_amount' => 0
                    ]);
                }
                $customerAccountDebit += $itemValueAfterDiscount;
            }
            //TAXES ACCOUNT
            $taxes = ErpSaleInvoiceTed::where('sale_invoice_id', $document->id)->where('ted_type', "Tax")->get();
            foreach ($taxes as $tax) {
                $taxDetail = TaxDetail::find($tax->ted_id);
                $taxLedgerId = $taxDetail->ledger_id ?? null; //MAKE IT DYNAMIC
                $taxLedgerGroupId = $taxDetail->ledger_group_id ?? null; //MAKE IT DYNAMIC
                $taxLedger = Ledger::find($taxLedgerId);
                $taxLedgerGroup = Group::find($taxLedgerGroupId);
                if (!isset($taxLedger) || !isset($taxLedgerGroup)) {
                    $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'Tax Account not setup';
                    break;
                }
                $existingTaxLedger = array_filter($postingArray[FinancialPostingHelper::TAX_ACCOUNT], function ($posting) use ($taxLedgerId, $taxLedgerGroupId) {
                    return $posting['ledger_id'] == $taxLedgerId && $posting['ledger_group_id'] === $taxLedgerGroupId;
                });
                //Ledger found
                if (count($existingTaxLedger) > 0) {
                    $postingArray[FinancialPostingHelper::TAX_ACCOUNT][0]['credit_amount'] += $tax->ted_amount;
                } else { //Assign a new ledger
                    array_push($postingArray[FinancialPostingHelper::TAX_ACCOUNT], [
                        'ledger_id' => $taxLedgerId,
                        'ledger_group_id' => $taxLedgerGroupId,
                        'ledger_code' => $taxLedger?->code,
                        'ledger_name' => $taxLedger?->name,
                        'ledger_group_code' => $taxLedgerGroup?->name,
                        'credit_amount' => $tax->ted_amount,
                        'debit_amount' => 0,
                    ]);
                }
                $customerAccountDebit += $tax->ted_amount;
            }
            //EXPENSES
            $expenses = ErpSaleInvoiceTed::where('sale_invoice_id', $document->id)->where('ted_type', "Expense")->get();
            foreach ($expenses as $expense) {
                $expenseDetail = ExpenseMaster::find($expense->ted_id);
                $expenseLedgerId = $expenseDetail?->expense_ledger_id; //MAKE IT DYNAMIC - 5
                $expenseLedgerGroupId = $expenseDetail?->expense_ledger_group_id; //MAKE IT DYNAMIC - 9
                $expenseLedger = Ledger::find($expenseLedgerId);
                $expenseLedgerGroup = Group::find($expenseLedgerGroupId);
                if (!isset($expenseLedger) || !isset($expenseLedgerGroup)) {
                    $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'Expense Account not setup';
                    break;
                }
                $existingExpenseLedger = array_filter($postingArray[FinancialPostingHelper::EXPENSE_ACCOUNT], function ($posting) use ($expenseLedgerId, $expenseLedgerGroupId) {
                    return $posting['ledger_id'] == $expenseLedgerId && $posting['ledger_group_id'] === $expenseLedgerGroupId;
                });
                //Ledger found
                if (count($existingExpenseLedger) > 0) {
                    $postingArray[FinancialPostingHelper::EXPENSE_ACCOUNT][0]['credit_amount'] += $expense->ted_amount;
                } else { //Assign a new ledger
                    array_push($postingArray[FinancialPostingHelper::EXPENSE_ACCOUNT], [
                        'ledger_id' => $expenseLedgerId,
                        'ledger_group_id' => $expenseLedgerGroupId,
                        'ledger_code' => $expenseLedger?->code,
                        'ledger_name' => $expenseLedger?->name,
                        'ledger_group_code' => $expenseLedgerGroup?->name,
                        'credit_amount' => $expense->ted_amount,
                        'debit_amount' => 0,
                    ]);
                }
                $customerAccountDebit += $expense->ted_amount;
            }
            //Seperate posting of Discount
            if ($discountSeperatePosting) {
                $discounts = ErpSaleInvoiceTed::where('sale_invoice_id', $document->id)->where('ted_type', "Discount")->get();
                foreach ($discounts as $discount) {
                    $discountDetail = DiscountMaster::find($discount->ted_id);
                    $discountLedgerId = $discountDetail?->discount_ledger_id; //MAKE IT DYNAMIC
                    $discountLedgerGroupId = $discountDetail?->discount_ledger_group_id; //MAKE IT DYNAMIC
                    $discountLedger = Ledger::find($discountLedgerId);
                    $discountLedgerGroup = Group::find($discountLedgerGroupId);
                    if (!isset($discountLedger) || !isset($discountLedgerGroup)) {
                        $ledgerErrorStatus = FinancialPostingHelper::ERROR_PREFIX . 'Discount Account not setup';
                        break;
                    }
                    $existingDiscountLedger = array_filter($postingArray[FinancialPostingHelper::DISCOUNT_ACCOUNT], function ($posting) use ($discountLedgerId, $discountLedgerGroupId) {
                        return $posting['ledger_id'] == $discountLedgerId && $posting['ledger_group_id'] === $discountLedgerGroupId;
                    });
                    //Ledger found
                    if (count($existingDiscountLedger) > 0) {
                        $postingArray[FinancialPostingHelper::DISCOUNT_ACCOUNT][0]['debit_amount'] += $discount->ted_amount;
                    } else { //Assign a new ledger
                        array_push($postingArray[FinancialPostingHelper::DISCOUNT_ACCOUNT], [
                            'ledger_id' => $discountLedgerId,
                            'ledger_group_id' => $discountLedgerGroupId,
                            'ledger_code' => $discountLedger?->code,
                            'ledger_name' => $discountLedger?->name,
                            'ledger_group_code' => $discountLedgerGroup?->name,
                            'debit_amount' => $discount->ted_amount,
                            'credit_amount' => 0,
                        ]);
                    }
                }
            }
            //Payment Terms breakup
            //Break Customer Account according to payment terms schedule - due date wise
            $invoicePaymentTerms = $document->payment_term_schedules()
                ->select('due_date', DB::raw('SUM(percent) as total_percentage'))->groupBy('due_date')->get();
            $totalPaymentTermsAmount = 0;
            if ($invoicePaymentTerms && count($invoicePaymentTerms)) {
                foreach ($invoicePaymentTerms as $invoicePaymentTerm) {
                    $currentAmount = $customerAccountDebit * ($invoicePaymentTerm->total_percentage / 100);
                    $totalPaymentTermsAmount += $currentAmount;
                    //Check for same ledger and group in CUSTOMER ACCOUNT
                    $existingcustomerLedger = array_filter($postingArray[FinancialPostingHelper::CUSTOMER_ACCOUNT], function ($posting) use ($customerLedgerId, $customerLedgerGroupId, $invoicePaymentTerm) {
                        return $posting['ledger_id'] == $customerLedgerId && $posting['ledger_group_id'] === $customerLedgerGroupId && $posting['due_date'] === $invoicePaymentTerm->due_date;
                    });
                    //Ledger found
                    if (count($existingcustomerLedger) > 0) {
                        $postingArray[FinancialPostingHelper::CUSTOMER_ACCOUNT][0]['debit_amount'] += $currentAmount;
                    } else { //Assign a new ledger
                        array_push($postingArray[FinancialPostingHelper::CUSTOMER_ACCOUNT], [
                            'ledger_id' => $customerLedgerId,
                            'ledger_group_id' => $customerLedgerGroupId,
                            'ledger_code' => $customerLedger?->code,
                            'ledger_name' => $customerLedger?->name,
                            'ledger_group_code' => $customerLedgerGroup?->name,
                            'debit_amount' => $currentAmount,
                            'credit_amount' => 0,
                            'due_date' => $invoicePaymentTerm->due_date,
                        ]);
                    }
                }
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

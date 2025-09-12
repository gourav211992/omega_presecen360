<?php

namespace App\Http\Controllers\API\Integration;

use App\Models\ErpStagingFurbooksLedger;
use App\Models\ErpLedgerFurbook;
use App\Models\ErpCurrency;
use App\Models\Voucher;
use App\Models\ItemDetail;
use App\Models\Ledger;
use App\Models\Book;
use App\Models\AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Helper;
use App\Helpers\CurrencyHelper;
use App\Helpers\ConstantHelper;

class FurbooksController
{
    public function create(Request $request)
    {
        $request->validate([
            'furbooks' => 'required|array|min:1',
            'furbooks.*.furbook_code' => 'required|string',
            'furbooks.*.document_date' => 'required|date',
            'furbooks.*.location_id' => 'required|integer',
            'furbooks.*.organization_id' => 'required|integer',
            'furbooks.*.currency_code' => 'required|string',
            'furbooks.*.credit_amount' => 'required|numeric',
            'furbooks.*.debit_amount' => 'required|numeric',
            'furbooks.*.cost_center' => 'nullable|string',
            'furbooks.*.remark' => 'nullable|string',
            'furbooks.*.final_remark' => 'nullable|string',
        ]);

        $furbooksData = $request->input('furbooks');

        DB::beginTransaction();
        $processedData = [];

        try {
            foreach ($furbooksData as $data) {
                $stagingFurbooks = ErpStagingFurbooksLedger::create([
                    'furbooks_code' => $data['furbook_code'],
                    'document_date' => $data['document_date'],
                    'location_id' => $data['location_id'],
                    'organization_id' => $data['organization_id'],
                    'currency_code' => $data['currency_code'],
                    'credit_amount' => $data['credit_amount'],
                    'debit_amount' => $data['debit_amount'],
                    'cost_center' => $data['cost_center'] ?? null,
                    'remark' => $data['remark'] ?? null,
                    'final_remark' => $data['final_remark'] ?? null,
                    'status' => 'Success',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $processedData[] = $stagingFurbooks;
            }

            // Process transfer inside the same transaction
            $this->transferToVoucher($processedData);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Furbooks processed and transferred successfully.',
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();

            \Log::error('Furbooks Create Process Failed: ' . $ex->getMessage(), [
                'line' => $ex->getLine(),
                'file' => $ex->getFile(),
                'trace' => $ex->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing furbooks.',
                'error' => $ex->getMessage(),
            ], 500);
        }
    }

    private function transferToVoucher($processedData)
    {
        foreach ($processedData as $furbook) {
            try {
                $matchedData = $this->findFurbookMatch($furbook->furbooks_code);
                if (!$matchedData) {
                    throw new \Exception('No matching furbook found for code: ' . $furbook->furbooks_code);
                }

                $documentDate = $furbook->document_date ?? now()->format('Y-m-d');

                $userLogin = AuthUser::where('organization_id', $furbook->organization_id)
                    ->where('user_type', 'IAM-SUPER')
                    ->firstOrFail();

                Auth::guard('web')->login($userLogin);

                $numberPatternData = Helper::generateDocumentNumberNew($matchedData['book_id'], $documentDate);

                if (!$numberPatternData) {
                    throw new \Exception('Failed to generate document number.');
                }

                $currency = ErpCurrency::where('short_name', $furbook->currency_code)->firstOrFail();
                $exchangeRates = CurrencyHelper::getCurrencyExchangeRates($currency->id, $documentDate);

                $user = Helper::getAuthenticatedUser();
                $organization = $user->organization;

                $book = Book::findOrFail($matchedData['book_id']);

                $voucher = Voucher::withoutGlobalScopes()
                    ->where('voucher_no', trim($numberPatternData['document_number']))
                    ->where('organization_id', (int)$furbook->organization_id)
                    ->where('group_id', (int)$organization->group_id)
                    ->first();

                $ledgerData = Ledger::findOrFail($matchedData['ledger_id']);

                if ($voucher) {
                    $this->createItemDetail($voucher->id, $matchedData, $furbook, $organization, $ledgerData, $documentDate);
                } else {
                    $voucher = $this->createVoucher(
                        $numberPatternData,
                        $book,
                        $currency,
                        $exchangeRates,
                        $furbook,
                        $organization,
                        $userLogin
                    );

                    $this->createItemDetail($voucher->id, $matchedData, $furbook, $organization, $ledgerData, $documentDate);
                }

                $furbook->update([
                    'status' => 'Transferred',
                    'updated_at' => now(),
                ]);

            } catch (\Exception $ex) {
                $this->handleTransferError($furbook, $ex);
                // No rollback here — will roll back the outer transaction
            }
        }
    }


    private function createVoucher($numberPatternData, $book, $currency, $exchangeRates, $furbook, $organization, $userLogin)
    {
        $userData = Helper::userCheck();

        $voucher = new Voucher();
        $voucher->voucher_no = $numberPatternData['document_number'];
        $voucher->voucher_name = $book->book_name;
        $voucher->book_id = $book->id;
        $voucher->currency_id = $currency->id;
        $voucher->currency_code = $furbook->currency_code;

        if ($exchangeRates) {
            $voucher->org_currency_id = $exchangeRates['organization_currency_id'] ?? null;
            $voucher->org_currency_code = $exchangeRates['organization_currency_code'] ?? null;
            $voucher->org_currency_exg_rate = $exchangeRates['organization_exchange_rate'] ?? null;
            $voucher->comp_currency_id = $exchangeRates['company_currency_id'] ?? null;
            $voucher->comp_currency_code = $exchangeRates['company_currency_code'] ?? null;
            $voucher->comp_currency_exg_rate = $exchangeRates['company_exchange_rate'] ?? null;
            $voucher->group_currency_id = $exchangeRates['group_currency_id'] ?? null;
            $voucher->group_currency_code = $exchangeRates['group_currency_code'] ?? null;
            $voucher->group_currency_exg_rate = $exchangeRates['group_exchange_rate'] ?? null;
        }

        $voucher->date = $furbook->document_date;
        $voucher->document_date = $furbook->document_date;
        $voucher->amount = $furbook->debit_amount + $furbook->credit_amount;
        $voucher->remarks = $furbook->remarks ?? '';
        $voucher->organization_id = $furbook->organization_id;
        $voucher->group_id = $organization->group_id;
        $voucher->company_id = $organization->company_id;
        $voucher->location = $furbook->location_id;
        $voucher->revision_number = 0;
        $voucher->doc_no = $numberPatternData['doc_no'] ?? null;
        $voucher->doc_number_type = $numberPatternData['type'] ?? null;
        $voucher->doc_reset_pattern = $numberPatternData['reset_pattern'] ?? null;
        $voucher->doc_prefix = $numberPatternData['prefix'] ?? null;
        $voucher->doc_suffix = $numberPatternData['suffix'] ?? null;
        $voucher->approvalStatus = ConstantHelper::APPROVED;
        $voucher->approvalLevel = 1;
        $voucher->created_by = $userLogin->id;
        $voucher->voucherable_id = $userLogin->id;
        $voucher->voucherable_type = $userData['user_type'] ?? null;

        $voucher->save();

        return $voucher;
    }

    private function createItemDetail($voucherId, $matchedData, $furbook, $organization, $ledgerData, $documentDate)
    {
        ItemDetail::create([
            'voucher_id' => $voucherId,
            'ledger_id' => $matchedData['ledger_id'],
            'debit_amt' => $furbook->debit_amount,
            'credit_amt' => $furbook->credit_amount,
            'debit_amt_org' => $furbook->debit_amount,
            'credit_amt_org' => $furbook->credit_amount,
            'debit_amt_comp' => $furbook->debit_amount,
            'credit_amt_comp' => $furbook->credit_amount,
            'debit_amt_group' => $furbook->debit_amount,
            'credit_amt_group' => $furbook->credit_amount,
            'ledger_parent_id' => $matchedData['ledger_group_id'],
            'cost_center_id' => $ledgerData->cost_center_id,
            'notes' => null,
            'date' => $documentDate,
            'organization_id' => $organization->id,
            'group_id' => $organization->group_id,
            'company_id' => $organization->company_id,
            'remarks' => null,
        ]);
    }

    private function findFurbookMatch($inputFurbookCode)
    {
        return ErpLedgerFurbook::all()->flatMap(function ($furbook) {
            return json_decode($furbook->ledgers, true) ?? [];
        })->firstWhere('furbook_code', $inputFurbookCode);
    }

    private function handleTransferError($furbook, \Exception $ex)
    {
        $errorMessage = sprintf(
            'Error: %s | Line: %s | File: %s',
            $ex->getMessage(),
            $ex->getLine(),
            $ex->getFile()
        );

        ErpStagingFurbooksLedger::where('id', $furbook->id)
            ->update([
                'remarks' => $errorMessage,
                'updated_at' => now(),
            ]);

        Log::error("Furbook Transfer Failed (ID: {$furbook->id}): $errorMessage");
    }
}

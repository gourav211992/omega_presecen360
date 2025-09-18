<?php

namespace App\Imports;

use App\Helpers\Helper;
use App\Models\ErpVoucherUpload;
use App\Models\Ledger;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class VoucherImportData implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading
{
    protected $bookId;
    protected $documentNumberType;
    protected $bookTypeId;
    protected $documentDate;
    protected $location;
    protected $currencyId;
    protected $exchangeRate;
    protected $orgCurrencyId;
    protected $compCurrencyId;
    protected $groupCurrencyId;
    protected $orgExchangeRate;
    protected $compExchangeRate;
    protected $groupExchangeRate;
    protected $successfulVouchers = [];
    protected $failedVouchers = [];

    // Cache maps
    protected $ledgerMap;
    protected $costCenterMap;

    public function __construct(
        $bookId,
        $bookTypeId,
        $documentDate,
        $location,
        $currencyId,
        $documentNumberType,
        $exchangeRate,
        $orgCurrencyId,
        $compCurrencyId,
        $groupCurrencyId,
        $orgExchangeRate,
        $compExchangeRate,
        $groupExchangeRate
    ) {
        $this->bookId            = $bookId;
        $this->bookTypeId        = $bookTypeId;
        $this->documentDate      = $documentDate;
        $this->location          = $location;
        $this->currencyId        = $currencyId;
        $this->documentNumberType= $documentNumberType;
        $this->exchangeRate      = $exchangeRate;
        $this->orgCurrencyId     = $orgCurrencyId;
        $this->compCurrencyId    = $compCurrencyId;
        $this->groupCurrencyId   = $groupCurrencyId;
        $this->orgExchangeRate   = $orgExchangeRate;
        $this->compExchangeRate  = $compExchangeRate;
        $this->groupExchangeRate = $groupExchangeRate;

        // Preload ledgers and cost centers
        $this->ledgerMap     = Ledger::all()->keyBy('code');
        $this->costCenterMap = CostCenter::pluck('name', 'id');
    }

    public function onSuccess($voucherUpload, $ledgerName)
    {
        $this->successfulVouchers[] = [
            'ledger_code'   => $voucherUpload->ledger_code,
            'ledger_name'   => $ledgerName,
            'debit_amount'  => $voucherUpload->debit_amount,
            'credit_amount' => $voucherUpload->credit_amount,
            'cost_center'   => $voucherUpload->cost_center_id,
            'row_number'    => $voucherUpload->row_number,
            'status'        => 'success',
            'remarks'       => $voucherUpload->remark,
        ];
    }

    public function onFailure($voucherUpload, $ledgerName = null)
    {
        $this->failedVouchers[] = [
            'ledger_code'   => $voucherUpload->ledger_code,
            'ledger_name'   => $ledgerName ?? $voucherUpload->ledger_code,
            'debit_amount'  => $voucherUpload->debit_amount,
            'credit_amount' => $voucherUpload->credit_amount,
            'cost_center'   => $voucherUpload->cost_center_id,
            'row_number'    => $voucherUpload->row_number,
            'status'        => 'failed',
            'remarks'       => $voucherUpload->reason, 
        ];
    }


    public function getSuccessfulVouchers()
    {
        return $this->successfulVouchers;
    }

    public function getFailedVouchers()
    {
        return $this->failedVouchers;
    }

    public function collection(Collection $rows)
{
    $user = Helper::getAuthenticatedUser();

    Log::info('VoucherImport: Starting import process', [
        'user_id'        => $user->auth_user_id,
        'organization_id'=> $user->organization_id,
        'total_rows'     => $rows->count()
    ]);

   
    ErpVoucherUpload::where('created_by', $user->auth_user_id)->delete();

    
    $rows = $rows->values()->map(function ($row, $i) {
        $row['row_number'] = $i + 2; 
        return $row;
    });

    DB::transaction(function () use ($rows, $user) {
        for ($i = 0; $i < $rows->count(); $i += 2) {
            $row1 = $rows[$i];
            $row2 = $rows[$i + 1] ?? null;

           
            if (!$row2) {
                $this->createFailedVoucher($row1, $user, ["Voucher must contain exactly 2 entries"]);
                $this->createFailedVoucher(['row_number' => $row1['row_number'] + 1], $user, ["Missing second entry for voucher pair"]);
                continue;
            }

            // Identify debit and credit
            if ($row1['debit_amount'] > 0 && $row2['credit_amount'] > 0) {
                $debitRow  = $row1;
                $creditRow = $row2;
            } elseif ($row2['debit_amount'] > 0 && $row1['credit_amount'] > 0) {
                $debitRow  = $row2;
                $creditRow = $row1;
            } else {
                $this->createFailedVoucher($row1, $user, ["Invalid pair: must be one debit and one credit"]);
                $this->createFailedVoucher($row2, $user, ["Invalid pair: must be one debit and one credit"]);
                continue;
            }

           
            if (floatval($debitRow['debit_amount']) != floatval($creditRow['credit_amount'])) {
                $this->createFailedVoucher($debitRow, $user, ["Debit and credit amounts do not match"]);
                $this->createFailedVoucher($creditRow, $user, ["Debit and credit amounts do not match"]);
                continue;
            }

           
            $ledgerDebit  = $this->ledgerMap[$debitRow['ledger_code']] ?? null;
            $ledgerCredit = $this->ledgerMap[$creditRow['ledger_code']] ?? null;

          
            if (!$ledgerDebit || !$ledgerCredit) {
                if (!$ledgerDebit) {
                    $this->createFailedVoucher($debitRow, $user, ["Ledger not found: " . ($debitRow['ledger_code'] ?? 'N/A')]);
                }
                if (!$ledgerCredit) {
                    $this->createFailedVoucher($creditRow, $user, ["Ledger not found: " . ($creditRow['ledger_code'] ?? 'N/A')]);
                }
                continue;
            }

          
            $debitRecord = ErpVoucherUpload::create([
                'book_id'        => $this->bookId,
                'book_type_id'   => $this->bookTypeId,
                'document_date'  => $this->documentDate,
                'location'       => $this->location,
                'currency_id'    => $this->currencyId,
                'exchange_rate'  => $this->exchangeRate,
                'doc_number_type'=> $this->documentNumberType,
                'ledger_id'      => $ledgerDebit->id,
                'ledger_code'    => $ledgerDebit->code,
                'ledger_name'    => $ledgerDebit->name,
                'group_id'       => $ledgerDebit->group_id ?? null,
                'debit_amount'   => $debitRow['debit_amount'],
                'credit_amount'  => 0,
                'cost_center_id' => $debitRow['cost_center_id'] ?? null,
                'remark'         => $debitRow['final_remark'] ?? '',
                'row_number'     => $debitRow['row_number'],
                'migrate_status' => 0,
                'created_by'     => $user->auth_user_id,
                'organization_id'=> $user->organization_id,
            ]);

            
            $creditRecord = ErpVoucherUpload::create([
                'book_id'        => $this->bookId,
                'book_type_id'   => $this->bookTypeId,
                'document_date'  => $this->documentDate,
                'location'       => $this->location,
                'currency_id'    => $this->currencyId,
                'exchange_rate'  => $this->exchangeRate,
                'doc_number_type'=> $this->documentNumberType,
                'ledger_id'      => $ledgerCredit->id,
                'ledger_code'    => $ledgerCredit->code,
                'ledger_name'    => $ledgerCredit->name,
                'group_id'       => $ledgerCredit->group_id ?? null,
                'debit_amount'   => 0,
                'credit_amount'  => $creditRow['credit_amount'],
                'cost_center_id' => $creditRow['cost_center_id'] ?? null,
                'remark'         => $creditRow['final_remark'] ?? '',
                'row_number'     => $creditRow['row_number'],
                'migrate_status' => 0,
                'created_by'     => $user->auth_user_id,
                'organization_id'=> $user->organization_id,
            ]);

            $this->onSuccess($debitRecord, $ledgerDebit->name);
            $this->onSuccess($creditRecord, $ledgerCredit->name);
        }
    });
}


    public function chunkSize(): int
    {
        return 100;
    }

    private function createFailedVoucher($row, $user, $errors)
    {
        $reason = is_array($errors) ? implode(', ', $errors) : $errors;

        $voucherUpload = ErpVoucherUpload::create([
            'book_id'         => $this->bookId,
            'book_type_id'    => $this->bookTypeId,
            'document_date'   => $this->documentDate,
            'location'        => $this->location,
            'currency_id'     => $this->currencyId,
            'exchange_rate'   => $this->exchangeRate,
            'doc_number_type' => $this->documentNumberType,
            'ledger_code'     => $row['ledger_code'] ?? null,
            'debit_amount'    => $row['debit_amount'] ?? 0,
            'credit_amount'   => $row['credit_amount'] ?? 0,
            'cost_center_name'=> $this->costCenterMap[$row['cost_center_id']] ?? null,
            'cost_center_id'  => $row['cost_center_id'] ?? null,
            'remark'          => $row['final_remark'] ?? '',
            'row_number'      => $row['row_number'],
            'reason'          => $reason,   
            'migrate_status'  => 1,
            'created_by'      => $user->auth_user_id,
            'organization_id' => $user->organization_id,
        ]);

        $this->onFailure($voucherUpload);
    }

}

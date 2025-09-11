<?php

namespace App\Http\Controllers\API\Integration;

use App\Models\ErpStagingFurbooksLedger;
use App\Imports\FurbooksImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\ImportComplete;
use Illuminate\Support\Facades\Validator;
use App\Models\ErpLedgerFurbook;
use Carbon\Carbon;
use App\Http\Controllers\BookController;
use App\Models\ErpCurrency;
use App\Helpers\CurrencyHelper;
use App\Models\Voucher;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Jobs\TransferFurbooksToVoucher;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Helpers\ServiceParametersHelper;
use App\Models\BookType;
use App\Models\Book;
use App\Helpers\InventoryHelper;
use App\Helpers\ConstantHelper;
use App\Models\DynamicField;
use App\Models\DynamicFieldDetail;
use App\Models\AuthUser;
use App\Models\User;
use App\Helpers\DynamicFieldHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\ItemDetail;
use App\Models\Ledger;

class FurbooksController
{
    // public function create(Request $request)
    // {
    //     $request->validate([
    //     'file' => 'required|mimes:xlsx,xls|max:30720',
    //     ]);

    //     if (!$request->hasFile('file')) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No file uploaded.',
    //         ], 400);
    //     }

    //     $file = $request->file('file');

    //     try {
    //         $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    //     } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Invalid or corrupted Excel file.',
    //         ], 400);
    //     }

    //     $sheet = $spreadsheet->getActiveSheet();
    //     $rowCount = $sheet->getHighestRow() - 1;

    //     if ($rowCount > 10000) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'File contains more than 10000 items.',
    //         ], 400);
    //     }

    //     if ($rowCount < 1) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'The uploaded file is empty.',
    //         ], 400);
    //     }

    //     $import = new FurbooksImport();
    //     Excel::import($import, $file);

    //     $successfulFurbooks = $import->getSuccessfulFurbooks();
    //     $failedFurbooks = $import->getFailedFurbooks();

    //     $status = count($failedFurbooks) > 0 ? 'failure' : 'success';
    //     $message = count($failedFurbooks) > 0 
    //         ? 'Furbooks import completed with some failures.' 
    //         : 'Furbooks import completed successfully.';
        
    //     // TransferFurbooksToVoucher::dispatch();
    //      $this->transferToVoucher();
    //    return response()->json([
    //         'status' => $status,
    //         'message' => $message,
    //         'data' => [
    //             'successful_customers' => $successfulFurbooks,
    //             'failed_customers' => $failedFurbooks,
    //         ]
    //     ], 200);
    // }
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

        foreach ($furbooksData as $data) {
            ErpStagingFurbooksLedger::create([
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
                'status' => 'Success',  // assuming initial status
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Now trigger your existing voucher transfer
        $this->transferToVoucher();

        return response()->json([
            'status' => true,
            'message' => 'Furbooks processed successfully.',
        ]);
    }


        public function transferToVoucher()
{
    $stagingFurbooks = ErpStagingFurbooksLedger::where('status', 'Success')->get();

    foreach ($stagingFurbooks as $key => $furbook) {
        DB::beginTransaction();

        try {
            $matchedData = $this->findFurbookMatch($furbook->furbooks_code);

            if (!$matchedData) {
                throw new \Exception('Matching furbook not found.');
            }

            $document_date = $furbook->document_date ?? now()->format('Y-m-d');

            $userlogin = AuthUser::where('organization_id', $furbook->organization_id)
                ->where('user_type', 'IAM-SUPER')
                ->first();
            if (!$userlogin) {
                throw new \Exception('User not found for organization_id: ' . $furbook->organization_id);
            }

            Auth::guard('web')->login(AuthUser::find($userlogin->id));

            $numberPatternData = Helper::generateDocumentNumberNew( $matchedData['book_id'], $document_date);
            if (!isset($numberPatternData)) {
                return response()->json([
                    'message' => "Invalid Book",
                    'error' => "",
                ], 422);
            }

            // Currency & exchange rate
            $currency = ErpCurrency::where('short_name', $furbook->currency_code)->first();
            if (!$currency) {
                throw new \Exception('Currency not found: ' . $furbook->currency_code);
            }

            $exchangeRates = CurrencyHelper::getCurrencyExchangeRates($currency->id, $document_date);

            $user = Helper::getAuthenticatedUser();
            $organization = $user->organization;

            $book = Book::find($matchedData['book_id']);

          $voucherQuery = Voucher::withoutGlobalScopes()->where('voucher_no', trim($numberPatternData['document_number']))
            ->where('organization_id', (int)$furbook->organization_id)
            ->where('group_id', (int)$organization->group_id)->first();

            $data = Ledger::where('id', $matchedData['ledger_id'])->first();
           
            if(!empty($voucher))
            {
                ItemDetail::create([
                        'voucher_id' => $voucher->id,
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
                        'cost_center_id' =>$data->cost_center_id,
                        'notes' => null,
                        'date' => $document_date,
                        'organization_id' => $organization->id,
                        'group_id' => $organization->group_id,
                        'company_id' => $organization->company_id,
                        'remarks' => null
                    ]);

                // Mark staging furbook as transferred
                $furbook->update([
                    'status' => 'Transferred',
                    'updated_at' => now(),
                ]);
            }
            else
            {

                // Create voucher
                $voucher = new Voucher();
                $voucher->voucher_no = $numberPatternData['document_number'] ?? null;
                $voucher->voucher_name = $book->book_name ?? '';
                $voucher->book_id = $book->id;
                $voucher->currency_id = $currency->id;
                $voucher->currency_code = $furbook->currency_code;

                if ($exchangeRates) 
                {
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

                // Date & amount
                $voucher->date = $document_date;
                $voucher->document_date = $document_date;
                $voucher->amount = $furbook->amount ?? 0;
                $voucher->remarks = $furbook->remarks ?? '';

                // Common fields
                $voucher->organization_id = $furbook->organization_id;
                $voucher->group_id = $organization->group_id ?? null;
                $voucher->company_id = $organization->company_id ?? null;
                $voucher->location = $furbook->location_id ?? null;
                $voucher->revision_number = 0;

                // Document number fields
                $voucher->doc_no = $numberPatternData['doc_no'] ?? null;
                $voucher->doc_number_type = $numberPatternData['type'] ?? null;
                $voucher->doc_reset_pattern = $numberPatternData['reset_pattern'] ?? null;
                $voucher->doc_prefix = $numberPatternData['prefix'] ?? null;
                $voucher->doc_suffix = $numberPatternData['suffix'] ?? null;

                // Approval fields
                $voucher->approvalStatus = ConstantHelper::APPROVED;
                $voucher->approvalLevel = 1;
                $voucher->created_by = $userlogin->id;

                // User fields
                $userData = Helper::userCheck();
                $voucher->voucherable_id = $userlogin->id;
                $voucher->voucherable_type = $userData['user_type'] ?? null;

                $voucher->save();

                ItemDetail::create([
                        'voucher_id' => $voucher->id,
                        'ledger_id' => $matchedData['ledger_id'],
                        'debit_amt' => $furbook->debit_amount,
                        'credit_amt' => $furbook->credit_amount,
                        'debit_amt_org' =>$furbook->debit_amount,
                        'credit_amt_org' =>$furbook->credit_amount,
                        'debit_amt_comp' => $furbook->debit_amount,
                        'credit_amt_comp' =>$furbook->credit_amount,
                        'debit_amt_group' => $furbook->debit_amount,
                        'credit_amt_group' => $furbook->credit_amount,
                        'ledger_parent_id' => $matchedData['ledger_group_id'],
                        'cost_center_id' =>$data->cost_center_id,
                        'notes' => null,
                        'date' => $document_date,
                        'organization_id' => $organization->id,
                        'group_id' => $organization->group_id,
                        'company_id' => $organization->group_id,
                        'remarks' => null
                    ]);

                // Mark staging furbook as transferred
                $furbook->update([
                    'status' => 'Transferred',
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

        } catch (\Exception $ex) {
            DB::rollBack();

            // Log the error in remarks column
            ErpStagingFurbooksLedger::where('id', $furbook->id)
            ->update([
                'remarks' => 'Error: ' . $ex->getMessage() . ' | Line: ' . $ex->getLine() . ' | File: ' . $ex->getFile(),
                'updated_at' => now(),
            ]);

            // Optional: also log to Laravel log
            \Log::error('Error (ID: ' . $furbook->id . '): ' . $ex->getMessage() . ' | Line: ' . $ex->getLine() . ' | File: ' . $ex->getFile());
            \Log::error('Furbook transfer failed (ID: ' . $furbook->id . '): ' . $ex->getMessage() . ' | Line: ' . $ex->getLine() . ' | File: ' . $ex->getFile());
        }
    }
}


        public function findFurbookMatch($inputFurbookCode)
        {
            $furbooks = ErpLedgerFurbook::all();

            foreach ($furbooks as $furbook) {
                if ($furbook && $furbook->ledgers) {
                    $ledgers = json_decode($furbook->ledgers, true);

                    $matched = collect($ledgers)->firstWhere('furbook_code', $inputFurbookCode);

                    if ($matched) {
                        return $matched;
                    }
                }
            }

            return null;
        }




}
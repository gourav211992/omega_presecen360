<?php

namespace App\Imports;

use App\Models\ErpStagingFurbooksLedger;
use App\Helpers\Helper;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class FurbooksImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $successfulFurbooks = [];
    protected $failedFurbooks = [];

    public function chunkSize(): int
    {
        return 500;
    }

    public function onSuccess($furbook)
    {
        $this->successfulFurbooks[] = [
            'furbooks_code' => $furbook->furbooks_code,
            'debit_amount' => $furbook->debit_amount,
            'credit_amount' => $furbook->credit_amount,
            'amount' => $furbook->amount,
            'status' => 'success',
            'remarks' => 'Successfully uploaded',
        ];
    }

    public function onFailure($uploadedFurbook)
    {
        $this->failedFurbooks[] = [
            'furbooks_code' => $uploadedFurbook->furbooks_code,
            'debit_amount' => $uploadedFurbook->debit_amount,
            'credit_amount' => $uploadedFurbook->credit_amount,
            'amount' => $uploadedFurbook->amount,
            'status' => 'failed',
            'remarks' => $uploadedFurbook->remarks,
        ];
    }

    public function getSuccessfulFurbooks()
    {
        return $this->successfulFurbooks;
    }

    public function getFailedFurbooks()
    {
        return $this->failedFurbooks;
    }

    public function collection(Collection $rows)
    {
        $uploadedFurbooks = collect(); 
        foreach ($rows as $rowIndex => $row) {
            DB::beginTransaction();
            $uploadedFurbook = null;
            
            try {
                // Validate required fields
                $validator = Validator::make($row->toArray(), [
                    'location_id' => 'required|integer',
                    'organization_id' => 'required|integer',
                    'currency_code' => 'required',
                    'furbook_code' => 'required',
                    'debit_amount' => 'nullable|numeric|min:0',
                    'credit_amount' => 'nullable|numeric|min:0',
                    'amount' => 'nullable|numeric',
                    'document_date' => 'nullable',
                    'cost_center' => 'nullable',
                    'remark' => 'nullable',
                    'final_remark' => 'nullable',
                ]);


                if ($validator->fails()) {
                    throw new Exception('Validation failed: ' . implode(', ', $validator->errors()->all()));
                }

               
                $debitAmount = (float) ($row['debit_amount'] ?? 0);
                $creditAmount = (float) ($row['credit_amount'] ?? 0);
                
                // Business Rule: If credit amount > 0, then debit amount = 0
                // If debit amount > 0, then credit amount = 0
                if ($creditAmount > 0) {
                    $debitAmount = 0;
                    $amount = $row['amount'] ?? (-$creditAmount); 
                } elseif ($debitAmount > 0) {
                    $creditAmount = 0;
                    $amount = $row['amount'] ?? $debitAmount; 
                } else {
                    // Both are 0 or not provided
                    $amount = $row['amount'] ?? 0;
                }

                $documentDate = isset($row['document_date']) ? ExcelDate::excelToDateTimeObject($row['document_date'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                
                // Create staging record
                $uploadedFurbook = ErpStagingFurbooksLedger::create([
                    'location_id' => $row['location_id'] ?? null,
                    'organization_id' => $row['organization_id'],
                    'currency_code' => $row['currency_code'] ?? 'USD',
                    'furbooks_code' => $row['furbook_code'] ?? 'UNKNOWN_' . $rowIndex, // Using furbook_code from Excel
                    'cost_center' => $row['cost_center'] ?? null,
                    'remark' => $row['remark'] ?? null,
                    'final_remark' => $row['final_remark'] ?? null,
                    'document_date' => $documentDate,
                    'debit_amount' => (float) ($row['debit_amount'] ?? 0),
                    'credit_amount' => (float) ($row['credit_amount'] ?? 0),
                    'amount' => (float) ($row['amount'] ?? 0),
                    'status' => 'Success',
                ]);

                DB::commit();
                
                if ($uploadedFurbook) {
                    $uploadedFurbooks->push($uploadedFurbook);
                    $this->onSuccess($uploadedFurbook);
                }

            } catch (Exception $e) {
                DB::rollback();
                
                Log::error("Error importing furbook: " . $e->getMessage(), [
                    'error' => $e,
                    'row' => $row->toArray(),
                    'row_index' => $rowIndex
                ]);

                $errorMessage = $e->getMessage();  // Capture exception message

                // Create failed record in try-catch to handle any creation errors
                try 
                {
                    $documentDate = isset($row['document_date']) ? ExcelDate::excelToDateTimeObject($row['document_date'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');

                    $uploadedFurbook = ErpStagingFurbooksLedger::create([
                        'location_id' => $row['location_id'] ?? null,
                        'organization_id' => $row['organization_id'],
                        'currency_code' => $row['currency_code'] ?? 'USD',
                        'furbooks_code' => $row['furbook_code'] ?? 'UNKNOWN_' . $rowIndex, // Using furbook_code from Excel
                        'cost_center' => $row['cost_center'] ?? null,
                        'remark' => $row['remark'] ?? null,
                        'final_remark' => $row['final_remark'] ?? null,
                        'document_date' =>$documentDate,
                        'debit_amount' => (float) ($row['debit_amount'] ?? 0),
                        'credit_amount' => (float) ($row['credit_amount'] ?? 0),
                        'amount' => (float) ($row['amount'] ?? 0),
                        'status' => 'Failed',
                        'remarks' => $e->getMessage(),  // Save exact error reason
                    ]);

                    if ($uploadedFurbook) {
                        $uploadedFurbooks->push($uploadedFurbook);
                        $this->onFailure($uploadedFurbook);
                    }
                } catch (Exception $failedRecordException) {
                    Log::error("Failed to create failed record: " . $failedRecordException->getMessage());
                }
            }
        }

        return $uploadedFurbooks;
    }
}

<?php
namespace App\Jobs;

use App\Models\ErpStagingFurbooksLedger;
use App\Models\Voucher;
use App\Models\ErpCurrency;
use App\Http\Controllers\BookController;
use App\Helpers\Helper;
use App\Helpers\CurrencyHelper;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransferFurbooksToVoucher implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $stagingFurbooks = ErpStagingFurbooksLedger::where('status', 'Success')->get();

        foreach ($stagingFurbooks as $furbook) {
            DB::beginTransaction();

            try {
                // Example matching logic
                $matchedData = Helper::findFurbookMatch($furbook->furbooks_code);
                if (!$matchedData) {
                    throw new Exception('Matching furbook not found.');
                }

                // Generate document no, currency exchange rates, etc.
                $bookController = new BookController();
                $docRequest = new \Illuminate\Http\Request([
                    'book_id' => $matchedData['book_id'],
                    'document_date' => $furbook->document_date ?? Carbon::now()->format('Y-m-d')
                ]);

                $docResponse = $bookController->getBookDocNoAndParameters($docRequest);
                $docData = $docResponse->getData();
                if ($docData->status !== 200) {
                    throw new Exception('Document number generation failed.');
                }

                $currency = ErpCurrency::where('short_name', $furbook->currency_code)->first();
                if (!$currency) {
                    throw new Exception('Currency not found: ' . $furbook->currency_code);
                }

                $exchangeRates = CurrencyHelper::getCurrencyExchangeRates($currency->id, $furbook->document_date);

                // Example voucher creation logic
                $voucher = new Voucher();
                $voucher->voucher_no = $docData->data->doc->document_number;
                $voucher->voucher_name = 'Generated Voucher';
                $voucher->book_id = $matchedData['book_id'];
                $voucher->currency_id = $currency->id;
                $voucher->currency_code = $currency->code;
                $voucher->date = $furbook->document_date ?? Carbon::now()->format('Y-m-d');
                $voucher->document_date = $furbook->document_date ?? Carbon::now()->format('Y-m-d');
                $voucher->amount = $furbook->amount;
                $voucher->organization_id = $furbook->organization_id;
                $voucher->location = $furbook->location_id;
                $voucher->status = 'Created';
                $voucher->save();

                // Mark staging record as transferred
                $furbook->status = 'Transferred';
                $furbook->save();

                DB::commit();
            } catch (Exception $ex) {
                DB::rollback();

                Log::error("Furbook to Voucher transfer failed", [
                    'furbook_id' => $furbook->id,
                    'furbooks_code' => $furbook->furbooks_code,
                    'error' => $ex->getMessage()
                ]);

                // Update furbook status with failure reason
                // $furbook->status = 'Transfer Failed';
                $furbook->remarks = $ex->getMessage();
                $furbook->save();
            }
        }
    }
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\FurbooksController;

/*
|--------------------------------------------------------------------------
| Finance Routes
|--------------------------------------------------------------------------
|
| Here is where you can register finance routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group with "finance" prefix. Make something great!
|
*/


Route::middleware(['user.auth'])->group(function () {
    // ADD INSIDE AUTH MIDDLEWARE AS AUTH USER IS BEING USED INSIDE
    Route::get('/assign-menu', function () {
        $menuName = request()->menu_name ?? '';
        $menuAlias = request()->menu_alias ?? '';
        $serviceIds = request()->service_ids ?? '';
        if ($serviceIds) {
            $serviceIds = explode(',', $serviceIds);
        }
        return Helper::setMenuAccessToEmployee($menuName, $menuAlias, $serviceIds);
    });


    Route::get('voucher-import', [VoucherController::class, 'import'])->name('vouchers.import');
    Route::post('voucher-import-save', [VoucherController::class, 'importSave'])->name('vouchers.import.save');
    Route::get('voucher-import-error', [VoucherController::class, 'importError'])->name('vouchers.import.error');
    Route::get('voucher-import-success', [VoucherController::class, 'importSuccess'])->name('vouchers.import.success');
    Route::get('voucher-export-successful', [VoucherController::class, 'exportSuccessful'])->name('vouchers.export.successful');
    Route::get('voucher-export-failed', [VoucherController::class, 'exportFailed'])->name('vouchers.export.failed');
    Route::get('voucher-download-sample', [VoucherController::class, 'downloadSample'])->name('vouchers.download.sample');


    Route::prefix('furbooks')->group(function () {
        Route::get('/', [FurbooksController::class, 'index'])->name('furbooks.index');
        Route::post('/', [FurbooksController::class, 'store'])->name('furbooks.store');
        Route::delete('/{id}', [FurbooksController::class, 'destroy'])->name('furbooks.destroy');
        Route::post('furbook-ledger-search', [FurbooksController::class, 'furbook_ledgers_search'])->name('furbook-ledger-search');
        Route::get('/get-series', [FurbooksController::class, 'getSeries'])->name('furbooks.get-series');
        Route::get('/data', [FurbooksController::class, 'furbookdata'])->name('furbooks.data');
        Route::get('/transfer-to-voucher', [FurbooksController::class, 'transferToVoucher'])->name('furbooks.transfer.voucher');
    });

});


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\FurbooksController;
use App\Http\Controllers\Finance\GstrController;
use App\Http\Controllers\AdvancePaymentVoucherController;

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

    Route::post('advanceuploadVouchers', [AdvancePaymentVoucherController::class, 'uploadVouchers'])->name('advanceuploadVouchers');
    Route::get('advance-receipt-vouchers/{type}', [AdvancePaymentVoucherController::class, 'index'])->name('advancepaymentVoucher.receipt');
    Route::post('advanceapprovePaymentVoucher', [AdvancePaymentVoucherController::class, 'advanceapprovePaymentVoucher'])->name('advanceapprovePaymentVoucher');
    Route::post('advancegetParties', [AdvancePaymentVoucherController::class, 'advancegetParties'])->name('advancegetParties');
    Route::get('advancepaymentVouchersAmendment/{id}', [AdvancePaymentVoucherController::class, 'amendment'])->name('advancepaymentVouchers.amendment');
    Route::resource('advance-payments', AdvancePaymentVoucherController::class)->except(['show', 'destroy', 'edit']);
    Route::resource('advance-receipts', AdvancePaymentVoucherController::class)->except(['show', 'destroy', 'edit']);
    Route::get('advance-payments/{payment}/edit', [AdvancePaymentVoucherController::class, 'edit'])->name('advance-payments.edit');
    Route::get('advance-receipts/{payment}/edit', [AdvancePaymentVoucherController::class, 'edit'])->name('advance-receipts.edit');
    Route::get('/advancepayment-vouchers/voucher/get', [AdvancePaymentVoucherController::class, 'getPostingDetails'])->name('advancepaymentVouchers.getPostingDetails');
    Route::post('/advancepayment-vouchers/voucher/post', [AdvancePaymentVoucherController::class, 'postPostingDetails'])->name('advancepaymentVouchers.post');
    Route::get('/advancepayment-receipt/revoke', [AdvancePaymentVoucherController::class, 'revokeDocument'])->name('advancepaymentVouchers.revoke.document');
    Route::get('/advancepayment-receipt/cancel', [AdvancePaymentVoucherController::class, 'cancelDocument'])->name('advancepaymentVouchers.cancel.document');
    Route::get('/advancepayment-receipt/print/{id}/{ledger}/{group}', [AdvancePaymentVoucherController::class, 'getPrint'])->name('advancepaymentVouchers.print');
    Route::post('/advancepayment-receipt/email', [AdvancePaymentVoucherController::class, 'sendMail'])->name('advancepaymentVouchers.email');
    Route::post('/advancevoucher/check-reference', [AdvancePaymentVoucherController::class, 'checkReference'])->name('advancevoucher.checkReference');
    Route::post('advancegetLedgerVouchers', [AdvancePaymentVoucherController::class, 'getLedgerVouchers'])->name('advancegetLedgerVouchers');

    Route::controller(GstrController::class)->prefix('finance/gstr')->group(function () {
        Route::get('/gstr-3b', 'gstr3b')->name('finance.gstr.gstr-3b');
        Route::get('/gstr-3b-pdf', 'gstr3bPdf')->name('finance.gstr.gstr-3b-pdf');
    });

});




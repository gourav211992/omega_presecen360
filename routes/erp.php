<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ErpSaleReturnController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\ProductionSlip\ProductionSlipController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\SaleInvoiceController;

Route::middleware(['user.auth'])->group(function () {
    Route::prefix('erp-shifts')->controller(ShiftController::class)->group(function () {
        Route::get('', 'index')->name('shift.index');
        Route::get('create', 'create')->name('shift.create');
        Route::post('store', 'store')->name('shift.store');
        Route::get('edit/{id}', 'edit')->name('shift.edit');
        Route::delete('destroy/{id}', 'destroy')->name('shift.destroy');
    });

    Route::prefix('production-slip')->controller(ProductionSlipController::class)->group(function () {
        Route::get('{id}/labels', 'generateLabels')->name('production.slip.generate-labels');
        Route::get('{id}/qr', 'generateQr')->name('production.slip.generate-qr');
    });

    Route::post('/tcs/calculate', [ErpSaleReturnController::class, 'getTcsTax'])->name('return.tcs.calculate');
    Route::post('/tds/calculate', [PurchaseReturnController::class, 'getTdsTax'])->name('return.tds.calculate');
    Route::get('sales/customer/sub-store', [CustomerController::class, 'getCustomerSubStore']) -> name('sales.customer.subStore.get');
    Route::post('sales/invoice/cancel', [SaleInvoiceController::class, 'cancelDocument']) -> name('sale.invoice.cancel');

    Route::prefix('document-approval')
        ->name('document.approval.')
        ->controller(DocumentApprovalController::class)
        ->group(function () {
            Route::post('scrap', 'scrap')->name('scrap');
        });

    Route::prefix('purchase-return')
        ->name('purchase-return.')
        ->controller(PurchaseReturnController::class)
        ->group(function () {
        Route::post('/cancel-ewaybill', 'cancelEWayBill')->name('cancel-ewaybill');
    });
});
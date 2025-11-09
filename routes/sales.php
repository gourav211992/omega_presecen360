<?php

use App\Http\Controllers\Sales\CustomerConsigneeController;
use App\Http\Controllers\Sales\EInvoiceController;
use App\Http\Controllers\Sales\SalesAutoCompleteController;
use App\Http\Controllers\DocumentApprovalController;
use App\Http\Controllers\RepairOrderController;
use App\Http\Controllers\RcaController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Sales Routes
|--------------------------------------------------------------------------
|
| Here is where you can register sales routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group with "sales" prefix. Make something great!
|
*/

Route::group(['middleware' => ['user.auth']], function () {
     Route::get('/auto-complete/customer-consignee', [SalesAutoCompleteController::class, 'customerConsigneeList'])->name('autoComplete.customer.consignee');
     Route::get('/auto-complete/transporters', [SalesAutoCompleteController::class, 'transporterList'])->name('autoComplete.transporters');
     Route::get('/customer-consignee/addresses/{id}', [CustomerConsigneeController::class, 'getCustomerConsigneeAddresses'])->name('customer.consignee.addresses');
     Route::post('/cancel-e-invoice', [EInvoiceController::class, 'cancelEInvoice'])->name('cancel.e-invoice');
     Route::post('/cancel-ewb', [EInvoiceController::class, 'cancelEWayBill'])->name('cancel.ewb');

   Route::prefix('document-approval')
     ->name('document.approval.')
     ->controller(DocumentApprovalController::class)
     ->group(function () {
          Route::post('repairOrder', 'repairOrder')->name('repairOrder');
          Route::post('rca', 'rca')->name('rca');
     });

  Route::prefix('repair-order')->controller(RepairOrderController::class)->group(function () {
        Route::get('/', 'index')->name('repair-order.index');
        Route::get('/{id}/edit', 'edit')->name('repair-order.edit');
        Route::put('/{id}', 'update')->name('repair-order.update');
        Route::delete('/{id}', 'destroy')->name('repair-order.destroy');
        Route::get('revoke-document', 'revokeDocument')->name('repair-order.revoke');
    });

    Route::prefix('rca')->controller(RcaController::class)->group(function () {
        Route::get('/', 'index')->name('rca.index');                  
        Route::get('/{id}/edit', 'edit')->name('rca.edit');       
        Route::put('/{id}', 'update')->name('rca.update');          
        Route::delete('/{id}', 'destroy')->name('rca.destroy');     
        Route::get('revoke-document', 'revokeDocument')->name('rca.revoke'); 
        Route::post('/update-remark', 'updateRemark')->name('rca.item.updateRemark');
    });
});



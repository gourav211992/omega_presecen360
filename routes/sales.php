<?php

use App\Http\Controllers\Sales\CustomerConsigneeController;
use App\Http\Controllers\Sales\SalesAutoCompleteController;
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
     Route::get('/customer-consignee/addresses/{id}', [CustomerConsigneeController::class, 'getCustomerConsigneeAddresses'])->name('customer.consignee.addresses');
});



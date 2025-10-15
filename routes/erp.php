<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;


Route::middleware(['user.auth'])->group(function () {
   Route::prefix('shift')->controller(ShiftController::class)->group(function () {
        Route::get('','index')->name('shift.index');
        Route::get('create','create')->name('shift.create');
        Route::post('store','store')->name('shift.store');
        Route::get('edit/{id}','edit')->name('shift.edit');
        Route::delete('destroy/{id}','destroy')->name('shift.destroy');

    });
});

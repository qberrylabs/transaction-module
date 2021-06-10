<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


use Illuminate\Support\Facades\Route;
use Modules\TransactionModule\Http\Controllers\Deposit;
use Modules\TransactionModule\Http\Controllers\RequestTransaction;
use Modules\TransactionModule\Http\Controllers\TransactionController;
use Modules\TransactionModule\Http\Controllers\Transfer;

Route::group(['middleware' => ['auth','isAdmin']], function() {
    Route::get('/transactions/type/{typeID}/{name}', [TransactionController::class,"getTransactionsByType"])->name('admin.transactions.type');
});

Route::group(['middleware' => ['auth','IsRegistrationCompleted','is2fa','IsProfileCompleted']], function() {

    Route::get('transactions/all', [TransactionController::class,'index'])->name('transactions.all');
    Route::post('transactions/filter', [TransactionController::class,'transactionFilter'])->name('transactions.filter');
    Route::get('transaction/details/{id}', [TransactionController::class,'getTransactionDetails'])->name('transactions.details');

    /* Transfer */
    Route::get('transfer', [Transfer::class,'index'])->name('transfer');
    Route::post('transfer', [Transfer::class,'build'])->name('transfer');

    /* Deposit */
    Route::get('deposit', [Deposit::class,'index'])->name('deposit');
    Route::post('deposit', [Deposit::class,'build'])->name('deposit');
    Route::post('create-reference-number',[Deposit::class,'createReferenceNumber'])->name("createReferenceNumber");
    Route::post('send-reference-number-email', [Deposit::class,'sendReferenceNumberEmail'])->name('sendReferenceNumberEmail');
    Route::post('deposit-fee', [Deposit::class,'depositFee'])->name('deposit.fee');

    /* Request */
    Route::get('request', [RequestTransaction::class,'index'])->name('request');
    Route::post('request', [RequestTransaction::class,'build'])->name('request');
    Route::get('request/panding', [RequestTransaction::class,'getRequests'])->name('request.panding');
    Route::get('request/change-status/{transactionID}/{status}', [RequestTransaction::class,'requestChangeStatus'])->name('requestChangeStatus');

});


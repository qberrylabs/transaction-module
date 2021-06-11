<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\TransactionModule\Http\Controllers\API\TransactionController;
use Modules\TransactionModule\Http\Controllers\API\WithdrawaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group(['middleware' => ['api','auth','isVerified','isEmailVerified','IsProfileCompleted']], function ($router) {
    //Start Transaction API
    Route::post('user-wallet-transactions',[TransactionController::class, 'getUserWalletTransactions']);
    // Route::post('create-transaction', 'TransactionController@store');
    Route::post('create-uuid-code', [TransactionController::class, 'createUuidCode']);
    Route::post('complete-payment', [TransactionController::class, 'completePayment']);

    Route::get('search-transaction/{full_name}', [TransactionController::class, 'search']);
    Route::post('search-transaction-date', [TransactionController::class, 'searchDate']);

    Route::post('create-request',[TransactionController::class, 'createRequest']);
    Route::post('change-status-request', [TransactionController::class, 'changeStatusRequest']);

    Route::post('create-transfer',[TransactionController::class, 'createTransfer']);
    Route::post('create-withdraw',[TransactionController::class, 'createWithdraw']);

    Route::get('get-transaction-pending',[TransactionController::class, 'getTransactionPending']);

    Route::post('create-deposit',[TransactionController::class, 'createDeposit']);
    //End Transaction API

    /* Start Withdrawa */
    Route::post('create-withdraw', [WithdrawaController::class,'create']);
    Route::get('get-withdraw-transaction/{statusID}', [WithdrawaController::class,'getWithdrawTransaction']);
    Route::get('withdraw/accepted/{transactionID}', [WithdrawaController::class,'accepted']);
    Route::get('withdraw/decline/{transactionID}', [WithdrawaController::class,'decline']);
    Route::post('withdraw-generate-uuid', [WithdrawaController::class,'generateUUID']);
    Route::post('withdraw-scanning', [WithdrawaController::class,'scanning']);
    /* End Withdrawa */
});

Route::post('get-fee', [TransactionController::class,'getFeeApi']);
Route::post('get-exchange-rate', [TransactionController::class,'getExchangeRateApi']);


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
use Modules\TransactionModule\Http\Controllers\TransactionController;

Route::group(['middleware' => ['auth','isAdmin']], function() {
    Route::get('/transactions/type/{typeID}/{name}', [TransactionController::class,"getTransactionsByType"])->name('admin.transactions.type');
});


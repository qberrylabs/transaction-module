<?php

namespace Modules\TransactionModule\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TransactionModule\Entities\Transaction;

abstract class TransactionOperation extends Controller
{
    public $fromWallet;
    public $toWallet;

    public $type;
    public $status;
    public $fromWalletId;
    public $toWalletId;
    public $amount;
    public $toAmount;
    public $fromCurrency;
    public $toCurrency;
    public $exchangeRate;
    public $fee;
    public $agentWalletId=null;

    public function setFromWallet($value){
        $this->fromWallet=$value;
    }
    public function setToWallet($value){
        $this->toWallet=$value;
    }


    public function setType($value){
        $this->type=$value;
    }
    public function setStatus($value){
        $this->status=$value;
    }
    public function setFromWalletId($value){
        $this->fromWalletId=$value;
    }
    public function setToWalletId($value){
        $this->toWalletId=$value;
    }
    public function setAmount($value){
        $this->amount=$value;
    }
    public function setToAmount($value){
        $this->toAmount=$value;
    }

    public function setFromCurrency($value){
        $this->fromCurrency=$value;
    }
    public function setToCurrency($value){
        $this->toCurrency=$value;
    }
    public function setExchangeRate($value){
        $this->exchangeRate=$value;
    }
    public function setFee($value){
        $this->fee=$value;
    }

    public abstract function build(Request $request);
    public abstract function index();

    public function saveTransaction()
    {

        $transaction = new Transaction();
        $transaction->transaction_type_id = $this->type;
        $transaction->transaction_status_id = $this->status;

        $transaction->from_wallet_id = $this->fromWalletId;
        $transaction->to_wallet_id = $this->toWalletId;
        $transaction->agent_wallet_id=$this->agentWalletId;

        $transaction->transaction_amount = $this->amount;
        $transaction->from_amount=$this->amount;
        $transaction->to_amount=$this->toAmount;

        $transaction->transaction_currency = $this->fromCurrency;
        $transaction->to_currency=$this->toCurrency;

        $transaction->transfer_fee = $this->fee;
        $transaction->exchange_rate = $this->exchangeRate;

        $transaction->total=$this->amount + $this->fee;

        $transaction->transaction_date = now();
        $transaction->save();
        return $transaction;
    }
}

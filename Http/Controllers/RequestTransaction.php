<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\TransactionModule\Events\TransactionEvent;
use App\Http\Controllers\Controller;
use Modules\CoreModule\Http\Controllers\User\UserSingleton;
use Modules\CoreModule\Http\Controllers\Wallet\WalletSingleton;
use Modules\CoreModule\Entities\Wallet;
use Modules\TransactionModule\Traits\TransactionTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Modules\ConnectionModule\Traits\ConnectionTrait;
use Modules\TransactionModule\Entities\Transaction;

class RequestTransaction extends TransactionOperation
{
    use ConnectionTrait , TransactionTraits;
    public  function index(){

        $connections=$this->getUserConnectionsByStatus(2);
        $wallet=WalletSingleton::getUserWallet();
        //dd($connections);
        return view('transactionmodule::transaction.request',['connections'=>$connections,'wallet'=>$wallet]);

    }
    public function build(Request $request)
    {
        $this->validate($request, [
            'from_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        $user=UserSingleton::getUser();

        $fromWalletId=$request['from_wallet_id'];
        $transactionAmount=$request['transaction_amount'];
        //dd($fromWalletId);
        $fromWallet=Wallet::find($fromWalletId);
        $toWallet=WalletSingleton::getUserWallet();

        $fromWalletID=$fromWallet->id;
        $fromWalletCurrency=$fromWallet->currency;
        $fromWalletBalance=$fromWallet->balance;

        $toWalletID=$toWallet->id;
        $toWalletCurrency=$toWallet->currency;
        $toWalletBalance=$toWallet->balance;

        $fee=$this->getFee('request' , $user->country , $transactionAmount);
        if ($fee == null) {
            return Redirect::back()->withErrors(['In Valid Fee']);
        }

        $transactionAmountAfterExchangeRate = $transactionAmount;
        $exchangeRate=0;

        if ($fromWalletCurrency != $toWalletCurrency) {
            $exchangeRate=$this->getExchangeRate($toWalletCurrency,$fromWalletCurrency);
            if ($exchangeRate == null) {
                return Redirect::back()->withErrors(['In Valid Exchange Rate']);
            }
            $transactionAmountAfterExchangeRate *= $exchangeRate;
        }

        $this->setType(5);
        $this->setStatus(1);
        $this->setFromWalletId($fromWalletID);
        $this->setToWalletId($toWalletID);
        $this->setAmount($transactionAmountAfterExchangeRate);
        $this->setToAmount($transactionAmount);
        $this->setFromCurrency($fromWalletCurrency);
        $this->setToCurrency($toWalletCurrency);
        $this->setExchangeRate($exchangeRate);
        $this->setFee($fee);

        $transaction=$this->saveTransaction();

        $toUser=$this->getUserInformationByWallet($fromWalletID);

        event(new TransactionEvent('request',$transaction,$toUser));


        return back()->with(['success'=>'Done']);
    }

    public function getRequests()
    {
        $transactions=$this->getTransactionsByTypeAndStatus(5,1);
        //dd($transactions);
        return view('transactionmodule::transaction.index',['transactions'=>$transactions]);
    }

    public function requestChangeStatus($transactionID,$status)
    {

        $transaction=Transaction::find($transactionID);

        if (!$transaction) {
            return back()->with('failed','Transaction Failed');
        }

        if ($status == "cancel") {
            $transaction->delete();
            return back()->with('success','Transaction cancelled successfully');
        }
        $fromWalletId=$transaction->from_wallet_id;
        $toWalletId=$transaction->to_wallet_id;

        $toUser=$this->getUserInformationByWallet($toWalletId);

        if ($status == "decline") {
            event(new TransactionEvent('request decline',$transaction,$toUser));
            $transaction->transaction_status_id=3;
            $transaction->save();
            return back()->with('success','Connection Change  successfully');
        }

        $fromWallet=WalletSingleton::getUserWallet();
        $toWallet=Wallet::find($toWalletId);

        $fromAmount=$transaction->from_amount;
        $toAmount=$transaction->to_amount;

        $totalAmount=$transaction->total;




        if ($fromWallet->balance < $totalAmount) {
            return Redirect::back()->withErrors(['You do not have enough Balance']);
        }
        $this->moneyTransfer($fromWallet,$toWallet,$totalAmount,$toAmount);
        $transaction->transaction_status_id=$status;
        $transaction->save();
        event(new TransactionEvent('request approved',$transaction,$toUser));

        return back()->with('success','Connection Change  successfully');
    }
}

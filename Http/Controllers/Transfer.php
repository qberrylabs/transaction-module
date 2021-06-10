<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\TransactionModule\Events\TransactionEvent;
use Modules\CoreModule\Http\Controllers\User\UserSingleton;
use Modules\CoreModule\Http\Controllers\Wallet\WalletSingleton;
use Modules\CoreModule\Entities\Wallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Modules\ConnectionModule\Traits\ConnectionTrait;
use Modules\TransactionModule\Traits\TransactionTraits;



class Transfer extends TransactionOperation
{
    use ConnectionTrait , TransactionTraits;

    public function index()
    {
        $connections=$this->getUserConnectionsByStatus(2);
        $wallet=WalletSingleton::getUserWallet();

        return view('transactionmodule::transaction.transfer',['connections'=>$connections,'wallet'=>$wallet]);
    }

    public function build(Request $request)
    {
        $this->validate($request, [
            'to_wallet_id' => 'required | numeric  | exists:wallets,id',
            'transaction_amount' => 'required | numeric | min:0 | not_in:0',
        ]);

        $user=UserSingleton::getUser();

        $toWalletId=$request['to_wallet_id'];
        $transactionAmount=$request['transaction_amount'];

        $fromWallet=WalletSingleton::getUserWallet();
        $toWallet=Wallet::find($toWalletId);

        $fromWalletID=$fromWallet->id;
        $fromWalletCurrency=$fromWallet->currency;
        $fromWalletBalance=$fromWallet->balance;

        $toWalletID=$toWallet->id;
        $toWalletCurrency=$toWallet->currency;
        $toWalletBalance=$toWallet->balance;


        $fee=$this->getFee('transfer' , $user->country , $transactionAmount);

        if ($fee == null) {
            return Redirect::back()->withErrors(['In Valid Fee']);
        }

        $totalAmount=$transactionAmount+$fee;

        if ($fromWalletBalance < $totalAmount) {
            return Redirect::back()->withErrors(['You do not have enough Balance']);
        }

        $transactionAmountAfterExchangeRate = $transactionAmount;
        $exchangeRate=0;

        if ($fromWalletCurrency != $toWalletCurrency) {

            $exchangeRate=$this->getExchangeRate($fromWalletCurrency,$toWalletCurrency);

            if ($exchangeRate == null) {
                return Redirect::back()->withErrors(['In Valid Exchange Rate']);
            }
            $transactionAmountAfterExchangeRate *= $exchangeRate;

        }

        $this->moneyTransfer($fromWallet,$toWallet,$totalAmount,$transactionAmountAfterExchangeRate);

        $this->setType(1);
        $this->setStatus(2);
        $this->setFromWalletId($fromWalletID);
        $this->setToWalletId($toWalletID);
        $this->setAmount($transactionAmount);
        $this->setToAmount($transactionAmountAfterExchangeRate);
        $this->setFromCurrency($fromWalletCurrency);
        $this->setToCurrency($toWalletCurrency);
        $this->setExchangeRate($exchangeRate);
        $this->setFee($fee);

        $transaction=$this->saveTransaction();
        $toUser=$this->getUserInformationByWallet($toWalletID);

        event(new TransactionEvent('transfer',$transaction,$toUser));
        return back()->with(['success'=>'Done']);

        //dd($this->fromWalletId);
    }
}

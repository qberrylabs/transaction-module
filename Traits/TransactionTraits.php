<?php

namespace Modules\TransactionModule\Traits;

use Modules\CoreModule\Http\Controllers\Wallet\WalletSingleton;

use Modules\CoreModule\Entities\DepositFee;
use Modules\CoreModule\Entities\ExchangeRate;
use Modules\CoreModule\Entities\Fee;
use Modules\TransactionModule\Entities\Transaction;

trait TransactionTraits {

    public function getTransactionsByTypeAndStatus($type,$status)
    {
        $wallet=WalletSingleton::getUserWallet();

        $transactions = Transaction::where(function ($query) use ($wallet )  {

            $query->where('from_wallet_id', $wallet->id);
            $query->orWhere('to_wallet_id', $wallet->id);

        })->Where(function ($query)  use ($status , $type ){
            $query->where('transaction_type_id',$type);
            $query->where('transaction_status_id',$status);
        })->get();

        foreach ($transactions as $transaction) {
            $transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());

            $transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
            $transaction->setAttribute('transaction_type', $transaction->getTransactionType->transaction_type_name);

            $transaction->setAttribute('signal', $this->getTransactionSignal($transaction));

            $transaction->setAttribute('user_name', $this->getTransactionShowUser($transaction));

            $transaction->setAttribute('transaction_action', $this->getTransactionAction($transaction));

            $transaction->setAttribute('show_amount', $this->getTramsactionShowAmount($transaction));

        }
        return  $transactions;
    }

    public function getTramsactionShowAmount($transaction)
    {
        $walletID=WalletSingleton::getUserWallet()->id;
        $amount=null;
        if ($transaction->from_wallet_id == $walletID) {
            $amount= $transaction->from_amount;

        }elseif($transaction->to_wallet_id == $walletID){
            $amount= $transaction->to_amount;

        }else{
            return " ";
        }
        return $amount;
    }

    private function getTransactionAction($transaction)
    {
        $walletID=WalletSingleton::getUserWallet()->id;

        if ($transaction->from_wallet_id == $walletID) {
            return "Received";

        }elseif($transaction->to_wallet_id == $walletID){
            return "Sender";
        }else{
            return " ";
        }
    }

    private function getTransactionSignal($transaction)
    {
        $wallet=WalletSingleton::getUserWallet();

        if ($transaction->transaction_type_id == 2) {
            return "+";
        }

        if ($transaction->from_wallet_id == $wallet->id) {
            return "-";
        }elseif($transaction->to_wallet_id == $wallet->id){
            return "+";
        }else{
            return " ";
        }
    }

    public function getTransactionShowUser($transaction)
    {
        $wallet=WalletSingleton::getUserWallet();

        if ($transaction->from_wallet_id == $wallet->id) {
            return $transaction->getWalletInformations->getUserInformations->full_name;

        }elseif($transaction->to_wallet_id == $wallet->id){
            return $transaction->getWalletInformationsFrom->getUserInformations->full_name;
        }else{
            return " ";
        }
    }

    public function getUserTransactionsFilter($from,$to)
    {
        $transactions=Transaction::whereBetween('transaction_date', [$from, $to])
            ->with(['getTransactionType:id,transaction_type_name',
                    'getWalletInformations.getUserInformations:id,full_name,name',
                    'getWalletInformationsFrom.getUserInformations:id,full_name,name',
            ])
            ->Where(function($q){
                $walletID=WalletSingleton::getUserWallet()->id;
                $q->where('from_wallet_id',$walletID );
                $q->orwhere('to_wallet_id',$walletID);
            })->orderBy('id','DESC')->get();

            //dd($transactions);

        foreach ($transactions as $transaction) {
            //$transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());

            //$transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
            //$transaction->setAttribute('transaction_type', $transaction->getTransactionType->transaction_type_name);

            $transaction->setAttribute('signal', $this->getTransactionSignal($transaction));

            //$transaction->setAttribute('user_name', $this->getTransactionShowUser($transaction));

        }
        return $transactions;
    }

    public function getUserTransactions($limit=null)
    {

        $wallet = WalletSingleton::getUserWallet();

        if ($limit != null) {
            $transactions = Transaction::where('from_wallet_id',$wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->with(['getTransactionType:id,transaction_type_name',
                    'getWalletInformations.getUserInformations:id,full_name,name',
                    'getWalletInformationsFrom.getUserInformations:id,full_name,name',
            ])
            ->orderBy('id','DESC')
            ->take($limit)
            ->get();
        }else{
            $transactions = Transaction::where('from_wallet_id',$wallet->id)
            ->with(['getTransactionType:id,transaction_type_name',
                    'getWalletInformations.getUserInformations:id,full_name,name',
                    'getWalletInformationsFrom.getUserInformations:id,full_name,name',
            ])
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderBy('id','DESC')
            ->paginate(10);
        }
        //dd($transactions);



        foreach ($transactions as $transaction) {
            $transaction->setAttribute('signal', $this->getTransactionSignal($transaction));
        }
        return  $transactions;
    }

    public function getTransactionTemplateName($id)
    {
        $template=null;
        switch ($id) {
            case 1:
                $template='transfer';
                break;
            case 2:
                $template='Deposit Create';
                break;
            case 5:
                $template='request';
                break;
            default:
                # code...
                break;
        }
        return $template;
    }

    public function getFee($transactionType,$country,$amount)
    {
        $fee=Fee::where('name',$transactionType)->where('country',$country)->first();
        if($fee){
            $feeType=$fee->fee_type;
            if($feeType == "Percentage"){
                return $amount * $fee->price;
            }else{
                return $fee->price;
            }

        }else{
            return 0;
        }
    }

    public function getExchangeRate($from,$to)
    {
        $exchangeRate = ExchangeRate::where('from_currency', $from)->where('to_currency', $to)->first();
        if ($exchangeRate) {
            return $exchangeRate->exchange_rate;
        }
        return null;
    }

    private function getDepositFee($country,$paymentMethod,$amount)
    {
        $fee=DepositFee::where('country',$country)->where('payment_method',$paymentMethod)->first();
        if (!$fee) {
            return 0;
        }
        $feeType=$fee->fee_type;
        if($feeType == "Percentage"){
            return $amount * $fee->price;
        }else{
            return $fee->price;
        }

    }


}

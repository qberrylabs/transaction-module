<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\CoreModule\Entities\Template;
use Modules\CoreModule\Traits\WalletTrait;
use Illuminate\Support\Facades\Session;

class TransactionEmailTemplate
{
    use WalletTrait;

    public $type;
    public $transaction;

    /*Start Transaction Information*/
    public $transactionID;
    public $transactionTypeID;
    public $fromAmount;
    public $toAmount;
    public $fromCurrency;
    public $toCurrency;
    public $fee;
    public $exchangeRate;
    public $transactionDate;
    /*End Transaction Information*/

    /*Start Wallet Information */
    public $fromWalletID;
    public $toWalletID;
    public $fromWallet;
    public $toWallet;
    /*End Wallet Information */

    /*Start Template Information */
    public $emailTemplate;
    /*End Template Information */

    public function __construct($type,$transaction)
    {
        $this->type=$type;
        $this->transaction=$transaction;
        $this->setTransactionVariable();
        $this->setTransactionTemplate();
    }

    public function setTransactionVariable()
    {
        $this->transactionID=$this->transaction->id;
        $this->transactionTypeID=$this->transaction->transaction_type_id;
        $this->fromAmount=$this->transaction->from_amount;
        $this->toAmount=$this->transaction->to_amount;
        $this->fromCurrency=$this->transaction->transaction_currency;
        $this->toCurrency=$this->transaction->to_currency;
        $this->fee=$this->transaction->transfer_fee;
        $this->exchangeRate=$this->transaction->exchange_rate;
        $this->transactionDate=$this->transaction->transaction_date;
        /*End Transaction Information*/

        /*Start Wallet Information */
        $fromWalletID=$this->transaction->from_wallet_id;
        $toWalletID=$this->transaction->to_wallet_id;
        $this->fromWallet=$this->getUserInformationByWallet($fromWalletID);
        $this->toWallet=$this->getUserInformationByWallet($toWalletID);
        /*End Wallet Information */
    }

    public function setTransactionTemplate()
    {

       $this->emailTemplate=Template::where('name',$this->type)->first();
    }



    public function getTransactionEmailTemplate()
    {
        $content=null;
        switch ($this->type) {
            case "transfer":
                $content=$this->getTransferEmailTemplate();
                break;
            case "request":
                $content=$this->getRequestEmailTemplate();
                break;
            case "request approved":
                $content=$this->getRequestApprovedTemplate();
                break;

            case "request decline":
                $content=$this->getRequestDeclineTemplate();
                break;

            case "Deposit Create":
                $content=$this->getDepositTemplate();
                break;

            default:
                Session::flash('failed', 'The Email Has Not Sent');
                break;
        }
        return $content;
    }

    public function getTransferEmailTemplate()
    {
        $old = [
            'sender_name',
            'recipient_name',
            'from_amount',
            'to_amount',
            'from_currency',
            'to_currency',
            'fee_amount',
            'exchange_rate',
            'transaction_date',
            'id',
            ];

            $new = [
            $this->fromWallet->name ,
            $this->toWallet->name ,
            $this->fromAmount ,
            $this->toAmount ,
            $this->fromCurrency ,
            $this->toCurrency ,
            $this->fee ,
            $this->exchangeRate ,
            $this->transactionDate ,
            $this->transactionID
            ];

            $content = str_replace($old, $new, $this->emailTemplate->content);
            return $content;
    }

    public function getRequestEmailTemplate()
    {
        $old = [
            'sender_name',
            'recipient_name',
            'from_amount',
            'to_amount',
            'from_currency',
            'to_currency',
            'fee_amount',
            'exchange_rate',
            'transaction_date',
            'id',
            ];

            $new = [
            $this->toWallet->name ,
            $this->fromWallet->name ,
            $this->fromAmount ,
            $this->toAmount ,
            $this->fromCurrency ,
            $this->toCurrency ,
            $this->fee ,
            $this->exchangeRate ,
            $this->transactionDate ,
            $this->transactionID
            ];

            $content = str_replace($old, $new, $this->emailTemplate->content);
            return $content;
    }

    public function getDepositTemplate()
    {
        $old = ['user_full_name','deposit_amount'];
        $new   = [$this->fromWallet->name , $this->fromAmount];
        $content = str_replace($old, $new, $this->emailTemplate->content);
        return $content;
    }

    public function getRequestApprovedTemplate()
    {
        $old = ['user_full_name'];
        $new   = [$this->fromWallet->name];
        $content = str_replace($old, $new, $this->emailTemplate->content);
        return $content;
    }

    public function getRequestDeclineTemplate()
    {
        $old = ['user_full_name'];
        $new   = [$this->fromWallet->name];
        $content = str_replace($old, $new, $this->emailTemplate->content);
        return $content;
    }


}

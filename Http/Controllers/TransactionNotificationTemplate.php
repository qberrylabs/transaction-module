<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\CoreModule\Entities\NotificationTemplate;
use Modules\CoreModule\Traits\UserTrait;
use Modules\CoreModule\Traits\WalletTrait;
use Modules\CoreModule\Entities\Notification;
use Illuminate\Support\Facades\Session;
use Modules\CoreModule\Enum\NotificationSettingEnum;

class TransactionNotificationTemplate
{
    use UserTrait , WalletTrait;

    public $type;
    public $user;
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
    public $notificationTemplate;
    /*End Template Information */

    public function __construct($type,$user,$transaction)
    {
        $this->type=$type;
        $this->user=$user;
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

       $this->notificationTemplate=NotificationTemplate::where('type',$this->type)->first();
    }



    public function getTransactionNotificationTemplate()
    {
        $content=null;
        switch ($this->type) {
            case "transfer":
                $content=$this->getTransferNotificationTemplate();
                break;
            case "request":
                $content=$this->getRequestNotificationTemplate();
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
                Session::flash('failed', 'The Notification Has Not Sent');
                break;
        }
        return $content;
    }

    public function getTransferNotificationTemplate()
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

            $content = str_replace($old, $new, $this->notificationTemplate->content);
            return $content;
    }

    public function getRequestNotificationTemplate()
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

            $content = str_replace($old, $new, $this->notificationTemplate->content);
            return $content;
    }

    public function getDepositTemplate()
    {
        $old = ['user_full_name','deposit_amount'];
        $new   = [$this->fromWallet->name , $this->fromAmount];
        $content = str_replace($old, $new, $this->notificationTemplate->content);
        return $content;
    }

    public function getRequestApprovedTemplate()
    {
        $old = ['user_full_name'];
        $new   = [$this->fromWallet->name];
        $content = str_replace($old, $new, $this->notificationTemplate->content);
        return $content;
    }

    public function getRequestDeclineTemplate()
    {
        $old = ['user_full_name'];
        $new   = [$this->fromWallet->name];
        $content = str_replace($old, $new, $this->notificationTemplate->content);
        return $content;
    }

    public function sendNotification()
    {

        $user=$this->user;

        $title=$this->notificationTemplate->title;
        $body=$this->getTransactionNotificationTemplate();
        $type=$this->type;

        $notification=new Notification();
        $notification->user_id=$user->id;
        $notification->notification_type=$type;
        $notification->notification_title=$title;
        $notification->contant=$body;
        $notification->save();

        $userTokens=$user->getUserDeviceTokens()->get();

        foreach ($userTokens as $userToken) {

           $this->sendNotificationToTokens($userToken->device_token,$title,$body,$type);
        }
    }

    public function sendNotificationToTokens($token,$title,$body,$type)
    {
        $firebaseToken = $token;
        $SERVER_API_KEY = NotificationSettingEnum::SERVER_API_KEY;
        //$SERVER_API_KEY = 'AAAAXF-71xs:APA91bH1ZH77AZiXqD5yiMqtCr6X9yv8d5zg_6CVIxRfIT-IUJ2S8fXtqhefpkIyfZ0emVvHVZZ_IPMF8fxl0JRMddK11I-4I5PJQt7yLSJdjFmXe0ItwsxJ6JiYWhoTDH1-el5n2zni';

        $data = [
            "registration_ids" =>array( $firebaseToken),
            "notification" => [
                "title" => $title,
                "body" => $body,
                "type" =>$type
            ]
        ];
        $dataString = json_encode($data);
        //dd($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);
        } catch (\Throwable $th) {
            Session::flash('failed', 'The Notification Has Not Sent');
        }


    }


}

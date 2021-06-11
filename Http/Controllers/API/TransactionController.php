<?php

namespace Modules\TransactionModule\Http\Controllers\API;

use App\Enum\TransactionStatusEnum;
use App\Enum\TransactionTypesEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\TransactionModule\Entities\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Modules\CoreModule\Entities\Wallet;
use Modules\CoreModule\Entities\Setting;
use Modules\CoreModule\Entities\ExchangeRate;
use Modules\CoreModule\Entities\PaymentOperation;
use Modules\CoreModule\Entities\Fee;
use Webpatser\Uuid\Uuid;
use App\Mail\TransferEmail;
use App\Mail\RequestEmail;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangeStatusRequestEmail;
use Modules\CoreModule\Entities\DepositFee;
use App\Traits\ErrorHandlingTraits;
use Modules\CoreModule\Entities\NotificationTemplate;
use Modules\TransactionModule\Entities\TransactionType;
use App\Traits\NotificationTraits;
use App\Traits\TransactionTraits;

class TransactionController extends Controller
{
    use ErrorHandlingTraits , NotificationTraits , TransactionTraits;

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

    public function createDeposit(Request $request)
    {
        $this->validate($request, [
            'payment_method'=>'required',
            'reference_number' => 'required',
            'amount_paid' => 'required'
        ]);
        $referenceNumber=$request['reference_number'];
        $amountPaid=$request['amount_paid'];
        $paymentMethod=$request['payment_method'];

        $user = Auth::user();
        $wallet=$user->getUserWallets->first();

        $checkDeposit=$this->checkDeposit($referenceNumber,$amountPaid,$user->id,$wallet->currency);

        if ($checkDeposit) {

            $amountPaid=$checkDeposit->amount_paid;
            $fee=$this->getDepositFee($user->country,$paymentMethod,$amountPaid);

            $transaction_after_fee=$amountPaid - $fee;

            $depositTransaction=$this->createNewDepositTransaction($wallet->id,$amountPaid,$wallet->currency,$fee);

            if ($depositTransaction) {
                $wallet->balance+=$transaction_after_fee;
                $wallet->save();

                return response()->json(['success' => 'true','message'=>'Deposit Create Successfuly'], 200);
            } else {
                return response()->json(['success' => 'false','message'=>'Deposit Failed'], 400);
            }


            return response()->json(['success' => 'true','message'=>$wallet], 200);
        } else {
            return response()->json(['success' => 'false','message'=>'Deposit InValid'], 400);
        }



    }

    public function getExchangeRateApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            'amount'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $from=$request['from'];
        $to=$request['to'];
        $exchangeRate = ExchangeRate::where('from_currency', $from)->where('to_currency', $to)->first();

        if ($exchangeRate) {
            return response()->json($exchangeRate->exchange_rate * $request['amount']);

        }else{
            return response()->json(['message' => 'inValid ExchangeRate'],400);
        }

    }

    public function getFeeApi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_type' => 'required',
            'countery' => 'required',
            'amount' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $transactionType=$request['transaction_type'];
        $countery=$request['countery'];
        $amount=$request['amount'];

        $fee=Fee::where('name',$transactionType)->where('country',$countery)->first();

        if ($fee) {
            $feeType=$fee->fee_type;
            if($feeType == "Percentage"){
                return response()->json($amount * $fee->price);

            }else{
                return response()->json($fee->price);
            }
        }else{
            return response()->json(['message' => 'inValid Fee'],400);
        }

    }

    public function getTransactionPending()
    {

        $userID = Auth::id();
        $walletId=User::find($userID)->getUserWallets()->first()->id;

        if (User::find($userID)->getUserWallets()->where('id', $walletId)->exists()) {
            $transactions = Transaction::with(
                [
                'getTransactionStatus:id,transaction_status_name',
                'getTransactionType:id,transaction_type_name',
                ]
            )
            ->where('transaction_status_id', 1)
            ->where('from_wallet_id', $walletId)
            ->orWhere('to_wallet_id', $walletId)
            ->get();
            // $transactions = User::find($userID)->getUserWallets()->where('id', $request['wallet_id'])->first()->getWalletTransactions;
            foreach ($transactions as $transaction) {
                $transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());
                $transaction->setAttribute('from_user', $transaction->getWalletInformationsFrom->getUserInformations->full_name);
                $transaction->setAttribute('to_user', $transaction->getWalletInformations->getUserInformations->full_name);
                // $transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
                // $transaction->setAttribute('transaction_type', $transaction->getTransactionType->transaction_type_name);
            }
            return response()->json(['transactions' => $transactions], 200);
        }
        return response()->json(['message' => 'User Has Not this Wallet']);
    }

    public function searchDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'required | date',
            'to' => 'required | date'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userID = Auth::id();

        $wallet=User::find($userID)->getUserWallets()->first();

        if($wallet){
            $from =  $request['from'];
            $to = $request['to'];


            $transactions=Transaction::whereBetween('transaction_date', [$from, $to])
            ->Where(function($q){
                $walletID=User::find(Auth::id())->getUserWallets()->first()->id;
                $q->where('from_wallet_id',$walletID );
                $q->orwhere('to_wallet_id',$walletID);
            })->get();

            foreach ($transactions as $transaction) {
                $transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());
                $transaction->setAttribute('from_user', $transaction->getWalletInformationsFrom->getUserInformations->full_name);
                $transaction->setAttribute('to_user', $transaction->getWalletInformations->getUserInformations->full_name);
                $transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
                $transaction->setAttribute('transaction_type', $transaction->getTransactionType->transaction_type_name);
            }

            return response()->json(['transactions' => $transactions], 200);
        }else{
            return response()->json(['message' => "There Are No Transactions"]);
        }
    }

    public function createNewTransaction($transactionType,$transactionStatus,$fromWalletId,$toWalletId,$agentWalletId,$transactionAmount,$currency,$exchange_rate,$transaction_amount_fee,$toAmount,$toCurrency)
    {
        $transaction = new Transaction();
        $transaction->transaction_type_id = $transactionType;
        $transaction->transaction_status_id = $transactionStatus;
        $transaction->from_wallet_id = $fromWalletId;
        $transaction->to_wallet_id = $toWalletId;
        $transaction->agent_wallet_id=$agentWalletId;
        $transaction->transaction_amount = $transactionAmount;
        $transaction->transaction_date = now();
        $transaction->transaction_currency = $currency;
        $transaction->exchange_rate = $exchange_rate;

        $transaction->total=$transactionAmount + $transaction_amount_fee;
        $transaction->from_amount=$transactionAmount;
        $transaction->to_amount=$toAmount;
        $transaction->to_currency=$toCurrency;
        $transaction->transfer_fee = $transaction_amount_fee;
        $transaction->save();
        if($transaction){
            // $wallet->getUserInformations->name
            $from_wallet_informations=Wallet::find($fromWalletId)->getUserInformations;
            $to_wallet_informations=Wallet::find($toWalletId)->getUserInformations;

            $from_full_name=$from_wallet_informations->full_name;
            $to_full_name=$to_wallet_informations->full_name;

            $to_email=$to_wallet_informations->email;



            switch ($transactionType) {
                case 1:
                    $data = [
                        'sender_name' => $from_full_name,
                        'recipient_name' => $to_full_name,
                        'from_amount' => $transactionAmount,
                        'from_currency' => $currency,
                        'to_amount' => $toAmount,
                        'to_currency' => $toCurrency,
                        'fee_amount'=>$transaction_amount_fee,
                        'exchange_rate'=>$exchange_rate,
                        'transaction_date'=>$transaction->transaction_date,
                        'id'=>$transaction->id,

                    ];
                    try {
                        Mail::to($to_email)->send(new TransferEmail($data));

                        $token=$to_wallet_informations->device_token;

                        $notificationTemplate=NotificationTemplate::where('type','transfer')->first();
                        $title=$notificationTemplate->title;
                        $content=$notificationTemplate->content;
                        $type=$notificationTemplate->type;

                        $old = ["sender_name", "recipient_name","from_amount","from_currency","to_amount","to_currency","fee_amount","exchange_rate","transaction_date","id"];
                        $new   = [
                            $from_full_name,$to_full_name,$transactionAmount,
                            $currency, $toAmount,$toCurrency,
                            $transaction_amount_fee, $exchange_rate,$transaction->transaction_date,
                            $transaction->id
                        ];

                        $resContent = str_replace($old, $new, $content);



                        $this->sendNotification($to_wallet_informations,$token,$title,$resContent,$type);


                    } catch (\Throwable $th) {
                        return response()->json(['message' => 'Transaction Successfully Created But Can Not Send Emaill Or Notification'], 200);
                    }

                    break;
                case 5:
                    $data = [
                        'sender_name' => $from_full_name,
                        'recipient_name' => $to_full_name,
                        'from_amount' => $transactionAmount,
                        'from_currency' => $currency,
                        'to_amount' => $toAmount,
                        'to_currency' => $toCurrency,
                        'fee_amount'=>$transaction_amount_fee,
                        'exchange_rate'=>$exchange_rate,
                        'transaction_date'=>$transaction->transaction_date,
                        'id'=>$transaction->id,

                    ];
                    try {
                        Mail::to($to_email)->send(new RequestEmail($data));
                        $token=$to_wallet_informations->device_token;

                        $notificationTemplate=NotificationTemplate::where('type','request')->first();
                        $title=$notificationTemplate->title;
                        $content=$notificationTemplate->content;
                        $type=$notificationTemplate->type;

                        $old = ["sender_name", "recipient_name","from_amount","from_currency","to_amount","to_currency","fee_amount","exchange_rate","transaction_date","id"];
                        $new   = [
                            $from_full_name,$to_full_name,$transactionAmount,
                            $currency, $toAmount,$toCurrency,
                            $transaction_amount_fee, $exchange_rate,$transaction->transaction_date,
                            $transaction->id
                        ];

                        $resContent = str_replace($old, $new, $content);



                        $this->sendNotification($to_wallet_informations,$token,$title,$resContent,$type);

                    } catch (\Throwable $th) {
                        return response()->json(['message' => 'Transaction Successfully Created But Can Not Send Email Or Notification'], 200);
                    }


                    break;
                default:
                    # code...
                    break;
            }
            return response()->json(['message' => 'Transaction Successfully Created'], 200);

        }else{
            return $this->showError("Transaction Failed");
        }

    }

    public function getUserWalletTransactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required | numeric'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userID = Auth::id();
        if (User::find($userID)->getUserWallets()->where('id', $request['wallet_id'])->exists()) {

            $transactions = Transaction::with(
                [
                'getTransactionStatus:id,transaction_status_name',
                'getTransactionType:id,transaction_type_name',
                ]
            )
            ->where('transaction_status_id','!=', 1)
            ->where('from_wallet_id', $request['wallet_id'])
            ->orWhere('to_wallet_id', $request['wallet_id'])
            ->orderBy('id','DESC')
            ->paginate(5);
            // $transactions = User::find($userID)->getUserWallets()->where('id', $request['wallet_id'])->first()->getWalletTransactions;
            foreach ($transactions as $transaction) {
                $transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());

                $transaction->setAttribute('from_user', $transaction->getWalletInformationsFrom->getUserInformations->full_name);
                $transaction->setAttribute('to_user', $transaction->getWalletInformations->getUserInformations->full_name);

                //$transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
                //$transaction->setAttribute('transaction_type', $transaction->getTransactionType->transaction_type_name);
            }
            return response()->json(['transactions' => $transactions], 200);
        }



        return response()->json(['message' => 'User Has Not this Wallet']);
    }

    public function createTransfer(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'from_wallet_id' => 'required | numeric | exists:wallets,id',
            'to_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }

        if(!$this->isValidWallets($request['from_wallet_id'],$request['to_wallet_id'])){
            return $this->showError("inValid Wallet");
        }

        $from_wallet = Wallet::find($request['from_wallet_id']);
        $to_wallet = Wallet::find($request['to_wallet_id']);

        $from_wallet_currency=$from_wallet->currency;
        $to_wallet_currency=$to_wallet->currency;

        $walletCountry=$this->getWalletCountry($from_wallet_currency);

        if (!$this->isValidFee("transfer",$walletCountry) ) {
            return $this->showError("inValid Fee");
        }

        $transaction_amount_fee = $this->getFee("transfer",$walletCountry,$request['transaction_amount']);
        $transaction_amount = $request['transaction_amount'] + $transaction_amount_fee;

        $transaction_amount_after_exchange_rate = $request['transaction_amount'];
        $exchange_rate = 0;

        if ($from_wallet->balance < $transaction_amount) {
            return $this->showError("Your Current Balance is not Sufficient for a Transfer");
        }

        if ($from_wallet_currency != $to_wallet_currency) {
            if(!$this->isValidExchangeRate($from_wallet_currency,$to_wallet_currency)){
                return $this->showError("inValid Exchange Rate");
            }
            $exchange_rate=$this->getExchangeRate($from_wallet_currency,$to_wallet_currency);
            $transaction_amount_after_exchange_rate *= $exchange_rate ;
        }

        $from_wallet->balance -= $transaction_amount;
        $from_wallet->save();

        $to_wallet->balance += $transaction_amount_after_exchange_rate;
        $to_wallet->save();

        return $this->createNewTransaction(TransactionTypesEnum::TRANSFER,TransactionStatusEnum::COMPLETED,$from_wallet->id,$to_wallet->id,NULL,$request['transaction_amount'],$from_wallet_currency,$exchange_rate,$transaction_amount_fee,$transaction_amount_after_exchange_rate,$to_wallet_currency);

    }


    public function createWithdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_wallet_id' => 'required | numeric | exists:wallets,id',
            'to_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }

        if(!$this->isValidWallets($request['from_wallet_id'],$request['to_wallet_id'])){
            return $this->showError("inValid Wallet");
        }

        $from_wallet = Wallet::find($request['from_wallet_id']);
        $to_wallet = Wallet::find($request['to_wallet_id']);

        $from_wallet_currency=$from_wallet->currency;
        $to_wallet_currency=$to_wallet->currency;

        $walletCountry=$this->getWalletCountry($from_wallet_currency);

        if(!$this->isValidFee("withdraw",$walletCountry)){
            return $this->showError("inValid Fee");
        }

        $transaction_amount_fee = $this->getFee("withdraw",$walletCountry,$request['transaction_amount']);
        $transaction_amount = $request['transaction_amount'] + $transaction_amount_fee;

        $transaction_amount_after_exchange_rate = $request['transaction_amount'];
        $exchange_rate = 0;
        if ($from_wallet->balance < $transaction_amount) {
            return $this->showError("Your Current Balance is not Sufficient for a Transfer");
        }

        if ($from_wallet_currency != $to_wallet_currency) {
            if(!$this->isValidExchangeRate($from_wallet_currency,$to_wallet_currency)){
                return $this->showError("inValid Exchange Rate");
            }
            $exchange_rate=$this->getExchangeRate($from_wallet_currency,$to_wallet_currency);
            $transaction_amount_after_exchange_rate *= $exchange_rate ;

        }

        $from_wallet->balance -= $transaction_amount;
        $from_wallet->save();

        $to_wallet->balance += $transaction_amount_after_exchange_rate;
        $to_wallet->save();

        return $this->createNewTransaction(TransactionTypesEnum::WITHDRAW,TransactionStatusEnum::COMPLETED , $from_wallet->id , $to_wallet->id , $to_wallet->id , $request['transaction_amount'] ,$from_wallet_currency,$exchange_rate,$transaction_amount_fee,$transaction_amount_after_exchange_rate,$to_wallet_currency);


    }

    public function createUuidCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_wallet_id' => 'required | numeric | exists:wallets,id',
            'to_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }


        $userID = Auth::id();
        $from_wallet = Wallet::find($request['from_wallet_id']);
        $to_wallet = Wallet::find($request['to_wallet_id']);

        if (User::find($userID)->getUserWallets()->where('id', $request['from_wallet_id'])->exists() && Wallet::where('id', $request['to_wallet_id'])->exists()) {

            if ($from_wallet->balance >= $request['transaction_amount']) {

                $uuidCode=(string)Uuid::generate();
                $paymentOperation=new PaymentOperation();
                $paymentOperation->from_wallet_id=$request['from_wallet_id'];
                $paymentOperation->to_wallet_id=$request['to_wallet_id'];
                $paymentOperation->transaction_amount=$request['transaction_amount'];
                $paymentOperation->currency=$from_wallet->currency;
                $paymentOperation->uuid_code=$uuidCode;
                $paymentOperation->payment_operation_date=now();
                $paymentOperation->save();
                return response()->json(['code' => $uuidCode]);

            }else{
                return response()->json(['message' => 'Your Current Balance is not Sufficient for a Transfer']);
            }
        }else{
            return response()->json(['message' => 'Wallet Failed']);
        }
    }



    public function completePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid_code' => 'required',
            //'country' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'message'=>"The code you scanned is not correct!"], 400);
        }
        $paymentOperation=PaymentOperation::where('uuid_code',$request['uuid_code'])->first();
        $setting = Setting::find(1);
        $userID = Auth::id();

        $from_wallet = Wallet::find($paymentOperation->from_wallet_id);
        $to_wallet = Wallet::find($paymentOperation->to_wallet_id);

        $from_wallet_currency=$from_wallet->currency;
        $to_wallet_currency=$to_wallet->currency;

        $walletCountry=$this->getWalletCountry($from_wallet_currency);

        if(!$this->isValidFee("pay",$walletCountry)){
            return $this->showError("Coudn't send to wallet due to an Fee problem");
        }

        $from_wallet_ID=$paymentOperation->from_wallet_id;
        $to_wallet_ID=$paymentOperation->to_wallet_id;

        $amount=$paymentOperation->transaction_amount;
        $transaction_amount_fee = $this->getFee("pay",$walletCountry,$amount);

        $transaction_amount=$amount + $transaction_amount_fee;
        $transaction_currency=$paymentOperation->currency;
        if ($from_wallet->balance < $transaction_amount) {
            return response()->json(['message' => 'Your Current Balance is not Sufficient to finish this Payment!']);
        }

        //$transaction_amount_after_exchange_rate = $request['transaction_amount'];
        $exchange_rate = 0;

        if ($from_wallet_currency != $to_wallet_currency) {

            if(!$this->isValidExchangeRate($from_wallet_currency,$to_wallet_currency)){
                return $this->showError("Coudn't send to wallet due to an extchge rate problem");
            }
            $exchange_rate=$this->getExchangeRate($from_wallet_currency,$to_wallet_currency);
            $amount *= $exchange_rate ;

        }

        $from_wallet->balance -= $transaction_amount;
        $from_wallet->save();

        $to_wallet->balance += $amount;
        $to_wallet->save();

        return $this->createNewTransaction(TransactionTypesEnum::PAY,TransactionStatusEnum::COMPLETED ,$from_wallet_ID,$to_wallet_ID,NULL,$paymentOperation->transaction_amount,$transaction_currency,$exchange_rate,$transaction_amount_fee,$amount,$to_wallet_currency);

    }

    public function createRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_wallet_id' => 'required | numeric | exists:wallets,id',
            'to_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }
        if(!$this->isValidWallets($request['to_wallet_id'],$request['from_wallet_id'])){
            return $this->showError("inValid Wallet");
        }


            $from_wallet = Wallet::find($request['to_wallet_id']);
            $to_wallet = Wallet::find($request['from_wallet_id']);

            $from_wallet_currency=$from_wallet->currency;
            $to_wallet_currency=$to_wallet->currency;

            $walletCountry=$this->getWalletCountry($to_wallet_currency);

            if(!$this->isValidFee("request",$walletCountry)){
                return $this->showError("inValid Fee");
            }

            $transaction_amount_fee = $this->getFee("request",$walletCountry,$request['transaction_amount']);

            $transaction_amount_after_exchange_rate = $request['transaction_amount'];
            $exchange_rate = 0;
            //$transaction_amount = $request['transaction_amount'] + $transaction_amount_fee;

            if ($from_wallet_currency != $to_wallet_currency) {

                if(!$this->isValidExchangeRate($to_wallet_currency,$from_wallet_currency)){
                    return $this->showError("inValid Exchange Rate");
                }
                $exchange_rate=$this->getExchangeRate($to_wallet_currency,$from_wallet_currency);
                    $transaction_amount_after_exchange_rate *= $exchange_rate ;

            }

            return $this->createNewTransaction(TransactionTypesEnum::REAUEST,TransactionStatusEnum::PENDING,$to_wallet->id,$from_wallet->id,NULL,$request['transaction_amount'],$to_wallet_currency,$exchange_rate,$transaction_amount_fee,$transaction_amount_after_exchange_rate,$from_wallet_currency);

    }


    public function changeStatusRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required | numeric',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }

        if($request['status'] ==  'accepted'){
            $transactionID=$request['transaction_id'];

            $transaction = Transaction::find($transactionID);
            $transaction->transaction_status_id = 2;
            $transaction->transaction_date = now();
            $transaction->save();

            $transactionAmount=$transaction->transaction_amount + $transaction->transfer_fee;
            $amount=$transaction->transaction_amount;

            $from_wallet = Wallet::find($transaction->from_wallet_id);
            $to_wallet = Wallet::find($transaction->to_wallet_id);

            $from_wallet_currency=$from_wallet->currency;
            $to_wallet_currency=$to_wallet->currency;

            if ($from_wallet->balance >= $transactionAmount) {
                //$transaction_amount_after_exchange_rate = $request['transaction_amount'];
                $exchange_rate = 0;

                if ($from_wallet_currency != $to_wallet_currency) {

                    if( $this->isValidExchangeRate($from_wallet_currency,$to_wallet_currency)){
                        $exchange_rate=$this->getExchangeRate($from_wallet_currency,$to_wallet_currency);
                        $amount *= $exchange_rate ;
                    }else{
                        return $this->showError("inValid Exchange Rate");
                    }

                }

                $from_wallet->balance -= $transactionAmount;
                $from_wallet->save();

                $to_wallet->balance +=$amount ;
                $to_wallet->save();

                $from_wallet_informations=$from_wallet->getUserInformations;
                $to_wallet_informations=$to_wallet->getUserInformations;

                $to_full_name=$to_wallet_informations->full_name;
                $from_full_name=$from_wallet_informations->full_name;
                $from_email=$from_wallet_informations->email;

                $data = ['status'=>'approved','user_full_name' => $to_full_name];
                try {
                    Mail::to($from_email)->send(new ChangeStatusRequestEmail($data));
                    $token=$to_wallet_informations->device_token;

                    $notificationTemplate=NotificationTemplate::where('type','request approved')->first();
                    $title=$notificationTemplate->title;
                    $content=$notificationTemplate->content;
                    $type=$notificationTemplate->type;

                    $old = ["user_full_name"];
                    $new   = [$from_full_name];

                    $resContent = str_replace($old, $new, $content);



                    $this->sendNotification($to_wallet_informations,$token,$title,$resContent,$type);

                } catch (\Throwable $th) {
                    return response()->json(['message' => 'Transaction Successfully Created But Can Not Send Email'], 200);
                }




                return response()->json(['message' => 'Transaction Successfully Created'], 200);

            }else{
                return response()->json(['message' => 'Your Current Balance is not Sufficient for a Transfer']);
            }

            //return response()->json(['message' => 'Transaction Successfully Created'], 200);

        }else{
            $transactionID=$request['transaction_id'];

            $transaction = Transaction::find($transactionID);
            $transaction->transaction_status_id = 3;
            $transaction->transaction_date = now();
            $transaction->save();

            $from_wallet = Wallet::find($transaction->from_wallet_id);
            $to_wallet = Wallet::find($transaction->to_wallet_id);

            $from_wallet_informations=$from_wallet->getUserInformations;
            $to_wallet_informations=$to_wallet->getUserInformations;

            $to_full_name=$to_wallet_informations->full_name;
            $from_full_name=$from_wallet_informations->full_name;
            $from_email=$from_wallet_informations->email;
            $data = ['status'=>'decline','user_full_name' => $to_full_name];
            try {
                Mail::to($from_email)->send(new ChangeStatusRequestEmail($data));
                $token=$to_wallet_informations->device_token;

                $notificationTemplate=NotificationTemplate::where('type','request decline')->first();
                $title=$notificationTemplate->title;
                $content=$notificationTemplate->content;
                $type=$notificationTemplate->type;

                $old = ["user_full_name"];
                $new   = [$from_full_name];

                $resContent = str_replace($old, $new, $content);




                $this->sendNotification($to_wallet_informations,$token,$title,$resContent,$type);
            } catch (\Throwable $th) {
                return response()->json(['message' => 'Transaction Successfully Created But Can Not Send Email'], 200);
            }




            return response()->json(['message' => 'Transaction Successfully Rejected'], 200);
        }
    }
}

<?php

namespace Modules\TransactionModule\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Modules\CoreModule\Entities\ExchangeRate;
use Modules\CoreModule\Entities\Fee;
use Modules\TransactionModule\Entities\Transaction;
use App\Models\Withdraw;
use App\Traits\ErrorHandlingTraits;
use App\Traits\WalletTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Webpatser\Uuid\Uuid;

class WithdrawaController extends Controller
{
    use WalletTrait , ErrorHandlingTraits;
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_wallet_id' => 'required | numeric | exists:wallets,id',
            'transaction_amount' => 'required | numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }


        $user = Auth::user();
        $transactionAmount=$request['transaction_amount'];

        $fromWallet=$user->getUserWallets->first();
        $fromWalletID=$fromWallet->id;
        $fromUser=$fromWallet->getUserInformations;
        $fromCurrency=$fromWallet->currency;

        $toWalletID=$request['to_wallet_id'];
        $toWallet=$this->getWalletByID($toWalletID);
        $toUser=$toWallet->getUserInformations;
        $toCurrency=$toWallet->currency;

        $fee=$this->getFee('withdraw',$fromUser->country,$transactionAmount);

        $transactionAmountAfterExchangeRate = $transactionAmount;
        $exchangeRate=0;

        if ($fromCurrency != $toCurrency) {

            $exchangeRate=$this->getExchangeRate($fromCurrency,$toCurrency);

            if ($exchangeRate == null) {
                return response()->json(['message' => "inValid Exchange Rate",401]);
            }
            $transactionAmountAfterExchangeRate *= $exchangeRate;

        }

        if ($fee == null) {
            return response()->json(['message' => "inValid Fee",401]);
        }


        $transaction =  $this->createWithdrawTransaction(3,1,$fromWalletID,$toWalletID,$toWalletID,$transactionAmount,$fromCurrency,$exchangeRate,$fee,$transactionAmountAfterExchangeRate,$toCurrency);
        return response()->json(['success' => 'true','message'=>'done','transaction'=>$transaction], 200);

        //dd($transaction);
    }

    public function getWithdrawTransaction($statusID)
    {
        $user = Auth::user();
        $wallet=$user->getUserWallets->first();
        $walletID=$wallet->id;

        $transactions=Transaction::with(
            [
            'getTransactionStatus:id,transaction_status_name',
            ]
        )->where('transaction_status_id', $statusID)->where('transaction_type_id',3)
            ->Where(function($q) use ($walletID){
                $q->where('from_wallet_id',$walletID );
                $q->orwhere('to_wallet_id',$walletID);
            })->get();

        // $transactions = Transaction::where('transaction_status_id', $statusID)->where('transaction_status_id', $statusID)->where('from_wallet_id', $walletID)->orWhere('to_wallet_id', $walletID)->get();

        foreach ($transactions as $transaction) {
            $transaction->setAttribute('added_at', $transaction->created_at->diffForHumans());
            $transaction->setAttribute('from_user', $transaction->getWalletInformationsFrom->getUserInformations->full_name);
            $transaction->setAttribute('to_user', $transaction->getWalletInformations->getUserInformations->full_name);
            //$transaction->setAttribute('transaction_status', $transaction->getTransactionStatus->transaction_status_name);
            $transaction->setAttribute('transaction_type', 'withdraw');
        }
        return response()->json(['transactions' => $transactions], 200);
    }

    public function accepted($transactionID)
    {
        $transaction=Transaction::find($transactionID);
        if (!$transaction) {
            return response()->json(['message' => "inValid Transaction",401]);
        }
        $transaction->transaction_status_id=4;
        $transaction->save();

        return response()->json(['success' => 'true','message'=>'done','transaction'=>$transaction], 200);
    }

    public function decline($transactionID)
    {
        $transaction=Transaction::find($transactionID);
        if (!$transaction) {
            return response()->json(['message' => "inValid Transaction",401]);
        }
        $transaction->transaction_status_id=3;
        $transaction->save();

        return response()->json(['success' => 'true','message'=>'done','transaction'=>$transaction], 200);
    }

    public function generateUUID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required | numeric | exists:transactions,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }
        $transactionID=$request['transaction_id'];

        //$transaction=Transaction::find($transactionID);
        $uuidCode=(string)Uuid::generate();
        $withdraw=new Withdraw();
        $withdraw->transaction_id=$transactionID;
        $withdraw->uuid=$uuidCode;
        $withdraw->save();

        return response()->json(['success' => 'true','message'=>'done','uuidCode'=>$uuidCode], 200);


    }


    public function scanning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required  | exists:withdraws,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => 'false','status'=>$this->getStatusCode(400),'error'=>$validator->errors()], 400);
        }
        $uuid=$request['uuid'];
        $withdraw=Withdraw::where('uuid',$uuid)->first();

        $transactionID=$withdraw->transaction_id;
        $transaction=Transaction::find($transactionID);
        if (!$transaction) {
            return response()->json(['message' => "inValid Transaction",401]);
        }


        $fromWalletID=$transaction->from_wallet_id;
        $fromWallet=$this->getWalletByID($fromWalletID);
        $fromUser=$fromWallet->getUserInformations;

        $toWalletID=$transaction->to_wallet_id;
        $toWallet=$this->getWalletByID($toWalletID);
        $toUser=$toWallet->getUserInformations;

        $transactionTotal=$transaction->total;

        if ($fromWallet->balance < $transactionTotal) {
            return response()->json(['message' => "inValid Balance",401]);
        }

        $fromWallet->balance -= $transactionTotal;
        $fromWallet->save();

        $toWallet->balance += $transaction->to_amount;
        $toWallet->save();

        $transaction->transaction_status_id=2;
        $transaction->save();

        $withdraw->delete();

        return response()->json(['success' => 'true','message'=>'done'], 200);
    }

    private function getFee($transactionType,$country,$amount)
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
            return null;
        }
    }

    private function getExchangeRate($from,$to)
    {
        $exchangeRate = ExchangeRate::where('from_currency', $from)->where('to_currency', $to)->first();
        if ($exchangeRate) {
            return $exchangeRate->exchange_rate;
        }
        return null;
    }

    private function createWithdrawTransaction($transactionType,$transactionStatus,$fromWalletId,$toWalletId,$agentWalletId,$transactionAmount,$currency,$exchange_rate,$transaction_amount_fee,$toAmount,$toCurrency)
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

        return $transaction;
    }



}

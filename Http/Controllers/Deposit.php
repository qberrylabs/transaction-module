<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\CoreModule\Http\Controllers\User\UserSingleton;
use Modules\CoreModule\Http\Controllers\Wallet\WalletSingleton;
use Modules\TransactionModule\Emails\SendDepositMail;
use App\Mail\SendReferenceNumberEmailToUser;
use Modules\CoreModule\Entities\InteracLoad;
use Modules\PaymentMethodeModule\Entities\PaymentMethod;
use App\Traits\OrderTrait;
use Modules\CoreModule\Traits\PaymentMethodTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

class Deposit extends TransactionOperation
{
    use PaymentMethodTrait , OrderTrait ;

    public  function index(){
        $paymentMethods=$this->getUserPaymentMethod();
        $referenceNumber =substr(md5(uniqid(rand(), true)), 0, 6);

        return view('transactionmodule::transaction.deposit',['paymentMethods'=>$paymentMethods,'referenceNumber'=>$referenceNumber]);
    }

    public  function build(Request $request){
        $this->validate($request, [
            'reference_number' => 'required',
            'amount_paid' => 'required ',
            'type'=> 'required'
        ]);

        $wallet=WalletSingleton::getUserWallet();
        $user=UserSingleton::getUser();

        $paymentMethod=$request['type'];
        $amountPaid=$request['amount_paid'];

        $paymentMethod=$this->checkPaymentMethod($paymentMethod);
        if ($paymentMethod == null) {
            return Redirect::back()->withErrors(['Payment Method Not Available']);
        }

        $checkPaymentMethodLimits=$this->checkPaymentMethodLimits($paymentMethod,$amountPaid);
        if ($checkPaymentMethodLimits == null) {
            return Redirect::back()->withErrors(["The Min {$paymentMethod->min} and Max {$paymentMethod->max}"]);
        }

        $input = $request->all();

        $input['name'] = $user->name;
        $input['email_name'] = $user->email;
        $input['currency'] = $wallet->currency;
        $input['user_id'] = $user->id;
        $input['amount_paid']=number_format((float)$amountPaid, 2, '.', '');


        $order = InteracLoad::create($input);

        $instructions=
        "
        <p  class='text-3 mb-1'>Go to the nearest bank and paid <span class='text-4 font-weight-500'>amount_paid</span> .</p>
        <p  class='text-3 mb-1'>And send the reference number <span class='text-4 font-weight-500'>reference_number</span>  to this email.</p>
        <p  class='text-3 mb-1'>And we will add the balance to you automatically</p>
        ";

        $old = ['reference_number','amount_paid'];
        $new   = [$order->reference_number,$order->amount_paid." ".$order->currency];
        $instructions = str_replace($old, $new, $instructions);

        try {
            Mail::to($user)->send(new SendDepositMail($order));
        } catch (\Throwable $th) {
            Session::flash('failed', 'The Email Has Not Sent');
        }



        return view('transactionmodule::transaction.components.transaction_success',['order'=>$order,"instructions"=>$instructions]);
        // return $this->transactionRedirect($order,$instructions);



    }

    // public  function build(Request $request){
    //     $this->validate($request, [
    //         'reference_number' => 'required',
    //         'amount_paid' => 'required ',
    //         'type'=> 'required'
    //     ]);
    //     $referenceNumber=$request['reference_number'];
    //     $amountPaid=$request['amount_paid'];
    //     $type=$request['type'];

    //     $paymentMethod=$this->checkPaymentMethod($type);
    //     if ($paymentMethod == null) {
    //         return Redirect::back()->withErrors(['Payment Method Not Available']);
    //     }

    //     $checkPaymentMethodLimits=$this->checkPaymentMethodLimits($paymentMethod,$amountPaid);
    //     if ($checkPaymentMethodLimits == null) {
    //         return Redirect::back()->withErrors(["The Min {$paymentMethod->min} and Max {$paymentMethod->max}"]);
    //     }

    //     $order=$this->checkOrder($referenceNumber,$amountPaid,$type);
    //     if ($order == null) {
    //         return Redirect::back()->withErrors(['Not Available']);
    //     }
    //     //$wallet=WalletSingleton::getUserWallet();

    //     $this->setType(2);
    //     $this->setStatus(2);
    //     $this->setFromWalletId($order->wallet_id);
    //     $this->setToWalletId($order->wallet_id);
    //     $this->setAmount($amountPaid);
    //     $this->setToAmount($amountPaid);
    //     $this->setFromCurrency($order->currency);
    //     $this->setToCurrency($order->currency);
    //     $this->setExchangeRate(0);
    //     $this->setFee($order->fee);
    //     $transaction=$this->saveTransaction();

    //     $toUser=$this->getUserInformationByWallet($transaction->from_wallet_id);
    //     event(new TransactionEvent('Deposit Create',$transaction,$toUser));
    //     // try {
    //     //
    //     //     Mail::to($toUser->email)->send(new CreateTransactionMail('Deposit Create',$transaction));
    //     // } catch (\Throwable $th) {
    //     //     Session::flash('failed', 'The Email Has Not Sent');
    //     // }

    //     return back()->with(['success'=>'Done']);



    // }


    public function transactionRedirect($order)
    {
        return view('transactionmodule::transaction.components.transaction_success',['order'=>$order]);
    }

    private function checkPaymentMethod($type)
    {

        $user=UserSingleton::getUser();
        $paymentMethods=PaymentMethod::where('name',$type)->where('country',$user->country)->first();

        if ($paymentMethods) {
            return $paymentMethods;
        }
        return null;

    }

    private function checkPaymentMethodLimits($paymentMethods,$amount)
    {

        $min=$paymentMethods->min;
        $max=$paymentMethods->max;

        if ($amount >= $min && $amount <= $max) {
            return $paymentMethods;
        }
        return null;
    }

    private function createNewReferenceNumber()
    {
        $user=UserSingleton::getUser();
        $wallet=WalletSingleton::getUserWallet();
        $referenceNumber =substr(md5(uniqid(rand(), true)), 0, 6);

        $interacLoad=new InteracLoad();
        $interacLoad->name=$user->name;
        $interacLoad->email_name=$user->email;
        $interacLoad->user_id=$user->id;
        $interacLoad->reference_number=$referenceNumber;
        $interacLoad->currency=$wallet->currency;
        $interacLoad->save();

        return $referenceNumber;

    }

    public function createReferenceNumber(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
        ]);
        $user=UserSingleton::getUser();
        $referenceNumber =substr(md5(uniqid(rand(), true)), 0, 6);

        $interacLoad=new InteracLoad();
        $interacLoad->name=$user->name;
        $interacLoad->email_name=$user->email;
        $interacLoad->user_id=$user->id;
        $interacLoad->reference_number=$referenceNumber;
        $interacLoad->type=$request['type'];
        $interacLoad->save();

        $data = [
            'title' => "Create Reference Number",
            'name' => "Create Reference Number Complete",
            'content' => "Your Reference Number is <span class='text-5 font-weight-500'>$referenceNumber</span> ",
        ];

        return $this->transactionRedirect($data);
    }

    public function sendReferenceNumberEmail(Request $request)
    {
        $user=UserSingleton::getUser();
        $referenceNumber=$request['referenceNumber'];
        try {
            Mail::to($user)->send(new SendReferenceNumberEmailToUser($referenceNumber));
        } catch (\Throwable $th) {
            return response()->json(['message'=>'The email has not been sent successfully'], 400);
            //throw $th;
        }
        return response()->json(['message'=>'The email has been sent successfully'], 200);

    }

    public function depositFee(Request $request)
    {
        $country=UserSingleton::getUser()->country;
        $paymentMethod=$request['paymentMethod'];
        $amount=$request['amount'];
        return $this->getDepositFee($country,$paymentMethod,$amount);
    }

    public function getUserReferenceNumber()
    {
        $user=UserSingleton::getUser();
        $interacLoads=$user->getUserInteracLoads()->where('is_available',0)->first();

        $referenceNumber=null;
        if ($interacLoads) {
            $referenceNumber=$interacLoads->reference_number;
        }else{
            $referenceNumber=$this->createNewReferenceNumber();
        }


        return $referenceNumber;
    }
}

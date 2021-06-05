<?php

namespace Modules\TransactionModule\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TransactionModule\Entities\Transaction;

class TransactionController extends Controller
{

    public function getUserFullName($id)
    {
        return User::find($id)->full_name;
    }

    public function getTransactionsByType($typeID,$name)
    {
        $transactions=Transaction::with(
            [
                'getWalletFromInformations.getUserInformations:id,full_name',
                'getWalletInformations.getUserInformations:id,full_name',
                'getAgentInformationsByWallet.getUserInformations:id,full_name',
                'getTransactionStatus',
                'getTransactionType'
            ])
        ->where('transaction_type_id',$typeID)->orderBy('created_at','DESC')->get();
        //dd($transactions);
        return view('transactionmodule::admin.transactions.index',['transactions'=>$transactions,'transaction_type'=>$name]);

    }


}

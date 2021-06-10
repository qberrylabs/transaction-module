<?php

namespace Modules\TransactionModule\Http\Controllers;

use Modules\CoreModule\Http\Controllers\Wallet\WalletSingleton;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\TransactionModule\Entities\Transaction;
use Modules\TransactionModule\Traits\TransactionTraits;

class TransactionController extends Controller
{
    use TransactionTraits;

    public function index()
    {
        $transactions=$this->getUserTransactions();
        $wallet=WalletSingleton::getUserWallet();
        //dd($transactions);

        return view('transactionmodule::transaction.index',['transactions'=>$transactions,'wallet'=>$wallet]);
    }

    public function transactionFilter(Request $request)
    {
        $date=$request['date'];
        $dateExplode=explode("-", $date);

        $from=strtotime(trim($dateExplode[0]));
        $fromDate=date('Y-m-d',$from);

        $to=strtotime(trim($dateExplode[1]));
        $toDate=date('Y-m-d',$to);

        //dd($fromDate);

        $transactions=$this->getUserTransactionsFilter($fromDate,$toDate);
        $wallet=WalletSingleton::getUserWallet();

        return view('transactionmodule::transaction.index',['transactions'=>$transactions,'wallet'=>$wallet]);
    }

    public function getTransactionDetails($id)
    {
        $transaction=Transaction::find($id);
        return response()->json($transaction, 200);

    }

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

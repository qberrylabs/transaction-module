<?php

namespace Modules\TransactionModule\Entities;

use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\Wallet;
use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use ClearsResponseCache;
    protected $fillable = [
        'transaction_type_id', 'transaction_status_id', 'from_wallet_id','to_wallet_id','agent_wallet_id','transaction_amount','transaction_date','transaction_currency','exchange_rate','transfer_fee'
    ];

    public function getAgentInformationsByWallet()
    {
        return $this->belongsTo(Wallet::class,'agent_wallet_id');
    }


    public function getWalletFromInformations()
    {
        return $this->belongsTo(Wallet::class,'from_wallet_id');
    }

    public function getWalletInformations()
    {
        return $this->belongsTo(Wallet::class,'to_wallet_id');
    }

    public function getTransactionStatus()
    {
        return $this->belongsTo(TransactionStatus::class,'transaction_status_id');
    }
    public function getTransactionType()
    {
        return $this->belongsTo(TransactionType::class,'transaction_type_id');
    }

}

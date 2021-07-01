<?php

namespace Modules\TransactionModule\Entities;

use Modules\TransactionModule\Entities\TransactionStatus;
use Modules\TransactionModule\Entities\TransactionType;
use Modules\CoreModule\Entities\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{

    protected $guarded = [];

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

    public function getWalletInformationsFrom()
    {
        return $this->belongsTo(Wallet::class,'from_wallet_id');
    }

}

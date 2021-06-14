<?php

namespace Modules\TransactionModule\Entities;

use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Model;

class TransactionStatus extends Model
{
    use ClearsResponseCache;
}

<?php

namespace Modules\TransactionModule\Entities;

use App\Traits\ClearsResponseCache;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    use ClearsResponseCache;
}

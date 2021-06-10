<?php

namespace Modules\TransactionModule\Events;

use Illuminate\Queue\SerializesModels;

class TransactionEvent
{
    use SerializesModels;

    public $type;
    public $transaction;
    public $toUser;

    public function __construct($type,$transaction,$toUser)
    {
        $this->type=$type;
        $this->transaction=$transaction;
        $this->toUser=$toUser;
    }
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}

<?php

namespace Modules\TransactionModule\Listeners;

use Modules\TransactionModule\Events\TransactionEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Modules\TransactionModule\Emails\CreateTransactionMail;
use Modules\TransactionModule\Http\Controllers\TransactionNotificationTemplate;

class SendTransactionAlert
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param TransactionEvent $event
     * @return void
     */
    public function handle(TransactionEvent $event)
    {
        $type=$event->type;
        $transaction=$event->transaction;
        $toUser=$event->toUser;

        try {
            Mail::to($toUser->email)->send(new CreateTransactionMail($type,$transaction));
        } catch (\Throwable $th) {
            Session::flash('failed', 'The Email Has Not Sent');
        }

        $transactionNotificationTemplate= new TransactionNotificationTemplate($type,$toUser,$transaction);
        $transactionNotificationTemplate->sendNotification();
    }
}

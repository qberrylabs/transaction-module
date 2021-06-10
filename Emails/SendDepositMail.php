<?php

namespace Modules\TransactionModule\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\CoreModule\Entities\Template;

class SendDepositMail extends Mailable
{
    use Queueable, SerializesModels;

    //public $paymentMethod;
    //public $referenceNumber;
    public $order;

    public function __construct($order)
    {
        //$this->paymentMethod=$paymentMethod;
        $this->order=$order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order=$this->order;
        $paymentMethod="deposit {$order->type}";
        $emailTemplate=Template::where('name',$paymentMethod)->first();

        $old = ['reference_number','deposit_amount'];
        $new   = [$order->reference_number,$order->amount_paid];
        $content = str_replace($old, $new, $emailTemplate->content);

        $address = env('MAIL_FROM_ADDRESS');
        $subject = $emailTemplate->subject;
        $name = env('MAIL_FROM_NAME');

        return $this->view('emails.send_mail')
                    ->from($address, $name)
                    ->cc($address, $name)
                    ->bcc($address, $name)
                    ->replyTo($address, $name)
                    ->subject($subject)
                    ->with(['content' => $content] );
    }
}

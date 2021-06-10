<?php

namespace Modules\TransactionModule\Emails;

use Modules\CoreModule\Entities\Template;
use Modules\CoreModule\Traits\EmailTemplateTrait;
use Modules\CoreModule\Traits\WalletTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\TransactionModule\Http\Controllers\TransactionEmailTemplate;
use Modules\TransactionModule\Traits\TransactionTraits;

class CreateTransactionMail extends Mailable
{
    use Queueable, SerializesModels , TransactionTraits , WalletTrait , EmailTemplateTrait;

    public $type;
    public $transaction;

    public function __construct($type,$transaction)
    {
        $this->type=$type;
        $this->transaction=$transaction;
    }


    public function build()
    {
        $type=$this->type;

        $emailTemplate=Template::where('name',$type)->first();

        $transactionEmailTemplate=new TransactionEmailTemplate($type,$this->transaction);
        $content=$transactionEmailTemplate->getTransactionEmailTemplate();

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

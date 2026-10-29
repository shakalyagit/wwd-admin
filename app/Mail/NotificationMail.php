<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mail_subject;
    public $body;

    public function __construct($mail_subject, $body)
    {
        $this->mail_subject = $mail_subject;
        $this->body = $body;
    }

    public function build()
    {
        return $this->subject($this->mail_subject)
                    ->view('emails.notification');
    }
}

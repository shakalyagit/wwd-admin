<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// Sends an email already rendered from the shared email templates (see EmailTemplateRenderer)
class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mail_subject;
    public $mail_html;

    public function __construct($mail_subject, $mail_html)
    {
        $this->mail_subject = $mail_subject;
        $this->mail_html = $mail_html;
    }

    public function build()
    {
        return $this->subject($this->mail_subject)
                    ->html($this->mail_html);
    }
}

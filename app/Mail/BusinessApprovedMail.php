<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BusinessApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $business;
    public $user;

    public function __construct($business, $user)
    {
        $this->business = $business;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Your Business Has Been Approved 🎉')
                    ->view('emails.business-approved');
    }
}


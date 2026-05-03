<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * FIX #13: Accept and pass the rejection reason so the company
 * knows why their registration was denied.
 */
class CompanyRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $reason;

    public function __construct(string $reason = 'No reason provided')
    {
        $this->reason = $reason;
    }

    public function build(): self
    {
        return $this->subject('Company Registration Rejected')
                    ->view('emails.company_rejected')
                    ->with(['reason' => $this->reason]);
    }
}
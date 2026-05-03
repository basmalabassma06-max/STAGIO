<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InternshipStatusMail extends Mailable
{
    use SerializesModels;

    public $status;
    public $files;

    public function __construct($status, $files = [])
    {
        $this->status = $status;
        $this->files  = $files;
    }

    public function build()
    {
        $mail = $this->view('emails.status')
            ->with(['status' => $this->status])
            ->subject('Internship ' . $this->status);

        foreach ($this->files as $file) {
            $mail->attach($file);
        }

        return $mail;
    }
}
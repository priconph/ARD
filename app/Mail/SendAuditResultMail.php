<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAuditResultMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;
    protected $auditResultAttachment;
    protected $emailSubject;
    protected $auditResultType;
    public function __construct($data, $auditResultAttachment, $emailSubject, $auditResultType)
    {
        $this->data = $data;
        $this->auditResultAttachment = $auditResultAttachment;
        $this->emailSubject = $emailSubject;
        $this->auditResultType = $auditResultType;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->data;
        $auditResultType = $this->auditResultType;
        $mail = $this->subject($this->emailSubject)
                     ->view('mail.send_audit_result', compact(['data', 'auditResultType']));
        foreach ($this->auditResultAttachment as $attachment) {
            $extension = pathinfo($attachment->filename, PATHINFO_EXTENSION);
            // dd($extension);

            $filePath = storage_path(
                'app/public/' . $attachment->path . $attachment->id . '.' . $extension
            );

            if (file_exists($filePath)) {
                $mail->attach(
                    $filePath,
                    [
                        'as'   => $attachment->filename,
                    ]
                );
            }
        }
        return $mail;
    }
}

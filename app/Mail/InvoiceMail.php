<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfBinary,
        public string $fileName
    ) {}

    public function build()
    {
        $subject = app()->getLocale() === 'ar'
            ? "فاتورة رقم {$this->invoice->number}"
            : "Invoice {$this->invoice->number}";

        return $this->subject($subject)
            ->view('emails.invoice') 
            ->with(['invoice' => $this->invoice])
            ->attachData($this->pdfBinary, $this->fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}

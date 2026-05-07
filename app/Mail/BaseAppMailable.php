<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

abstract class BaseAppMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Seconds before the queue worker stops this send (SMTP + rendering + PDF attachments).
     * Keeps {@see SendQueuedMailable} from failing at the default worker timeout (often 60s).
     */
    public int $timeout = 180;

    abstract protected function subjectLine(): string;

    abstract protected function viewName(): string;

    abstract protected function viewData(): array;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(view: $this->viewName(), with: $this->viewData());
    }
}

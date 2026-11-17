<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A message sent from one of the "Get in touch" forms.
 */
class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public const string FEEDBACK = 'feedback';

    public const string SUGGEST_TERM = 'suggest-term';

    public const string SUGGEST_TRANSLATION = 'suggest-translation';

    public function __construct(
        public string $kind,
        public string $senderName,
        public string $senderEmail,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->senderEmail, $this->senderName)],
            subject: match ($this->kind) {
                self::FEEDBACK => 'GET-IT! Website feedback from '.$this->senderName,
                default => 'GET-IT! New term suggestions from '.$this->senderName,
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact',
            with: [
                'intro' => match ($this->kind) {
                    self::FEEDBACK => 'has submitted the following feedback to the GET-IT Glossary:',
                    self::SUGGEST_TERM => 'has made the following new term suggestions for the GET-IT Glossary:',
                    default => 'would like to propose the following GET-IT Glossary translation:',
                },
                'actionUrl' => $this->kind === self::FEEDBACK ? url('/login') : url('/terms'),
            ],
        );
    }
}

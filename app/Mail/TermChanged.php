<?php

namespace App\Mail;

use App\Models\Term;
use App\Models\User;
use App\Support\TermPresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the administrators that a new version of a term has been saved,
 * either by an edit or by rolling back to an earlier version.
 */
class TermChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Term $term,
        public User $updatedBy,
        public bool $rolledBack = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rolledBack
                ? 'GET-IT! A term has been rolled back and a new version created'
                : 'GET-IT! A term has been updated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.term-changed',
            with: [
                'heading' => $this->rolledBack
                    ? 'A GET-IT Glossary term has been rolled back and a new version created:'
                    : 'A GET-IT Glossary term has been updated:',
                'entry' => TermPresenter::full($this->term),
            ],
        );
    }
}

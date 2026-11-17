<?php

namespace App\Mail;

use App\Models\Term;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a user they have been made an editor of a term.
 */
class NewTermEditor extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Term $term) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'GET-IT! You are now an editor of the glossary term: '.$this->term->term,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.new-term-editor');
    }
}

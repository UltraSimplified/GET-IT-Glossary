<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the three "Get in touch" forms.
 */
class ContactController extends Controller
{
    public function feedback(Request $request): RedirectResponse
    {
        return $this->send($request, ContactMessage::FEEDBACK, 'feedback', config('glossary.contact.feedback'));
    }

    public function suggestTerm(Request $request): RedirectResponse
    {
        return $this->send($request, ContactMessage::SUGGEST_TERM, 'terms', config('glossary.contact.suggestions'));
    }

    public function suggestTranslation(Request $request): RedirectResponse
    {
        return $this->send($request, ContactMessage::SUGGEST_TRANSLATION, 'suggestion', config('glossary.contact.suggestions'));
    }

    private function send(Request $request, string $kind, string $field, ?string $recipient): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            $field => ['required', 'string', 'max:10000'],
            // Hidden from people; anything typed here came from a bot.
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        if (filled($data['website'] ?? null)) {
            // Look like success so the bot has nothing to learn from.
        } elseif (blank($recipient)) {
            Log::warning('Contact form submitted but no recipient address is configured.', ['kind' => $kind]);
        } else {
            Mail::to($recipient, config('glossary.contact.name'))
                ->send(new ContactMessage($kind, $data['name'], $data['email'], $data[$field]));
        }

        return redirect('/get-in-touch/'.$kind)->with('contact.sent', true);
    }
}

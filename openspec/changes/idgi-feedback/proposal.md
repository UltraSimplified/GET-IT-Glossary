# Proposal

## Why

The "I don't get it" monkey tells editors that a definition did not help, but not why. Every edition of the glossary has only ever counted the clicks: no form was ever built, though the original stylesheet kept an unused class for one. Editors can see that a definition is failing readers, but have nothing to go on when rewriting it. Asking the reader, in a sentence, what did not make sense turns the count into something editors can act on.

## What Changes

- After a reader presses the monkey, the click is counted exactly as now. The box then offers a short, optional form: "What didn't make sense?", with a send button. Readers who don't want to write anything need do nothing more.
- A reply is anonymous. Only the reader's words are kept, with the term version they were reading and the date. No name, email address or IP address is stored, and the form asks readers not to include personal details.
- A reply is accepted only from a reader who has just pressed the monkey for that term in the same browser session. It is limited in length and in how often it can be sent, and has a hidden field to catch automated spam.
- On a term's edit screen, beside the IDGI score, editors and administrators see the term's replies, newest first, each marked with the version it was about. New replies are listed first; ones marked "dealt with" stay as a record and can be shown on request.
- Anyone who can edit the term can mark a reply as dealt with, or as new again. Administrators can also delete a reply, for spam.

The change touches both the **public glossary** (the "Still don't get it?" box on term pages) and the **admin screens** (the term edit screen). It **adds one public address**, for sending a reply. It does not change any existing URL, the `/v1` API, the widget or the import command, so existing links and embeds keep working. The existing click address and the IDGI count behave exactly as before.

It **adds a migration**: one new table for replies. No existing data changes and nothing needs importing again.

## Capabilities

### New Capabilities

- `definition-feedback`: how readers tell editors a definition did not help them (the "I don't get it" count and the optional reply), and how editors review those replies.

### Modified Capabilities

None. No specs exist yet.

## Impact

- **Public glossary:** `IdgiPrompt.vue` gains the optional reply form after a click, with its text in `lang/en/interface.php`. A new `POST /idgi/{term}/reply` route and controller accept replies; `IdgiController` also notes the click in the session.
- **Admin screens:** the term edit screen (`TermForm.vue` sidebar, fed by `Admin\TermController::edit`) lists the term's replies. New routes let editors mark a reply as dealt with or new again, and let administrators delete one.
- **Back end:** a new `IdgiReply` model and `idgi_replies` table, linked to the term version (`term_id`) and to the term as a whole (`master_id`).
- **Out of scope:** a list of replies across all terms, emailing editors about new replies, replying to readers, and any change to how the IDGI count or score is worked out.

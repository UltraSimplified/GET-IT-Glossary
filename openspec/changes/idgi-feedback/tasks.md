# Tasks

## 1. Storing replies

- [x] 1.1 Add a migration creating `idgi_replies` (`term_id` foreign key to `terms` with cascade on delete, indexed `master_id`, `message` text, `handled_at`, `handled_by` foreign key to `users` with null on delete, timestamps), an `IdgiReply` model with `term` and `handler` relations, and a factory. Verify with feature tests that a reply belongs to its version and its term, that deleting the term's row deletes its replies, and that deleting the handling user clears `handled_by`. Then run `php artisan migrate` locally and confirm only the new table was added.

## 2. Readers sending a reply

- [x] 2.1 Make `IdgiController` note the click in the session (`idgi.reply.{term id}`), still counting it without touching `updated_at`. Add `POST /idgi/{term}/reply`, throttled to 6 a minute. It accepts only published versions, requires and then removes the session note, trims the message and checks it holds 1 to 1,000 characters, and discards a filled-in hidden `website` field while returning success. Verify with feature tests:
  - a reply after a click is stored with its text, version and date, and with no visitor details
  - refused without a click, and a second reply for one click refused
  - an empty reply and a 1,001-character reply refused with their messages
  - the hidden field discards the reply but returns success
  - draft, archived and revision versions refused
  - the throttle applies
  - the click count and `updated_at` behave as before
- [x] 2.2 Add the reply form to `IdgiPrompt.vue`, shown after a successful click: a labelled box, the note about personal details, the characters left, and Send. Focus moves to the box. Validation messages show beside it, a failure suggests pressing the monkey again, and success shows a thank-you line in place of the form. Its text goes in `lang/en/interface.php`. Verify in the browser, with the keyboard only and at desktop and phone widths: the form appears after a click, a reply sends and is thanked, an empty send shows its message, and nothing scrolls sideways.
- [x] 2.3 Add feature tests that replies never reach the public: a term page with replies sends none in its data to a visitor or to a signed-in contributor, and `/v1/terms`, `/v1/terms/{term}` and `/widget` respond exactly as before.

## 3. Editors reading and handling replies

- [x] 3.1 Add a `replies` prop to `Admin\TermController::edit`: every reply for the term's `master_id`, new ones first, then those dealt with, newest first within each. Each carries its id, message, date and version, whether that version is earlier than the one being edited, and, once handled, who handled it and when. Verify with feature tests for the order, the earlier-version flag and the handled fields, and that someone who isn't an editor of the term is still refused the edit screen.
- [x] 3.2 Add `IdgiReplyPolicy` and the routes `PATCH /idgi-replies/{reply}` (`handled: true|false`) and `DELETE /idgi-replies/{reply}`, both behind `auth`. Verify with feature tests:
  - an editor of the term marks a reply dealt with (recording who and when) and new again (clearing both)
  - a user who isn't an editor of the term is refused and nothing changes
  - an editor who isn't an administrator can't delete
  - an administrator deletes a reply
- [x] 3.3 Add an `IdgiReplies.vue` panel to the `TermForm` sidebar, below `IdgiScore`. It lists new replies with their date and version, and marks those about an earlier version. Dealt-with replies sit inside a collapsed `<details>`, each with who handled it and when. Each reply has "Mark dealt with" or "Mark as new"; administrators also get "Delete", with a confirm dialog. Requests use `preserveState`, `preserveScroll` and `only: ['replies']`. Messages are rendered as text, never as HTML. Its text goes in `lang/en/interface.php`. Verify in the browser, at desktop and phone widths: marking a reply dealt with while the term form has unsaved changes keeps those changes, and a reply containing `<b>` and `<script>` shows as typed.
- [x] 3.4 Verify with a feature test that saving a term as a new version, updating a version and rolling back all leave its replies in place, marked as about the version they were sent for.
- [x] 3.5 Document the feature in the README: what readers see after pressing the monkey, where editors find and handle replies, that replies keep no personal details, and, in the import section, that `glossary:import` removes replies along with the terms it replaces. Verify with `npx vp check`.

## 4. Integration

- [x] 4.1 Check the whole path in the browser, using throwaway test accounts and replies that are removed afterwards by their exact ids, never by pattern. Path: a visitor presses the monkey on a published term and sends a reply; the term's editor sees it on the edit screen and marks it dealt with; an administrator deletes it. Check at desktop and phone widths, and confirm the visitor's term page shows no replies throughout.
- [x] 4.2 Run `composer ci:check` and verify that it passes.

## Workflow follow-up

- The maintainer reviews and commits the change, and runs `php artisan migrate` wherever it is deployed. Agents do not commit, push or deploy.
- Archive the change with `/opsx:archive` after review.

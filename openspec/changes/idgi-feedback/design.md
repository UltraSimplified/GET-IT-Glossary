# Design

## Context

See `proposal.md` for why, and `specs/definition-feedback` for the required behaviour.

Today the monkey is `IdgiPrompt.vue` in the term page's sidebar. Pressing it posts to `POST /idgi/{term}` (throttled to 10 a minute), and `IdgiController` adds one to the term row's `IDGI` column through the query builder, so `updated_at` is left alone. The component then shows the "thanks" picture. The count belongs to a version: `SaveTermVersion` starts each new version at `IDGI = 0`, and editors see the score (IDGI as a share of hits) on the term list, the edit screen and the version comparison.

Every version of a term is its own `terms` row. Rows of one term share a `master_id`, and editor assignments (`terms_editors.term_id`) point at it. `User::canEdit($term)` is true for administrators and for editors assigned to the term's `master_id`. The edit screen (`Admin\TermController::edit`) refuses anyone else.

The contact forms already use the same defences this needs: a `throttle` middleware and a hidden `website` field that people never fill in. The term edit screen is one large `TermForm` with a leave-without-saving guard.

## Goals / Non-Goals

**Goals:**

- No change to how clicks are counted or scored.
- No personal data stored with a reply.
- Handling a reply never disturbs an editor's unsaved changes to the term.

**Non-Goals:**

- Telling a term's editors by email, or listing replies across terms (see the proposal's out-of-scope list).
- Moderating replies before editors see them. They are only ever shown to the term's editors and administrators.

## Decisions

### 1. An `idgi_replies` table, linked to the version and to the term

| Column        | Notes                                                                       |
| ------------- | --------------------------------------------------------------------------- |
| `id`          |                                                                             |
| `term_id`     | The version the reader was looking at. Foreign key to `terms`, cascade on delete. |
| `master_id`   | The term as a whole, indexed, so one query lists every version's replies.   |
| `message`     | Text, up to 1,000 characters, trimmed.                                      |
| `handled_at`  | Null while new.                                                             |
| `handled_by`  | Who marked it dealt with. Foreign key to `users`, null on delete.           |
| `created_at`, `updated_at` |                                                                |

- *Why:* the version tells editors whether a reply is about wording that has since changed, matching how the count works. `master_id` avoids a join through `terms` to list a term's replies, as `terms_editors` already does.
- *Why cascade:* `glossary:import` clears `terms` with plain deletes, so replies go with the terms they belong to, and the import needs no change.
- *Rejected:* adding replies to `comments`. Comments are signed editors' discussion shown to every signed-in editor, with an author. Replies are anonymous, need a handled state, and have different permissions.

### 2. One reply per click, tied to the session

When `IdgiController` counts a click, it also puts `idgi.reply.{term id}` in the session. `POST /idgi/{term}/reply` pulls that key, so the reply is accepted once, and only after a real click on that same version. Without the key, the request is refused with 403.

- *Why:* spam scripts would have to load a page and press the monkey first, and each click allows only one reply. It costs nothing for real readers, who always click first.
- *Rejected:* a CAPTCHA, which is heavy for a one-line reply and hard for some readers.

### 3. The usual defences for public forms

The reply route is throttled to 6 a minute, like the contact forms. The form carries the same hidden `website` field. When that field is filled in, the controller returns the usual success response without storing anything, so a bot learns nothing. Only published versions (`enabled`) accept replies; anything else is a 404.

The throttle keys on the visitor's IP, but only as a short-lived hashed cache entry, as Laravel does for every throttled route. Nothing about the visitor is written to the database.

### 4. The reader's form, in `IdgiPrompt.vue`

After a successful click, the "thanks" state reveals a small form: a labelled text box ("What didn't make sense? Optional."), a note asking readers not to include their name or other personal details, a count of characters left, and **Send**. Focus moves to the text box, because the monkey button becomes disabled. Sending uses the existing `postJson` helper, shows any validation message beside the box, and on success replaces the form with a thank-you line. All the text lives in `lang/en/interface.php`.

### 5. Replies on the edit screen, below the IDGI score

`Admin\TermController::edit` adds a `replies` prop: every reply for the term's `master_id`. Each one carries its id, message, date, version, whether that version is earlier than the one being edited, and, once handled, who handled it and when. New replies come first, newest first, then those dealt with. `TermForm`'s sidebar shows them in a panel below `IdgiScore`, with the dealt-with ones inside a collapsed `<details>`. Messages are rendered with text interpolation, never `v-html`.

### 6. Handling replies without losing the editor's work

- `PATCH /idgi-replies/{reply}` with `handled: true|false` marks a reply dealt with (setting `handled_at` and `handled_by`) or new again (clearing both).
- `DELETE /idgi-replies/{reply}` deletes one, after a confirm dialog.

An `IdgiReplyPolicy` allows the update to anyone who `canEdit` the reply's term, and the delete to administrators only. Both routes sit behind `auth`.

The panel makes these requests with Inertia's `router`, using `preserveState: true`, `preserveScroll: true` and `only: ['replies']`. Only the replies are reloaded, so anything the editor has typed into the term form stays as it was. These are not GET visits, so the leave guard doesn't fire.

## Risks / Trade-offs

- **[A reader types personal details anyway]** → The form asks them not to. The text is only ever shown to the term's editors and administrators, and administrators can delete a reply.
- **[Spam gets through the click-first and throttle checks]** → Administrators can delete it. If it becomes a problem, a per-term daily limit can be added without changing the specs.
- **[Importing the old site again removes replies]** → `glossary:import` replaces every term, and the cascade takes their replies with it. The import is a one-off migration tool that already replaces all editing done here; the README's import section will say that replies go too.
- **[A reader's session expires between the click and sending]** → The reply is refused. The form then says it couldn't be sent and suggests pressing the monkey again, which is rare enough to accept.

## Migration Plan

1. Deploy the code and run `php artisan migrate`, which adds `idgi_replies` and changes nothing else.
2. No data needs filling in: there have never been replies.

To roll back, run `php artisan migrate:rollback` and redeploy the previous code. The count and score are untouched either way.

## Open Questions

- **Exact wording of the prompt and thanks:** settle it when it's checked in the browser. The specs fix what the form asks, not its words.

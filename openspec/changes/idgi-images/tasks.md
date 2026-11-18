# Tasks

## 1. Storing the pictures

- [x] 1.1 Add a migration adding nullable `idgi_rest`, `idgi_hover` and `idgi_thanks` columns to `sites`, and add them to the `Site` model's fillable attributes and docblock. Verify with a feature test that a new site has all three empty. Then run `php artisan migrate` locally and confirm that only those three columns were added and the site row's other values are unchanged.

## 2. The Site screen

- [x] 2.1 Extend `SiteRequest` and `Admin\SiteController` for the three pictures. Validate them with `UploadedLogos::RULES`, accept the `remove_idgi_*` flags, and refuse a hover or thanks picture without a resting one, with the messages in `lang/en/interface.php`. Store uploads under `uploads/site/`, replacing and deleting old files, and make removing the resting picture remove all three. Also pass the current pictures to the edit screen. Verify with feature tests, extending `SiteDetailsTest`:
  - an administrator uploads all three
  - an SVG is refused and a 3 MB file is refused, each with its message
  - a thanks picture without a resting one is refused
  - removing the hover picture leaves the other two
  - removing the resting picture removes all three and deletes their files
  - a replaced picture's old file is deleted
  - someone who is not an administrator is refused
- [x] 2.2 Add the "I don't get it" pictures section to `admin/site/Edit.vue`. Each of the three slots has its label, a preview of what the button shows now (the uploaded picture or the built-in bubble), a file input and a remove checkbox, and its text goes in `lang/en/interface.php`. Verify in the browser at desktop and phone widths: the previews show the bubbles before any upload. Then upload this install's monkey pictures from `_legacy/public_html/assets/img/IDGI/` (resting `IDGI-off.png`, hover `IDGI-click.png`, thanks `IDGI-thanks.png`) and check that the previews show them.

## 3. The button

- [x] 3.1 Share `glossary.idgi` from `HandleInertiaRequests`: `null` without a resting picture, and otherwise `{rest, hover, thanks}` with the fallbacks applied. Verify with feature tests for no pictures, only a resting picture, and all three.
- [x] 3.2 Add `IdgiBubble.vue`. It is drawn in HTML and CSS in a 480 × 324 box, with the tail as an inline SVG, theme colours (`--ink`, and `--card`, `--brand-soft` or `--success-soft` by state) and `--font-headings`. Its text size scales with the box and its words wrap, it is `aria-hidden`, and its three phrases go in `lang/en/interface.php`. Make `IdgiPrompt.vue` show the site's pictures in the white panel when `glossary.idgi` is set, and the bubbles without the panel when it isn't. Verify in the browser:
  - the bubbles at rest, on hover, on keyboard focus and after the click, under the classic, AIM and YPAGS themes and in AIM's dark colours
  - a long test phrase wraps inside the bubble
  - the accessibility tree names the button only "I don't get it", with the bubbles and with uploaded pictures
  - nothing scrolls sideways at phone width
- [x] 3.3 Delete `public/assets/img/IDGI/` and add `/public/uploads` to `.gitignore`. Verify that no code refers to `assets/img/IDGI`, with a feature test that the folder no longer exists and that a term page doesn't mention it. Also verify that `git status` no longer lists newly uploaded files under `public/uploads`.
- [x] 3.4 Document the pictures in the README's "I don't get it" section: the built-in bubbles, replacing them on the Site screen with the recommended size, and that sites licensed for the monkey upload it themselves. Add a note in "Where things are" that uploads are not part of the repository. Verify with `npx vp check`.

## 4. Integration

- [x] 4.1 Check the whole path in the browser on a term page, using a throwaway test admin that is removed afterwards by its exact id:
  - the monkey uploaded in 2.2 shows at rest, on hover and after the click
  - removing only the hover picture makes hover show the resting picture
  - re-uploading `IDGI-click.png` restores it
  - this install ends with the monkey's three pictures set
  - check at desktop and phone widths
- [x] 4.2 Run `composer ci:check` and verify that it passes.

## Workflow follow-up

- The maintainer reviews and commits the change, and runs `php artisan migrate` wherever it is deployed. Agents do not commit, push or deploy.
- On each Minervation site that should keep the monkey, the maintainer uploads its three pictures on the Site screen after deploying.
- The maintainer and the licence holder decide whether to untrack the uploads already committed (`git rm -r --cached public/uploads`), and whether to clear the monkey and those uploads from Git history and force-push.
- Archive the change with `/opsx:archive` after review.

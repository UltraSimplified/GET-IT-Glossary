# Design

## Context

See `proposal.md` for why, and `specs/definition-feedback` and `specs/site-identity` for the required behaviour.

`IdgiPrompt.vue` hard-codes three files in `public/assets/img/IDGI/`: `IDGI-off.png` at rest, `IDGI-click.png` on hover or focus, and `IDGI-thanks.png` after the click. All are 480 × 324 PNGs drawn on white, shown in a white panel up to 240px wide. The button's accessible name comes from `aria-label` ("I don't get it"), and the picture has an empty `alt`. Nothing else uses the files. Identical copies are in the git-ignored `_legacy/` folders.

The site record already has a logo, uploaded on the Site screen through `Admin\SiteController` and `SiteRequest`. It is stored on the `uploads` disk under `site/` by the `UploadedLogos` helper, which also holds the shared upload rules (PNG, JPEG, GIF or WebP up to 2 MB). `HandleInertiaRequests` shares the site's details with every page as `glossary`.

`public/uploads` is not in `.gitignore`. Five content images and three pronunciation recordings are committed and pushed there already.

## Goals / Non-Goals

**Goals:**

- A fresh install looks finished without any licensed artwork.
- The built-in pictures suit every theme and language without extra files.
- Nothing uploaded on a site can end up in the repository.

**Non-Goals:**

- Choosing pictures per theme, or more than three states.
- Rewriting Git history. That is the maintainer's step; see the migration plan.

## Decisions

### 1. Three nullable columns on `sites`

`idgi_rest`, `idgi_hover` and `idgi_thanks` each hold the public path of an uploaded picture, like `logo`.

- *Why:* three fixed slots, each validated as a file, map directly onto columns. The site record already holds the logo this way.
- *Rejected:* one JSON column, which makes validating and clearing each slot clumsier for no gain. Pictures on the theme, which the user ruled out: the setting belongs to the site.

### 2. Fallbacks resolved on the server

`HandleInertiaRequests` adds `glossary.idgi`. It is `null` when the site has no resting picture, and otherwise `{rest, hover, thanks}`, with a missing hover or thanks picture replaced by the resting one. `IdgiPrompt` shows those pictures when `glossary.idgi` is set, and otherwise the built-in bubble for the current state.

- *Why:* the fallback rule lives in one place, and the Vue side only ever picks between pictures and bubbles.

### 3. The built-in bubbles are HTML and CSS, not image files

A new `IdgiBubble.vue` takes a `state` (`rest`, `hover` or `thanks`) and draws a speech bubble in a box with the same 480 × 324 proportions as the monkey, so the layout doesn't change.

- **Shape:** a rounded rectangle, with its tail drawn by a small inline SVG.
- **Colours:** `--ink` for the outline and text. The fill is `--card` at rest, `--brand-soft` on hover and `--success-soft` after the click, echoing the monkey's white, blue and green bubbles.
- **Text:** in `--font-headings`. The words are interface strings (`idgi-bubble-rest`, `idgi-bubble-hover` and `idgi-bubble-thanks` in `lang/en`), so other editions can translate them. The text wraps on its own, and its size follows the box, using `container-type: inline-size` with a `cqi` font size, so longer translations still fit.
- **Accessibility:** the whole bubble is `aria-hidden`, leaving the button's `aria-label` as its only name.

Built-in bubbles drop the white panel, which exists only because the monkey artwork is drawn on white. Uploaded pictures keep it.

- *Rejected:* drawing new PNGs, which couldn't follow the theme's colours or the site's language. Plain SVG text, which doesn't wrap, so a long translation would overflow.

### 4. Uploads on the Site screen

`SiteRequest` accepts `idgi_rest`, `idgi_hover` and `idgi_thanks` files with the shared `UploadedLogos::RULES`, plus a `remove_idgi_*` flag for each. An after-validation check refuses a hover or thanks picture unless the site will have a resting picture after the save: one already set and not being removed, or one uploaded in the same request. Its messages are interface strings in `lang/en`.

`SiteController::update` then applies each slot as the logo is applied:

- A new upload replaces the old file.
- Removing a picture deletes its file.
- Removing the resting picture also removes the hover and thanks pictures.

Only files under `uploads/site/` are ever deleted.

The Site screen gets an "I don't get it" pictures section: three slots side by side on wide screens and stacked on phones. Each slot has its label, a preview of what the button shows now (the uploaded picture, or `IdgiBubble` for that state), a file input, and a remove checkbox when a picture is set. The hints recommend 480 × 324 pixels on a white or clear background. They also say the hover and thanks pictures are optional, and that removing the resting picture removes the other two.

### 5. Keeping licensed and uploaded files out of the repository

- `public/assets/img/IDGI/` is deleted from the code.
- `.gitignore` gains `/public/uploads`, so files uploaded on any site never show up as changes to commit.
- A feature test checks that `public/assets/img/IDGI` no longer exists and that a term page doesn't refer to it.

## Risks / Trade-offs

- **[The monkey and the committed uploads are still in Git history and in any existing clones]** → Deleting the files stops them shipping from now on. Clearing history (for example with `git filter-repo`) and force-pushing is a decision for the maintainer and the licence holder, listed in the workflow follow-up. Copies already cloned elsewhere can't be recalled.
- **[Minervation's sites show the bubbles until the monkey is uploaded]** → The migration plan uploads it straight after deploying; it takes a minute per site.
- **[Ignoring `public/uploads` hides the committed content images from a fresh clone]** → They belong to the AIM site's content, not the code. A fresh install has no terms that use them, and an imported site brings its own uploads folder.
- **[An uploaded picture with very different proportions]** → It is scaled to the panel's width, keeping its own proportions. The hint recommends 480 × 324.

## Migration Plan

1. Deploy the code and run `php artisan migrate`, which adds the three columns and changes no data.
2. On each Minervation site that should keep the monkey, upload `IDGI-off.png` as the resting picture, `IDGI-click.png` for hover and `IDGI-thanks.png` for thanks, from the legacy copies, on the Site screen. This install is done during the browser check.
3. Optionally, untrack the committed uploads (`git rm -r --cached public/uploads`) and clear the monkey and those uploads from Git history.

To roll back, run `php artisan migrate:rollback` and redeploy the previous code. The previous code expects the monkey files in `public/assets/img/IDGI/`, so copy them back from the legacy folder.

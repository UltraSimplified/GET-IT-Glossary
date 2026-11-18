# Proposal

## Why

The monkey pictures behind the "I don't get it" button are licensed to Minervation for its own sites, but they are committed to the public GitHub repository, so every copy of the glossary ships them. Other organisations installing the glossary have no right to use them, and Minervation's own sites have no way to change them.

## What Changes

- The "I don't get it" button gets built-in pictures made for this project: simple speech bubbles reading "I don't get it" at rest, "Click & tell us" on hover or keyboard focus, and "Thanks!" after the click. They are drawn in the active theme's colours and fonts, and their words come from the interface language, so they suit every theme and edition. These are what a fresh install shows.
- Administrators can replace them on the Site screen with their own three pictures: at rest, on hover, and after the click. Only the resting picture is needed. The hover and thanks pictures fall back to it, and neither can be saved without it. Uploads follow the logo rules: PNG, JPEG, GIF or WebP up to 2 MB, and no SVG. Each picture can be removed, and removing the resting picture returns the site to the built-in bubbles.
- The pictures are always decorative. The button keeps its spoken label, "I don't get it", whatever it shows.
- **BREAKING (for Minervation's sites):** the monkey pictures are removed from the repository. Sites that should keep the monkey upload it once on the Site screen; the original files remain in the legacy copies outside the repository.
- Uploaded files (`public/uploads`) are ignored by Git from now on. Otherwise an uploaded monkey, logo, picture or pronunciation recording could be committed back into the public repository. Some uploads are committed already: five images used in term content and three pronunciation recordings. Untracking those, and clearing them from history if need be, is a step for the maintainer.

The change touches both the **public glossary** (the "Still don't get it?" box on term pages) and the **admin screens** (the Site screen). It does not change any public URL, the `/v1` API, the widget or the import command.

It **adds a migration**: three new nullable columns on the site record for the uploaded pictures. No existing data changes. Until the monkey is uploaded, each site shows the built-in bubbles.

## Capabilities

### New Capabilities

These two capabilities are introduced by the `site-themes` and `idgi-feedback` changes, which are complete but not yet archived, so there are no main specs to modify. This change adds requirements to both.

- `definition-feedback`: what the "I don't get it" button shows (the built-in bubbles or the site's own pictures) and how it stays accessible.
- `site-identity`: administrators setting, replacing and removing the site's "I don't get it" pictures on the Site screen.

### Modified Capabilities

None.

## Impact

- **Public glossary:** `IdgiPrompt.vue` shows the site's pictures when they are set, and otherwise a new themed `IdgiBubble.vue` speech bubble. `HandleInertiaRequests` shares the picture addresses with every page under `glossary.idgi`.
- **Admin screens:** the Site screen (`admin/site/Edit.vue`, `Admin\SiteController`, `SiteRequest`) gains an "I don't get it" pictures section, with an upload, a preview and a remove option for each of the three pictures. Its text goes in `lang/en/interface.php`, as do the bubbles' words.
- **Back end:** three nullable columns on `sites`. Uploads are stored on the `uploads` disk under `site/`, through the existing `UploadedLogos` helper.
- **Repository:** `public/assets/img/IDGI/` is deleted, and `.gitignore` gains `/public/uploads`. Untracking the uploads already committed, and removing the monkey and those uploads from Git history, are separate steps for the maintainer.
- **Out of scope:** pictures chosen per theme, animated pictures, and changing what the button does.

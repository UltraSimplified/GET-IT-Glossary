# Proposal

## Why

Five glossaries (GET-IT, AIM, Finnish, Norwegian and Generation R) run near-identical copies of one old codebase, and they differ almost only in how they look and what they are called. The rebuild currently has AIM's look and name built in, so it can serve AIM but none of the others. Letting one install from GitHub choose a theme and its own name, rather than forking the code, is the first step towards running every edition from this repository, and is useful on its own before any work on sharing content between sites.

## What Changes

- Add a **site** record that holds what makes an install itself: its name, tagline, logo, language and an optional font stylesheet (for licensed fonts such as the Sirba kit). A single install has exactly one site.
- Add **themes** that change only the look, never page structure. A theme holds colours (a light set, an optional dark set, and one or several accent colours), body and heading fonts with fallbacks, base text size, corner radius, a default logo, and four switches: letter shape, home alphabet layout, definition style and letter colours.
- Ship three built-in themes, read-only and defined in code: **classic** (the current GET-IT look, set in Sirba), **AIM** (the look the rebuild has now) and **YPAGS** (the classic layout with Generation R's multi-colour palette).
- Let administrators make a **custom theme** by copying any theme and editing it in a form with a live preview and colour-contrast warnings.
- Add administrator screens for the site's details and for choosing and managing themes.
- Move "AIM Glossary" out of the English language file and the logo out of `.env` into the site record. Page titles use the site's name instead of a name fixed when the front end is built.
- A theme without a dark set always shows in light, and the dark-mode setting is hidden while such a theme is active.
- A fresh install starts with the classic theme. This install is switched to AIM through a new `GLOSSARY_THEME` setting read when the site record is first created.

The change touches both the **public glossary** (how every page looks) and the **admin screens** (new site and theme screens). It does **not** change any public URL, the `/v1` API, the widget or the import command; existing links and embeds keep working. Page content and data are untouched.

It **adds a migration**: two new tables for sites and custom themes, plus one site row created from the install's existing settings. No existing data changes and nothing needs importing again.

## Capabilities

### New Capabilities

- `site-identity`: what an install is called and how it presents itself (name, tagline, logo, language, font stylesheet), and how administrators change it.
- `themes`: how the glossary looks, covering built-in and custom themes, choosing a theme, the switches a theme controls, fonts, dark mode, and editing a custom theme safely.

### Modified Capabilities

None. No specs exist yet.

## Impact

- **Front end:** the colour and font tokens in `resources/css/app.css` become runtime values a theme sets. The hexagon motif (`hex` class) in the A-Z navigation, home page, top ten, comments and version comparison becomes the theme's letter shape. The honeycomb becomes one of the home alphabet layouts. The term page's definition gains a second style. New Vue pages cover the site settings and the theme editor.
- **Fonts:** fonts are no longer fixed at build time. The build bundles a small curated set of open fonts, and each page loads only the fonts its theme uses. A site's font stylesheet (such as the Adobe Fonts kit for Sirba) is linked from the page head. The kit's allowed domains need any new addresses added; that is outside the code.
- **Back end:** new `Site` and `Theme` models, a service that resolves the active site and theme (cached), shared Inertia props for both, and admin routes and controllers. The `glossary.title`, `glossary.tagline` and `glossary.logo` props come from the site.
- **Configuration:** `config/glossary.php` gains the built-in theme definitions and the `GLOSSARY_THEME` setting. `APP_NAME` and `GLOSSARY_LOGO` only seed the first site row. `.env.example` loses the AIM-specific `APP_NAME`.
- **Out of scope:** sharing terms between sites, serving several sites from one install (multi-tenancy), translating the interface into Finnish, and email wording.

# Design

## Context

See `proposal.md` for why, and `specs/site-identity` and `specs/themes` for the required behaviour.

Today the look is compiled in. `resources/css/app.css` holds the AIM colours as CSS variables on `:root` and `.dark`, and Tailwind utilities such as `bg-primary` and `text-ink` read those variables. Fonts are compiled in too: `--font-sans` is a literal family list, and the Vite fonts plugin downloads Atkinson Hyperlegible at build time and serves it from `/build`, with `@fonts` in `app.blade.php` writing the preloads.

The hexagon is a `hex` utility class (a `clip-path`), used in `AlphabetNav` (strip and honeycomb), `TopTen` rank badges, `CommentList` initials and the version picker in `Compare`. The honeycomb is a second branch of `AlphabetNav`.

The name comes from `__('interface.getit-glossary')` ("AIM Glossary" in `lang/en`), the tagline from `interface.plain-language`, the logo from `config('glossary.logo')`, and the tab title suffix from `VITE_APP_NAME`, which is fixed at build time.

Dark mode comes from the starter kit: an `appearance` cookie and `localStorage` value (light, dark or system), a `dark` class on `<html>` set server-side and by an inline script, and a settings page.

Inertia renders on the client (no SSR), so page content appears once Vue mounts. Only what the server writes into `<head>` and onto `<html>` is in place before that.

## Goals / Non-Goals

**Goals:**

- One source of truth for turning theme settings into CSS, used by both the page head and the theme editor's preview.
- No change to page components' structure beyond the four switches; every Tailwind utility keeps its name.
- Built-in themes update with the repository; custom themes are data that survive updates.
- The model can be extended to several sites later (multi-tenancy) without rework.

**Non-Goals:**

- Per-visitor theme choice. The theme belongs to the site; visitors keep only the light/dark preference.
- Importing or exporting themes between installs.
- Self-hosting licensed fonts. Licensed fonts arrive only through the site's font stylesheet.
- Thumbnails of themes on the themes screen.

## Decisions

### 1. A `sites` table with one row, not settings in `.env`

The `sites` table has typed columns: name, tagline, locale, logo path, font stylesheet URL, a `font_families` list read from that stylesheet, and `theme_key`. A migration creates it and inserts the one row from the install's existing settings (`APP_NAME`, `APP_LOCALE`, `GLOSSARY_LOGO`, and a new `GLOSSARY_THEME` defaulting to `classic`), so this install keeps "AIM Glossary" and can be set to AIM before migrating.

- *Why:* administrators must change these without server access, and multi-tenancy later only adds a `domain` column and more rows.
- *Rejected:* `.env` and config only, because they need server access and a deploy for every rename. A generic key-value settings table, because it loses types and validation, and the Site is a real entity.

### 2. Built-in themes as JSON files in the repository; custom themes in a `themes` table

The built-ins live in `resources/themes/{classic,aim,ypags}.json`. Custom themes are rows with `key` (unique slug), `name`, `based_on` and a `settings` JSON column of the same shape as a built-in. A `ThemeRegistry` lists both, with the built-in keys reserved.

- *Why:* fixes to a built-in reach every install through the repository, and one settings shape means one validator and one renderer.
- *Rejected:* built-ins as seeded database rows, which go stale on installs that never re-seed. PHP classes per theme, which means more code for what is data.

### 3. Theme settings shape

```
version            1
colours.light      background, surface, text, muted_text, border, link,
                   primary, primary_text, accent, highlight, highlight_text
colours.dark       same roles, or null (no dark set)
accents            1-6 hex colours (YPAGS: pink, sky, lime, orange, yellow)
fonts.body         { family, source: bundled|site, fallback: <bundled family> }
fonts.heading      same
base_size          15-20 (px)
radius             0-16 (px)
logo               default logo path
letter_shape       plain | square | hexagon | circle
home_alphabet      row | grid | honeycomb
definition_style   plain | italic-dash
letter_colours     single | cycle
```

Authors set eleven colour roles. Every other token is derived from them in CSS with `color-mix()`: the starter kit's `--card`, `--muted`, `--secondary`, `--accent`, `--input`, `--ring`, and our `--brand-soft`. Status colours (warning, success, danger) stay fixed, so a brand palette can't make an error look like a success. A `version` field lets settings be normalised on read when the shape grows.

- *Rejected:* exposing all ~30 current tokens, which is too much for a form and easy to make inconsistent.

### 4. Settings become CSS variables written by the server

`app.css` stops holding literal values. Its tokens point at `--theme-*` variables, for example `--primary: var(--theme-primary)` and `--font-sans: var(--theme-font-body)`, with classic's values as a fallback when no theme style is present.

A `ThemeStyles` class turns a theme's settings into a variable map (light, dark, and the switch variables). `app.blade.php` writes it into a `<style>` in `<head>`, so colours and fonts are right on first paint.

The switches that are purely visual become variables too: the letter shape sets `--letter-clip`, `--letter-radius`, `--letter-ratio` (height to width, so hexagons are taller than squares), and `--letter-current-fill`, `--letter-current-outline`, `--letter-current-text` and `--letter-current-decoration` for the current letter (plain letters underline it). A new `letter-token` component class reads those variables and replaces `hex`, with `letter-token-current` for the current letter. Variables cascade to the nearest ancestor, so the editor's preview can override them on a wrapper without fighting the page's own theme. Attribute selectors such as `[data-letter-shape]` would match both the page and the preview.

The tokens are declared on `:root, [data-theme-scope]`. A token such as `--primary: var(--theme-primary)` is resolved where it is declared, so the preview's wrapper carries `data-theme-scope` to have the tokens worked out again from its own `--theme-*` values.

The text size is a share of the visitor's own browser setting (`--theme-base-size`, such as `112.5%` for 18px), never a fixed pixel size.

Inertia visits never reload the page head, so the Inertia asset version includes a hash of the site and theme settings. After an administrator changes either, the next visit by anyone loads the whole page afresh, with the new head.

Switches that change markup reach Vue as a shared `theme` prop: `home_alphabet` picks the honeycomb, row or grid branch, `definition_style` picks the dash and italic, and `letter_colours` plus `accents` drive the colour of each letter.

### 5. One renderer for the page and the preview

The editor previews by posting the draft settings to `POST /themes/preview`. The response is the same variable map `ThemeStyles` produces, which the preview applies to its wrapper. The request is debounced at about 150 ms and limited to administrators.

- *Why:* a single PHP implementation of the settings-to-CSS mapping, so the preview can't drift from the real site.
- *Rejected:* mirroring the mapping in TypeScript. That gives an instant preview, but there are two implementations to keep in step.

### 6. Fonts: a bundled open set, loaded per theme, plus the site's stylesheet

The Vite fonts plugin builds a small curated set into `/build`: Atkinson Hyperlegible Next (and Mono for code), Source Sans 3 and Source Serif 4. `app.blade.php` calls `@fonts($aliases)` with only the active theme's bundled families. The directive already accepts aliases and leaves the rest out.

A family with `source: site` is written first in the font stack, with its bundled `fallback` after it, so classic is `"sirba-web", "Source Serif 4", serif`. When the site has a font stylesheet, the head links it with a `preconnect`.

A font stylesheet is unlayered and may set fonts itself (the Adobe kit sets the font of `body`). Unlayered rules beat Tailwind's layered utilities, so `app.css` sets the body font in an unlayered rule too, or the kit's font would replace the theme's.

The theme editor can preview any bundled font, so its page loads them all, and it is opened with a full page load (a plain link, and a server-side redirect after copying) so the head includes them.

When the address is saved, the server fetches the stylesheet once and stores the `font-family` names it declares in `sites.font_families`. That is what the form offers as site fonts. A failed fetch keeps the address and warns, rather than blocking the save.

- *Rejected:* letting administrators type any family name, which invites typos that silently fall back. Loading fonts from a third-party CDN at runtime, because bundling keeps the bundled fonts self-hosted, with no outside requests.

### 7. Resolving the site and theme once per request

A `CurrentSite` singleton loads the site row and its theme from the cache (key `site.current`), which is cleared whenever a site or theme is saved or deleted.

A middleware early in the web group sets the app locale from the site. `HandleInertiaRequests` keeps sharing `glossary.title`, `glossary.tagline` and `glossary.logo`, now from the site, so existing components don't change, and adds a `theme` prop (key, switches, accents, `has_dark`).

`app.blade.php` puts the site name on `<html data-site-name>`, and the Inertia `title` callback reads it, replacing `VITE_APP_NAME`.

An unknown `theme_key` resolves to classic and logs a warning.

### 8. Dark mode is gated by the theme

`<html>` gets `data-has-dark`. The server adds the `dark` class only when the theme has a dark set. The inline script and `initializeTheme()` read `data-has-dark` and force light when it is false. The settings navigation hides "Appearance" when `theme.has_dark` is false, and its route redirects to the profile page.

### 9. Admin screens and permissions

- `GET/PUT /site` edits the site's details, and `PUT /site/theme` chooses the site's theme.
- `/themes` lists, copies (`POST`), edits and deletes themes. Keys come from the theme's name; the built-in keys, `preview` and `create` are reserved.
- `POST /themes/preview` serves the editor's preview.
- The editor's page prop is `editing`, not `theme`, because a page prop of the same name would replace the shared `theme` prop the site layout draws with.

All of them sit behind the existing `admin` gate, and `AdminNav` gains "Site" and "Themes" tabs. Logos go to the existing `uploads` disk under `site/` and `themes/`. Uploads are limited to PNG, JPEG, GIF and WebP up to 2 MB; SVG is refused because it can carry script.

Theme settings are validated by one rule set shared by update and preview (a copy takes a theme that is already valid), including the cross-field rules: honeycomb needs hexagon or circle letters, and cycling needs at least two accents. Contrast is checked in the form only, as a warning. It covers text, quiet text and links on the background and on panels, links on the pale accent tint, button text, and highlighted text, in the light and the dark set.

## Risks / Trade-offs

- **[A domain is missing from the Adobe Fonts kit, so Sirba never loads]** → The stack falls back to Source Serif 4 and the page still reads well. The README notes that new domains must be added to the kit.
- **[A site stylesheet makes a third-party request on every page]** → Only when an administrator sets one, as the old sites already do. The site screen says so next to the field.
- **[`color-mix()` is unsupported in old browsers]** → It is supported by all current browsers. The classic literal fallbacks in `app.css` keep text readable where it isn't.
- **[Classic won't be pixel-identical to the old Bootstrap site]** → The aim is a recognisable look, not a copy. It gets checked side by side with the live site before the change is done.
- **[A preview request per keystroke]** → Debounced, administrator-only and cheap: no database writes.
- **[Custom themes saved under an older settings shape]** → The `version` field, plus normalising on read with defaults for missing keys.
- **[Tests asserting "AIM Glossary"]** → Tests set the site row explicitly instead of relying on `.env`, and `phpunit.xml` pins `GLOSSARY_THEME=classic` so a local `.env` can't change the first site row.

## Migration Plan

1. Before migrating this install, set `GLOSSARY_THEME=aim` in `.env` (`APP_NAME` is already "AIM Glossary"). Alternatively, choose AIM on the themes screen afterwards.
2. Run `php artisan migrate`. It adds the `sites` and `themes` tables and one site row, and changes no existing table or row.
3. Add the new local and production domains to the Adobe Fonts kit, in Adobe's interface.

To roll back, run `php artisan migrate:rollback` (dropping the two tables) and redeploy the previous code. No other data is affected.

## Open Questions

- **Classic's open fallback serif:** settled on Source Serif 4. Checked by eye against Sirba on the term and home pages: the proportions and weight are close enough that the page keeps its look when the kit is missing. The kit declares the family as `sirba-web`, which is what `classic.json` names.
- **Exact YPAGS colours:** settled. The YPAGS stylesheet uses pink `#da3c8d` for links, headings and letter outlines, Source Sans Pro, and a 20px base size. Pink that bright fails WCAG AA as text, so links, headings and buttons use a darker `#b33174`, which also passes on the pale pink tints mixed from the accent. `#da3c8d` stays as the accent for underlines, markers and focus rings. The letter colours are the hand logo's pink (as `#c93782`, so white text on it passes), sky `#6cc7ee`, lime `#bacf2d`, orange `#f9b53d` and yellow `#f9e843`. The body font is Source Sans 3 and the base size 18px.
- **Cycling letter colours and contrast:** most of the YPAGS colours are too pale for text on white. Letters that take the accents in turn are therefore filled tiles, each with white or near-black text, whichever reads better on that colour. The current letter keeps the shape's own marking.

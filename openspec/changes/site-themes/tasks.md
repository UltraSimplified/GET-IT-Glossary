# Tasks

## 1. Site record and theme registry

- [x] 1.1 Add a migration creating the `sites` and `themes` tables and inserting the single site row from `APP_NAME`, `APP_LOCALE`, `GLOSSARY_LOGO` and a new `GLOSSARY_THEME` (default `classic`), leaving an existing row untouched; add `Site` and `Theme` models and factories. Verify with feature tests for the env-seeded row, the defaults when the settings are missing, and a second run not overwriting the row.
- [x] 1.2 Add `config/glossary.php` entries for `theme` (`GLOSSARY_THEME`) and the bundled font list, and remove the AIM logo default. Set `GLOSSARY_THEME=aim` in this install's local `.env`, run `php artisan migrate`, and verify the site row reads "AIM Glossary" with theme `aim` and that no other table changed.
- [x] 1.3 Add the theme settings shape (version, colours, accents, fonts, size, radius, logo, switches), with normalising on read and one validation rule set covering hex colours, ranges, fonts, honeycomb-needs-hexagon-or-circle and cycle-needs-two-accents. Verify with unit tests for each rule and for normalising a settings array with missing keys.
- [x] 1.4 Add `resources/themes/{classic,aim,ypags}.json`, with AIM's values taken from the current `app.css`, and a `ThemeRegistry` listing built-ins and custom themes with the built-in keys reserved. Verify with tests that the three built-ins load and pass validation, and that an unknown key resolves to classic with a logged warning.
- [x] 1.5 Add the cached `CurrentSite` service (cleared when a site or theme is saved or deleted) and a middleware that sets the app locale from the site. Verify with tests that the locale follows the site, falling back to English for missing strings, and that the cache clears on save.
- [x] 1.6 Share `glossary.title`, `glossary.tagline` and `glossary.logo` from the site (logo falling back to the theme's default) and a new `theme` prop. Replace remaining uses of `interface.getit-glossary` for the site name, set its English value back to "GET-IT Glossary", and replace the AIM `APP_NAME` in `.env.example`. Update `PublicGlossaryTest` to set the site explicitly, and verify the shared props in feature tests.

## 2. Rendering the active theme

- [x] 2.1 Add `ThemeStyles`, turning theme settings into the CSS variable map for light, dark and the letter-shape variables. Verify with unit tests that the AIM map reproduces the current `app.css` values and that classic produces no dark map.
- [x] 2.2 Rework `resources/css/app.css` so tokens read `--theme-*` variables, with classic fallbacks: derive the starter kit's tokens and `--brand-soft` with `color-mix()`, point `--font-sans` and `--font-heading` at theme variables, and replace the `hex` utility with a `letter-token` class driven by `--letter-*` variables. Verify with `npm run build`, and that the AIM home, term and admin pages look the same as before in side-by-side screenshots.
- [x] 2.3 Add Source Sans 3 and Source Serif 4 to the Vite fonts configuration, and render `@fonts` with only the active theme's bundled families. Verify that the build's font manifest lists the new families and that an AIM page loads only Atkinson Hyperlegible.
- [x] 2.4 Update `app.blade.php`: write the theme variables into a head `<style>`, link the site's font stylesheet with a `preconnect`, set `data-site-name` and `data-has-dark` on `<html>`, and add the `dark` class only when the theme has a dark set. Make the inline script, `initializeTheme()` and the Inertia `title` callback read those attributes. Verify with feature tests on the rendered head, and in the browser that the tab title ends with the site name.

## 3. Switches in the pages

- [x] 3.1 Update `AlphabetNav` and `Home` to use `letter-token`, choose the row, grid or honeycomb layout from the `theme` prop, and cycle letter colours through the accents when set. Verify in the browser under each switch value, at desktop and phone widths, with no sideways scrolling.
- [x] 3.2 Switch the `TopTen` rank badges, `CommentList` initials and the `Compare` version picker from `hex` to `letter-token`. Verify in the browser under the hexagon and square outline shapes.
- [x] 3.3 Add the italic-after-a-dash definition style to the term page from the `theme` prop. Verify in the browser under both definition styles, and that screen readers get the definition text without the dash.
- [x] 3.4 Hide "Appearance" in the settings navigation when the theme has no dark set, and redirect its route to the profile page. Verify with a feature test for the redirect, and in the browser that a dark-preferring device shows classic in light.

## 4. Built-in themes

- [x] 4.1 Finish `classic.json`: colours measured from the live GET-IT site, Sirba from the site stylesheet with Source Serif 4 as fallback, square outline letters, inline row, italic definitions, no dark set. Copy the book icon from the legacy GET-IT public folder as its default logo. Verify side by side with the live GET-IT home and term pages, with and without the font stylesheet set, and record the chosen fallback serif in the design.
- [x] 4.2 Finish `ypags.json` from the YPAGS stylesheet and logo (accent palette, letter colours cycling, classic switches), and copy the pink hand logo from the legacy YPAGS public folder. Verify the home page and a term page in the browser, and that every text and background pair passes WCAG AA.
- [x] 4.3 Confirm `aim.json` reproduces the pre-change look in light and dark, and verify that every built-in passes the settings validation test from 1.4.

## 5. Site details screen

- [x] 5.1 Add `GET/PUT /site` for administrators. Validate a required name, tagline, a language limited to the `lang/` folders present, an https-only font stylesheet address, and PNG, JPEG, GIF or WebP logos up to 2 MB. Store logos on the `uploads` disk under `site/`, allow removing the logo, and on save read the stylesheet's `font-family` names into `font_families`, warning (not failing) when the fetch fails. Verify with feature tests: admin saves, editor refused, http address refused, SVG refused, families parsed through `Http::fake`, and the fetch failure warns.
- [x] 5.2 Add the `admin/site/Edit` page and a "Site" tab in `AdminNav`, with the third-party request note beside the font stylesheet field and its text in `lang/en/interface.php`. Verify in the browser that the name and tagline change on the home page after saving, at desktop and phone widths.
- [x] 5.3 Document the site settings, `GLOSSARY_THEME`, and adding new domains to an Adobe Fonts kit in the README. Verify with `npx vp check`.

## 6. Themes screens

- [x] 6.1 Add the theme routes for administrators: index (built-ins and custom, with the active one marked), copy, edit and update (custom only), delete (refusing built-ins and the active theme) and choosing the site's theme, all validated by the rule set from 1.3. Verify with feature tests: copy a built-in, refuse editing a built-in, refuse deleting the active theme, editor refused, and a switch showing on the next page.
- [x] 6.2 Add `POST /themes/preview`, returning the `ThemeStyles` map for draft settings. Verify with feature tests for an administrator, an editor (refused) and invalid settings.
- [x] 6.3 Add the `admin/themes/Index` page (swatches, active marker, choose, copy, delete) and a "Themes" tab in `AdminNav`, with its text in `lang/en/interface.php`. Verify in the browser at desktop and phone widths.
- [x] 6.4 Add the `admin/themes/Edit` form: light colours and optional dark colours, accents, fonts (bundled families and the site's families), size, radius, default logo upload (`uploads/themes/`) and the four switches, with honeycomb and cycling disabled when their rules aren't met. Add a WCAG contrast check that warns with the pair and its ratio, and the leave-without-saving guard, with text in `lang/en/interface.php`. Verify in the browser that low-contrast links warn but still save, and that validation errors show beside their fields.
- [x] 6.5 Add the live preview to the edit form: header, alphabet, a term and a list of terms, with the debounced preview response applied to its wrapper. Verify in the browser that colour and switch changes show in the preview within a moment and that the live site is unchanged until saving.
- [x] 6.6 Document making, editing and choosing themes in the README. Verify with `npx vp check`.

## 7. Integration

- [x] 7.1 Check every built-in theme in the browser, on the home, listing, term, search and an admin page, at desktop and phone widths: AIM in light and dark, classic and YPAGS in light only on a dark-preferring device. Confirm the `/v1` API, the widget and term addresses respond as before.
- [x] 7.2 Run `composer ci:check` and verify that it passes.

## Workflow follow-up

- The maintainer reviews and commits the change. Agents do not commit or push.
- The maintainer adds the local and production addresses to the Adobe Fonts kit's allowed domains in Adobe's interface.
- Archive the change with `/opsx:archive` after review.

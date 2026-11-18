# GET-IT-Glossary

A plain-language glossary of terms commonly used in health research.

Readers browse the glossary A to Z, search it, and follow links from one definition to the next. Signed-in editors discuss definitions, revise them, and compare or bring back earlier versions. Other websites can show the glossary through a public API, an embeddable widget and a WordPress plugin.

This is a rebuild of the original Laravel 5 site on Laravel 13, with Inertia and Vue handling page changes in the browser. Addresses, the API and the widget work exactly as before, so existing links and embeds keep working.

## Contents

- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [The site and its look](#the-site-and-its-look)
- ["I don't get it"](#i-dont-get-it)
- [Importing the live glossary](#importing-the-live-glossary)
- [Development](#development)
- [Where things are](#where-things-are)
- [Licence](#licence)

## Tech stack

| Layer        | Tools                                                                        |
| ------------ | ---------------------------------------------------------------------------- |
| Back end     | Laravel 13, PHP 8.5, Fortify (sign-in, password reset, two-factor, passkeys) |
| Front end    | Inertia 3, Vue 3, TypeScript, Tailwind CSS 4, Tiptap (rich text editing)     |
| Build        | Vite (via Vite+), Wayfinder (typed routes)                                   |
| Database     | SQLite locally, MySQL or MariaDB in production                               |
| Code quality | Pest, Pint, PHPStan (Larastan), Sloppy, Vite+ lint and format, vue-tsc       |

## Requirements

- **PHP 8.3 or later** (CI runs 8.5), with the `intl`, `pdo_sqlite` (or `pdo_mysql`) and `zip` extensions
- **Composer 2**
- **Node.js 24** (the version CI uses) and npm

## Installation

```bash
git clone https://github.com/UltraSimplified/GET-IT-Glossary.git
cd GET-IT-Glossary
composer setup
```

`composer setup` installs the PHP and JavaScript packages, creates `.env` from `.env.example`, generates the app key, migrates the database and builds the front end.

For sample content to work with, seed the database:

```bash
php artisan db:seed
```

This creates three accounts, `admin@example.com`, `editor@example.com` and `contributor@example.com`, all with the password `password`. They are for local development only: never seed a production database.

With [Laravel Herd](https://herd.laravel.com) the site is served at `https://get-it-glossary.test`. Otherwise run `composer dev`, which starts the server, the queue worker, the log viewer and Vite together.

## Configuration

Beyond Laravel's usual settings, `.env` holds the glossary's own:

| Variable                 | What it does                                                            |
| ------------------------ | ----------------------------------------------------------------------- |
| `GLOSSARY_INSTANCE`      | Edition name quoted in outgoing emails, such as `English`               |
| `EMAIL_ADDRESS`          | Where "Suggest a term" and "Suggest a translation" messages go          |
| `EMAIL_FEEDBACK_ADDRESS` | Where "Send us your feedback" messages go (defaults to `EMAIL_ADDRESS`) |
| `EMAIL_FROM`             | Name those messages are addressed to                                    |

Four more are read only once, when `php artisan migrate` first creates the site record. After that, change these on the Site and Themes screens (see [The site and its look](#the-site-and-its-look)):

| Variable         | What it sets in the first site record                                         |
| ---------------- | ----------------------------------------------------------------------------- |
| `APP_NAME`       | The site's name (default `GET-IT Glossary`)                                   |
| `APP_LOCALE`     | The interface language. `en` and `no` are included, in `lang/`                |
| `GLOSSARY_THEME` | The theme: `classic` (the default), `aim` or `ypags`                          |
| `GLOSSARY_LOGO`  | Path of the site's own logo in `public/`. Without one the theme's logo shows. |

To install an edition with its own look from the start, set these before migrating. For example, the AIM glossary uses `APP_NAME="AIM Glossary"` and `GLOSSARY_THEME=aim`.

The tags editors can choose from are listed in `config/glossary.php`.

## The site and its look

Everything that makes an install itself is in the database and changed by administrators, with no deploy or server access needed.

**Site details** (`/site`, the Site tab of the management screens) holds the site's name, tagline, interface language, logo and an optional font stylesheet address. The name appears in the header, on the home page and in the browser tab. Logos can be PNG, JPEG, GIF or WebP, up to 2 MB; SVG is refused because an SVG file can carry script. A site with no logo of its own shows its theme's logo. Interface text that the chosen language has no translation for shows in English.

### Themes

A theme changes only how the glossary looks, never what the pages say. It sets eleven colours (every other shade is mixed from them), an optional set of dark colours, one to six accent colours, the body and heading fonts, the text size, how rounded corners are, a default logo, and four switches:

| Switch                    | Choices                                                                 |
| ------------------------- | ----------------------------------------------------------------------- |
| Letter shape              | Plain, square outline, hexagon or circle, for the alphabet and badges   |
| Alphabet on the home page | One row, a grid, or a honeycomb (which needs hexagon or circle letters) |
| Definitions on term pages | Large and plain, or in italic after a dash                              |
| Letter colours            | All one colour, or the accent colours in turn (which needs two or more) |

Three themes are built in and kept in `resources/themes` as JSON, so fixes to them arrive with the code:

| Theme   | Look                                                                                                     |
| ------- | -------------------------------------------------------------------------------------------------------- |
| Classic | The original GET-IT site: dark grey on white, dark blue links, Sirba, square letters, italic definitions |
| AIM     | Navy, teal and yellow, Atkinson Hyperlegible, hexagon letters and the honeycomb, light and dark          |
| YPAGS   | The classic layout in the Generation R colours, Source Sans, letters in the hand logo's colours          |

**Choosing a theme:** on the **Themes** screen (`/themes`), press **Use this theme**. Every visitor sees it on the next page they open; nothing needs rebuilding. A theme without dark colours always shows in light, and the appearance setting is hidden while it is in use.

**Making your own:** built-in themes can't be changed, so press **Copy** on any theme, built in or your own, and name the copy. The theme editor opens with a live preview of the header, the alphabet, a term and a list of terms, which follows every change before anything is saved. The editor warns when a text colour and its background fall below the WCAG AA contrast ratio of 4.5 to 1; the warning is advice, and the theme can still be saved. Custom themes are stored in the database, so they survive code updates.

A custom theme can be deleted unless the site is using it; choose another theme first. Fonts come from the open fonts the build includes (Atkinson Hyperlegible, Source Sans 3 and Source Serif 4, listed in `config/glossary.php` and `vite.config.ts`) or from the site's font stylesheet. Each page loads only the fonts its theme uses.

### Licensed fonts and Adobe Fonts

Themes can use licensed fonts that the site loads from a font service, such as the Sirba kit in Adobe Fonts that the classic theme is drawn in. Put the kit's stylesheet address (for example `https://use.typekit.net/<kit id>.css`) in **Font stylesheet address** on the Site screen. When it is saved, the glossary reads the font families the stylesheet declares and offers them in the theme editor. If the stylesheet can't be read, the address is still saved and the screen says so.

Every page then loads that stylesheet from the service's own servers, which is a request to a third party on each visit. Leave the address empty if the theme uses only the bundled fonts.

Adobe Fonts only serves a kit to the domains listed in it. Whenever the glossary runs at a new address (a new production domain, a staging site or a local `.test` address), add that domain to the kit's web project in Adobe Fonts, in its list of domains (under **Web Projects**). Until then, pages fall back to the theme's open font; for classic that is Source Serif 4.

## "I don't get it"

Every term page ends with "Still don't get it?" and the "I don't get it" button. Pressing it adds one to the version's "I don't get it" count, and editors see that count as a share of the term's views: on the term list, the edit screen and the version comparison. Each new version starts its count again from zero.

After pressing the button, readers can say in a few words what didn't make sense. That's optional, and a reply is anonymous: only the reader's words, the version they were reading and the date are kept, with no name, email or IP address, and the form asks them not to include personal details. A reply is only accepted straight after a click on that version, once per click, and at most six a minute from one visitor. A hidden field catches automated spam.

Replies appear on the term's edit screen, below the "I don't get it" score, for every version of the term. Replies about an earlier version are marked as such. Anyone who can edit the term can mark a reply dealt with, which keeps it as a record under "Dealt with", or mark it new again. Administrators can also delete a reply, for spam. Replies never appear on public pages, in the API or in the widget.

### The button's pictures

Out of the box the button shows speech bubbles: "I don't get it" at rest, "Click & tell us" on hover or keyboard focus, and "Thanks!" after the click. They are drawn in the theme's colours and heading font, and their words are interface text, so each language edition can translate them.

Administrators can replace them on the Site screen, under **"I don't get it" pictures**, with three pictures of their own: at rest, on hover and after the click. Only the picture at rest is needed; the other two fall back to it. Pictures look best at 480 × 324 pixels on a white or clear background, as PNG, JPEG, GIF or WebP up to 2 MB (SVG is refused). Removing the picture at rest removes all three and brings the bubbles back.

The monkey pictures the original GET-IT sites used are licensed to Minervation for its own sites, so they are not part of this repository. A site licensed to use them uploads them once on the Site screen: `IDGI-off.png` at rest, `IDGI-click.png` on hover and `IDGI-thanks.png` after the click.

## Importing the live glossary

`glossary:import` loads a database export of the original site, plain or gzipped, into whichever database `.env` points at. It keeps every record's id, so term versions, comments, editor assignments and "updated by" names stay connected.

```bash
php artisan glossary:import path/to/export.sql.gz
```

It replaces all users, terms, pages and comments, and asks before doing so (`--force` skips the question). Readers' "I don't get it" replies belong to the terms they were sent about, so they are removed too. The search log can run to millions of rows, so it is left out unless you add `--searches`.

Export from phpMyAdmin with **Include column names in every INSERT statement** ticked, or use `mysqldump --complete-insert`.

## Development

| Command               | What it does                                                                                  |
| --------------------- | --------------------------------------------------------------------------------------------- |
| `composer dev`        | Runs the site with live reloading                                                             |
| `composer lint`       | Formats the PHP code with Pint                                                                |
| `composer test:lint`  | Checks the PHP formatting without changing anything                                           |
| `composer analyse`    | Runs PHPStan (Larastan) at level 7, then the Sloppy code-smell analyser                       |
| `composer test`       | Checks formatting, then runs the test suite                                                   |
| `composer ci:check`   | Runs everything the GitHub workflow runs                                                      |
| `npm run check`       | Lints and checks the formatting of the TypeScript and Vue code (`npm run check:fix` fixes it) |
| `npm run types:check` | Type-checks the front end with vue-tsc                                                        |

The [Test workflow](.github/workflows/tests.yml) runs the formatting checks, front-end lint and type check, static analysis and the tests on every push to `main` and on every pull request.

### Static analysis baselines

PHPStan runs with no baseline: fix what it reports rather than silencing it. Sloppy compares against [`.sloppy-baseline.json`](.sloppy-baseline.json), which holds findings that are deliberate:

- the import writer's loops run over a fixed list of tables, so there is nothing to batch
- `sessions.user_id` and `terms_editors.user_id` have no database constraint, because Laravel keeps sessions for deleted users and the original site's data can name deleted users

After Sloppy flags something new, fix it. Only refresh the baseline (`vendor/bin/sloppy baseline --force`) for a finding that is deliberate, and say why in the commit.

### Routes in TypeScript

Wayfinder writes typed route helpers to `resources/js/routes` and `resources/js/actions` whenever Vite runs. They are generated, so they are not committed.

## Where things are

| Path                         | What is there                                                           |
| ---------------------------- | ----------------------------------------------------------------------- |
| `app/Http/Controllers`       | Public glossary pages, comments, contact forms, the API and widget      |
| `app/Http/Controllers/Admin` | Managing terms, their versions, pages, users, the site and its themes   |
| `app/Actions/Terms`          | Saving a term as a new version, rolling back, assigning editors         |
| `app/Services/LegacyImport`  | Reading a database export of the original site                          |
| `app/Support`                | Turning `[term]` references into links, and shaping terms for the pages |
| `app/Themes`                 | Theme settings, the built-in and custom theme registry, theme CSS       |
| `resources/themes`           | The built-in themes (`classic`, `aim`, `ypags`) as JSON                 |
| `resources/js/pages`         | One Vue component per page; `admin/` holds the management screens       |
| `lang/`                      | Interface text in each language                                         |
| `public/uploads`             | Files uploaded on a site; ignored by Git, as they belong to the site    |

## Licence

[MIT](LICENSE)

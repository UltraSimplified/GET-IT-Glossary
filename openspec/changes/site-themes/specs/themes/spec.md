# Spec Delta

## Purpose

Controls how the glossary looks (colours, fonts, shapes and a few layout styles) through themes that a site chooses, so one codebase can carry the look of every glossary edition without changing page structure or content.

## ADDED Requirements

### Requirement: Themes change only the look

A theme SHALL change only colours, fonts, text size, corner radius, the default logo and the four switches defined below. Every theme MUST show the same pages, sections, links and content in the same order.

#### Scenario: Same content under two themes

- **WHEN** the same term page is shown under the classic theme and under the AIM theme
- **THEN** both show the same sections, links and text in the same order

### Requirement: Three built-in themes

The system SHALL provide three built-in themes: classic, AIM and YPAGS. Built-in themes MUST NOT be editable or deletable.

#### Scenario: Built-ins listed

- **WHEN** an administrator opens the themes screen on a fresh install
- **THEN** classic, AIM and YPAGS are listed and marked as built in

#### Scenario: Built-in cannot be edited

- **WHEN** an administrator tries to save changes to, or delete, a built-in theme
- **THEN** the request is refused and the theme is unchanged

### Requirement: The classic theme matches the current GET-IT look

The classic theme SHALL use dark-grey text and dark-blue links on white, the Sirba typeface with an open serif fallback, letters with the current letter in a square outline, the alphabet as one inline row on the home page, definitions in italic after a dash, and the book icon as its default logo. It has no dark set.

#### Scenario: Classic term page

- **WHEN** a visitor opens a term page under the classic theme
- **THEN** the definition appears in italic after a dash, in Sirba when the site provides it

### Requirement: The AIM theme matches the current rebuild

The AIM theme SHALL reproduce the look the rebuild has before this change: its current colours in light and dark, Atkinson Hyperlegible, hexagon letters, the honeycomb alphabet on the home page, large plain definitions and the teal hexagon as its default logo.

#### Scenario: AIM home page

- **WHEN** a visitor opens the home page under the AIM theme
- **THEN** the alphabet appears as a honeycomb of hexagons in the AIM colours

### Requirement: The YPAGS theme uses the Generation R palette

The YPAGS theme SHALL use the classic layout settings with Generation R's accent colours (pink, sky blue, lime, orange and yellow), letters coloured in turn from those accents, and the pink hand as its default logo.

#### Scenario: Letters cycle through the accents

- **WHEN** a visitor views the alphabet under the YPAGS theme
- **THEN** consecutive letters take consecutive accent colours, starting again after the last

### Requirement: A site uses one theme at a time

Every page SHALL be drawn with the site's chosen theme. A fresh install MUST start with the classic theme, unless `GLOSSARY_THEME` names another theme when the site record is first created.

#### Scenario: Fresh install

- **WHEN** a visitor opens any page on a fresh install with no `GLOSSARY_THEME` set
- **THEN** the page is drawn with the classic theme

#### Scenario: Theme set at install

- **WHEN** the site record is first created with `GLOSSARY_THEME=aim`
- **THEN** pages are drawn with the AIM theme

#### Scenario: Unknown theme

- **WHEN** the site's chosen theme no longer exists
- **THEN** pages are drawn with the classic theme

### Requirement: Administrators switch the site's theme

Administrators SHALL be able to choose any built-in or custom theme for the site. The change MUST apply to the next page any visitor opens, without rebuilding the front end. Other users MUST NOT be able to switch themes.

#### Scenario: Switching theme

- **WHEN** an administrator switches the site from classic to AIM
- **THEN** the next page any visitor opens is drawn with the AIM theme

#### Scenario: Editor cannot switch

- **WHEN** a signed-in user who is not an administrator tries to switch the theme
- **THEN** the request is refused and the theme is unchanged

### Requirement: The look is right on first paint

Pages SHALL arrive with the active theme's colours, fonts and switches already applied, so visitors never see another theme's look first.

#### Scenario: No flash of the wrong theme

- **WHEN** a visitor loads any page with the classic theme active
- **THEN** the first rendered frame uses the classic colours and layout, with no AIM colours shown first

### Requirement: Letter shape switch

A theme SHALL set the letter shape used for the alphabet and other letter and number badges to one of: plain, square outline, hexagon or circle. The current letter MUST remain distinguishable from the others in every shape.

#### Scenario: Square outline

- **WHEN** a visitor views the listing for B under a theme whose letter shape is square outline
- **THEN** B is drawn inside a square outline and the other letters are plain

#### Scenario: Hexagon

- **WHEN** a visitor views the listing for B under a theme whose letter shape is hexagon
- **THEN** B is drawn inside a filled hexagon

### Requirement: Home alphabet switch

A theme SHALL set how the home page lays out the alphabet: one inline row, a grid, or a honeycomb. The honeycomb MUST only be available with the hexagon or circle letter shape.

#### Scenario: Inline row

- **WHEN** a visitor opens the home page under a theme whose home alphabet is an inline row
- **THEN** the letters appear in one row that wraps on narrow screens

#### Scenario: Honeycomb refused with square letters

- **WHEN** an administrator saves a custom theme with the honeycomb layout and the square outline letter shape
- **THEN** the change is refused with a message that the honeycomb needs hexagon or circle letters

### Requirement: Definition style switch

A theme SHALL set how a term's definition is shown on its page: large and plain, or italic after a dash.

#### Scenario: Italic after a dash

- **WHEN** a visitor opens a term page under a theme whose definition style is italic after a dash
- **THEN** the definition is shown in italic and introduced by a dash

### Requirement: Letter colours switch

A theme SHALL set whether alphabet letters share one colour or take the theme's accent colours in turn. Cycling MUST only be available when the theme has at least two accents.

#### Scenario: One colour

- **WHEN** a visitor views the alphabet under a theme whose letter colours are one colour
- **THEN** every available letter has the same colour

### Requirement: Fonts load only for the active theme

Pages SHALL load only the fonts the active theme uses. A theme font that comes from the site's font stylesheet MUST fall back to the theme's open fallback font when the stylesheet does not provide it.

#### Scenario: Only the theme's fonts load

- **WHEN** a visitor opens a page under the AIM theme
- **THEN** the page loads Atkinson Hyperlegible and no font that only another theme uses

#### Scenario: Licensed font unavailable

- **WHEN** the classic theme is active on a site with no font stylesheet
- **THEN** text is shown in the classic theme's open serif fallback

### Requirement: Dark mode depends on the theme

When the active theme has a dark set, visitors' light, dark or system preference SHALL apply as before. When it has none, pages MUST always show in light and the dark-mode setting MUST be hidden.

#### Scenario: Theme without dark set

- **WHEN** a visitor whose device prefers dark mode opens a page under the classic theme
- **THEN** the page is shown in the classic light colours

#### Scenario: Setting hidden

- **WHEN** a signed-in user opens their settings while the classic theme is active
- **THEN** the appearance setting is not offered

#### Scenario: Theme with dark set

- **WHEN** a visitor whose device prefers dark mode opens a page under the AIM theme
- **THEN** the page is shown in the AIM dark colours

### Requirement: Administrators make custom themes by copying

Administrators SHALL be able to create a custom theme by copying any built-in or custom theme, give it a name, edit it, and delete it. The theme the site is using MUST NOT be deletable.

#### Scenario: Copy a built-in

- **WHEN** an administrator copies the YPAGS theme and names the copy "Generation R bright"
- **THEN** a custom theme with that name exists, with YPAGS's settings

#### Scenario: Active theme cannot be deleted

- **WHEN** an administrator tries to delete the custom theme the site is using
- **THEN** the request is refused with a message to choose another theme first

### Requirement: The theme form edits every theme setting

The custom theme form SHALL let administrators change every theme setting: colours for light and, optionally, dark; accent colours; body and heading fonts from the offered list; base text size; corner radius; default logo; and the four switches.

#### Scenario: Removing the dark set

- **WHEN** an administrator turns off dark colours for a custom theme and saves
- **THEN** sites using that theme always show in light

#### Scenario: Fonts limited to the offered list

- **WHEN** an administrator chooses a body font
- **THEN** only the bundled open fonts, and font families the site's stylesheet provides, are offered

#### Scenario: Invalid colour refused

- **WHEN** an administrator saves a colour that is not a valid hex colour
- **THEN** the change is refused with a message naming the colour that is wrong

### Requirement: The theme form previews changes live

While editing a custom theme, the form SHALL show a preview of glossary pages (header, alphabet, a term and a list of terms) that updates as settings change, before anything is saved.

#### Scenario: Colour change previewed

- **WHEN** an administrator changes the link colour in the form
- **THEN** links in the preview take the new colour at once, and the live site is unchanged until the theme is saved

### Requirement: The theme form warns about poor contrast

The form SHALL warn when a text colour and its background fall below the WCAG AA contrast ratio (4.5:1 for normal text). Warnings MUST NOT stop the theme being saved.

#### Scenario: Low-contrast links

- **WHEN** an administrator sets a link colour with a contrast ratio below 4.5:1 against the background
- **THEN** the form shows a warning naming the colour pair and its ratio, and saving is still allowed

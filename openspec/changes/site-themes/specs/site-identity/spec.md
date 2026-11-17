# Spec Delta

## Purpose

Holds what makes an install of the glossary itself (its name, tagline, logo, interface language and any licensed font stylesheet) and lets administrators change it without touching code or configuration files.

## ADDED Requirements

### Requirement: Every install has one site record

The system SHALL keep exactly one site record per install, holding the site's name, tagline, logo, interface language, optional font stylesheet address and chosen theme.

#### Scenario: Site record created on first migration

- **WHEN** the database is migrated for the first time on an install with `APP_NAME`, `APP_LOCALE`, `GLOSSARY_LOGO` and `GLOSSARY_THEME` set
- **THEN** one site record exists whose name, language, logo and theme are taken from those settings

#### Scenario: Defaults when settings are missing

- **WHEN** the site record is created on an install with none of those settings
- **THEN** its name is "GET-IT Glossary", its tagline is "Plain language definitions of health research terms", its language is English, it has no logo of its own and its theme is classic

#### Scenario: Existing record is not overwritten

- **WHEN** the migrations run again on an install that already has a site record
- **THEN** the site record keeps the values it has

### Requirement: Pages show the site's name and tagline

Every page SHALL show the site's name wherever the glossary names itself, including the header, the home page heading and the browser tab title, and SHALL show the site's tagline on the home page.

#### Scenario: Name in the page title

- **WHEN** a visitor opens any page on a site named "AIM Glossary"
- **THEN** the browser tab title ends with "AIM Glossary"

#### Scenario: Name and tagline on the home page

- **WHEN** a visitor opens the home page of a site named "Generation R" with the tagline "Understanding health research terms"
- **THEN** the home page heading reads "Generation R" and the tagline under it reads "Understanding health research terms"

#### Scenario: Name changed by an administrator

- **WHEN** an administrator renames the site and a visitor then opens any page
- **THEN** the new name appears without the front end being rebuilt

### Requirement: The logo comes from the site, falling back to the theme

Pages SHALL show the site's own logo when it has one, and otherwise the default logo of the active theme.

#### Scenario: Site has its own logo

- **WHEN** the site has a logo and its theme is classic
- **THEN** pages show the site's logo, not the classic theme's book icon

#### Scenario: Site has no logo

- **WHEN** the site has no logo and its theme is AIM
- **THEN** pages show the AIM theme's default logo

### Requirement: The interface language comes from the site

The interface text SHALL be shown in the site's language, falling back to English for any text that language has no translation for.

#### Scenario: Norwegian site

- **WHEN** the site's language is Norwegian
- **THEN** interface text appears in Norwegian, and any text without a Norwegian translation appears in English

### Requirement: A licensed font stylesheet can be linked

When the site has a font stylesheet address, every page SHALL link that stylesheet so that licensed fonts it provides (such as an Adobe Fonts kit) can be used by the theme. The address MUST use https.

#### Scenario: Kit linked on every page

- **WHEN** the site's font stylesheet address is set
- **THEN** every page links that stylesheet in its head

#### Scenario: No kit set

- **WHEN** the site has no font stylesheet address
- **THEN** pages link no outside font stylesheet

#### Scenario: Insecure address refused

- **WHEN** an administrator enters a font stylesheet address that does not start with https://
- **THEN** the change is refused with a message saying the address must use https

### Requirement: Administrators edit the site's details

Administrators SHALL be able to change the site's name, tagline, language, logo and font stylesheet address from an admin screen. Other users MUST NOT be able to open the screen or save changes.

#### Scenario: Administrator saves new details

- **WHEN** an administrator changes the tagline and saves
- **THEN** the new tagline is stored and appears on the home page

#### Scenario: Logo uploaded

- **WHEN** an administrator uploads a PNG, JPEG, GIF or WebP image of up to 2 MB as the logo
- **THEN** the image is stored and becomes the site's logo

#### Scenario: Other files refused as a logo

- **WHEN** an administrator uploads an SVG or any file that is not one of those image types
- **THEN** the upload is refused with a message listing the accepted types

#### Scenario: Logo removed

- **WHEN** an administrator removes the site's logo
- **THEN** pages show the active theme's default logo

#### Scenario: Language limited to available translations

- **WHEN** an administrator chooses the site's language
- **THEN** only languages that have interface text in the install are offered

#### Scenario: Editor is refused

- **WHEN** a signed-in user who is not an administrator opens the site details screen or submits changes to it
- **THEN** the request is refused and nothing changes

#### Scenario: Name required

- **WHEN** an administrator saves the site with an empty name
- **THEN** the change is refused with a message asking for a name

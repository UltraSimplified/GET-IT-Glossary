# Spec Delta

## Purpose

Holds what makes an install of the glossary itself (its name, tagline, logo, interface language and any licensed font stylesheet) and lets administrators change it without touching code or configuration files.

## ADDED Requirements

### Requirement: Administrators set the "I don't get it" pictures

The Site screen SHALL let administrators upload three pictures for the "I don't get it" button: at rest, on hover, and after the click. For each, it MUST show what the button shows now: the site's picture, or the built-in bubble.

#### Scenario: Pictures uploaded

- **WHEN** an administrator uploads resting, hover and thanks pictures on the Site screen and saves
- **THEN** the pictures are stored, and term pages show them on the button

#### Scenario: What is shown now

- **WHEN** an administrator opens the Site screen on a site with no pictures of its own
- **THEN** each of the three slots shows the built-in bubble it falls back to

### Requirement: Picture uploads follow the logo rules

Each picture SHALL be a PNG, JPEG, GIF or WebP image of up to 2 MB. Any other file, SVG included, MUST be refused with a message listing the accepted types, and nothing is stored.

#### Scenario: SVG refused

- **WHEN** an administrator uploads an SVG as the resting picture
- **THEN** the upload is refused with a message listing PNG, JPEG, GIF and WebP, and the site's pictures are unchanged

#### Scenario: Too large

- **WHEN** an administrator uploads a 3 MB PNG as the thanks picture
- **THEN** the upload is refused with a message giving the 2 MB limit

### Requirement: The resting picture comes first

A hover or thanks picture SHALL only be saved when the site has a resting picture, either already or uploaded in the same save. Otherwise the save MUST be refused with a message asking for the resting picture first.

#### Scenario: Thanks picture without a resting one

- **WHEN** an administrator uploads only a thanks picture on a site with no resting picture
- **THEN** the save is refused with a message asking for the resting picture first, and nothing is stored

### Requirement: Pictures can be removed

Administrators SHALL be able to remove any of the pictures. Removing the hover or thanks picture MUST make that state fall back to the resting picture. Removing the resting picture MUST remove the hover and thanks pictures too, returning the button to the built-in bubbles.

#### Scenario: Remove the hover picture

- **WHEN** an administrator removes the hover picture and saves
- **THEN** the button shows the resting picture on hover

#### Scenario: Remove the resting picture

- **WHEN** an administrator removes the resting picture on a site with all three and saves
- **THEN** all three pictures are removed, and the button shows the built-in bubbles

### Requirement: Only administrators change the pictures

Changing the "I don't get it" pictures SHALL be limited to administrators, like the rest of the Site screen.

#### Scenario: Editor refused

- **WHEN** a signed-in user who is not an administrator submits new pictures
- **THEN** the request is refused and the pictures are unchanged

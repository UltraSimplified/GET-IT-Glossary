# Spec Delta

## Purpose

Lets readers tell editors that a definition did not help them, by pressing "I don't get it" and optionally saying why, and lets editors review and handle those replies.

## ADDED Requirements

### Requirement: Built-in speech bubbles by default

When the site has no "I don't get it" pictures of its own, the button SHALL show built-in speech bubbles: "I don't get it" at rest, "Click & tell us" while it is hovered or has keyboard focus, and "Thanks!" after it has been pressed.

#### Scenario: Fresh install

- **WHEN** a visitor opens a term page on a site with no pictures of its own
- **THEN** the button shows a speech bubble reading "I don't get it"

#### Scenario: Hover and focus

- **WHEN** a visitor hovers over the button or moves keyboard focus to it
- **THEN** the bubble reads "Click & tell us"

#### Scenario: After the click

- **WHEN** a visitor presses the button
- **THEN** the bubble reads "Thanks!"

### Requirement: Built-in bubbles follow the theme and language

The built-in bubbles SHALL be drawn in the active theme's colours and heading font. Their words MUST come from the site's interface language, falling back to English for any word that has no translation.

#### Scenario: Theme colours

- **WHEN** the same term page is viewed under the classic theme and then under the AIM theme
- **THEN** the bubble's outline and text take each theme's colours

#### Scenario: Language

- **WHEN** the site's interface language has a translation for the bubble's words
- **THEN** the bubble shows the translated words, and otherwise the English ones

### Requirement: The site's own pictures replace the bubbles

When the site has a resting picture, the button SHALL show the site's pictures instead of the bubbles: the resting picture at rest, the hover picture while hovered or focused, and the thanks picture after it is pressed. A missing hover or thanks picture MUST fall back to the resting picture.

#### Scenario: All three set

- **WHEN** the site has resting, hover and thanks pictures and a visitor hovers over the button and then presses it
- **THEN** the button shows the resting picture, then the hover picture, then the thanks picture

#### Scenario: Only the resting picture set

- **WHEN** the site has only a resting picture and a visitor hovers over the button and then presses it
- **THEN** the button shows the resting picture throughout

### Requirement: The pictures are decorative

Whatever it shows, the button SHALL keep the accessible name "I don't get it". Its pictures and the bubbles' words MUST NOT be announced separately by screen readers.

#### Scenario: Screen reader

- **WHEN** a screen reader reaches the button on a site with its own pictures, and again on a site with the built-in bubbles
- **THEN** both times it announces a button named "I don't get it", and nothing else for the picture

### Requirement: No licensed artwork ships with the glossary

The code base SHALL NOT include artwork licensed only to a particular organisation. A site MUST show such artwork only after an administrator has uploaded it.

#### Scenario: Fresh install

- **WHEN** the glossary is installed from the repository and a visitor opens a term page
- **THEN** no picture other than the built-in bubbles is shown or requested for the button

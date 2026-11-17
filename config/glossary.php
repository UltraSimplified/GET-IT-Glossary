<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Glossary instance
    |--------------------------------------------------------------------------
    |
    | Several language editions of the glossary run from the same codebase.
    | The instance name is quoted in outgoing emails so recipients can tell
    | which edition a message came from. The interface language itself is
    | controlled by APP_LOCALE (see lang/{locale}/interface.php).
    |
    */

    'instance' => env('GLOSSARY_INSTANCE', 'English'),

    /*
    |--------------------------------------------------------------------------
    | Contact addresses
    |--------------------------------------------------------------------------
    |
    | Where the "Get in touch" forms deliver. The env names match the legacy
    | Laravel 5 application so an existing .env can be carried over as is.
    |
    */

    'contact' => [
        'name' => env('EMAIL_FROM', env('APP_NAME', 'Glossary')),
        'suggestions' => env('EMAIL_ADDRESS'),
        'feedback' => env('EMAIL_FEEDBACK_ADDRESS', env('EMAIL_ADDRESS')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Term tags
    |--------------------------------------------------------------------------
    |
    | The fixed set of tags editors can attach to a term.
    |
    */

    'tags' => [
        'diagnosis',
        'economics',
        'guidelines',
        'HTA',
        'iEtD',
        'iSoF',
        'testing treatments',
    ],

    /*
    |--------------------------------------------------------------------------
    | First site settings
    |--------------------------------------------------------------------------
    |
    | The site's name, tagline, logo, language and theme live in the database
    | and are changed on the Site screen. These settings, with APP_NAME and
    | APP_LOCALE, are read only once: when the site record is first created
    | by `php artisan migrate`. The logo path is relative to the public
    | directory; without one the theme's default logo is shown.
    |
    */

    'logo' => env('GLOSSARY_LOGO'),

    'theme' => env('GLOSSARY_THEME', 'classic'),

    /*
    |--------------------------------------------------------------------------
    | Bundled fonts
    |--------------------------------------------------------------------------
    |
    | Open fonts the build downloads and serves from /build (see the fonts
    | list in vite.config.ts). Themes choose from these by family name, and
    | each page loads only the families its theme uses. `aliases` are the
    | names the @fonts directive takes; `generic` ends the font stack.
    |
    */

    'fonts' => [
        'Atkinson Hyperlegible Next' => [
            'generic' => 'sans-serif',
            'aliases' => ['atkinson-hyperlegible-next', 'atkinson-hyperlegible-mono'],
        ],
        'Source Sans 3' => [
            'generic' => 'sans-serif',
            'aliases' => ['source-sans-3'],
        ],
        'Source Serif 4' => [
            'generic' => 'serif',
            'aliases' => ['source-serif-4'],
        ],
    ],

    'soundcloud_profile' => env('GLOSSARY_SOUNDCLOUD', 'https://soundcloud.com/get-it-glossary'),

];

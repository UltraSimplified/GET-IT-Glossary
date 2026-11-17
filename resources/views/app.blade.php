@inject('currentSite', 'App\Support\CurrentSite')
@inject('themeStyles', 'App\Themes\ThemeStyles')
@php
    $site = $currentSite->site();
    $theme = $currentSite->theme();
    $hasDark = $theme->hasDark();
    $fontOrigin = $site->fontStylesheetOrigin();
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-site-name="{{ $site->name }}"
    data-has-dark="{{ $hasDark ? 'true' : 'false' }}"
    @class(['dark' => $hasDark && ($appearance ?? 'system') == 'dark'])
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Apply a dark preference before the first paint, when the theme has dark colours --}}
        <script>
            (function() {
                const root = document.documentElement;

                if (root.dataset.hasDark !== 'true') {
                    root.classList.remove('dark');

                    return;
                }

                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    root.classList.add('dark');
                }
            })();
        </script>

        {{-- The site's theme: colours, fonts and letter shape, in place for the first paint --}}
        <style>
            {!! $themeStyles->css($theme) !!}
            html {
                background-color: var(--theme-background);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/icons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="196x196" href="/assets/img/icons/favicon-196x196.png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @if ($fontOrigin)
            {{-- Licensed fonts (such as an Adobe Fonts kit) from the site's font stylesheet --}}
            <link rel="preconnect" href="{{ $fontOrigin }}" crossorigin>
            <link rel="stylesheet" href="{{ $site->font_stylesheet_url }}">
        @endif

        {{-- Only the theme's fonts; the theme editor previews any bundled font, so it gets them all --}}
        @fonts($page['component'] === 'admin/themes/Edit' ? null : $theme->fontAliases())

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $site->name }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

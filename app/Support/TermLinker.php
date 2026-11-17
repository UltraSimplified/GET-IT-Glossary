<?php

namespace App\Support;

/**
 * Handles the glossary's inline cross-reference syntax.
 *
 *   [bias]                 links the word "bias" to the term "bias"
 *   [skewed|bias]          links the word "skewed" to the term "bias"
 *   !!                     marks a synonym or phrase as "not recommended"
 */
class TermLinker
{
    private const string PIPED = '/\[([\w\s,\'+\-]+)\|([\w\s,\'+\-]+)\]/u';

    private const string PLAIN = '/\[([\w\s,\'+\-]+)\]/u';

    public const string WARNING_MARK = '<span class="warn-mark" role="img" aria-label="Not recommended"></span>';

    /**
     * A term name or search phrase as a URL segment. Spaces become "+", as
     * they always have in glossary URLs.
     */
    public static function slug(string $text): string
    {
        $slug = rawurlencode(str_replace(' ', '+', trim($text)));

        return str_replace(['%2B', '%2F', '%27', '%2C'], ['+', '/', "'", ','], $slug);
    }

    /**
     * Reverse of slug(): what the visitor meant by a URL segment.
     */
    public static function unslug(string $segment): string
    {
        return trim(mb_strtolower(str_replace('+', ' ', $segment)));
    }

    /**
     * The public URL path for a term name.
     */
    public static function path(string $term): string
    {
        return '/term/'.self::slug($term);
    }

    /**
     * Turn cross-references into links to their term pages.
     */
    public static function link(?string $html): string
    {
        return self::replace($html, function (string $text, string $term): string {
            return '<a href="'.e(self::path($term)).'">'.$text.'</a>';
        });
    }

    /**
     * Remove the cross-reference syntax, keeping only the visible words.
     */
    public static function plain(?string $html): string
    {
        return self::replace($html, fn (string $text): string => $text);
    }

    /**
     * Emphasise cross-references without linking them (used by the widget,
     * which is embedded on other people's sites).
     */
    public static function strong(?string $html): string
    {
        return self::replace($html, fn (string $text): string => '<strong>'.$text.'</strong>');
    }

    /**
     * Swap the "!!" marker for the warning icon.
     */
    public static function warnings(?string $html): string
    {
        return str_replace('!!', self::WARNING_MARK, (string) $html);
    }

    /**
     * @param  callable(string, string): string  $callback  receives the visible text and the target term
     */
    private static function replace(?string $html, callable $callback): string
    {
        $html = (string) $html;

        if ($html === '' || ! str_contains($html, '[')) {
            return $html;
        }

        $piped = preg_replace_callback(
            self::PIPED,
            fn (array $m): string => $callback($m[1], $m[2]),
            $html,
        );

        // preg returns null on malformed UTF-8; leave such content untouched.
        if ($piped === null) {
            return $html;
        }

        return preg_replace_callback(
            self::PLAIN,
            fn (array $m): string => $callback($m[1], $m[1]),
            $piped,
        ) ?? $piped;
    }
}

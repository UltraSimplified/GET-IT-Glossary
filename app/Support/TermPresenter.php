<?php

namespace App\Support;

use App\Models\IdgiReply;
use App\Models\Term;

/**
 * Shapes Term models into the arrays the Vue pages render.
 *
 * Term text is authored by trusted editors and may contain inline HTML, so
 * the *_html values are rendered as markup on the client.
 */
class TermPresenter
{
    /**
     * A term as it appears in a list (A–Z listing, search results, top ten).
     *
     * @return array<string, mixed>
     */
    public static function summary(Term $term): array
    {
        return [
            'id' => $term->id,
            'term' => $term->term,
            'term_ordered' => $term->term_ordered,
            'path' => $term->path(),
            'do_not_use' => (bool) $term->do_not_use,
            'synonyms_html' => self::synonyms($term->synonyms),
            'definition_html' => trim(TermLinker::plain($term->definition)),
        ];
    }

    /**
     * Everything shown on a term's own page.
     *
     * @return array<string, mixed>
     */
    public static function full(Term $term): array
    {
        return [
            'id' => $term->id,
            'master_id' => $term->master_id,
            'term' => $term->term,
            'path' => $term->path(),
            'version' => $term->version,
            'do_not_use' => (bool) $term->do_not_use,
            'definition_html' => trim(TermLinker::link($term->definition)),
            'synonyms_html' => self::synonyms($term->synonyms),
            'explanation_html' => nl2br(TermLinker::warnings(TermLinker::link(trim((string) $term->explanation))), false),
            'cartoon_html' => (string) $term->cartoon,
            'video_html' => (string) $term->video,
            'example_html' => (string) $term->example,
            'links_html' => (string) $term->links,
            'warning_html' => (string) $term->warning,
            'see_also' => self::seeAlso($term->see_also),
            'tags' => self::tags($term->tags),
            'pronunciation' => $term->pronunciation,
        ];
    }

    /**
     * Who saved a version and when, plus its "I don't get it" score.
     *
     * @return array<string, mixed>
     */
    public static function versionMeta(Term $term): array
    {
        $percent = $term->idgiPercent();

        return [
            'version' => $term->version,
            'updated_by' => $term->version > 1 ? $term->updater?->name : null,
            // Version 1 was imported; later versions show how long ago they were saved.
            'date' => $term->version === 1
                ? $term->created_at?->translatedFormat('l d F Y')
                : $term->updated_at?->diffForHumans(),
            'idgi' => $percent === null ? null : [
                'percent' => $percent,
                'count' => $term->IDGI,
                'hits' => $term->hits,
            ],
        ];
    }

    /**
     * What readers said after pressing "I don't get it", for every version
     * of the term: new replies first, then those dealt with, newest first
     * within each.
     *
     * @return list<array{id: int, message: string, date: string|null, datetime: string|null, version: int, earlier: bool, handled: array{by: string, date: string|null}|null}>
     */
    public static function idgiReplies(Term $term): array
    {
        $replies = IdgiReply::query()
            ->where('master_id', $term->master_id)
            ->with(['term:id,version', 'handler:id,first_name,last_name'])
            ->orderByRaw('handled_at is not null')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return array_values($replies->map(fn (IdgiReply $reply): array => [
            'id' => $reply->id,
            'message' => $reply->message,
            'date' => $reply->created_at?->diffForHumans(),
            'datetime' => $reply->created_at?->toIso8601String(),
            'version' => $reply->term->version,
            'earlier' => $reply->term->version < $term->version,
            'handled' => $reply->handled_at === null ? null : [
                'by' => $reply->handler !== null ? $reply->handler->name : __('Former user'),
                'date' => $reply->handled_at->diffForHumans(),
            ],
        ])->all());
    }

    /**
     * One side of the version comparison screen.
     *
     * @return array<string, mixed>
     */
    public static function comparable(Term $term): array
    {
        return [
            ...self::full($term),
            'status' => $term->term_status,
            'meta' => self::versionMeta($term),
        ];
    }

    /**
     * Synonyms are stored as "one; two; !!three". Present them as a comma
     * separated phrase with warning icons in place of "!!".
     */
    public static function synonyms(?string $synonyms): string
    {
        $items = array_filter(array_map('trim', explode(';', (string) $synonyms)), fn (string $s) => $s !== '');

        return TermLinker::warnings(e(implode(', ', $items), false));
    }

    /**
     * @return list<array{term: string, path: string}>
     */
    public static function seeAlso(?string $seeAlso): array
    {
        $terms = array_filter(array_map('trim', explode(';', (string) $seeAlso)), fn (string $s) => $s !== '');

        return array_values(array_map(
            fn (string $term): array => ['term' => $term, 'path' => TermLinker::path($term)],
            $terms,
        ));
    }

    /**
     * @return list<string>
     */
    public static function tags(?string $tags): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', (string) $tags)),
            fn (string $tag) => $tag !== '',
        ));
    }
}

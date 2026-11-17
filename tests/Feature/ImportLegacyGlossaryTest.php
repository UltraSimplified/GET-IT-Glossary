<?php

namespace Tests\Feature;

use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportLegacyGlossaryTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'glossary-dump-').'.sql.gz';
        file_put_contents($this->path, gzencode($this->dump()));
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    /**
     * A small dump in the phpMyAdmin format the live site exports.
     */
    private function dump(): string
    {
        $hash = Hash::make('legacy-password');

        return <<<SQL
        SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

        CREATE TABLE `users` (
          `id` int(10) UNSIGNED NOT NULL
        ) ENGINE=InnoDB;

        INSERT INTO `users` (`id`, `first_name`, `last_name`, `enabled`, `username`, `password`, `created_at`, `updated_at`, `remember_token`, `email`, `is_admin`) VALUES
        (1, 'Robin', 'O\\'Reader', 1, 'robin', '{$hash}', '2015-05-29 08:28:58', '0000-00-00 00:00:00', '', 'Robin@Example.COM', 1),
        (2, 'Doug', 'Editor', 1, '', '{$hash}', '2015-05-29 08:31:32', '2015-05-29 08:31:32', 'abc', 'doug@example.com', 0);

        INSERT INTO `terms` (`id`, `master_id`, `term`, `term_ordered`, `do_not_use`, `warning`, `term_status`, `tags`, `version`, `avoid_using`, `synonyms`, `see_also`, `definition`, `explanation`, `common_variants`, `audio_clip`, `video`, `test`, `cartoon`, `example`, `hits`, `IDGI`, `enabled`, `updated_by`, `created_at`, `updated_at`) VALUES
        (2, 2, 'acceptability', '', 0, NULL, 'REVISION', '', 1, '', NULL, NULL, 'First wording;\nwith a semicolon at the end of a line;
        ', 'It''s quoted two ways: \\"here\\" and ''there''.', '', '', NULL, '', '', '', 40, 3, 0, 6, '2015-05-27 21:20:00', '2020-01-01 10:00:00'),
        (3, 3, 'bias', 'bias', 1, 'Avoid', 'LIVE', 'HTA', 1, '', 'systematic error', 'blinding', 'A push away from the [truth].', 'Back\\\\slash', '', '', NULL, '', '', '', 12, 1, 1, 0, '2015-05-27 21:20:00', '0000-00-00 00:00:00');
        INSERT INTO `terms` (`id`, `master_id`, `term`, `term_ordered`, `do_not_use`, `warning`, `term_status`, `tags`, `version`, `avoid_using`, `synonyms`, `see_also`, `definition`, `explanation`, `common_variants`, `audio_clip`, `video`, `test`, `cartoon`, `example`, `hits`, `IDGI`, `enabled`, `updated_by`, `created_at`, `updated_at`) VALUES
        (307, 2, 'acceptability', '', 0, NULL, 'LIVE', '', 2, '', NULL, NULL, 'Second wording.', '', '', '', NULL, '', '', '', 5, 0, 1, 1, '2015-05-27 21:20:00', '2026-04-09 12:00:00'),
        (302, 0, 'concurrent validity', '', 0, NULL, 'LIVE', '', 1, '', NULL, NULL, 'Made with the old create form.', '', '', '', NULL, '', '', '', 0, 0, 1, 1, '2026-03-01 09:00:00', '2026-03-01 09:00:00'),
        (303, 0, 'Cronbach alpha', '', 0, NULL, 'LIVE', '', 1, '', NULL, NULL, 'Also made with the old create form.', '', '', '', NULL, '', '', '', 0, 0, 1, 1, '2026-03-01 09:00:00', '2026-03-01 09:00:00');

        INSERT INTO `pages` (`id`, `title`, `body`, `menu`, `position`, `created_at`, `updated_at`, `updated_by`, `slug`) VALUES
        (11, '<h1>Who we are</h1>', '<p>Editors.</p>', 1, 2, '2014-10-06 10:23:17', '2014-10-06 10:23:17', 1, 'who-we-are');

        INSERT INTO `comments` (`id`, `term_id`, `comment`, `author_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
        (1, 307, 'new comment here', 1, '2026-04-09 12:28:48', '2026-04-09 12:28:48', NULL),
        (2, 307, 'removed on the old site', 1, '2026-04-09 12:28:48', '2026-04-09 12:28:48', '2026-04-10 09:00:00');

        INSERT INTO `searches` (`id`, `search`, `term`, `term_id`, `created_at`, `updated_at`, `ip_address`) VALUES
        (1, 'bias', 'bias', 3, '2015-06-01 10:00:00', '2015-06-01 10:00:00', '127.0.0.1'),
        (2, 'nothing; here', 'NO MATCH', 0, '2015-06-01 10:00:00', '2015-06-01 10:00:00', '127.0.0.1');

        ALTER TABLE `users`
          MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

        ALTER TABLE `terms`
          MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=309;
        COMMIT;
        SQL;
    }

    public function test_it_replaces_the_data_and_keeps_ids_versions_and_text_intact(): void
    {
        User::factory()->create(['email' => 'sample@example.com']);
        Term::factory()->create(['term' => 'sample term']);

        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'sample@example.com']);
        $this->assertDatabaseMissing('terms', ['term' => 'sample term']);

        $this->assertSame([2, 3, 302, 303, 307], Term::query()->orderBy('id')->pluck('id')->all());

        $first = Term::query()->findOrFail(2);
        $this->assertSame("First wording;\nwith a semicolon at the end of a line;\n", $first->definition);
        $this->assertSame('It\'s quoted two ways: "here" and \'there\'.', $first->explanation);
        $this->assertSame(Term::STATUS_REVISION, $first->term_status);
        $this->assertSame(6, $first->updated_by, 'Versions keep the id of the person who saved them.');

        $bias = Term::query()->findOrFail(3);
        $this->assertSame('Back\\slash', $bias->explanation);
        $this->assertTrue($bias->do_not_use);
        $this->assertNull($bias->updated_by, 'A zero user id means nobody.');
        $this->assertNull($bias->updated_at, 'MySQL zero dates become empty.');

        // Both versions of "acceptability" belong to the same term.
        $this->assertSame([1, 2], Term::query()->findOrFail(307)->versions()->pluck('version')->all());
    }

    public function test_terms_saved_without_a_version_group_get_their_own(): void
    {
        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true])->assertSuccessful();

        $this->assertSame(302, Term::query()->findOrFail(302)->master_id);
        $this->assertSame(303, Term::query()->findOrFail(303)->master_id);
        $this->assertSame(0, Term::query()->where('master_id', 0)->count());
    }

    public function test_imported_users_can_sign_in_with_their_old_password(): void
    {
        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true])->assertSuccessful();

        $robin = User::query()->findOrFail(1);
        $this->assertSame('robin@example.com', $robin->email);
        $this->assertSame("O'Reader", $robin->last_name);
        $this->assertTrue($robin->is_admin);
        $this->assertNull(User::query()->findOrFail(2)->username);

        $this->post('/login', ['email' => 'Robin@Example.com', 'password' => 'legacy-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($robin);
    }

    public function test_pages_and_live_comments_are_imported_and_numbering_continues(): void
    {
        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('pages', ['id' => 11, 'slug' => 'who-we-are', 'menu' => 1]);
        $this->assertSame([1], DB::table('comments')->pluck('id')->all(), 'Comments deleted on the old site stay deleted.');

        // New records carry on from the old database's numbering.
        $this->assertSame(10, User::factory()->create()->id);
        $this->assertSame(309, Term::factory()->create()->id);
    }

    public function test_the_search_log_is_only_imported_when_asked_for(): void
    {
        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true])->assertSuccessful();
        $this->assertSame(0, DB::table('searches')->count());

        $this->artisan('glossary:import', ['file' => $this->path, '--force' => true, '--searches' => true])->assertSuccessful();
        $this->assertSame(2, DB::table('searches')->count());
        $this->assertDatabaseHas('searches', ['id' => 2, 'search' => 'nothing; here', 'term_id' => null]);
    }

    public function test_it_asks_before_replacing_data(): void
    {
        $this->artisan('glossary:import', ['file' => $this->path])
            ->expectsConfirmation(
                'This replaces every user, term, page and comment in the [sqlite] database (:memory:). Continue?',
                'no',
            )
            ->assertFailed();

        $this->artisan('glossary:import', ['file' => '/no/such/file.sql'])->assertFailed();
    }
}

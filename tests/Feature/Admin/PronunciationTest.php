<?php

namespace Tests\Feature\Admin;

use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PronunciationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads', ['url' => '/uploads']);
    }

    private function recording(string $name = 'pronunciation.webm', string $type = 'audio/webm'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 24, $type);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return [
            'term' => 'placebo',
            'term_ordered' => '',
            'term_status' => Term::STATUS_LIVE,
            'do_not_use' => false,
            'tags' => [],
            'definition' => 'A dummy treatment.',
            'mode' => 'update',
            ...$overrides,
        ];
    }

    public function test_an_administrator_uploads_a_recording(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post('/upload/pronunciation', ['file' => $this->recording()])
            ->assertOk();

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringStartsWith('/uploads/pronunciations/', $url);
        Storage::disk('uploads')->assertExists('pronunciations/'.basename($url));
    }

    public function test_safari_recordings_are_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/upload/pronunciation', ['file' => $this->recording('pronunciation.m4a', 'audio/mp4')])
            ->assertOk();
    }

    public function test_an_editor_of_a_term_can_record(): void
    {
        $editor = User::factory()->create();
        $term = Term::factory()->create();
        TermEditor::query()->create(['term_id' => $term->master_id, 'user_id' => $editor->id]);

        $this->actingAs($editor)
            ->post('/upload/pronunciation', ['file' => $this->recording()])
            ->assertOk();
    }

    public function test_someone_who_edits_no_terms_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/upload/pronunciation', ['file' => $this->recording()])
            ->assertForbidden();
    }

    public function test_only_audio_is_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/upload/pronunciation', ['file' => UploadedFile::fake()->image('word.png')])
            ->assertSessionHasErrors(['file' => 'The recording must be an audio file.']);
    }

    public function test_long_recordings_are_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/upload/pronunciation', ['file' => UploadedFile::fake()->create('long.webm', 3000, 'audio/webm')])
            ->assertSessionHasErrors('file');
    }

    public function test_the_pronunciation_is_saved_with_the_term_and_shown_on_its_page(): void
    {
        $term = Term::factory()->create(['term' => 'placebo']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['pronunciation' => '/uploads/pronunciations/abc123.webm']))
            ->assertSessionHasNoErrors();

        $this->assertSame('/uploads/pronunciations/abc123.webm', $term->fresh()?->pronunciation);

        $this->get("/terms/{$term->id}/edit")
            ->assertInertia(fn (Assert $page) => $page->where('term.pronunciation', '/uploads/pronunciations/abc123.webm'));

        $this->get('/term/placebo')
            ->assertInertia(fn (Assert $page) => $page
                ->where('term.pronunciation', '/uploads/pronunciations/abc123.webm')
                ->missing('term.audio_track'));
    }

    public function test_a_new_version_keeps_the_pronunciation(): void
    {
        $term = Term::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['pronunciation' => '/uploads/pronunciations/abc123.webm', 'mode' => 'version']))
            ->assertSessionHasNoErrors();

        $this->assertSame('/uploads/pronunciations/abc123.webm', Term::query()->where('master_id', $term->master_id)->where('version', 2)->sole()->pronunciation);
    }

    public function test_only_uploaded_recordings_can_be_saved(): void
    {
        $term = Term::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['pronunciation' => 'https://example.com/x.mp3']))
            ->assertSessionHasErrors(['pronunciation' => 'Record the pronunciation again: the recording could not be found.']);

        $this->assertNull($term->fresh()?->pronunciation);
    }

    public function test_removing_the_pronunciation_clears_it(): void
    {
        $term = Term::factory()->create(['pronunciation' => '/uploads/pronunciations/abc123.webm']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form(['pronunciation' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($term->fresh()?->pronunciation);
    }

    public function test_old_soundcloud_clips_are_kept_but_no_longer_shown(): void
    {
        $term = Term::factory()->create(['term' => 'placebo', 'audio_clip' => '<iframe src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/123"></iframe>']);

        $this->actingAs(User::factory()->admin()->create())
            ->put("/terms/{$term->id}", $this->form())
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString('soundcloud', (string) $term->fresh()?->audio_clip);
        $this->get('/term/placebo')->assertInertia(fn (Assert $page) => $page->missing('term.audio_track'));
    }
}

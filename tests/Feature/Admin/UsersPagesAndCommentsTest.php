<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Page;
use App\Models\Term;
use App\Models\TermEditor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersPagesAndCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_list_shows_each_persons_role(): void
    {
        $admin = User::factory()->admin()->create(['last_name' => 'Aardvark']);
        $editor = User::factory()->create(['last_name' => 'Badger']);
        User::factory()->create(['last_name' => 'Camel']);
        TermEditor::query()->create(['term_id' => Term::factory()->create()->master_id, 'user_id' => $editor->id]);

        $this->actingAs($admin)->get('/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('users', 3)
                ->where('users.0.role', 'Admin')
                ->where('users.1.role', 'Editor')
                ->where('users.1.terms', 1)
                ->where('users.2.role', 'Contributor'));
    }

    public function test_an_admin_can_add_a_user_with_a_working_password(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/users', [
            'first_name' => 'Nina',
            'last_name' => 'New',
            'username' => 'nina',
            'email' => 'Nina@Example.com',
            'password' => 'a-long-passphrase',
            'is_admin' => false,
        ])->assertRedirect('/users');

        $user = User::query()->where('email', 'nina@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('a-long-passphrase', $user->password));
        $this->assertFalse($user->is_admin);
    }

    public function test_an_admin_can_update_a_user_and_the_terms_they_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['password' => 'original-password']);
        $bias = Term::factory()->create(['term' => 'bias']);
        $placebo = Term::factory()->create(['term' => 'placebo']);
        TermEditor::query()->create(['term_id' => $bias->master_id, 'user_id' => $user->id]);

        $this->actingAs($admin)->get("/users/{$user->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Edit')
                ->where('terms.0.term', 'bias')
                ->where('terms.0.assigned', true)
                ->where('terms.1.assigned', false));

        $this->actingAs($admin)->put("/users/{$user->id}", [
            'first_name' => 'Renamed',
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => '',
            'is_admin' => true,
            'terms' => [$placebo->master_id],
        ])->assertRedirect('/users');

        $user->refresh();
        $this->assertSame('Renamed', $user->first_name);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('original-password', $user->password), 'A blank password keeps the current one.');
        $this->assertSame([$placebo->master_id], TermEditor::query()->where('user_id', $user->id)->pluck('term_id')->all());
    }

    public function test_admins_cannot_delete_or_demote_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        TermEditor::query()->create(['term_id' => 1, 'user_id' => $other->id]);

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertStatus(422);

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'first_name' => $admin->first_name, 'last_name' => $admin->last_name,
            'email' => $admin->email, 'is_admin' => false,
        ]);
        $this->assertTrue($admin->fresh()->is_admin);

        $this->actingAs($admin)->delete("/users/{$other->id}")->assertRedirect('/users');
        $this->assertModelMissing($other);
        $this->assertDatabaseMissing('terms_editors', ['user_id' => $other->id]);
    }

    public function test_an_admin_can_manage_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/pages', [
            'title' => 'Who we are',
            'body' => '<p>Editors and readers.</p>',
            'menu' => Page::MENU_ABOUT,
            'position' => 1,
        ])->assertRedirect('/pages');

        $page = Page::query()->where('slug', 'who-we-are')->firstOrFail();
        $this->assertSame('/about/who-we-are', $page->path());

        $this->get('/')->assertInertia(fn (Assert $inertia) => $inertia
            ->where('glossary.footer.0.links.1.href', '/about/who-we-are'));

        $this->actingAs($admin)->put("/pages/{$page->id}", [
            'title' => 'About the team',
            'body' => '<p>Updated.</p>',
            'menu' => null,
            'position' => null,
        ])->assertRedirect('/pages');

        $page->refresh();
        $this->assertSame('About the team', $page->title);
        $this->assertSame('who-we-are', $page->slug, 'Editing a title must not break existing links.');

        $this->actingAs($admin)->post('/pages', ['title' => '', 'body' => ''])
            ->assertSessionHasErrors(['title', 'body']);

        $this->actingAs($admin)->delete("/pages/{$page->id}")->assertRedirect('/pages');
        $this->assertModelMissing($page);
    }

    public function test_only_editors_and_admins_can_upload_images(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);
        $editor = User::factory()->create();
        TermEditor::query()->create(['term_id' => 1, 'user_id' => $editor->id]);

        $this->actingAs(User::factory()->create())
            ->postJson('/upload', ['file' => UploadedFile::fake()->image('cartoon.png')])
            ->assertForbidden();

        $url = $this->actingAs($editor)
            ->postJson('/upload', ['file' => UploadedFile::fake()->image('cartoon.png')])
            ->assertOk()
            ->json('url');

        $this->assertStringStartsWith('/uploads/', $url);
        Storage::disk('uploads')->assertExists(basename($url));

        $this->actingAs($editor)
            ->postJson('/upload', ['file' => UploadedFile::fake()->create('script.php', 4, 'text/x-php')])
            ->assertUnprocessable();
    }

    public function test_signed_in_users_can_comment_and_only_the_author_or_an_admin_can_delete(): void
    {
        $term = Term::factory()->create(['term' => 'bias', 'hits' => 3]);
        $author = User::factory()->create();
        $stranger = User::factory()->create();

        $this->post('/comments', ['term_id' => $term->id, 'comment' => 'Hello'])->assertRedirect('/login');

        $this->actingAs($author)->from('/term/bias')
            ->post('/comments', ['term_id' => $term->id, 'comment' => 'Reads <b>well</b>.'])
            ->assertRedirect('/term/bias');

        $comment = Comment::query()->firstOrFail();
        $this->assertSame($author->id, $comment->author_id);

        // Returning to the term after commenting is not a new view.
        $this->actingAs($author)->get('/term/bias');
        $this->assertSame(3, $term->fresh()->hits);
        $this->actingAs($author)->get('/term/bias');
        $this->assertSame(4, $term->fresh()->hits);

        $this->actingAs($stranger)->delete("/comments/{$comment->id}")->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->delete("/comments/{$comment->id}")->assertRedirect();
        $this->assertModelMissing($comment);
    }

    public function test_the_comment_thread_groups_comments_by_version(): void
    {
        $author = User::factory()->create();
        $first = Term::factory()->revision()->create(['term' => 'bias', 'version' => 1, 'definition' => 'Old.']);
        $current = Term::factory()->create(['term' => 'bias', 'version' => 2, 'master_id' => $first->id, 'updated_by' => $author->id]);
        $first->comments()->create(['comment' => 'On the first version.', 'author_id' => $author->id]);
        $current->comments()->create(['comment' => 'On the current version.', 'author_id' => $author->id]);

        $this->get('/comments/bias')->assertRedirect('/login');

        $this->actingAs($author)->get('/comments/bias')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Comments')
                ->where('term.term', 'bias')
                ->has('versions', 2)
                ->where('versions.0.version', 1)
                ->where('versions.0.comments.0.comment', 'On the first version.')
                ->where('versions.1.version', 2)
                ->where('versions.1.updated_by', $author->name));
    }
}

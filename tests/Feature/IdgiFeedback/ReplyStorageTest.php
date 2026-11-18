<?php

namespace Tests\Feature\IdgiFeedback;

use App\Models\IdgiReply;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reply_belongs_to_its_version_and_its_term(): void
    {
        $first = Term::factory()->create();
        $second = Term::factory()->create(['master_id' => $first->master_id, 'version' => 2]);

        $reply = IdgiReply::factory()->for($second)->create();

        $this->assertTrue($reply->term->is($second));
        $this->assertSame($first->master_id, $reply->master_id);
    }

    public function test_deleting_the_version_deletes_its_replies(): void
    {
        $term = Term::factory()->create();
        $reply = IdgiReply::factory()->for($term)->create();

        $term->delete();

        $this->assertModelMissing($reply);
    }

    public function test_deleting_the_handling_user_keeps_the_reply(): void
    {
        $editor = User::factory()->create();
        $reply = IdgiReply::factory()->handled($editor)->create();

        $editor->delete();

        $reply->refresh();
        $this->assertNull($reply->handled_by);
        $this->assertNotNull($reply->handled_at);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with local development accounts and
     * sample glossary content.
     *
     * All three accounts use the factory's default password ("password").
     * They exist for local development only; never seed a production database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'first_name' => 'Ada',
            'last_name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->create([
            'first_name' => 'Edith',
            'last_name' => 'Editor',
            'username' => 'editor',
            'email' => 'editor@example.com',
        ]);

        User::factory()->create([
            'first_name' => 'Colin',
            'last_name' => 'Contributor',
            'username' => 'contributor',
            'email' => 'contributor@example.com',
        ]);

        $this->call(GlossarySeeder::class);
    }
}

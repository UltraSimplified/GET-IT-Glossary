<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A recording of the term said aloud, made in the term editor. It takes
     * the place of the SoundCloud clip, whose old embed codes stay in
     * `audio_clip` untouched.
     */
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->string('pronunciation')->nullable()->after('audio_clip');
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn('pronunciation');
        });
    }
};

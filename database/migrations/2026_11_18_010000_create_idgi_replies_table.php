<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What readers say after pressing "I don't get it". Replies are
     * anonymous: only the words, the version they were reading and when.
     */
    public function up(): void
    {
        Schema::create('idgi_replies', function (Blueprint $table) {
            $table->id();
            // The version the reader was looking at. The import clears terms
            // with plain deletes, so replies go with the terms they belong to.
            $table->foreignId('term_id')->constrained('terms')->cascadeOnDelete();
            // The term as a whole, so one query lists every version's replies.
            $table->unsignedBigInteger('master_id')->index();
            $table->text('message');
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idgi_replies');
    }
};

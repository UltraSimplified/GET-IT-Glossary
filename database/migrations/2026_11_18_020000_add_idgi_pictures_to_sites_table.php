<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The site's own pictures for the "I don't get it" button: at rest, on
     * hover and after the click. Without a resting picture the button shows
     * the built-in speech bubbles.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('idgi_rest')->nullable()->after('logo');
            $table->string('idgi_hover')->nullable()->after('idgi_rest');
            $table->string('idgi_thanks')->nullable()->after('idgi_hover');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['idgi_rest', 'idgi_hover', 'idgi_thanks']);
        });
    }
};

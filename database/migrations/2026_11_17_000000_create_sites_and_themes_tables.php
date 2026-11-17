<?php

use App\Actions\Site\CreateSiteFromConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The site record holds what makes an install itself (name, tagline,
     * logo, language, font stylesheet, theme). Custom themes are rows of
     * `themes`; the built-in themes are files in resources/themes.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tagline')->default('');
            $table->string('locale', 10)->default('en');
            $table->string('logo')->nullable();
            $table->string('font_stylesheet_url')->nullable();
            $table->json('font_families')->nullable();
            $table->string('theme_key', 64)->default('classic');
            $table->timestamps();
        });

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('based_on', 64)->nullable();
            $table->json('settings');
            $table->timestamps();
        });

        // The one site row, from the install's existing settings.
        (new CreateSiteFromConfig)->handle();
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
        Schema::dropIfExists('sites');
    }
};

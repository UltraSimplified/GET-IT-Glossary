<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table and column names are kept identical to the legacy Laravel 5
     * schema so that a dump of the live glossary can be loaded straight in.
     */
    public function up(): void
    {
        $sqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        Schema::create('terms', function (Blueprint $table) use ($sqlite) {
            $table->id();

            // Every saved version of a term is its own row. Rows that belong
            // to the same term share a master_id (the id of version 1).
            $table->unsignedBigInteger('master_id')->default(0)->index();

            // MySQL compares and sorts names without regard to case; SQLite
            // needs telling, or "PICO" sorts before "acceptability".
            $term = $table->string('term');
            $ordered = $table->string('term_ordered')->default('');

            if ($sqlite) {
                $term->collation('nocase');
                $ordered->collation('nocase');
            }

            $table->boolean('do_not_use')->default(false);
            $table->text('warning')->nullable();
            $table->string('term_status', 20)->default('LIVE')->index();
            $table->string('tags')->default('');
            $table->integer('version')->default(1);
            $table->text('avoid_using')->nullable();
            $table->string('synonyms')->nullable();
            $table->text('see_also')->nullable();
            $table->text('links')->nullable();
            $table->text('definition')->nullable();
            $table->text('explanation')->nullable();
            $table->text('common_variants')->nullable();
            $table->text('audio_clip')->nullable();
            $table->text('video')->nullable();
            $table->text('test')->nullable();
            $table->text('cartoon')->nullable();
            $table->text('example')->nullable();
            $table->integer('hits')->default(0);
            $table->integer('IDGI')->default(0)->comment("I Don't Get It!");
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('term');

            if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText('term');
                $table->fullText('common_variants');
                $table->fullText('synonyms');
                $table->fullText('definition');
                $table->fullText('explanation');
                $table->fullText(
                    ['term', 'common_variants', 'synonyms', 'definition', 'explanation'],
                    'terms_search_fulltext',
                );
            }
        });

        Schema::create('terms_editors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id')->comment('The master_id of the term');
            $table->foreignId('user_id');
            $table->timestamps();

            $table->index(['term_id', 'user_id']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('slug')->index();
            $table->integer('menu')->nullable();
            $table->integer('position')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id')->index();
            $table->mediumText('comment');
            $table->unsignedBigInteger('author_id');
            $table->timestamps();
        });

        Schema::create('searches', function (Blueprint $table) {
            $table->id();
            $table->string('search');
            $table->string('term')->nullable();
            $table->unsignedBigInteger('term_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searches');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('terms_editors');
        Schema::dropIfExists('terms');
    }
};

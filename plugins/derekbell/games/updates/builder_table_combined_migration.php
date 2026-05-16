<?php

namespace DerekBell\Games\Updates;

use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCombinedMigration extends Migration
{
    public function up()
    {
        // Create the games table if it doesn't exist
        if (!Schema::hasTable('derekbell_games_games')) {
            Schema::create('derekbell_games_games', function ($table) {
                $table->engine = 'InnoDB';

                $table->increments('id');
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->text('excerpt')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_published')->nullable()->default(true);
                $table->boolean('is_promoted')->nullable()->default(false);
                $table->integer('sort_order')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Create the episodes table if it doesn't exist
        if (!Schema::hasTable('derekbell_games_episodes')) {
            Schema::create('derekbell_games_episodes', function ($table) {
                $table->engine = 'InnoDB';

                $table->increments('id');
                $table->integer('game_id')->unsigned()->nullable()->index();
                $table->string('title', 255)->nullable();
                $table->string('slug', 255)->nullable();
                $table->integer('start_level')->nullable();
                $table->integer('end_level')->nullable();
                $table->integer('episode_number')->nullable();
                $table->text('excerpt')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_published')->default(true)->nullable();
                $table->boolean('is_promoted')->default(false)->nullable();
                $table->integer('sort_order')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                // Adding a foreign key constraint for game_id referencing id on derekbell_games_games table
                $table->foreign('game_id')->references('id')->on('derekbell_games_games')->onDelete('cascade');
            });
        }

        // Create the levels table if it doesn't exist
        if (!Schema::hasTable('derekbell_games_levels')) {
            Schema::create('derekbell_games_levels', function ($table) {
                $table->engine = 'InnoDB';

                $table->increments('id');
                $table->integer('episode_id')->nullable()->unsigned()->index();
                $table->string('title')->nullable();
                $table->string('slug')->nullable();
                $table->integer('level_number')->nullable()->unsigned();
                $table->integer('episode_number')->nullable()->unsigned();
                $table->string('youtube_id')->nullable();
                $table->text('excerpt')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_published')->nullable()->default(true);
                $table->boolean('is_promoted')->nullable()->default(false);
                $table->integer('sort_order')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();

                // Adding a foreign key constraint for episode_id referencing id on derekbell_games_episodes table
                $table->foreign('episode_id')->references('id')->on('derekbell_games_episodes')->onDelete('cascade');
            });
        }

    }

    public function down()
    {
        Schema::table('derekbell_games_levels', function ($table) {
            $table->dropForeign(['episode_id']);
        });

        Schema::table('derekbell_games_episodes', function ($table) {
            $table->dropForeign(['game_id']);
        });

        Schema::dropIfExists('derekbell_games_levels');
        Schema::dropIfExists('derekbell_games_episodes');
        Schema::dropIfExists('derekbell_games_games');
    }
}

<?php

namespace DerekBell\Games\Updates;

use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameEpisodesIdColumn extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('derekbell_games_levels', 'episodes_id')) {
            // Try to drop the foreign key if it exists
            try {
                Schema::table('derekbell_games_levels', function ($table) {
                    $table->dropForeign(['episodes_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            // Rename the column
            Schema::table('derekbell_games_levels', function ($table) {
                $table->renameColumn('episodes_id', 'episode_id');
            });

            // Add the foreign key constraint
            Schema::table('derekbell_games_levels', function ($table) {
                $table->foreign('episode_id')->references('id')->on('derekbell_games_episodes')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::table('derekbell_games_levels', function ($table) {
            if (Schema::hasColumn('derekbell_games_levels', 'episode_id')) {
                // Drop foreign key
                $table->dropForeign(['episode_id']);

                // Rename back
                $table->renameColumn('episode_id', 'episodes_id');

                // Re-add foreign key with old name
                $table->foreign('episodes_id')->references('id')->on('derekbell_games_episodes')->onDelete('cascade');
            }
        });
    }
}

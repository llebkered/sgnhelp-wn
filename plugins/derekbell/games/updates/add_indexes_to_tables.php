<?php

namespace DerekBell\Games\Updates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

class AddIndexesToTables extends Migration
{
    public function up()
    {
        // Add indexes to games table
        if (!$this->indexExists('derekbell_games_games', 'idx_games_slug')) {
            Schema::table('derekbell_games_games', function ($table) {
                $table->index('slug', 'idx_games_slug');
            });
        }
        if (!$this->indexExists('derekbell_games_games', 'idx_games_is_published')) {
            Schema::table('derekbell_games_games', function ($table) {
                $table->index('is_published', 'idx_games_is_published');
            });
        }
        if (!$this->indexExists('derekbell_games_games', 'idx_games_is_promoted')) {
            Schema::table('derekbell_games_games', function ($table) {
                $table->index('is_promoted', 'idx_games_is_promoted');
            });
        }
        if (!$this->indexExists('derekbell_games_games', 'idx_games_sort_order')) {
            Schema::table('derekbell_games_games', function ($table) {
                $table->index('sort_order', 'idx_games_sort_order');
            });
        }
        if (!$this->indexExists('derekbell_games_games', 'idx_games_published_promoted')) {
            Schema::table('derekbell_games_games', function ($table) {
                $table->index(['is_published', 'is_promoted'], 'idx_games_published_promoted');
            });
        }

        // Add indexes to episodes table
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_slug')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index('slug', 'idx_episodes_slug');
            });
        }
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_is_published')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index('is_published', 'idx_episodes_is_published');
            });
        }
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_is_promoted')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index('is_promoted', 'idx_episodes_is_promoted');
            });
        }
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_sort_order')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index('sort_order', 'idx_episodes_sort_order');
            });
        }
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_game_slug')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index(['game_id', 'slug'], 'idx_episodes_game_slug');
            });
        }
        if (!$this->indexExists('derekbell_games_episodes', 'idx_episodes_game_published')) {
            Schema::table('derekbell_games_episodes', function ($table) {
                $table->index(['game_id', 'is_published'], 'idx_episodes_game_published');
            });
        }

        // Add indexes to levels table
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_slug')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('slug', 'idx_levels_slug');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_is_published')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('is_published', 'idx_levels_is_published');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_is_promoted')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('is_promoted', 'idx_levels_is_promoted');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_sort_order')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('sort_order', 'idx_levels_sort_order');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_level_number')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('level_number', 'idx_levels_level_number');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_episode_slug')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index(['episode_id', 'slug'], 'idx_levels_episode_slug');
            });
        }
        if (!$this->indexExists('derekbell_games_levels', 'idx_levels_episode_published')) {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index(['episode_id', 'is_published'], 'idx_levels_episode_published');
            });
        }
    }

    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    public function down()
    {
        // Skip dropping indexes to avoid errors if they don't exist
        // Indexes will be recreated in up() if needed
    }
}

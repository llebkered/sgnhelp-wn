<?php namespace DerekBell\Games\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddLevelsQueryIndexes extends Migration
{
    public function up()
    {
        // Indexes may already exist from a previous migration, wrap in try/catch
        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('is_published');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index('is_promoted');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->index(['is_promoted', 'updated_at']);
            });
        } catch (\Exception $e) {}
    }

    public function down()
    {
        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->dropIndex(['is_published']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->dropIndex(['is_promoted']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('derekbell_games_levels', function ($table) {
                $table->dropIndex(['is_promoted', 'updated_at']);
            });
        } catch (\Exception $e) {}
    }
}

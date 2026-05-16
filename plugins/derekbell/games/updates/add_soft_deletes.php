<?php

namespace DerekBell\Games\Updates;

use Illuminate\Support\Facades\Schema;
use Winter\Storm\Database\Updates\Migration;

class AddSoftDeletes extends Migration
{
    public function up()
    {
        // Add soft delete column to games table
        Schema::table('derekbell_games_games', function ($table) {
            $table->timestamp('deleted_at')->nullable();
        });

        // Add soft delete column to episodes table
        Schema::table('derekbell_games_episodes', function ($table) {
            $table->timestamp('deleted_at')->nullable();
        });

        // Add soft delete column to levels table
        Schema::table('derekbell_games_levels', function ($table) {
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        // Remove soft delete column from games table
        Schema::table('derekbell_games_games', function ($table) {
            $table->dropColumn('deleted_at');
        });

        // Remove soft delete column from episodes table
        Schema::table('derekbell_games_episodes', function ($table) {
            $table->dropColumn('deleted_at');
        });

        // Remove soft delete column from levels table
        Schema::table('derekbell_games_levels', function ($table) {
            $table->dropColumn('deleted_at');
        });
    }
}

<?php namespace DerekBell\Games\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddDeletedAtToGamesTables extends Migration
{
    public function up()
    {
        Schema::table('derekbell_games_games', function($table) {
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::table('derekbell_games_episodes', function($table) {
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::table('derekbell_games_levels', function($table) {
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('derekbell_games_games', function($table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('derekbell_games_episodes', function($table) {
            $table->dropColumn('deleted_at');
        });
        Schema::table('derekbell_games_levels', function($table) {
            $table->dropColumn('deleted_at');
        });
    }
}

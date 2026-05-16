<?php namespace DerekBell\Games\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddUniqueEpisodePerGame extends Migration
{
    public function up()
    {
        Schema::table('derekbell_games_episodes', function ($table) {
            $table->unique(['game_id', 'episode_number'], 'games_episodes_game_episode_unique');
        });
    }

    public function down()
    {
        Schema::table('derekbell_games_episodes', function ($table) {
            $table->dropUnique('games_episodes_game_episode_unique');
        });
    }
}

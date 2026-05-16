<?php namespace DerekBell\Games\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class FixYoutubeIdDefault extends Migration
{
    public function up()
    {
        Schema::table('derekbell_games_levels', function ($table) {
            $table->string('youtube_id', 255)->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('derekbell_games_levels', function ($table) {
            $table->string('youtube_id', 255)->default('xxxx')->change();
        });
    }
}

<?php

use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;

class AddColorThemeToGames extends Migration
{
    public function up()
    {
        Schema::table('derekbell_games_games', function (Blueprint $table) {
            $table->string('color_theme', 50)->default('default')->after('is_promoted');
        });
    }

    public function down()
    {
        Schema::table('derekbell_games_games', function (Blueprint $table) {
            $table->dropColumn('color_theme');
        });
    }
}

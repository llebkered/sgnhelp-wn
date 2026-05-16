<?php

namespace DerekBell\Games\Database\Seeds;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use Winter\Storm\Database\Updates\Seeder;

class SeedGamesData extends Seeder
{
    public function run()
    {
        $gamesPath = base_path('plugins/derekbell/games/database/data/games.json');
        $episodesPath = base_path('plugins/derekbell/games/database/data/episodes.json');
        $levelsPath = base_path('plugins/derekbell/games/database/data/levels.json');

        $games = json_decode(file_get_contents($gamesPath), true);
        $episodes = json_decode(file_get_contents($episodesPath), true);
        $levels = json_decode(file_get_contents($levelsPath), true);

        // Truncate tables (disable foreign key checks for safety)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Level::truncate();
        Episode::truncate();
        Game::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Seed games
        foreach ($games as $gameData) {
            $game = Game::create([
                'id' => $gameData['id'],
                'title' => $gameData['name'],
                'slug' => isset($gameData['slug']) ? $gameData['slug'] : str_slug($gameData['name']),
                'excerpt' => $gameData['name'],
                'description' => $gameData['name'],
                'is_published' => true,
                'is_promoted' => false,
                'sort_order' => $gameData['id'],
            ]);
        }

        // Seed episodes
        foreach ($episodes as $episodeData) {
            $episode = Episode::create([
                'id' => $episodeData['id'],
                'game_id' => $episodeData['game_id'],
                'title' => $episodeData['name'],
                'slug' => isset($episodeData['slug']) ? $episodeData['slug'] : str_slug($episodeData['name']),
                'start_level' => null,
                'end_level' => null,
                'episode_number' => $episodeData['id'],
                'excerpt' => $episodeData['name'],
                'description' => $episodeData['name'],
                'is_published' => true,
                'is_promoted' => false,
                'sort_order' => $episodeData['id'],
            ]);
        }

        // Seed levels
        foreach ($levels as $levelData) {
            $level = Level::create([
                'id' => $levelData['id'],
                'episode_id' => $levelData['episode_id'],
                'title' => $levelData['name'],
                'slug' => isset($levelData['slug']) ? $levelData['slug'] : str_slug($levelData['name']),
                'level_number' => $levelData['id'],
                'episode_number' => $levelData['episode_id'],
                'youtube_id' => isset($levelData['youtube_id']) ? $levelData['youtube_id'] : null,
                'excerpt' => $levelData['name'],
                'description' => $levelData['name'],
                'is_published' => true,
                'is_promoted' => false,
                'sort_order' => $levelData['id'],
            ]);
        }
    }
}

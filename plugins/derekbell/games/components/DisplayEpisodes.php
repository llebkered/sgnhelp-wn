<?php

namespace DerekBell\Games\Components;

use Cms\Classes\ComponentBase;
use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Helpers\CacheHelper;

class DisplayEpisodes extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Display Episodes Component',
            'description' => 'Displays published episodes as cards for a particular game with promoted episodes at the top'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * Runs when the component is executed
     */
    public function onRun()
    {
        // Load the game and episodes from the database
        $this->page['game'] = $this->loadGame();
        $this->page['episodes'] = $this->loadEpisodes();
    }

    /**
     * Loads the game from the database based on URL slug
     */
    protected function loadGame()
    {
        $game_slug = $this->param('slug') ?: $this->param('game');

        if (!$game_slug) {
            return null;
        }

        $game = Game::where('slug', $game_slug)
            ->with('logo')
            ->first();

        return $game;
    }

    /**
     * Loads published episodes from the database for the current game
     * with promoted episodes displayed first
     */
    protected function loadEpisodes()
    {
        $game = $this->page['game'];

        if (!$game) {
            return collect([]);
        }

        // Get published episodes with promoted episodes first
        $episodes = Episode::where('game_id', $game->id)
            ->isPublished()
            ->orderBy('is_promoted', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->remember(CacheHelper::CACHE_DURATION_EPISODES, CacheHelper::getEpisodesListKey($game->id))
            ->get();

        return $episodes;
    }
}

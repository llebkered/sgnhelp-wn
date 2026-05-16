<?php

namespace DerekBell\Games\Components;

use Cms\Classes\ComponentBase;
use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Helpers\CacheHelper;
use DerekBell\Games\Jobs\AttachYoutubeThumbnail;

class DisplayLevels extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Display Levels Component',
            'description' => 'Displays the levels of the game'
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
        // Load the levels from the database
        $this->page['levels'] = $this->loadLevels();
    }

    /**
     * Loads the levels from the database
     */
    protected function loadLevels()
    {
        // get the url params
        $game_slug = $this->param('game');
        $episode_slug = $this->param('episode');

        // get the game with episodes eager loaded
        $game = Game::where('slug', $game_slug)
            ->with('episodes')
            ->first();

        if (!$game) {
            return [];
        }

        $episode = $game->episodes()->where('slug', $episode_slug)->first();

        if (!$episode) {
            return [];
        }

        // Eager load youtube_thumbnail attachment to prevent N+1
        $levels = Level::where('episode_id', $episode->id)
            ->isPublished()
            ->with('youtube_thumbnail')
            ->orderBy('sort_order', 'asc')
            ->remember(CacheHelper::CACHE_DURATION_LEVELS, CacheHelper::getEpisodeLevelsKey($episode->id))
            ->get();

        // loop through the levels to check if they have thumbnails
        foreach ($levels as $level) {
            // Only dispatch job if youtube_id exists
            if ($level->youtube_id && !$this->hasAttachedThumbnail($level)) {
                dispatch(new AttachYoutubeThumbnail($level));
            }
        }

        return $levels;
    }

    /**
     * Checks if the level has an attached thumbnail
     */
    protected function hasAttachedThumbnail($level)
    {
        // check if the level has an attached youtube thumbnail
        if ($level->youtube_thumbnail) {
            return true;
        }
        return false;
    }

}

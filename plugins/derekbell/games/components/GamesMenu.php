<?php

namespace DerekBell\Games\Components;

use Cms\Classes\ComponentBase;
use DerekBell\Games\Models\Game;
use DerekBell\Games\Helpers\CacheHelper;
use enshrined\svgSanitize\Sanitizer;


class GamesMenu extends ComponentBase
{
    /**
     * Provides details about the GamesMenu component.
     *
     * @return array An associative array containing the component's name and description.
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Games Menu',
            'description' => 'Displays a list of published games'
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
     * Executed when this component is bound to a page or layout.
     */
    public function onRun()
    {
        $this->page['games'] = $this->loadPublishedGames();

        // loop through the games and add the logo path to each game
        foreach ($this->page['games'] as $game) {
            $game->logo_svg = $this->getLogoSVG($game);
        }
    }

    /**
     * Fetches the logo SVG content and returns it as a sanitized string.
     *
     * @param \DerekBell\Games\Models\Game $game The game instance containing the logo.
     * @return string|false The sanitized SVG content or false if no logo is found.
     */
    public function getLogoSVG($game)
    {
        if (!$game->logo) {
            return false;
        }

        $logo_path = $game->logo->getPath();

        // strip protocol and domain from the path
        $logo_path = str_replace(url('/'), '', $logo_path);

        // fetch the file contents
        $svg_content = $game->logo->getContents();

        // check if the file is an SVG
        if (strpos($svg_content, '<svg') === false) {
            return false;
        }

        // sanitise the SVG — the library strips <script> tags, on* event handler
        // attributes, and javascript: hrefs by default; minify() only controls whitespace
        $sanitizer = new Sanitizer();
        $sanitizer->minify(true);
        $svg_content = $sanitizer->sanitize($svg_content);

        // sanitize() returns false for invalid/unparseable SVG
        if (!$svg_content) {
            return false;
        }

        // Remove the XML declaration
        $svg_content = preg_replace('/<\?xml.*?\?>/', '', $svg_content);

        return $svg_content;
    }

    /**
     * Loads the published games from the database.
     *
     * This method retrieves all games that are marked as published,
     * orders them by promotion status in descending order, and then
     * by title in ascending order.
     *
     * Results are cached for 1 hour to improve performance.
     * Eager loads logo attachments to prevent N+1 queries.
     *
     * @return \Illuminate\Database\Eloquent\Collection The collection of published games.
     */
    protected function loadPublishedGames()
    {
        // Use query caching and eager load logo to prevent N+1
        return Game::isPublished()
            ->with('logo')
            ->orderBy('is_promoted', 'desc')
            ->orderBy('title', 'asc')
            ->remember(CacheHelper::CACHE_DURATION_GAMES, CacheHelper::getGamesListKey())
            ->get();
    }
}

<?php namespace DerekBell\Games;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'DerekBell\Games\Components\GamesMenu' => 'gamesMenu',
            'DerekBell\Games\Components\DisplayLevels' => 'displayLevels',
            'DerekBell\Games\Components\DisplayEpisodes' => 'displayEpisodes',
            'DerekBell\Games\Components\FrontPageLevels' => 'frontPageLevels',
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'Games Settings',
                'description' => 'Configure limits and defaults for the Games plugin.',
                'category'    => 'Games',
                'icon'        => 'icon-gamepad',
                'class'       => \DerekBell\Games\Models\GamesSettings::class,
                'order'       => 500,
                'keywords'    => 'games levels episodes limits scaffold playlist',
                'permissions' => ['derekbell.games.access_games'],
            ],
        ];
    }

    /**
     * Register backend permissions
     */
    public function registerPermissions()
    {
        return [
            'derekbell.games.access_games' => [
                'tab' => 'Games',
                'label' => 'Manage Games',
                'comment' => 'Allows managing game entries',
            ],
            'derekbell.games.access_episodes' => [
                'tab' => 'Games',
                'label' => 'Manage Episodes',
                'comment' => 'Allows managing episode entries',
            ],
            'derekbell.games.access_levels' => [
                'tab' => 'Games',
                'label' => 'Manage Levels',
                'comment' => 'Allows managing level entries',
            ],
            'derekbell.games.importexport' => [
                'tab' => 'Games',
                'label' => 'Import/Export',
                'comment' => 'Allows import/export of games, levels, episodes, and images',
            ],
        ];
    }

    /**
     * Register console commands
     */
    public function register()
    {
        $this->registerConsoleCommand('games:clear-cache', \DerekBell\Games\Console\ClearCaches::class);
        $this->registerConsoleCommand('games:export', \DerekBell\Games\Console\ExportGames::class);
        $this->registerConsoleCommand('games:import', \DerekBell\Games\Console\ImportGames::class);
        $this->registerConsoleCommand('games:seed-json', \DerekBell\Games\Console\SeedGamesJson::class);
        $this->registerConsoleCommand('episodes:export', \DerekBell\Games\Console\ExportEpisodes::class);
        $this->registerConsoleCommand('episodes:import', \DerekBell\Games\Console\ImportEpisodes::class);
        $this->registerConsoleCommand('levels:export', \DerekBell\Games\Console\ExportLevels::class);
        $this->registerConsoleCommand('levels:import', \DerekBell\Games\Console\ImportLevels::class);
    }

    /**
     * Register search handlers for the Winter.Search plugin
     */
    public function registerSearchHandlers()
    {
        return [
            'games' => [
                'name' => 'Games',
                'model' => \DerekBell\Games\Models\Game::class,
                'record' => function ($model, $query) {
                    if (!$model->is_published) {
                        return false;
                    }
                    return [
                        'title' => $model->title,
                        'description' => $model->excerpt,
                        'url' => '/game/' . $model->slug,
                    ];
                },
            ],
            'episodes' => [
                'name' => 'Episodes',
                'model' => \DerekBell\Games\Models\Episode::class,
                'record' => function ($model, $query) {
                    if (!$model->is_published) {
                        return false;
                    }
                    return [
                        'title' => $model->title,
                        'description' => $model->excerpt,
                        'url' => '/game/' . optional($model->game)->slug . '/' . $model->slug,
                    ];
                },
            ],
            'levels' => [
                'name' => 'Levels',
                'model' => \DerekBell\Games\Models\Level::class,
                'record' => function ($model, $query) {
                    if (!$model->is_published) {
                        return false;
                    }
                    $episode = $model->episode;
                    $game = optional($episode)->game;
                    return [
                        'title' => $model->title,
                        'description' => $model->excerpt,
                        'url' => '/game/' . optional($game)->slug . '/' . optional($episode)->slug . '/' . $model->slug,
                    ];
                },
            ],
        ];
    }

    /**
     * Register plugin routes
     */
    public function boot()
    {
        // Load backend CSS fixes to avoid icon font leaking into component labels
        \Event::listen('backend.page.beforeDisplay', function($controller) {
            $controller->addCss('/plugins/derekbell/games/assets/css/backend-fixes.css');
        });
    }
}

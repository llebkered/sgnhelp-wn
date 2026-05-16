<?php namespace DerekBell\Games\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Backend;
use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Helpers\YoutubeHelper;
use DerekBell\Games\Models\GamesSettings;
use Flash;

class Scaffold extends Controller
{
    public $requiredPermissions = ['derekbell.games.access_episodes', 'derekbell.games.access_levels'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('DerekBell.Games', 'main-menu-item', 'scaffold');
    }

    public function index()
    {
        $this->pageTitle = 'Scaffold Episode';
        $this->vars['games']         = Game::isPublished()->orderBy('title', 'asc')->get();
        $this->vars['maxBulkLevels'] = (int) GamesSettings::get('max_bulk_levels', 100);
    }

    public function import()
    {
        BackendMenu::setContext('DerekBell.Games', 'main-menu-item', 'scaffold-playlist');
        $this->pageTitle = 'Scaffold Episode from Playlist';
        $this->vars['games']              = Game::isPublished()->orderBy('title', 'asc')->get();
        $this->vars['maxPlaylistLevels']  = (int) GamesSettings::get('max_playlist_levels', 500);
    }

    /**
     * AJAX handler to create episode with levels
     */
    public function onScaffoldEpisode()
    {
        $gameId = post('game_id');
        $episodeTitle = post('episode_title');
        $numberOfLevels = (int) post('number_of_levels', 0);
        $maxBulkLevels  = (int) GamesSettings::get('max_bulk_levels', 100);

        // Validation
        $rules = [
            'game_id'          => 'required|exists:derekbell_games_games,id',
            'episode_title'    => 'required|min:3|max:255',
            'number_of_levels' => "required|integer|min:1|max:{$maxBulkLevels}",
        ];

        $validation = \Validator::make(
            [
                'game_id'          => $gameId,
                'episode_title'    => $episodeTitle,
                'number_of_levels' => $numberOfLevels,
            ],
            $rules,
            [
                'game_id.required'          => 'Please select a game',
                'game_id.exists'            => 'Selected game does not exist',
                'episode_title.required'    => 'Episode title is required',
                'episode_title.min'         => 'Episode title must be at least 3 characters',
                'number_of_levels.required' => 'Number of levels is required',
                'number_of_levels.integer'  => 'Number of levels must be a number',
                'number_of_levels.min'      => 'You must create at least 1 level',
                'number_of_levels.max'      => "You cannot create more than {$maxBulkLevels} levels at once",
            ]
        );

        if ($validation->fails()) {
            throw new \ValidationException($validation);
        }

        try {
            // Get the game
            $game = Game::find($gameId);

            // Get next episode number
            $lastEpisodeNumber = Episode::where('game_id', $gameId)
                ->max('episode_number');

            $episodeNumber = $lastEpisodeNumber ? $lastEpisodeNumber + 1 : 1;

            // Create episode
            $episode = new Episode();
            $episode->game_id = $gameId;
            $episode->title = $episodeTitle;
            $episode->episode_number = $episodeNumber;
            $episode->is_published = false; // Default to unpublished for review
            $episode->save();

            // Create levels
            for ($i = 1; $i <= $numberOfLevels; $i++) {
                $level = new Level();
                $level->episode_id = $episode->id;
                $level->title = "Level {$i}";
                $level->level_number = $i;
                $level->episode_number = $episodeNumber;
                $level->sort_order = $i;
                $level->is_published = false; // Default to unpublished
                $level->save();
            }

            Flash::success("Successfully created episode '{$episodeTitle}' with {$numberOfLevels} levels!");

            return [
                'success' => true,
                'episodeId' => $episode->id,
                'message' => "Episode created successfully with {$numberOfLevels} levels",
                'redirect' => Backend::url('derekbell/games/episodecontroller/update/' . $episode->id)
            ];

        } catch (\Exception $e) {
            \Log::error('Scaffold episode failed: ' . $e->getMessage());
            throw new \ApplicationException('Failed to scaffold episode: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to create episode and levels from a YouTube playlist
     */
    public function onScaffoldEpisodeFromPlaylist()
    {
        $gameId = post('game_id');
        $playlistUrl = post('playlist_url');
        $episodeTitleInput = trim((string) post('episode_title'));

        $rules = [
            'game_id' => 'required|exists:derekbell_games_games,id',
            'playlist_url' => 'required',
            'episode_title' => 'nullable|max:255'
        ];

        $validation = \Validator::make(
            [
                'game_id' => $gameId,
                'playlist_url' => $playlistUrl,
                'episode_title' => $episodeTitleInput
            ],
            $rules,
            [
                'game_id.required' => 'Please select a game',
                'game_id.exists' => 'Selected game does not exist',
                'playlist_url.required' => 'Playlist URL is required',
                'episode_title.max' => 'Episode title cannot be longer than 255 characters'
            ]
        );

        if ($validation->fails()) {
            throw new \ValidationException($validation);
        }

        $youtube = new YoutubeHelper();
        $playlistId = $youtube->extractPlaylistId($playlistUrl);

        if (!$playlistId) {
            throw new \ValidationException(['playlist_url' => 'Invalid YouTube playlist URL or ID']);
        }

        $maxPlaylistLevels = (int) GamesSettings::get('max_playlist_levels', 500);

        try {
            $items = $youtube->fetchPlaylistItems($playlistId);
            $playlistTitle = $youtube->fetchPlaylistTitle($playlistId);

            if (empty($items)) {
                throw new \ApplicationException('No videos found in playlist');
            }

            if (count($items) > $maxPlaylistLevels) {
                $items = array_slice($items, 0, $maxPlaylistLevels);
                Flash::warning("Playlist exceeds the {$maxPlaylistLevels}-level limit. Only the first {$maxPlaylistLevels} videos will be imported.");
            }

            $result = \Db::transaction(function () use ($gameId, $playlistId, $playlistTitle, $items, $episodeTitleInput) {
                $game = Game::find($gameId);

                $lastEpisodeNumber = Episode::where('game_id', $gameId)
                    ->lockForUpdate()
                    ->max('episode_number');

                $episodeNumber = $lastEpisodeNumber ? $lastEpisodeNumber + 1 : 1;
                $episodeTitle = $episodeTitleInput ?: ($playlistTitle ?: 'Playlist ' . $playlistId);

                // Find the true max episode number across all existing episodes for this game
                $existingNumbers = Episode::where('game_id', $gameId)
                    ->withTrashed()
                    ->pluck('episode_number')
                    ->toArray();

                $episodeNumber = !empty($existingNumbers) ? max($existingNumbers) + 1 : 1;

                $episode = new Episode();
                $episode->game_id = $gameId;
                $episode->title = $episodeTitle;
                $episode->episode_number = $episodeNumber;
                $episode->is_published = false;

                // Remove unique constraint — we already computed a safe number
                $episode->rules['episode_number'] = 'required|integer|min:1';
                $episode->save();

                $importedCount = 0;
                $skippedCount = 0;
                $levelNumber = 1;

                foreach ($items as $item) {
                    if (empty($item['video_id'])) {
                        $skippedCount++;
                        continue;
                    }

                    $level = new Level();
                    $level->episode_id = $episode->id;
                    $level->title = $item['title'];
                    $level->description = $item['description'];
                    $level->youtube_id = $item['video_id'];
                    $level->level_number = $levelNumber;
                    $level->episode_number = $episodeNumber;
                    $level->sort_order = $levelNumber;
                    $level->is_published = false;
                    $level->save();

                    $levelNumber++;
                    $importedCount++;
                }

                return [
                    'episode' => $episode,
                    'importedCount' => $importedCount,
                    'skippedCount' => $skippedCount
                ];
            });

            Flash::success(
                "Created '{$result['episode']->title}' with {$result['importedCount']} levels" .
                ($result['skippedCount'] > 0 ? " ({$result['skippedCount']} skipped)" : '')
            );

            return [
                'success' => true,
                'episodeId' => $result['episode']->id,
                'message' => "Episode created successfully with {$result['importedCount']} levels",
                'redirect' => Backend::url('derekbell/games/episodecontroller/update/' . $result['episode']->id)
            ];
        } catch (\Exception $e) {
            \Log::error('Scaffold from playlist failed: ' . $e->getMessage());
            throw new \ApplicationException('Failed to scaffold episode: ' . $e->getMessage());
        }
    }
}

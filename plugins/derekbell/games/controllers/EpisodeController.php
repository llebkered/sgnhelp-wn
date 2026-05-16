<?php namespace DerekBell\Games\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Helpers\YoutubeHelper;
use DerekBell\Games\Models\GamesSettings;
use Flash;

class EpisodeController extends Controller
{
    public $implement = [
                'Backend\Behaviors\ListController',
                'Backend\Behaviors\FormController',
                'Backend\Behaviors\RelationController',
                \Backend\Behaviors\ReorderController::class];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public function onUploadImportFile()
    {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            return [
                'partial' => '<div class="alert alert-danger">Please upload a valid zip file.</div>'
            ];
        }
        $uploaded = $_FILES['import_file'];
        $unique = uniqid('import_', true) . '.zip';
        $target = storage_path('app/' . $unique);
        if (!move_uploaded_file($uploaded['tmp_name'], $target)) {
            return [
                'partial' => '<div class="alert alert-danger">Failed to save uploaded file.</div>'
            ];
        }
        $partial = '<div class="alert alert-success">File uploaded. Ready to import.<input type="hidden" id="uploaded_import_file" name="uploaded_import_file" value="' . e($unique) . '"></div>';
        return [
            'partial' => $partial
        ];
    }
    public $relationConfig = 'config_relation.yaml';
    public $requiredPermissions = ['derekbell.games.access_episodes'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('DerekBell.Games', 'main-menu-item', 'side-menu-item2');
        // Add import/export page to the controller
        $this->addViewPath(__DIR__ . '/episodecontroller');
    }
    public function importexport()
    {
        $this->pageTitle = 'Import/Export Episodes';
    }

    public function duplicates()
    {
        [$sortColumn, $sortDirection] = $this->getDuplicateSort(['id', 'title', 'slug', 'game', 'episode_number', 'levels_count', 'reasons', 'is_published'], 'title');
        $this->pageTitle = 'Duplicate Episodes';
        $this->vars['sortColumn'] = $sortColumn;
        $this->vars['sortDirection'] = $sortDirection;
        $this->vars['duplicateEpisodes'] = $this->findDuplicateEpisodes($sortColumn, $sortDirection);
    }

    public function onDeleteDuplicates()
    {
        $episodeIds = array_filter((array) post('episode_ids', []));

        if (empty($episodeIds)) {
            Flash::error('Select at least one duplicate episode to delete.');
            return redirect()->back();
        }

        $deletedCount = 0;

        foreach (Episode::whereIn('id', $episodeIds)->get() as $episode) {
            $episode->delete();
            $deletedCount++;
        }

        Flash::success($deletedCount . ' duplicate episode(s) deleted.');

        return redirect('backend/derekbell/games/episodecontroller/duplicates');
    }

    protected function findDuplicateEpisodes($sortColumn = 'title', $sortDirection = 'asc')
    {
        $episodes = Episode::with(['game'])->withCount('levels')->orderBy('title', 'asc')->get();
        $titleCounts = [];
        $slugCounts = [];
        $gameEpisodeNumberCounts = [];

        foreach ($episodes as $episode) {
            $normalizedTitle = $this->normalizeDuplicateValue($episode->title);
            $normalizedSlug = $this->normalizeDuplicateValue($episode->slug);
            $gameEpisodeKey = $episode->game_id . ':' . (string) $episode->episode_number;

            if ($normalizedTitle !== '') {
                $titleCounts[$normalizedTitle] = ($titleCounts[$normalizedTitle] ?? 0) + 1;
            }

            if ($normalizedSlug !== '') {
                $slugCounts[$normalizedSlug] = ($slugCounts[$normalizedSlug] ?? 0) + 1;
            }

            if ($episode->game_id && $episode->episode_number) {
                $gameEpisodeNumberCounts[$gameEpisodeKey] = ($gameEpisodeNumberCounts[$gameEpisodeKey] ?? 0) + 1;
            }
        }

        $duplicates = $episodes->map(function ($episode) use ($titleCounts, $slugCounts, $gameEpisodeNumberCounts) {
            $normalizedTitle = $this->normalizeDuplicateValue($episode->title);
            $normalizedSlug = $this->normalizeDuplicateValue($episode->slug);
            $gameEpisodeKey = $episode->game_id . ':' . (string) $episode->episode_number;
            $reasons = [];

            if ($normalizedTitle !== '' && ($titleCounts[$normalizedTitle] ?? 0) > 1) {
                $reasons[] = 'same title (' . $titleCounts[$normalizedTitle] . ')';
            }

            if ($normalizedSlug !== '' && ($slugCounts[$normalizedSlug] ?? 0) > 1) {
                $reasons[] = 'same slug (' . $slugCounts[$normalizedSlug] . ')';
            }

            if ($episode->game_id && $episode->episode_number && ($gameEpisodeNumberCounts[$gameEpisodeKey] ?? 0) > 1) {
                $reasons[] = 'same episode number in game (' . $gameEpisodeNumberCounts[$gameEpisodeKey] . ')';
            }

            if (empty($reasons)) {
                return null;
            }

            return [
                'episode' => $episode,
                'reasons' => implode(', ', $reasons),
            ];
        })->filter()->values();

        return $this->sortDuplicateEpisodes($duplicates, $sortColumn, $sortDirection);
    }

    protected function normalizeDuplicateValue($value)
    {
        return mb_strtolower(trim((string) $value));
    }

    protected function getDuplicateSort(array $allowedColumns, $defaultColumn)
    {
        $sortColumn = get('sort', $defaultColumn);
        $sortDirection = strtolower((string) get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!in_array($sortColumn, $allowedColumns, true)) {
            $sortColumn = $defaultColumn;
        }

        return [$sortColumn, $sortDirection];
    }

    protected function sortDuplicateEpisodes($duplicates, $sortColumn, $sortDirection)
    {
        $sorted = $duplicates->sortBy(function ($row) use ($sortColumn) {
            $episode = $row['episode'];

            switch ($sortColumn) {
                case 'id':
                    return $episode->id;
                case 'slug':
                    return $this->normalizeDuplicateValue($episode->slug);
                case 'game':
                    return $this->normalizeDuplicateValue(optional($episode->game)->title);
                case 'episode_number':
                    return (int) $episode->episode_number;
                case 'levels_count':
                    return (int) $episode->levels_count;
                case 'reasons':
                    return $this->normalizeDuplicateValue($row['reasons']);
                case 'is_published':
                    return $episode->is_published ? 1 : 0;
                case 'title':
                default:
                    return $this->normalizeDuplicateValue($episode->title);
            }
        });

        return $sortDirection === 'desc'
            ? $sorted->reverse()->values()
            : $sorted->values();
    }


    public function onExportEpisodes()
    {
        \Artisan::call('episodes:export');
        return redirect('backend/derekbell/games/episodecontroller/downloadExportEpisodes');
    }

    public function downloadExportEpisodes()
    {
        $file = storage_path('app/episodes-export.zip');
        if (!file_exists($file)) {
            \Flash::error('Export file not found.');
            return redirect()->back();
        }
        return \Response::download($file, 'episodes-export.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function onImportEpisodes()
    {
        $filename = post('uploaded_import_file');
        if (!$filename || !file_exists(storage_path('app/' . $filename))) {
            \Flash::error('No uploaded file found.');
            return redirect()->back();
        }
        $target = storage_path('app/episodes-export.zip');
        copy(storage_path('app/' . $filename), $target);
        \Artisan::call('episodes:import');
        \Flash::success('Episodes import complete.');
        return redirect()->back();
    }

    /**
     * Ensure list query returns distinct episodes (prevents duplicate rows from joins)
     */
    public function listExtendQuery($query)
    {
        $query->addSelect('derekbell_games_episodes.*')->distinct();
    }

    /**
     * Extend the filter widget to customize game options ordering
     */
    public function listFilterExtendScopes($filter)
    {
        if ($filter->getScope('game')) {
            $filter->addScopes([
                'game' => [
                    'label' => 'Filter by Game',
                    'type' => 'group',
                    'conditions' => 'game_id in (:filtered)',
                    'options' => \DerekBell\Games\Models\Game::isPublished()
                        ->orderBy('title', 'asc')
                        ->pluck('title', 'id')
                        ->all(),
                    'emptyOption' => 'All Games',
                ]
            ]);
        }
    }

    /**
     * AJAX handler to preview YouTube playlist
     */
    public function onImportPlaylist()
    {
        $episodeId = post('episode_id');
        $playlistUrl = post('playlist_url');

        if (!$episodeId) {
            throw new \ValidationException(['episode_id' => 'Episode ID is required']);
        }

        if (!$playlistUrl) {
            throw new \ValidationException(['playlist_url' => 'Playlist URL is required']);
        }

        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \ValidationException(['episode_id' => 'Episode not found']);
        }

        try {
            $youtube = new YoutubeHelper();
            $playlistId = $youtube->extractPlaylistId($playlistUrl);

            if (!$playlistId) {
                return [
                    '#playlist-preview' => $this->makePartial('playlist_preview', [
                        'error' => 'Invalid YouTube playlist URL or ID'
                    ])
                ];
            }

            $items = $youtube->fetchPlaylistItems($playlistId);

            return [
                '#playlist-preview' => $this->makePartial('playlist_preview', [
                    'items' => $items,
                    'episodeId' => $episodeId
                ])
            ];

        } catch (\Exception $e) {
            return [
                '#playlist-preview' => $this->makePartial('playlist_preview', [
                    'error' => $e->getMessage()
                ])
            ];
        }
    }

    /**
     * AJAX handler to confirm and import playlist videos as levels
     */
    public function onConfirmImportPlaylist()
    {
        $episodeId = post('episode_id');
        $playlistUrl = post('playlist_url');

        if (!$episodeId || !$playlistUrl) {
            throw new \ValidationException(['error' => 'Missing required data']);
        }

        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \ValidationException(['episode_id' => 'Episode not found']);
        }

        try {
            $youtube = new YoutubeHelper();
            $playlistId = $youtube->extractPlaylistId($playlistUrl);
            $items = $youtube->fetchPlaylistItems($playlistId);

            $maxPlaylistLevels = (int) GamesSettings::get('max_playlist_levels', 500);

            if (count($items) > $maxPlaylistLevels) {
                $items = array_slice($items, 0, $maxPlaylistLevels);
                Flash::warning("Playlist exceeds the {$maxPlaylistLevels}-level limit. Only the first {$maxPlaylistLevels} videos will be imported.");
            }

            $importedCount = 0;
            $skippedCount = 0;

            // Start level_number after the highest existing level for this episode
            $nextLevelNumber = (Level::where('episode_id', $episodeId)->max('level_number') ?? 0) + 1;

            \Db::transaction(function () use ($items, $episodeId, &$importedCount, &$skippedCount, &$nextLevelNumber) {
                foreach ($items as $item) {
                    // Check if level with this youtube_id already exists for this episode
                    $existingLevel = Level::where('episode_id', $episodeId)
                        ->where('youtube_id', $item['video_id'])
                        ->first();

                    if ($existingLevel) {
                        $skippedCount++;
                        continue;
                    }

                    // Create new level
                    $level = new Level();
                    $level->episode_id = $episodeId;
                    $level->title = $item['title'];
                    $level->description = $item['description'];
                    $level->youtube_id = $item['video_id'];
                    $level->level_number = $nextLevelNumber;
                    $level->sort_order = $item['position'];
                    $level->is_published = false; // Default to unpublished for review
                    $level->save();

                    $nextLevelNumber++;
                    $importedCount++;
                }
            });

            Flash::success("Successfully imported {$importedCount} levels" .
                         ($skippedCount > 0 ? " ({$skippedCount} skipped as duplicates)" : ""));

            return [
                '#playlist-preview' => $this->makePartial('import_success', [
                    'importedCount' => $importedCount,
                    'skippedCount'  => $skippedCount,
                ])
            ];

        } catch (\Exception $e) {
            \Log::error('Playlist import failed: ' . $e->getMessage());
            throw new \ApplicationException('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX handler to toggle publish state for a level
     */
    public function onToggleLevelPublish()
    {
        $levelId = post('id');
        $isPublished = (bool) post('is_published');

        if (!$levelId) {
            throw new \ValidationException(['id' => 'Level ID is required']);
        }

        $level = Level::find($levelId);
        if (!$level) {
            throw new \ApplicationException('Level not found');
        }

        $level->is_published = $isPublished;
        $level->save();

        Flash::success('Level ' . ($isPublished ? 'published' : 'unpublished') . '.');

        return ['success' => true];
    }

    /**
     * AJAX handler to toggle promoted state for a level
     */
    public function onToggleLevelPromoted()
    {
        $levelId = post('id');
        $isPromoted = (bool) post('is_promoted');

        if (!$levelId) {
            throw new \ValidationException(['id' => 'Level ID is required']);
        }

        $level = Level::find($levelId);
        if (!$level) {
            throw new \ApplicationException('Level not found');
        }

        $level->is_promoted = $isPromoted;
        $level->save();

        Flash::success('Level ' . ($isPromoted ? 'promoted' : 'unpromoted') . '.');

        return ['success' => true];
    }

    /**
     * AJAX handler to toggle publish state for an episode
     */
    public function onToggleEpisodePublish()
    {
        $episodeId = post('id');
        $isPublished = (bool) post('is_published');

        if (!$episodeId) {
            throw new \ValidationException(['id' => 'Episode ID is required']);
        }

        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \ApplicationException('Episode not found');
        }

        $episode->is_published = $isPublished;
        $episode->save();

        Flash::success('Episode ' . ($isPublished ? 'published' : 'unpublished') . '.');

        return ['success' => true];
    }

    /**
     * AJAX handler to toggle promoted state for an episode
     */
    public function onToggleEpisodePromoted()
    {
        $episodeId = post('id');
        $isPromoted = (bool) post('is_promoted');

        if (!$episodeId) {
            throw new \ValidationException(['id' => 'Episode ID is required']);
        }

        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \ApplicationException('Episode not found');
        }

        $episode->is_promoted = $isPromoted;
        $episode->save();

        Flash::success('Episode ' . ($isPromoted ? 'promoted' : 'unpromoted') . '.');

        return ['success' => true];
    }
}

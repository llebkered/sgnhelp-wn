<?php

namespace DerekBell\Games\Components;

use Cms\Classes\ComponentBase;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Helpers\CacheHelper;
use DerekBell\Games\Jobs\AttachYoutubeThumbnail;

class FrontPageLevels extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'FrontPageLevels Component',
            'description' => 'Provides a list of levels for the front page'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [
            'levelCount' => [
                'title'       => 'Number of Levels',
                'description' => 'The number of levels to display. Set to 0 to show all levels.',
                'default'     => 12,
                'type'        => 'number',
                'group'       => 'Settings',
            ],
            'paginate' => [
                'title'       => 'Enable Pagination',
                'description' => 'If checked, levels will be paginated and pagination links provided.',
                'default'     => true,
                'type'        => 'checkbox',
                'group'       => 'Settings',
            ],
            'perPage' => [
                'title'       => 'Levels Per Page',
                'description' => 'Number of levels per page when pagination is enabled.',
                'default'     => 12,
                'type'        => 'number',
                'group'       => 'Settings',
            ],
            'pageParam' => [
                'title'       => 'Page Parameter',
                'description' => 'Query parameter name used for pagination links (e.g., "page" or "levels_page").',
                'default'     => 'page',
                'type'        => 'string',
                'group'       => 'Settings',
            ],
            'paginationType' => [
                'title'       => 'Pagination Type',
                'description' => 'Use "simple" for faster queries with just Prev/Next, or "full" for page numbers.',
                'default'     => 'full',
                'type'        => 'dropdown',
                'options'     => [
                    'simple' => 'Simple (Prev/Next only)',
                    'full'   => 'Full (Page numbers)',
                ],
                'group'       => 'Settings',
            ],
        ];
    }

    /**
     * Runs when the component is executed
     */
    public function onRun()
    {
        // Load the levels and episodes from the database
        $this->page['levels'] = $this->loadLevels();
        $this->page['episodes'] = $this->loadEpisodes();
    }

    /**
     * Loads the levels from the database
     */
    protected function loadLevels()
    {
        // Get component properties
        $levelCount = (int) $this->property('levelCount');
        $paginate = (bool) $this->property('paginate');
        $perPage = (int) $this->property('perPage');
        $pageParam = $this->property('pageParam') ?: 'page';
        $paginationType = $this->property('paginationType') ?: 'full';

        // Promoted and published levels
        $query = Level::isPublished()
            ->isPromoted()
            ->with('episode')
            ->with('episode.game')
            ->with('youtube_thumbnail')
            ->orderBy('updated_at', 'desc');

        if ($levelCount === 0) {
            if ($paginate) {
                $levels = $paginationType === 'simple'
                    ? $query->simplePaginate($perPage, ['*'], $pageParam)
                    : $query->paginate($perPage, ['*'], $pageParam);
            } else {
                CacheHelper::registerFrontPageCount(0);
                $levels = $query
                    ->remember(CacheHelper::CACHE_DURATION_LEVELS, CacheHelper::getFrontPageLevelsKey(0))
                    ->get();
            }
        } else {
            CacheHelper::registerFrontPageCount($levelCount);
            $levels = $query
                ->take($levelCount)
                ->remember(CacheHelper::CACHE_DURATION_LEVELS, CacheHelper::getFrontPageLevelsKey($levelCount))
                ->get();
        }

        // Batch-dispatch thumbnail attachment jobs for levels without thumbnails
        foreach ($levels as $level) {
            if ($level->youtube_thumbnail === null && $level->youtube_id !== null) {
                AttachYoutubeThumbnail::dispatch($level);
            }
        }

        return $levels;
    }

    /**
     * Loads promoted and published episodes from the database
     */
    protected function loadEpisodes()
    {
        return Episode::isPublished()
            ->isPromoted()
            ->with('game')
            ->with(['levels' => function ($query) {
                $query->isPublished()->orderBy('sort_order', 'asc');
            }])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}

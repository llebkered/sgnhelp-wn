<?php

namespace DerekBell\Games\Helpers;

use Cache;

/**
 * Cache helper class for managing cache keys and operations
 */
class CacheHelper
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION_GAMES = 60;      // 1 hour for games list
    const CACHE_DURATION_EPISODES = 30;   // 30 minutes for episodes
    const CACHE_DURATION_LEVELS = 15;     // 15 minutes for levels
    const FRONTPAGE_LEVELS_REGISTRY_KEY = 'levels.frontpage.counts'; // Registry of actively cached front page counts

    /**
     * Flag to prevent recursive cache clearing
     */
    private static $clearing = false;

    /**
     * Get cache key for published games list
     */
    public static function getGamesListKey()
    {
        return 'games.published.list';
    }

    /**
     * Get cache key for episodes by game
     */
    public static function getEpisodesListKey($gameId)
    {
        return "episodes.game.{$gameId}";
    }

    /**
     * Get cache key for front page levels
     */
    public static function getFrontPageLevelsKey($count)
    {
        return "levels.frontpage.{$count}";
    }

    /**
     * Register a front page level count as actively cached.
     * Called by FrontPageLevels at write time so clearLevelsCaches() knows which per-count keys exist.
     */
    public static function registerFrontPageCount($count)
    {
        $counts = Cache::get(self::FRONTPAGE_LEVELS_REGISTRY_KEY, []);
        if (!in_array($count, $counts, true)) {
            $counts[] = $count;
            Cache::put(self::FRONTPAGE_LEVELS_REGISTRY_KEY, $counts, self::CACHE_DURATION_LEVELS);
        }
    }

    /**
     * Get cache key for episode levels
     */
    public static function getEpisodeLevelsKey($episodeId)
    {
        return "levels.episode.{$episodeId}";
    }

    /**
     * Clear all game-related caches
     */
    public static function clearGamesCaches()
    {
        if (self::$clearing) {
            return;
        }

        self::$clearing = true;

        try {
            Cache::forget(self::getGamesListKey());
        } finally {
            self::$clearing = false;
        }
    }

    /**
     * Clear episode caches for a specific game
     */
    public static function clearEpisodesCaches($gameId = null)
    {
        if (self::$clearing) {
            return;
        }

        self::$clearing = true;

        try {
            if ($gameId) {
                Cache::forget(self::getEpisodesListKey($gameId));
            }
            // Clear games cache as well since episodes affect navigation
            Cache::forget(self::getGamesListKey());
        } finally {
            self::$clearing = false;
        }
    }

    /**
     * Clear level caches
     */
    public static function clearLevelsCaches($episodeId = null)
    {
        if (self::$clearing) {
            return;
        }

        self::$clearing = true;

        try {
            // Clear front page caches by reading the registry of active counts
            $counts = Cache::get(self::FRONTPAGE_LEVELS_REGISTRY_KEY, []);
            foreach ($counts as $count) {
                Cache::forget(self::getFrontPageLevelsKey($count));
            }
            Cache::forget(self::FRONTPAGE_LEVELS_REGISTRY_KEY);

            if ($episodeId) {
                Cache::forget(self::getEpisodeLevelsKey($episodeId));
            }
        } finally {
            self::$clearing = false;
        }
    }

    /**
     * Clear all caches
     */
    public static function clearAllCaches()
    {
        if (self::$clearing) {
            return;
        }

        self::$clearing = true;

        try {
            Cache::forget(self::getGamesListKey());

            // Clear front page caches by reading the registry of active counts
            $counts = Cache::get(self::FRONTPAGE_LEVELS_REGISTRY_KEY, []);
            foreach ($counts as $count) {
                Cache::forget(self::getFrontPageLevelsKey($count));
            }
            Cache::forget(self::FRONTPAGE_LEVELS_REGISTRY_KEY);

            // If using cache tags (Redis/Memcached)
            try {
                Cache::tags(['games', 'episodes', 'levels'])->flush();
            } catch (\Exception $e) {
                // Cache tags not supported, silently continue
            }
        } finally {
            self::$clearing = false;
        }
    }
}

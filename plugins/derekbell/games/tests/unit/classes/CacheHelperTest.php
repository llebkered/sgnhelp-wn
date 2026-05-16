<?php

namespace DerekBell\Games\Tests\Unit\Classes;

use Cache;
use DerekBell\Games\Helpers\CacheHelper;
use System\Tests\Bootstrap\PluginTestCase;

class CacheHelperTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testGetGamesListKey()
    {
        $key = CacheHelper::getGamesListKey();
        $this->assertEquals('games.published.list', $key);
    }

    public function testGetEpisodesListKey()
    {
        $key = CacheHelper::getEpisodesListKey(123);
        $this->assertEquals('episodes.game.123', $key);
    }

    public function testGetFrontPageLevelsKey()
    {
        $key = CacheHelper::getFrontPageLevelsKey(12);
        $this->assertEquals('levels.frontpage.12', $key);
    }

    public function testRegisterFrontPageCount()
    {
        // Registry starts empty
        $this->assertFalse(Cache::has('levels.frontpage.counts'));

        // Registering a count stores it in the registry
        CacheHelper::registerFrontPageCount(12);
        $counts = Cache::get('levels.frontpage.counts');
        $this->assertContains(12, $counts);

        // Registering the same count again does not duplicate it
        CacheHelper::registerFrontPageCount(12);
        $counts = Cache::get('levels.frontpage.counts');
        $this->assertCount(1, $counts);

        // Registering a different count adds it
        CacheHelper::registerFrontPageCount(20);
        $counts = Cache::get('levels.frontpage.counts');
        $this->assertCount(2, $counts);
        $this->assertContains(20, $counts);
    }

    public function testGetEpisodeLevelsKey()
    {
        $key = CacheHelper::getEpisodeLevelsKey(456);
        $this->assertEquals('levels.episode.456', $key);
    }

    public function testClearGamesCaches()
    {
        // Set a cache value
        Cache::put('games.published.list', ['test' => 'data'], 60);
        $this->assertTrue(Cache::has('games.published.list'));

        // Clear caches
        CacheHelper::clearGamesCaches();

        // Verify it's cleared
        $this->assertFalse(Cache::has('games.published.list'));
    }

    public function testClearEpisodesCaches()
    {
        // Set cache values
        Cache::put('episodes.game.123', ['test' => 'data'], 60);
        Cache::put('episodes.game.456', ['test' => 'data'], 60);

        // Clear caches for specific game
        CacheHelper::clearEpisodesCaches(123);

        // Verify only game 123 is cleared
        $this->assertFalse(Cache::has('episodes.game.123'));
        $this->assertTrue(Cache::has('episodes.game.456'));
    }

    public function testClearLevelsCaches()
    {
        // Populate the registry so clearLevelsCaches knows which front page keys to invalidate
        Cache::put('levels.frontpage.counts', [12, 20], 60);
        Cache::put('levels.frontpage.12', ['test' => 'data'], 60);
        Cache::put('levels.frontpage.20', ['test' => 'data'], 60);
        Cache::put('levels.episode.789', ['test' => 'data'], 60);

        // Clear caches
        CacheHelper::clearLevelsCaches(789);

        // Verify all registered front page keys, the registry, and the episode key are cleared
        $this->assertFalse(Cache::has('levels.frontpage.12'));
        $this->assertFalse(Cache::has('levels.frontpage.20'));
        $this->assertFalse(Cache::has('levels.frontpage.counts'));
        $this->assertFalse(Cache::has('levels.episode.789'));
    }

    public function testClearAllCaches()
    {
        // Set multiple cache values, including the front page registry
        Cache::put('games.published.list', ['test' => 'data'], 60);
        Cache::put('episodes.game.123', ['test' => 'data'], 60);
        Cache::put('levels.frontpage.counts', [12], 60);
        Cache::put('levels.frontpage.12', ['test' => 'data'], 60);

        // Clear all caches
        CacheHelper::clearAllCaches();

        // Verify all are cleared
        $this->assertFalse(Cache::has('games.published.list'));
        $this->assertFalse(Cache::has('episodes.game.123'));
        $this->assertFalse(Cache::has('levels.frontpage.12'));
        $this->assertFalse(Cache::has('levels.frontpage.counts'));
    }

    public function testRecursionProtection()
    {
        // This should not cause infinite recursion
        CacheHelper::clearAllCaches();
        CacheHelper::clearAllCaches();

        $this->assertTrue(true); // If we get here, no recursion occurred
    }
}

<?php

namespace DerekBell\Games\Tests\Feature;

use Cache;
use DerekBell\Games\Helpers\CacheHelper;
use System\Tests\Bootstrap\PluginTestCase;

class ConsoleCommandTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testClearAllCaches()
    {
        // Set up some cache values
        Cache::put('games.published.list', ['data'], 60);
        Cache::put('episodes.game.123', ['data'], 60);
        Cache::put('levels.frontpage.12', ['data'], 60);

        // Run the command
        $this->artisan('games:clear-cache', ['--all' => true])
            ->assertExitCode(0);

        // Verify caches are cleared
        $this->assertFalse(Cache::has('games.published.list'));
        $this->assertFalse(Cache::has('episodes.game.123'));
        $this->assertFalse(Cache::has('levels.frontpage.12'));
    }

    public function testClearGamesCaches()
    {
        Cache::put('games.published.list', ['data'], 60);
        Cache::put('episodes.game.123', ['data'], 60);

        $this->artisan('games:clear-cache', ['--games' => true])
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('games.published.list'));
        // Other caches should remain
        $this->assertTrue(Cache::has('episodes.game.123'));
    }

    public function testClearEpisodesCaches()
    {
        Cache::put('episodes.game.123', ['data'], 60);
        Cache::put('levels.frontpage.12', ['data'], 60);

        $this->artisan('games:clear-cache', ['--episodes' => true])
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('episodes.game.123'));
        // Other caches should remain
        $this->assertTrue(Cache::has('levels.frontpage.12'));
    }

    public function testClearLevelsCaches()
    {
        Cache::put('levels.frontpage.12', ['data'], 60);
        Cache::put('levels.episode.456', ['data'], 60);
        Cache::put('games.published.list', ['data'], 60);

        $this->artisan('games:clear-cache', ['--levels' => true])
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('levels.frontpage.12'));
        $this->assertFalse(Cache::has('levels.episode.456'));
        // Other caches should remain
        $this->assertTrue(Cache::has('games.published.list'));
    }

    public function testCommandWithNoOptions()
    {
        // Command should work with no options (clear all by default)
        Cache::put('games.published.list', ['data'], 60);

        $this->artisan('games:clear-cache')
            ->assertExitCode(0);
    }
}

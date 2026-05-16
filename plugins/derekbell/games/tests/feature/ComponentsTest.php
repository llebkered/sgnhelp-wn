<?php

namespace DerekBell\Games\Tests\Feature;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Components\GamesMenu;
use DerekBell\Games\Components\DisplayLevels;
use DerekBell\Games\Components\FrontPageLevels;
use System\Tests\Bootstrap\PluginTestCase;

class ComponentsTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');
    }

    public function testGamesMenuComponent()
    {
        // Create test games
        Game::create(['title' => 'Game 1', 'is_published' => true]);
        Game::create(['title' => 'Game 2', 'is_published' => true]);
        Game::create(['title' => 'Game 3', 'is_published' => false]);

        $component = new GamesMenu();
        $component->onRun();

        $games = $component->page['games'];

        $this->assertCount(2, $games);
        $this->assertEquals('Game 1', $games[0]->title);
    }

    public function testFrontPageLevelsComponent()
    {
        $game = Game::create(['title' => 'Test Game', 'is_published' => true]);
        $episode = Episode::create([
            'game_id' => $game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        // Create multiple levels
        for ($i = 1; $i <= 15; $i++) {
            Level::create([
                'episode_id' => $episode->id,
                'title' => "Level {$i}",
                'level_number' => $i,
                'episode_number' => 1,
                'is_published' => true,
                'is_promoted' => $i <= 3, // First 3 are promoted
            ]);
        }

        $component = new FrontPageLevels();
        $component->levelCount = 12;
        $component->onRun();

        $levels = $component->page['levels'];

        $this->assertCount(12, $levels);
    }

    public function testFrontPageLevelsPromotedOnly()
    {
        $game = Game::create(['title' => 'Test Game', 'is_published' => true]);
        $episode = Episode::create([
            'game_id' => $game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        // Create levels, only some promoted
        Level::create([
            'episode_id' => $episode->id,
            'title' => 'Promoted Level',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
            'is_promoted' => true,
        ]);

        Level::create([
            'episode_id' => $episode->id,
            'title' => 'Regular Level',
            'level_number' => 2,
            'episode_number' => 1,
            'is_published' => true,
            'is_promoted' => false,
        ]);

        $component = new FrontPageLevels();
        $component->levelCount = 0; // 0 = promoted only
        $component->onRun();

        $levels = $component->page['levels'];

        $this->assertCount(1, $levels);
        $this->assertEquals('Promoted Level', $levels->first()->title);
    }

    public function testDisplayLevelsComponent()
    {
        $game = Game::create(['title' => 'Test Game', 'slug' => 'test-game', 'is_published' => true]);
        $episode = Episode::create([
            'game_id' => $game->id,
            'title' => 'Episode 1',
            'slug' => 'episode-1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Level::create([
                'episode_id' => $episode->id,
                'title' => "Level {$i}",
                'level_number' => $i,
                'episode_number' => 1,
                'is_published' => true,
            ]);
        }

        $component = new DisplayLevels();

        // Mock URL parameters
        $this->app['url']->setRouteResolver(function () use ($game, $episode) {
            return [
                'parameters' => [
                    'game' => $game->slug,
                    'episode' => $episode->slug,
                ],
            ];
        });

        $component->onRun();

        $levels = $component->page['levels'];

        $this->assertCount(5, $levels);
    }

    public function testComponentsUseEagerLoading()
    {
        $game = Game::create(['title' => 'Test Game', 'is_published' => true]);
        $episode = Episode::create([
            'game_id' => $game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        Level::create([
            'episode_id' => $episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $component = new FrontPageLevels();
        $component->levelCount = 12;
        $component->onRun();

        $levels = $component->page['levels'];
        $firstLevel = $levels->first();

        // Check that relationships are loaded
        $this->assertTrue($firstLevel->relationLoaded('episode'));
    }
}

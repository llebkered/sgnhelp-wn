<?php

namespace DerekBell\Games\Tests\Unit\Models;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use System\Tests\Bootstrap\PluginTestCase;

class GameTest extends PluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');
    }

    public function testGameCreation()
    {
        $game = Game::create([
            'title' => 'Test Game',
            'excerpt' => 'Test excerpt',
            'description' => 'Test description',
            'is_published' => true,
            'is_promoted' => false,
            'sort_order' => 1,
        ]);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertEquals('Test Game', $game->title);
        $this->assertTrue($game->is_published);
        $this->assertNotNull($game->slug);
    }

    public function testSlugAutoGeneration()
    {
        $game = Game::create([
            'title' => 'Candy Crush Saga',
            'is_published' => true,
        ]);

        $this->assertEquals('candy-crush-saga', $game->slug);
    }

    public function testUniqueSlugGeneration()
    {
        Game::create(['title' => 'Duplicate Game', 'is_published' => true]);
        $game2 = Game::create(['title' => 'Duplicate Game', 'is_published' => true]);

        $this->assertNotEquals('duplicate-game', $game2->slug);
        $this->assertStringStartsWith('duplicate-game-', $game2->slug);
    }

    public function testValidationRules()
    {
        $this->expectException(\ValidationException::class);

        Game::create([
            'title' => '', // Required field
            'is_published' => true,
        ]);
    }

    public function testEpisodesRelationship()
    {
        $game = Game::create([
            'title' => 'Test Game',
            'is_published' => true,
        ]);

        $episode = Episode::create([
            'game_id' => $game->id,
            'title' => 'Test Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Episode::class, $game->episodes->first());
        $this->assertEquals($episode->id, $game->episodes->first()->id);
    }

    public function testIsPublishedScope()
    {
        Game::create(['title' => 'Published Game', 'is_published' => true]);
        Game::create(['title' => 'Unpublished Game', 'is_published' => false]);

        $publishedGames = Game::isPublished()->get();

        $this->assertCount(1, $publishedGames);
        $this->assertEquals('Published Game', $publishedGames->first()->title);
    }

    public function testIsPromotedScope()
    {
        Game::create(['title' => 'Promoted Game', 'is_published' => true, 'is_promoted' => true]);
        Game::create(['title' => 'Regular Game', 'is_published' => true, 'is_promoted' => false]);

        $promotedGames = Game::isPromoted()->get();

        $this->assertCount(1, $promotedGames);
        $this->assertEquals('Promoted Game', $promotedGames->first()->title);
    }

    public function testSoftDelete()
    {
        $game = Game::create([
            'title' => 'Test Game',
            'is_published' => true,
        ]);

        $game->delete();

        $this->assertNotNull($game->deleted_at);
        $this->assertCount(0, Game::all());
        $this->assertCount(1, Game::withTrashed()->get());
    }

    public function testTimestamps()
    {
        $game = Game::create([
            'title' => 'Test Game',
            'is_published' => true,
        ]);

        $this->assertNotNull($game->created_at);
        $this->assertNotNull($game->updated_at);
        $this->assertEquals($game->created_at->timestamp, $game->updated_at->timestamp);

        sleep(1);
        $game->title = 'Updated Game';
        $game->save();

        $this->assertNotEquals($game->created_at->timestamp, $game->updated_at->timestamp);
    }
}

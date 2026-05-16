<?php

namespace DerekBell\Games\Tests\Unit\Models;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use System\Tests\Bootstrap\PluginTestCase;

class LevelTest extends PluginTestCase
{
    protected $game;
    protected $episode;

    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');

        $this->game = Game::create([
            'title' => 'Test Game',
            'is_published' => true,
        ]);

        $this->episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Test Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);
    }

    public function testLevelCreation()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'youtube_id' => 'dQw4w9WgXcQ',
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Level::class, $level);
        $this->assertEquals('Level 1', $level->title);
        $this->assertEquals('dQw4w9WgXcQ', $level->youtube_id);
    }

    public function testSlugAutoGeneration()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Candy Crush Saga Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertEquals('candy-crush-saga-level-1', $level->slug);
    }

    public function testValidationRules()
    {
        $this->expectException(\ValidationException::class);

        Level::create([
            'episode_id' => $this->episode->id,
            'title' => '', // Required
            'level_number' => 1,
        ]);
    }

    public function testYoutubeIdValidation()
    {
        $this->expectException(\ValidationException::class);

        Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Invalid Level',
            'level_number' => 1,
            'episode_number' => 1,
            'youtube_id' => 'invalid', // Must be 11 characters
            'is_published' => true,
        ]);
    }

    public function testEpisodeRelationship()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Episode::class, $level->episode);
        $this->assertEquals($this->episode->id, $level->episode->id);
    }

    public function testGameAccessor()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        // Load the relationship
        $level->load('episode.game');

        $this->assertInstanceOf(Game::class, $level->game);
        $this->assertEquals($this->game->id, $level->game->id);
    }

    public function testIsPublishedScope()
    {
        Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Published Level',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Unpublished Level',
            'level_number' => 2,
            'episode_number' => 1,
            'is_published' => false,
        ]);

        $publishedLevels = Level::isPublished()->get();

        $this->assertCount(1, $publishedLevels);
        $this->assertEquals('Published Level', $publishedLevels->first()->title);
    }

    public function testLevelNumberValidation()
    {
        $this->expectException(\ValidationException::class);

        Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Invalid Level',
            'level_number' => 0, // Must be at least 1
            'episode_number' => 1,
            'is_published' => true,
        ]);
    }

    public function testSoftDelete()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Test Level',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $level->delete();

        $this->assertNotNull($level->deleted_at);
        $this->assertCount(0, Level::all());
        $this->assertCount(1, Level::withTrashed()->get());
    }

    public function testYoutubeUrlAccessor()
    {
        $level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'youtube_id' => 'dQw4w9WgXcQ',
            'is_published' => true,
        ]);

        $this->assertEquals('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $level->youtube_url);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $level->youtube_embed_url);
    }
}

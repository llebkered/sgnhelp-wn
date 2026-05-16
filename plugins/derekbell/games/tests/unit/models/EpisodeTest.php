<?php

namespace DerekBell\Games\Tests\Unit\Models;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use System\Tests\Bootstrap\PluginTestCase;

class EpisodeTest extends PluginTestCase
{
    protected $game;

    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');

        $this->game = Game::create([
            'title' => 'Test Game',
            'is_published' => true,
        ]);
    }

    public function testEpisodeCreation()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'start_level' => 1,
            'end_level' => 10,
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Episode::class, $episode);
        $this->assertEquals('Episode 1', $episode->title);
        $this->assertEquals($this->game->id, $episode->game_id);
    }

    public function testSlugAutoGeneration()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Test Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertEquals('test-episode', $episode->slug);
    }

    public function testValidationRules()
    {
        $this->expectException(\ValidationException::class);

        Episode::create([
            'game_id' => $this->game->id,
            'title' => '', // Required
            'episode_number' => 1,
        ]);
    }

    public function testGameRelationship()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Game::class, $episode->game);
        $this->assertEquals($this->game->id, $episode->game->id);
    }

    public function testLevelsRelationship()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Episode 1',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $level = Level::create([
            'episode_id' => $episode->id,
            'title' => 'Level 1',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->assertInstanceOf(Level::class, $episode->levels->first());
        $this->assertEquals($level->id, $episode->levels->first()->id);
    }

    public function testIsPublishedScope()
    {
        Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Published Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Unpublished Episode',
            'episode_number' => 2,
            'is_published' => false,
        ]);

        $publishedEpisodes = Episode::isPublished()->get();

        $this->assertCount(1, $publishedEpisodes);
        $this->assertEquals('Published Episode', $publishedEpisodes->first()->title);
    }

    public function testEpisodeNumberValidation()
    {
        $this->expectException(\ValidationException::class);

        Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Invalid Episode',
            'episode_number' => 0, // Must be at least 1
            'is_published' => true,
        ]);
    }

    public function testSoftDelete()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Test Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $episode->delete();

        $this->assertNotNull($episode->deleted_at);
        $this->assertCount(0, Episode::all());
        $this->assertCount(1, Episode::withTrashed()->get());
    }

    public function testSoftDeleteCascadesToLevels()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Cascade Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $level = Level::create([
            'episode_id' => $episode->id,
            'title' => 'Cascade Level',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $episode->delete();

        $this->assertTrue($episode->trashed());
        $this->assertCount(0, Level::all());
        $this->assertCount(1, Level::withTrashed()->get());
        $this->assertTrue(Level::withTrashed()->find($level->id)->trashed());
    }

    public function testRestoreRestoresSoftDeletedLevels()
    {
        $episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Restored Episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $level = Level::create([
            'episode_id' => $episode->id,
            'title' => 'Restored Level',
            'level_number' => 1,
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $episode->delete();
        $episode->restore();

        $this->assertFalse($episode->trashed());
        $this->assertCount(1, Level::all());
        $this->assertFalse(Level::withTrashed()->find($level->id)->trashed());
    }
}

<?php

namespace DerekBell\Games\Tests\Feature;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use System\Tests\Bootstrap\PluginTestCase;

class ApiControllerTest extends PluginTestCase
{
    protected $game;
    protected $episode;
    protected $level;

    public function setUp(): void
    {
        parent::setUp();
        $this->runPluginRefreshCommand('DerekBell.Games');

        // Create test data
        $this->game = Game::create([
            'title' => 'Test Game',
            'slug' => 'test-game',
            'excerpt' => 'Test excerpt',
            'is_published' => true,
        ]);

        $this->episode = Episode::create([
            'game_id' => $this->game->id,
            'title' => 'Test Episode',
            'slug' => 'test-episode',
            'episode_number' => 1,
            'is_published' => true,
        ]);

        $this->level = Level::create([
            'episode_id' => $this->episode->id,
            'title' => 'Test Level',
            'slug' => 'test-level',
            'level_number' => 1,
            'episode_number' => 1,
            'youtube_id' => 'dQw4w9WgXcQ',
            'is_published' => true,
        ]);
    }

    public function testGamesEndpoint()
    {
        $response = $this->get('/api/derekbell/games');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Game', $data['data'][0]['title']);
    }

    public function testGameEndpoint()
    {
        $response = $this->get('/api/derekbell/games/test-game');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('Test Game', $data['data']['title']);
        $this->assertArrayHasKey('episodes', $data['data']);
    }

    public function testGameEndpointNotFound()
    {
        $response = $this->get('/api/derekbell/games/non-existent');

        $response->assertStatus(404);
        $data = $response->json();

        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('message', $data);
    }

    public function testEpisodesEndpoint()
    {
        $response = $this->get('/api/derekbell/games/test-game/episodes');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Episode', $data['data'][0]['title']);
    }

    public function testEpisodeEndpoint()
    {
        $response = $this->get('/api/derekbell/games/test-game/episodes/test-episode');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('Test Episode', $data['data']['title']);
        $this->assertArrayHasKey('levels', $data['data']);
    }

    public function testLevelsEndpoint()
    {
        $response = $this->get('/api/derekbell/games/test-game/episodes/test-episode/levels');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Level', $data['data'][0]['title']);
    }

    public function testLevelEndpoint()
    {
        $response = $this->get('/api/derekbell/games/test-game/episodes/test-episode/levels/test-level');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals('Test Level', $data['data']['title']);
        $this->assertEquals('dQw4w9WgXcQ', $data['data']['youtube_id']);
        $this->assertArrayHasKey('youtube_url', $data['data']);
        $this->assertArrayHasKey('youtube_embed_url', $data['data']);
    }

    public function testOnlyPublishedContentReturned()
    {
        // Create unpublished game
        Game::create([
            'title' => 'Unpublished Game',
            'slug' => 'unpublished-game',
            'is_published' => false,
        ]);

        $response = $this->get('/api/derekbell/games');
        $data = $response->json();

        // Should only return the published game
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Game', $data['data'][0]['title']);
    }

    public function testResponseStructure()
    {
        $response = $this->get('/api/derekbell/games');
        $data = $response->json();

        // Check response structure
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertIsArray($data['data']);
        $this->assertIsInt($data['count']);
    }
}

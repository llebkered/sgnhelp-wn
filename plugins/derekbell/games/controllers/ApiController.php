<?php

namespace DerekBell\Games\Controllers;

use DerekBell\Games\Models\Game;
use DerekBell\Games\Models\Episode;
use DerekBell\Games\Models\Level;
use Illuminate\Routing\Controller;
use Response;

class ApiController extends Controller
{
    /**
     * Get all published games
     * GET /api/derekbell/games
     */
    public function games()
    {
        $games = Game::isPublished()
            ->with('logo')
            ->orderBy('is_promoted', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('title', 'asc')
            ->get()
            ->map(fn($game) => $this->formatGame($game));

        return Response::json([
            'success' => true,
            'data' => $games,
            'count' => $games->count()
        ]);
    }

    /**
     * Get a single game by slug
     * GET /api/derekbell/games/{slug}
     */
    public function game($slug)
    {
        $game = Game::where('slug', $slug)
            ->isPublished()
            ->with(['logo', 'episodes' => function($query) {
                $query->isPublished()
                    ->orderBy('episode_number', 'asc');
            }])
            ->first();

        if (!$game) {
            return Response::json([
                'success' => false,
                'message' => 'Game not found'
            ], 404);
        }

        $data = $this->formatGame($game);
        $data['episodes'] = $game->episodes->map(function ($episode) {
            return [
                'id'             => $episode->id,
                'title'          => $episode->title,
                'slug'           => $episode->slug,
                'episode_number' => $episode->episode_number,
                'start_level'    => $episode->start_level,
                'end_level'      => $episode->end_level,
                'excerpt'        => $episode->excerpt,
                'is_promoted'    => $episode->is_promoted,
            ];
        });

        return Response::json(['success' => true, 'data' => $data]);
    }

    /**
     * Get episodes for a game
     * GET /api/derekbell/games/{gameSlug}/episodes
     */
    public function episodes($gameSlug)
    {
        $game = Game::where('slug', $gameSlug)->isPublished()->first();

        if (!$game) {
            return Response::json([
                'success' => false,
                'message' => 'Game not found'
            ], 404);
        }

        $episodes = Episode::where('game_id', $game->id)
            ->isPublished()
            ->orderBy('episode_number', 'asc')
            ->get()
            ->map(fn($episode) => $this->formatEpisode($episode));

        return Response::json([
            'success' => true,
            'data' => $episodes,
            'count' => $episodes->count()
        ]);
    }

    /**
     * Get a single episode
     * GET /api/derekbell/games/{gameSlug}/episodes/{episodeSlug}
     */
    public function episode($gameSlug, $episodeSlug)
    {
        $game = Game::where('slug', $gameSlug)->isPublished()->first();

        if (!$game) {
            return Response::json([
                'success' => false,
                'message' => 'Game not found'
            ], 404);
        }

        $episode = Episode::where('game_id', $game->id)
            ->where('slug', $episodeSlug)
            ->isPublished()
            ->with(['levels' => function($query) {
                $query->isPublished()
                    ->with('youtube_thumbnail')
                    ->orderBy('level_number', 'asc');
            }])
            ->first();

        if (!$episode) {
            return Response::json([
                'success' => false,
                'message' => 'Episode not found'
            ], 404);
        }

        $data = $this->formatEpisode($episode);
        $data['levels'] = $episode->levels->map(function ($level) {
            return [
                'id'            => $level->id,
                'title'         => $level->title,
                'slug'          => $level->slug,
                'level_number'  => $level->level_number,
                'youtube_id'    => $level->youtube_id,
                'excerpt'       => $level->excerpt,
                'is_promoted'   => $level->is_promoted,
                'thumbnail_url' => $level->youtube_thumbnail ? $level->youtube_thumbnail->getPath() : null,
            ];
        });

        return Response::json(['success' => true, 'data' => $data]);
    }

    /**
     * Get levels for an episode
     * GET /api/derekbell/games/{gameSlug}/episodes/{episodeSlug}/levels
     */
    public function levels($gameSlug, $episodeSlug)
    {
        $game = Game::where('slug', $gameSlug)->isPublished()->first();

        if (!$game) {
            return Response::json([
                'success' => false,
                'message' => 'Game not found'
            ], 404);
        }

        $episode = Episode::where('game_id', $game->id)
            ->where('slug', $episodeSlug)
            ->isPublished()
            ->first();

        if (!$episode) {
            return Response::json([
                'success' => false,
                'message' => 'Episode not found'
            ], 404);
        }

        $levels = Level::where('episode_id', $episode->id)
            ->isPublished()
            ->with('youtube_thumbnail')
            ->orderBy('level_number', 'asc')
            ->get()
            ->map(fn($level) => $this->formatLevel($level));

        return Response::json([
            'success' => true,
            'data' => $levels,
            'count' => $levels->count()
        ]);
    }

    /**
     * Get a single level
     * GET /api/derekbell/games/{gameSlug}/episodes/{episodeSlug}/levels/{levelSlug}
     */
    public function level($gameSlug, $episodeSlug, $levelSlug)
    {
        $game = Game::where('slug', $gameSlug)->isPublished()->first();

        if (!$game) {
            return Response::json([
                'success' => false,
                'message' => 'Game not found'
            ], 404);
        }

        $episode = Episode::where('game_id', $game->id)
            ->where('slug', $episodeSlug)
            ->isPublished()
            ->first();

        if (!$episode) {
            return Response::json([
                'success' => false,
                'message' => 'Episode not found'
            ], 404);
        }

        $level = Level::where('episode_id', $episode->id)
            ->where('slug', $levelSlug)
            ->isPublished()
            ->with('youtube_thumbnail')
            ->first();

        if (!$level) {
            return Response::json([
                'success' => false,
                'message' => 'Level not found'
            ], 404);
        }

        return Response::json(['success' => true, 'data' => $this->formatLevel($level)]);
    }

    // -------------------------------------------------------------------------
    // Private formatters
    // -------------------------------------------------------------------------

    private function formatGame(Game $game): array
    {
        return [
            'id'          => $game->id,
            'title'       => $game->title,
            'slug'        => $game->slug,
            'excerpt'     => $game->excerpt,
            'description' => $game->description,
            'is_promoted' => $game->is_promoted,
            'sort_order'  => $game->sort_order,
            'logo_url'    => $game->logo ? $game->logo->getPath() : null,
            'created_at'  => $game->created_at->toIso8601String(),
            'updated_at'  => $game->updated_at->toIso8601String(),
        ];
    }

    private function formatEpisode(Episode $episode): array
    {
        return [
            'id'             => $episode->id,
            'title'          => $episode->title,
            'slug'           => $episode->slug,
            'episode_number' => $episode->episode_number,
            'start_level'    => $episode->start_level,
            'end_level'      => $episode->end_level,
            'excerpt'        => $episode->excerpt,
            'description'    => $episode->description,
            'is_promoted'    => $episode->is_promoted,
            'sort_order'     => $episode->sort_order,
            'created_at'     => $episode->created_at->toIso8601String(),
            'updated_at'     => $episode->updated_at->toIso8601String(),
        ];
    }

    private function formatLevel(Level $level): array
    {
        return [
            'id'               => $level->id,
            'title'            => $level->title,
            'slug'             => $level->slug,
            'level_number'     => $level->level_number,
            'episode_number'   => $level->episode_number,
            'youtube_id'       => $level->youtube_id,
            'excerpt'          => $level->excerpt,
            'description'      => $level->description,
            'is_promoted'      => $level->is_promoted,
            'sort_order'       => $level->sort_order,
            'thumbnail_url'    => $level->youtube_thumbnail ? $level->youtube_thumbnail->getPath() : null,
            'youtube_url'      => $level->youtube_id ? "https://www.youtube.com/watch?v={$level->youtube_id}" : null,
            'youtube_embed_url' => $level->youtube_id ? "https://www.youtube.com/embed/{$level->youtube_id}" : null,
            'created_at'       => $level->created_at->toIso8601String(),
            'updated_at'       => $level->updated_at->toIso8601String(),
        ];
    }
}

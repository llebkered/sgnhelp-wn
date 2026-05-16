<?php namespace DerekBell\Games\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use DerekBell\Games\Models\Game;
use Flash;

class GameController extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        \Backend\Behaviors\ReorderController::class,
        ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['derekbell.games.access_games'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('DerekBell.Games', 'main-menu-item');
    }

    public function duplicates()
    {
        [$sortColumn, $sortDirection] = $this->getDuplicateSort(['id', 'title', 'slug', 'reasons', 'episodes_count', 'is_published'], 'title');
        $this->pageTitle = 'Duplicate Games';
        $this->vars['sortColumn'] = $sortColumn;
        $this->vars['sortDirection'] = $sortDirection;
        $this->vars['duplicateGames'] = $this->findDuplicateGames($sortColumn, $sortDirection);
    }

    public function onDeleteDuplicates()
    {
        $gameIds = array_filter((array) post('game_ids', []));

        if (empty($gameIds)) {
            Flash::error('Select at least one duplicate game to delete.');
            return redirect()->back();
        }

        $deletedCount = 0;

        foreach (Game::whereIn('id', $gameIds)->get() as $game) {
            $game->delete();
            $deletedCount++;
        }

        Flash::success($deletedCount . ' duplicate game(s) deleted.');

        return redirect('backend/derekbell/games/gamecontroller/duplicates');
    }

    protected function findDuplicateGames($sortColumn = 'title', $sortDirection = 'asc')
    {
        $games = Game::withCount('episodes')->orderBy('title', 'asc')->get();
        $titleCounts = [];
        $slugCounts = [];

        foreach ($games as $game) {
            $normalizedTitle = $this->normalizeDuplicateValue($game->title);
            $normalizedSlug = $this->normalizeDuplicateValue($game->slug);

            if ($normalizedTitle !== '') {
                $titleCounts[$normalizedTitle] = ($titleCounts[$normalizedTitle] ?? 0) + 1;
            }

            if ($normalizedSlug !== '') {
                $slugCounts[$normalizedSlug] = ($slugCounts[$normalizedSlug] ?? 0) + 1;
            }
        }

        $duplicates = $games->map(function ($game) use ($titleCounts, $slugCounts) {
            $normalizedTitle = $this->normalizeDuplicateValue($game->title);
            $normalizedSlug = $this->normalizeDuplicateValue($game->slug);
            $reasons = [];

            if ($normalizedTitle !== '' && ($titleCounts[$normalizedTitle] ?? 0) > 1) {
                $reasons[] = 'same title (' . $titleCounts[$normalizedTitle] . ')';
            }

            if ($normalizedSlug !== '' && ($slugCounts[$normalizedSlug] ?? 0) > 1) {
                $reasons[] = 'same slug (' . $slugCounts[$normalizedSlug] . ')';
            }

            if (empty($reasons)) {
                return null;
            }

            return [
                'game' => $game,
                'reasons' => implode(', ', $reasons),
            ];
        })->filter()->values();

        return $this->sortDuplicateGames($duplicates, $sortColumn, $sortDirection);
    }

    protected function normalizeDuplicateValue($value)
    {
        return mb_strtolower(trim((string) $value));
    }

    protected function getDuplicateSort(array $allowedColumns, $defaultColumn)
    {
        $sortColumn = get('sort', $defaultColumn);
        $sortDirection = strtolower((string) get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!in_array($sortColumn, $allowedColumns, true)) {
            $sortColumn = $defaultColumn;
        }

        return [$sortColumn, $sortDirection];
    }

    protected function sortDuplicateGames($duplicates, $sortColumn, $sortDirection)
    {
        $sorted = $duplicates->sortBy(function ($row) use ($sortColumn) {
            $game = $row['game'];

            switch ($sortColumn) {
                case 'id':
                    return $game->id;
                case 'slug':
                    return $this->normalizeDuplicateValue($game->slug);
                case 'reasons':
                    return $this->normalizeDuplicateValue($row['reasons']);
                case 'episodes_count':
                    return (int) $game->episodes_count;
                case 'is_published':
                    return $game->is_published ? 1 : 0;
                case 'title':
                default:
                    return $this->normalizeDuplicateValue($game->title);
            }
        });

        return $sortDirection === 'desc'
            ? $sorted->reverse()->values()
            : $sorted->values();
    }

    /**
     * Extend the query to sort by title
     */
    public function listExtendQuery($query)
    {
        $query->orderBy('title', 'asc');
    }

    /**
     * AJAX handler to toggle publish state for a game
     */
    public function onToggleGamePublish()
    {
        $gameId = post('id');
        $isPublished = (bool) post('is_published');

        if (!$gameId) {
            throw new \ValidationException(['id' => 'Game ID is required']);
        }

        $game = Game::find($gameId);
        if (!$game) {
            throw new \ApplicationException('Game not found');
        }

        $game->is_published = $isPublished;
        $game->save();

        Flash::success('Game ' . ($isPublished ? 'published' : 'unpublished') . '.');

        return ['success' => true];
    }

    /**
     * AJAX handler to toggle promoted state for a game
     */
    public function onToggleGamePromoted()
    {
        $gameId = post('id');
        $isPromoted = (bool) post('is_promoted');

        if (!$gameId) {
            throw new \ValidationException(['id' => 'Game ID is required']);
        }

        $game = Game::find($gameId);
        if (!$game) {
            throw new \ApplicationException('Game not found');
        }

        $game->is_promoted = $isPromoted;
        $game->save();

        Flash::success('Game ' . ($isPromoted ? 'promoted' : 'unpromoted') . '.');

        return ['success' => true];
    }
}

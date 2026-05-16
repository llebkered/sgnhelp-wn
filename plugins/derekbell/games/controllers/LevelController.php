<?php namespace DerekBell\Games\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use DerekBell\Games\Models\Level;
use Flash;

class LevelController extends Controller
{
    public $implement = [
                'Backend\Behaviors\ListController',
                'Backend\Behaviors\FormController',
                \Backend\Behaviors\ReorderController::class];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $requiredPermissions = ['derekbell.games.access_levels'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('DerekBell.Games', 'main-menu-item', 'side-menu-item2');
        $this->addViewPath(__DIR__ . '/levelcontroller');
    }

    public function onUploadImportFile()
    {
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            return [
                'partial' => '<div class="alert alert-danger">Please upload a valid zip file.</div>'
            ];
        }
        $uploaded = $_FILES['import_file'];
        $unique = uniqid('import_', true) . '.zip';
        $target = storage_path('app/' . $unique);
        if (!move_uploaded_file($uploaded['tmp_name'], $target)) {
            return [
                'partial' => '<div class="alert alert-danger">Failed to save uploaded file.</div>'
            ];
        }
        $partial = '<div class="alert alert-success">File uploaded. Ready to import.<input type="hidden" id="uploaded_import_file" name="uploaded_import_file" value="' . e($unique) . '"></div>';
        return [
            'partial' => $partial
        ];
    }

    public function importexport()
    {
        $this->pageTitle = 'Import/Export Levels';
    }

    public function duplicates()
    {
        [$sortColumn, $sortDirection] = $this->getDuplicateSort(['id', 'title', 'slug', 'youtube_id', 'episode', 'game', 'reasons', 'is_published'], 'title');
        $this->pageTitle = 'Duplicate Levels';
        $this->vars['sortColumn'] = $sortColumn;
        $this->vars['sortDirection'] = $sortDirection;
        $this->vars['duplicateLevels'] = $this->findDuplicateLevels($sortColumn, $sortDirection);
    }

    public function onDeleteDuplicates()
    {
        $levelIds = array_filter((array) post('level_ids', []));

        if (empty($levelIds)) {
            Flash::error('Select at least one duplicate level to delete.');
            return redirect()->back();
        }

        $deletedCount = 0;

        foreach (Level::whereIn('id', $levelIds)->get() as $level) {
            $level->delete();
            $deletedCount++;
        }

        Flash::success($deletedCount . ' duplicate level(s) deleted.');

        return redirect('backend/derekbell/games/levelcontroller/duplicates');
    }

    protected function findDuplicateLevels($sortColumn = 'title', $sortDirection = 'asc')
    {
        $levels = Level::with(['episode.game'])->orderBy('title', 'asc')->get();
        $titleCounts = [];
        $slugCounts = [];
        $youtubeCounts = [];

        foreach ($levels as $level) {
            $normalizedTitle = $this->normalizeDuplicateValue($level->title);
            $normalizedSlug = $this->normalizeDuplicateValue($level->slug);
            $normalizedYoutubeId = $this->normalizeDuplicateValue($level->youtube_id);

            if ($normalizedTitle !== '') {
                $titleCounts[$normalizedTitle] = ($titleCounts[$normalizedTitle] ?? 0) + 1;
            }

            if ($normalizedSlug !== '') {
                $slugCounts[$normalizedSlug] = ($slugCounts[$normalizedSlug] ?? 0) + 1;
            }

            if ($normalizedYoutubeId !== '') {
                $youtubeCounts[$normalizedYoutubeId] = ($youtubeCounts[$normalizedYoutubeId] ?? 0) + 1;
            }
        }

        $duplicates = $levels->map(function ($level) use ($titleCounts, $slugCounts, $youtubeCounts) {
            $normalizedTitle = $this->normalizeDuplicateValue($level->title);
            $normalizedSlug = $this->normalizeDuplicateValue($level->slug);
            $normalizedYoutubeId = $this->normalizeDuplicateValue($level->youtube_id);
            $reasons = [];

            if ($normalizedTitle !== '' && ($titleCounts[$normalizedTitle] ?? 0) > 1) {
                $reasons[] = 'same title (' . $titleCounts[$normalizedTitle] . ')';
            }

            if ($normalizedSlug !== '' && ($slugCounts[$normalizedSlug] ?? 0) > 1) {
                $reasons[] = 'same slug (' . $slugCounts[$normalizedSlug] . ')';
            }

            if ($normalizedYoutubeId !== '' && ($youtubeCounts[$normalizedYoutubeId] ?? 0) > 1) {
                $reasons[] = 'same YouTube ID (' . $youtubeCounts[$normalizedYoutubeId] . ')';
            }

            if (empty($reasons)) {
                return null;
            }

            return [
                'level' => $level,
                'reasons' => implode(', ', $reasons),
            ];
        })->filter()->values();

        return $this->sortDuplicateLevels($duplicates, $sortColumn, $sortDirection);
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

    protected function sortDuplicateLevels($duplicates, $sortColumn, $sortDirection)
    {
        $sorted = $duplicates->sortBy(function ($row) use ($sortColumn) {
            $level = $row['level'];

            switch ($sortColumn) {
                case 'id':
                    return $level->id;
                case 'slug':
                    return $this->normalizeDuplicateValue($level->slug);
                case 'youtube_id':
                    return $this->normalizeDuplicateValue($level->youtube_id);
                case 'episode':
                    return $this->normalizeDuplicateValue(optional($level->episode)->title);
                case 'game':
                    return $this->normalizeDuplicateValue(optional(optional($level->episode)->game)->title);
                case 'reasons':
                    return $this->normalizeDuplicateValue($row['reasons']);
                case 'is_published':
                    return $level->is_published ? 1 : 0;
                case 'title':
                default:
                    return $this->normalizeDuplicateValue($level->title);
            }
        });

        return $sortDirection === 'desc'
            ? $sorted->reverse()->values()
            : $sorted->values();
    }


    public function onExportLevels()
    {
        \Artisan::call('levels:export');
        return redirect('backend/derekbell/games/levelcontroller/downloadExportLevels');
    }

    public function downloadExportLevels()
    {
        $file = storage_path('app/levels-export.zip');
        if (!file_exists($file)) {
            \Flash::error('Export file not found.');
            return redirect()->back();
        }
        return \Response::download($file, 'levels-export.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function onImportLevels()
    {
        $filename = post('uploaded_import_file');
        if (!$filename || !file_exists(storage_path('app/' . $filename))) {
            \Flash::error('No uploaded file found.');
            return redirect()->back();
        }
        $target = storage_path('app/levels-export.zip');
        copy(storage_path('app/' . $filename), $target);
        \Artisan::call('levels:import');
        \Flash::success('Levels import complete.');
        return redirect()->back();
    }

    /**
     * Filter episode options to only show episodes belonging to the selected game
     */
    public function listFilterExtendScopes($filterWidget)
    {
        $filterWidget->bindEvent('filter.extendQuery', function ($query, $scope) use ($filterWidget) {
            if ($scope->scopeName !== 'episode') {
                return;
            }
            $gameScope = $filterWidget->getScope('game');
            if ($gameScope && $gameScope->value) {
                $gameIds = is_array($gameScope->value)
                    ? array_keys($gameScope->value)
                    : [$gameScope->value];
                $query->whereIn('game_id', $gameIds);
            }
        });
    }

    /**
     * AJAX handler to toggle publish state for a level
     */
    public function onToggleLevelPublish()
    {
        $levelId = post('id');
        $isPublished = (bool) post('is_published');

        if (!$levelId) {
            throw new \ValidationException(['id' => 'Level ID is required']);
        }

        $level = Level::find($levelId);
        if (!$level) {
            throw new \ApplicationException('Level not found');
        }

        $level->is_published = $isPublished;
        $level->save();

        Flash::success('Level ' . ($isPublished ? 'published' : 'unpublished') . '.');

        return ['success' => true];
    }

    /**
     * AJAX handler to toggle promoted state for a level
     */
    public function onToggleLevelPromoted()
    {
        $levelId = post('id');
        $isPromoted = (bool) post('is_promoted');

        if (!$levelId) {
            throw new \ValidationException(['id' => 'Level ID is required']);
        }

        $level = Level::find($levelId);
        if (!$level) {
            throw new \ApplicationException('Level not found');
        }

        $level->is_promoted = $isPromoted;
        $level->save();

        Flash::success('Level ' . ($isPromoted ? 'promoted' : 'unpromoted') . '.');

        return ['success' => true];
    }
}

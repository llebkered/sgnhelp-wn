<?php
namespace Derekbell\Games\Controllers;

use Backend\Classes\Controller;
use BackendMenu;
use Flash;
use Illuminate\Support\Facades\Artisan;

class ImportExport extends Controller
{
    public $implement = [];
    public $requiredPermissions = ['derekbell.games.importexport'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Derekbell.Games', 'games', 'importexport');
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
        // Render a success box with hidden field for import
        $partial = '<div class="alert alert-success">File uploaded. Ready to import.<input type="hidden" id="uploaded_import_file" name="uploaded_import_file" value="' . e($unique) . '"></div>';
        return [
            'partial' => $partial
        ];
    }

    public function index()
    {
        $this->pageTitle = 'Import/Export Games';
    }


    public function onExport()
    {
        Artisan::call('games:export');
        // Redirect to download route
        return redirect('backend/derekbell/games/importexport/downloadExport');
    }

    public function downloadExport()
    {
        $file = storage_path('app/games-export.zip');
        if (!file_exists($file)) {
            \Flash::error('Export file not found.');
            return redirect()->back();
        }
        return \Response::download($file, 'games-export.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function onImport()
    {
        $filename = post('uploaded_import_file');
        if (!$filename || !file_exists(storage_path('app/' . $filename))) {
            Flash::error('No uploaded file found.');
            return redirect()->back();
        }
        // Move/copy to games-export.zip for import command
        $target = storage_path('app/games-export.zip');
        copy(storage_path('app/' . $filename), $target);
        \Artisan::call('games:import');
        Flash::success('Import complete.');
        return redirect()->back();
    }
}

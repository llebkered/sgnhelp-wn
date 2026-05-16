<?php
namespace Derekbell\Games\Console;

use Illuminate\Console\Command;
use Derekbell\Games\Models\Level;

class ExportLevels extends Command
{
    protected $name = 'levels:export';
    protected $description = 'Export levels and images to JSON and zip.';

    public function handle()
    {
        $export = [
            'levels' => Level::all()->toArray(),
        ];
        $json = json_encode($export, JSON_PRETTY_PRINT);
        $jsonPath = storage_path('app/levels-export.json');
        file_put_contents($jsonPath, $json);

        // Only export JSON (no images)
        $zipPath = storage_path('app/levels-export.zip');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFile($jsonPath, 'levels-export.json');
            $zip->close();
        }
        $this->info('Levels exported to ' . $zipPath);
    }
}

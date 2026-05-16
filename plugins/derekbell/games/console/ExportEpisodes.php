<?php
namespace Derekbell\Games\Console;

use Illuminate\Console\Command;
use Derekbell\Games\Models\Episode;

class ExportEpisodes extends Command
{
    protected $name = 'episodes:export';
    protected $description = 'Export episodes and images to JSON and zip.';

    public function handle()
    {
        $export = [
            'episodes' => Episode::all()->toArray(),
        ];
        $json = json_encode($export, JSON_PRETTY_PRINT);
        $jsonPath = storage_path('app/episodes-export.json');
        file_put_contents($jsonPath, $json);

        // Only export JSON (no images)
        $zipPath = storage_path('app/episodes-export.zip');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFile($jsonPath, 'episodes-export.json');
            $zip->close();
        }
        $this->info('Episodes exported to ' . $zipPath);
    }
}

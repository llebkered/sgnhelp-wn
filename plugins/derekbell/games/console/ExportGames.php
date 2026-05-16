<?php
namespace Derekbell\Games\Console;

use Illuminate\Console\Command;
use Derekbell\Games\Models\Game;
use Derekbell\Games\Models\Level;
use Derekbell\Games\Models\Episode;
use Storage;

class ExportGames extends Command
{
    protected $name = 'games:export';
    protected $description = 'Export games, levels, episodes, and images to JSON and zip.';

    public function handle()
    {
        $export = [
            'games' => Game::all()->toArray(),
            'levels' => Level::all()->toArray(),
            'episodes' => Episode::all()->toArray(),
        ];
        $json = json_encode($export, JSON_PRETTY_PRINT);
        $jsonPath = storage_path('app/games-export.json');
        file_put_contents($jsonPath, $json);

        // Only export JSON (no images)
        $zipPath = storage_path('app/games-export.zip');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFile($jsonPath, 'games-export.json');
            $zip->close();
        }
        $this->info('Exported to ' . $zipPath);
    }
}

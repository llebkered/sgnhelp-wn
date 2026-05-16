<?php
namespace Derekbell\Games\Console;

use Illuminate\Console\Command;
use Derekbell\Games\Models\Game;
use Derekbell\Games\Models\Level;
use Derekbell\Games\Models\Episode;
use Storage;

class ImportGames extends Command
{
    protected $name = 'games:import';
    protected $description = 'Import games, levels, episodes, and images from JSON and zip.';

    public function handle()
    {
        $zipPath = storage_path('app/games-export.zip');
        $extractPath = storage_path('app/games-import');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $this->error('Could not open zip file.');
            return;
        }
        $jsonPath = $extractPath . '/games-export.json';
        if (!file_exists($jsonPath)) {
            $this->error('JSON file not found in zip.');
            return;
        }
        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            $this->error('Invalid JSON.');
            return;
        }
        // Import data (simple example, consider upserts/clearing first)
        foreach ($data['games'] as $game) {
            Game::updateOrCreate(['id' => $game['id']], $game);
        }
        foreach ($data['levels'] as $level) {
            Level::updateOrCreate(['id' => $level['id']], $level);
        }
        foreach ($data['episodes'] as $episode) {
            Episode::updateOrCreate(['id' => $episode['id']], $episode);
        }
        // Import images
        $imagesPath = $extractPath . '/images';
        $destPath = base_path('plugins/derekbell/games/assets/images');
        if (is_dir($imagesPath)) {
            foreach (glob($imagesPath . '/*') as $file) {
                copy($file, $destPath . '/' . basename($file));
            }
        }
        $this->info('Import complete.');
    }
}

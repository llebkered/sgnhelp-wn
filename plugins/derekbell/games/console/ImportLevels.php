<?php
namespace Derekbell\Games\Console;

use Illuminate\Console\Command;
use Derekbell\Games\Models\Level;

class ImportLevels extends Command
{
    protected $name = 'levels:import';
    protected $description = 'Import levels and images from JSON and zip.';

    public function handle()
    {
        $zipPath = storage_path('app/levels-export.zip');
        $extractPath = storage_path('app/levels-import');
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
        } else {
            $this->error('Could not open zip file.');
            return;
        }
        $jsonPath = $extractPath . '/levels-export.json';
        if (!file_exists($jsonPath)) {
            $this->error('JSON file not found in zip.');
            return;
        }
        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data) {
            $this->error('Invalid JSON.');
            return;
        }
        foreach ($data['levels'] as $level) {
            Level::updateOrCreate(['id' => $level['id']], $level);
        }
        // Import images
        $imagesPath = $extractPath . '/images';
        $destPath = base_path('plugins/derekbell/games/assets/images');
        if (is_dir($imagesPath)) {
            foreach (glob($imagesPath . '/*') as $file) {
                copy($file, $destPath . '/' . basename($file));
            }
        }
        $this->info('Levels import complete.');
    }
}

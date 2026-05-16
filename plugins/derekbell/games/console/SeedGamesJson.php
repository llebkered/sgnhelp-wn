<?php
namespace DerekBell\Games\Console;

use Illuminate\Console\Command;
use DerekBell\Games\Database\Seeds\SeedGamesData;

class SeedGamesJson extends Command
{
    protected $name = 'games:seed-json';
    protected $description = 'Seed games, episodes, and levels from JSON files.';

    public function handle()
    {
        $this->info('Seeding games, episodes, and levels from JSON...');
        (new SeedGamesData())->run();
        $this->info('Seeding complete.');
    }
}

<?php

namespace DerekBell\Games\Console;

use Illuminate\Console\Command;
use DerekBell\Games\Helpers\CacheHelper;

/**
 * Clear Games Plugin Caches
 */
class ClearCaches extends Command
{
    /**
     * @var string The console command name.
     */
    protected $signature = 'games:clear-cache
                            {--all : Clear all game-related caches}
                            {--games : Clear games list cache}
                            {--levels : Clear levels caches}
                            {--episodes : Clear episodes caches}';

    /**
     * @var string The console command description.
     */
    protected $description = 'Clear caches for the Games plugin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cleared = [];

        if ($this->option('all') || (!$this->option('games') && !$this->option('levels') && !$this->option('episodes'))) {
            CacheHelper::clearAllCaches();
            $this->info('✓ All game-related caches cleared successfully');
            return 0;
        }

        if ($this->option('games')) {
            CacheHelper::clearGamesCaches();
            $cleared[] = 'games';
        }

        if ($this->option('levels')) {
            CacheHelper::clearLevelsCaches();
            $cleared[] = 'levels';
        }

        if ($this->option('episodes')) {
            CacheHelper::clearEpisodesCaches();
            $cleared[] = 'episodes';
        }

        if (!empty($cleared)) {
            $this->info('✓ Cleared caches for: ' . implode(', ', $cleared));
        }

        return 0;
    }
}

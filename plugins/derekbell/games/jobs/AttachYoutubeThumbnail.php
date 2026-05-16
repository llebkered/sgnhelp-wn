<?php

namespace DerekBell\Games\Jobs;

use Illuminate\Bus\Queueable;
use DerekBell\Games\Models\Level;
use DerekBell\Games\Helpers\YoutubeHelper;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AttachYoutubeThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $level;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 60;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Level $level)
    {
        $this->level = $level;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Skip if no youtube_id is set
        if (empty($this->level->youtube_id)) {
            return;
        }

        try {
            $youtube = new YoutubeHelper();
            $file = $youtube->getThumbnailFile($this->level->youtube_id);

            if ($file) {
                $youtube->attachYoutubeThumbnail($this->level->id, $file);
            }
        } catch (\Exception $e) {
            \Log::error("Failed to attach YouTube thumbnail for level {$this->level->id}: " . $e->getMessage());

            // Don't retry if it's an invalid ID
            if ($e instanceof \InvalidArgumentException) {
                return;
            }

            throw $e; // Retry for other errors
        }
    }
}

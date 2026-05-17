<?php

namespace DerekBell\Games\Models;

use Model;
use DerekBell\Games\Helpers\CacheHelper;
use DerekBell\Games\Helpers\YoutubeHelper;

/**
 * Model
 */
class Level extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;
    use \Winter\Storm\Database\Traits\SoftDelete;
    use \Winter\Storm\Database\Traits\Sluggable;



    // Implements the Winter.Search searchable behaviour for full-text search integration
    public $implement = [
        \Winter\Search\Behaviors\Searchable::class,
    ];

    public $searchable = [
        'title',
        'excerpt',
        'description',
    ];


    /**
     * @var array Dates to cast as Carbon instances
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array Generate slugs for these attributes.
     */
    protected $slugs = ['slug' => 'title'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'derekbell_games_levels';

    /**
     * @var array Fillable attributes
     */
    protected $fillable = [
        'episode_id',
        'title',
        'slug',
        'level_number',
        'episode_number',
        'youtube_id',
        'excerpt',
        'description',
        'is_published',
        'is_promoted',
        'sort_order'
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'episode_id' => 'required|exists:derekbell_games_episodes,id',
        'title' => 'required|max:255',
        'slug' => 'nullable|max:255|alpha_dash',
        'level_number' => 'required|integer|min:1',
        'youtube_id' => 'nullable|size:11|alpha_dash',
        'sort_order' => 'nullable|integer|min:0'
    ];

    // Relationships
    public $belongsTo = [
        'episode' => [
            'DerekBell\Games\Models\Episode',
            'key' => 'episode_id'
        ]
    ];


    public $attachOne = [
        'youtube_thumbnail' => 'System\Models\File'
    ];

    /**
     * Scope to get only published levels
     */
    public function scopeIsPublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only promoted levels
     */
    public function scopeIsPromoted($query)
    {
        return $query->where('is_promoted', true);
    }

    /**
     * Get the level's game through episode
     * Note: This accessor is excluded from array/JSON serialization to prevent recursion
     */
    public function getGameAttribute()
    {
        // Only compute if episode is already loaded to avoid N+1
        if (!$this->relationLoaded('episode')) {
            return null;
        }

        $episode = $this->getRelation('episode');

        if (!$episode) {
            return null;
        }

        // Only compute if game is already loaded on episode
        if (!$episode->relationLoaded('game')) {
            return null;
        }

        return $episode->getRelation('game');
    }

    /**
     * Boot the model and clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($level) {
            CacheHelper::clearLevelsCaches($level->episode_id);
        });

        static::deleted(function ($level) {
            CacheHelper::clearLevelsCaches($level->episode_id);
        });
    }

    /**
     * Normalize YouTube URL to video ID
     */
    public function beforeValidate()
    {
        if (!empty($this->youtube_id)) {
            $youtube = new YoutubeHelper();
            $videoId = $youtube->extractVideoId($this->youtube_id);

            if ($videoId) {
                $this->youtube_id = $videoId;
            }
        }
    }
}

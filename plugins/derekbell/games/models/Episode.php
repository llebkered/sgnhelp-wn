<?php namespace DerekBell\Games\Models;

use Model;
use DerekBell\Games\Helpers\CacheHelper;

/**
 * Model
 */
class Episode extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;
    use \Winter\Storm\Database\Traits\SoftDelete;
    use \Winter\Storm\Database\Traits\Sluggable;

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
    public $table = 'derekbell_games_episodes';

    /**
     * @var array Fillable attributes
     */
    protected $fillable = [
        'game_id',
        'title',
        'slug',
        'start_level',
        'end_level',
        'episode_number',
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
        'game_id' => 'required|exists:derekbell_games_games,id',
        'title' => 'required|max:255',
        'slug' => 'nullable|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        'episode_number' => 'required|integer|min:1',
        'start_level' => 'nullable|integer|min:1',
        'end_level' => 'nullable|integer|min:1',
        'sort_order' => 'nullable|integer|min:0'
    ];

    // relationships
    public $belongsTo = [
        'game' => [
            'DerekBell\Games\Models\Game',
            'key' => 'game_id'
        ]
    ];

    public $hasMany = [
        'levels' => [
            'DerekBell\Games\Models\Level',
            'key' => 'episode_id',
            'otherKey' => 'id',
            'order' => 'sort_order asc',
            'softDelete' => true,
        ]
    ];

    /**
     * Scope to get only published episodes
     */
    public function scopeIsPublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only promoted episodes
     */
    public function scopeIsPromoted($query)
    {
        return $query->where('is_promoted', true);
    }

    /**
     * Scope to filter episodes by game
     */
    public function scopeFilterGame($query, $gameId)
    {
        if ($gameId) {
            return $query->where('game_id', $gameId);
        }
        return $query;
    }

    /**
     * Boot the model and clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($episode) {
            CacheHelper::clearEpisodesCaches($episode->game_id);
        });

        static::deleted(function ($episode) {
            CacheHelper::clearEpisodesCaches($episode->game_id);
        });
    }

    /**
     * Add unique validation for episode number per game
     */
    public function beforeValidate()
    {
        if (!empty($this->slug)) {
            $this->slug = strtolower($this->slug);

            $ignoreId = $this->id ?: 'NULL';
            $this->rules['slug'] =
                'nullable|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:derekbell_games_episodes,slug,' .
                $ignoreId . ',id';
        }

        if ($this->game_id && $this->episode_number) {
            $ignoreId = $this->id ?: 'NULL';
            $this->rules['episode_number'] =
                'required|integer|min:1|unique:derekbell_games_episodes,episode_number,' .
                $ignoreId . ',id,game_id,' . $this->game_id;
        }
    }
}

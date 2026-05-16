<?php namespace DerekBell\Games\Models;

use Model;
use DerekBell\Games\Helpers\CacheHelper;

/**
 * Model
 */
class Game extends Model
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
    public $table = 'derekbell_games_games';

    /**
     * @var array Fillable attributes
     */
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'description',
        'is_published',
        'is_promoted',
        'sort_order',
        'color_theme'
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'title'      => 'required|max:255',
        'slug'       => 'nullable|max:255|alpha_dash',
        'excerpt'    => 'nullable|max:500',
        'sort_order' => 'nullable|integer|min:0',
    ];

    // attach files
    public $attachOne = [
        'logo' => 'System\Models\File'
    ];

    // relationships
    public $hasMany = [
        'episodes' => [
            'DerekBell\Games\Models\Episode',
            'key' => 'game_id' // Ensure this matches your foreign key
        ]
    ];

    /**
     * Scope to get only published games
     */
    public function scopeIsPublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to get only promoted games
     */
    public function scopeIsPromoted($query)
    {
        return $query->where('is_promoted', true);
    }

    /**
     * Scope for filter dropdowns - returns unique published games ordered by title
     */
    public function scopeForFilter($query)
    {
        return $query->isPublished()->orderBy('title', 'asc');
    }

    /**
     * Returns options array for use in dropdown filter scopes [id => title]
     */
    public function getFilterOptions()
    {
        return static::orderBy('title', 'asc')->pluck('title', 'id')->all();
    }

    /**
     * Inject the ignore-clause into the slug uniqueness rule so that
     * saving an existing game without changing the slug does not fail.
     */
    public function beforeValidate()
    {
        $ignoreId = $this->id ?: 'NULL';
        $this->rules['slug'] = "nullable|unique:derekbell_games_games,slug,{$ignoreId},id|max:255|alpha_dash";
    }

    /**
     * Boot the model and clear cache on save/delete
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            CacheHelper::clearGamesCaches();
        });

        static::deleted(function () {
            CacheHelper::clearGamesCaches();
        });
    }
}

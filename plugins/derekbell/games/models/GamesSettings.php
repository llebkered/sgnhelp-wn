<?php namespace DerekBell\Games\Models;

use Model;

class GamesSettings extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $implement = [
        \System\Behaviors\SettingsModel::class,
    ];

    public $settingsCode = 'derekbell_games_settings';

    public $settingsFields = 'fields.yaml';

    public $rules = [
        'max_bulk_levels'    => 'required|integer|min:1|max:1000',
        'max_playlist_levels' => 'required|integer|min:1|max:5000',
    ];

    public function initSettingsData()
    {
        $this->max_bulk_levels    = 100;
        $this->max_playlist_levels = 500;
    }
}

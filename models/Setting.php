<?php namespace Anguro\Capse\Models;

use Model;

/**
 * Setting Model
 */
class Setting extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'anguro_capse_settings';
    public $settingsFields = 'fields.yaml';

    public function initSettingsData()
    {
        $this->google_maps_key = '';
    }
}

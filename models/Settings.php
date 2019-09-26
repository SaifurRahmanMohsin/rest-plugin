<?php namespace Mohsin\Rest\Models;

use Model;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'mobile_api_settings';

    public $settingsFields = 'fields.yaml';

    public function beforeFetch()
    {
    }

    public function getProviderOptions()
    {
        $values = [];
        $providers = ProviderManager::instance()->listProviderObjects();
        foreach ($providers as $key => $value) {
            $values[$key] = $value->providerDetails()['name'];
        }
        return $values;
    }
}

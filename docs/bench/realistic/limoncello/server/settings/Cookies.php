<?php namespace Settings;

use Limoncello\Application\Packages\Cookies\CookieSettings;

/**
 * @package Settings
 */
class Cookies extends CookieSettings
{
    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        return [
                static::KEY_DEFAULT_IS_ACCESSIBLE_ONLY_THROUGH_HTTP => true,

            ] + parent::getSettings();
    }
}

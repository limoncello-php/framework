<?php namespace Limoncello\Application\Packages\Authorization;

use Limoncello\Application\Authorization\AuthorizationRulesLoader;
use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package Limoncello\Application
 */
abstract class AuthorizationSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_LOG_IS_ENABLED = 0;

    /** Settings key */
    const KEY_POLICIES_DATA = self::KEY_LOG_IS_ENABLED + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_POLICIES_DATA + 1;

    /** Top level policy set name (used in logging) */
    const POLICIES_NAME = 'Application';

    /**
     * @return string
     */
    abstract protected function getPoliciesPath(): string;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $loader = (new AuthorizationRulesLoader($this->getPoliciesPath(), static::POLICIES_NAME));

        return [
            static::KEY_LOG_IS_ENABLED => true,
            static::KEY_POLICIES_DATA  => $loader->getRulesData(),
        ];
    }
}

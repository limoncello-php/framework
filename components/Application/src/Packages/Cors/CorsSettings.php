<?php namespace Limoncello\Application\Packages\Cors;

/**
 * Copyright 2015-2017 info@neomerx.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Settings\SettingsInterface;
use Neomerx\Cors\Strategies\Settings;

/**
 * @package Limoncello\Application
 */
class CorsSettings implements SettingsInterface
{
    /** @see Settings */
    const VALUE_ALLOW_ORIGIN_ALL = Settings::VALUE_ALLOW_ORIGIN_ALL;

    /** @see Settings */
    const KEY_SERVER_ORIGIN = Settings::KEY_SERVER_ORIGIN;

    /** @see Settings */
    const KEY_SERVER_ORIGIN_SCHEME = Settings::KEY_SERVER_ORIGIN_SCHEME;

    /** @see Settings */
    const KEY_SERVER_ORIGIN_HOST = Settings::KEY_SERVER_ORIGIN_HOST;

    /** @see Settings */
    const KEY_SERVER_ORIGIN_PORT = Settings::KEY_SERVER_ORIGIN_PORT;

    /** @see Settings */
    const KEY_ALLOWED_ORIGINS = Settings::KEY_ALLOWED_ORIGINS;

    /** @see Settings */
    const KEY_ALLOWED_METHODS = Settings::KEY_ALLOWED_METHODS;

    /** @see Settings */
    const KEY_ALLOWED_HEADERS = Settings::KEY_ALLOWED_HEADERS;

    /** @see Settings */
    const KEY_EXPOSED_HEADERS = Settings::KEY_EXPOSED_HEADERS;

    /** @see Settings */
    const KEY_IS_USING_CREDENTIALS = Settings::KEY_IS_USING_CREDENTIALS;

    /** @see Settings */
    const KEY_FLIGHT_CACHE_MAX_AGE = Settings::KEY_FLIGHT_CACHE_MAX_AGE;

    /** @see Settings */
    const KEY_IS_FORCE_ADD_METHODS = Settings::KEY_IS_FORCE_ADD_METHODS;

    /** @see Settings */
    const KEY_IS_FORCE_ADD_HEADERS = Settings::KEY_IS_FORCE_ADD_HEADERS;

    /** @see Settings */
    const KEY_IS_CHECK_HOST = Settings::KEY_IS_CHECK_HOST;

    /** Settings key */
    const KEY_LOG_IS_ENABLED = self::KEY_IS_CHECK_HOST + 10;

    /** Settings key */
    protected const KEY_LAST = self::KEY_LOG_IS_ENABLED;

    /**
     * @var array
     */
    private $appConfig;

    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $this->appConfig = $appConfig;

        return $this->getSettings();
    }

    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        // Settings do not provide any public methods to get default settings so we use this trick
        $defaults = (new class extends Settings
        {
            /**
             * @return array
             */
            public function getHiddenDefaults(): array
            {
                return $this->getDefaultSettings();
            }
        })->getHiddenDefaults();

        $appConfig = $this->getAppConfig();

        $defaults[static::KEY_LOG_IS_ENABLED] = (bool)($appConfig[A::KEY_IS_LOG_ENABLED] ?? false);

        if (array_key_exists(A::KEY_APP_ORIGIN_SCHEME, $appConfig) === true &&
            array_key_exists(A::KEY_APP_ORIGIN_HOST, $appConfig) === true &&
            array_key_exists(A::KEY_APP_ORIGIN_PORT, $appConfig) === true
        ) {
            /**
             * Array should be in parse_url() result format.
             *
             * @see http://php.net/manual/function.parse-url.php
             */
            $defaults[static::KEY_SERVER_ORIGIN] = [
                static::KEY_SERVER_ORIGIN_SCHEME => (string)$appConfig[A::KEY_APP_ORIGIN_SCHEME],
                static::KEY_SERVER_ORIGIN_HOST   => (string)$appConfig[A::KEY_APP_ORIGIN_HOST],
                static::KEY_SERVER_ORIGIN_PORT   => (string)$appConfig[A::KEY_APP_ORIGIN_PORT],
            ];
        }

        return $defaults;
    }

    /**
     * @return mixed
     */
    protected function getAppConfig()
    {
        return $this->appConfig;
    }
}

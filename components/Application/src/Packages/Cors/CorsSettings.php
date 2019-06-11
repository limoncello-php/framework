<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Cors;

/**
 * Copyright 2015-2019 info@neomerx.com
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
    const KEY_ALLOWED_ORIGINS = 0;

    /** @see Settings */
    const KEY_ALLOWED_METHODS = self::KEY_ALLOWED_ORIGINS + 1;

    /** @see Settings */
    const KEY_ALLOWED_HEADERS = self::KEY_ALLOWED_METHODS + 1;

    /** @see Settings */
    const KEY_EXPOSED_HEADERS = self::KEY_ALLOWED_HEADERS + 1;

    /** @see Settings */
    const KEY_IS_USING_CREDENTIALS = self::KEY_EXPOSED_HEADERS + 1;

    /** @see Settings */
    const KEY_FLIGHT_CACHE_MAX_AGE = self::KEY_IS_USING_CREDENTIALS + 1;

    /** @see Settings */
    const KEY_IS_FORCE_ADD_METHODS = self::KEY_FLIGHT_CACHE_MAX_AGE + 1;

    /** @see Settings */
    const KEY_IS_FORCE_ADD_HEADERS = self::KEY_IS_FORCE_ADD_METHODS + 1;

    /** @see Settings */
    const KEY_IS_CHECK_HOST = self::KEY_IS_FORCE_ADD_HEADERS + 1;

    /** Settings key */
    const KEY_LOG_IS_ENABLED = self::KEY_IS_CHECK_HOST + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_LOG_IS_ENABLED;

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

        $serverOriginScheme = $appConfig[A::KEY_APP_ORIGIN_SCHEMA];
        $serverOriginHost   = $appConfig[A::KEY_APP_ORIGIN_HOST];
        $serverOriginPort   = $appConfig[A::KEY_APP_ORIGIN_PORT] ? (int)$appConfig[A::KEY_APP_ORIGIN_PORT] : null;

        $corsSettings = (new Settings())->init($serverOriginScheme, $serverOriginHost, $serverOriginPort);

        // convert settings into Cors Settings and then into cache data
        $packageSettings = $this->getSettings();

        $corsSettings->setAllowedOrigins($packageSettings[static::KEY_ALLOWED_ORIGINS]);
        $corsSettings->setAllowedMethods($packageSettings[static::KEY_ALLOWED_METHODS]);
        $corsSettings->setAllowedHeaders($packageSettings[static::KEY_ALLOWED_HEADERS]);
        $corsSettings->setExposedHeaders($packageSettings[static::KEY_EXPOSED_HEADERS]);
        $corsSettings->setPreFlightCacheMaxAge($packageSettings[static::KEY_FLIGHT_CACHE_MAX_AGE]);

        $packageSettings[static::KEY_IS_USING_CREDENTIALS] === true ?
            $corsSettings->setCredentialsSupported() : $corsSettings->setCredentialsNotSupported();

        $packageSettings[static::KEY_IS_FORCE_ADD_METHODS] === true ?
            $corsSettings->enableAddAllowedMethodsToPreFlightResponse() :
            $corsSettings->disableAddAllowedMethodsToPreFlightResponse();

        $packageSettings[static::KEY_IS_FORCE_ADD_HEADERS] === true ?
            $corsSettings->enableAddAllowedHeadersToPreFlightResponse() :
            $corsSettings->disableAddAllowedHeadersToPreFlightResponse();

        $packageSettings[static::KEY_IS_CHECK_HOST] === true ?
            $corsSettings->enableCheckHost() : $corsSettings->disableCheckHost();

        return [$corsSettings->getData(), (bool)$packageSettings[static::KEY_LOG_IS_ENABLED]];
    }

    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        $appConfig = $this->getAppConfig();

        $serverOrigin = $appConfig[A::KEY_APP_ORIGIN_URI] ?? null;
        $isLogEnabled = (bool)($appConfig[A::KEY_IS_LOG_ENABLED] ?? false);

        return [
            static::KEY_ALLOWED_ORIGINS      => empty($serverOrigin) === true ? [] : [$serverOrigin],
            static::KEY_ALLOWED_METHODS      => [],
            static::KEY_ALLOWED_HEADERS      => [],
            static::KEY_EXPOSED_HEADERS      => [],
            static::KEY_IS_USING_CREDENTIALS => false,
            static::KEY_FLIGHT_CACHE_MAX_AGE => 0,
            static::KEY_IS_FORCE_ADD_METHODS => false,
            static::KEY_IS_FORCE_ADD_HEADERS => false,
            static::KEY_IS_CHECK_HOST        => true,
            static::KEY_LOG_IS_ENABLED       => $isLogEnabled,
        ];
    }

    /**
     * @return mixed
     */
    protected function getAppConfig()
    {
        return $this->appConfig;
    }
}

<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Cookies;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Contracts\Settings\Packages\CookieSettingsInterface;
use function assert;
use function is_bool;
use function is_string;

/**
 * @package Limoncello\Application
 */
class CookieSettings implements CookieSettingsInterface
{
    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $path = $defaults[static::KEY_DEFAULT_PATH] ?? null;
        assert(is_string($path), 'Invalid default path.');

        $domain = $defaults[static::KEY_DEFAULT_DOMAIN] ?? null;
        assert(is_string($domain), 'Invalid default domain.');

        $isSecure = $defaults[static::KEY_DEFAULT_IS_SEND_ONLY_OVER_SECURE_CONNECTION] ?? null;
        assert(is_bool($isSecure), 'Invalid `secure` value.');

        $isHttpOnly = $defaults[static::KEY_DEFAULT_IS_ACCESSIBLE_ONLY_THROUGH_HTTP] ?? null;
        assert(is_bool($isHttpOnly), 'Invalid `httpOnly` value.');

        $isRaw = $defaults[static::KEY_DEFAULT_IS_RAW] ?? null;
        assert(is_bool($isRaw), 'Invalid Send Raw Cookies value.');

        return $defaults;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_DEFAULT_PATH                                => '',
            static::KEY_DEFAULT_DOMAIN                              => '',
            static::KEY_DEFAULT_IS_SEND_ONLY_OVER_SECURE_CONNECTION => false,
            static::KEY_DEFAULT_IS_ACCESSIBLE_ONLY_THROUGH_HTTP     => true,
            static::KEY_DEFAULT_IS_RAW                              => false,
        ];
    }
}

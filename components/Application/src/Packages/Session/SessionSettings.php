<?php namespace Limoncello\Application\Packages\Session;

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

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package Limoncello\Application
 */
class SessionSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_SAVE_PATH = 'save_path';

    /** Settings key */
    const KEY_NAME = 'name';

    /** Settings key */
    const KEY_SAVE_HANDLER = 'save_handler';

    /** Settings key */
    const KEY_COOKIE_LIFETIME = 'cookie_lifetime';

    /** Settings key */
    const KEY_COOKIE_PATH = 'cookie_path';

    /** Settings key */
    const KEY_COOKIE_DOMAIN = 'cookie_domain';

    /** Settings key */
    const KEY_COOKIE_SECURE = 'cookie_secure';

    /** Settings key */
    const KEY_COOKIE_HTTP_ONLY = 'cookie_httponly';

    /** Settings key */
    const KEY_USE_STRICT_MODE = 'use_strict_mode';

    /** Settings key */
    const KEY_USE_COOKIES = 'use_cookies';

    /** Settings key */
    const KEY_USE_ONLY_COOKIES = 'use_only_cookies';

    /** Settings key */
    const KEY_CACHE_LIMITER = 'cache_limiter';

    /** Settings key */
    const KEY_CACHE_EXPIRE = 'cache_expire';

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        return $defaults;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_NAME             => 'session_id',
            static::KEY_USE_STRICT_MODE  => '1',
            static::KEY_COOKIE_HTTP_ONLY => '1',
        ];
    }
}

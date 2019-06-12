<?php namespace Limoncello\Contracts\Settings\Packages;

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

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * Provides individual settings for a component.
 *
 * @package Limoncello\Contracts
 */
interface DoctrineSettingsInterface extends SettingsInterface
{
    /** Settings key */
    const KEY_USER_NAME = 0;

    /** Settings key */
    const KEY_PASSWORD = self::KEY_USER_NAME + 1;

    /** Settings key */
    const KEY_DATABASE_NAME = self::KEY_PASSWORD + 1;

    /** Settings key */
    const KEY_HOST = self::KEY_DATABASE_NAME + 1;

    /** Settings key */
    const KEY_PORT = self::KEY_HOST + 1;

    /** Settings key */
    const KEY_CHARSET = self::KEY_PORT + 1;

    /** Settings key */
    const KEY_DRIVER = self::KEY_CHARSET + 1;

    /** Settings key */
    const KEY_URL = self::KEY_DRIVER + 1;

    /** Settings key */
    const KEY_MEMORY = self::KEY_URL + 1;

    /** Settings key */
    const KEY_EXTRA = self::KEY_MEMORY + 1;

    /** Settings key */
    const KEY_PATH = self::KEY_EXTRA + 1;

    /** Settings key */
    const KEY_EXEC = self::KEY_PATH + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_EXEC;
}

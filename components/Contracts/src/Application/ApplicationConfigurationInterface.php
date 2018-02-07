<?php namespace Limoncello\Contracts\Application;

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

/**
 * @package Limoncello\Contracts
 */
interface ApplicationConfigurationInterface
{
    /** Settings key */
    const KEY_APP_NAME = 0;

    /** Settings key */
    const KEY_APP_ORIGIN_SCHEME = self::KEY_APP_NAME + 1;

    /** Settings key */
    const KEY_APP_ORIGIN_HOST = self::KEY_APP_ORIGIN_SCHEME + 1;

    /** Settings key */
    const KEY_APP_ORIGIN_PORT = self::KEY_APP_ORIGIN_HOST + 1;

    /** Settings key */
    const KEY_APP_ORIGIN_URI = self::KEY_APP_ORIGIN_PORT + 1;

    /** Settings key */
    const KEY_PROVIDER_CLASSES = self::KEY_APP_ORIGIN_URI + 1;

    /** Settings key */
    const KEY_CACHE_FOLDER = self::KEY_PROVIDER_CLASSES + 1;

    /** Settings key */
    const KEY_CACHE_CALLABLE = self::KEY_CACHE_FOLDER + 1;

    /** Settings key */
    const KEY_COMMANDS_FOLDER = self::KEY_CACHE_CALLABLE + 1;

    /** Settings key */
    const KEY_COMMANDS_FILE_MASK = self::KEY_COMMANDS_FOLDER + 1;

    /** Settings key */
    const KEY_IS_DEBUG = self::KEY_COMMANDS_FILE_MASK + 1;

    /** Settings key */
    const KEY_IS_LOG_ENABLED = self::KEY_IS_DEBUG + 1;

    /** Settings key */
    const KEY_ROUTES_PATH = self::KEY_IS_LOG_ENABLED + 1;

    /** Settings key */
    const KEY_ROUTES_FOLDER = self::KEY_ROUTES_PATH + 1;

    /** Settings key */
    const KEY_ROUTES_FILE_MASK = self::KEY_ROUTES_FOLDER + 1;

    /** Settings key */
    const KEY_CONTAINER_CONFIGURATORS_PATH = self::KEY_ROUTES_FILE_MASK + 1;

    /** Settings key */
    const KEY_EXCEPTION_DUMPER = self::KEY_CONTAINER_CONFIGURATORS_PATH + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_EXCEPTION_DUMPER + 1;

    /**
     * @return array
     */
    public function get(): array;
}

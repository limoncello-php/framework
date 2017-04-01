<?php namespace Limoncello\Application\Packages\Application;

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

use Limoncello\Contracts\Application\ApplicationSettingsInterface;

/**
 * @package Limoncello\Application
 */
abstract class ApplicationSettings implements ApplicationSettingsInterface
{
    /** Settings key */
    const KEY_IS_DEBUG = self::KEY_PROVIDER_CLASSES + 1;

    /** Settings key */
    const KEY_ROUTES_PATH = self::KEY_IS_DEBUG + 1;

    /** Settings key */
    const KEY_CONTAINER_CONFIGURATORS_PATH = self::KEY_ROUTES_PATH + 1;

    /** Settings key */
    const KEY_EXCEPTION_DUMPER = self::KEY_CONTAINER_CONFIGURATORS_PATH + 1;

    /** Settings key */
    const KEY_CACHE_PATH = self::KEY_EXCEPTION_DUMPER + 1;

    /** Settings key */
    const KEY_CACHE_CALLABLE = self::KEY_CACHE_PATH + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_CACHE_CALLABLE + 1;
}

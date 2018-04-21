<?php namespace Limoncello\Contracts\Settings\Packages;

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
 * Provides individual settings for a component.
 *
 * @package Limoncello\Contracts
 */
interface DataSettingsInterface extends SettingsInterface
{
    /** Settings key */
    const KEY_MODELS_FOLDER = 0;

    /** Settings key */
    const KEY_MODELS_FILE_MASK = self::KEY_MODELS_FOLDER + 1;

    /** Settings key */
    const KEY_MIGRATIONS_FOLDER = self::KEY_MODELS_FILE_MASK + 1;

    /** Settings key */
    const KEY_MIGRATIONS_LIST_FILE = self::KEY_MIGRATIONS_FOLDER + 1;

    /** Settings key */
    const KEY_SEEDS_FOLDER = self::KEY_MIGRATIONS_LIST_FILE + 1;

    /** Settings key */
    const KEY_SEEDS_LIST_FILE = self::KEY_SEEDS_FOLDER + 1;

    /** Settings key */
    const KEY_SEED_INIT = self::KEY_SEEDS_LIST_FILE + 1;

    /** Settings key */
    const KEY_MODELS_SCHEMA_INFO = self::KEY_SEED_INIT + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_MODELS_SCHEMA_INFO;
}

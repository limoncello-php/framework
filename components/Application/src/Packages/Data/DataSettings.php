<?php namespace Limoncello\Application\Packages\Data;

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
abstract class DataSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_MIGRATIONS_PATH = 0;

    /** Settings key */
    const KEY_SEEDS_PATH = self::KEY_MIGRATIONS_PATH + 1;

    /** Settings key */
    const KEY_SEED_INIT = self::KEY_SEEDS_PATH + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_SEED_INIT + 1;
}

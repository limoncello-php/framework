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
interface AuthorizationSettingsInterface extends SettingsInterface
{
    /** Settings key */
    const KEY_LOG_IS_ENABLED = 0;

    /**
     * Settings key
     *
     * Top level policy set name (used in logging).
     */
    const KEY_TOP_POLICY_NAME = self::KEY_LOG_IS_ENABLED + 1;

    /** Settings key */
    const KEY_POLICIES_FOLDER = self::KEY_TOP_POLICY_NAME + 1;

    /** Settings key */
    const KEY_POLICIES_FILE_MASK = self::KEY_POLICIES_FOLDER + 1;

    /** Settings key */
    const KEY_POLICIES_DATA = self::KEY_POLICIES_FILE_MASK + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_POLICIES_DATA;
}

<?php namespace Limoncello\Application\Packages\Authorization;

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

use Limoncello\Application\Authorization\AuthorizationRulesLoader;
use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package Limoncello\Application
 */
abstract class AuthorizationSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_LOG_IS_ENABLED = 0;

    /** Settings key */
    const KEY_POLICIES_DATA = self::KEY_LOG_IS_ENABLED + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_POLICIES_DATA + 1;

    /** Top level policy set name (used in logging) */
    const POLICIES_NAME = 'Application';

    /**
     * @return string
     */
    abstract protected function getPoliciesPath(): string;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $loader = (new AuthorizationRulesLoader($this->getPoliciesPath(), static::POLICIES_NAME));

        return [
            static::KEY_LOG_IS_ENABLED => true,
            static::KEY_POLICIES_DATA  => $loader->getRulesData(),
        ];
    }
}

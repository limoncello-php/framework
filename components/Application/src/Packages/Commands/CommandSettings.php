<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Commands;

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

use Limoncello\Contracts\Settings\Packages\CommandSettingsInterface;
use Limoncello\Contracts\Settings\SettingsInterface;
use function assert;
use function is_array;
use function is_int;
use function is_string;

/**
 * @package Limoncello\Application
 */
class CommandSettings implements SettingsInterface, CommandSettingsInterface
{
    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $userIdentity   = $defaults[static::KEY_IMPERSONATE_AS_USER_IDENTITY] ?? null;
        $userProperties = $defaults[static::KEY_IMPERSONATE_WITH_USER_PROPERTIES] ?? null;

        assert(
            $userIdentity === null || (is_string($userIdentity) === true && empty($userIdentity) === false) ||
            is_int($userIdentity) === true,
            'Invalid Impersonation User Identity.'
        );
        assert(is_array($userProperties) === true, 'Invalid Impersonation User Properties.');

        return $defaults;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_IMPERSONATE_AS_USER_IDENTITY     => null,
            static::KEY_IMPERSONATE_WITH_USER_PROPERTIES => [],
        ];
    }
}

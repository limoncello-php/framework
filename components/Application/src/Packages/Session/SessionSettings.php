<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\Session;

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

use Limoncello\Contracts\Settings\Packages\SessionSettingsInterface;

/**
 * @package Limoncello\Application
 */
class SessionSettings implements SessionSettingsInterface
{
    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
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

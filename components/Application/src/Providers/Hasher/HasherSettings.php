<?php namespace Limoncello\Application\Providers\Hasher;

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
class HasherSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_ALGORITHM = 0;

    /** Settings key */
    const KEY_COST = self::KEY_ALGORITHM + 1;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            /** @see http://php.net/manual/en/password.constants.php */
            static::KEY_ALGORITHM => PASSWORD_DEFAULT,
            /** @see http://php.net/manual/en/function.password-hash.php */
            static::KEY_COST      => 10,
        ];
    }
}

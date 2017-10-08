<?php namespace Limoncello\Crypt\Package;

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
 * @package Limoncello\Crypt
 */
class AsymmetricCryptSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_PUBLIC_PATH_OR_KEY_VALUE = 0;

    /** Settings key */
    const KEY_PRIVATE_PATH_OR_KEY_VALUE = self::KEY_PUBLIC_PATH_OR_KEY_VALUE + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_PRIVATE_PATH_OR_KEY_VALUE;

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        $publicValue = $defaults[static::KEY_PUBLIC_PATH_OR_KEY_VALUE];
        assert(empty($publicValue) === false, "Public key/value cannot be empty.");

        $privateValue = $defaults[static::KEY_PUBLIC_PATH_OR_KEY_VALUE];
        assert(empty($privateValue) === false, "Private key/value cannot be empty.");

        return $defaults;
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [];
    }
}

<?php namespace Limoncello\Application\Packages\L10n;

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
use Limoncello\l10n\Messages\FileBundleEncoder;

/**
 * @package Limoncello\Application
 */
abstract class L10nSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_DEFAULT_LOCALE = 0;

    /** Settings key */
    const KEY_LOCALES_DATA = self::KEY_DEFAULT_LOCALE + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_LOCALES_DATA;

    /**
     * @return string
     */
    abstract protected function getDefaultLocale(): string;

    /**
     * @return string
     */
    abstract protected function getLocalesPath(): string;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $defaultLocale = $this->getDefaultLocale();
        $loader        = (new FileBundleEncoder($this->getLocalesPath()));

        return [
            static::KEY_DEFAULT_LOCALE => $defaultLocale,
            static::KEY_LOCALES_DATA   => $loader->getStorageData($defaultLocale),
        ];
    }
}

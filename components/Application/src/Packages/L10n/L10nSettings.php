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
    const KEY_LOCALES_FOLDER = self::KEY_DEFAULT_LOCALE + 1;

    /** Settings key */
    const KEY_LOCALES_DATA = self::KEY_LOCALES_FOLDER + 1;

    /** Settings key */
    protected const KEY_LAST = self::KEY_LOCALES_DATA;

    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $defaults = $this->getSettings();

        $defaultLocale = $defaults[static::KEY_DEFAULT_LOCALE] ?? null;
        assert(empty($defaultLocale) === false, "Invalid default locale `$defaultLocale`.");

        $localesFolder = $defaults[static::KEY_LOCALES_FOLDER] ?? null;
        assert(
            $localesFolder !== null && empty(glob($localesFolder)) === false,
            "Invalid Locales folder `$localesFolder`."
        );

        return $defaults + [
                static::KEY_LOCALES_DATA => (new FileBundleEncoder($localesFolder))->getStorageData($defaultLocale),
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_DEFAULT_LOCALE => 'en',
        ];
    }
}

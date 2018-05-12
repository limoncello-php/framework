<?php namespace Limoncello\Application\Packages\L10n;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Provider\ProvidesMessageResourcesInterface;
use Limoncello\Contracts\Settings\Packages\L10nSettingsInterface;
use Limoncello\l10n\Messages\FileBundleEncoder;

/**
 * @package Limoncello\Application
 */
abstract class L10nSettings implements L10nSettingsInterface
{
    /**
     * @var array
     */
    private $appConfig;

    /**
     * @inheritdoc
     */
    final public function get(array $appConfig): array
    {
        $this->appConfig = $appConfig;

        $defaults = $this->getSettings();

        $defaultLocale = $defaults[static::KEY_DEFAULT_LOCALE] ?? null;
        assert(empty($defaultLocale) === false, "Invalid default locale `$defaultLocale`.");

        $localesFolder = $defaults[static::KEY_LOCALES_FOLDER] ?? null;
        assert(
            $localesFolder !== null && empty(glob($localesFolder)) === false,
            "Invalid Locales folder `$localesFolder`."
        );

        $bundleEncoder = new FileBundleEncoder($this->getMessageDescriptionsFromProviders(), $localesFolder);

        return $defaults + [
                static::KEY_LOCALES_DATA => $bundleEncoder->getStorageData($defaultLocale),
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

    /**
     * @return mixed
     */
    protected function getAppConfig()
    {
        return $this->appConfig;
    }

    /**
     *
     * @return iterable
     */
    private function getMessageDescriptionsFromProviders(): iterable
    {
        $providerClasses = $this->getAppConfig()[A::KEY_PROVIDER_CLASSES] ?? [];
        foreach ($providerClasses as $class) {
            if (in_array(ProvidesMessageResourcesInterface::class, class_implements($class)) === true) {
                /** @var ProvidesMessageResourcesInterface $class */
                foreach ($class::getMessageDescriptions() as $messageDescription) {
                    yield $messageDescription;
                }
            }
        }
    }
}

<?php declare(strict_types=1);

namespace Limoncello\Application\Packages\L10n;

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

use Limoncello\Application\Packages\L10n\L10nSettings as S;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\l10n\Format\Formatter;
use Limoncello\l10n\Format\Translator;
use Limoncello\l10n\Messages\BundleStorage;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class L10nContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[FormatterFactoryInterface::class] = function (PsrContainerInterface $container) {
            $settingsProvider = $container->get(SettingsProviderInterface::class);
            $settings         = $settingsProvider->get(S::class);

            $defaultLocale = $settings[S::KEY_DEFAULT_LOCALE];
            $storageData   = $settings[S::KEY_LOCALES_DATA];

            $factory = new class ($defaultLocale, $storageData) implements FormatterFactoryInterface
            {
                /**
                 * @var string
                 */
                private $defaultLocale;

                /**
                 * @var array
                 */
                private $storageData;

                /**
                 * @param string $defaultLocale
                 * @param array  $storageData
                 */
                public function __construct(string $defaultLocale, array $storageData)
                {
                    $this->defaultLocale = $defaultLocale;
                    $this->storageData   = $storageData;
                }

                /**
                 * @inheritdoc
                 */
                public function createFormatter(string $namespace): FormatterInterface
                {
                    return $this->createFormatterForLocale($namespace, $this->defaultLocale);
                }

                /**
                 * @inheritdoc
                 */
                public function createFormatterForLocale(string $namespace, string $locale): FormatterInterface
                {
                    $translator = new Translator(new BundleStorage($this->storageData));
                    $formatter  = new Formatter($locale, $namespace, $translator);

                    return $formatter;
                }
            };

            return $factory;
        };
    }
}

<?php namespace Limoncello\Application\Packages\Cookies;

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

use Limoncello\Application\Contracts\Cookie\CookieFunctionsInterface;
use Limoncello\Application\Cookies\CookieFunctions;
use Limoncello\Application\Cookies\CookieJar;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Cookies\CookieJarInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class CookieContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[CookieJarInterface::class] =
            function (PsrContainerInterface $container): CookieJarInterface {
                $settings = $container->get(SettingsProviderInterface::class)->get(CookieSettings::class);

                return new CookieJar(
                    $settings[CookieSettings::KEY_DEFAULT_PATH],
                    $settings[CookieSettings::KEY_DEFAULT_DOMAIN],
                    $settings[CookieSettings::KEY_DEFAULT_IS_SEND_ONLY_OVER_SECURE_CONNECTION],
                    $settings[CookieSettings::KEY_DEFAULT_IS_ACCESSIBLE_ONLY_THROUGH_HTTP],
                    $settings[CookieSettings::KEY_DEFAULT_IS_RAW]
                );
            };

        $container[CookieFunctionsInterface::class] =
            function (PsrContainerInterface $container): CookieFunctionsInterface {
                return new CookieFunctions();
            };
    }
}

<?php declare(strict_types=1);

namespace Limoncello\Templates\Package;

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

use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Contracts\Templates\TemplatesInterface;
use Limoncello\Templates\Contracts\TemplatesCacheInterface;
use Limoncello\Templates\Package\TemplatesSettings as C;
use Limoncello\Templates\TwigTemplates;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Templates
 */
class TwigTemplatesContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[TemplatesInterface::class] = function (PsrContainerInterface $container): TemplatesInterface {
            $settings  = $container->get(SettingsProviderInterface::class)->get(C::class);
            $templates = new TwigTemplates(
                $settings[C::KEY_APP_ROOT_FOLDER],
                $settings[C::KEY_TEMPLATES_FOLDER],
                $settings[C::KEY_CACHE_FOLDER] ?? null,
                $settings[C::KEY_IS_DEBUG] ?? false,
                $settings[C::KEY_IS_AUTO_RELOAD] ?? false
            );

            return $templates;
        };

        $container[TemplatesCacheInterface::class] =
            function (PsrContainerInterface $container): TemplatesCacheInterface {
                return $container->get(TemplatesInterface::class);
            };
    }
}

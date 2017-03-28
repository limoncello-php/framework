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

use Limoncello\Application\Contracts\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Crypt\Contracts\HasherInterface;
use Limoncello\Crypt\Hasher;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Limoncello\Application\Providers\Hasher\HasherSettings as C;

/**
 * @package Limoncello\Application
 */
class HasherContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const METHOD = [self::class, self::METHOD_NAME];

    /**
     * @param LimoncelloContainerInterface $container
     *
     * @return void
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        $container[HasherInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(C::class);
            $hasher   = new Hasher($settings[C::KEY_ALGORITHM], $settings[C::KEY_COST]);

            return $hasher;
        };
    }
}

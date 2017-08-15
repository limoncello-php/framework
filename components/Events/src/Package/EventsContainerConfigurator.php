<?php namespace Limoncello\Events\Package;

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

use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Events\Contracts\EventEmitterInterface;
use Limoncello\Events\Package\EventSettings as C;
use Limoncello\Events\SimpleEventEmitter;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Events
 */
class EventsContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[EventEmitterInterface::class] = function (PsrContainerInterface $container): EventEmitterInterface {
            $emitter   = new SimpleEventEmitter();
            $cacheData = $container->get(SettingsProviderInterface::class)->get(C::class);
            $emitter->setStaticSubscribers($cacheData);

            return $emitter;
        };
    }
}

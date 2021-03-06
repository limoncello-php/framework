<?php declare(strict_types=1);

namespace Limoncello\Events\Package;

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
use Limoncello\Events\Contracts\EventDispatcherInterface;
use Limoncello\Events\Contracts\EventEmitterInterface;
use Limoncello\Events\Package\EventSettings as C;
use Limoncello\Events\SimpleEventEmitter;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use function call_user_func;

/**
 * @package Limoncello\Events
 */
class EventsContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $emitter            = null;
        $getOrCreateEmitter = function (PsrContainerInterface $container) use (&$emitter): SimpleEventEmitter {
            if ($emitter === null) {
                $emitter   = new SimpleEventEmitter();
                $cacheData = $container->get(SettingsProviderInterface::class)->get(C::class)[C::KEY_CACHED_DATA];
                $emitter->setData($cacheData);
            }

            return $emitter;
        };

        $container[EventEmitterInterface::class] =
            function (PsrContainerInterface $container) use ($getOrCreateEmitter): EventEmitterInterface {
                return call_user_func($getOrCreateEmitter, $container);
            };

        $container[EventDispatcherInterface::class] =
            function (PsrContainerInterface $container) use ($getOrCreateEmitter): EventDispatcherInterface {
                return call_user_func($getOrCreateEmitter, $container);
            };
    }
}

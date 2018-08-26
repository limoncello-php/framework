<?php namespace Limoncello\Tests\Commands\Data;

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

use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Commands
 */
class TestCliContainerConfiguratorGlobal implements ContainerConfiguratorInterface
{
    /** @var bool Flag if container was called */
    private static $isExecuted = false;

    /** @var array Container callable */
    public const CALLABLE_METHOD = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(ContainerInterface $container): void
    {
        static::$isExecuted = true;
    }

    /**
     * @return bool
     */
    public static function isExecuted(): bool
    {
        return static::$isExecuted;
    }

    /**
     * Clear `is executed` flag.
     */
    public static function clear(): void
    {
        static::$isExecuted = false;
    }
}

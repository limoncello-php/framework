<?php namespace Limoncello\Contracts\Commands;

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

use Closure;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Contracts
 */
interface MiddlewareInterface
{
    /**
     * Configurator's method name.
     */
    const MIDDLEWARE_METHOD_NAME = 'handle';

    /**
     * @param IoInterface        $inOut
     * @param Closure            $next
     * @param ContainerInterface $container
     *
     * @return void
     */
    public static function handle(
        IoInterface $inOut,
        Closure $next,
        ContainerInterface $container
    ): void;
}

<?php namespace Limoncello\Commands;

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

use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Commands
 */
trait CommandRoutesTrait
{
    /**
     * @param GroupInterface $group
     * @param string         $commandName
     * @param callable[]     $configurators
     *
     * @return GroupInterface
     */
    protected static function commandContainer(
        GroupInterface $group,
        string $commandName,
        ...$configurators
    ): GroupInterface {
        return $group->method(
            CommandConstants::HTTP_METHOD,
            $commandName,
            [static::class, 'handlerStub'],
            [
                RouteInterface::PARAM_REQUEST_FACTORY         => null,
                RouteInterface::PARAM_CONTAINER_CONFIGURATORS => $configurators,
            ]
        );
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function handlerStub(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        assert($routeParams || $container || $request);

        throw new LogicException('This handler should not be used.');
    }
}

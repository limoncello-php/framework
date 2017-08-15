<?php namespace Limoncello\Contracts\Routing;

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

use Closure;
use Iterator;

/**
 * @package Limoncello\Contracts
 */
interface GroupInterface
{
    /** Parameter key */
    const PARAM_NAME_PREFIX = 'as';

    /** Parameter key */
    const PARAM_MIDDLEWARE_LIST = 'middleware_list';

    /** Parameter key */
    const PARAM_CONTAINER_CONFIGURATORS = 'container_configurators';

    /** Parameter key */
    const PARAM_REQUEST_FACTORY = 'request_factory';

    /**
     * @return null|GroupInterface
     */
    public function parentGroup(): ?GroupInterface;

    /**
     * @return null|string
     */
    public function getName(): ?string;

    /**
     * @return string
     */
    public function getUriPrefix(): string;

    /**
     * @return callable[]
     */
    public function getMiddleware(): array;

    /**
     * @return callable[]
     */
    public function getContainerConfigurators(): array;

    /**
     * @return callable|null
     */
    public function getRequestFactory(): ?callable;

    /**
     * @return Iterator
     */
    public function getRoutes(): Iterator;

    /**
     * @param string  $prefix
     * @param Closure $closure
     * @param array   $parameters
     *
     * @return GroupInterface
     */
    public function group(string $prefix, Closure $closure, array $parameters = []): GroupInterface;

    /**
     * @param Closure        $closure
     * @param GroupInterface $group
     *
     * @return GroupInterface
     */
    public function addGroup(Closure $closure, GroupInterface $group): GroupInterface;

    /**
     * @param RouteInterface $route
     *
     * @return GroupInterface
     */
    public function addRoute(RouteInterface $route): GroupInterface;

    /**
     * @param string   $method
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function method(string $method, string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function get(string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function post(string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function put(string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function patch(string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return GroupInterface
     */
    public function delete(string $uriPath, callable $handler, array $parameters = []): GroupInterface;

    /**
     * @return bool
     */
    public function hasTrailSlash(): bool;
}

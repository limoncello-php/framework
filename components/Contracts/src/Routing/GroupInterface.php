<?php namespace Limoncello\Contracts\Routing;

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
     * @param array $middleware
     *
     * @return self
     */
    public function addMiddleware(array $middleware): self;

    /**
     * @return callable[]
     */
    public function getContainerConfigurators(): array;

    /**
     * @param callable[] $configurators
     *
     * @return self
     */
    public function addContainerConfigurators(array $configurators): self;

    /**
     * @return callable|null
     */
    public function getRequestFactory(): ?callable;

    /**
     * @return iterable
     */
    public function getRoutes(): iterable;

    /**
     * @param string  $prefix
     * @param Closure $closure
     * @param array   $parameters
     *
     * @return self
     */
    public function group(string $prefix, Closure $closure, array $parameters = []): self;

    /**
     * @param Closure        $closure
     * @param GroupInterface $group
     *
     * @return self
     */
    public function addGroup(Closure $closure, GroupInterface $group): self;

    /**
     * @param RouteInterface $route
     *
     * @return self
     */
    public function addRoute(RouteInterface $route): self;

    /**
     * @param string   $method
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function method(string $method, string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function get(string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function post(string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function put(string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function patch(string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return self
     */
    public function delete(string $uriPath, callable $handler, array $parameters = []): self;

    /**
     * @return bool
     */
    public function hasTrailSlash(): bool;
}

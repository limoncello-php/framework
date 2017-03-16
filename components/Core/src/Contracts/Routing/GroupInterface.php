<?php namespace Limoncello\Core\Contracts\Routing;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
 * @package Limoncello\Core
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
    public function parentGroup();

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @return null|string
     */
    public function getUriPrefix();

    /**
     * @return callable[]
     */
    public function getMiddleware();

    /**
     * @return callable[]
     */
    public function getContainerConfigurators();

    /**
     * @return callable|null
     */
    public function getRequestFactory();

    /**
     * @return Iterator
     */
    public function getRoutes();

    /**
     * @param string  $prefix
     * @param Closure $closure
     * @param array   $parameters
     *
     * @return $this
     */
    public function group($prefix, Closure $closure, array $parameters = []);

    /**
     * @param Closure        $closure
     * @param GroupInterface $group
     *
     * @return $this
     */
    public function addGroup(Closure $closure, GroupInterface $group);

    /**
     * @param RouteInterface $route
     *
     * @return $this
     */
    public function addRoute(RouteInterface $route);

    /**
     * @param string   $method
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function method($method, $uriPath, callable $handler, array $parameters = []);

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function get($uriPath, callable $handler, array $parameters = []);

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function post($uriPath, callable $handler, array $parameters = []);

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function put($uriPath, callable $handler, array $parameters = []);

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function patch($uriPath, callable $handler, array $parameters = []);

    /**
     * @param string   $uriPath
     * @param callable $handler
     * @param array    $parameters
     *
     * @return $this
     */
    public function delete($uriPath, callable $handler, array $parameters = []);

    /**
     * @return bool
     */
    public function hasTrailSlash();
}

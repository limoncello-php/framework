<?php namespace Limoncello\Core\Routing;

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
use Limoncello\Core\Contracts\Routing\GroupInterface;
use Limoncello\Core\Contracts\Routing\RouteInterface;
use Limoncello\Core\Routing\Traits\CallableTrait;
use Limoncello\Core\Routing\Traits\HasConfiguratorsTrait;
use Limoncello\Core\Routing\Traits\HasMiddlewareTrait;
use Limoncello\Core\Routing\Traits\HasRequestFactoryTrait;
use Limoncello\Core\Routing\Traits\UriTrait;

/**
 * @package Limoncello\Core
 */
abstract class BaseGroup implements GroupInterface
{
    use CallableTrait, UriTrait, HasConfiguratorsTrait, HasMiddlewareTrait, HasRequestFactoryTrait;

    /** Default value if routes should use request factory from its group */
    const USE_FACTORY_FROM_GROUP_DEFAULT = true;

    /**
     * @var null|GroupInterface
     */
    private $parent;

    /**
     * @var string
     */
    private $uriPrefix = '';

    /**
     * @var string|null
     */
    private $name = null;

    /**
     * @var array
     */
    private $items = [];

    /**
     * @var bool
     */
    private $trailSlashes = false;

    /**
     * @return BaseGroup
     */
    abstract protected function createGroup();

    /**
     * @param GroupInterface $parent
     *
     * @return $this
     */
    public function setParentGroup(GroupInterface $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param string $uriPrefix
     *
     * @return $this
     */
    public function setUriPrefix($uriPrefix)
    {
        $this->uriPrefix = $uriPrefix;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $trailSlashes
     *
     * @return $this
     */
    public function setHasTrailSlash($trailSlashes)
    {
        $this->trailSlashes = $trailSlashes;

        return $this;
    }

    /**
     * @return null|GroupInterface
     */
    public function parentGroup()
    {
        return $this->parent;
    }

    /**
     * @inheritdoc
     */
    public function getUriPrefix()
    {
        $parentPrefix = $this->getParentUriPrefix();
        if ($parentPrefix !== null) {
            return $this->normalizeUri($this->concatUri($parentPrefix, $this->uriPrefix), $this->hasTrailSlash());
        }

        return $this->normalizeUri($this->uriPrefix, $this->hasTrailSlash());
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getMiddleware()
    {
        $result = array_merge($this->getParentMiddleware(), $this->middleware);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getContainerConfigurators()
    {
        $result = array_merge($this->getParentConfigurators(), $this->configurators);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRequestFactory()
    {
        if ($this->isRequestFactorySet() === true) {
            return $this->requestFactory;
        }

        $parent = $this->parentGroup();
        $result = $parent === null ? $this->getDefaultRequestFactory() : $parent->getRequestFactory();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRoutes()
    {
        foreach ($this->items as $routeOrGroup) {
            if ($routeOrGroup instanceof RouteInterface) {
                /** @var RouteInterface $routeOrGroup */
                yield $routeOrGroup;
                continue;
            }

            /** @var GroupInterface $routeOrGroup */
            foreach ($routeOrGroup->getRoutes() as $route) {
                yield $route;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function group($prefix, Closure $closure, array $parameters = [])
    {
        list($middleware, $configurators, $factoryWasGiven, $requestFactory, $name) =
            $this->normalizeGroupParameters($parameters);

        $group = $this->createGroup()
            ->setUriPrefix($prefix)
            ->setMiddleware($middleware)
            ->setConfigurators($configurators)
            ->setName($name);

        $factoryWasGiven === false ?: $group->setRequestFactory($requestFactory);

        return $this->addGroup($closure, $group);
    }

    /**
     * @inheritdoc
     */
    public function addGroup(Closure $closure, GroupInterface $group)
    {
        $closure($group);

        $this->items[] = $group;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addRoute(RouteInterface $route)
    {
        $this->items[] = $route;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function method($method, $uriPath, callable $handler, array $parameters = [])
    {
        list($middleware, $configurators, $requestFactory, $useGroupFactory, $name) =
            $this->normalizeRouteParameters($parameters);

        $uriPath = $this->normalizeUri($uriPath, $this->hasTrailSlash());

        $route = $this->createRoute($this, $method, $uriPath)
            ->setUseGroupRequestFactory($useGroupFactory)
            ->setRequestFactory($requestFactory)
            ->setConfigurators($configurators)
            ->setMiddleware($middleware)
            ->setHandler($handler)
            ->setName($name);

        return $this->addRoute($route);
    }

    /**
     * @inheritdoc
     */
    public function get($uriPath, callable $handler, array $parameters = [])
    {
        return $this->method('GET', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function post($uriPath, callable $handler, array $parameters = [])
    {
        return $this->method('POST', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function put($uriPath, callable $handler, array $parameters = [])
    {
        return $this->method('PUT', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function patch($uriPath, callable $handler, array $parameters = [])
    {
        return $this->method('PATCH', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function delete($uriPath, callable $handler, array $parameters = [])
    {
        return $this->method('DELETE', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function hasTrailSlash()
    {
        return $this->trailSlashes;
    }

    /**
     * @param GroupInterface $group
     * @param string         $method
     * @param string         $uriPath
     *
     * @return Route
     */
    protected function createRoute(GroupInterface $group, $method, $uriPath)
    {
        $route = (new Route($group, $method, $uriPath));

        return $route;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function normalizeRouteParameters(array $parameters)
    {
        $defaults = [
            RouteInterface::PARAM_NAME                    => null,
            RouteInterface::PARAM_REQUEST_FACTORY         => null,
            RouteInterface::PARAM_FACTORY_FROM_GROUP      => self::USE_FACTORY_FROM_GROUP_DEFAULT,
            RouteInterface::PARAM_MIDDLEWARE_LIST         => [],
            RouteInterface::PARAM_CONTAINER_CONFIGURATORS => [],
        ];

        $result = array_merge($defaults, $parameters);

        $factoryWasGiven = array_key_exists(RouteInterface::PARAM_REQUEST_FACTORY, $parameters);

        return [
            $result[RouteInterface::PARAM_MIDDLEWARE_LIST],
            $result[RouteInterface::PARAM_CONTAINER_CONFIGURATORS],
            $result[RouteInterface::PARAM_REQUEST_FACTORY],
            $factoryWasGiven === true ? false : $result[RouteInterface::PARAM_FACTORY_FROM_GROUP],
            $result[RouteInterface::PARAM_NAME],
        ];
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function normalizeGroupParameters(array $parameters)
    {
        $defaults = [
            GroupInterface::PARAM_NAME_PREFIX             => null,
            GroupInterface::PARAM_REQUEST_FACTORY         => null,
            GroupInterface::PARAM_MIDDLEWARE_LIST         => [],
            GroupInterface::PARAM_CONTAINER_CONFIGURATORS => [],
        ];

        $result = array_merge($defaults, $parameters);

        $factoryWasGiven = array_key_exists(GroupInterface::PARAM_REQUEST_FACTORY, $parameters);

        return [
            $result[GroupInterface::PARAM_MIDDLEWARE_LIST],
            $result[GroupInterface::PARAM_CONTAINER_CONFIGURATORS],
            $factoryWasGiven,
            $result[GroupInterface::PARAM_REQUEST_FACTORY],
            $result[GroupInterface::PARAM_NAME_PREFIX],
        ];
    }

    /**
     * @return null|string
     */
    private function getParentUriPrefix()
    {
        $parent = $this->parentGroup();
        $result = $parent === null ? null : $parent->getUriPrefix();

        return $result;
    }

    /**
     * @return array
     */
    private function getParentMiddleware()
    {
        $parent = $this->parentGroup();
        $result = $parent === null ? [] : $parent->getMiddleware();

        return $result;
    }

    /**
     * @return array
     */
    private function getParentConfigurators()
    {
        $parent = $this->parentGroup();
        $result = $parent === null ? [] : $parent->getContainerConfigurators();

        return $result;
    }
}

<?php namespace Limoncello\Core\Routing;

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
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use Limoncello\Core\Reflection\CheckCallableTrait;
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
    use CallableTrait, UriTrait, HasConfiguratorsTrait, HasMiddlewareTrait, HasRequestFactoryTrait, CheckCallableTrait {
        CheckCallableTrait::checkPublicStaticCallable insteadof HasMiddlewareTrait;
        CheckCallableTrait::checkPublicStaticCallable insteadof HasConfiguratorsTrait;
        CheckCallableTrait::checkPublicStaticCallable insteadof HasRequestFactoryTrait;
    }

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
    abstract protected function createGroup(): BaseGroup;

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
     * @return BaseGroup
     */
    public function setUriPrefix(string $uriPrefix): BaseGroup
    {
        $this->uriPrefix = $uriPrefix;

        return $this;
    }

    /**
     * @param string|null $name
     *
     * @return BaseGroup
     */
    public function setName(string $name = null): BaseGroup
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param bool $trailSlashes
     *
     * @return BaseGroup
     */
    public function setHasTrailSlash(bool $trailSlashes): BaseGroup
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
    public function getUriPrefix(): string
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
    public function getMiddleware(): array
    {
        $result = array_merge($this->getParentMiddleware(), $this->middleware);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getContainerConfigurators(): array
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
    public function getRoutes(): Iterator
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
    public function group(string $prefix, Closure $closure, array $parameters = []): GroupInterface
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
    public function addGroup(Closure $closure, GroupInterface $group): GroupInterface
    {
        $closure($group);

        $this->items[] = $group;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addRoute(RouteInterface $route): GroupInterface
    {
        $this->items[] = $route;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function method(string $method, string $uriPath, callable $handler, array $parameters = []): GroupInterface
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
    public function get(string $uriPath, callable $handler, array $parameters = []): GroupInterface
    {
        return $this->method('GET', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function post(string $uriPath, callable $handler, array $parameters = []): GroupInterface
    {
        return $this->method('POST', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function put(string $uriPath, callable $handler, array $parameters = []): GroupInterface
    {
        return $this->method('PUT', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function patch(string $uriPath, callable $handler, array $parameters = []): GroupInterface
    {
        return $this->method('PATCH', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $uriPath, callable $handler, array $parameters = []): GroupInterface
    {
        return $this->method('DELETE', $uriPath, $handler, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function hasTrailSlash(): bool
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

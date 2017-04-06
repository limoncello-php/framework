<?php namespace Limoncello\Application\CoreSettings;

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

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use Generator;
use Limoncello\Application\Traits\SelectClassesTrait;
use Limoncello\Application\Traits\SelectClassImplementsTrait;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Application\MiddlewareInterface;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;
use Limoncello\Contracts\Provider\ProvidesMiddlewareInterface;
use Limoncello\Contracts\Provider\ProvidesRouteConfiguratorsInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouterInterface;
use Limoncello\Core\Application\BaseCoreSettings;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Limoncello\Core\Routing\Group;
use Limoncello\Core\Routing\Router;
use ReflectionMethod;

/**
 * @package Limoncello\Application
 */
class CoreSettings extends BaseCoreSettings
{
    use SelectClassesTrait, SelectClassImplementsTrait;

    /**
     * @var string
     */
    private $routesPath;

    /**
     * @var string
     */
    private $configuratorsPaths;

    /**
     * @var string[]
     */
    private $providerClasses;

    /**
     * CoreSettings constructor.
     *
     * @param string   $routesPath
     * @param string   $configuratorsPaths
     * @param string[] $providerClasses
     */
    public function __construct(string $routesPath, string $configuratorsPaths, array $providerClasses)
    {
        $this->routesPath         = $routesPath;
        $this->configuratorsPaths = $configuratorsPaths;
        $this->providerClasses    = $providerClasses;
    }

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        list ($generatorClass, $dispatcherClass) = $this->getGeneratorAndDispatcherClasses();
        assert($this->isValidRouterGeneratorAndDispatcher($generatorClass, $dispatcherClass) === true);

        $routesData = $this
            ->createRouter($generatorClass, $dispatcherClass)
            ->getCachedRoutes($this->addRoutes($this->createGroup()));

        $globalConfigurators = iterator_to_array($this->getGlobalContainerConfigurators(), false);
        $globalMiddleware    = iterator_to_array($this->getGlobalMiddleWareHandlers(), false);

        return [
            static::KEY_ROUTER_PARAMS                  => [
                static::KEY_ROUTER_PARAMS__GENERATOR  => $generatorClass,
                static::KEY_ROUTER_PARAMS__DISPATCHER => $dispatcherClass,
            ],
            static::KEY_ROUTES_DATA                    => $routesData,
            static::KEY_GLOBAL_CONTAINER_CONFIGURATORS => $globalConfigurators,
            static::KEY_GLOBAL_MIDDLEWARE              => $globalMiddleware,
        ];
    }

    /**
     * @return array
     */
    protected function getGeneratorAndDispatcherClasses(): array
    {
        return [GroupCountBasedGenerator::class, GroupCountBasedDispatcher::class];
    }

    /**
     * @return Generator
     */
    protected function getGlobalContainerConfigurators(): Generator
    {
        $interfaceName = ContainerConfiguratorInterface::class;
        foreach ($this->selectClasses($this->getConfiguratorsPaths(), $interfaceName) as $selectClass) {
            $configurator = [$selectClass, ContainerConfiguratorInterface::METHOD_NAME];
            assert($this->isValidContainerConfigurator($configurator) === true);
            yield $configurator;
        }

        $interfaceName = ProvidesContainerConfiguratorsInterface::class;
        foreach ($this->selectProviders($this->getProviderClasses(), $interfaceName) as $providerClass) {
            /** @var ProvidesContainerConfiguratorsInterface $providerClass */
            foreach ($providerClass::getContainerConfigurators() as $configurator) {
                assert($this->isValidContainerConfigurator($configurator) === true);
                yield $configurator;
            }
        }
    }

    /**
     * @param GroupInterface $group
     *
     * @return GroupInterface
     */
    protected function addRoutes(GroupInterface $group): GroupInterface
    {
        foreach ($this->selectClasses($this->getRoutesPath(), RoutesConfiguratorInterface::class) as $selectClass) {
            /** @var RoutesConfiguratorInterface $selectClass */
            $selectClass::configureRoutes($group);
        }

        $interfaceName = ProvidesRouteConfiguratorsInterface::class;
        foreach ($this->selectProviders($this->getProviderClasses(), $interfaceName) as $providerClass) {
            /** @var ProvidesRouteConfiguratorsInterface $providerClass */
            foreach ($providerClass::getRouteConfigurators() as $configurator) {
                assert($this->isValidRouteConfigurator($configurator) === true);
                $configurator($group);
            }
        }

        return $group;
    }

    /**
     * @return Generator
     */
    protected function getGlobalMiddleWareHandlers(): Generator
    {
        // select global middleware from routes
        foreach ($this->selectClasses($this->getRoutesPath(), RoutesConfiguratorInterface::class) as $selectClass) {
            /** @var RoutesConfiguratorInterface $selectClass */
            foreach ($selectClass::getMiddleware() as $middlewareClass) {
                $handler = [$middlewareClass, MiddlewareInterface::METHOD_NAME];
                assert($this->isValidMiddlewareHandler($handler) === true);
                yield $handler;
            }
        }

        // select global middleware from providers
        $interfaceName = ProvidesMiddlewareInterface::class;
        foreach ($this->selectProviders($this->getProviderClasses(), $interfaceName) as $providerClass) {
            /** @var ProvidesMiddlewareInterface $providerClass */
            foreach ($providerClass::getMiddleware() as $handler) {
                assert($this->isValidMiddlewareHandler($handler) === true);
                yield $handler;
            }
        }
    }

    /**
     * @return GroupInterface
     */
    protected function createGroup(): GroupInterface
    {
        return new Group();
    }

    /**
     * @param string $generatorClass
     * @param string $dispatcherClass
     *
     * @return RouterInterface
     */
    protected function createRouter(string $generatorClass, string $dispatcherClass): RouterInterface
    {
        return new Router($generatorClass, $dispatcherClass);
    }

    /**
     * @return string
     */
    protected function getConfiguratorsPaths(): string
    {
        return $this->configuratorsPaths;
    }

    /**
     * @return string[]
     */
    protected function getProviderClasses(): array
    {
        return $this->providerClasses;
    }

    /**
     * @return string
     */
    protected function getRoutesPath(): string
    {
        return $this->routesPath;
    }

    /**
     * @param string $generatorClass
     * @param string $dispatcherClass
     *
     * @return bool
     */
    private function isValidRouterGeneratorAndDispatcher(string $generatorClass, string $dispatcherClass): bool
    {
        assert($generatorClass && $dispatcherClass);

        // TODO add validation for router generator and dispatcher classes
        return true;
    }

    /**
     * @param $mightBeConfigurator
     *
     * @return bool
     */
    private function isValidContainerConfigurator($mightBeConfigurator): bool
    {
        return $this->isStaticCallableWithParameters($mightBeConfigurator, [ContainerInterface::class]);
    }

    /**
     * @param $mightBeHandler
     *
     * @return bool
     */
    private function isValidMiddlewareHandler($mightBeHandler): bool
    {
        //return $this->isStaticCallableWithParameters($mightBeHandler, [ContainerInterface::class]);

        // TODO add validation for middleware handler
        return is_callable($mightBeHandler);
    }

    /**
     * @param $mightBeConfigurator
     *
     * @return bool
     */
    private function isValidRouteConfigurator($mightBeConfigurator): bool
    {
        //return $this->isStaticCallableWithParameters($mightBeConfigurator, [ContainerInterface::class]);

        // TODO add validation for routes configurator
        return is_callable($mightBeConfigurator);
    }

    /**
     * @param mixed $mightBeCallable
     * @param array $parameterTypes
     *
     * @return bool
     */
    private function isStaticCallableWithParameters($mightBeCallable, array $parameterTypes = []): bool
    {
        $result = false;
        if (is_callable($mightBeCallable) === true) {
            $class = $method = null;
            if (is_string($mightBeCallable) === true) {
                list ($class, $method) = explode('::', $mightBeCallable);
            } elseif (is_array($mightBeCallable) === true && is_string($class = $mightBeCallable[0])) {
                $method = $mightBeCallable[1];
            }

            if ($class !== null && $method !== null) {
                $reflectionMethod = new ReflectionMethod($class, $method);
                $result           = $reflectionMethod->isStatic();
                $reflectionParams = $reflectionMethod->getParameters();
                $count            = count($reflectionParams);
                $result           = $result === true && count($parameterTypes) === $count;
                if ($result === true) {
                    for ($index = 0; $index < $count; $index++) {
                        $parameterType =$parameterTypes[$index];
                        $paramClass = $reflectionParams[$index]->getClass();
                        if (!$paramClass->implementsInterface($parameterType) && !$paramClass->isSubclassOf($parameterType)) {
                            $result = false;
                            break;
                        }
                    }
                }
            }
        }

        return $result;
    }
}

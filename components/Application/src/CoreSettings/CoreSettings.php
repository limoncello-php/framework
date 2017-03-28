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
use Limoncello\Application\Contracts\ContainerConfiguratorInterface;
use Limoncello\Application\Contracts\MiddlewareInterface;
use Limoncello\Application\Contracts\RoutesConfiguratorInterface;
use Limoncello\Application\Traits\SelectClassesTrait;
use Limoncello\Application\Traits\SelectClassImplementsTrait;
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;
use Limoncello\Contracts\Provider\ProvidesRouteConfiguratorsInterface;
use Limoncello\Core\Contracts\Application\CoreSettingsInterface;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Limoncello\Core\Routing\Group;
use Limoncello\Core\Routing\Router;
use ReflectionMethod;

/**
 * @package Limoncello\Application
 */
class CoreSettings implements CoreSettingsInterface
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
        // TODO check returned generator and dispatcher (correctness and compatibility)

        list ($routesData, $globalMiddleware) = $this->handleRouteConfigurators($generatorClass, $dispatcherClass);

        return [
            static::KEY_ROUTER_PARAMS                  => [
                static::KEY_ROUTER_PARAMS__GENERATOR  => $generatorClass,
                static::KEY_ROUTER_PARAMS__DISPATCHER => $dispatcherClass,
            ],
            static::KEY_ROUTES_DATA                    => $routesData,
            static::KEY_GLOBAL_CONTAINER_CONFIGURATORS => $this->getGlobalContainerConfigurators(),
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
     * @param string $generatorClass
     * @param string $dispatcherClass
     *
     * @return array
     */
    protected function handleRouteConfigurators(string $generatorClass, string $dispatcherClass): array
    {
        // TODO think of more flexible way of creating top level route group
        $routes = new Group();

        $middleware = [];
        foreach ($this->selectClasses($this->getRoutesPath(), RoutesConfiguratorInterface::class) as $selectClass) {
            /** @var RoutesConfiguratorInterface $selectClass */
            foreach ($selectClass::getMiddleware() as $middlewareClass) {
                // TODO check is valid middleware
                $middleware[] = [$middlewareClass, MiddlewareInterface::METHOD_NAME];
            }

            $selectClass::configureRoutes($routes);
        }

        $interfaceName = ProvidesRouteConfiguratorsInterface::class;
        foreach ($this->selectProviders($this->getProviderClasses(), $interfaceName) as $providerClass) {
            /** @var ProvidesRouteConfiguratorsInterface $providerClass */
            foreach ($providerClass::getRouteConfigurators() as $configurator) {
                // TODO check route configurator is valid
                $configurator($routes);
            }
        }

        // TODO think of more flexible way of creating routes data
        $routeData = (new Router($generatorClass, $dispatcherClass))->getCachedRoutes($routes);

        return [$routeData, $middleware];
    }

    /**
     * @return array
     */
    protected function getGlobalContainerConfigurators(): array
    {
        $path          = $this->getConfiguratorsPaths();
        $configurators = [];
        foreach ($this->selectClasses($path, ContainerConfiguratorInterface::class) as $selectClass) {
            $configurator = [$selectClass, ContainerConfiguratorInterface::METHOD_NAME];
            assert($this->isValidContainerConfigurator($configurator) === true);
            $configurators[] = $configurator;
        }

        $interfaceName = ProvidesContainerConfiguratorsInterface::class;
        foreach ($this->selectProviders($this->getProviderClasses(), $interfaceName) as $providerClass) {
            /** @var ProvidesContainerConfiguratorsInterface $providerClass */
            foreach ($providerClass::getContainerConfigurators() as $configurator) {
                assert($this->isValidContainerConfigurator($configurator) === true);
                $configurators[] = $configurator;
            }
        }

        return $configurators;
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
     * @param $mightBeConfigurator
     *
     * @return bool
     */
    private function isValidContainerConfigurator($mightBeConfigurator): bool
    {
        return $this->isStaticCallableWithParameters($mightBeConfigurator, [ContainerInterface::class]);
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

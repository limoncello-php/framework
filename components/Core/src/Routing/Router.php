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

use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Limoncello\Contracts\Routing\DispatcherInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use Limoncello\Contracts\Routing\RouterInterface;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Core
 */
class Router implements RouterInterface
{
    /**
     * @var false|array
     */
    private $cachedRoutes = false;

    /**
     * @var string
     */
    private $generatorClass;

    /**
     * @var string
     */
    private $dispatcherClass;

    /**
     * @var DispatcherInterface
     */
    private $dispatcher;

    /**
     * @param string $generatorClass
     * @param string $dispatcherClass
     */
    public function __construct($generatorClass, $dispatcherClass)
    {
        assert(class_exists($generatorClass) === true);
        assert(class_exists($dispatcherClass) === true);

        $this->generatorClass  = $generatorClass;
        $this->dispatcherClass = $dispatcherClass;
    }

    /**
     * @inheritdoc
     */
    public function getCachedRoutes(GroupInterface $group): array
    {
        $collector = $this->createRouteCollector();

        $routeIndex         = 0;
        $allRoutesInfo      = [];
        $namedRouteUriPaths = [];
        foreach ($group->getRoutes() as $route) {
            /** @var RouteInterface $route */
            $allRoutesInfo[] = [
                $route->getHandler(),
                $route->getMiddleware(),
                $route->getContainerConfigurators(),
                $route->getRequestFactory(),
            ];

            $routeName = $route->getName();
            if (empty($routeName) === false) {
                $namedRouteUriPaths[$routeName] = $route->getUriPath();
            }

            $collector->addRoute($route->getMethod(), $route->getUriPath(), $routeIndex);

            $routeIndex++;
        }

        return [$collector->getData(), $allRoutesInfo, $namedRouteUriPaths];
    }

    /**
     * @inheritdoc
     */
    public function loadCachedRoutes(array $cachedRoutes)
    {
        $this->cachedRoutes  = $cachedRoutes;
        list($collectorData) = $cachedRoutes;

        $this->dispatcher = $this->createDispatcher();
        $this->dispatcher->setData($collectorData);
    }

    /**
     * @inheritdoc
     */
    public function match(string $method, string $uriPath): array
    {
        $this->checkRoutesLoaded();

        $result = $this->dispatcher->dispatchRequest($method, $uriPath);

        // Array contains matching result code, allowed methods list, handler parameters list, handler,
        // middleware list, container configurators list, custom request factory.
        switch ($result[0]) {
            case DispatcherInterface::ROUTE_FOUND:
                $routeIndex    = $result[1];
                $handlerParams = $result[2];

                list(, $allRoutesInfo) = $this->cachedRoutes;
                $routeInfo             = $allRoutesInfo[$routeIndex];

                return array_merge([self::MATCH_FOUND, null, $handlerParams], $routeInfo);

            case DispatcherInterface::ROUTE_METHOD_NOT_ALLOWED:
                $allowedMethods = $result[1];

                return [self::MATCH_METHOD_NOT_ALLOWED, $allowedMethods, null, null, null, null, null];

            default:
                return [self::MATCH_NOT_FOUND, null, null, null, null, null, null];
        }
    }

    /**
     * @inheritdoc
     */
    public function getUriPath($routeName)
    {
        $this->checkRoutesLoaded();

        list(, , $namedRouteUriPaths) = $this->cachedRoutes;

        $result = array_key_exists($routeName, $namedRouteUriPaths) === true ? $namedRouteUriPaths[$routeName] : null;

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function get(
        ServerRequestInterface $request,
        $routeName,
        array $placeholders = [],
        array $queryParams = []
    ): string {
        $prefix = $this->getServerUriPrefix($request);
        $path   = $this->getUriPath($routeName);
        $path   = $this->replacePlaceholders($path, $placeholders);
        $url    = empty($queryParams) === true ? "$prefix$path" : "$prefix$path?" . http_build_query($queryParams);

        return $url;
    }

    /**
     * @return RouteCollector
     */
    protected function createRouteCollector()
    {
        return new RouteCollector(new Std(), new $this->generatorClass);
    }

    /**
     * @return DispatcherInterface
     */
    protected function createDispatcher()
    {
        return new $this->dispatcherClass;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getServerUriPrefix(ServerRequestInterface $request)
    {
        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();
        $uriHost   = $uri->getHost();
        $uriPort   = $uri->getPort();
        $prefix    = empty($uriPort) === true ? "$uriScheme://$uriHost" : "$uriScheme://$uriHost:$uriPort";

        return $prefix;
    }

    /**
     * @param string $path
     * @param array  $placeholders
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function replacePlaceholders($path, array $placeholders)
    {
        $result            = '';
        $inPlaceholder     = false;
        $curPlaceholder    = null;
        $inPlaceholderName = false;
        $pathLength        = strlen($path);
        for ($index = 0; $index < $pathLength; ++$index) {
            $character = $path[$index];
            switch ($character) {
                case '{':
                    $inPlaceholder     = true;
                    $inPlaceholderName = true;
                    break;
                case '}':
                    $result .= array_key_exists($curPlaceholder, $placeholders) === true ?
                        $placeholders[$curPlaceholder] : '{' . $curPlaceholder . '}';
                    $inPlaceholder     = false;
                    $curPlaceholder    = null;
                    $inPlaceholderName = false;
                    break;
                default:
                    if ($inPlaceholder === false) {
                        $result .= $character;
                    } else {
                        if ($character === ':') {
                            $inPlaceholderName = false;
                        } elseif ($inPlaceholderName === true) {
                            $curPlaceholder .= $character;
                        }
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * @return void
     */
    private function checkRoutesLoaded()
    {
        if ($this->cachedRoutes === false) {
            throw new LogicException('Routes are not loaded yet.');
        }
    }
}

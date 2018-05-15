<?php namespace Limoncello\Core\Routing;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use Limoncello\Contracts\Routing\DispatcherInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use Limoncello\Contracts\Routing\RouterInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Core
 */
class Router implements RouterInterface
{
    use ClassIsTrait;

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
    public function __construct(string $generatorClass, string $dispatcherClass)
    {
        assert(static::classImplements($generatorClass, DataGenerator::class));
        assert(static::classImplements($dispatcherClass, Dispatcher::class));

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
                assert(
                    $this->checkRouteNameIsUnique($route, $namedRouteUriPaths, $uriPath, $otherUri) === true,
                    "Route name `$routeName` from `$uriPath` has already been used for `$otherUri`."
                );
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
    public function loadCachedRoutes(array $cachedRoutes): void
    {
        $this->cachedRoutes = $cachedRoutes;
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
        list ($dispatchResult) = $result;
        switch ($dispatchResult) {
            case DispatcherInterface::ROUTE_FOUND:
                list (, $routeIndex, $handlerParams) = $result;
                list(, $allRoutesInfo) = $this->cachedRoutes;
                $routeInfo = $allRoutesInfo[$routeIndex];

                return array_merge([self::MATCH_FOUND, null, $handlerParams], $routeInfo);

            case DispatcherInterface::ROUTE_METHOD_NOT_ALLOWED:
                list (, $allowedMethods) = $result;

                return [self::MATCH_METHOD_NOT_ALLOWED, $allowedMethods, null, null, null, null, null];

            default:
                return [self::MATCH_NOT_FOUND, null, null, null, null, null, null];
        }
    }

    /**
     * @inheritdoc
     */
    public function getUriPath(string $routeName): ?string
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
        string $hostUri,
        string $routeName,
        array $placeholders = [],
        array $queryParams = []
    ): string {
        $path = $this->getUriPath($routeName);
        $path = $path === null ? $path : $this->replacePlaceholders($path, $placeholders);
        $url  = empty($queryParams) === true ? "$hostUri$path" : "$hostUri$path?" . http_build_query($queryParams);

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function getHostUri(ServerRequestInterface $request): string
    {
        $uri       = $request->getUri();
        $uriScheme = $uri->getScheme();
        $uriHost   = $uri->getHost();
        $uriPort   = $uri->getPort();
        $hostUri   = empty($uriPort) === true ? "$uriScheme://$uriHost" : "$uriScheme://$uriHost:$uriPort";

        return $hostUri;
    }

    /**
     * @return RouteCollector
     */
    protected function createRouteCollector(): RouteCollector
    {
        return new RouteCollector(new Std(), new $this->generatorClass);
    }

    /**
     * @return DispatcherInterface
     */
    protected function createDispatcher(): DispatcherInterface
    {
        return new $this->dispatcherClass;
    }

    /**
     * @param string $path
     * @param array  $placeholders
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function replacePlaceholders(string $path, array $placeholders): string
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
                    assert($inPlaceholder === false, 'Nested placeholders (e.g. `{{}}}` are not allowed.');
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
    private function checkRoutesLoaded(): void
    {
        if ($this->cachedRoutes === false) {
            throw new LogicException('Routes are not loaded yet.');
        }
    }

    /**
     * @param RouteInterface $route
     * @param array          $namedRouteUriPaths
     * @param null|string    $url
     * @param null|string    $otherUrl
     *
     * @return bool
     */
    private function checkRouteNameIsUnique(
        RouteInterface $route,
        array $namedRouteUriPaths,
        ?string &$url,
        ?string &$otherUrl
    ): bool {
        // check is really simple, the main purpose of the method is to prepare data for assert
        $isUnique = array_key_exists($route->getName(), $namedRouteUriPaths) === false;

        $url      = $isUnique === true ? null : $route->getUriPath();
        $otherUrl = $isUnique === true ? null : $namedRouteUriPaths[$route->getName()];

        return $isUnique;
    }
}

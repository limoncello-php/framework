<?php namespace Limoncello\Core\Application;

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
use Interop\Container\ContainerInterface;
use Limoncello\Core\Contracts\Application\ApplicationInterface;
use Limoncello\Core\Contracts\Application\SapiInterface;
use Limoncello\Core\Contracts\Routing\RouterConfigInterface;
use Limoncello\Core\Contracts\Routing\RouterInterface;
use Limoncello\Core\Routing\Router;
use Limoncello\Core\Routing\RouterConfig;
use LogicException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\ServerRequest;

/**
 * @package Limoncello\Core
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Application implements ApplicationInterface
{
    /** Method name for default request factory. */
    const FACTORY_METHOD = 'defaultRequestFactory';

    /**
     * @var SapiInterface|null
     */
    private $sapi;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @return ContainerInterface
     */
    abstract protected function createContainer();

    /**
     * @return array
     */
    abstract protected function getRoutesData();

    /**
     * @return callable[]
     */
    abstract protected function getGlobalMiddleware();

    /**
     * @param SapiInterface      $sapi
     * @param ContainerInterface $container
     *
     * @return void
     */
    abstract protected function setUpExceptionHandler(SapiInterface $sapi, ContainerInterface $container);

    /**
     * @inheritdoc
     */
    public function setSapi(SapiInterface $sapi): ApplicationInterface
    {
        $this->sapi = $sapi;

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function run()
    {
        if ($this->sapi === null) {
            throw new LogicException('SAPI not set.');
        }

        $userContainer = $this->createContainer();

        $this->setUpExceptionHandler($this->sapi, $userContainer);

        list($matchCode, $allowedMethods, $handlerParams, $handler, $routeMiddleware, $configurators, $requestFactory) =
            $this->getRouter()->match($this->sapi->getMethod(), $this->sapi->getUri()->getPath());

        if (empty($configurators) === false) {
            $this->configureUserContainer($userContainer, $configurators);
        }

        switch ($matchCode) {
            case RouterInterface::MATCH_FOUND:
                $handler = $this->createOrdinaryTerminalHandler($handler, $handlerParams, $userContainer);
                break;
            case RouterInterface::MATCH_METHOD_NOT_ALLOWED:
                $handler = $this->createMethodNotAllowedTerminalHandler($allowedMethods);
                break;
            default:
                assert($matchCode === RouterInterface::MATCH_NOT_FOUND);
                $handler = $this->createNotFoundTerminalHandler();
                break;
        }

        $globalMiddleware = $this->getGlobalMiddleware();
        $hasMiddleware    = empty($globalMiddleware) === false || empty($routeMiddleware) === false;

        $handler = $hasMiddleware === true ?
            $this->createMiddlewareChain($handler, $userContainer, $globalMiddleware, $routeMiddleware) : $handler;

        $request = $requestFactory === null && $hasMiddleware === false && $matchCode === RouterInterface::MATCH_FOUND ?
            null :
            $this->createRequest($this->sapi, $userContainer, $requestFactory ?? static::getDefaultRequestFactory());

        // send `Request` down all middleware (global then route's then terminal handler in `Controller` and back) and
        // then send `Response` to SAPI
        $this->sapi->handleResponse($this->handleRequest($handler, $request));
    }

    /**
     * @return callable
     */
    public static function getDefaultRequestFactory(): callable
    {
        return [static::class, static::FACTORY_METHOD];
    }

    /**
     * @param SapiInterface $sapi
     *
     * @return ServerRequestInterface
     */
    public static function defaultRequestFactory(SapiInterface $sapi): ServerRequestInterface
    {
        return new ServerRequest(
            $sapi->getServer(),
            $sapi->getFiles(),
            $sapi->getUri(),
            $sapi->getMethod(),
            $sapi->getRequestBody(),
            $sapi->getHeaders(),
            $sapi->getCookies(),
            $sapi->getQueryParams(),
            $sapi->getParsedBody(),
            $sapi->getProtocolVersion()
        );
    }

    /**
     * @param Closure               $handler
     * @param RequestInterface|null $request
     *
     * @return ResponseInterface
     */
    protected function handleRequest(Closure $handler, RequestInterface $request = null): ResponseInterface
    {
        $response = call_user_func($handler, $request);

        assert($response instanceof ResponseInterface);

        return $response;
    }

    /**
     * @param int   $status
     * @param array $headers
     *
     * @return ResponseInterface
     */
    protected function createEmptyResponse($status = 204, array $headers = []): ResponseInterface
    {
        $response = new EmptyResponse($status, $headers);

        return $response;
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter(): RouterInterface
    {
        if ($this->router === null) {
            $this->router = $this->createRouter();
            $this->router->loadCachedRoutes($this->getRoutesData());
        }

        return $this->router;
    }

    /**
     * @return array
     */
    protected function getRouterConfig(): array
    {
        return RouterConfig::DEFAULTS;
    }

    /**
     * @param ContainerInterface $container
     * @param callable[]         $configurators
     *
     * @return void
     */
    protected function configureUserContainer(ContainerInterface $container, array $configurators)
    {
        foreach ($configurators as $configurator) {
            call_user_func($configurator, $container);
        }
    }

    /**
     * @param Closure            $handler
     * @param ContainerInterface $userContainer
     * @param array|null         $globalMiddleware
     * @param array|null         $routeMiddleware
     *
     * @return Closure
     */
    protected function createMiddlewareChain(
        Closure $handler,
        ContainerInterface $userContainer,
        array $globalMiddleware,
        array $routeMiddleware = null
    ): Closure {
        $handler = $this->createMiddlewareChainImpl($handler, $userContainer, $routeMiddleware);
        $handler = $this->createMiddlewareChainImpl($handler, $userContainer, $globalMiddleware);

        return $handler;
    }

    /**
     * @param callable                    $handler
     * @param array                       $handlerParams
     * @param ContainerInterface          $container
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    protected function callControllerHandler(
        callable $handler,
        array $handlerParams,
        ContainerInterface $container,
        ServerRequestInterface $request = null
    ): ResponseInterface {
        return call_user_func($handler, $handlerParams, $container, $request);
    }

    /**
     * @return RouterInterface
     */
    private function createRouter(): RouterInterface
    {
        $config = $this->getRouterConfig();

        $generatorClass  = $config[RouterConfigInterface::KEY_GENERATOR];
        $dispatcherClass = $config[RouterConfigInterface::KEY_DISPATCHER];
        $router          = new Router($generatorClass, $dispatcherClass);

        return $router;
    }

    /**
     * @param SapiInterface      $sapi
     * @param ContainerInterface $userContainer
     * @param callable           $requestFactory
     *
     * @return ServerRequestInterface
     */
    private function createRequest(
        SapiInterface $sapi,
        ContainerInterface $userContainer,
        callable $requestFactory
    ): ServerRequestInterface {
        $request = call_user_func($requestFactory, $sapi, $userContainer);

        return $request;
    }

    /**
     * @param callable           $handler
     * @param array              $handlerParams
     * @param ContainerInterface $container
     *
     * @return Closure
     */
    private function createOrdinaryTerminalHandler(
        callable $handler,
        array $handlerParams,
        ContainerInterface $container
    ): Closure {
        return function (ServerRequestInterface $request = null) use ($handler, $handlerParams, $container) {
            return $this->callControllerHandler($handler, $handlerParams, $container, $request);
        };
    }

    /**
     * @param array $allowedMethods
     *
     * @return Closure
     */
    private function createMethodNotAllowedTerminalHandler(array $allowedMethods): Closure
    {
        // 405 Method Not Allowed
        return function () use ($allowedMethods) {
            return $this->createEmptyResponse(405, ['Accept' => implode(',', $allowedMethods)]);
        };
    }

    /**
     * @return Closure
     */
    private function createNotFoundTerminalHandler(): Closure
    {
        // 404 Not Found
        return function () {
            return $this->createEmptyResponse(404);
        };
    }

    /**
     * @param Closure            $handler
     * @param ContainerInterface $userContainer
     * @param array|null         $middleware
     *
     * @return Closure
     */
    private function createMiddlewareChainImpl(
        Closure $handler,
        ContainerInterface $userContainer,
        array $middleware = null
    ): Closure {
        $start = count($middleware) - 1;
        for ($index = $start; $index >= 0; $index--) {
            $handler = $this->createMiddlewareChainLink($handler, $middleware[$index], $userContainer);
        }

        return $handler;
    }

    /**
     * @param Closure            $next
     * @param callable           $middleware
     * @param ContainerInterface $userContainer
     *
     * @return Closure
     */
    private function createMiddlewareChainLink(
        Closure $next,
        callable $middleware,
        ContainerInterface $userContainer
    ): Closure {
        return function (ServerRequestInterface $request) use ($next, $middleware, $userContainer) {
            return call_user_func($middleware, $request, $next, $userContainer);
        };
    }
}

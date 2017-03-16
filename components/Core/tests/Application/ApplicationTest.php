<?php namespace Limoncello\Tests\Core\Application;

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
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use Interop\Container\ContainerInterface;
use Limoncello\Core\Application\Application;
use Limoncello\Core\Application\Sapi;
use Limoncello\Core\Contracts\Application\SapiInterface;
use Limoncello\Core\Contracts\Routing\GroupInterface;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use Limoncello\Core\Routing\Group;
use Limoncello\Core\Routing\Router;
use Limoncello\Tests\Core\TestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequestFactory;

/**
 * @package Limoncello\Tests\Core
 */
class ApplicationTest extends TestCase
{
    /** Filed name for storing response in SAPI mock */
    const FIELD_RESPONSE = 'response';

    /**
     * @var bool
     */
    private static $isConfiguratorCalled;

    /**
     * @var bool
     */
    private static $isGlobalMiddleware1Called;

    /**
     * @var bool
     */
    private static $isGlobalMiddleware2Called;

    /**
     * @var bool
     */
    private static $isRouteMiddlewareCalled;

    /**
     * @var bool
     */
    private static $isRequestFactoryCalled;

    /**
     * Set up tests.
     */
    protected function setUp()
    {
        parent::setUp();

        self::$isConfiguratorCalled      = false;
        self::$isGlobalMiddleware1Called = false;
        self::$isGlobalMiddleware2Called = false;
        self::$isRouteMiddlewareCalled   = false;
        self::$isRequestFactoryCalled    = false;
    }

    /**
     * Test page.
     */
    public function testHomeIndex()
    {
        /** @var Application $app */
        /** @var SapiInterface $sapi */
        list($app, $sapi) = $this->createApp('GET', '/', $this->getRoutesData());
        $app->run();

        /** @var ResponseInterface $response */
        $response = $sapi->{self::FIELD_RESPONSE};
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('home index', $this->getText($response->getBody()));

        $this->assertFalse(self::$isConfiguratorCalled);
        $this->assertTrue(self::$isGlobalMiddleware1Called);
        $this->assertFalse(self::$isRouteMiddlewareCalled);
        $this->assertFalse(self::$isRequestFactoryCalled);
    }

    /**
     * Test page.
     */
    public function testPostsIndex()
    {
        /** @var Application $app */
        /** @var SapiInterface $sapi */
        list($app, $sapi) = $this->createApp('POST', '/posts', $this->getRoutesData());
        $app->run();

        /** @var ResponseInterface $response */
        $response = $sapi->{self::FIELD_RESPONSE};
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('post create', $this->getText($response->getBody()));

        $this->assertTrue(self::$isConfiguratorCalled);
        $this->assertTrue(self::$isGlobalMiddleware1Called);
        $this->assertTrue(self::$isRouteMiddlewareCalled);
        $this->assertTrue(self::$isRequestFactoryCalled);
    }

    /**
     * Test page.
     */
    public function test404()
    {
        /** @var Application $app */
        /** @var SapiInterface $sapi */
        list($app, $sapi) = $this->createApp('GET', '/posts/XYZ', $this->getRoutesData());
        $app->run();

        /** @var ResponseInterface $response */
        $response = $sapi->{self::FIELD_RESPONSE};
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(0, $response->getBody()->getSize());

        $this->assertFalse(self::$isConfiguratorCalled);
        $this->assertTrue(self::$isGlobalMiddleware1Called);
        $this->assertFalse(self::$isRouteMiddlewareCalled);
        $this->assertFalse(self::$isRequestFactoryCalled);
    }

    /**
     * Test page.
     */
    public function test405()
    {
        /** @var Application $app */
        /** @var SapiInterface $sapi */
        list($app, $sapi) = $this->createApp('INFO', '/', $this->getRoutesData());
        $app->run();

        /** @var ResponseInterface $response */
        $response = $sapi->{self::FIELD_RESPONSE};
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals(0, $response->getBody()->getSize());
        $this->assertEquals(['GET'], $response->getHeader('Accept'));

        $this->assertFalse(self::$isConfiguratorCalled);
        $this->assertTrue(self::$isGlobalMiddleware1Called);
        $this->assertFalse(self::$isRouteMiddlewareCalled);
        $this->assertFalse(self::$isRequestFactoryCalled);
    }

    /**
     * Test Application not configured.
     *
     * @expectedException \LogicException
     */
    public function testSapiNotSet()
    {
        $app = Mockery::mock(Application::class)->makePartial()->shouldAllowMockingProtectedMethods();

        /** @var Application $app */

        $app->run();
    }

    /**
     * Test user can ask not to create request.
     */
    public function testNoRequestInController()
    {
        /** @var Application $app */
        /** @var SapiInterface $sapi */
        list($app, $sapi) = $this->createApp('GET', '/', $this->getRoutesDataForNoRequest(), []);
        $app->run();

        /** @var ResponseInterface $response */
        $response = $sapi->{self::FIELD_RESPONSE};
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('home index', $this->getText($response->getBody()));

        $this->assertTrue(self::$isConfiguratorCalled);
        $this->assertFalse(self::$isGlobalMiddleware1Called);
        $this->assertFalse(self::$isRouteMiddlewareCalled);
        $this->assertFalse(self::$isRequestFactoryCalled);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $routesData
     * @param array  $globalMiddleware
     *
     * @return Application
     */
    private function createApp(
        $method,
        $uri,
        array $routesData,
        array $globalMiddleware = [[self::class, 'globalMiddlewareItem1'], [self::class, 'globalMiddlewareItem2']]
    ) {
        $container = Mockery::mock(ContainerInterface::class);

        $server['REQUEST_URI']    = $uri;
        $server['REQUEST_METHOD'] = $method;

        /** @var EmitterInterface $emitter */
        $emitter = Mockery::mock(EmitterInterface::class);

        $sapi = Mockery::mock(Sapi::class, [$emitter, $server])->makePartial();
        $sapi->shouldReceive('getMethod')->zeroOrMoreTimes()->withAnyArgs()->passthru();
        $sapi->shouldReceive('handleResponse')->once()->withAnyArgs()->andReturnUsing(function ($response) use ($sapi) {
            $sapi->{self::FIELD_RESPONSE} = $response;
        });

        $app = Mockery::mock(Application::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $app->shouldReceive('getRoutesData')->zeroOrMoreTimes()->withNoArgs()->andReturn($routesData);
        $app->shouldReceive('createContainer')->zeroOrMoreTimes()->withNoArgs()->andReturn($container);
        $app->shouldReceive('getGlobalMiddleware')->zeroOrMoreTimes()->withNoArgs()->andReturn($globalMiddleware);
        $app->shouldReceive('setUpExceptionHandler')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        /** @var Application $app */
        /** @var SapiInterface $sapi */
        $app->setSapi($sapi);

        return [$app, $sapi];
    }

    /**
     * @return array
     */
    private function getRoutesData()
    {
        $group = (new Group())
            ->get('/', [self::class, 'homeIndex'])
            ->group('posts', function (GroupInterface $group) {
                $group
                    ->post('', [self::class, 'postsCreate'], [
                        GroupInterface::PARAM_MIDDLEWARE_LIST         => [self::class . '::createPostMiddleware'],
                        GroupInterface::PARAM_CONTAINER_CONFIGURATORS => [self::class . '::createPostConfigurator'],
                        GroupInterface::PARAM_REQUEST_FACTORY         => self::class . '::createRequest',
                    ]);
            });

        $router     = new Router(GroupCountBasedGenerator::class, GroupCountBasedDispatcher::class);
        $routesData = $router->getCachedRoutes($group);

        return $routesData;
    }

    /**
     * @return array
     */
    private function getRoutesDataForNoRequest()
    {
        $group = (new Group([
            GroupInterface::PARAM_CONTAINER_CONFIGURATORS => [self::class . '::createPostConfigurator'],
            GroupInterface::PARAM_REQUEST_FACTORY         => null,
        ]))->get('/', [self::class, 'homeIndexNoRequest']);

        $router     = new Router(GroupCountBasedGenerator::class, GroupCountBasedDispatcher::class);
        $routesData = $router->getCachedRoutes($group);

        return $routesData;
    }

    /**
     * @return ServerRequestInterface
     */
    public static function createRequest()
    {
        // dummy for tests
        self::$isRequestFactoryCalled = true;

        return ServerRequestFactory::fromGlobals();
    }

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     * @param ContainerInterface     $container
     *
     * @return ResponseInterface
     */
    public static function globalMiddlewareItem1(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ) {
        self::assertFalse(self::$isGlobalMiddleware1Called);
        self::assertFalse(self::$isGlobalMiddleware2Called);

        $container ?: null;

        // dummy for tests
        self::$isGlobalMiddleware1Called = true;

        return $next($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     *
     * @return ResponseInterface
     */
    public static function globalMiddlewareItem2(ServerRequestInterface $request, Closure $next)
    {
        self::assertTrue(self::$isGlobalMiddleware1Called);
        self::assertFalse(self::$isGlobalMiddleware2Called);

        self::$isGlobalMiddleware2Called = true;

        return $next($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     * @param ContainerInterface     $container
     *
     * @return ResponseInterface
     */
    public static function createPostMiddleware(
        ServerRequestInterface $request,
        Closure $next,
        ContainerInterface $container
    ) {

        $container ?: null;

        // dummy for tests
        self::$isRouteMiddlewareCalled = true;

        return $next($request);
    }

    /**
     * Container configurator.
     *
     * @param ContainerInterface $container
     *
     * @return void
     */
    public static function createPostConfigurator(ContainerInterface $container)
    {
        // dummy for tests
        $container ?: null;

        self::$isConfiguratorCalled = true;
    }

    /**
     * @param array                       $params
     * @param ContainerInterface          $container
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    public static function homeIndex(
        array $params,
        ContainerInterface $container,
        ServerRequestInterface $request = null
    ) {
        // dummy for tests

        $params && $request && $container ?: null;

        return new TextResponse('home index');
    }

    /**
     * @return ResponseInterface
     */
    public static function postsCreate()
    {
        // dummy for tests
        return new TextResponse('post create');
    }

    /**
     * @param array                       $params
     * @param ContainerInterface          $container
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    public static function homeIndexNoRequest(
        array $params,
        ContainerInterface $container,
        ServerRequestInterface $request = null
    ) {
        // we didn't specified any middleware and request factory was set to null thus request should be null
        self::assertNull($request);

        // dummy for tests

        $params && $request && $container ?: null;

        return new TextResponse('home index');
    }

    /**
     * @param StreamInterface $stream
     *
     * @return string
     */
    private function getText(StreamInterface $stream)
    {
        $text = $stream->read($stream->getSize());

        return $text;
    }
}

<?php namespace Limoncello\Tests\Core\Routing;

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

use Closure;
use Exception;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Routing\GroupInterface as GI;
use Limoncello\Contracts\Routing\RouteInterface as RI;
use Limoncello\Core\Application\Application;
use Limoncello\Core\Routing\Group;
use Limoncello\Core\Routing\Traits\CallableTrait;
use Limoncello\Tests\Core\TestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;

/**
 * @package Limoncello\Tests\Core
 */
class GroupTest extends TestCase
{
    use CallableTrait;

    /**
     * Test basic groups.
     *
     * @throws Exception
     */
    public function testBasicGroups(): void
    {
        $topGroup = (new Group([
            GI::PARAM_NAME_PREFIX => 'all::',
        ]));

        $topGroup
            ->get('/', [self::class, 'homeIndex'])
            ->group('posts', function (GI $group) {
                $group
                    ->get('', [self::class, 'postsIndex'])
                    ->post('', [self::class, 'postsCreate'], [RI::PARAM_NAME => 'createPost'])
                    ->delete('{id}', [self::class, 'postsDelete'])
                    ->group('edit', function (GI $group) {
                        $group->get('', [self::class, 'postEdit'], [RI::PARAM_NAME => 'editPost']);
                    }, [GI::PARAM_NAME_PREFIX => 'edit::']);
            })
            ->post('', [self::class, 'createNews']);

        /** @var RI[] $routes */
        $routes = [];
        foreach ($topGroup->getRoutes() as $route) {
            /** @var RI $route */
            $routes[] = $route;
        }
        /** @noinspection PhpParamsInspection */
        $this->assertCount(6, $routes);

        $this->assertEquals('GET', $routes[0]->getMethod());
        $this->assertEquals('/', $routes[0]->getUriPath());
        $this->assertEquals([self::class, 'homeIndex'], $routes[0]->getHandler());
        $this->assertEmpty($routes[0]->getMiddleware());
        $this->assertEmpty($routes[0]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[0]->getRequestFactory());
        $this->assertEquals(null, $routes[0]->getName());

        $this->assertEquals('GET', $routes[1]->getMethod());
        $this->assertEquals('/posts', $routes[1]->getUriPath());
        $this->assertEquals([self::class, 'postsIndex'], $routes[1]->getHandler());
        $this->assertEmpty($routes[1]->getMiddleware());
        $this->assertEmpty($routes[1]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[1]->getRequestFactory());
        $this->assertEquals(null, $routes[1]->getName());

        $this->assertEquals('POST', $routes[2]->getMethod());
        $this->assertEquals('/posts', $routes[2]->getUriPath());
        $this->assertEquals([self::class, 'postsCreate'], $routes[2]->getHandler());
        $this->assertEmpty($routes[2]->getMiddleware());
        $this->assertEmpty($routes[2]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[2]->getRequestFactory());
        $this->assertEquals('all::createPost', $routes[2]->getName());

        $this->assertEquals('DELETE', $routes[3]->getMethod());
        $this->assertEquals('/posts/{id}', $routes[3]->getUriPath());
        $this->assertEquals([self::class, 'postsDelete'], $routes[3]->getHandler());
        $this->assertEmpty($routes[3]->getMiddleware());
        $this->assertEmpty($routes[3]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[3]->getRequestFactory());
        $this->assertEquals(null, $routes[3]->getName());

        $this->assertEquals('GET', $routes[4]->getMethod());
        $this->assertEquals('/posts/edit', $routes[4]->getUriPath());
        $this->assertEquals([self::class, 'postEdit'], $routes[4]->getHandler());
        $this->assertEmpty($routes[4]->getMiddleware());
        $this->assertEmpty($routes[4]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[4]->getRequestFactory());
        $this->assertEquals('all::edit::editPost', $routes[4]->getName());

        $this->assertEquals('POST', $routes[5]->getMethod());
        $this->assertEquals('/', $routes[5]->getUriPath());
        $this->assertEquals([self::class, 'createNews'], $routes[5]->getHandler());
        $this->assertEmpty($routes[5]->getMiddleware());
        $this->assertEmpty($routes[5]->getContainerConfigurators());
        $this->assertEquals([Application::class, Application::FACTORY_METHOD], $routes[5]->getRequestFactory());
        $this->assertEquals(null, $routes[5]->getName());
    }

    /**
     * Test advanced groups.
     *
     * @throws Exception
     */
    public function testAdvancedGroups(): void
    {
        $topGroup = (new Group([
            GI::PARAM_MIDDLEWARE_LIST         => [self::class . '::topMiddlewareHandler'],
            GI::PARAM_CONTAINER_CONFIGURATORS => [[self::class, 'topContainerConfigurator']],
            GI::PARAM_REQUEST_FACTORY         => null,
        ]))->setUriPrefix('/api/v1');

        $topGroup
            ->get('/', [self::class, 'homeIndex'])
            ->group(
                '/posts',
                function (GI $group) {
                    $group
                        ->put('/', [self::class, 'postsCreate'])
                        ->patch('/', self::class . '::postsUpdate', [
                            RI::PARAM_MIDDLEWARE_LIST         => [self::class . '::methodMiddlewareHandler'],
                            RI::PARAM_CONTAINER_CONFIGURATORS => [self::class . '::methodContainerConfigurator'],
                            RI::PARAM_REQUEST_FACTORY         => self::class . '::patchRequestFactory',
                        ])->get('/search', [self::class, 'postsSearch'])
                        ->delete('/kill-all/', [self::class, 'postsDeleteAll']);

                    // group Middleware could be set via params as for the top group or with dedicated methods
                    $group->addMiddleware([self::class . '::groupMiddlewareHandler']);
                    // container configurators can be added as well
                    $group->addContainerConfigurators([self::class . '::groupContainerConfigurator']);
                },
                [
                    GI::PARAM_REQUEST_FACTORY => [self::class, 'requestFactory'],
                ]
            )
            ->post('', [self::class, 'createNews']);

        /** @var RI[] $routes */
        $routes = [];
        foreach ($topGroup->getRoutes() as $route) {
            /** @var RI $route */
            $routes[] = $route;
        }
        /** @noinspection PhpParamsInspection */
        $this->assertCount(6, $routes);

        $this->assertEquals('GET', $routes[0]->getMethod());
        $this->assertEquals('/api/v1', $routes[0]->getUriPath());
        $this->assertEquals([self::class, 'homeIndex'], $routes[0]->getHandler());
        $this->assertEquals([self::class . '::topMiddlewareHandler'], $routes[0]->getMiddleware());
        $this->assertEquals([[self::class, 'topContainerConfigurator']], $routes[0]->getContainerConfigurators());
        $this->assertEquals(null, $routes[0]->getRequestFactory());

        $this->assertEquals('PUT', $routes[1]->getMethod());
        $this->assertEquals('/api/v1/posts', $routes[1]->getUriPath());
        $this->assertEquals([self::class, 'postsCreate'], $routes[1]->getHandler());
        $this->assertEquals(
            [self::class . '::topMiddlewareHandler', self::class . '::groupMiddlewareHandler'],
            $routes[1]->getMiddleware()
        );
        $this->assertEquals(
            [[self::class, 'topContainerConfigurator'], self::class . '::groupContainerConfigurator'],
            $routes[1]->getContainerConfigurators()
        );
        $this->assertEquals([self::class, 'requestFactory'], $routes[1]->getRequestFactory());

        $this->assertEquals('PATCH', $routes[2]->getMethod());
        $this->assertEquals('/api/v1/posts', $routes[2]->getUriPath());
        $this->assertEquals(self::class . '::postsUpdate', $routes[2]->getHandler());
        $this->assertEquals(
            [
                self::class . '::topMiddlewareHandler',
                self::class . '::groupMiddlewareHandler',
                self::class . '::methodMiddlewareHandler'
            ],
            $routes[2]->getMiddleware()
        );
        $this->assertEquals(
            [
                [self::class, 'topContainerConfigurator'],
                self::class . '::groupContainerConfigurator',
                self::class . '::methodContainerConfigurator'
            ],
            $routes[2]->getContainerConfigurators()
        );
        $this->assertEquals(self::class . '::patchRequestFactory', $routes[2]->getRequestFactory());

        $this->assertEquals('GET', $routes[3]->getMethod());
        $this->assertEquals('/api/v1/posts/search', $routes[3]->getUriPath());

        $this->assertEquals('DELETE', $routes[4]->getMethod());
        $this->assertEquals('/api/v1/posts/kill-all', $routes[4]->getUriPath());

        $this->assertEquals('POST', $routes[5]->getMethod());
        $this->assertEquals('/api/v1', $routes[5]->getUriPath());
    }

    /**
     * Test trailing slashes enabled.
     *
     * @throws Exception
     */
    public function testEnabledTrailingSlashes(): void
    {
        $topGroup = (new Group())->setUriPrefix('/')->setHasTrailSlash(true);

        $topGroup
            ->get('/', [self::class, 'homeIndex'])
            ->group('posts', function (GI $group) {
                $group
                    ->get('', [self::class, 'homeIndex'])
                    ->post('/', [self::class, 'homeIndex'])
                    ->delete('{id}', [self::class, 'homeIndex'])
                    ->patch('/{id}/', [self::class, 'homeIndex'])
                    ->group('edit', function (GI $group) {
                        $group->get('', [self::class, 'homeIndex']);
                        $group->post('/', [self::class, 'homeIndex']);
                    });
            })
            ->group('comments/', function (GI $group) {
                $group
                    ->get('', [self::class, 'homeIndex']);
            })
            ->post('', [self::class, 'homeIndex']);

        /** @var RI[] $routes */
        $routes = [];
        foreach ($topGroup->getRoutes() as $route) {
            /** @var RI $route */
            $routes[] = $route;
        }
        /** @noinspection PhpParamsInspection */
        $this->assertCount(9, $routes);

        $this->assertEquals('/', $routes[0]->getUriPath());
        $this->assertEquals('/posts/', $routes[1]->getUriPath());
        $this->assertEquals('/posts/', $routes[2]->getUriPath());
        $this->assertEquals('/posts/{id}/', $routes[3]->getUriPath());
        $this->assertEquals('/posts/{id}/', $routes[4]->getUriPath());
        $this->assertEquals('/posts/edit/', $routes[5]->getUriPath());
        $this->assertEquals('/posts/edit/', $routes[6]->getUriPath());
        $this->assertEquals('/comments/', $routes[7]->getUriPath());
        $this->assertEquals('/', $routes[8]->getUriPath());
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidCallable1(): void
    {
        (new Group())->setMiddleware(['NonExistingClass::method']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidCallable2(): void
    {
        (new Group())->setConfigurators(['NonExistingClass::method']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testInvalidCallable3(): void
    {
        (new Group())->setRequestFactory(function () {
        });
    }

    /**
     * @expectedException \LogicException
     *
     * @throws ReflectionException
     */
    public function testInvalidCallable4(): void
    {
        (new Group())->get('/', function () {
        });
    }

    /**
     * @expectedException \LogicException
     *
     * @throws ReflectionException
     */
    public function testInvalidCallable5(): void
    {
        (new Group())->get(
            '/',
            self::class . '::homeIndex',
            [GI::PARAM_MIDDLEWARE_LIST => ['NonExistingClass::method']]
        );
    }

    /**
     * @expectedException \LogicException
     *
     * @throws ReflectionException
     */
    public function testInvalidCallable6(): void
    {
        (new Group())->get(
            '/',
            self::class . '::homeIndex',
            [GI::PARAM_CONTAINER_CONFIGURATORS => ['NonExistingClass::method']]
        );
    }

    /**
     * @expectedException \LogicException
     *
     * @throws ReflectionException
     */
    public function testInvalidCallable7(): void
    {
        (new Group())->get(
            '/',
            self::class . '::homeIndex',
            [
                GI::PARAM_REQUEST_FACTORY => function () {
                }
            ]
        );
    }

    /**
     * @return ResponseInterface
     */
    public static function homeIndex(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsIndex(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsCreate(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsUpdate(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postEdit(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsSearch(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsDelete(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function postsDeleteAll(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public static function createNews(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    /**
     * @param SapiInterface $sapi
     *
     * @return ServerRequestInterface
     */
    public static function requestFactory(SapiInterface $sapi): ServerRequestInterface
    {
        $sapi ?: null;

        /** @var ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);

        return $request;
    }

    /**
     * @param SapiInterface $sapi
     *
     * @return ServerRequestInterface
     */
    public static function patchRequestFactory(SapiInterface $sapi): ServerRequestInterface
    {
        $sapi ?: null;

        /** @var ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     *
     * @return ResponseInterface
     */
    public static function topMiddlewareHandler(ServerRequestInterface $request, Closure $next): ResponseInterface
    {
        assert($request || $next);

        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    public static function topContainerConfigurator()
    {
        // dummy for tests
    }

    /**
     * @return ResponseInterface
     */
    public static function groupMiddlewareHandler(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    public static function groupContainerConfigurator()
    {
        // dummy for tests
    }

    /**
     * @return ResponseInterface
     */
    public static function methodMiddlewareHandler(): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        return $response;
    }

    public static function methodContainerConfigurator(): void
    {
        // dummy for tests
    }
}

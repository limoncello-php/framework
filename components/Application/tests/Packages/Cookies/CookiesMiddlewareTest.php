<?php namespace Limoncello\Tests\Application\Packages\Cookies;

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
use Limoncello\Application\Contracts\Cookie\CookieFunctionsInterface;
use Limoncello\Application\Cookies\CookieFunctions;
use Limoncello\Application\Cookies\CookieJar;
use Limoncello\Application\Packages\Cookies\CookieMiddleware;
use Limoncello\Container\Container;
use Limoncello\Contracts\Cookies\CookieJarInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Application
 */
class CookiesMiddlewareTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected const SET_COOKIE_CALLABLE = [self::class, 'setCookie'];

    /**
     * @inheritdoc
     */
    protected const SET_RAW_COOKIE_CALLABLE = [self::class, 'setRawCookie'];

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Closure
     */
    private $next;

    /**
     * @var CookieJarInterface
     */
    private $cookieJar;

    /**
     * @var CookieFunctionsInterface
     */
    private $cookieFunctions;

    /**
     * @var array
     */
    private $cookieArgs;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->request = Mockery::mock(ServerRequestInterface::class);
        $responseMock  = Mockery::mock(ResponseInterface::class);
        $this->next    = function () use ($responseMock) {
            return $responseMock;
        };

        $this->cookieArgs = [];

        $this->cookieJar       = new CookieJar('/path', 'domain', false, false, false);
        $this->cookieFunctions = (new CookieFunctions())
            ->setWriteCookieCallable([$this, 'setCookie'])
            ->setWriteRawCookieCallable([$this, 'setRawCookie']);

        $container                                  = new Container();
        $container[CookieJarInterface::class]       = $this->cookieJar;
        $container[CookieFunctionsInterface::class] = $this->cookieFunctions;
        $this->container                            = $container;
    }

    /**
     * Test setting cookies.
     */
    public function testSettingCookies()
    {
        $this->cookieJar->create('raw')->setValue('raw_value')->setAsRaw();
        $this->cookieJar->create('not_raw')->setValue('not_raw_value')->setAsNotRaw();

        CookieMiddleware::handle($this->request, $this->next, $this->container);

        $this->assertEquals([
            [true, 'raw', 'raw_value', 0, '/path', 'domain', false, false],
            [false, 'not_raw', 'not_raw_value', 0, '/path', 'domain', false, false],
        ], $this->cookieArgs);
    }

    /**
     * @param array $args
     */
    public function setCookie(...$args): void
    {
        $this->setCookieInt(false, $args);
    }

    /**
     * @param array $args
     */
    public function setRawCookie(...$args): void
    {
        $this->setCookieInt(true, $args);
    }

    /**
     * @param bool  $isRaw
     * @param array $args
     */
    private function setCookieInt(bool $isRaw, array $args): void
    {
        $this->cookieArgs[] = array_merge([$isRaw], $args);
    }
}

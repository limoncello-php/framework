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
use Limoncello\Application\Cookies\CookieJar;
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

        $this->cookieJar = new CookieJar('/path', 'domain', false, false, false);

        $container                            = new Container();
        $container[CookieJarInterface::class] = $this->cookieJar;
        $this->container                      = $container;
    }

    /**
     * Test setting cookies.
     */
    public function testSettingCookies()
    {
        $this->cookieJar->create('raw')->setValue('raw_value')->setAsRaw();
        $this->cookieJar->create('not_raw')->setValue('not_raw_value')->setAsNotRaw();

        TestCookieMiddleware::reset();
        TestCookieMiddleware::handle($this->request, $this->next, $this->container);

        $setCookieInputs = TestCookieMiddleware::getInputs();
        $this->assertEquals([
            [true, 'raw', 'raw_value', 0, '/path', 'domain', false, false],
            [false, 'not_raw', 'not_raw_value', 0, '/path', 'domain', false, false],
        ], $setCookieInputs);
    }
}

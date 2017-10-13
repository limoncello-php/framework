<?php namespace Limoncello\Tests\Application\Packages\Session;

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
use Limoncello\Application\Contracts\Session\SessionFunctionsInterface;
use Limoncello\Application\Contracts\Settings\CacheSettingsProviderInterface;
use Limoncello\Application\Packages\Session\SessionMiddleware;
use Limoncello\Application\Packages\Session\SessionSettings;
use Limoncello\Application\Session\SessionFunctions;
use Limoncello\Container\Container;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Application
 */
class SessionMiddlewareTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Closure
     */
    private $next;

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
    }

    /**
     * Test setting cookies.
     */
    public function testSettingCookies()
    {
        $sessionStartCalled = false;
        $sessionCloseCalled = false;

        $settingsFunctions = new SessionFunctions();
        $settingsFunctions->setStartCallable(function () use (&$sessionStartCalled) {
            $sessionStartCalled = true;
        });
        $settingsFunctions->setWriteCloseCallable(function () use (&$sessionCloseCalled) {
            $sessionCloseCalled = true;
        });

        /** @var Mock $providerMock */
        $providerMock = Mockery::mock(SettingsProviderInterface::class);
        $providerMock->shouldReceive('get')->once()->with(SessionSettings::class)
            ->andReturn((new SessionSettings())->get());

        $container                                   = new Container();
        $container[SessionFunctionsInterface::class] = $settingsFunctions;
        $container[SettingsProviderInterface::class] = $providerMock;

        SessionMiddleware::handle($this->request, $this->next, $container);

        $this->assertTrue($sessionStartCalled);
        $this->assertTrue($sessionCloseCalled);
    }
}

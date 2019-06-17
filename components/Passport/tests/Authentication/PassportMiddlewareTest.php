<?php declare(strict_types=1);

namespace Limoncello\Tests\Passport\Authentication;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Exception;
use Limoncello\Contracts\Passport\PassportAccountInterface;
use Limoncello\Contracts\Passport\PassportAccountManagerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Authentication\PassportMiddleware;
use Limoncello\Passport\Exceptions\AuthenticationException;
use Limoncello\Tests\Passport\Data\TestContainer;
use Limoncello\Tests\Passport\Package\PassportContainerConfiguratorTest;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @package Limoncello\Tests\Passport
 */
class PassportMiddlewareTest extends TestCase
{
    /**
     * Test handle.
     *
     * @throws Exception
     */
    public function testHandleWithValidToken()
    {
        $token      = 'abc123';
        $request    = (new ServerRequest())->withHeader('Authorization', "Bearer $token");
        $nextCalled = false;
        $next       = function () use (&$nextCalled) {
            $nextCalled = true;

            return new Response();
        };
        $container  = new TestContainer();
        /** @var Mock $managerMock */
        $container[PassportAccountManagerInterface::class] = $managerMock =
            Mockery::mock(PassportAccountManagerInterface::class);
        $accountMock                                       = Mockery::mock(PassportAccountInterface::class);
        $managerMock->shouldReceive('setAccountWithTokenValue')->once()->with($token)->andReturn($accountMock);

        PassportMiddleware::handle($request, $next, $container);

        $this->assertTrue($nextCalled);
    }

    /**
     * Test handle.
     *
     * @throws Exception
     */
    public function testHandleWithMalformedToken()
    {
        $request                                     = (new ServerRequest())->withHeader('Authorization', 'Bearer ');
        $nextCalled                                  = false;
        $container                                   = new TestContainer();
        $container[LoggerInterface::class]           = new NullLogger();
        $container[SettingsProviderInterface::class] = PassportContainerConfiguratorTest::createSettingsProvider();
        $next                                        = function () use (&$nextCalled) {
            $nextCalled = true;

            return new Response();
        };

        $response = PassportMiddleware::handle($request, $next, $container);

        $this->assertTrue($nextCalled);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test handle.
     *
     * @throws Exception
     */
    public function testHandleWithInvalidToken()
    {
        $request                                     = (new ServerRequest())->withHeader('Authorization', 'Bearer XXX');
        $nextCalled                                  = false;
        $container                                   = new TestContainer();
        $container[LoggerInterface::class]           = new NullLogger();
        $container[SettingsProviderInterface::class] = PassportContainerConfiguratorTest::createSettingsProvider();
        $next                                        = function () use (&$nextCalled) {
            $nextCalled = true;

            return new Response();
        };
        /** @var Mock $managerMock */
        $container[PassportAccountManagerInterface::class] = $managerMock =
            Mockery::mock(PassportAccountManagerInterface::class);
        $managerMock->shouldReceive('setAccountWithTokenValue')
            ->once()->withAnyArgs()->andThrow(AuthenticationException::class);

        $response = PassportMiddleware::handle($request, $next, $container);

        $this->assertFalse($nextCalled);
        $this->assertEquals(401, $response->getStatusCode());
    }
}

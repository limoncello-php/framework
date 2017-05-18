<?php namespace Limoncello\Tests\Passport\Authentication;

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

use Limoncello\Passport\Authentication\PassportMiddleware;
use Limoncello\Passport\Contracts\Authentication\PassportAccountInterface;
use Limoncello\Passport\Contracts\Authentication\PassportAccountManagerInterface;
use Limoncello\Tests\Passport\Data\TestContainer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

/**
 * @package Limoncello\Tests\Passport
 */
class PassportMiddlewareTest extends TestCase
{
    /**
     * Test handle.
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
        /** @var Mockery\Mock $managerMock */
        $container[PassportAccountManagerInterface::class] = $managerMock =
            Mockery::mock(PassportAccountManagerInterface::class);
        $accountMock                                       = Mockery::mock(PassportAccountInterface::class);
        $managerMock->shouldReceive('setAccountWithTokenValue')->once()->with($token)->andReturn($accountMock);

        PassportMiddleware::handle($request, $next, $container);

        $this->assertTrue($nextCalled);
    }

    /**
     * Test handle.
     */
    public function testHandleWithInvalidToken()
    {
        $request    = (new ServerRequest())->withHeader('Authorization', 'Bearer ');
        $nextCalled = false;
        $container  = new TestContainer();
        $next       = function () use (&$nextCalled) {
            $nextCalled = true;

            return new Response();
        };

        PassportMiddleware::handle($request, $next, $container);

        $this->assertTrue($nextCalled);
    }
}

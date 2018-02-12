<?php namespace Limoncello\Tests\Core\Application;

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

use Exception;
use Limoncello\Core\Application\Sapi;
use Limoncello\Tests\Core\TestCase;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * @package Limoncello\Tests\Core
 */
class SapiTest extends TestCase
{
    /**
     * Test handle Response.
     *
     * @throws Exception
     */
    public function testHomeIndex(): void
    {
        /** @var Mockery\Mock $emitter */
        $emitter  = Mockery::mock(EmitterInterface::class);
        /** @var Mockery\Mock $response */
        $response = Mockery::mock(ResponseInterface::class);

        $emitter->shouldReceive('emit')->once()->with($response)->andReturnUndefined();

        /** @var EmitterInterface $emitter */
        /** @var ResponseInterface $response */

        $sapi = new Sapi($emitter);
        $sapi->handleResponse($response);

        $this->assertNotNull($sapi->getCookies());
        $this->assertNotNull($sapi->getUri());
        $this->assertNotNull($sapi->getFiles());
        $this->assertNotNull($sapi->getHeaders());
        $this->assertNotNull($sapi->getMethod());
        $this->assertNotNull($sapi->getParsedBody());
        $this->assertNotNull($sapi->getQueryParams());
        $this->assertNotNull($sapi->getRequestBody());
        $this->assertNotNull($sapi->getServer());
        $this->assertNotNull($sapi->getProtocolVersion());
    }
}

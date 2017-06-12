<?php namespace Limoncello\Tests\Testing;

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

use Limoncello\Testing\Sapi;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmitterInterface;

/**
 * @package Limoncello\Tests\Testing
 */
class SapiTest extends TestCase
{
    /**
     * Test handle response.
     */
    public function testHandleResponse()
    {
        /** @var EmitterInterface $emitter */
        $emitter  = Mockery::mock(EmitterInterface::class);
        /** @var ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        $sapi = new Sapi($emitter);
        $sapi->handleResponse($response);
        $this->assertSame($response, $sapi->getResponse());
    }
}

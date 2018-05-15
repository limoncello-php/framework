<?php namespace Limoncello\Tests\Flute\Http\Errors;

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

use Exception;
use Limoncello\Container\Container;
use Limoncello\Contracts\Http\ThrowableResponseInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Http\ThrowableHandlers\FluteThrowableHandler;
use Limoncello\Tests\Flute\Data\Exceptions\JsonApiThrowableConverter;
use Limoncello\Tests\Flute\TestCase;
use Mockery;
use Mockery\Mock;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Flute
 */
class FluteExceptionHandlerTest extends TestCase
{
    /**
     * Test Exception handler.
     *
     * @throws Exception
     */
    public function testHandlerWithNonJsonException(): void
    {
        /** @var Mock $encoderMock */
        $encoderMock = Mockery::mock(EncoderInterface::class);
        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        /** @var EncoderInterface $encoderMock */

        $isDebug = true;

        $throwableConverterClass = null;
        $handler = new FluteThrowableHandler($encoderMock, [], 500, $isDebug, $throwableConverterClass);
        $handler->setLogger(new NullLogger());

        $response = $handler->createResponse(new Exception(), new Container());
        $this->assertInstanceOf(ThrowableResponseInterface::class, $response);
        $this->assertNotNull($response->getThrowable());
    }

    /**
     * Test Exception handler.
     *
     * @throws Exception
     */
    public function testHandlerWithJsonException(): void
    {
        /** @var Mock $encoderMock */
        $encoderMock = Mockery::mock(EncoderInterface::class);
        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        /** @var EncoderInterface $encoderMock */

        $isDebug = true;

        $throwableConverterClass = null;
        $handler = new FluteThrowableHandler($encoderMock, [], 500, $isDebug, $throwableConverterClass);
        $handler->setLogger(new NullLogger());

        $response = $handler->createResponse(new JsonApiException([]), new Container());
        $this->assertInstanceOf(ThrowableResponseInterface::class, $response);
        $this->assertNotNull($response->getThrowable());
    }

    /**
     * Test Exception handler.
     *
     * @throws Exception
     */
    public function testHandlerWithFaultyLogger(): void
    {
        /** @var Mock $encoderMock */
        $encoderMock = Mockery::mock(EncoderInterface::class);
        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        /** @var EncoderInterface $encoderMock */

        $isDebug = true;

        $throwableConverterClass = null;
        $handler = new FluteThrowableHandler($encoderMock, [], 500, $isDebug, $throwableConverterClass);
        /** @var Mock $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->once()->withAnyArgs()->andThrow(new Exception('From logger'));
        /** @var LoggerInterface $logger */
        $handler->setLogger($logger);

        $response = $handler->createResponse(new Exception('Original Error'), new Container());
        $this->assertInstanceOf(ThrowableResponseInterface::class, $response);
        $this->assertNotNull($response->getThrowable());
        $this->assertEquals('Original Error', $response->getThrowable()->getMessage());
    }

    /**
     * Test Exception handler.
     *
     * @throws Exception
     */
    public function testHandlerWithNonJsonExceptionAndConverter(): void
    {
        /** @var Mock $encoderMock */
        $encoderMock = Mockery::mock(EncoderInterface::class);
        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        /** @var EncoderInterface $encoderMock */

        $isDebug = true;

        JsonApiThrowableConverter::setShouldThrow(false);
        $throwableConverterClass = JsonApiThrowableConverter::class;
        $handler = new FluteThrowableHandler($encoderMock, [], 500, $isDebug, $throwableConverterClass);
        $handler->setLogger(new NullLogger());

        $response = $handler->createResponse(new Exception(), new Container());
        $this->assertInstanceOf(ThrowableResponseInterface::class, $response);
        $this->assertInstanceOf(JsonApiException::class, $response->getThrowable());
    }

    /**
     * Test Exception handler.
     *
     * @throws Exception
     */
    public function testHandlerWithNonJsonExceptionAndFaultyConverter(): void
    {
        /** @var Mock $encoderMock */
        $encoderMock = Mockery::mock(EncoderInterface::class);
        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        /** @var EncoderInterface $encoderMock */

        $isDebug = true;

        JsonApiThrowableConverter::setShouldThrow(true);
        $throwableConverterClass = JsonApiThrowableConverter::class;
        $handler = new FluteThrowableHandler($encoderMock, [], 500, $isDebug, $throwableConverterClass);
        $handler->setLogger(new NullLogger());

        $response = $handler->createResponse($exception = new Exception(), new Container());
        $this->assertInstanceOf(ThrowableResponseInterface::class, $response);
        $this->assertSame($exception, $response->getThrowable());
    }
}

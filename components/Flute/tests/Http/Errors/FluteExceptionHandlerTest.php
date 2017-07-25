<?php namespace Limoncello\Tests\Flute\Http\Errors;

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
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Http\Cors\CorsStorageInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Http\Errors\FluteExceptionHandler;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Tests\Flute\Data\Package\Flute;
use Limoncello\Tests\Flute\Data\Package\SettingsProvider;
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
     */
    public function testExceptionHandler()
    {
        $handler = new FluteExceptionHandler();

        $container = new Container();
        $container[SettingsProviderInterface::class] = new SettingsProvider([
            FluteSettings::class => (new Flute($this->getSchemeMap(), $this->getValidationRuleSets()))->get(),
            ApplicationSettingsInterface::class => [
                ApplicationSettingsInterface::KEY_IS_DEBUG => true,
            ],
        ]);
        $container[LoggerInterface::class] = new NullLogger();
        /** @var Mock $encoderMock */
        /** @var Mock $corsStorageMock */
        $container[EncoderInterface::class] = $encoderMock = Mockery::mock(EncoderInterface::class);
        $container[CorsStorageInterface::class] = $corsStorageMock = Mockery::mock(CorsStorageInterface::class);

        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        $corsStorageMock->shouldReceive('getHeaders')->once()->withNoArgs()->andReturn(['some' => 'cors_headers']);

        /** @var Mock $sapi */
        $sapi = Mockery::mock(SapiInterface::class);
        $sapi->shouldReceive('handleResponse')->once()->withAnyArgs()->andReturnSelf();

        /** @var SapiInterface $sapi */

        $handler->handleException(new Exception(), $sapi, $container);

        // Mock will do the checks when test finishes
        $this->assertTrue(true);
    }

    /**
     * Test Exception handler.
     */
    public function testExceptionHandlerWithoutCorsHeaders()
    {
        $handler = new FluteExceptionHandler();

        $container = new Container();
        $container[SettingsProviderInterface::class] = new SettingsProvider([
            FluteSettings::class => (new Flute($this->getSchemeMap(), $this->getValidationRuleSets()))->get(),
            ApplicationSettingsInterface::class => [
                ApplicationSettingsInterface::KEY_IS_DEBUG => true,
            ],
        ]);
        $container[LoggerInterface::class] = new NullLogger();
        /** @var Mock $encoderMock */
        $container[EncoderInterface::class] = $encoderMock = Mockery::mock(EncoderInterface::class);

        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');

        /** @var Mock $sapi */
        $sapi = Mockery::mock(SapiInterface::class);
        $sapi->shouldReceive('handleResponse')->once()->withAnyArgs()->andReturnSelf();

        /** @var SapiInterface $sapi */

        $handler->handleException(new Exception(), $sapi, $container);

        // Mock will do the checks when test finishes
        $this->assertTrue(true);
    }

    /**
     * Test Throwable handler.
     */
    public function testThrowableHandler()
    {
        $handler = new FluteExceptionHandler();

        $container = new Container();
        $container[SettingsProviderInterface::class] = new SettingsProvider([
            FluteSettings::class => (new Flute($this->getSchemeMap(), $this->getValidationRuleSets()))->get(),
            ApplicationSettingsInterface::class => [
                ApplicationSettingsInterface::KEY_IS_DEBUG => true,
            ],
        ]);
        /** @var Mock $encoderMock */
        /** @var Mock $corsStorageMock */
        $container[EncoderInterface::class] = $encoderMock = Mockery::mock(EncoderInterface::class);
        $container[CorsStorageInterface::class] = $corsStorageMock = Mockery::mock(CorsStorageInterface::class);

        $encoderMock->shouldReceive('encodeErrors')->once()->withAnyArgs()->andReturn('error_info');
        $corsStorageMock->shouldReceive('getHeaders')->once()->withNoArgs()->andReturn(['some' => 'cors_headers']);

        /** @var Mock $sapi */
        $sapi = Mockery::mock(SapiInterface::class);
        $sapi->shouldReceive('handleResponse')->once()->withAnyArgs()->andReturnSelf();

        /** @var SapiInterface $sapi */

        $handler->handleThrowable(new JsonApiException([]), $sapi, $container);

        $handler->handleFatal([
            'message' => 'some message',
            'type'    => 0,
            'file'    => __FILE__,
            'line'    => __LINE__,
        ], $container);

        // Mock will do the checks when test finishes
        $this->assertTrue(true);
    }
}

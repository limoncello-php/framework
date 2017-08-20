<?php namespace Limoncello\Tests\Application\ExceptionHandlers;

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
use Limoncello\Application\ExceptionHandlers\DefaultHandler;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationSettingsInterface as A;
use Limoncello\Contracts\Core\SapiInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Application
 */
class ExceptionHandlersTest extends TestCase
{
    /**
     * Test handler.
     */
    public function testDefaultExceptionHandler()
    {
        $handler = new DefaultHandler();

        $exception = new Exception();
        try {
            $handler->handleException($exception, $this->createSapi(), $this->createContainer(true));
        } catch (Exception $exception) {
            //echo $exception->getMessage() . PHP_EOL;
        }

        // Mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test handler.
     */
    public function testDefaultExceptionHandlerDebugDisabled()
    {
        $handler = new DefaultHandler();

        $handler->handleException(new Exception(), $this->createSapi(), $this->createContainer(false));

        // Mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * @return SapiInterface
     */
    private function createSapi(): SapiInterface
    {
        /** @var Mock $sapi */
        $sapi = Mockery::mock(SapiInterface::class);

        $sapi->shouldReceive('handleResponse')->once()->withAnyArgs()->andReturnUndefined();

        /** @var SapiInterface $sapi */

        return $sapi;
    }

    /**
     * @param bool $debugEnabled
     *
     * @return ContainerInterface
     */
    private function createContainer(bool $debugEnabled): ContainerInterface
    {
        $container = new Container();

        $container[LoggerInterface::class] = new NullLogger();

        /** @var Mock $provider */
        $container[SettingsProviderInterface::class] = $provider = Mockery::mock(SettingsProviderInterface::class);
        $provider->shouldReceive('has')->once()->with(A::class)->andReturn(true);
        $provider->shouldReceive('get')->once()->with(A::class)->andReturn([
            A::KEY_IS_DEBUG         => $debugEnabled,
            A::KEY_APP_NAME         => 'Test App',
            A::KEY_EXCEPTION_DUMPER => [self::class, 'exceptionDumper'],
        ]);

        return $container;
    }

    /**
     * @param array ...$args
     *
     * @return array
     */
    public static function exceptionDumper(...$args): array
    {
        assert($args);

        return [
            'some' => 'related details',
        ];
    }
}

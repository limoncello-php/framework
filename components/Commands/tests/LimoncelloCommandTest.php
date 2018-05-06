<?php namespace Limoncello\Tests\Commands;

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

use Composer\Composer;
use Exception;
use Limoncello\Commands\CommandConstants;
use Limoncello\Commands\CommandRoutesTrait;
use Limoncello\Commands\LimoncelloCommand;
use Limoncello\Commands\Traits\CacheFilePathTrait;
use Limoncello\Commands\Traits\CommandSerializationTrait;
use Limoncello\Commands\Traits\CommandTrait;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Exceptions\ThrowableHandlerInterface;
use Limoncello\Contracts\Http\ThrowableResponseInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Tests\Commands\Data\TestApplication;
use Limoncello\Tests\Commands\Data\TestCommand;
use Mockery;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Tests\Commands
 */
class LimoncelloCommandTest extends TestCase
{
    use CacheFilePathTrait, CommandSerializationTrait, CommandTrait, CommandRoutesTrait;

    /** @var bool */
    private static $executedFlag = false;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        static::$executedFlag = false;
    }

    /**
     * Test basic command behaviour.
     *
     * @throws Exception
     */
    public function testCommand(): void
    {
        $name    = 'name';
        $command = $this->createCommandMock($name);

        $container = Mockery::mock(ContainerInterface::class);
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);

        /** @var LimoncelloCommand $command */

        $this->assertEquals($name, $command->getName());

        $input    = Mockery::mock(InputInterface::class);
        $output   = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);
        $command->execute($input, $output);

        $this->assertTrue(static::$executedFlag);
    }

    /**
     * Test if container creation fails.
     *
     * @throws Exception
     */
    public function testContainerCreationFails(): void
    {
        $command = $this->createCommandMock();

        $command->shouldReceive('createContainer')
            ->once()->withAnyArgs()->andThrow(new Exception('Oops, container failed'));

        /** @var LimoncelloCommand $command */

        $input    = Mockery::mock(InputInterface::class);
        $output   = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        $output->shouldReceive('writeln')->once()->withAnyArgs()->andReturnUndefined();

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);

        $exception = null;
        try {
            $command->execute($input, $output);
        } catch (Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertStringStartsWith('Oops, container failed', $exception->getMessage());

        $this->assertFalse(static::$executedFlag);
    }

    /**
     * Test custom error handler.
     *
     * @throws Exception
     */
    public function testCustomErrorHandler(): void
    {
        $command = $this->createCommandMock('name', [self::class, 'callbackWithThrow']);

        $handler   = Mockery::mock(ThrowableHandlerInterface::class);
        $response  = Mockery::mock(ThrowableResponseInterface::class);
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('has')->withArgs([ThrowableHandlerInterface::class])->andReturn(true);
        $container->shouldReceive('get')->withArgs([ThrowableHandlerInterface::class])->andReturn($handler);
        $handler->shouldReceive('createResponse')->once()->withAnyArgs()->andReturn($response);
        $response->shouldReceive('getBody')->once()->withAnyArgs()->andReturn('does not matter');


        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);

        /** @var LimoncelloCommand $command */

        $input    = Mockery::mock(InputInterface::class);
        $output   = Mockery::mock(OutputInterface::class);
        $composer = Mockery::mock(Composer::class);

        $output->shouldReceive('writeln')->once()->withAnyArgs()->andReturnUndefined();

        /** @var InputInterface $input */
        /** @var OutputInterface $output */
        /** @var Composer $composer */

        $command->setComposer($composer);

        $exception = null;
        try {
            $command->execute($input, $output);
        } catch (Exception $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertStringStartsWith('Oops, command thrown and exception', $exception->getMessage());

        $this->assertTrue(static::$executedFlag);
    }

    /**
     * Test trait method.
     *
     * @throws Exception
     */
    public function testGetCommandsCacheFilePath(): void
    {
        /** @var Mockery\Mock $composer */
        $composer = Mockery::mock(Composer::class);

        $fileName = 'composer.json';
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('getExtra')->once()->withNoArgs()->andReturn([
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__COMMANDS_CACHE => $fileName,
            ],
        ]);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);

        /** @var Composer $composer */

        $path     = $this->getCommandsCacheFilePath($composer);
        $expected = realpath($vendorDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $fileName);
        $this->assertEquals($expected, $path);
    }

    /**
     * Test trait method.
     *
     * @throws Exception
     */
    public function testCommandSerialization(): void
    {
        $this->assertNotEmpty($this->commandClassToArray(TestCommand::class));
    }

    /**
     * Test trait method.
     *
     * @throws Exception
     */
    public function testCreateContainer(): void
    {
        /** @var Mockery\Mock $composer */
        $composer = Mockery::mock(Composer::class);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);

        $extra = [
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS => TestApplication::class,
            ],
        ];
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('getExtra')->once()->withNoArgs()->andReturn($extra);

        /** @var Composer $composer */

        $commandName = 'some_command';
        $this->assertNotNull($this->createContainer($composer, $commandName));
    }

    /**
     * Test trait method.
     *
     * @expectedException \Limoncello\Commands\Exceptions\ConfigurationException
     */
    public function testCreateContainerForInvalidAppClass(): void
    {
        /** @var Mockery\Mock $composer */
        $composer = Mockery::mock(Composer::class);

        $vendorDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';
        $composer->shouldReceive('getConfig')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('get')->once()->with('vendor-dir')->andReturn($vendorDir);

        $extra = [
            CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION => [
                CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS => self::class, // <-- invalid App class
            ],
        ];
        $composer->shouldReceive('getPackage')->once()->withNoArgs()->andReturnSelf();
        $composer->shouldReceive('getExtra')->once()->withNoArgs()->andReturn($extra);

        /** @var Composer $composer */

        $commandName = 'some_command';
        $this->createContainer($composer, $commandName);
    }

    /**
     * Test trait method.
     *
     * @throws Exception
     */
    public function testCommandContainer(): void
    {
        /** @var Mockery\Mock $group */
        $group = Mockery::mock(GroupInterface::class);
        $group->shouldReceive('method')->once()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $commandName = 'some_command';
        $this->assertSame($group, static::commandContainer($group, $commandName, [self::class, 'callback1']));
    }

    /**
     * @expectedException \LogicException
     */
    public function testHandlerStubShouldFail()
    {
        $request   = Mockery::mock(ServerRequestInterface::class);
        $container = Mockery::mock(ContainerInterface::class);

        /** @var ContainerInterface $container */
        /** @var ServerRequestInterface $request */

        static::handlerStub([], $container, $request);
    }

    /**
     * @return void
     */
    public static function callback1(): void
    {
        static::$executedFlag = true;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public static function callbackWithThrow(): void
    {
        static::$executedFlag = true;

        throw new Exception('Oops, command thrown and exception');
    }

    /**
     * @param string $name
     *
     * @param array  $callable
     *
     * @return Mockery\Mock
     */
    private function createCommandMock(string $name = 'name', $callable = [self::class, 'callback1'])
    {
        $description = 'description';
        $help        = 'help';
        $argName1    = 'arg1';
        $arguments   = [
            [
                CommandInterface::ARGUMENT_NAME => $argName1,
            ],
        ];
        $optName1    = 'opt1';
        $options     = [
            [
                CommandInterface::OPTION_NAME => $optName1,
            ],
        ];

        /** @var Mockery\Mock $command */
        $command = Mockery::mock(
            LimoncelloCommand::class . '[createContainer]',
            [$name, $description, $help, $arguments, $options, $callable]
        );
        $command->shouldAllowMockingProtectedMethods();

        return $command;
    }
}

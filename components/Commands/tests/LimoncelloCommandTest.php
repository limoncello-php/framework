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
use Limoncello\Commands\CommandConstants;
use Limoncello\Commands\CommandRoutesTrait;
use Limoncello\Commands\LimoncelloCommand;
use Limoncello\Commands\Traits\CacheFilePathTrait;
use Limoncello\Commands\Traits\CommandSerializationTrait;
use Limoncello\Commands\Traits\CommandTrait;
use Limoncello\Contracts\Commands\CommandInterface;
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
     */
    public function testCommand()
    {
        $name        = 'name';
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
        $callable    = [self::class, 'callback1'];

        /** @var Mockery\Mock $command */
        $command = Mockery::mock(
            LimoncelloCommand::class . '[createContainer]',
            [$name, $description, $help, $arguments, $options, $callable]
        );
        $command->shouldAllowMockingProtectedMethods();

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
     * Test trait method.
     */
    public function testGetCommandsCacheFilePath()
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
     */
    public function testCommandSerialization()
    {
        $this->assertNotEmpty($this->commandClassToArray(TestCommand::class));
    }

    /**
     * Test trait method.
     */
    public function testCreateContainer()
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
    public function testCreateContainerForInvalidAppClass()
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
     */
    public function testCommandContainer()
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
        $request = Mockery::mock(ServerRequestInterface::class);
        $container = Mockery::mock(ContainerInterface::class);

        /** @var ContainerInterface $container */
        /** @var ServerRequestInterface $request */

        static::handlerStub([], $container, $request);
    }

    /**
     * @return void
     */
    public static function callback1()
    {
        static::$executedFlag = true;
    }
}

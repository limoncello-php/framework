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
use Limoncello\Commands\CommandRoutesTrait;
use Limoncello\Commands\CommandsCommand;
use Limoncello\Commands\Traits\CacheFilePathTrait;
use Limoncello\Commands\Traits\CommandSerializationTrait;
use Limoncello\Commands\Traits\CommandTrait;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Commands\Data\TestCommand;
use Mockery;
use Mockery\Mock;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as S;

/**
 * @package Limoncello\Tests\Commands
 */
class CommandsCommandTest extends TestCase
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
     * Test execution for Connect.
     */
    public function testConnect()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class     => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMock()),
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        $command->shouldReceive('getCommandsCacheFilePath')->once()->withAnyArgs()->andReturn('path_to_cache_file');
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var Mock $fileSystem */
        $fileSystem->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(false)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     */
    public function testConnectWithInvalidCommand()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class     => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMockWithInvalidCommand()),
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     */
    public function testConnectWithEmptyCachePath()
    {
        $container = $this->createContainerMock([
            FileSystemInterface::class     => ($fileSystem = $this->createFileSystemMock()),
            CommandStorageInterface::class => ($this->createCommandStorageMock()),
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        $command->shouldReceive('getCommandsCacheFilePath')->once()->withAnyArgs()->andReturn(''); // <-- empty path
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CONNECT),
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     */
    public function testCreate()
    {
        $folder     = 'some_folder';
        $cmdClass   = 'NewCommandClass';
        $classPath  = $folder . DIRECTORY_SEPARATOR . $cmdClass . '.php';
        $tplPath    = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'src', 'SampleCommand.txt']));
        $tplContent = file_get_contents($tplPath);

        $container = $this->createContainerMock([
            FileSystemInterface::class       => ($fileSystem = $this->createFileSystemMock()),
            SettingsProviderInterface::class => $this->createSettingsProviderMock($folder)
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var Mock $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(true);
        $fileSystem->shouldReceive('exists')->once()->with($classPath)->andReturn(false);
        $fileSystem->shouldReceive('read')->once()->with($tplPath)->andReturn($tplContent);
        $fileSystem->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(false)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     */
    public function testCreateNoArgClass()
    {
        $cmdClass = null;

        $container = $this->createContainerMock([]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     */
    public function testCreateInvalidCommandsFolder()
    {
        $folder   = 'some_folder';
        $cmdClass = 'NewCommandClass';

        $container = $this->createContainerMock([
            FileSystemInterface::class       => ($fileSystem = $this->createFileSystemMock()),
            SettingsProviderInterface::class => $this->createSettingsProviderMock($folder)
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var Mock $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(false); // <-- invalid folder

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Create.
     */
    public function testCreateCommandAlreadyExists()
    {
        $folder    = 'some_folder';
        $cmdClass  = 'NewCommandClass';
        $classPath = $folder . DIRECTORY_SEPARATOR . $cmdClass . '.php';

        $container = $this->createContainerMock([
            FileSystemInterface::class       => ($fileSystem = $this->createFileSystemMock()),
            SettingsProviderInterface::class => $this->createSettingsProviderMock($folder)
        ]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        /** @var Mock $fileSystem */
        $fileSystem->shouldReceive('isFolder')->once()->with($folder)->andReturn(true);
        $fileSystem->shouldReceive('exists')->once()->with($classPath)->andReturn(true); // <-- already exists

        $command->execute(
            $this->createInputMock(CommandsCommand::ACTION_CREATE, $cmdClass),
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * Test execution for Connect.
     */
    public function testInvalidAction()
    {
        $cmdClass = null;

        $container = $this->createContainerMock([]);

        /** @var Mock $command */
        $command = Mockery::mock(CommandsCommand::class . '[createContainer,getCommandsCacheFilePath]');
        $command->shouldAllowMockingProtectedMethods();
        $command->shouldReceive('createContainer')->once()->withAnyArgs()->andReturn($container);
        /** @var CommandsCommand $command */
        $command->setComposer($this->createComposerMock());

        $command->execute(
            $this->createInputMock('XXX', $cmdClass), // <-- Invalid action
            $this->createOutputMock(true)
        );

        // actual check would be performed by Mockery on test completion.
        $this->assertTrue(true);
    }

    /**
     * @return Composer
     */
    private function createComposerMock(): Composer
    {
        /** @var CommandsCommand $command */

        $composer = Mockery::mock(Composer::class);

        /** @var Composer $composer */

        return $composer;
    }

    /**
     * @param string      $argAction
     * @param string|null $argClass
     *
     * @return InputInterface
     */
    private function createInputMock(string $argAction, string $argClass = null): InputInterface
    {
        /** @var Mock $input */
        $input = Mockery::mock(InputInterface::class);

        $input->shouldReceive('getArgument')->once()->withAnyArgs()->andReturn($argAction);

        if ($argClass !== null) {
            $input->shouldReceive('hasArgument')->once()->with(CommandsCommand::ARG_CLASS)->andReturn(true);
            $input->shouldReceive('getArgument')->once()->with(CommandsCommand::ARG_CLASS)->andReturn($argClass);
        } else {
            $input->shouldReceive('hasArgument')->zeroOrMoreTimes()->with(CommandsCommand::ARG_CLASS)->andReturn(false);
        }

        /** @var InputInterface $input */

        return $input;
    }

    /**
     * @param bool $expectOutput
     *
     * @return OutputInterface
     */
    private function createOutputMock(bool $expectOutput): OutputInterface
    {
        /** @var Mock $output */
        $output = Mockery::mock(OutputInterface::class);

        if ($expectOutput === true) {
            $output->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();
        }

        /** @var OutputInterface $output */

        return $output;
    }

    /**
     * @return FileSystemInterface
     */
    private function createFileSystemMock(): FileSystemInterface
    {
        /** @var FileSystemInterface $fileSystem */
        $fileSystem = Mockery::mock(FileSystemInterface::class);

        return $fileSystem;
    }

    /**
     * @return CommandStorageInterface
     */
    private function createCommandStorageMock(): CommandStorageInterface
    {
        /** @var Mock $commandStorage */
        $commandStorage = Mockery::mock(CommandStorageInterface::class);

        $commandStorage->shouldReceive('getAll')->once()->withNoArgs()->andReturn([TestCommand::class]);

        /** @var CommandStorageInterface $commandStorage */

        return $commandStorage;
    }

    /**
     * @return CommandStorageInterface
     */
    private function createCommandStorageMockWithInvalidCommand(): CommandStorageInterface
    {
        /** @var Mock $commandStorage */
        $commandStorage = Mockery::mock(CommandStorageInterface::class);

        $commandStorage->shouldReceive('getAll')->once()->withNoArgs()->andReturn([self::class]); // <-- invalid class

        /** @var CommandStorageInterface $commandStorage */

        return $commandStorage;
    }

    /**
     * @param array $items
     *
     * @return ContainerInterface
     */
    private function createContainerMock(array $items): ContainerInterface
    {
        /** @var Mock $container */
        $container = Mockery::mock(ContainerInterface::class);

        foreach ($items as $key => $item) {
            $container->shouldReceive('has')->zeroOrMoreTimes()->with($key)->andReturn(true);
            $container->shouldReceive('get')->zeroOrMoreTimes()->with($key)->andReturn($item);
        }

        /** @var ContainerInterface $container */

        return $container;
    }

    /**
     * @param string $commandFolder
     *
     * @return SettingsProviderInterface
     */
    private function createSettingsProviderMock(string $commandFolder): SettingsProviderInterface
    {
        /** @var Mock $provider */
        $provider = Mockery::mock(SettingsProviderInterface::class);

        $provider->shouldReceive('get')->once()->with(S::class)->andReturn([S::KEY_COMMANDS_FOLDER => $commandFolder]);

        /** @var SettingsProviderInterface $provider */

        return $provider;
    }
}

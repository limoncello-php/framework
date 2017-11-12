<?php namespace Limoncello\Tests\Application\Commands;

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

use Limoncello\Application\Commands\ApplicationCommand;
use Limoncello\Container\Container;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Application
 */
class ApplicationCommandTest extends TestCase
{
    /**
     * Test command descriptions.
     */
    public function testCommandDescriptions(): void
    {
        $this->assertNotEmpty(ApplicationCommand::getName());
        $this->assertNotEmpty(ApplicationCommand::getHelp());
        $this->assertNotEmpty(ApplicationCommand::getDescription());
        $this->assertNotEmpty(ApplicationCommand::getArguments());
        $this->assertEmpty(ApplicationCommand::getOptions());
    }

    /**
     * Test command called with invalid action parameter.
     */
    public function testInvalidAction(): void
    {
        $container = new Container();
        $inOut     = $this->createInOutMock(
            ApplicationCommand::ARG_ACTION,
            'non_existing_action',
            true
        );

        ApplicationCommand::execute($container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test action.
     */
    public function testCache(): void
    {
        $container = new Container();

        /** @var Mock $providerMock */
        $providerMock                                     = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $providerMock;
        $container[CacheSettingsProviderInterface::class] = $providerMock;
        $providerMock->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_CACHE_FOLDER   => '/some/path',
                ApplicationConfigurationInterface::KEY_CACHE_CALLABLE => 'Namespace\\ClassName::methodName',
            ]);
        $providerMock->shouldReceive('serialize')->once()->withNoArgs()->andReturn(['some' => 'cache']);
        /** @var Mock $fsMock */
        $container[FileSystemInterface::class] = $fsMock = Mockery::mock(FileSystemInterface::class);
        $fsMock->shouldReceive('write')->once()->withAnyArgs()->andReturnUndefined();

        $inOut = $this->createInOutMock(ApplicationCommand::ARG_ACTION, ApplicationCommand::ACTION_CREATE_CACHE);

        $method = new ReflectionMethod(ApplicationCommand::class, 'run');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $method->invoke($command, $container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test action.
     *
     * @expectedException \Limoncello\Application\Exceptions\ConfigurationException
     */
    public function testCacheInvalidCallable(): void
    {
        $container = new Container();

        /** @var Mock $providerMock */
        $providerMock                                     = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $providerMock;
        $container[CacheSettingsProviderInterface::class] = $providerMock;
        $providerMock->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_CACHE_FOLDER   => '/some/path',
                ApplicationConfigurationInterface::KEY_CACHE_CALLABLE => '', // <-- invalid value
            ]);

        $inOut = $this->createInOutMock(ApplicationCommand::ARG_ACTION, ApplicationCommand::ACTION_CREATE_CACHE);

        $method = new ReflectionMethod(ApplicationCommand::class, 'run');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $method->invoke($command, $container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test action.
     */
    public function testClear(): void
    {
        $container = new Container();

        /** @var Mock $providerMock */
        $providerMock                                     = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $providerMock;
        $container[CacheSettingsProviderInterface::class] = $providerMock;
        $providerMock->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_CACHE_FOLDER   => '/some/path',
                ApplicationConfigurationInterface::KEY_CACHE_CALLABLE => 'Namespace\\ClassName::methodName',
            ]);
        /** @var Mock $fsMock */
        $container[FileSystemInterface::class] = $fsMock = Mockery::mock(FileSystemInterface::class);
        $fsMock->shouldReceive('exists')->once()->withAnyArgs()->andReturn(true);
        $fsMock->shouldReceive('delete')->once()->withAnyArgs()->andReturnUndefined();

        $inOut = $this->createInOutMock(ApplicationCommand::ARG_ACTION, ApplicationCommand::ACTION_CLEAR_CACHE);

        $method = new ReflectionMethod(ApplicationCommand::class, 'run');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $method->invoke($command, $container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test action.
     */
    public function testClearNonExistingCache(): void
    {
        $container = new Container();

        /** @var Mock $providerMock */
        $providerMock                                     = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $providerMock;
        $container[CacheSettingsProviderInterface::class] = $providerMock;
        $providerMock->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_CACHE_FOLDER   => '/some/path',
                ApplicationConfigurationInterface::KEY_CACHE_CALLABLE => 'Namespace\\ClassName::methodName',
            ]);
        /** @var Mock $fsMock */
        $container[FileSystemInterface::class] = $fsMock = Mockery::mock(FileSystemInterface::class);
        $fsMock->shouldReceive('exists')->once()->withAnyArgs()->andReturn(false);

        $inOut = $this->createInOutMock(ApplicationCommand::ARG_ACTION, ApplicationCommand::ACTION_CLEAR_CACHE);

        $method = new ReflectionMethod(ApplicationCommand::class, 'run');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $method->invoke($command, $container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test action.
     */
    public function testParseCacheCallable1(): void
    {
        $mightBeCallable = '';

        $method = new ReflectionMethod(ApplicationCommand::class, 'parseCacheCallable');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $result = $method->invoke($command, $mightBeCallable);

        $this->assertEquals([null, null, null], $result);
    }

    /**
     * Test action.
     */
    public function testParseCacheCallable2(): void
    {
        $mightBeCallable = ['NamespaceName\\ClassName', 'methodName'];

        $method = new ReflectionMethod(ApplicationCommand::class, 'parseCacheCallable');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $result = $method->invoke($command, $mightBeCallable);

        $this->assertEquals(['NamespaceName', 'ClassName', 'methodName'], $result);
    }

    /**
     * Test action.
     */
    public function testParseCacheCallable3(): void
    {
        $mightBeCallable = ['NamespaceName\\123ClassName', 'methodName']; // <- numbers not allowed

        $method = new ReflectionMethod(ApplicationCommand::class, 'parseCacheCallable');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $result = $method->invoke($command, $mightBeCallable);

        $this->assertEquals([null, null, null], $result);
    }

    /**
     * Test action.
     *
     * @expectedException \Limoncello\Application\Exceptions\ConfigurationException
     */
    public function testClearInvalidCallable(): void
    {
        $container = new Container();

        /** @var Mock $providerMock */
        $providerMock                                     = Mockery::mock(CacheSettingsProviderInterface::class);
        $container[SettingsProviderInterface::class]      = $providerMock;
        $container[CacheSettingsProviderInterface::class] = $providerMock;
        $providerMock->shouldReceive('getApplicationConfiguration')->once()->withNoArgs()
            ->andReturn([
                ApplicationConfigurationInterface::KEY_CACHE_FOLDER   => '/some/path',
                ApplicationConfigurationInterface::KEY_CACHE_CALLABLE => '', // <-- invalid value
            ]);
        /** @var Mock $fsMock */
        $container[FileSystemInterface::class] = $fsMock = Mockery::mock(FileSystemInterface::class);

        $inOut = $this->createInOutMock(ApplicationCommand::ARG_ACTION, ApplicationCommand::ACTION_CLEAR_CACHE);

        $method = new ReflectionMethod(ApplicationCommand::class, 'run');
        $method->setAccessible(true);
        $command = new ApplicationCommand();
        $method->invoke($command, $container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * @param string $arName
     * @param string $argValue
     * @param bool   $expectErrors
     *
     * @return IoInterface
     */
    private function createInOutMock(string $arName, string $argValue, bool $expectErrors = false): IoInterface
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(IoInterface::class);
        $mock->shouldReceive('getArgument')->zeroOrMoreTimes()->with($arName)->andReturn($argValue);
        if ($expectErrors === true) {
            $mock->shouldReceive('writeError')->zeroOrMoreTimes()->withAnyArgs()->andReturnSelf();
        }

        $mock->shouldReceive('writeInfo')->zeroOrMoreTimes()->withAnyArgs()->andReturnSelf();

        /** @var IoInterface $mock */

        return $mock;
    }
}

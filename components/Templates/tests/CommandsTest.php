<?php namespace Limoncello\Tests\Templates;

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

use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Templates\Commands\TemplatesCommand;
use Limoncello\Templates\Contracts\TemplatesCacheInterface;
use Limoncello\Templates\Package\TemplatesSettings;
use Limoncello\Tests\Templates\Data\Templates;
use Limoncello\Tests\Templates\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Templates
 */
class CommandsTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test `Clean` command.
     */
    public function testClean()
    {
        $container = $this->createContainer();
        $this
            ->addSettingsProvider($container)
            ->addFileSystem($container);

        /** @var Mock $command */
        $command = Mockery::mock(TemplatesCommand::class . '[createCachingTemplateEngine]');
        $command->shouldAllowMockingProtectedMethods();

        /** @var TemplatesCommand $command */

        $command->execute($container, $this->createIo(TemplatesCommand::ACTION_CLEAR_CACHE));

        // Mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test `Create` command.
     */
    public function testCreate()
    {
        $container = $this->createContainer();
        $this
            ->addSettingsProvider($container);

        /** @var Mock $cacheMock */
        $cacheMock = Mockery::mock(TemplatesCacheInterface::class);
        $cacheMock->shouldReceive('cache')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        $container[TemplatesCacheInterface::class] = $cacheMock;

        $command = new TemplatesCommand();

        $this->assertNotEmpty($command::getName());
        $this->assertNotEmpty($command::getDescription());
        $this->assertNotEmpty($command::getHelp());
        $this->assertNotEmpty($command::getArguments());
        $this->assertEmpty($command::getOptions());

        $command::execute($container, $this->createIo(TemplatesCommand::ACTION_CREATE_CACHE));
    }

    /**
     * Test invalid action command.
     */
    public function testInvalidAction()
    {
        $container = $this->createContainer();

        /** @var Mock $command */
        $command = Mockery::mock(TemplatesCommand::class . '[createCachingTemplateEngine]');
        $command->shouldAllowMockingProtectedMethods();

        /** @var TemplatesCommand $command */

        $errors = 1;
        $command->execute($container, $this->createIo('XXX', $errors));

        // Mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    private function addSettingsProvider(ContainerInterface $container): self
    {
        $settings = (new Templates())->get();

        /** @var Mock $settingsMock */
        $settingsMock = Mockery::mock(SettingsProviderInterface::class);
        $settingsMock->shouldReceive('get')->once()->with(TemplatesSettings::class)->andReturn($settings);

        $container[SettingsProviderInterface::class] = $settingsMock;

        return $this;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    private function addFileSystem(ContainerInterface $container): self
    {
        /** @var Mock $fsMock */
        $fsMock = Mockery::mock(FileSystemInterface::class);
        $folder = '/some/path';
        $fsMock->shouldReceive('scanFolder')->once()->withAnyArgs()->andReturn([$folder]);
        $fsMock->shouldReceive('isFolder')->once()->with($folder)->andReturn(true);
        $fsMock->shouldReceive('deleteFolderRecursive')->once()->with($folder)->andReturnUndefined();

        $container[FileSystemInterface::class] = $fsMock;

        return $this;
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer(): ContainerInterface
    {
        return new TestContainer();
    }

    /**
     * @param string $action
     * @param int    $errors
     *
     * @return IoInterface
     */
    private function createIo(string $action, int $errors = 0): IoInterface
    {
        /** @var Mock $ioMock */
        $ioMock = Mockery::mock(IoInterface::class);

        $ioMock->shouldReceive('getArgument')->once()
            ->with(TemplatesCommand::ARG_ACTION)->andReturn($action);

        if ($errors > 0) {
            $ioMock->shouldReceive('writeError')->times($errors)
                ->withAnyArgs()->andReturnSelf();
        }

        /** @var IoInterface $ioMock */

        return $ioMock;
    }
}

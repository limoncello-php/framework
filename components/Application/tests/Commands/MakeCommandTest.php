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

use Limoncello\Application\Commands\MakeCommand;
use Limoncello\Container\Container;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\Packages\AuthorizationSettingsInterface;
use Limoncello\Contracts\Settings\Packages\DataSettingsInterface;
use Limoncello\Contracts\Settings\Packages\FluteSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Tests\Application\TestCase;
use Mockery;
use Mockery\Mock;

/**
 * @package Limoncello\Tests\Application
 */
class MakeCommandTest extends TestCase
{
    /**
     * @var Mock
     */
    private $fileSystemMock = null;

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->fileSystemMock = Mockery::mock(FileSystemInterface::class);
    }

    /**
     * Test command for make JSON API resource.
     */
    public function testMakeJsonApiResource(): void
    {
        $container = $this->createContainerWithSettings([
            DataSettingsInterface::KEY_MODELS_FOLDER     => '/models',
            DataSettingsInterface::KEY_MIGRATIONS_FOLDER => '/migrations',
            DataSettingsInterface::KEY_SEEDS_FOLDER      => '/seeds',
        ], [
            FluteSettingsInterface::KEY_SCHEMAS_FOLDER               => '/schemas',
            FluteSettingsInterface::KEY_JSON_VALIDATION_RULES_FOLDER => '/rules',
            FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER       => '/validators',
            FluteSettingsInterface::KEY_JSON_CONTROLLERS_FOLDER      => '/controllers',
            FluteSettingsInterface::KEY_ROUTES_FOLDER                => '/routes',
            FluteSettingsInterface::KEY_API_FOLDER                   => '/api',
        ], [
            AuthorizationSettingsInterface::KEY_POLICIES_FOLDER => '/policies',
        ]);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_JSONAPI,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();

        $data = [
            [
                'Model.txt'         => '{%SINGULAR_CC%},{%SINGULAR_LC%},{%SINGULAR_UC%},{%PLURAL_LC%},{%PLURAL_UC%}',
                '/models/Board.php' => 'Board,board,BOARD,boards,BOARDS',
            ],
            [
                'Migration.txt'                   => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/migrations/BoardsMigration.php' => 'Board,Boards',
            ],
            [
                'Seed.txt'              => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/seeds/BoardsSeed.php' => 'Board,Boards',
            ],
            [
                'Schema.txt'               => '{%SINGULAR_CC%},{%PLURAL_LC%}',
                '/schemas/BoardSchema.php' => 'Board,boards',
            ],
            [
                'Api.txt'            => '{%SINGULAR_CC%},{%PLURAL_CC%},{%SINGULAR_UC%},{%PLURAL_UC%}',
                '/api/BoardsApi.php' => 'Board,Boards,BOARD,BOARDS',
            ],
            [
                'ApiAuthorization.txt'     =>
                    '{%SINGULAR_CC%},{%PLURAL_CC%},{%SINGULAR_UC%},{%PLURAL_UC%},{%SINGULAR_LC%}',
                '/policies/BoardRules.php' => 'Board,Boards,BOARD,BOARDS,board',
            ],
            [
                'ValidationRules.txt'   => '{%SINGULAR_CC%},{%PLURAL_LC%},{%SINGULAR_LC%}',
                '/rules/BoardRules.php' => 'Board,boards,board',
            ],
            [
                'JsonRuleSetOnCreate.txt'     => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/BoardCreate.php' => 'Board,board',
            ],
            [
                'JsonRuleSetOnUpdate.txt'     => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/BoardUpdate.php' => 'Board,board',
            ],
            [
                'JsonController.txt'                => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/controllers/BoardsController.php' => 'Board,Boards',
            ],
            [
                'JsonRoutes.txt'             => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardApiRoutes.php' => 'Board,Boards',
            ],
        ];

        foreach ($data as $item) {
            $files  = array_keys($item);
            $bodies = array_values($item);
            $this->fileSystemMock->shouldReceive('read')->once()
                ->with($this->getPathToResource($files[0]))->andReturn($bodies[1]);
            $this->fileSystemMock->shouldReceive('write')->once()
                ->with($files[1], $bodies[1])->andReturnUndefined();
        }

        MakeCommand::execute($container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test command for make model seed.
     */
    public function testMakeSeed(): void
    {
        $container = $this->createContainerWithSettings([
            DataSettingsInterface::KEY_SEEDS_FOLDER => '/seeds',
        ]);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_SEED,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();

        $data = [
            [
                'Seed.txt'              => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/seeds/BoardsSeed.php' => 'Board,Boards',
            ],
        ];

        foreach ($data as $item) {
            $files  = array_keys($item);
            $bodies = array_values($item);
            $this->fileSystemMock->shouldReceive('read')->once()
                ->with($this->getPathToResource($files[0]))->andReturn($bodies[1]);
            $this->fileSystemMock->shouldReceive('write')->once()
                ->with($files[1], $bodies[1])->andReturnUndefined();
        }

        MakeCommand::execute($container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test command for make model migration.
     */
    public function testMakeMigration(): void
    {
        $container = $this->createContainerWithSettings([
            DataSettingsInterface::KEY_MIGRATIONS_FOLDER => '/migrations',
        ]);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_MIGRATE,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();

        $data = [
            [
                'Migration.txt'                   => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/migrations/BoardsMigration.php' => 'Board,Boards',
            ],
        ];

        foreach ($data as $item) {
            $files  = array_keys($item);
            $bodies = array_values($item);
            $this->fileSystemMock->shouldReceive('read')->once()
                ->with($this->getPathToResource($files[0]))->andReturn($bodies[1]);
            $this->fileSystemMock->shouldReceive('write')->once()
                ->with($files[1], $bodies[1])->andReturnUndefined();
        }

        MakeCommand::execute($container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test command for make web controller.
     */
    public function testMakeWebController(): void
    {
        $container = $this->createContainerWithSettings([], [
            FluteSettingsInterface::KEY_WEB_CONTROLLERS_FOLDER => '/controllers',
            FluteSettingsInterface::KEY_ROUTES_FOLDER          => '/routes',
        ]);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_CONTROLLER,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();

        $data = [
            [
                'WebController.txt'                 => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/controllers/BoardsController.php' => 'Board,Boards',
            ],
            [
                'WebRoutes.txt'              => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardWebRoutes.php' => 'Board,Boards',
            ],
        ];

        foreach ($data as $item) {
            $files  = array_keys($item);
            $bodies = array_values($item);
            $this->fileSystemMock->shouldReceive('read')->once()
                ->with($this->getPathToResource($files[0]))->andReturn($bodies[1]);
            $this->fileSystemMock->shouldReceive('write')->once()
                ->with($files[1], $bodies[1])->andReturnUndefined();
        }

        MakeCommand::execute($container, $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * Test command descriptions.
     */
    public function testCommandDescriptions(): void
    {
        $this->assertNotEmpty(MakeCommand::getName());
        $this->assertNotEmpty(MakeCommand::getHelp());
        $this->assertNotEmpty(MakeCommand::getDescription());
        $this->assertNotEmpty(MakeCommand::getArguments());
        $this->assertEmpty(MakeCommand::getOptions());
    }

    /**
     * Test invalid class name.
     *
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     */
    public function testInvalidClassName1(): void
    {
        $inOut = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_JSONAPI,
            MakeCommand::ARG_SINGULAR => 'Invalid Class Name',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        MakeCommand::execute($this->createContainerWithSettings(), $inOut);
    }

    /**
     * Test invalid class name.
     *
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     */
    public function testInvalidClassName2(): void
    {
        $inOut = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_JSONAPI,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Invalid Class Name',
        ]);

        MakeCommand::execute($this->createContainerWithSettings(), $inOut);
    }

    /**
     * Test command called with invalid item parameter.
     */
    public function testInvalidItem(): void
    {
        $inOut = $this->createInOutMock(
            [
                MakeCommand::ARG_ITEM     => 'non_existing_item',
                MakeCommand::ARG_SINGULAR => 'Board',
                MakeCommand::ARG_PLURAL   => 'Boards',
            ],
            [],
            true
        );

        MakeCommand::execute($this->createContainerWithSettings(), $inOut);

        // Mockery will do checks when the test finishes
        $this->assertTrue(true);
    }

    /**
     * @param array $dataSettings
     * @param array $fluteSettings
     * @param array $authSettings
     *
     * @return Container
     */
    private function createContainerWithSettings(
        array $dataSettings = [],
        array $fluteSettings = [],
        array $authSettings = []
    ): Container {
        $container = new Container();

        /** @var Mock $providerMock */
        $container[SettingsProviderInterface::class] = $providerMock = Mockery::mock(SettingsProviderInterface::class);
        $providerMock->shouldReceive('get')
            ->zeroOrMoreTimes()->with(DataSettingsInterface::class)->andReturn($dataSettings);
        $providerMock->shouldReceive('get')
            ->zeroOrMoreTimes()->with(FluteSettingsInterface::class)->andReturn($fluteSettings);
        $providerMock->shouldReceive('get')
            ->zeroOrMoreTimes()->with(AuthorizationSettingsInterface::class)->andReturn($authSettings);

        $container[FileSystemInterface::class] = $this->fileSystemMock;

        return $container;
    }

    /**
     * @param array $arguments
     * @param array $options
     * @param bool  $expectErrors
     *
     * @return IoInterface
     */
    private function createInOutMock(array $arguments, array $options = [], bool $expectErrors = false): IoInterface
    {
        /** @var Mock $mock */
        $mock = Mockery::mock(IoInterface::class);
        $mock->shouldReceive('getArguments')->zeroOrMoreTimes()->withNoArgs()->andReturn($arguments);
        $mock->shouldReceive('getOptions')->zeroOrMoreTimes()->withNoArgs()->andReturn($options);
        if ($expectErrors === true) {
            $mock->shouldReceive('writeError')->zeroOrMoreTimes()->withAnyArgs()->andReturnSelf();
        }

        /** @var IoInterface $mock */

        return $mock;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getPathToResource(string $fileName): string
    {
        $root       = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..']);
        $filePath   = realpath(implode(DIRECTORY_SEPARATOR, [$root, 'src', 'Commands', 'MakeCommand.php']));
        $folderPath = substr($filePath, 0, -16);

        return implode(DIRECTORY_SEPARATOR, [$folderPath, '..', '..', 'res', 'CodeTemplates', $fileName]);
    }
}

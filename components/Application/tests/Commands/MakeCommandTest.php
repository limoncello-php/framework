<?php declare(strict_types=1);

namespace Limoncello\Tests\Application\Commands;

/**
 * Copyright 2015-2019 info@neomerx.com
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
    public function testMakeFullResource(): void
    {
        $this->checkOutputs(MakeCommand::ITEM_FULL_RESOURCE, 'Board', 'Boards', [
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
                'QueryRulesOnRead.txt'                  => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/validators/Board/BoardsReadQuery.php' => 'Board,Boards',
            ],
            [
                'ApiAuthorization.txt'     =>
                    '{%SINGULAR_CC%},{%PLURAL_CC%},{%SINGULAR_UC%},{%PLURAL_UC%},{%SINGULAR_LC%}',
                '/policies/BoardRules.php' => 'Board,Boards,BOARD,BOARDS,board',
            ],
            [
                'ValidationRules.txt'         => '{%SINGULAR_CC%},{%PLURAL_LC%},{%SINGULAR_LC%}',
                '/rules/Board/BoardRules.php' => 'Board,boards,board',
            ],
            [
                'JsonRulesOnCreate.txt'                 => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/Board/BoardCreateJson.php' => 'Board,board',
            ],
            [
                'JsonRulesOnUpdate.txt'                 => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/Board/BoardUpdateJson.php' => 'Board,board',
            ],
            [
                'JsonController.txt'                     => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/json-controllers/BoardsController.php' => 'Board,Boards',
            ],
            [
                'JsonRoutes.txt'             => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardApiRoutes.php' => 'Board,Boards',
            ],
            [
                'WebRulesOnCreate.txt'                  => '{%SINGULAR_CC%}',
                '/validators/Board/BoardCreateForm.php' => 'Board',
            ],
            [
                'WebRulesOnUpdate.txt'                  => '{%SINGULAR_CC%}',
                '/validators/Board/BoardUpdateForm.php' => 'Board',
            ],
            [
                'WebController.txt'                     => '{%SINGULAR_CC%},{%PLURAL_CC%},{%PLURAL_LC%},{%PLURAL_UC%}',
                '/web-controllers/BoardsController.php' => 'Board,Boards,boards,BOARDS',
            ],
            [
                'WebRoutes.txt'              => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardWebRoutes.php' => 'Board,Boards',
            ],
        ]);
    }

    /**
     * Test command for make web controller.
     */
    public function testMakeJsonApiResource(): void
    {
        $this->checkOutputs(MakeCommand::ITEM_JSON_API_RESOURCE, 'Board', 'Boards', [
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
                'QueryRulesOnRead.txt'                  => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/validators/Board/BoardsReadQuery.php' => 'Board,Boards',
            ],
            [
                'ApiAuthorization.txt'     =>
                    '{%SINGULAR_CC%},{%PLURAL_CC%},{%SINGULAR_UC%},{%PLURAL_UC%},{%SINGULAR_LC%}',
                '/policies/BoardRules.php' => 'Board,Boards,BOARD,BOARDS,board',
            ],
            [
                'ValidationRules.txt'         => '{%SINGULAR_CC%},{%PLURAL_LC%},{%SINGULAR_LC%}',
                '/rules/Board/BoardRules.php' => 'Board,boards,board',
            ],
            [
                'JsonRulesOnCreate.txt'                 => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/Board/BoardCreateJson.php' => 'Board,board',
            ],
            [
                'JsonRulesOnUpdate.txt'                 => '{%SINGULAR_CC%},{%SINGULAR_LC%}',
                '/validators/Board/BoardUpdateJson.php' => 'Board,board',
            ],
            [
                'JsonController.txt'                     => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/json-controllers/BoardsController.php' => 'Board,Boards',
            ],
            [
                'JsonRoutes.txt'             => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardApiRoutes.php' => 'Board,Boards',
            ],
        ]);
    }

    /**
     * Test command for make model seed.
     */
    public function testMakeWebResource(): void
    {
        $this->checkOutputs(MakeCommand::ITEM_WEB_RESOURCE, 'Board', 'Boards', [
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
                'QueryRulesOnRead.txt'                  => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/validators/Board/BoardsReadQuery.php' => 'Board,Boards',
            ],
            [
                'ApiAuthorization.txt'     =>
                    '{%SINGULAR_CC%},{%PLURAL_CC%},{%SINGULAR_UC%},{%PLURAL_UC%},{%SINGULAR_LC%}',
                '/policies/BoardRules.php' => 'Board,Boards,BOARD,BOARDS,board',
            ],
            [
                'ValidationRules.txt'         => '{%SINGULAR_CC%},{%PLURAL_LC%},{%SINGULAR_LC%}',
                '/rules/Board/BoardRules.php' => 'Board,boards,board',
            ],
            [
                'WebRulesOnCreate.txt'                  => '{%SINGULAR_CC%}',
                '/validators/Board/BoardCreateForm.php' => 'Board',
            ],
            [
                'WebRulesOnUpdate.txt'                  => '{%SINGULAR_CC%}',
                '/validators/Board/BoardUpdateForm.php' => 'Board',
            ],
            [
                'WebController.txt'                     => '{%SINGULAR_CC%},{%PLURAL_CC%},{%PLURAL_LC%},{%PLURAL_UC%}',
                '/web-controllers/BoardsController.php' => 'Board,Boards,boards,BOARDS',
            ],
            [
                'WebRoutes.txt'              => '{%SINGULAR_CC%},{%PLURAL_CC%}',
                '/routes/BoardWebRoutes.php' => 'Board,Boards',
            ],
        ]);
    }

    /**
     * Test command for make model migration.
     */
    public function testMakeDataResource(): void
    {
        $this->checkOutputs(MakeCommand::ITEM_DATA_RESOURCE, 'Board', 'Boards', [
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
        ]);
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
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_FULL_RESOURCE,
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
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_FULL_RESOURCE,
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
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     *
     * @return void
     */
    public function testItShouldFailIfRootDirectoryDoNotExist(): void
    {
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/models')->andReturnTrue();
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/migrations')->andReturnTrue();

        // this one will trigger the error
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/seeds')->andReturnFalse();

        $this->prepareDataForDataResourceToEmulateFileSystemIssuesAndRunTheCommand();
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     *
     * @return void
     */
    public function testItShouldFailIfOneOfTheFilesAlreadyExists(): void
    {
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/models')->andReturnTrue();
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/migrations')->andReturnTrue();
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/seeds')->andReturnTrue();

        // this one will trigger the error
        $this->fileSystemMock->shouldReceive('exists')
            ->once()->with('/seeds/BoardsSeed.php')->andReturnTrue();

        $this->prepareDataForDataResourceToEmulateFileSystemIssuesAndRunTheCommand();
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     *
     * @return void
     */
    public function testItShouldFailIfOutputFolderIsNotWritable(): void
    {
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/models')->andReturnTrue();
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/migrations')->andReturnTrue();
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with('/seeds')->andReturnTrue();

        // this one will trigger the error
        $this->fileSystemMock->shouldReceive('isWritable')->once()->with('/seeds')->andReturnFalse();

        $this->prepareDataForDataResourceToEmulateFileSystemIssuesAndRunTheCommand();
    }

    /**
     * @expectedException \Limoncello\Application\Exceptions\InvalidArgumentException
     *
     * @return void
     */
    public function testItShouldFailIfRootFolderIsNotWritable(): void
    {
        $this->fileSystemMock->shouldReceive('exists')->once()->with('/migrations')->andReturnTrue();

        // this one will trigger the error
        $this->fileSystemMock->shouldReceive('isWritable')->once()->with('/migrations')->andReturnFalse();

        $this->prepareDataForDataResourceToEmulateFileSystemIssuesAndRunTheCommand();
    }

    /**
     * Run command test.
     *
     * @param string $command
     * @param string $singular
     * @param string $plural
     * @param array  $fileExpectations
     *
     * @return void
     */
    private function checkOutputs(string $command, string $singular, string $plural, array $fileExpectations): void
    {
        $dataSettings  = [
            DataSettingsInterface::KEY_MODELS_FOLDER     => '/models',
            DataSettingsInterface::KEY_MIGRATIONS_FOLDER => '/migrations',
            DataSettingsInterface::KEY_SEEDS_FOLDER      => '/seeds',
        ];
        $fluteSettings = [
            FluteSettingsInterface::KEY_SCHEMAS_FOLDER               => '/schemas',
            FluteSettingsInterface::KEY_JSON_VALIDATION_RULES_FOLDER => '/rules',
            FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER       => '/validators',
            FluteSettingsInterface::KEY_JSON_CONTROLLERS_FOLDER      => '/json-controllers',
            FluteSettingsInterface::KEY_WEB_CONTROLLERS_FOLDER       => '/web-controllers',
            FluteSettingsInterface::KEY_ROUTES_FOLDER                => '/routes',
            FluteSettingsInterface::KEY_API_FOLDER                   => '/api',
        ];
        $authSettings  = [
            AuthorizationSettingsInterface::KEY_POLICIES_FOLDER => '/policies',
        ];

        $existingFolders = array_merge(
            array_values($dataSettings),
            array_values($fluteSettings),
            array_values($authSettings)
        );
        foreach ($existingFolders as $existingFolder) {
            $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->with($existingFolder)->andReturnTrue();
        }
        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();
        $this->fileSystemMock->shouldReceive('isWritable')->zeroOrMoreTimes()->withAnyArgs()->andReturnTrue();
        $this->fileSystemMock->shouldReceive('createFolder')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        $container = $this->createContainerWithSettings($dataSettings, $fluteSettings, $authSettings);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => $command,
            MakeCommand::ARG_SINGULAR => $singular,
            MakeCommand::ARG_PLURAL   => $plural,
        ]);

        foreach ($fileExpectations as $expectation) {
            $files  = array_keys($expectation);
            $bodies = array_values($expectation);
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
     * @return void
     */
    private function prepareDataForDataResourceToEmulateFileSystemIssuesAndRunTheCommand(): void
    {
        $dataSettings  = [
            DataSettingsInterface::KEY_MODELS_FOLDER     => '/models',
            DataSettingsInterface::KEY_MIGRATIONS_FOLDER => '/migrations',
            DataSettingsInterface::KEY_SEEDS_FOLDER      => '/seeds',
        ];

        $this->fileSystemMock->shouldReceive('exists')->zeroOrMoreTimes()->withAnyArgs()->andReturnFalse();
        $this->fileSystemMock->shouldReceive('isWritable')->zeroOrMoreTimes()->withAnyArgs()->andReturnTrue();
        $this->fileSystemMock->shouldReceive('createFolder')->zeroOrMoreTimes()->withAnyArgs()->andReturnUndefined();

        $container = $this->createContainerWithSettings($dataSettings, [], []);
        $inOut     = $this->createInOutMock([
            MakeCommand::ARG_ITEM     => MakeCommand::ITEM_DATA_RESOURCE,
            MakeCommand::ARG_SINGULAR => 'Board',
            MakeCommand::ARG_PLURAL   => 'Boards',
        ]);

        $fileExpectations = [
            [
                'Model.txt'         => 'content does not matter for this test',
                '/models/Board.php' => 'content does not matter for this test',
            ],
            [
                'Migration.txt'                   => 'content does not matter for this test',
                '/migrations/BoardsMigration.php' => 'content does not matter for this test',
            ],
            [
                'Seed.txt'              => 'content does not matter for this test',
                '/seeds/BoardsSeed.php' => 'content does not matter for this test',
            ],
        ];

        foreach ($fileExpectations as $expectation) {
            $files  = array_keys($expectation);
            $bodies = array_values($expectation);
            $this->fileSystemMock->shouldReceive('read')->once()
                ->with($this->getPathToResource($files[0]))->andReturn($bodies[1]);
        }

        MakeCommand::execute($container, $inOut);

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

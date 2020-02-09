<?php declare(strict_types=1);

namespace Limoncello\Application\Commands;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Application\Exceptions\InvalidArgumentException;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\Packages\AuthorizationSettingsInterface;
use Limoncello\Contracts\Settings\Packages\DataSettingsInterface;
use Limoncello\Contracts\Settings\Packages\FluteSettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function array_merge;
use function implode;
use function preg_match;
use function str_replace;
use function strtolower;
use function strtoupper;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MakeCommand implements CommandInterface
{
    /**
     * Command name.
     */
    const NAME = 'l:make';

    /** Argument name */
    const ARG_ITEM = 'item';

    /** Argument name */
    const ARG_SINGULAR = 'singular';

    /** Argument name */
    const ARG_PLURAL = 'plural';

    /** Command action */
    const ITEM_DATA_RESOURCE = 'data-resource';

    /** Command action */
    const ITEM_WEB_RESOURCE = 'web-resource';

    /** Command action */
    const ITEM_JSON_API_RESOURCE = 'json-resource';

    /** Command action */
    const ITEM_FULL_RESOURCE = 'resource';

    /**
     * Taken from http://php.net/manual/en/language.oop5.basic.php
     */
    protected const VALID_CLASS_NAME_REGEX = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return static::NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Creates necessary classes for models, migrations and data seeds.';
    }

    /**
     * @inheritdoc
     */
    public static function getHelp(): string
    {
        return 'This command creates necessary classes for models, migrations and data seeds.';
    }

    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        $data     = static::ITEM_DATA_RESOURCE;
        $web      = static::ITEM_WEB_RESOURCE;
        $json     = static::ITEM_JSON_API_RESOURCE;
        $resource = static::ITEM_FULL_RESOURCE;

        return [
            [
                static::ARGUMENT_NAME        => static::ARG_ITEM,
                static::ARGUMENT_DESCRIPTION => "Action such as `$data`, `$web`, `$json` or `$resource`.",
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
            [
                static::ARGUMENT_NAME        => static::ARG_SINGULAR,
                static::ARGUMENT_DESCRIPTION => 'Singular name in camel case (e.g. `Post`).',
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
            [
                static::ARGUMENT_NAME        => static::ARG_PLURAL,
                static::ARGUMENT_DESCRIPTION => 'Plural name in camel case (e.g. `Posts`).',
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function execute(ContainerInterface $container, IoInterface $inOut): void
    {
        (new static())->run($container, $inOut);
    }

    /**
     * @param ContainerInterface $container
     * @param IoInterface        $inOut
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function run(ContainerInterface $container, IoInterface $inOut): void
    {
        $item     = $inOut->getArguments()[static::ARG_ITEM];
        $singular = $inOut->getArguments()[static::ARG_SINGULAR];
        $plural   = $inOut->getArguments()[static::ARG_PLURAL];

        if ($this->isValidShortClassName($singular) === false) {
            throw new InvalidArgumentException("`$singular` is not a valid class name.");
        }
        if ($this->isValidShortClassName($plural) === false) {
            throw new InvalidArgumentException("`$plural` is not a valid class name.");
        }

        /** @var FileSystemInterface $fileSystem */
        $fileSystem = $container->get(FileSystemInterface::class);
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(SettingsProviderInterface::class);

        $dataTemplates = function () use ($settingsProvider, $fileSystem, $singular, $plural) : array {
            return [
                $this->composeMigration($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeSeed($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeModel($settingsProvider, $fileSystem, $singular, $plural),
            ];
        };

        $basicTemplates = function () use ($settingsProvider, $fileSystem, $singular, $plural) : array {
            return [
                $this->composeSchema($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeAuthorization($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeApi($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeValidationRules($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeQueryValidationOnReadRules($settingsProvider, $fileSystem, $singular, $plural),
            ];
        };

        $webTemplates = function () use ($settingsProvider, $fileSystem, $singular, $plural) : array {
            return [
                $this->composeWebValidationOnCreateRules($settingsProvider, $fileSystem, $singular),
                $this->composeWebValidationOnUpdateRules($settingsProvider, $fileSystem, $singular),
                $this->composeWebController($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeWebRoute($settingsProvider, $fileSystem, $singular, $plural),
            ];
        };

        $jsonTemplates = function () use ($settingsProvider, $fileSystem, $singular, $plural) : array {
            return [
                $this->composeJsonValidationOnCreateRules($settingsProvider, $fileSystem, $singular),
                $this->composeJsonValidationOnUpdateRules($settingsProvider, $fileSystem, $singular),
                $this->composeJsonController($settingsProvider, $fileSystem, $singular, $plural),
                $this->composeJsonRoute($settingsProvider, $fileSystem, $singular, $plural),
            ];
        };

        switch ($item) {
            case static::ITEM_DATA_RESOURCE:
                $this->createTemplates($fileSystem, array_merge(
                    $dataTemplates()
                ));
                break;
            case static::ITEM_WEB_RESOURCE:
                $this->createTemplates($fileSystem, array_merge(
                    $dataTemplates(),
                    $basicTemplates(),
                    $webTemplates()
                ));
                break;
            case static::ITEM_JSON_API_RESOURCE:
                $this->createTemplates($fileSystem, array_merge(
                    $dataTemplates(),
                    $basicTemplates(),
                    $jsonTemplates()
                ));
                break;
            case static::ITEM_FULL_RESOURCE:
                $this->createTemplates($fileSystem, array_merge(
                    $dataTemplates(),
                    $basicTemplates(),
                    $webTemplates(),
                    $jsonTemplates()
                ));
                break;
            default:
                $inOut->writeError("Unsupported item type `$item`." . PHP_EOL);
                break;
        }
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeMigration(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(DataSettingsInterface::class)[DataSettingsInterface::KEY_MIGRATIONS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'Migration.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('Migration.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeSeed(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(DataSettingsInterface::class)[DataSettingsInterface::KEY_SEEDS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'Seed.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('Seed.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeModel(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(DataSettingsInterface::class)[DataSettingsInterface::KEY_MODELS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . '.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('Model.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%SINGULAR_LC%}' => strtolower($singular),
                '{%SINGULAR_UC%}' => strtoupper($singular),
                '{%PLURAL_LC%}'   => strtolower($plural),
                '{%PLURAL_UC%}'   => strtoupper($plural),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeSchema(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_SCHEMAS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'Schema.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('Schema.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_LC%}'   => strtolower($plural),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeApi(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_API_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'Api.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('Api.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
                '{%SINGULAR_UC%}' => strtoupper($singular),
                '{%PLURAL_UC%}'   => strtoupper($plural),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeAuthorization(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(AuthorizationSettingsInterface::class)[AuthorizationSettingsInterface::KEY_POLICIES_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'Rules.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('ApiAuthorization.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
                '{%SINGULAR_LC%}' => strtolower($singular),
                '{%PLURAL_UC%}'   => strtoupper($plural),
                '{%SINGULAR_UC%}' => strtoupper($singular),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeValidationRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATION_RULES_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'Rules.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('ValidationRules.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
                '{%SINGULAR_LC%}' => strtolower($singular),
                '{%PLURAL_LC%}'   => strtolower($plural),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     *
     * @return TemplateOutput
     */
    private function composeJsonValidationOnCreateRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'CreateJson.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('JsonRulesOnCreate.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%SINGULAR_LC%}' => strtolower($singular),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     *
     * @return TemplateOutput
     */
    private function composeJsonValidationOnUpdateRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'UpdateJson.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('JsonRulesOnUpdate.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%SINGULAR_LC%}' => strtolower($singular),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     *
     */
    private function composeQueryValidationOnReadRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'ReadQuery.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('QueryRulesOnRead.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeJsonController(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_CONTROLLERS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'Controller.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('JsonController.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeJsonRoute(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_ROUTES_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'ApiRoutes.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('JsonRoutes.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     *
     * @return TemplateOutput
     */
    private function composeWebValidationOnCreateRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'CreateForm.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('WebRulesOnCreate.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     *
     * @return TemplateOutput
     */
    private function composeWebValidationOnUpdateRules(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'UpdateForm.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('WebRulesOnUpdate.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent, $singular);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeWebController(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider
                      ->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_WEB_CONTROLLERS_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $plural . 'Controller.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('WebController.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
                '{%PLURAL_LC%}'   => strtolower($plural),
                '{%PLURAL_UC%}'   => strtoupper($plural),
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param SettingsProviderInterface $settingsProvider
     * @param FileSystemInterface       $fileSystem
     * @param string                    $singular
     * @param string                    $plural
     *
     * @return TemplateOutput
     */
    private function composeWebRoute(
        SettingsProviderInterface $settingsProvider,
        FileSystemInterface $fileSystem,
        string $singular,
        string $plural
    ): TemplateOutput {
        $folder = $settingsProvider->get(FluteSettingsInterface::class)[FluteSettingsInterface::KEY_ROUTES_FOLDER];

        $outputRootFolder = $this->filterOutFolderMask($folder);
        $outputFileName   = $singular . 'WebRoutes.php';
        $outputContent    = $this->composeTemplateContent(
            $fileSystem,
            $this->getTemplatePath('WebRoutes.txt'),
            [
                '{%SINGULAR_CC%}' => $singular,
                '{%PLURAL_CC%}'   => $plural,
            ]
        );

        return new TemplateOutput($outputRootFolder, $outputFileName, $outputContent);
    }

    /**
     * @param FileSystemInterface $fileSystem
     * @param TemplateOutput[]    $templateOutputs
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function createTemplates(FileSystemInterface $fileSystem, array $templateOutputs): void
    {
        // before making any changes in the filesystem we have to check there is a good chance we can make it
        foreach ($templateOutputs as $templateOutput) {
            if ($fileSystem->exists($templateOutput->getOutputRootFolder()) === false) {
                $rootFolder = $templateOutput->getOutputRootFolder();
                throw new InvalidArgumentException("Folder `$rootFolder` do not exist.");
            }

            if ($fileSystem->exists($templateOutput->getOutputPath()) === true) {
                $filePath = $templateOutput->getOutputPath();
                throw new InvalidArgumentException("File `$filePath` already exists.");
            }

            $outFolder = $templateOutput->getOutputFolder();
            if ($fileSystem->exists($outFolder) === true) {
                // the folder already exist so we have to check it is writable
                if ($fileSystem->isWritable($outFolder) === false) {
                    throw new InvalidArgumentException("Folder `$outFolder` is not writable.");
                }
            } else {
                // it should be a root folder with not yet existing sub-folder so root should be writable
                $rootFolder = $templateOutput->getOutputRootFolder();
                if ($fileSystem->isWritable($rootFolder) === false) {
                    throw new InvalidArgumentException("Folder `$rootFolder` is not writable.");
                }
            }
        }

        foreach ($templateOutputs as $templateOutput) {
            if ($fileSystem->exists($templateOutput->getOutputFolder()) === false) {
                $fileSystem->createFolder($templateOutput->getOutputFolder());
            }
            $fileSystem->write($templateOutput->getOutputPath(), $templateOutput->getOutputContent());
        }
    }

    /**
     * @param FileSystemInterface $fileSystem
     * @param string              $templatePath
     * @param iterable            $templateParams
     *
     * @return string
     */
    private function composeTemplateContent(
        FileSystemInterface $fileSystem,
        string $templatePath,
        iterable $templateParams
    ): string {
        $templateContent = $fileSystem->read($templatePath);

        foreach ($templateParams as $key => $value) {
            $templateContent = str_replace($key, $value, $templateContent);
        }

        return $templateContent;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isValidShortClassName(string $name): bool
    {
        return empty($name) === false && preg_match(static::VALID_CLASS_NAME_REGEX, $name) === 1;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getTemplatePath(string $fileName): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'res', 'CodeTemplates', $fileName]);
    }

    /**
     * Folder paths might include masks such as `**`. This function tries to filter them out.
     *
     * @param string $folder
     *
     * @return string
     */
    private function filterOutFolderMask(string $folder): string
    {
        $mask = '**';

        $folder = str_replace($mask . DIRECTORY_SEPARATOR, '', $folder);
        $folder = str_replace($mask, '', $folder);

        return $folder;
    }
}

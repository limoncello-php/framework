<?php namespace Limoncello\Application\Commands;

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

/**
 * @package Limoncello\Application
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
    const ITEM_MIGRATE = 'migrate';

    /** Command action */
    const ITEM_SEED = 'seed';

    /** Command action */
    const ITEM_CONTROLLER = 'controller';

    /** Command action */
    const ITEM_JSONAPI = 'jsonapi';

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
        $migrate = static::ITEM_MIGRATE;
        $seed    = static::ITEM_SEED;
        $jsonapi = static::ITEM_JSONAPI;

        return [
            [
                static::ARGUMENT_NAME        => static::ARG_ITEM,
                static::ARGUMENT_DESCRIPTION => "Action such as `$migrate`, `$seed` or `$jsonapi`.",
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

        switch ($item) {
            case static::ITEM_MIGRATE:
                $this->createTemplates($this->getFileSystem($container), [
                    $this->composeMigrationParameters($container, $singular, $plural),
                ]);
                break;
            case static::ITEM_SEED:
                $this->createTemplates($this->getFileSystem($container), [
                    $this->composeSeedParameters($container, $singular, $plural),
                ]);
                break;
            case static::ITEM_CONTROLLER:
                $this->createTemplates($this->getFileSystem($container), [
                    $this->composeWebControllerParameters($container, $singular, $plural),
                    $this->composeWebRouteParameters($container, $singular, $plural),
                ]);
                break;
            case static::ITEM_JSONAPI:
                $this->createTemplates($this->getFileSystem($container), [
                    $this->composeMigrationParameters($container, $singular, $plural),
                    $this->composeSeedParameters($container, $singular, $plural),
                    $this->composeModelParameters($container, $singular, $plural),
                    $this->composeSchemaParameters($container, $singular, $plural),
                    $this->composeApiParameters($container, $singular, $plural),
                    $this->composeAuthorizationParameters($container, $singular, $plural),
                    $this->composeValidationRulesParameters($container, $singular, $plural),
                    $this->composeValidationOnCreateRuleSetsParameters($container, $singular),
                    $this->composeValidationOnUpdateRuleSetsParameters($container, $singular),
                    $this->composeJsonControllerParameters($container, $singular, $plural),
                    $this->composeJsonRouteParameters($container, $singular, $plural),
                ]);
                break;
            default:
                $inOut->writeError("Unsupported item type `$item`." . PHP_EOL);
                break;
        }
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeMigrationParameters(ContainerInterface $container, string $singular, string $plural): array
    {
        $outputPath = $this->getDataSettings($container)[DataSettingsInterface::KEY_MIGRATIONS_FOLDER]
            . DIRECTORY_SEPARATOR . $plural . 'Migration.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
        ];

        return [$outputPath, $this->getTemplatePath('Migration.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeSeedParameters(ContainerInterface $container, string $singular, string $plural): array
    {
        $outputPath = $this->getDataSettings($container)[DataSettingsInterface::KEY_SEEDS_FOLDER]
            . DIRECTORY_SEPARATOR . $plural . 'Seed.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
        ];

        return [$outputPath, $this->getTemplatePath('Seed.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeModelParameters(ContainerInterface $container, string $singular, string $plural): array
    {
        $outputPath = $this->getDataSettings($container)[DataSettingsInterface::KEY_MODELS_FOLDER]
            . DIRECTORY_SEPARATOR . $singular . '.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%SINGULAR_LC%}' => strtolower($singular),
            '{%SINGULAR_UC%}' => strtoupper($singular),
            '{%PLURAL_LC%}'   => strtolower($plural),
            '{%PLURAL_UC%}'   => strtoupper($plural),
        ];

        return [$outputPath, $this->getTemplatePath('Model.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeSchemaParameters(ContainerInterface $container, string $singular, string $plural): array
    {
        $outputPath = $this->getFluteSettings($container)[FluteSettingsInterface::KEY_SCHEMAS_FOLDER]
            . DIRECTORY_SEPARATOR . $singular . 'Schema.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_LC%}'   => strtolower($plural),
        ];

        return [$outputPath, $this->getTemplatePath('Schema.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeApiParameters(ContainerInterface $container, string $singular, string $plural): array
    {
        $outputPath = $this->getFluteSettings($container)[FluteSettingsInterface::KEY_API_FOLDER]
            . DIRECTORY_SEPARATOR . $plural . 'Api.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
            '{%SINGULAR_UC%}' => strtoupper($singular),
            '{%PLURAL_UC%}'   => strtoupper($plural),
        ];

        return [$outputPath, $this->getTemplatePath('Api.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeAuthorizationParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $outputPath = $this->getAuthorizationSettings($container)[AuthorizationSettingsInterface::KEY_POLICIES_FOLDER]
            . DIRECTORY_SEPARATOR . $singular . 'Rules.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
            '{%SINGULAR_LC%}' => strtolower($singular),
            '{%PLURAL_UC%}'   => strtoupper($plural),
            '{%SINGULAR_UC%}' => strtoupper($singular),
        ];

        return [$outputPath, $this->getTemplatePath('ApiAuthorization.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeValidationRulesParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $outputPath = $this->getFluteSettings($container)[FluteSettingsInterface::KEY_JSON_VALIDATION_RULES_FOLDER]
            . DIRECTORY_SEPARATOR . $singular . 'Rules.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%SINGULAR_LC%}' => strtolower($singular),
            '{%PLURAL_LC%}'   => strtolower($plural),
        ];

        return [$outputPath, $this->getTemplatePath('ValidationRules.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeValidationOnCreateRuleSetsParameters(
        ContainerInterface $container,
        string $singular
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $singular . 'Create.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%SINGULAR_LC%}' => strtolower($singular),
        ];

        return [$outputPath, $this->getTemplatePath('JsonRuleSetOnCreate.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeValidationOnUpdateRuleSetsParameters(
        ContainerInterface $container,
        string $singular
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_JSON_VALIDATORS_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $singular . 'Update.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%SINGULAR_LC%}' => strtolower($singular),
        ];

        return [$outputPath, $this->getTemplatePath('JsonRuleSetOnUpdate.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeJsonControllerParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_JSON_CONTROLLERS_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $plural . 'Controller.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
        ];

        return [$outputPath, $this->getTemplatePath('JsonController.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeJsonRouteParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_ROUTES_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $singular . 'ApiRoutes.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
        ];

        return [$outputPath, $this->getTemplatePath('JsonRoutes.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeWebControllerParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_WEB_CONTROLLERS_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $plural . 'Controller.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
            '{%PLURAL_LC%}'   => strtolower($plural),
        ];

        return [$outputPath, $this->getTemplatePath('WebController.txt'), $parameters];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $singular
     * @param string             $plural
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function composeWebRouteParameters(
        ContainerInterface $container,
        string $singular,
        string $plural
    ): array {
        $folder     = $this->filterOutFolderMask(
            $this->getFluteSettings($container)[FluteSettingsInterface::KEY_ROUTES_FOLDER]
        );
        $outputPath = $folder . DIRECTORY_SEPARATOR . $singular . 'WebRoutes.php';
        $parameters = [
            '{%SINGULAR_CC%}' => $singular,
            '{%PLURAL_CC%}'   => $plural,
        ];

        return [$outputPath, $this->getTemplatePath('WebRoutes.txt'), $parameters];
    }

    /**
     * @param FileSystemInterface $fileSystem
     * @param array               $pathsAndParams
     *
     * @return void
     */
    private function createTemplates(FileSystemInterface $fileSystem, array $pathsAndParams): void
    {
        foreach ($pathsAndParams as list($outputPath)) {
            if ($fileSystem->exists($outputPath) === true) {
                throw new InvalidArgumentException("File `$outputPath` already exists.");
            }
        }

        foreach ($pathsAndParams as list($outputPath, $templatePath, $parameters)) {
            $this->writeByTemplate($fileSystem, $outputPath, $templatePath, $parameters);
        }
    }

    /**
     * @param FileSystemInterface $fileSystem
     * @param string              $outputPath
     * @param string              $templatePath
     * @param iterable            $parameters
     *
     * @return void
     */
    private function writeByTemplate(
        FileSystemInterface $fileSystem,
        string $outputPath,
        string $templatePath,
        iterable $parameters
    ): void {
        $templateContent = $fileSystem->read($templatePath);
        $outputContent   = $this->replaceInTemplate($templateContent, $parameters);
        $fileSystem->write($outputPath, $outputContent);
    }

    /**
     * @param string   $template
     * @param iterable $parameters
     *
     * @return string
     */
    private function replaceInTemplate(string $template, iterable $parameters): string
    {
        $result = $template;
        foreach ($parameters as $key => $value) {
            $result = str_replace($key, $value, $result);
        }

        return $result;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return FileSystemInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getFileSystem(ContainerInterface $container): FileSystemInterface
    {
        assert($container->has(FileSystemInterface::class));

        /** @var FileSystemInterface $fileSystem */
        $fileSystem = $container->get(FileSystemInterface::class);

        return $fileSystem;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SettingsProviderInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getSettingsProvider(ContainerInterface $container): SettingsProviderInterface
    {
        assert($container->has(SettingsProviderInterface::class));

        /** @var SettingsProviderInterface $provider */
        $provider = $container->get(SettingsProviderInterface::class);

        return $provider;
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
     * @param ContainerInterface $container
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getDataSettings(ContainerInterface $container): array
    {
        $dataSettings = $this->getSettingsProvider($container)->get(DataSettingsInterface::class);

        return $dataSettings;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getFluteSettings(ContainerInterface $container): array
    {
        $dataSettings = $this->getSettingsProvider($container)->get(FluteSettingsInterface::class);

        return $dataSettings;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getAuthorizationSettings(ContainerInterface $container): array
    {
        $dataSettings = $this->getSettingsProvider($container)->get(AuthorizationSettingsInterface::class);

        return $dataSettings;
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

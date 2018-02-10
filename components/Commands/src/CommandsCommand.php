<?php namespace Limoncello\Commands;

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

use Composer\Command\BaseCommand;
use Limoncello\Commands\Traits\CacheFilePathTrait;
use Limoncello\Commands\Traits\CommandSerializationTrait;
use Limoncello\Commands\Traits\CommandTrait;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as S;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a special command which is immediately available from composer. The main purpose of it is to
 * load command list from user application and generate a special cache file with the list. On the next
 * composer run the list would be loaded into composer and all the commands would be available.
 *
 * Also it provides such a nice feature as generation of an empty/template command for the developer.
 *
 * @package Limoncello\Commands
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommandsCommand extends BaseCommand
{
    use CommandTrait, CommandSerializationTrait, CacheFilePathTrait;

    /**
     * Command name.
     */
    const NAME = 'l:commands';

    /** Argument name */
    const ARG_ACTION = 'action';

    /** Command action */
    const ACTION_CONNECT = 'connect';

    /** Command action */
    const ACTION_CREATE = 'create';

    /** Argument name */
    const ARG_CLASS = 'class';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(static::NAME);
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();

        $connect    = static::ACTION_CONNECT;
        $create     = static::ACTION_CREATE;
        $actionDesc = "Required action such as `$connect` to find and connect commands from application and plugins " .
            "or `$create` to create an empty command template.";

        $classDesc = "Required valid class name in commands' namespace for action `$create`.";

        $this
            ->setDescription('Manages commands executed from composer.')
            ->setHelp('This command connects plugin and user-defined commands and creates new commands.')
            ->setDefinition([
                new InputArgument(static::ARG_ACTION, InputArgument::REQUIRED, $actionDesc),
                new InputArgument(static::ARG_CLASS, InputArgument::OPTIONAL, $classDesc),
            ]);
    }


    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $inOut     = $this->wrapIo($input, $output);
        $container = $this->createContainer($this->getComposer(), static::NAME);

        $argAction = static::ARG_ACTION;
        $action    = $inOut->getArgument($argAction);
        switch ($action) {
            case static::ACTION_CONNECT:
                $this->executeConnect($container, $inOut);
                break;
            case static::ACTION_CREATE:
                $this->executeCreate($container, $inOut);
                break;
            default:
                $inOut->writeError("Unknown value `$action` for argument `$argAction`." . PHP_EOL);
                break;
        }
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
    private function executeConnect(ContainerInterface $container, IoInterface $inOut): void
    {
        assert($container->has(CommandStorageInterface::class));
        /** @var CommandStorageInterface $commandStorage */
        $commandStorage = $container->get(CommandStorageInterface::class);

        $commandClasses = [];
        foreach ($commandStorage->getAll() as $commandClass) {
            if (class_exists($commandClass) === false ||
                array_key_exists(CommandInterface::class, class_implements($commandClass)) === false
            ) {
                $inOut->writeWarning("Class `$commandClass` either do not exist or not a command class." . PHP_EOL);
                continue;
            }

            $inOut->writeInfo("Found command class `$commandClass`." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);

            $commandClasses[] = $this->commandClassToArray($commandClass);
        }

        if (empty($commandClasses) === false) {
            $now           = date(DATE_RFC2822);
            $data          = var_export($commandClasses, true);
            $content       = <<<EOT
<?php

// THIS FILE IS AUTO GENERATED. DO NOT EDIT IT MANUALLY.
// Generated at: $now

    return $data;

EOT;
            $cacheFilePath = $this->getCommandsCacheFilePath($this->getComposer());
            if (empty($cacheFilePath) === true) {
                $inOut->writeError("Commands cache file path is not set. Check your `Application` settings." . PHP_EOL);

                return;
            }

            $this->getFileSystem($container)->write($cacheFilePath, $content);

            $inOut->writeInfo('Commands connected.' . PHP_EOL);

            return;
        }

        $inOut->writeWarning('No commands found.' . PHP_EOL);
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
    private function executeCreate(ContainerInterface $container, IoInterface $inOut): void
    {
        $argClass = static::ARG_CLASS;
        if ($inOut->hasArgument($argClass) === false) {
            $inOut->writeError("Argument `$argClass` is not provided." . PHP_EOL);

            return;
        }
        $class = $inOut->getArgument($argClass);

        $fileSystem     = $this->getFileSystem($container);
        $commandsFolder = $this->getCommandsFolder($container);
        if (empty($commandsFolder) === true || $fileSystem->isFolder($commandsFolder) === false) {
            $inOut->writeError(
                "Commands folder `$commandsFolder` is not valid. Check your `Application` settings." . PHP_EOL
            );

            return;
        }

        $classPath = $commandsFolder . DIRECTORY_SEPARATOR . $class . '.php';
        if (ctype_alpha($class) === false ||
            $fileSystem->exists($classPath) === true
        ) {
            $inOut->writeError(
                "Class name `$class` does not look valid for a command. " .
                'Can you please choose another one?' . PHP_EOL
            );

            return;
        }

        $replace = function (string $template, iterable $parameters): string {
            $result = $template;
            foreach ($parameters as $key => $value) {
                $result = str_replace($key, $value, $result);
            }

            return $result;
        };

        $templateContent = $fileSystem->read(__DIR__ . DIRECTORY_SEPARATOR . 'SampleCommand.txt');
        $fileSystem->write($classPath, $replace($templateContent, [
            '{CLASS_NAME}'   => $class,
            '{COMMAND_NAME}' => strtolower($class),
            '{TO_DO}'        => 'TODO',
        ]));
    }

    /**
     * @param ContainerInterface $container
     *
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getCommandsFolder(ContainerInterface $container): string
    {
        assert($container->has(CacheSettingsProviderInterface::class));

        /** @var CacheSettingsProviderInterface $provider */
        $provider  = $container->get(CacheSettingsProviderInterface::class);
        $appConfig = $provider->getApplicationConfiguration();
        $folder    = $appConfig[S::KEY_COMMANDS_FOLDER];

        return $folder;
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
}

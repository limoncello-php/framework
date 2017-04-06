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
use Limoncello\Commands\Exceptions\ConfigurationException;
use Limoncello\Commands\Wrappers\ConsoleIoWrapper;
use Limoncello\Commands\Wrappers\DataArgumentWrapper;
use Limoncello\Commands\Wrappers\DataCommandWrapper;
use Limoncello\Commands\Wrappers\DataOptionWrapper;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Container\ContainerInterface;
use Limoncello\Contracts\Core\ApplicationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Commands
 */
class LimoncelloCommand extends BaseCommand
{
    /** Expected key at `composer.json` -> "extra" */
    const COMPOSER_JSON__EXTRA__APPLICATION = 'application';

    /** Expected key at `composer.json` -> "extra" -> "application" */
    const COMPOSER_JSON__EXTRA__APPLICATION__CLASS = 'class';

    /** Default application class name if not replaced via "extra" -> "application" -> "class" */
    const DEFAULT_APPLICATION_CLASS_NAME = '\\App\\Application';

    /**
     * @var DataCommandWrapper
     */
    private $wrapper;

    /**
     * @param CommandInterface $command
     */
    public function __construct(CommandInterface $command)
    {
        $this->wrapper = new DataCommandWrapper($command);

        parent::__construct($this->getWrapper()->getName());
    }

    /**
     * @inheritdoc
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setDescription($this->getWrapper()->getDescription())
            ->setHelp($this->getWrapper()->getHelp());

        foreach ($this->getWrapper()->getArguments() as $arg) {
            /** @var DataArgumentWrapper $arg */
            $this->addArgument($arg->getName(), $arg->getMode(), $arg->getDescription(), $arg->getDefault());
        }

        foreach ($this->getWrapper()->getOptions() as $opt) {
            /** @var DataOptionWrapper $opt */
            $this->addOption(
                $opt->getName(),
                $opt->getShortcut(),
                $opt->getMode(),
                $opt->getDescription(),
                $opt->getDefault()
            );
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getWrapper()->execute($this->getAppContainer(), $this->wrapIo($input, $output));
    }

    /**
     * @return ContainerInterface
     */
    private function getAppContainer(): ContainerInterface
    {
        // use application auto loader otherwise no app classes will be visible for us
        $autoLoaderPath = $this->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($autoLoaderPath) === true) {
            /** @noinspection PhpIncludeInspection */
            require_once $autoLoaderPath;
        }

        $application = null;
        $appClass    = $this->getValueFromApplicationExtra(
            static::COMPOSER_JSON__EXTRA__APPLICATION__CLASS,
            static::DEFAULT_APPLICATION_CLASS_NAME
        );
        if (class_exists($appClass) === false ||
            (($application = new $appClass()) instanceof ApplicationInterface) === false
        ) {
            $settingsPath =
                'extra->' .
                static::COMPOSER_JSON__EXTRA__APPLICATION . '->' .
                static::COMPOSER_JSON__EXTRA__APPLICATION__CLASS;
            throw new ConfigurationException(
                "Invalid application class specified '$appClass'. Check your settings at composer.json $settingsPath."
            );
        }

        /** @var ApplicationInterface $application */
        $container = $application->createContainer();

        return $container;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return IoInterface
     */
    private function wrapIo(InputInterface $input, OutputInterface $output): IoInterface
    {
        return new ConsoleIoWrapper($input, $output);
    }

    /**
     * @return DataCommandWrapper
     */
    private function getWrapper(): DataCommandWrapper
    {
        return $this->wrapper;
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return null|mixed
     */
    private function getValueFromApplicationExtra(string $key, $default = null)
    {
        $extra = $this->getComposer()->getPackage()->getExtra();
        $value = $extra[static::COMPOSER_JSON__EXTRA__APPLICATION][$key] ?? $default;

        return $value;
    }
}

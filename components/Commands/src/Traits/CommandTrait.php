<?php namespace Limoncello\Commands\Traits;

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
use Limoncello\Commands\Exceptions\ConfigurationException;
use Limoncello\Commands\Wrappers\ConsoleIoWrapper;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Core\ApplicationInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Limoncello\Commands
 */
trait CommandTrait
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return IoInterface
     */
    protected function wrapIo(InputInterface $input, OutputInterface $output): IoInterface
    {
        return new ConsoleIoWrapper($input, $output);
    }

    /**
     * @param Composer $composer
     * @param string   $commandName
     *
     * @return ContainerInterface
     */
    protected function createContainer(Composer $composer, string $commandName): ContainerInterface
    {
        // use application auto loader otherwise no app classes will be visible for us
        $autoLoaderPath = $this->getAutoloadPath($composer);
        if (file_exists($autoLoaderPath) === true) {
            /** @noinspection PhpIncludeInspection */
            require_once $autoLoaderPath;
        }

        $extra    = $this->getExtra($composer);
        $appKey   = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION;
        $classKey = CommandConstants::COMPOSER_JSON__EXTRA__APPLICATION__CLASS;
        $appClass = $extra[$appKey][$classKey] ?? CommandConstants::DEFAULT_APPLICATION_CLASS_NAME;
        if ($this->isValidApplicationClass($appClass) === false) {
            $settingsPath = "extra->$appKey->$classKey";
            throw new ConfigurationException(
                "Invalid application class specified '$appClass'. Check your settings at composer.json $settingsPath."
            );
        }

        $container = $this->createApplicationContainer($this->createApplication($appClass), $commandName);

        return $container;
    }

    /**
     * @param Composer $composer
     *
     * @return string
     */
    protected function getAutoloadPath(Composer $composer): string
    {
        return $composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'autoload.php';
    }

    /**
     * @param Composer $composer
     *
     * @return array
     */
    protected function getExtra(Composer $composer): array
    {
        return $composer->getPackage()->getExtra();
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isValidApplicationClass(string $className): bool
    {
        $reflectionClass = new ReflectionClass($className);

        return
            $reflectionClass->isInstantiable() === true &&
            $reflectionClass->implementsInterface(ApplicationInterface::class) === true;
    }

    /**
     * @param ApplicationInterface $application
     * @param string               $commandName
     *
     * @return ContainerInterface
     */
    protected function createApplicationContainer(
        ApplicationInterface $application,
        string $commandName
    ): ContainerInterface {
        return $application->createContainer(CommandConstants::HTTP_METHOD, '/' . $commandName);
    }

    /**
     * @param string $className
     *
     * @return ApplicationInterface
     */
    protected function createApplication(string $className): ApplicationInterface
    {
        return new $className();
    }
}

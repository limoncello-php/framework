<?php namespace Limoncello\Application\Packages\Application;

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

use Limoncello\Application\Commands\CommandStorage;
use Limoncello\Application\Packages\Application\ApplicationSettings as S;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\CommandStorageInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Provider\ProvidesCommandsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Core\Reflection\ClassIsTrait;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class ApplicationContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[CommandStorageInterface::class] =
            function (PsrContainerInterface $container): CommandStorageInterface {
                $creator = new class
                {
                    use ClassIsTrait;

                    /**
                     * @param string $commandsPath
                     * @param array  $providerClasses
                     *
                     * @return CommandStorageInterface
                     */
                    public function createCommandStorage(
                        string $commandsPath,
                        array $providerClasses
                    ): CommandStorageInterface {
                        $storage = new CommandStorage();

                        $interfaceName = CommandInterface::class;
                        foreach ($this->selectClasses($commandsPath, $interfaceName) as $commandClass) {
                            $storage->add($commandClass);
                        }

                        $interfaceName = ProvidesCommandsInterface::class;
                        foreach ($this->selectClassImplements($providerClasses, $interfaceName) as $providerClass) {
                            /** @var ProvidesCommandsInterface $providerClass */
                            foreach ($providerClass::getCommands() as $commandClass) {
                                $storage->add($commandClass);
                            }
                        }

                        return $storage;
                    }
                };

                /** @var SettingsProviderInterface $provider */
                $provider = $container->get(SettingsProviderInterface::class);
                $settings = $provider->get(S::class);

                $providerClasses  = $settings[S::KEY_PROVIDER_CLASSES];
                $commandsFolder   = $settings[S::KEY_COMMANDS_FOLDER];
                $commandsFileMask = $settings[S::KEY_COMMANDS_FILE_MASK] ?? '*.php';
                $commandsPath     = $commandsFolder . DIRECTORY_SEPARATOR . $commandsFileMask;

                $storage = $creator->createCommandStorage($commandsPath, $providerClasses);

                return $storage;
            };
    }
}

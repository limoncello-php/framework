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

use Limoncello\Application\Traits\ParseCallableTrait;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
abstract class ApplicationSettingsBase implements CommandInterface
{
    use ParseCallableTrait;

    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    protected function getApplicationSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(SettingsProviderInterface::class);
        $appSettings      = $settingsProvider->get(ApplicationSettingsInterface::class);

        return $appSettings;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return FileSystemInterface
     */
    protected function createFileSystem(ContainerInterface $container): FileSystemInterface
    {
        // TODO These commands want to have it from container. Need a feature for command to configure container (?).

        return $container->get(FileSystemInterface::class);
    }
}

<?php namespace Limoncello\Templates\Commands;

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

use Limoncello\Application\Contracts\FileSystemInterface;
use Limoncello\Application\FileSystem\FileSystem;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Templates\Package\TemplatesSettings;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Commands
 */
class TemplatesClean extends TemplatesBase
{
    /**
     * @inheritdoc
     */
    public function getCommandData(): array
    {
        return [
            self::COMMAND_NAME        => 'limoncello:cache:templates:clean',
            self::COMMAND_DESCRIPTION => 'Cleans templates caches.',
            self::COMMAND_HELP        => 'This command cleans caches for HTML templates.',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getArguments(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function execute(ContainerInterface $container, IoInterface $inOut)
    {
        $settings    = $this->getTemplatesSettings($container);
        $cacheFolder = $settings[TemplatesSettings::KEY_CACHE_FOLDER];

        $fileSystem = $this->createFileSystem();
        foreach ($fileSystem->scanFolder($cacheFolder) as $fileOrFolder) {
            $fileSystem->isFolder($fileOrFolder) === true ?
                $fileSystem->deleteFolderRecursive($fileOrFolder) : $fileSystem->delete($fileOrFolder);
        }
    }

    /**
     * @return FileSystemInterface
     */
    protected function createFileSystem(): FileSystemInterface
    {
        $fileSystem = new FileSystem();

        return $fileSystem;
    }
}

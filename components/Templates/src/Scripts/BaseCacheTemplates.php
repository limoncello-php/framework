<?php namespace Limoncello\Templates\Scripts;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Composer\Script\Event;
use Limoncello\AppCache\Contracts\FileSystemInterface;
use Limoncello\AppCache\FileSystem;
use Limoncello\Templates\TwigTemplates;

/**
 * @package Limoncello\Templates
 */
abstract class BaseCacheTemplates
{
    /**
     * @param Event  $event
     * @param string $templatesFolder
     * @param string $cacheFolder
     * @param array  $templates
     *
     * @return void
     */
    protected static function cacheTemplates(Event $event, $templatesFolder, $cacheFolder, array $templates)
    {
        $templateEngine = static::createCachingTemplateEngine($templatesFolder, $cacheFolder);

        foreach ($templates as $templateName) {
            // it will write template to cache
            $templateEngine->loadTemplate($templateName);
        }

        $event->getIO()->write('<info>Cached templates \'' . $cacheFolder . '\'.</info>');
    }

    /**
     * @param Event  $event
     * @param string $cacheFolder
     *
     * @return void
     */
    protected static function clearCacheFolder(Event $event, $cacheFolder)
    {
        $fileSystem  = static::createFileSystem();

        $allDeleted  = true;
        foreach ($fileSystem->scanFolder($cacheFolder) as $fileOrFolder) {
            $path = $cacheFolder . DIRECTORY_SEPARATOR . $fileOrFolder;
            if ($fileSystem->isFolder($path) === true) {
                $allDeleted = $allDeleted && $fileSystem->deleteFolderRecursive($path);
            }
        }

        $out = $event->getIO();
        if ($allDeleted !== true) {
            $out->writeError('<warning>Template cache was not fully cleaned.</warning>');
            return;
        }

        $out->write('<info>Template cache removed \'' . $cacheFolder . '\'.</info>');
    }

    /**
     * @return FileSystemInterface
     */
    protected static function createFileSystem()
    {
        return new FileSystem();
    }

    /**
     * @param string $templatesFolder
     * @param string $cacheFolder
     *
     * @return \Twig_Environment
     */
    protected static function createCachingTemplateEngine($templatesFolder, $cacheFolder)
    {
        $templates = new TwigTemplates($templatesFolder, $cacheFolder);

        return $templates;
    }
}

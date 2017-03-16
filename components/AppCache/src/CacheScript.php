<?php namespace Limoncello\AppCache;

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

/**
 * @package Limoncello\AppCache
 */
abstract class CacheScript
{
    /** Default namespace for cached data class. */
    const CACHED_NAMESPACE = 'Cached';

    /** Method to get data from cached data class. */
    const CACHED_METHOD = 'get';

    /** Class name. Child classes must override it. */
    const CACHED_CLASS = null;

    /**
     * @param Event $event
     *
     * @return void
     */
    public static function clear(Event $event)
    {
        $filePath = static::getFilePath($event);
        $name     = '\'' . static::CACHED_CLASS . '\'';
        if ($filePath === null || static::getFileSystem()->delete($filePath) !== true) {
            $event
                ->getIO()
                ->writeError('<warning>Removal of ' . $name . ' cache \'' . $filePath . '\' failed.</warning>');
            return;
        }
        $event->getIO()->write('<info>Cache file for ' . $name . ' removed \'' . $filePath . '\'.</info>');
    }

    /**
     * @param string $className
     * @param mixed  $value
     * @param string $methodName
     * @param string $namespace
     *
     * @return string
     */
    protected static function composeContent(
        $className,
        $value,
        $methodName,
        $namespace
    ) {
        $now     = date(DATE_RFC2822);
        $data    = var_export($value, true);
        $content = <<<EOT
<?php namespace $namespace;

// THIS FILE IS AUTO GENERATED. DO NOT EDIT IT MANUALLY.
// Generated at: $now

class $className
{
    public static function $methodName()
    {
        return $data;
    }
}

EOT;

        return $content;
    }

    /**
     * @param Event $event
     *
     * @return string|null
     */
    protected static function getFilePath(Event $event)
    {
        $composer = $event->getComposer();

        $namespace = static::CACHED_NAMESPACE . '\\';
        $auto      = $composer->getPackage()->getAutoload();
        if (isset($auto['psr-4'][$namespace]) === false) {
            $event
                ->getIO()
                ->writeError(
                    'Path for namespace \'' . $namespace . '\' is not found in composer.json autoload psr-4 section.'
                );
            return null;
        }

        $cachedDir = realpath($auto['psr-4'][$namespace]);
        $filePath  = $cachedDir . DIRECTORY_SEPARATOR . static::CACHED_CLASS . '.php';

        return $filePath;
    }

    /**
     * @param array $data
     * @param Event $event
     *
     * @return void
     */
    protected static function cacheData(array $data, Event $event)
    {
        $filePath = static::getFilePath($event);
        $name     = '\'' . static::CACHED_CLASS . '\'';
        $content  = static::composeContent(
            static::CACHED_CLASS,
            $data,
            static::CACHED_METHOD,
            static::CACHED_NAMESPACE
        );
        if ($filePath === null || static::getFileSystem()->write($filePath, $content) !== true) {
            $event->getIO()->writeError('<warning>Caching ' . $name . ' to \'' . $filePath . '\' failed.</warning>');
            return;
        }
        $event->getIO()->write('<info>Cached ' . $name . ' to \'' . $filePath . '\'.</info>');
    }

    /**
     * @return FileSystemInterface
     */
    protected static function getFileSystem()
    {
        return new FileSystem();
    }
}

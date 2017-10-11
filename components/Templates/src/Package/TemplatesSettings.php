<?php namespace Limoncello\Templates\Package;

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

use Limoncello\Contracts\Settings\SettingsInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @package Limoncello\Templates
 */
class TemplatesSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_TEMPLATES_FOLDER = 0;

    /** Settings key */
    const KEY_TEMPLATES_FILE_MASK = self::KEY_TEMPLATES_FOLDER + 1;

    /** Settings key */
    const KEY_CACHE_FOLDER = self::KEY_TEMPLATES_FILE_MASK + 1;

    /** Settings key */
    const KEY_APP_ROOT_FOLDER = self::KEY_CACHE_FOLDER + 1;

    /** Settings key */
    const KEY_IS_DEBUG = self::KEY_APP_ROOT_FOLDER + 1;

    /** Settings key */
    const KEY_TEMPLATES_LIST = self::KEY_IS_DEBUG + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_TEMPLATES_LIST;

    /**
     * @inheritdoc
     */
    final public function get(): array
    {
        $defaults = $this->getSettings();

        $templatesFolder   = $defaults[static::KEY_TEMPLATES_FOLDER] ?? null;
        $templatesFileMask = $defaults[static::KEY_TEMPLATES_FILE_MASK] ?? null;
        $cacheFolder       = $defaults[static::KEY_CACHE_FOLDER] ?? null;
        $appRootFolder     = $defaults[static::KEY_APP_ROOT_FOLDER] ?? null;

        assert(
            $templatesFolder !== null && empty(glob($templatesFolder)) === false,
            "Invalid Templates folder `$templatesFolder`."
        );
        assert(empty($templatesFileMask) === false, "Invalid Templates file mask `$templatesFileMask`.");
        assert(
            $cacheFolder !== null && empty(glob($cacheFolder)) === false,
            "Invalid Cache folder `$cacheFolder`."
        );
        assert(
            $appRootFolder !== null && empty(glob($appRootFolder)) === false,
            "Invalid App root folder `$appRootFolder`."
        );

        $realTemplatesFolder = realpath($templatesFolder);
        $realCacheFolder     = realpath($cacheFolder);
        $realAppRootFolder   = realpath($appRootFolder);

        $defaults[static::KEY_TEMPLATES_FOLDER] = $realTemplatesFolder;
        $defaults[static::KEY_CACHE_FOLDER]     = $realCacheFolder;
        $defaults[static::KEY_APP_ROOT_FOLDER]  = $realAppRootFolder;

        return $defaults + [
                static::KEY_TEMPLATES_LIST => $this->getTemplateNames($realTemplatesFolder, $templatesFileMask),
            ];
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_IS_DEBUG            => false,
            static::KEY_TEMPLATES_FILE_MASK => '*.twig',
        ];
    }

    /**
     * @param string $templatesFolder
     * @param string $templatesFileMask
     *
     * @return array
     */
    private function getTemplateNames(string $templatesFolder, string $templatesFileMask): array
    {
        return iterator_to_array(call_user_func(function () use ($templatesFolder, $templatesFileMask) {
            $flags    =
                RecursiveDirectoryIterator::SKIP_DOTS |
                RecursiveDirectoryIterator::FOLLOW_SYMLINKS |
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templatesFolder, $flags));
            foreach ($iterator as $found) {
                /** @var SplFileInfo $found */
                if ($found->isFile() === true && fnmatch($templatesFileMask, $found->getFilename()) === true) {
                    $fullFileName = $found->getPath() . DIRECTORY_SEPARATOR . $found->getFilename();
                    $templateName = str_replace($templatesFolder . DIRECTORY_SEPARATOR, '', $fullFileName);
                    yield $templateName;
                }
            }
        }));
    }
}

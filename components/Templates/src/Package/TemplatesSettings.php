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

use GlobIterator;
use Limoncello\Contracts\Settings\SettingsInterface;
use SplFileInfo;

/**
 * @package Limoncello\Templates
 */
abstract class TemplatesSettings implements SettingsInterface
{
    /** Settings key */
    const KEY_TEMPLATES_FOLDER = 0;

    /** Settings key */
    const KEY_CACHE_FOLDER = self::KEY_TEMPLATES_FOLDER + 1;

    /** Settings key */
    const KEY_LAST = self::KEY_CACHE_FOLDER + 1;

    /**
     * @param string $templatesFolder
     *
     * @return string[]
     */
    public static function getTemplateNames(string $templatesFolder): array
    {
        assert(is_dir($templatesFolder) === true);

        return iterator_to_array(call_user_func(function () use ($templatesFolder) {
            $globIterator = new GlobIterator(
                $templatesFolder . DIRECTORY_SEPARATOR . '*.html.twig',
                GlobIterator::SKIP_DOTS | GlobIterator::CURRENT_AS_FILEINFO
            );
            foreach ($globIterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                yield $fileInfo->getFilename();
            }
        }));
    }
}

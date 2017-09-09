<?php namespace Limoncello\Tests\Templates\Data;

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

use Limoncello\Templates\Package\TemplatesSettings;

/**
 * @package Limoncello\Tests\Templates
 */
class Templates extends TemplatesSettings
{
    /** Settings key */
    const KEY_TEMPLATES_LIST = self::KEY_LAST + 1;

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        $templatesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Templates']);
        $cacheFolder     = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Cache']);
        $templateNames   = $this->getTemplateNames($templatesFolder);

        return [
            static::KEY_IS_DEBUG         => true,
            static::KEY_TEMPLATES_FOLDER => $templatesFolder,
            static::KEY_CACHE_FOLDER     => $cacheFolder,
            static::KEY_TEMPLATES_LIST   => $templateNames,
        ];
    }
}

<?php namespace Limoncello\Templates;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Contracts\Templates\TemplatesInterface;
use Limoncello\Templates\Contracts\TemplatesCacheInterface;
use Twig_Environment;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Twig_Loader_Filesystem;

/**
 * @package Limoncello\Templates
 */
class TwigTemplates implements TemplatesInterface, TemplatesCacheInterface
{
    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @param string      $appRootFolder
     * @param string      $templatesFolder
     * @param null|string $cacheFolder
     * @param bool        $isDebug
     * @param bool        $isAutoReload
     */
    public function __construct(
        string $appRootFolder,
        string $templatesFolder,
        ?string $cacheFolder,
        bool $isDebug,
        bool $isAutoReload
    ) {
        // For Twig options see http://twig.sensiolabs.org/doc/api.html
        $options = [
            'debug'       => $isDebug,
            'cache'       => $cacheFolder === null ? false : $cacheFolder,
            'auto_reload' => $isAutoReload,
        ];

        $this->twig = new Twig_Environment(new Twig_Loader_Filesystem($templatesFolder, $appRootFolder), $options);
    }

    /**
     * @return Twig_Environment
     */
    public function getTwig(): Twig_Environment
    {
        return $this->twig;
    }

    /**
     * @param string $name
     * @param array  $context
     *
     * @return string
     *
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Syntax
     * @throws Twig_Error_Runtime
     */
    public function render(string $name, array $context = []): string
    {
        return $this->getTwig()->render($name, $context);
    }

    /**
     * @param string $name
     *
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Syntax
     */
    public function cache(string $name): void
    {
        $this->getTwig()->resolveTemplate($name);
    }
}

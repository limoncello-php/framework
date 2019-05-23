<?php declare(strict_types=1);

namespace Limoncello\Templates;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * @package Limoncello\Templates
 */
class TwigTemplates implements TemplatesInterface, TemplatesCacheInterface
{
    /**
     * @var Environment
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

        $this->twig = new Environment(new FilesystemLoader($templatesFolder, $appRootFolder), $options);
    }

    /**
     * @return Environment
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * @param string $name
     * @param array  $context
     *
     * @return string
     *
     * @throws LoaderError
     * @throws SyntaxError
     * @throws RuntimeError
     */
    public function render(string $name, array $context = []): string
    {
        return $this->getTwig()->render($name, $context);
    }

    /**
     * @param string $name
     *
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function cache(string $name): void
    {
        $this->getTwig()->resolveTemplate($name);
    }
}

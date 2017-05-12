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

use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Templates\Package\TemplatesSettings;
use Limoncello\Templates\TwigTemplates;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Templates
 */
class TemplatesCommand implements CommandInterface
{
    /** Argument name */
    const ARG_ACTION = 'action';

    /** Command action */
    const ACTION_CLEAR_CACHE = 'clear-cache';

    /** Command action */
    const ACTION_CREATE_CACHE = 'cache';

    /**
     * @inheritdoc
     */
    public static function getName(): string
    {
        return 'l:templates';
    }

    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Creates and cleans templates cache.';
    }

    /**
     * @inheritdoc
     */
    public static function getHelp(): string
    {
        return 'This command creates and cleans cache for HTML templates.';
    }

    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        $cache = static::ACTION_CREATE_CACHE;
        $clear = static::ACTION_CLEAR_CACHE;

        return [
            [
                static::ARGUMENT_NAME        => static::ARG_ACTION,
                static::ARGUMENT_DESCRIPTION => "Action such as `$cache` or `$clear`.",
                static::ARGUMENT_MODE        => static::ARGUMENT_MODE__REQUIRED,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute(ContainerInterface $container, IoInterface $inOut)
    {
        $action    = $inOut->getArgument(static::ARG_ACTION);
        switch ($action) {
            case static::ACTION_CREATE_CACHE:
                (new self())->executeCache($container);
                break;
            case static::ACTION_CLEAR_CACHE:
                (new self())->executeClear($container);
                break;
            default:
                $inOut->writeError("Unsupported action `$action`." . PHP_EOL);
                break;
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    protected function executeClear(ContainerInterface $container)
    {
        $settings    = $this->getTemplatesSettings($container);
        $cacheFolder = $settings[TemplatesSettings::KEY_CACHE_FOLDER];

        /** @var FileSystemInterface $fileSystem */
        $fileSystem = $container->get(FileSystemInterface::class);
        foreach ($fileSystem->scanFolder($cacheFolder) as $fileOrFolder) {
            $fileSystem->isFolder($fileOrFolder) === false ?: $fileSystem->deleteFolderRecursive($fileOrFolder);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function executeCache(ContainerInterface $container)
    {
        $settings        = $this->getTemplatesSettings($container);
        $cacheFolder     = $settings[TemplatesSettings::KEY_CACHE_FOLDER];
        $templatesFolder = $settings[TemplatesSettings::KEY_TEMPLATES_FOLDER];
        $templates       = TemplatesSettings::getTemplateNames($templatesFolder);
        $templateEngine  = $this->createCachingTemplateEngine($templatesFolder, $cacheFolder);

        foreach ($templates as $templateName) {
            // it will write template to cache
            $templateEngine->getTwig()->resolveTemplate($templateName);
        }
    }

    /**
     * @param string $templatesFolder
     * @param string $cacheFolder
     *
     * @return TwigTemplates
     */
    protected function createCachingTemplateEngine(string $templatesFolder, string $cacheFolder): TwigTemplates
    {
        $templates = new TwigTemplates($templatesFolder, $cacheFolder);

        return $templates;
    }
    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    protected function getTemplatesSettings(ContainerInterface $container)
    {
        $tplConfig = $container->get(SettingsProviderInterface::class)->get(TemplatesSettings::class);

        return $tplConfig;
    }
}

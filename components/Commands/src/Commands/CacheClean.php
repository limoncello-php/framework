<?php namespace Limoncello\Commands\Commands;

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

use Limoncello\Commands\Exceptions\ConfigurationException;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Templates\Commands\TemplatesClean;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Commands
 */
class CacheClean extends CacheBase
{
    /**
     * @inheritdoc
     */
    public function getCommandData(): array
    {
        return [
            self::COMMAND_NAME        => 'limoncello:cache:clean',
            self::COMMAND_DESCRIPTION => 'Cleans application caches.',
            self::COMMAND_HELP        => 'This command cleans caches for routes, settings, templates and etc.',
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
        $appSettings   = $this->getApplicationSettings($container);
        $cacheDir      = $appSettings[ApplicationSettingsInterface::KEY_CACHE_FOLDER];
        $cacheCallable = $appSettings[ApplicationSettingsInterface::KEY_CACHE_CALLABLE];
        list (, $class) = $this->parseCacheCallable($cacheCallable);

        if ($class === null) {
            throw new ConfigurationException();
        }

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @unlink($path);

        (new TemplatesClean())->execute($container, $inOut);
    }
}

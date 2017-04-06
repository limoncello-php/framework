<?php namespace Limoncello\Application\Commands;

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

use Limoncello\Application\Exceptions\ConfigurationException;
use Limoncello\Contracts\Application\ApplicationSettingsInterface;
use Limoncello\Contracts\Commands\IoInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
class ApplicationSettingsClean extends ApplicationSettingsBase
{
    /**
     * @inheritdoc
     */
    public function getCommandData(): array
    {
        return [
            self::COMMAND_NAME        => 'limoncello:cache:application:clean',
            self::COMMAND_DESCRIPTION => 'Cleans application caches.',
            self::COMMAND_HELP        => 'This command cleans caches for routes, settings and etc.',
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
            // parsing of cache callable failed (most likely error in settings)
            throw new ConfigurationException();
        }

        $path = $cacheDir . DIRECTORY_SEPARATOR . $class . '.php';

        $this->createFileSystem($container)->delete($path);

        // TODO check if it has any performance impact
        // Remove file from PHP cache if available
        if (function_exists('opcache_get_configuration') === true &&
            (opcache_get_configuration()['directives']['opcache.enable'] ?? false) === true &&
            function_exists('opcache_compile_file') === true
        ) {
            opcache_invalidate($path, true);
        }
    }
}

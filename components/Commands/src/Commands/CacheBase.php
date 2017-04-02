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
use Limoncello\Contracts\Commands\CommandInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Commands
 */
abstract class CacheBase implements CommandInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    protected function getApplicationSettings(ContainerInterface $container): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $container->get(SettingsProviderInterface::class);
        $appSettings      = $settingsProvider->get(ApplicationSettingsInterface::class);

        return $appSettings;
    }

    /**
     * @param $cacheCallable
     *
     * @return array
     */
    protected function parseCacheCallable($cacheCallable): array
    {
        if (is_string($cacheCallable) === true &&
            count($nsClassMethod = explode('::', $cacheCallable, 2)) === 2 &&
            ($nsCount = count($nsClass = explode('\\', $nsClassMethod[0]))) > 1
        ) {
            $canBeClass = $nsClass[$nsCount - 1];
            unset($nsClass[$nsCount - 1]);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $nsClassMethod[1];
        } elseif (is_array($cacheCallable) === true &&
            count($cacheCallable) === 2 &&
            ($nsCount = count($nsClass = explode('\\', $cacheCallable[0]))) > 1
        ) {
            $canBeClass = $nsClass[$nsCount - 1];
            unset($nsClass[$nsCount - 1]);
            $canBeNamespace = array_filter($nsClass);
            $canBeMethod    = $cacheCallable[1];
        } else {
            throw new ConfigurationException('Invalid callable value in application configuration.');
        }

        foreach (array_merge($canBeNamespace, [$canBeClass, $canBeMethod]) as $value) {
            // is string might have a-z, A-Z, _, numbers but has at least one a-z or A-Z.
            if (is_string($value) === false ||
                preg_match('/^\\w+$/i', $value) !== 1 ||
                preg_match('/^[a-z]+$/i', $value) !== 1
            ) {
                throw new ConfigurationException('Invalid callable value in application configuration.');
            }
        }

        $namespace = implode('\\', $canBeNamespace);
        $class     = $canBeClass;
        $method    = $canBeMethod;

        return [$namespace, $class, $method];
    }
}

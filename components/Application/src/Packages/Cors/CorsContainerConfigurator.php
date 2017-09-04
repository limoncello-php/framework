<?php namespace Limoncello\Application\Packages\Cors;

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

use Limoncello\Application\Packages\Cors\CorsSettings as C;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Application
 */
class CorsContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[AnalyzerInterface::class] = function (PsrContainerInterface $container) {
            $settingsProvider = $container->get(SettingsProviderInterface::class);
            $corsSettings     = $settingsProvider->get(C::class);
            $analyzer         = Analyzer::instance(new Settings($corsSettings));

            if ($corsSettings[C::KEY_LOG_IS_ENABLED] === true && $container->has(LoggerInterface::class)) {
                $logger = $container->get(LoggerInterface::class);
                $analyzer->setLogger($logger);
            }

            return $analyzer;
        };
    }
}

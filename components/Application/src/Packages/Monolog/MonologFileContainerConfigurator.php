<?php namespace Limoncello\Application\Packages\Monolog;

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

use Exception;
use Limoncello\Application\Packages\Monolog\MonologFileSettings as C;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface as A;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MonologFileContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[LoggerInterface::class] = function (PsrContainerInterface $container) {
            /** @var CacheSettingsProviderInterface $settingsProvider */
            $settingsProvider = $container->get(CacheSettingsProviderInterface::class);
            $appConfig        = $settingsProvider->getApplicationConfiguration();
            $monologSettings  = $settingsProvider->get(C::class);

            $monolog = new Logger($appConfig[A::KEY_APP_NAME]);
            $handler = $monologSettings[C::KEY_IS_ENABLED] === true ?
                static::createHandler($monologSettings) : new NullHandler();

            $monolog->pushHandler($handler);

            return $monolog;
        };
    }

    /**
     * @param array $settings
     *
     * @return HandlerInterface
     *
     * @throws Exception
     */
    protected static function createHandler(array $settings): HandlerInterface
    {
        assert(array_key_exists(C::KEY_LOG_PATH, $settings) === true);

        $logPath  = $settings[C::KEY_LOG_PATH];
        $logLevel = $settings[C::KEY_LOG_LEVEL] ?? Logger::ERROR;
        $handler  = new StreamHandler($logPath, $logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true, true));
        $handler->pushProcessor(new WebProcessor());
        $handler->pushProcessor(new UidProcessor());

        return $handler;
    }
}

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

use Limoncello\Application\Packages\Application\ApplicationSettings as A;
use Limoncello\Application\Packages\Monolog\MonologFileSettings as C;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
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
 */
class MonologFileContainerConfigurator implements ContainerConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container)
    {
        $container[LoggerInterface::class] = function (PsrContainerInterface $container) {
            $settingsProvider = $container->get(SettingsProviderInterface::class);
            $appSettings      = $settingsProvider->get(A::class);
            $monologSettings  = $settingsProvider->get(C::class);

            $monolog = new Logger($appSettings[A::KEY_APP_NAME]);
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

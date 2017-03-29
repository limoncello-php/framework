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

use Limoncello\Application\Contracts\ContainerConfiguratorInterface;
use Limoncello\Application\Packages\Application\ApplicationSettings as A;
use Limoncello\Application\Packages\Monolog\MonologFileSettings as C;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Monolog\Formatter\LineFormatter;
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
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        $container[LoggerInterface::class] = function (PsrContainerInterface $container) {
            $settingsProvider = $container->get(SettingsProviderInterface::class);
            $appSettings      = $settingsProvider->get(A::class);
            $monologSettings  = $settingsProvider->get(C::class);
            $monolog          = new Logger($appSettings[A::KEY_APP_NAME]);
            if ($monologSettings[C::KEY_IS_ENABLED] === true) {
                $handler = new StreamHandler($monologSettings[C::KEY_LOG_PATH], $monologSettings[C::KEY_LOG_LEVEL]);
                $handler->setFormatter(new LineFormatter(null, null, true, true));
                $handler->pushProcessor(new WebProcessor());
                $handler->pushProcessor(new UidProcessor());
            } else {
                $handler = new NullHandler();
            }
            $monolog->pushHandler($handler);

            return $monolog;
        };
    }
}

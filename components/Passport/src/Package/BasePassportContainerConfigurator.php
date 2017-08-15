<?php namespace Limoncello\Passport\Package;

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

use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Authentication\AccountManager;
use Limoncello\Passport\Contracts\Authentication\PassportAccountManagerInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Contracts\PassportServerInterface;
use Limoncello\Passport\Entities\DatabaseScheme;
use Limoncello\Passport\Package\PassportSettings as C;
use Limoncello\Passport\PassportServer;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Passport
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BasePassportContainerConfigurator
{
    /**
     * @inheritdoc
     */
    protected static function baseConfigureContainer(LimoncelloContainerInterface $container): void
    {
        $accountManager = null;
        $factory        = function (
            PsrContainerInterface $container
        ) use (&$accountManager): PassportAccountManagerInterface {
            if ($accountManager === null) {
                $accountManager = new AccountManager($container);
            }

            return $accountManager;
        };
        $container[AccountManagerInterface::class]         = $factory;
        $container[PassportAccountManagerInterface::class] = $factory;

        $container[DatabaseSchemeInterface::class] = function (
            PsrContainerInterface $container
        ): DatabaseSchemeInterface {
            $settings = $container->get(SettingsProviderInterface::class)->get(C::class);

            return new DatabaseScheme($settings[C::KEY_USER_TABLE_NAME], $settings[C::KEY_USER_PRIMARY_KEY_NAME]);
        };

        $container[PassportServerInterface::class] = function (
            PsrContainerInterface $container
        ): PassportServerInterface {
            $integration    = $container->get(PassportServerIntegrationInterface::class);
            $passportServer = new PassportServer($integration);

            $settings = $container->get(SettingsProviderInterface::class)->get(C::class);
            if ($settings[C::KEY_ENABLE_LOGS] === true && $container->has(LoggerInterface::class) === true) {
                $logger = $container->get(LoggerInterface::class);
                $passportServer->setLogger($logger);
            }

            return $passportServer;
        };
    }
}

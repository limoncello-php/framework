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

use Doctrine\DBAL\Connection;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Adaptors\Generic\GenericPassportServerIntegration;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Package\PassportSettings as C;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Passport
 */
class PassportContainerConfigurator extends BasePassportContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        static::configureContainer($container);

        $container[PassportServerIntegrationInterface::class] = function (PsrContainerInterface $container) {
            assert($container !== null);

            return new class ($container) extends GenericPassportServerIntegration
            {
                /**
                 * @var PsrContainerInterface
                 */
                private $container;

                /**
                 * @var array
                 */
                private $settings;

                /**
                 * @param PsrContainerInterface $container
                 */
                public function __construct(PsrContainerInterface $container)
                {
                    $this->container = $container;
                    $this->settings  = $container->get(SettingsProviderInterface::class)->get(C::class);

                    /** @var Connection $connection */
                    $connection = $container->get(Connection::class);

                    parent::__construct(
                        $connection,
                        $this->settings[C::KEY_DEFAULT_CLIENT_ID],
                        $this->settings[C::KEY_APPROVAL_URI_STRING],
                        $this->settings[C::KEY_ERROR_URI_STRING],
                        $this->settings[C::KEY_CODE_EXPIRATION_TIME_IN_SECONDS],
                        $this->settings[C::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS],
                        $this->settings[C::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH]
                    );
                }

                /**
                 * @inheritdoc
                 */
                public function validateUserId(string $userName, string $password)
                {
                    $validator    = $this->settings[C::KEY_USER_CREDENTIALS_VALIDATOR];
                    $nullOrUserId = call_user_func($validator, $this->container, $userName, $password);

                    return $nullOrUserId;
                }
            };
        };
    }
}

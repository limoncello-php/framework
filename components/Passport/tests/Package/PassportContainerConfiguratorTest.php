<?php namespace Limoncello\Tests\Passport\Package;

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
use Limoncello\Contracts\Authentication\AccountManagerInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Contracts\Authentication\PassportAccountManagerInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Contracts\PassportServerInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Package\MySqlPassportContainerConfigurator;
use Limoncello\Passport\Package\PassportContainerConfigurator;
use Limoncello\Passport\Package\PassportSettings as C;
use Limoncello\Tests\Passport\Data\TestContainer;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Limoncello\Tests\Templates
 */
class PassportContainerConfiguratorTest extends TestCase
{
    /**
     * Test container configurator.
     */
    public function testGenericContainerConfigurator()
    {
        $container = new TestContainer();
        $container[SettingsProviderInterface::class] = $this->createSettingsProvider();
        $container[Connection::class]                = Mockery::mock(Connection::class);
        $container[LoggerInterface::class]           = new NullLogger();

        PassportContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(AccountManagerInterface::class));
        $this->assertNotNull($container->get(PassportAccountManagerInterface::class));
        $this->assertNotNull($container->get(DatabaseSchemeInterface::class));
        $this->assertNotNull($container->get(PassportServerInterface::class));
        $this->assertNotNull($container->get(TokenRepositoryInterface::class));
        /** @var PassportServerIntegrationInterface $integration */
        $this->assertNotNull($integration = $container->get(PassportServerIntegrationInterface::class));
        $this->assertTrue($integration->validateUserId('test', 'test'));
    }

    /**
     * Test container configurator.
     */
    public function testMySqlContainerConfigurator()
    {
        $container = new TestContainer();
        $container[SettingsProviderInterface::class] = $this->createSettingsProvider();
        $container[Connection::class]                = Mockery::mock(Connection::class);
        $container[LoggerInterface::class]           = new NullLogger();

        MySqlPassportContainerConfigurator::configureContainer($container);

        $this->assertNotNull($container->get(TokenRepositoryInterface::class));
        /** @var PassportServerIntegrationInterface $integration */
        $this->assertNotNull($integration = $container->get(PassportServerIntegrationInterface::class));
        $this->assertTrue($integration->validateUserId('test', 'test'));
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * @return SettingsProviderInterface
     */
    private function createSettingsProvider(): SettingsProviderInterface
    {
        return new class implements SettingsProviderInterface {
            private $values = [
                C::class => [
                    C::KEY_ENABLE_LOGS                          => true,
                    C::KEY_USER_TABLE_NAME                      => 'users',
                    C::KEY_USER_PRIMARY_KEY_NAME                => 'id_user',
                    C::KEY_DEFAULT_CLIENT_ID                    => 'default_client',
                    C::KEY_APPROVAL_URI_STRING                  => '/approval-uri',
                    C::KEY_ERROR_URI_STRING                     => '/error-uri',
                    C::KEY_CODE_EXPIRATION_TIME_IN_SECONDS      => 3600,
                    C::KEY_TOKEN_EXPIRATION_TIME_IN_SECONDS     => 3600,
                    C::KEY_RENEW_REFRESH_VALUE_ON_TOKEN_REFRESH => true,
                    C::KEY_USER_CREDENTIALS_VALIDATOR           => [
                        PassportContainerConfiguratorTest::class,
                        'userValidator'
                    ],
                ],
            ];

            /**
             * @inheritdoc
             */
            public function has(string $className): bool
            {
                return array_key_exists($className, $this->values);
            }

            /**
             * @inheritdoc
             */
            public function get(string $className): array
            {
                return $this->values[$className];
            }
        };
    }

    /**
     * @return bool
     */
    public static function userValidator(): bool
    {
        return true;
    }
}

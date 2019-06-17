<?php declare(strict_types=1);

namespace Limoncello\Tests\Passport\Adaptors\MySql;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Exception;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Passport\Adaptors\MySql\RedirectUri;
use Limoncello\Passport\Adaptors\MySql\RedirectUriRepository;
use Limoncello\Passport\Adaptors\MySql\Scope;
use Limoncello\Passport\Adaptors\MySql\ScopeRepository;
use Limoncello\Passport\Adaptors\MySql\Token;
use Limoncello\Passport\Adaptors\MySql\TokenRepository;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Limoncello\Passport\Entities\DatabaseSchema;
use Limoncello\Passport\Package\MySqlPassportContainerConfigurator;
use Limoncello\Tests\Passport\Data\TestContainer;
use Limoncello\Tests\Passport\Package\PassportContainerConfiguratorTest;
use Mockery;
use ReflectionMethod;

/**
 * Class ClientTest
 *
 * @package Limoncello\Tests\Passport
 */
class ServerIntegrationTest extends TestCase
{
    /**
     * Test getters.
     *
     * @throws Exception
     */
    public function testGetters()
    {
        $integration = $this->createInstance();

        $this->assertNotNull($integration->getClientRepository());

        $this->assertNotNull($repo = $integration->getScopeRepository());
        $method = new ReflectionMethod(ScopeRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(Scope::class, $method->invoke($repo));
        $method = new ReflectionMethod(ScopeRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals(DatabaseSchema::TABLE_SCOPES, $method->invoke($repo));

        $this->assertNotNull($repo = $integration->getRedirectUriRepository());
        $method = new ReflectionMethod(RedirectUriRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(RedirectUri::class, $method->invoke($repo));
        $method = new ReflectionMethod(RedirectUriRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals(DatabaseSchema::TABLE_REDIRECT_URIS, $method->invoke($repo));

        $this->assertNotNull($repo = $integration->getTokenRepository());
        $method = new ReflectionMethod(TokenRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(Token::class, $method->invoke($repo));
        $method = new ReflectionMethod(TokenRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals(DatabaseSchema::VIEW_TOKENS, $method->invoke($repo));

        $this->assertNotNull($integration->createTokenInstance());
    }

    /**
     * @return PassportServerIntegrationInterface
     *
     * @throws Exception
     */
    private function createInstance(): PassportServerIntegrationInterface
    {
        $container = new TestContainer();
        $container[SettingsProviderInterface::class] = PassportContainerConfiguratorTest::createSettingsProvider();
        $container[Connection::class]                = Mockery::mock(Connection::class);

        MySqlPassportContainerConfigurator::configureContainer($container);

        $this->assertTrue($container->has(PassportServerIntegrationInterface::class));

        $integration = $container->get(PassportServerIntegrationInterface::class);

        return $integration;
    }
}

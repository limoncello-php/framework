<?php namespace Limoncello\Tests\Passport\Adaptors\MySql;

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
use Limoncello\Passport\Adaptors\MySql\MySqlPassportServerIntegration;
use Limoncello\Passport\Adaptors\MySql\RedirectUri;
use Limoncello\Passport\Adaptors\MySql\RedirectUriRepository;
use Limoncello\Passport\Adaptors\MySql\Scope;
use Limoncello\Passport\Adaptors\MySql\ScopeRepository;
use Limoncello\Passport\Adaptors\MySql\Token;
use Limoncello\Passport\Adaptors\MySql\TokenRepository;
use Limoncello\Passport\Entities\DatabaseScheme;
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
        $this->assertEquals(DatabaseScheme::TABLE_SCOPES, $method->invoke($repo));

        $this->assertNotNull($repo = $integration->getRedirectUriRepository());
        $method = new ReflectionMethod(RedirectUriRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(RedirectUri::class, $method->invoke($repo));
        $method = new ReflectionMethod(RedirectUriRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals(DatabaseScheme::TABLE_REDIRECT_URIS, $method->invoke($repo));

        $this->assertNotNull($repo = $integration->getTokenRepository());
        $method = new ReflectionMethod(TokenRepository::class, 'getClassName');
        $method->setAccessible(true);
        $this->assertEquals(Token::class, $method->invoke($repo));
        $method = new ReflectionMethod(TokenRepository::class, 'getTableNameForReading');
        $method->setAccessible(true);
        $this->assertEquals(DatabaseScheme::VIEW_TOKENS, $method->invoke($repo));

        $this->assertNotNull($integration->createTokenInstance());
    }

    /**
     * @return MySqlPassportServerIntegration
     */
    private function createInstance(): MySqlPassportServerIntegration
    {
        return new class extends MySqlPassportServerIntegration
        {
            /**
             *  constructor.
             */
            public function __construct()
            {
                $connection = Mockery::mock(Connection::class);

                /** @var Connection $connection */

                parent::__construct(
                    $connection,
                    'some_client_id',
                    'https://some.fake/uri',
                    'https://some.fake/uri'
                );
            }

            /**
             * @inheritdoc
             */
            public function validateUserId(string $userName, string $password)
            {
                assert($userName || $password);

                return null;
            }

            /**
             * @inheritdoc
             */
            public function verifyAllowedUserScope(int $userIdentity, array $scope = null)
            {
                assert($userIdentity || $scope);

                return null;
            }
        };
    }
}

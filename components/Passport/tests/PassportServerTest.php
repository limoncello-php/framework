<?php namespace Limoncello\Tests\Passport;

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
use Limoncello\OAuthServer\Contracts\AuthorizationServerInterface;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Integration\BasePassportServerIntegration;
use Limoncello\Passport\PassportServer;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Passport
 */
class PassportServerTest extends TestCase
{
    const TEST_DEFAULT_CLIENT_ID = 'MainClient';
    const TEST_USER_NAME         = 'john';
    const TEST_USER_PASSWORD     = 'secret';
    const TEST_USER_ID           = 5;
    const TEST_ERROR_URI         = 'http://example.app/auth_request_error';
    const TEST_APPROVAL_URI      = 'http://example.app/resource_owner_approval';

    /**
     * Test issuing resource owner password token.
     */
    public function testResourceOwnerPasswordToken()
    {
        $this->createDatabaseScheme($connection = $this->createSqLiteConnection());
        $scheme     = $this->getDatabaseScheme();
        $scopeRepo  = new ScopeRepository($connection, $scheme);
        $clientRepo = new ClientRepository($connection, $scheme);

        $clientRepo->inTransaction(function () use ($scopeRepo, $clientRepo) {
            $clientRepo->create(
                $client = (new Client())
                    ->setIdentifier(PassportServerTest::TEST_DEFAULT_CLIENT_ID)
                    ->setName('client name')
                    ->enablePasswordGrant()
                    ->useDefaultScopesOnEmptyRequest()
            );
            $scopeRepo->create($scope1 = (new Scope())->setIdentifier('scope1'));
            $scopeRepo->create($scope2 = (new Scope())->setIdentifier('scope2'));

            $clientRepo->bindScopes($client->getIdentifier(), [$scope1, $scope2]);
        });

        $integration = new class ($connection) extends BasePassportServerIntegration {

            /**
             * @param Connection $connection
             */
            public function __construct(Connection $connection)
            {
                parent::__construct(
                    $connection,
                    PassportServerTest::TEST_DEFAULT_CLIENT_ID,
                    PassportServerTest::TEST_APPROVAL_URI,
                    PassportServerTest::TEST_ERROR_URI
                );
            }

            /**
             * @inheritdoc
             */
            public function validateUserId(string $userName, string $password): int
            {
                return
                    $userName === PassportServerTest::TEST_USER_NAME &&
                    $password === PassportServerTest::TEST_USER_PASSWORD ?
                        PassportServerTest::TEST_USER_ID : null;
            }
        };

        /** @var AuthorizationServerInterface $server */
        $server   = new PassportServer($integration);
        $response = $server->postCreateToken(
            $this->createPasswordTokenRequest(static::TEST_USER_NAME, static::TEST_USER_PASSWORD)
        );
        $this->assertNotNull($response);
        $token = json_decode((string)$response->getBody());
        $this->assertTrue(property_exists($token, 'access_token'));
        $this->assertTrue(property_exists($token, 'token_type'));
        $this->assertTrue(property_exists($token, 'expires_in'));
        $this->assertTrue(property_exists($token, 'refresh_token'));
        $this->assertTrue(property_exists($token, 'scope'));

        $this->assertNotEmpty($token->access_token);
        $this->assertEquals('bearer', $token->token_type);
        $this->assertTrue(is_int($token->expires_in));
        $this->assertNotEmpty($token->refresh_token);
        $this->assertNotEmpty($token->scope);
    }

    /**
     * @param string|null $username
     * @param string|null $password
     * @param string|null $scope
     * @param array       $headers
     *
     * @return ServerRequestInterface
     */
    private function createPasswordTokenRequest(
        string $username = null,
        string $password = null,
        string $scope = null,
        array $headers = []
    ) {
        $request = $this->createServerRequest([
            'grant_type' => 'password',
            'username'   => $username,
            'password'   => $password,
            'scope'      => $scope,
        ], null, $headers);

        return $request;
    }
}

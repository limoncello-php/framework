<?php namespace Limoncello\Tests\Passport\Adaptors;

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

use DateTimeInterface;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Adaptors\MySql\Client;
use Limoncello\Passport\Adaptors\MySql\ClientRepository;
use Limoncello\Passport\Adaptors\MySql\DatabaseSchemeMigrationTrait;
use Limoncello\Passport\Adaptors\MySql\RedirectUri;
use Limoncello\Passport\Adaptors\MySql\RedirectUriRepository;
use Limoncello\Passport\Adaptors\MySql\Scope;
use Limoncello\Passport\Adaptors\MySql\ScopeRepository;
use Limoncello\Passport\Adaptors\MySql\Token;
use Limoncello\Passport\Adaptors\MySql\TokenRepository;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Tests\Passport\Data\User;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class MySqlTest extends TestCase
{
    use DatabaseSchemeMigrationTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initMySqlDatabase();
    }

    /**
     * Test reading client scopes and redirect URIs in one database request works fine.
     */
    public function testRelationshipsReading()
    {
        $scopeRepo  = $this->createScopeRepository();
        $clientRepo = $this->createClientRepository();
        $uriRepo    = $this->createRedirectUriRepository();

        $this->assertEmpty($clientRepo->index());

        $scope = $scopeRepo->create(
            (new Scope())
            ->setIdentifier('scope1')
        );
        $this->assertNotNull($scopeRepo->read($scope->getIdentifier()));

        $client = $clientRepo->create(
            (new Client())
                ->setIdentifier('client1')
                ->setName('client name')
                ->setConfidential()
                ->enablePasswordGrant()
                ->setScopeIdentifiers([$scope->getIdentifier()])
        );

        $uri = $uriRepo->create(
            (new RedirectUri())
                ->setClientIdentifier($client->getIdentifier())
                ->setValue('https://client.app/redirect-ur')
        );
        $this->assertNotNull($uriRepo->read($uri->getIdentifier()));

        $this->assertNotEmpty($clients = $clientRepo->index());
        $this->assertCount(1, $clients);
        /** @var Client $client */
        $client = $clients[0];
        $this->assertTrue($client instanceof ClientInterface);
        $this->assertEquals('client1', $client->getIdentifier());
        $this->assertNotEmpty($client->getScopeIdentifiers());
        $this->assertNotEmpty($client->getRedirectUriStrings());

        $sameClient = $clientRepo->read($client->getIdentifier());
        $this->assertEquals('client1', $sameClient->getIdentifier());
        $this->assertNotEmpty($sameClient->getScopeIdentifiers());
        $this->assertNotEmpty($sameClient->getRedirectUriStrings());
    }

    /**
     * Test reading client with empty relationships.
     */
    public function testReadingClientWithEmptyRelationships()
    {
        /** @var ClientRepositoryInterface $repo */
        $repo = $this->createClientRepository();

        $this->assertEmpty($repo->index());

        $repo->create(
            (new Client())
                ->setIdentifier('client1')
                ->setName('client name')
                ->setConfidential()
                ->enablePasswordGrant()
        );

        $this->assertNotEmpty($clients = $repo->index());
        $this->assertCount(1, $clients);
        /** @var Client $client */
        $client = $clients[0];
        $this->assertTrue($client instanceof ClientInterface);
        $this->assertEquals('client1', $client->getIdentifier());
        $this->assertEmpty($client->getScopeIdentifiers());
        $this->assertEmpty($client->getRedirectUriStrings());

        $sameClient = $repo->read($client->getIdentifier());
        $this->assertEquals('client1', $sameClient->getIdentifier());
        $this->assertEmpty($sameClient->getScopeIdentifiers());
        $this->assertEmpty($sameClient->getRedirectUriStrings());
    }

    /**
     * Test token reading with scope identifiers.
     */
    public function testTokenReading()
    {
        $scopeRepo = $this->createScopeRepository();
        $scope1    = $scopeRepo->create((new Scope())->setIdentifier('scope1'));
        $this->assertNotNull($scopeRepo->read($scope1->getIdentifier()));
        $scope2    = $scopeRepo->create((new Scope())->setIdentifier('scope2'));
        $this->assertNotNull($scopeRepo->read($scope2->getIdentifier()));

        $clientRepo = $this->createClientRepository();
        $client     = $clientRepo->create(
            (new Client())
                ->setIdentifier('client1')
                ->setName('client name')
                ->setConfidential()
                ->enablePasswordGrant()
                ->setScopeIdentifiers([$scope1->getIdentifier()])
        );

        $tokenRepo = $this->createTokenRepository();
        $token     =$tokenRepo->createToken(
            (new Token())
                ->setClientIdentifier($client->getIdentifier())
                ->setValue('secret-token')
                ->setUserIdentifier(1) // we know we have created one user
                ->setScopeIdentifiers([$scope1->getIdentifier(), $scope2->getIdentifier()])
        );

        $sameToken = $tokenRepo->read($token->getIdentifier());
        $this->assertCount(2, $sameToken->getScopeIdentifiers());
        $this->assertTrue($sameToken->getValueCreatedAt() instanceof DateTimeInterface);
    }

    /**
     * Test user with not typed attributes.
     */
    public function testReadUnTypedUserByToken()
    {
        $this->testTokenReading();

        /** @var TokenRepository $tokenRepo */
        $tokenRepo = $this->createTokenRepository();
        list($user, $scopes) = $tokenRepo->readUserByToken('secret-token', 10, User::class);
        $this->assertTrue($user instanceof User);
        $this->assertNotEmpty($scopes);
    }

    /**
     * Test user with not typed attributes.
     */
    public function testReadTypedUserByToken()
    {
        $this->testTokenReading();

        /** @var TokenRepository $tokenRepo */
        $tokenRepo = $this->createTokenRepository();
        $types = [
            User::FIELD_ID   => Type::getType(Type::INTEGER),
            User::FIELD_NAME => Type::getType(Type::STRING),
        ];
        list($user, $scopes) = $tokenRepo->readUserByToken('secret-token', 10, User::class, $types);
        $this->assertTrue($user instanceof User);
        $this->assertNotEmpty($scopes);
        $this->assertTrue(is_int($user->{User::FIELD_ID}));
    }

    /**
     * @return ClientRepositoryInterface
     */
    private function createClientRepository(): ClientRepositoryInterface
    {
        $clientRepo = new ClientRepository($this->getConnection(), $this->getDatabaseScheme());

        return $clientRepo;
    }

    /**
     * @return ScopeRepositoryInterface
     */
    private function createScopeRepository(): ScopeRepositoryInterface
    {
        $clientRepo = new ScopeRepository($this->getConnection(), $this->getDatabaseScheme());

        return $clientRepo;
    }

    /**
     * @return RedirectUriRepositoryInterface
     */
    private function createRedirectUriRepository(): RedirectUriRepositoryInterface
    {
        $clientRepo = new RedirectUriRepository($this->getConnection(), $this->getDatabaseScheme());

        return $clientRepo;
    }

    /**
     * @return TokenRepositoryInterface
     */
    private function createTokenRepository(): TokenRepositoryInterface
    {
        $clientRepo = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());

        return $clientRepo;
    }
}

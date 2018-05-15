<?php namespace Limoncello\Tests\Passport\Repositories;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use DateTimeImmutable;
use Exception;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Passport\Traits\DatabaseSchemaMigrationTrait;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class ClientRepositoryTest extends TestCase
{
    use DatabaseSchemaMigrationTrait;

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initSqliteDatabase();
    }

    /**
     * Test basic CRUD.
     *
     * @throws Exception
     */
    public function testCrud()
    {
        /** @var ClientRepositoryInterface $clientRepo */
        list($clientRepo) = $this->createRepositories();

        $this->assertEmpty($clientRepo->index());

        $clientRepo->create(
            (new Client())
                ->setIdentifier('client1')
                ->setName('client name')
                ->setConfidential()
                ->enablePasswordGrant()
                ->setScopeIdentifiers([])
        );

        $this->assertNotEmpty($clients = $clientRepo->index());
        $this->assertCount(1, $clients);
        /** @var Client $client */
        $client = $clients[0];
        $this->assertTrue($client instanceof ClientInterface);
        $this->assertEquals('client1', $client->getIdentifier());
        $this->assertEquals('client name', $client->getName());
        $this->assertTrue($client->isConfidential());
        $this->assertTrue($client->isPasswordGrantEnabled());
        $this->assertTrue($client->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertNull($client->getUpdatedAt());

        $client->setDescription(null);

        $clientRepo->update($client);
        $sameClient = $clientRepo->read($client->getIdentifier());
        $this->assertEquals('client1', $sameClient->getIdentifier());
        $this->assertNull($sameClient->getDescription());
        $this->assertEmpty($sameClient->getScopeIdentifiers());
        $this->assertEmpty($sameClient->getRedirectUriStrings());
        $this->assertTrue($sameClient->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameClient->getUpdatedAt() instanceof DateTimeImmutable);

        $clientRepo->delete($sameClient->getIdentifier());

        $this->assertEmpty($clientRepo->index());
    }

    /**
     * Test add and remove scopes.
     *
     * @throws Exception
     */
    public function testAddAndRemoveScopes()
    {
        /** @var ClientRepositoryInterface $clientRepo */
        /** @var ScopeRepositoryInterface $scopeRepo */
        list($clientRepo, $scopeRepo) = $this->createRepositories();

        $clientRepo->inTransaction(function () use ($clientRepo, $scopeRepo) {
            $scopeRepo->create($scope1 = (new Scope())->setIdentifier('scope1'));
            $scopeRepo->create($scope2 = (new Scope())->setIdentifier('scope2'));

            $clientRepo->create($client = (new Client())->setIdentifier('client1')->setName('client name'));

            $clientRepo->bindScopes($client->getIdentifier(), [$scope1, $scope2]);
        });

        $this->assertNotNull($client = $clientRepo->read('client1'));
        $this->assertCount(2, $client->getScopeIdentifiers());
        $this->assertEquals(['scope1', 'scope2'], $client->getScopeIdentifiers());

        $clientRepo->unbindScopes($client->getIdentifier());
        $this->assertNotNull($client = $clientRepo->read($client->getIdentifier()));
        $this->assertCount(0, $client->getScopeIdentifiers());

        // create a client with scopes
        $client2 = $clientRepo->create(
            (new Client())
                ->setIdentifier('client2')
                ->setName('client name')
                ->setConfidential()
                ->enablePasswordGrant()
                ->setScopeIdentifiers(['scope1', 'scope2'])
        );
        $this->assertNotNull($client2);
    }

    /**
     * Test entities get/set methods.
     *
     * @throws Exception
     */
    public function testEntities()
    {
        $client = (new Client())->setConfidential()->enableScopeExcess();
        $this->assertFalse($client->isPublic());
        $this->assertTrue($client->isScopeExcessAllowed());
    }

    /**
     * @return array
     */
    private function createRepositories(): array
    {
        $clientRepo = new ClientRepository($this->getConnection(), $this->getDatabaseSchema());
        $scopeRepo  = new ScopeRepository($this->getConnection(), $this->getDatabaseSchema());

        return [$clientRepo, $scopeRepo];
    }
}

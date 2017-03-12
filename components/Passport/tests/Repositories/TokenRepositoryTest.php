<?php namespace Limoncello\Tests\Passport\Repositories;

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

use DateTimeImmutable;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\Token;
use Limoncello\Passport\Adaptors\Generic\TokenRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Passport\Traits\DatabaseSchemeMigrationTrait;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class TokenRepositoryTest extends TestCase
{
    use DatabaseSchemeMigrationTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->initSqliteDatabase();
    }

    /**
     * Test basic CRUD.
     */
    public function testCrud()
    {
        /** @var TokenRepositoryInterface $tokenRepo */
        /** @var ScopeRepositoryInterface $scopeRepo */
        /** @var ClientRepositoryInterface $clientRepo */
        list($tokenRepo, $scopeRepo, $clientRepo) = $this->createRepositories();

        $newCode = (new Token())
            ->setUserIdentifier(1)
            ->setClientIdentifier('abc')
            ->setCode('some-secret-code');
        $tokenRepo->inTransaction(function () use (
            $tokenRepo,
            $scopeRepo,
            $clientRepo,
            &$newCode
        ) {
            $clientRepo->create(
                $client = (new Client())->setIdentifier($newCode->getClientIdentifier())->setName('client name')
            );

            $newCode = $tokenRepo->createCode($newCode);

            $scopeRepo->create($scope1 = (new Scope())->setIdentifier('scope1'));
            $scopeRepo->create($scope2 = (new Scope())->setIdentifier('scope2'));

            $tokenRepo->bindScopes($newCode->getIdentifier(), [$scope1, $scope2]);
        });
        $this->assertNotNull($newCode);

        $this->assertNotNull($tokenRepo->read($newCode->getIdentifier()));
        $this->assertNotNull($token = $tokenRepo->readByCode($newCode->getCode(), 10));
        $this->assertEquals($newCode->getCode(), $token->getCode());
        $this->assertNull($tokenRepo->readByCode($newCode->getCode(), 0));
        $this->assertNull($tokenRepo->readByRefresh($newCode->getCode(), 10));
        $this->assertTrue($token instanceof TokenInterface);
        $this->assertEquals($newCode->getIdentifier(), $token->getIdentifier());
        $this->assertEquals($newCode->getClientIdentifier(), $token->getClientIdentifier());
        $this->assertEquals($newCode->getUserIdentifier(), $token->getUserIdentifier());
        $this->assertNull($token->getValue());
        $this->assertNull($token->getRefreshValue());
        $this->assertCount(2, $token->getScopeIdentifiers());
        $this->assertTrue($token->getCodeCreatedAt() instanceof DateTimeImmutable);
        $this->assertNull($token->getValueCreatedAt());
        $this->assertNull($token->getRefreshCreatedAt());

        $newToken = (new Token())
            ->setCode($newCode->getCode())
            ->setValue('some-token-value')
            ->setType('bearer')
            ->setRefreshValue('some-refresh-value');
        $tokenRepo->assignValuesToCode($newToken, 10);

        $sameToken = $tokenRepo->read($token->getIdentifier());
        $this->assertEquals($newCode->getIdentifier(), $sameToken->getIdentifier());
        $this->assertEquals($newToken->getValue(), $sameToken->getValue());
        $this->assertEquals($newToken->getType(), $sameToken->getType());
        $this->assertEquals($newToken->getRefreshValue(), $sameToken->getRefreshValue());
        $this->assertTrue($sameToken->getCodeCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameToken->getValueCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameToken->getRefreshCreatedAt() instanceof DateTimeImmutable);

        $tokenRepo->unbindScopes($sameToken->getIdentifier());
        $sameToken = $tokenRepo->read($token->getIdentifier());
        $this->assertCount(0, $sameToken->getScopeIdentifiers());

        $tokenRepo->disable($newCode->getIdentifier());
        $this->assertNull($tokenRepo->readByCode($newCode->getCode(), 10));

        $tokenRepo->delete($newCode->getIdentifier());

        $this->assertEmpty($tokenRepo->read($newCode->getIdentifier()));
    }

    /**
     * Test create token (Resource Owner Credentials case).
     */
    public function testCreateTokenWithRefresh()
    {
        /** @var TokenRepositoryInterface $tokenRepo */
        /** @var ClientRepositoryInterface $clientRepo */
        list($tokenRepo, , $clientRepo) = $this->createRepositories();

        $clientRepo->create($client = (new Client())->setIdentifier('client1')->setName('client name'));
        $unsavedToken = (new Token())
            ->setClientIdentifier($client->getIdentifier())
            ->setUserIdentifier(1)
            ->setValue('some-token')
            ->setType('bearer')
            ->setRefreshValue('refresh-token');
        $this->assertNotNull($newToken = $tokenRepo->createToken($unsavedToken));
        $this->assertGreaterThan(0, $tokenId = $newToken->getIdentifier());

        $this->assertEquals($tokenId, $tokenRepo->readByValue('some-token', 10)->getIdentifier());
        $this->assertEquals($tokenId, $tokenRepo->readByRefresh('refresh-token', 10)->getIdentifier());
    }

    /**
     * Test create token (Resource Owner Credentials case).
     */
    public function testCreateTokenWithoutRefresh()
    {
        /** @var TokenRepositoryInterface $tokenRepo */
        /** @var ClientRepositoryInterface $clientRepo */
        list($tokenRepo, , $clientRepo) = $this->createRepositories();

        $clientRepo->create($client = (new Client())->setIdentifier('client1')->setName('client name'));
        $unsavedToken = (new Token())
            ->setClientIdentifier($client->getIdentifier())
            ->setUserIdentifier(1)
            ->setValue('some-token')
            ->setType('bearer');
        $this->assertNotNull($newToken = $tokenRepo->createToken($unsavedToken));
        $this->assertGreaterThan(0, $tokenId = $newToken->getIdentifier());

        $this->assertEquals($tokenId, $tokenRepo->readByValue('some-token', 10)->getIdentifier());
    }

    /**
     * @return array
     */
    private function createRepositories(): array
    {
        $tokenRepo  = new TokenRepository($this->getConnection(), $this->getDatabaseScheme());
        $scopeRepo  = new ScopeRepository($this->getConnection(), $this->getDatabaseScheme());
        $clientRepo = new ClientRepository($this->getConnection(), $this->getDatabaseScheme());

        return [$tokenRepo, $scopeRepo, $clientRepo];
    }
}

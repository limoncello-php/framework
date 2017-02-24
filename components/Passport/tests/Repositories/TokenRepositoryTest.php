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
use Limoncello\Passport\Adaptors\Generic\TokenRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class TokenRepositoryTest extends TestCase
{
    /**
     * Test basic CRUD.
     */
    public function testCrud()
    {
        /** @var TokenRepositoryInterface $tokenRepo */
        /** @var ScopeRepositoryInterface $scopeRepo */
        /** @var ClientRepositoryInterface $clientRepo */
        list($tokenRepo, $scopeRepo, $clientRepo) = $this->createRepositories();

        $tokenId  = null;
        $userId   = 1;
        $clientId = 'abc';
        $code     = 'some-secret-code';
        $tokenRepo->inTransaction(function () use (
            &$tokenId,
            $tokenRepo,
            $scopeRepo,
            $clientRepo,
            $clientId,
            $userId,
            $code
        ) {
            $clientRepo->create($client = (new Client())->setIdentifier($clientId)->setName('client name'));

            $tokenId = $tokenRepo->createCode($client->getIdentifier(), $userId, $code);

            $scopeRepo->create($scope1 = (new Scope())->setIdentifier('scope1'));
            $scopeRepo->create($scope2 = (new Scope())->setIdentifier('scope2'));

            $tokenRepo->bindScopes($tokenId, [$scope1, $scope2]);
        });
        $this->assertNotNull($tokenId);

        $this->assertNotNull($tokenRepo->read($tokenId));
        $this->assertNotNull($token = $tokenRepo->readByCode($code, 10));
        $this->assertEquals($code, $token->getCode());
        $this->assertNull($tokenRepo->readByCode($code, 0));
        $this->assertNull($tokenRepo->readByRefresh($code, 10));
        $this->assertTrue($token instanceof TokenInterface);
        $this->assertEquals($tokenId, $token->getIdentifier());
        $this->assertEquals($clientId, $token->getClientIdentifier());
        $this->assertEquals($userId, $token->getUserIdentifier());
        $this->assertNull($token->getValue());
        $this->assertNull($token->getRefreshValue());
        $this->assertCount(2, $token->getTokenScopeStrings());
        $this->assertTrue($token->getCodeCreatedAt() instanceof DateTimeImmutable);
        $this->assertNull($token->getValueCreatedAt());
        $this->assertNull($token->getRefreshCreatedAt());

        $tokenRepo->assignValuesToCode(
            $code,
            10,
            $value = 'some-token-value',
            $type = 'bearer',
            $refresh = 'some-refresh-value'
        );

        $sameToken = $tokenRepo->read($token->getIdentifier());
        $this->assertEquals($tokenId, $sameToken->getIdentifier());
        $this->assertEquals($value, $sameToken->getValue());
        $this->assertEquals($type, $sameToken->getType());
        $this->assertEquals($refresh, $sameToken->getRefreshValue());
        $this->assertTrue($sameToken->getCodeCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameToken->getValueCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameToken->getRefreshCreatedAt() instanceof DateTimeImmutable);

        $tokenRepo->unbindScopes($sameToken->getIdentifier());
        $sameToken = $tokenRepo->read($token->getIdentifier());
        $this->assertCount(0, $sameToken->getTokenScopeStrings());

        $tokenRepo->disable($tokenId);
        $this->assertNull($tokenRepo->readByCode($code, 10));

        $tokenRepo->delete($tokenId);

        $this->assertEmpty($tokenRepo->read($tokenId));
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
        $userId  = 1;
        $tokenId =
            $tokenRepo->createToken($client->getIdentifier(), $userId, 'some-token', 'bearer', 'refresh-token');
        $this->assertGreaterThan(0, $tokenId);

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
        $userId  = 1;
        $tokenId =
            $tokenRepo->createToken($client->getIdentifier(), $userId, 'some-token', 'bearer');
        $this->assertGreaterThan(0, $tokenId);

        $this->assertEquals($tokenId, $tokenRepo->readByValue('some-token', 10)->getIdentifier());
    }

    /**
     * @return array
     */
    private function createRepositories(): array
    {
        $this->createDatabaseScheme($connection = $this->createSqLiteConnection());
        $scheme = $this->getDatabaseScheme();

        $tokenRepo  = new TokenRepository($connection, $scheme);
        $scopeRepo  = new ScopeRepository($connection, $scheme);
        $clientRepo = new ClientRepository($connection, $scheme);

        return [$tokenRepo, $scopeRepo, $clientRepo];
    }
}

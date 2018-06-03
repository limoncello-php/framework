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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Exception;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\RedirectUri;
use Limoncello\Passport\Adaptors\Generic\RedirectUriRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Adaptors\Generic\Token;
use Limoncello\Passport\Adaptors\Generic\TokenRepository;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Traits\DatabaseSchemaMigrationTrait;
use Limoncello\Tests\Passport\TestCase;
use Mockery;
use ReflectionException;
use ReflectionMethod;

/**
 * @package Limoncello\Tests\Passport
 */
class BadDatabaseTest extends TestCase
{
    use DatabaseSchemaMigrationTrait;

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientIndex(): void
    {
        $this->createClientRepository()->index();
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientCreate(): void
    {
        $this->createClientRepository()->create(new Client());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientBindScopeIdentifiers(): void
    {
        $this->createClientRepository()->bindScopeIdentifiers('fakeClientId1', ['fakeScopeId1']);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientUnbindScopeIdentifiers(): void
    {
        $this->createClientRepository()->unbindScopes('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientRead(): void
    {
        $this->createClientRepository()->read('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientReadScopeIdentifiers(): void
    {
        $this->createClientRepository()->readScopeIdentifiers('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientReadRedirectUriStrings(): void
    {
        $this->createClientRepository()->readRedirectUriStrings('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientUpdate(): void
    {
        $this->createClientRepository()->update(new Client());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testClientDelete(): void
    {
        $this->createClientRepository()->delete('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testRedirectUriIndexClientUris(): void
    {
        $this->createRedirectUriRepository()->indexClientUris('fakeClientId1');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testRedirectUriCreate(): void
    {
        $this->createRedirectUriRepository()->create(new RedirectUri());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testRedirectUriRead(): void
    {
        $this->createRedirectUriRepository()->read(1);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testRedirectUriUpdate(): void
    {
        $this->createRedirectUriRepository()->update(new RedirectUri());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testRedirectUriDelete(): void
    {
        $this->createRedirectUriRepository()->delete(1);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testScopeIndex(): void
    {
        $this->createScopeRepository()->index();
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testScopeCreate(): void
    {
        $this->createScopeRepository()->create(new Scope());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testScopeRead(): void
    {
        $this->createScopeRepository()->read('fakeScopeId');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testScopeUpdate(): void
    {
        $this->createScopeRepository()->update(new Scope());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testScopeDelete(): void
    {
        $this->createScopeRepository()->delete('fakeScopeId');
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenCreateCode(): void
    {
        $this->createTokenRepository()->createCode((new Token())->setCode('fakeCode'));
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenAssignValuesToCode(): void
    {
        $this->createTokenRepository()->assignValuesToCode((new Token())->setCode('fakeCode'), 123);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenCreateToken(): void
    {
        $this->createTokenRepository()->createToken(new Token());
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenBindScopeIdentifiers(): void
    {
        $this->createTokenRepository()->bindScopeIdentifiers(1, ['fakeToken1']);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenUnbindScopes(): void
    {
        $this->createTokenRepository()->unbindScopes(1);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenRead(): void
    {
        $this->createTokenRepository()->read(1);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenReadByCode(): void
    {
        $this->createTokenRepository()->readByCode('fakeCode', 123);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenReadByUser(): void
    {
        $this->createTokenRepository()->readByUser(1, 123);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenReadPassport(): void
    {
        $this->createTokenRepository()->readPassport('fakeToken', 123);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenReadScopeIdentifiers(): void
    {
        $this->createTokenRepository()->readScopeIdentifiers(1);
    }

    /**
     * Test repository error handling.
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testTokenUpdateValues(): void
    {
        $this->createTokenRepository()->updateValues(new Token());
    }

    /**
     * Add test coverage to internal method.
     *
     * @throws ReflectionException
     * @throws Exception
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testInTransactionAddCoverage(): void
    {
        $connection  = Mockery::mock(Connection::class);
        $connection->shouldReceive('beginTransaction')->once()->withNoArgs()->andReturnUndefined();
        $connection->shouldReceive('commit')->once()->withNoArgs()->andThrow(new ConnectionException());

        /** @var Connection $connection */

        $repo    = new ClientRepository($connection, $this->initDefaultDatabaseSchema());
        $method  = new ReflectionMethod(ClientRepository::class, 'inTransaction');

        $method->setAccessible(true);
        // the exception thrown in the closure will be ignored
        $method->invoke($repo, function () {
        });
    }

    /**
     * Add test coverage to internal method.
     *
     * @throws ReflectionException
     * @throws Exception
     *
     * @expectedException \Limoncello\Passport\Exceptions\RepositoryException
     */
    public function testGetDateTimeForDbAddCoverage(): void
    {
        $connection  = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')->once()->withNoArgs()->andThrow(new DBALException());

        /** @var Connection $connection */

        $repo    = new ClientRepository($connection, $this->initDefaultDatabaseSchema());
        $method  = new ReflectionMethod(ClientRepository::class, 'getDateTimeForDb');

        $method->setAccessible(true);
        // the exception thrown in the closure will be ignored
        $method->invoke($repo, new DateTimeImmutable());
    }

    /**
     * Add test coverage to internal method.
     *
     * @throws ReflectionException
     */
    public function testIgnoreExceptionAddCoverage(): void
    {
        $repo    = $this->createClientRepository();
        $method  = new ReflectionMethod(ClientRepository::class, 'ignoreException');
        $closure = function () {
            throw new Exception();
        };

        $method->setAccessible(true);
        // the exception thrown in the closure will be ignored
        $method->invoke($repo, $closure);

        $this->assertTrue(true);
    }

    /**
     * @return ClientRepositoryInterface
     */
    private function createClientRepository(): ClientRepositoryInterface
    {
        return new ClientRepository($this->initDummyConnection(), $this->initDefaultDatabaseSchema());
    }

    /**
     * @return RedirectUriRepositoryInterface
     */
    private function createRedirectUriRepository(): RedirectUriRepositoryInterface
    {
        return new RedirectUriRepository($this->initDummyConnection(), $this->initDefaultDatabaseSchema());
    }

    /**
     * @return ScopeRepositoryInterface
     */
    private function createScopeRepository(): ScopeRepositoryInterface
    {
        return new ScopeRepository($this->initDummyConnection(), $this->initDefaultDatabaseSchema());
    }

    /**
     * @return TokenRepositoryInterface
     */
    private function createTokenRepository(): TokenRepositoryInterface
    {
        return new TokenRepository($this->initDummyConnection(), $this->initDefaultDatabaseSchema());
    }

    /**
     * @return Connection
     */
    private function initDummyConnection(): Connection
    {
        try {
            $this->setConnection($connection = static::createConnection());

            return $connection;
        } catch (Exception $exception) {
            $this->assertTrue(false, 'There is a problem with test database connection.');
        }

        return null;
    }
}

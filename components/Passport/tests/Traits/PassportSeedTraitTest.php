<?php namespace Limoncello\Tests\Passport\Traits;

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
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Entities\RedirectUriInterface;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Passport\Traits\PassportSeedTrait;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class PassportSeedTraitTest extends TestCase
{
    use PassportSeedTrait;

    /**
     * @var Mock|null
     */
    private $clientRepoMock = null;

    /**
     * @var Mock|null
     */
    private $scopeRepoMock = null;

    /**
     * @var Mock|null
     */
    private $uriRepoMock = null;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->clientRepoMock = Mockery::mock(ClientRepositoryInterface::class);
        $this->scopeRepoMock  = Mockery::mock(ScopeRepositoryInterface::class);
        $this->uriRepoMock    = Mockery::mock(RedirectUriRepositoryInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->clientRepoMock = null;
        $this->scopeRepoMock  = null;
        $this->uriRepoMock    = null;

        Mockery::close();
    }

    public function testSeedClient()
    {
        /** @var Connection $connection */
        $connection = Mockery::mock(Connection::class);
        /** @var DatabaseSchemeInterface $schemes */
        $schemes    = Mockery::mock(DatabaseSchemeInterface::class);

        $client = (new Client())->setScopeIdentifiers(['scope_1']);
        $scopes = [
            'scope_2' => 'Description for scope_2',
            'scope_3' => 'Description for scope_3',
        ];
        $redirectUris = [
            'http://some-uri.foo',
        ];

        $this->scopeRepoMock
            ->shouldReceive('create')
            ->times(2)
            ->withAnyArgs()
            ->andReturn(Mockery::mock(ScopeInterface::class));

        $this->clientRepoMock
            ->shouldReceive('create')
            ->once()
            ->with($client)
            ->andReturn($client);

        $this->uriRepoMock
            ->shouldReceive('create')
            ->times(1)
            ->withAnyArgs()
            ->andReturn(Mockery::mock(RedirectUriInterface::class));

        $this->seedClient(
            $connection,
            $schemes,
            $client,
            $scopes,
            $redirectUris
        );

        // assert it executed exactly as described above and we need at lease 1 assert to avoid PHP unit warning.
        $this->assertTrue(true);
    }

    /**
     * @inheritdoc
     */
    protected function createClientRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): ClientRepositoryInterface {
        assert($connection || $schemes);
        /** @var ClientRepositoryInterface $repo */
        $repo = $this->clientRepoMock;

        return $repo;
    }

    /**
     * @inheritdoc
     */
    protected function createScopeRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): ScopeRepositoryInterface {
        assert($connection || $schemes);
        /** @var ScopeRepositoryInterface $repo */
        $repo = $this->scopeRepoMock;

        return $repo;
    }

    /**
     * @inheritdoc
     */
    protected function createRedirectUriRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): RedirectUriRepositoryInterface {
        assert($connection || $schemes);
        /** @var RedirectUriRepositoryInterface $repo */
        $repo = $this->uriRepoMock;

        return $repo;
    }
}

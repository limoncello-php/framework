<?php declare(strict_types=1);

namespace Limoncello\Tests\Passport\Traits;

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

use Exception;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Contracts\Entities\RedirectUriInterface;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
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
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test seed client.
     *
     * @return void
     *
     * @throws Exception
     */
    public function testSeedClient()
    {
        $client       = (new Client())->setScopeIdentifiers(['scope_1']);
        $scopes       = [
            'scope_2' => 'Description for scope_2',
            'scope_3' => 'Description for scope_3',
        ];
        $redirectUris = [
            'http://some-uri.foo',
        ];

        /** @var Mock $clientRepoMock */
        /** @var Mock $scopeRepoMock */
        /** @var Mock $uriRepoMock */
        $clientRepoMock = Mockery::mock(ClientRepositoryInterface::class);
        $scopeRepoMock  = Mockery::mock(ScopeRepositoryInterface::class);
        $uriRepoMock    = Mockery::mock(RedirectUriRepositoryInterface::class);

        $scopeRepoMock
            ->shouldReceive('create')
            ->times(2)
            ->withAnyArgs()
            ->andReturn(Mockery::mock(ScopeInterface::class));

        $clientRepoMock
            ->shouldReceive('create')
            ->once()
            ->with($client)
            ->andReturn($client);

        $uriRepoMock
            ->shouldReceive('create')
            ->times(1)
            ->withAnyArgs()
            ->andReturn(Mockery::mock(RedirectUriInterface::class));

        /** @var Mock $intMock */
        $intMock = Mockery::mock(PassportServerIntegrationInterface::class);
        $intMock->shouldReceive('getScopeRepository')->once()->withNoArgs()->andReturn($scopeRepoMock);
        $intMock->shouldReceive('getClientRepository')->once()->withNoArgs()->andReturn($clientRepoMock);
        $intMock->shouldReceive('getRedirectUriRepository')->once()->withNoArgs()->andReturn($uriRepoMock);
        /** @var PassportServerIntegrationInterface $intMock */

        $this->seedClient($intMock, $client, $scopes, $redirectUris);

        // assert it executed exactly as described above and we need at lease 1 assert to avoid PHP unit warning.
        $this->assertTrue(true);
    }
}

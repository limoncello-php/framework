<?php declare(strict_types=1);

namespace Limoncello\Tests\Passport\Repositories;

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

use DateTimeImmutable;
use Exception;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\RedirectUri;
use Limoncello\Passport\Adaptors\Generic\RedirectUriRepository;
use Limoncello\Passport\Contracts\Entities\RedirectUriInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use Limoncello\Passport\Exceptions\InvalidArgumentException;
use Limoncello\Passport\Traits\DatabaseSchemaMigrationTrait;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class RedirectUriRepositoryTest extends TestCase
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

        $this->initDatabase();
    }

    /**
     * Test basic CRUD.
     *
     * @throws Exception
     */
    public function testCrud()
    {
        /** @var ClientRepositoryInterface $clientRepo */
        /** @var RedirectUriRepositoryInterface $uriRepo */
        list($clientRepo, $uriRepo) = $this->createRepositories();

        $clientRepo->create(
            $client = (new Client())->setIdentifier('client1')->setName('client name')
        );

        $clientId = $client->getIdentifier();
        $this->assertEmpty($uriRepo->indexClientUris($clientId));

        $uriId = $uriRepo->create(
            (new RedirectUri())
                ->setClientIdentifier($clientId)
                ->setValue('https://example.foo/boo')
        )->getIdentifier();

        $this->assertNotEmpty($uris = $uriRepo->indexClientUris($clientId));
        $this->assertCount(1, $uris);
        /** @var RedirectUri $uri */
        $uri = $uris[0];
        $this->assertTrue($uri instanceof RedirectUriInterface);
        $this->assertEquals($uriId, $uri->getIdentifier());
        $this->assertEquals($clientId, $uri->getClientIdentifier());
        $this->assertEquals('https://example.foo/boo', $uri->getValue());
        $this->assertTrue($uri->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertNull($uri->getUpdatedAt());

        $uriRepo->update($uri);
        $sameRedirectUri = $uriRepo->read($uri->getIdentifier());
        $this->assertEquals($uriId, $sameRedirectUri->getIdentifier());
        $this->assertTrue($sameRedirectUri->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameRedirectUri->getUpdatedAt() instanceof DateTimeImmutable);

        $uriRepo->delete($sameRedirectUri->getIdentifier());

        $this->assertEmpty($uriRepo->indexClientUris($clientId));
    }

    /**
     * Test entities get/set methods.
     *
     * @throws Exception
     */
    public function testEntities()
    {
        $uri = (new RedirectUri())->setValue('http://host.foo/path?param=value');
        $this->assertNotNull($uri->getUri());

        try {
            $uri->setValue('/no/host/value');
        } catch (InvalidArgumentException $exception) {
        }
        $this->assertTrue(isset($exception));
    }

    /**
     * @return array
     */
    private function createRepositories(): array
    {
        $clientRepository = new ClientRepository($this->getConnection(), $this->getDatabaseSchema());
        $uriRepository    = new RedirectUriRepository($this->getConnection(), $this->getDatabaseSchema());

        return [$clientRepository, $uriRepository];
    }
}

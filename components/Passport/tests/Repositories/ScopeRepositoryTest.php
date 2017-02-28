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
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;
use Limoncello\Tests\Passport\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class ScopeRepositoryTest extends TestCase
{
    /**
     * Test basic CRUD.
     */
    public function testCrud()
    {
        $repo = $this->createRepository();

        $this->assertEmpty($repo->index());

        $repo->create((new Scope())->setIdentifier('abc')->setDescription('desc'));

        $this->assertNotEmpty($scopes = $repo->index());
        $this->assertCount(1, $scopes);
        /** @var Scope $scope */
        $scope = $scopes[0];
        $this->assertTrue($scope instanceof ScopeInterface);
        $this->assertEquals('abc', $scope->getIdentifier());
        $this->assertEquals('desc', $scope->getDescription());
        $this->assertTrue($scope->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertNull($scope->getUpdatedAt());

        $scope->setDescription(null);

        $repo->update($scope);
        $sameScope = $repo->read($scope->getIdentifier());
        $this->assertEquals('abc', $sameScope->getIdentifier());
        $this->assertNull($sameScope->getDescription());
        $this->assertTrue($sameScope->getCreatedAt() instanceof DateTimeImmutable);
        $this->assertTrue($sameScope->getUpdatedAt() instanceof DateTimeImmutable);

        $repo->delete($sameScope->getIdentifier());

        $this->assertEmpty($repo->index());
    }

    /**
     * @return ScopeRepositoryInterface
     */
    private function createRepository(): ScopeRepositoryInterface
    {
        $this->createDatabaseScheme(
            $connection = $this->createSqLiteConnection(),
            $scheme = $this->getDatabaseScheme()
        );

        $repo = new ScopeRepository($connection, $scheme);

        return $repo;
    }
}

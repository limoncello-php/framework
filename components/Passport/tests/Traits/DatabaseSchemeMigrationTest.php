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
use Doctrine\DBAL\DBALException;
use Limoncello\Passport\Entities\DatabaseScheme;
use Limoncello\Passport\Traits\DatabaseSchemeMigrationTrait;
use Limoncello\Tests\Passport\Data\User;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Passport
 */
class DatabaseSchemeMigrationTest extends TestCase
{
    use DatabaseSchemeMigrationTrait;

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * Test migration fail.
     *
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function testMigrationFail()
    {
        /** @var Mock $connection */
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getSchemaManager')->zeroOrMoreTimes()->withNoArgs()->andReturnSelf();
        $connection->shouldReceive('dropAndCreateTable')->once()->withAnyArgs()
            ->andThrow(DBALException::invalidTableName('whatever'));
        $connection->shouldReceive('isConnected')->once()->withNoArgs()->andReturn(true);
        $connection->shouldReceive('tablesExist')->zeroOrMoreTimes()->withAnyArgs()->andReturn(false);

        /** @var Connection $connection */

        $this->createDatabaseScheme($connection, new DatabaseScheme(User::TABLE_NAME, User::FIELD_ID));
    }
}

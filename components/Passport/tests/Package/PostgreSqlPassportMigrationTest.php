<?php namespace Limoncello\Tests\Passport\Package;

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

use Doctrine\DBAL\Connection;
use Exception;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;
use Limoncello\Passport\Package\PostgreSqlPassportMigration;
use Limoncello\Tests\Passport\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Templates
 */
class PostgreSqlPassportMigrationTest extends TestCase
{
    /**
     * Test migrate and rollback.
     *
     * @throws Exception
     */
    public function testMigrateAndRollback()
    {
        /** @var Mock $migration */
        $migration = Mockery::mock(PostgreSqlPassportMigration::class . '[createDatabaseSchema,removeDatabaseSchema]');
        $migration->shouldAllowMockingProtectedMethods();

        $migration->shouldReceive('createDatabaseSchema')->once()->withAnyArgs()->andReturnSelf();
        $migration->shouldReceive('removeDatabaseSchema')->once()->withAnyArgs()->andReturnSelf();

        /** @var PostgreSqlPassportMigration $migration */

        $container                                 = new TestContainer();
        $container[DatabaseSchemaInterface::class] = Mockery::mock(DatabaseSchemaInterface::class);
        $container[Connection::class]              = Mockery::mock(Connection::class);

        $migration->init($container);

        $migration->migrate();
        $migration->rollback();

        // actual check would be performed by Mockery when test finished
        $this->assertTrue(true);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }
}

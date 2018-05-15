<?php namespace Limoncello\Tests\Passport\Adaptors\MySql;

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
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Exception;
use Mockery;

/**
 * Class ClientTest
 *
 * @package Limoncello\Tests\Passport
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * @return Connection
     *
     * @throws Exception
     */
    protected function createConnection(): Connection
    {
        return \Limoncello\Tests\Passport\TestCase::createSqliteDatabaseConnection();
    }

    /**
     * @param Connection $connection
     * @param string     $name
     * @param array      $columns
     *
     * @return void
     *
     * @throws Exception
     */
    protected function createTable(Connection $connection, string $name, array $columns)
    {
        $manager         = $connection->getSchemaManager();
        $doctrineColumns = [];
        foreach ($columns as $columnName => $typeName) {
            $doctrineColumns[] = new Column($columnName, Type::getType($typeName));
        }
        $manager->createTable(new Table($name, $doctrineColumns));
    }
}

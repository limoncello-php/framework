<?php declare (strict_types = 1);

namespace Limoncello\Tests\Data;

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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\SeedInterface;
use Limoncello\Data\Seeds\SeedTrait;
use Limoncello\Tests\Data\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Core
 */
class SeedTraitTest extends TestCase
{
    const TEST_MODEL_CLASS = 'TestClass';
    const TEST_COLUMN_NAME = 'value';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Test seeds.
     *
     * @throws DBALException
     */
    public function testSeeds()
    {
        $tableName  = 'table_name';
        $columnName = self::TEST_COLUMN_NAME;
        $types      = [$columnName => Type::STRING];

        $modelSchemas = Mockery::mock(ModelSchemaInfoInterface::class);

        $container = $this->createContainer($modelSchemas);
        $this->prepareTable($modelSchemas, self::TEST_MODEL_CLASS, $tableName, $types);

        $manager = $this->connection->getSchemaManager();
        $table   = new Table(
            $tableName,
            [new Column($columnName, Type::getType(Type::STRING))]
        );
        $table->addUniqueIndex([$columnName]);
        $manager->createTable($table);

        $this->createSeed($container)->run();
    }

    /**
     * @param ContainerInterface $container
     *
     * @return SeedInterface
     */
    private function createSeed(ContainerInterface $container): SeedInterface
    {
        $seed = new class ($this) implements SeedInterface
        {
            use SeedTrait;

            /**
             * @var TestCase
             */
            private $test;

            /**
             * @param TestCase $test
             */
            public function __construct(TestCase $test)
            {
                $this->test = $test;
            }

            /**
             * @inheritdoc
             */
            public function run(): void
            {
                $modelClass = SeedTraitTest::TEST_MODEL_CLASS;
                $columnName = SeedTraitTest::TEST_COLUMN_NAME;

                $this->test->assertTrue(is_string($this->now()));

                $this->test->assertCount(0, $this->readModelsData($modelClass));

                $this->seedModelsData(1, $modelClass, function () use ($columnName) {
                    return [$columnName => 'value1'];
                });
                $this->test->assertCount(1, $this->readModelsData($modelClass));

                $this->seedModelData($modelClass, [$columnName => 'value2']);
                $this->test->assertCount(2, $this->readModelsData($modelClass));

                $this->test->assertSame('2', $this->getLastInsertId());

                // inserting non-unique row will be ignored
                $this->seedModelData($modelClass, [$columnName => 'value2']);
                $this->test->assertCount(2, $this->readModelsData($modelClass));
            }
        };

        $seed->init($container);

        return $seed;
    }

    /**
     * @param MockInterface $modelSchemas
     *
     * @return ContainerInterface
     *
     * @throws DBALException
     */
    private function createContainer(MockInterface $modelSchemas): ContainerInterface
    {
        $container                    = new TestContainer();
        $container[Connection::class] = $this->connection = $this->createConnection();

        $container[ModelSchemaInfoInterface::class] = $modelSchemas;

        return $container;
    }

    /**
     * @return Connection
     *
     * @throws DBALException
     */
    private function createConnection(): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///', 'memory' => true]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $tableName
     * @param array         $attributeTypes
     *
     * @return Mock
     */
    private function prepareTable($mock, string $modelClass, string $tableName, array $attributeTypes)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('getTable')->zeroOrMoreTimes()->with($modelClass)->andReturn($tableName);
        $mock->shouldReceive('getAttributeTypes')->zeroOrMoreTimes()->with($modelClass)->andReturn($attributeTypes);

        return $mock;
    }
}

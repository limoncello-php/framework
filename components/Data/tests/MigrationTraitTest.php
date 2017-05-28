<?php namespace Limoncello\Tests\Data;

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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\MigrationInterface;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\Data\TimestampFields as TSF;
use Limoncello\Data\Migrations\MigrationTrait;
use Limoncello\Tests\Data\Data\TestContainer;
use Limoncello\Tests\Data\Data\TestTableMigration;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Core
 */
class MigrationTraitTest extends TestCase implements MigrationInterface
{
    use MigrationTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Test columns migration.
     */
    public function testColumns()
    {
        $modelClass             = 'TestModel1';
        $columnIntPrimary       = 'col_int_id';
        $tableName              = 'table_name';
        $columnNonNullString    = 'col_non_null_string';
        $columnNullableString   = 'col_nullable_string';
        $columnStringLength     = 100;
        $columnNonNullText      = 'col_non_null_text';
        $columnNullableText     = 'col_nullable_text';
        $columnUInt             = 'col_u_int';
        $columnNullableUInt     = 'col_nullable_u_int';
        $columnFloat            = 'col_float';
        $columnBool             = 'col_bool';
        $columnNonNullDate      = 'col_non_null_date';
        $columnNullableDate     = 'col_nullable_date';
        $columnNonNullDateTime  = 'col_non_null_datetime';
        $columnNullableDateTime = 'col_nullable_datetime';

        $columnToCreate = [
            $this->primaryInt($columnIntPrimary),
            $this->string($columnNonNullString),
            $this->nullableString($columnNullableString),
            $this->text($columnNonNullText),
            $this->nullableText($columnNullableText),
            $this->unsignedInt($columnUInt),
            $this->nullableUnsignedInt($columnNullableUInt),
            $this->float($columnFloat),
            $this->bool($columnBool, true),
            $this->timestamps(),
            $this->date($columnNonNullDate),
            $this->nullableDate($columnNullableDate),
            $this->datetime($columnNonNullDateTime),
            $this->nullableDatetime($columnNullableDateTime),

            $this->unique([$columnNonNullString]),

            $this->searchable([$columnNonNullText]),
        ];
        $migration = new TestTableMigration($modelClass, $columnToCreate);

        $modelSchemes = Mockery::mock(ModelSchemeInfoInterface::class);
        $this->prepareTable($modelSchemes, $modelClass, $tableName, 2);
        $this->prepareAttributeLength($modelSchemes, $modelClass, $columnNonNullString, $columnStringLength);
        $this->prepareAttributeLength($modelSchemes, $modelClass, $columnNullableString, $columnStringLength);
        $this->prepareTimestamps($modelSchemes, $modelClass);

        $container = $this->createContainer($modelSchemes);
        // as we create columns in one migration (this test) and then pass it to another migration
        // we have to init both migrations.
        $this->init($container);
        $migration->init($container)->migrate();

        // check migration
        $manager = $this->connection->getSchemaManager();
        $this->assertTrue($manager->tablesExist([$tableName]));
        $columnsCreated = $manager->listTableColumns($tableName);
        // +2 for timestamps and -2 for searchable and unique
        $this->assertCount(count($columnToCreate), $columnsCreated);

        $this->assertEquals('integer', $columnsCreated[$columnIntPrimary]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnIntPrimary]->getAutoincrement());

        $this->assertEquals('string', $columnsCreated[$columnNonNullString]->getType()->getName());
        $this->assertEquals($columnStringLength, $columnsCreated[$columnNonNullString]->getLength());
        $this->assertTrue($columnsCreated[$columnNonNullString]->getNotnull());

        $this->assertEquals('string', $columnsCreated[$columnNullableString]->getType()->getName());
        $this->assertEquals($columnStringLength, $columnsCreated[$columnNullableString]->getLength());
        $this->assertFalse($columnsCreated[$columnNullableString]->getNotnull());

        $this->assertEquals('text', $columnsCreated[$columnNonNullText]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNonNullText]->getNotnull());

        $this->assertEquals('text', $columnsCreated[$columnNullableText]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableText]->getNotnull());

        $this->assertEquals('integer', $columnsCreated[$columnUInt]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnUInt]->getUnsigned());
        $this->assertTrue($columnsCreated[$columnUInt]->getNotnull());

        $this->assertEquals('integer', $columnsCreated[$columnNullableUInt]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNullableUInt]->getUnsigned());
        $this->assertFalse($columnsCreated[$columnNullableUInt]->getNotnull());

        $this->assertEquals('float', $columnsCreated[$columnFloat]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnFloat]->getUnsigned());
        $this->assertTrue($columnsCreated[$columnFloat]->getNotnull());

        $this->assertEquals('boolean', $columnsCreated[$columnBool]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnBool]->getNotnull());

        $this->assertEquals('datetime', $columnsCreated[TSF::FIELD_CREATED_AT]->getType()->getName());
        $this->assertTrue($columnsCreated[TSF::FIELD_CREATED_AT]->getNotnull());
        $this->assertEquals('datetime', $columnsCreated[TSF::FIELD_UPDATED_AT]->getType()->getName());
        $this->assertFalse($columnsCreated[TSF::FIELD_UPDATED_AT]->getNotnull());
        $this->assertEquals('datetime', $columnsCreated[TSF::FIELD_DELETED_AT]->getType()->getName());
        $this->assertFalse($columnsCreated[TSF::FIELD_DELETED_AT]->getNotnull());

        $this->assertEquals('datetime', $columnsCreated[$columnNullableDateTime]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableDateTime]->getNotnull());

        $this->assertEquals('datetime', $columnsCreated[$columnNonNullDateTime]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNonNullDateTime]->getNotnull());

        $this->assertEquals('date', $columnsCreated[$columnNullableDate]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableDate]->getNotnull());

        $this->assertEquals('date', $columnsCreated[$columnNonNullDate]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNonNullDate]->getNotnull());

        $migration->rollback();
        $this->assertFalse($manager->tablesExist([$tableName]));
    }

    /**
     * Test relationship migration.
     */
    public function testRelationships()
    {
        $modelClass1 = 'TestModel1';
        $table1      = 'table_1';
        $pk1         = 'col_pk1';
        $modelClass2 = 'TestModel2';
        $table2      = 'table_2';
        $pk2         = 'col_pk2';
        $fk2_1       = 'col_fk2_1';
        $fk2_2       = 'col_fk2_2';
        $fk2_3       = 'col_fk2_3';
        $fk2_4       = 'col_fk2_4';
        $rel2_1      = 'rel_2_1_to_1';
        $rel2_3      = 'rel_2_3_to_1';
        $length      = 100;

        $modelSchemes = Mockery::mock(ModelSchemeInfoInterface::class);
        $this->prepareTable($modelSchemes, $modelClass1, $table1);
        $this->prepareTable($modelSchemes, $modelClass2, $table2);
        $this->prepareAttributeLength($modelSchemes, $modelClass2, $pk2, $length);
        $this->prepareRelationship($modelSchemes, $modelClass2, $rel2_1, $fk2_1, $modelClass1, $table1, $pk1);
        $this->prepareForeignRelationship($modelSchemes, $modelClass2, $fk2_2, $modelClass1, $table1, $pk1);
        $this->prepareRelationship($modelSchemes, $modelClass2, $rel2_3, $fk2_3, $modelClass1, $table1, $pk1);
        $this->prepareForeignRelationship($modelSchemes, $modelClass2, $fk2_4, $modelClass1, $table1, $pk1);

        $container = $this->createContainer($modelSchemes);
        // as we create columns in one migration (this test) and then pass it to another migration
        // we have to init both migrations.
        $this->init($container);

        $columnToCreate1 = [
            $this->primaryInt($pk1),
        ];
        $migration1 = new TestTableMigration($modelClass1, $columnToCreate1);
        $migration1->init($container)->migrate();

        $columnToCreate2 = [
            $this->primaryString($pk2),
            $this->relationship($rel2_1),
            $this->foreignRelationship($fk2_2, $modelClass1),
            $this->nullableRelationship($rel2_3),
            $this->nullableForeignRelationship($fk2_4, $modelClass1),
        ];
        $migration2 = new TestTableMigration($modelClass2, $columnToCreate2);
        $migration2->init($container)->migrate();
    }

    public function testEnum()
    {
        $connection = $this->createConnection();
        $platform   = $connection->getDatabasePlatform();

        $table = new Table('table_name');

        ($this->enum('enum1', ['val11', 'val21']))($table);
        ($this->nullableEnum('enum2', ['val21', 'val22']))($table);

        $columns = $table->getColumns();
        $this->assertCount(2, $columns);
        $this->assertEquals("ENUM('val21','val22')", $columns['enum1']->getType()->getSQLDeclaration([], $platform));
        $this->assertTrue($columns['enum1']->getNotnull());
        $this->assertEquals("ENUM('val21','val22')", $columns['enum2']->getType()->getSQLDeclaration([], $platform));
        $this->assertFalse($columns['enum2']->getNotnull());
    }

    /**
     * @param MockInterface $modelSchemes
     *
     * @return ContainerInterface
     */
    private function createContainer(MockInterface $modelSchemes): ContainerInterface
    {
        $container                    = new TestContainer();
        $container[Connection::class] = $this->connection = $this->createConnection();

        $container[ModelSchemeInfoInterface::class] = $modelSchemes;

        return $container;
    }

    /**
     * @return Connection
     */
    private function createConnection(): Connection
    {
        // user and password are needed for HHVM
        $connection = DriverManager::getConnection([
            'url'      => 'sqlite:///',
            'memory'   => true,
            'dbname'   => 'test',
            'user'     => '',
            'password' => '',
        ]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $tableName
     * @param int           $times
     *
     * @return Mock
     */
    private function prepareTable($mock, string $modelClass, string $tableName, int $times = 1)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('getTable')->times($times)->with($modelClass)->andReturn($tableName);

        return $mock;
    }

    /**
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $fieldName
     * @param int           $length
     *
     * @return Mock
     */
    private function prepareAttributeLength($mock, string $modelClass, string $fieldName, int $length)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('getAttributeLength')->once()->with($modelClass, $fieldName)->andReturn($length);

        return $mock;
    }

    /**
     * @param MockInterface $mock
     * @param string        $modelClass
     *
     * @return Mock
     */
    private function prepareTimestamps($mock, string $modelClass)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('hasAttributeType')->once()
            ->with($modelClass, TSF::FIELD_CREATED_AT)->andReturn(true);
        $mock->shouldReceive('hasAttributeType')
            ->once()->with($modelClass, TSF::FIELD_UPDATED_AT)->andReturn(true);
        $mock->shouldReceive('hasAttributeType')
            ->once()->with($modelClass, TSF::FIELD_DELETED_AT)->andReturn(true);

        return $mock;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $relName
     * @param string        $fkName
     * @param string        $reverseClass
     * @param string        $reverseTable
     * @param string        $reversePk
     * @param int           $relType
     * @param string        $colType
     *
     * @return Mock
     */
    private function prepareRelationship(
        $mock,
        string $modelClass,
        string $relName,
        string $fkName,
        string $reverseClass,
        string $reverseTable,
        string $reversePk,
        int $relType = RelationshipTypes::BELONGS_TO,
        string $colType = Type::INTEGER
    ) {
        /** @var Mock $mock */
        $mock->shouldReceive('hasRelationship')->once()->with($modelClass, $relName)->andReturn(true);
        $mock->shouldReceive('getRelationshipType')->once()->with($modelClass, $relName)->andReturn($relType);
        $mock->shouldReceive('getForeignKey')->once()->with($modelClass, $relName)->andReturn($fkName);
        $mock->shouldReceive('getAttributeType')->once()->with($modelClass, $fkName)->andReturn($colType);
        $mock->shouldReceive('getReverseModelClass')->once()->with($modelClass, $relName)->andReturn($reverseClass);
        $mock->shouldReceive('getTable')->once()->with($reverseClass)->andReturn($reverseTable);
        $mock->shouldReceive('getPrimaryKey')->once()->with($reverseClass)->andReturn($reversePk);

        return $mock;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $fkName
     * @param string        $reverseClass
     * @param string        $reverseTable
     * @param string        $reversePk
     * @param string        $colType
     *
     * @return Mock
     */
    private function prepareForeignRelationship(
        $mock,
        string $modelClass,
        string $fkName,
        string $reverseClass,
        string $reverseTable,
        string $reversePk,
        string $colType = Type::INTEGER
    ) {
        /** @var Mock $mock */
        $mock->shouldReceive('hasClass')->once()->with($reverseClass)->andReturn(true);
        $mock->shouldReceive('getTable')->once()->with($reverseClass)->andReturn($reverseTable);
        $mock->shouldReceive('getPrimaryKey')->once()->with($reverseClass)->andReturn($reversePk);
        $mock->shouldReceive('getAttributeType')->once()->with($modelClass, $fkName)->andReturn($colType);

        return $mock;
    }

    /**
     * @inheritdoc
     */
    public function migrate()
    {
    }

    /**
     * @inheritdoc
     */
    public function rollback()
    {
    }
}

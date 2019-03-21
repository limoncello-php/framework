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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\MigrationInterface;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\Data\TimestampFields as TSF;
use Limoncello\Data\Migrations\MigrationTrait;
use Limoncello\Data\Migrations\RawNameType;
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
     *
     * @throws DBALException
     */
    public function testColumns(): void
    {
        $modelClass             = 'TestModel1';
        $columnIntPrimary       = 'col_int_id';
        $tableName              = 'table_name';
        $columnNonNullString    = 'col_non_null_string';
        $columnNullableString   = 'col_nullable_string';
        $columnInt              = 'col_int';
        $columnNullableInt      = 'col_nullable_int';
        $columnInt2              = 'col_int_2';
        $columnNullableInt2      = 'col_nullable_int_2';
        $columnStringLength     = 100;
        $columnNonNullText      = 'col_non_null_text';
        $columnNullableText     = 'col_nullable_text';
        $columnUInt             = 'col_u_int';
        $columnNullableUInt     = 'col_nullable_u_int';
        $columnFloat            = 'col_float';
        $columnBool             = 'col_bool';
        $columnBinary           = 'col_binary';
        $columnNonNullDate      = 'col_non_null_date';
        $columnNullableDate     = 'col_nullable_date';
        $columnNonNullDateTime  = 'col_non_null_datetime';
        $columnNullableDateTime = 'col_nullable_datetime';

        $defaultUInt    = 123;
        $columnToCreate = [
            $this->primaryInt($columnIntPrimary),
            $this->string($columnNonNullString),
            $this->nullableString($columnNullableString),
            $this->int($columnInt),
            $this->nullableInt($columnNullableInt),
            $this->text($columnNonNullText),
            $this->nullableText($columnNullableText),
            $this->unsignedInt($columnUInt),
            $this->nullableUnsignedInt($columnNullableUInt),
            $this->float($columnFloat),
            $this->bool($columnBool, true),
            $this->binary($columnBinary),
            $this->timestamps(),
            $this->date($columnNonNullDate),
            $this->nullableDate($columnNullableDate),
            $this->datetime($columnNonNullDateTime),
            $this->nullableDatetime($columnNullableDateTime),

            $this->nullableInt($columnInt2),
            $this->int($columnNullableInt2),
            $this->notNullableValue($columnInt2),
            $this->nullableValue($columnNullableInt2),

            $this->unique([$columnNonNullString]),

            $this->searchable([$columnNonNullText]),

            $this->defaultValue($columnUInt, $defaultUInt)
        ];
        $migration      = new TestTableMigration($modelClass, $columnToCreate);

        $modelSchemas = Mockery::mock(ModelSchemaInfoInterface::class);
        $this->prepareTable($modelSchemas, $modelClass, $tableName, 2);
        $this->prepareAttributeLength($modelSchemas, $modelClass, $columnNonNullString, $columnStringLength);
        $this->prepareAttributeLength($modelSchemas, $modelClass, $columnNullableString, $columnStringLength);
        $this->prepareTimestamps($modelSchemas, $modelClass);

        $container = $this->createContainer($modelSchemas);
        // as we create columns in one migration (this test) and then pass it to another migration
        // we have to init both migrations.
        $this->init($container);
        $migration->init($container)->migrate();

        // check migration
        $manager = $this->connection->getSchemaManager();
        $this->assertTrue($manager->tablesExist([$tableName]));
        $columnsCreated = $manager->listTableColumns($tableName);
        // +2 for timestamps and -5 for searchable, unique, default value, nullable and not nullable value.
        /** @noinspection PhpParamsInspection */
        $this->assertCount(count($columnToCreate) - 3, $columnsCreated);

        $this->assertEquals('integer', $columnsCreated[$columnIntPrimary]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnIntPrimary]->getAutoincrement());

        $this->assertEquals('string', $columnsCreated[$columnNonNullString]->getType()->getName());
        $this->assertEquals($columnStringLength, $columnsCreated[$columnNonNullString]->getLength());
        $this->assertTrue($columnsCreated[$columnNonNullString]->getNotnull());

        $this->assertEquals('string', $columnsCreated[$columnNullableString]->getType()->getName());
        $this->assertEquals($columnStringLength, $columnsCreated[$columnNullableString]->getLength());
        $this->assertFalse($columnsCreated[$columnNullableString]->getNotnull());

        $this->assertEquals('integer', $columnsCreated[$columnInt]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnInt]->getNotnull());
        $this->assertEquals('integer', $columnsCreated[$columnInt2]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnInt2]->getNotnull());

        $this->assertEquals('integer', $columnsCreated[$columnNullableInt]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableInt]->getNotnull());
        $this->assertEquals('integer', $columnsCreated[$columnNullableInt2]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableInt2]->getNotnull());

        $this->assertEquals('text', $columnsCreated[$columnNonNullText]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNonNullText]->getNotnull());

        $this->assertEquals('text', $columnsCreated[$columnNullableText]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnNullableText]->getNotnull());

        $this->assertEquals('integer', $columnsCreated[$columnUInt]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnUInt]->getUnsigned());
        $this->assertTrue($columnsCreated[$columnUInt]->getNotnull());
        $this->assertEquals($defaultUInt, $columnsCreated[$columnUInt]->getDefault());

        $this->assertEquals('integer', $columnsCreated[$columnNullableUInt]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnNullableUInt]->getUnsigned());
        $this->assertFalse($columnsCreated[$columnNullableUInt]->getNotnull());

        $this->assertEquals('float', $columnsCreated[$columnFloat]->getType()->getName());
        $this->assertFalse($columnsCreated[$columnFloat]->getUnsigned());
        $this->assertTrue($columnsCreated[$columnFloat]->getNotnull());

        $this->assertEquals('boolean', $columnsCreated[$columnBool]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnBool]->getNotnull());

        $this->assertEquals('blob', $columnsCreated[$columnBinary]->getType()->getName());
        $this->assertTrue($columnsCreated[$columnBinary]->getNotnull());

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
     *
     * @throws DBALException
     */
    public function testRelationships(): void
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

        $modelSchemas = Mockery::mock(ModelSchemaInfoInterface::class);
        $this->prepareTable($modelSchemas, $modelClass1, $table1);
        $this->prepareTable($modelSchemas, $modelClass2, $table2);
        $this->prepareAttributeLength($modelSchemas, $modelClass2, $pk2, $length);
        $this->prepareRelationship($modelSchemas, $modelClass2, $rel2_1, $fk2_1, $modelClass1, $table1, $pk1);
        $this->prepareForeignRelationship($modelSchemas, $modelClass2, $fk2_2, $modelClass1, $table1, $pk1);
        $this->prepareRelationship($modelSchemas, $modelClass2, $rel2_3, $fk2_3, $modelClass1, $table1, $pk1);
        $this->prepareForeignRelationship($modelSchemas, $modelClass2, $fk2_4, $modelClass1, $table1, $pk1);

        $container = $this->createContainer($modelSchemas);
        // as we create columns in one migration (this test) and then pass it to another migration
        // we have to init both migrations.
        $this->init($container);

        $columnToCreate1 = [
            $this->primaryInt($pk1),
        ];
        $migration1      = new TestTableMigration($modelClass1, $columnToCreate1);
        $migration1->init($container)->migrate();

        $columnToCreate2 = [
            $this->primaryString($pk2),
            $this->relationship($rel2_1),
            $this->foreignRelationship($fk2_2, $modelClass1),
            $this->nullableRelationship($rel2_3),
            $this->nullableForeignRelationship($fk2_4, $modelClass1),
        ];
        $migration2      = new TestTableMigration($modelClass2, $columnToCreate2);
        $migration2->init($container)->migrate();
    }

    /**
     * @throws DBALException
     */
    public function testEnum(): void
    {
        $container = $this->createContainer();
        $this->init($container);
        $connection = $this->createConnection();
        $platform   = $connection->getDatabasePlatform();

        $table = new Table('table_name');

        ($this->enum('enum1', ['val11', 'val12']))($table);
        ($this->nullableEnum('enum2', ['val21', 'val22']))($table);

        $columns = $table->getColumns();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(2, $columns);
        $this->assertEquals(
            "ENUM('val11','val12')",
            $columns['enum1']->getType()->getSQLDeclaration($columns['enum1']->toArray(), $platform)
        );
        $this->assertTrue($columns['enum1']->getNotnull());
        $this->assertEquals(
            "ENUM('val21','val22')",
            $columns['enum2']->getType()->getSQLDeclaration($columns['enum2']->toArray(), $platform)
        );
        $this->assertFalse($columns['enum2']->getNotnull());
    }

    /**
     * @throws DBALException
     */
    public function testCreatePgEnum(): void
    {
        $expectedSql = "CREATE TYPE myEnum AS ENUM ('value1', 'value2');";

        $container = new TestContainer();
        $this->init($container);

        $container[Connection::class] = $mockConnection = Mockery::mock(Connection::class);
        $mockConnection->shouldReceive('getDriver')->once()->withNoArgs()->andReturnSelf();
        $mockConnection->shouldReceive('getName')->once()->withNoArgs()->andReturn('pdo_pgsql');
        $mockConnection->shouldReceive('exec')->once()->with($expectedSql)->andReturnUndefined();

        $this->createEnum('myEnum', ['value1', 'value2']);

        // the mock will be checked
        $this->assertTrue(true);
    }

    /**
     * @throws DBALException
     */
    public function testDropPgEnumIfExists(): void
    {
        $expectedSql = 'DROP TYPE IF EXISTS myEnum;';

        $container = new TestContainer();
        $this->init($container);

        $container[Connection::class] = $mockConnection = Mockery::mock(Connection::class);
        $mockConnection->shouldReceive('getDriver')->once()->withNoArgs()->andReturnSelf();
        $mockConnection->shouldReceive('getName')->once()->withNoArgs()->andReturn('pdo_pgsql');
        $mockConnection->shouldReceive('quoteIdentifier')->once()->with('myEnum')->andReturn('myEnum');
        $mockConnection->shouldReceive('exec')->once()->with($expectedSql)->andReturnUndefined();

        $this->dropEnumIfExists('myEnum');

        // the mock will be checked
        $this->assertTrue(true);
    }

    /**
     * @throws DBALException
     */
    public function testUsePgEnum(): void
    {
        $container = new TestContainer();
        $this->init($container);

        $container[Connection::class] = $mockConnection = Mockery::mock(Connection::class);
        $mockConnection->shouldReceive('getDriver')->once()->withNoArgs()->andReturnSelf();
        $mockConnection->shouldReceive('getName')->once()->withNoArgs()->andReturn('pdo_pgsql');

        $closure = $this->useEnum('my_column', 'myEnum');
        $table   = new Table('table_name');
        call_user_func($closure, $table);

        $columns = $table->getColumns();
        /** @noinspection PhpParamsInspection */
        $this->assertCount(1, $columns);
        $column = $columns['my_column'];
        $this->assertEquals(RawNameType::class, get_class($column->getType()));
    }

    /**
     * @throws DBALException
     */
    public function testRawNameType(): void
    {
        $typeName = RawNameType::TYPE_NAME;
        Type::hasType($typeName) === true ?: Type::addType($typeName, RawNameType::class);

        $type = Type::getType($typeName);

        $platform = Mockery::mock(AbstractPlatform::class);
        /** @var AbstractPlatform $platform */
        $declaration = $type->getSQLDeclaration([RawNameType::TYPE_NAME => 'myEnum'], $platform);
        $this->assertEquals('myEnum', $declaration);
        $this->assertEquals(RawNameType::TYPE_NAME, $type->getName());
    }

    /**
     * @param MockInterface|null $modelSchemas
     *
     * @return ContainerInterface
     *
     * @throws DBALException
     */
    private function createContainer(MockInterface $modelSchemas = null): ContainerInterface
    {
        $container                    = new TestContainer();
        $container[Connection::class] = $this->connection = $this->createConnection();

        if ($modelSchemas !== null) {
            $container[ModelSchemaInfoInterface::class] = $modelSchemas;
        }

        return $container;
    }

    /**
     * @return Connection
     *
     * @throws DBALException
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
    private function prepareTable(MockInterface $mock, string $modelClass, string $tableName, int $times = 1)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('hasClass')->times($times)->with($modelClass)->andReturn(true);
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
    private function prepareTimestamps(MockInterface $mock, string $modelClass)
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
        MockInterface $mock,
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
        MockInterface $mock,
        string $modelClass,
        string $fkName,
        string $reverseClass,
        string $reverseTable,
        string $reversePk,
        string $colType = Type::INTEGER
    ) {
        /** @var Mock $mock */
        $mock->shouldReceive('hasClass')->times(2)->with($reverseClass)->andReturn(true);
        $mock->shouldReceive('getTable')->once()->with($reverseClass)->andReturn($reverseTable);
        $mock->shouldReceive('getPrimaryKey')->once()->with($reverseClass)->andReturn($reversePk);
        $mock->shouldReceive('getAttributeType')->once()->with($modelClass, $fkName)->andReturn($colType);

        return $mock;
    }

    /**
     * @inheritdoc
     */
    public function migrate(): void
    {
    }

    /**
     * @inheritdoc
     */
    public function rollback(): void
    {
    }
}

<?php namespace Limoncello\Tests\Flute\Adapters;

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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Flute\Adapters\FilterOperations;
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\I18n\Translator;
use Limoncello\Tests\Flute\TestCase;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * @package Limoncello\Tests\Flute
 */
class FilterOperationsTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    private $builder;

    /**
     * @var FilterOperationsInterface
     */
    private $filters;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $connection = $this->createConnection();

        $this->builder = $connection->createQueryBuilder();
        $this->filters = new FilterOperations(new Translator());

        $schemaManager = $connection->getSchemaManager();
        $table         = new Table('table_name');
        $table->addColumn('column_name', Type::TEXT);
        $schemaManager->createTable($table);

        $connection->insert('table_name', ['column_name' => 'value']);
        $connection->insert('table_name', ['column_name' => 'value1']);
        $connection->insert('table_name', ['column_name' => 'value2']);
    }

    /**
     * Test apply condition.
     */
    public function testApplySingleCondition()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyEquals($this->builder, $link, $errors, 'table_name', 'column_name', 'value');
        $this->assertEquals(0, $errors->count());

        $this->builder->select('*')->from('table_name')->where($link);

        $this->assertEquals(
            'SELECT * FROM table_name WHERE `table_name`.`column_name` = :dcValue1',
            $this->builder->getSQL()
        );
        $this->assertEquals(['dcValue1' => 'value'], $this->builder->getParameters());

        $this->assertEquals([
            ['column_name' => 'value'],
        ], $this->builder->execute()->fetchAll());
    }

    /**
     * Test apply conditions.
     */
    public function testApplyMultiConditions()
    {
        $link   = $this->builder->expr()->orX();
        $errors = new ErrorCollection();

        $this->filters->applyEquals($this->builder, $link, $errors, 'table_name', 'column_name', ['value1', 'value2']);
        $this->assertEquals(0, $errors->count());

        $this->builder->select('*')->from('table_name')->where($link);

        $this->assertEquals(
            'SELECT * FROM table_name WHERE (`table_name`.`column_name` = :dcValue1) OR ' .
            '(`table_name`.`column_name` = :dcValue2)',
            $this->builder->getSQL()
        );
        $this->assertEquals(['dcValue1' => 'value1', 'dcValue2' => 'value2'], $this->builder->getParameters());

        $this->assertEquals([
            ['column_name' => 'value1'],
            ['column_name' => 'value2'],
        ], $this->builder->execute()->fetchAll());
    }

    /**
     * Test apply conditions.
     */
    public function testApplyOtherOperations()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyNotEquals($this->builder, $link, $errors, 'table_name', 'column_name', 'value1');
        $this->filters->applyLessThan($this->builder, $link, $errors, 'table_name', 'column_name', 'value2');
        $this->filters->applyLessOrEquals($this->builder, $link, $errors, 'table_name', 'column_name', 'value3');
        $this->filters->applyGreaterThan($this->builder, $link, $errors, 'table_name', 'column_name', 'value4');
        $this->filters->applyGreaterOrEquals($this->builder, $link, $errors, 'table_name', 'column_name', 'value5');
        $this->filters->applyIsNull($this->builder, $link, 'table_name', 'column_name');
        $this->filters->applyIsNotNull($this->builder, $link, 'table_name', 'column_name');
        $this->filters->applyLike($this->builder, $link, $errors, 'table_name', 'column_name', 'value6');
        $this->filters->applyNotLike($this->builder, $link, $errors, 'table_name', 'column_name', 'value7');
        $this->filters->applyIn($this->builder, $link, $errors, 'table_name', 'column_name', ['value8']);
        $this->filters->applyNotIn($this->builder, $link, $errors, 'table_name', 'column_name', ['value9', 'value10']);
        $this->assertEquals(0, $errors->count());

        $this->builder->select('*')->from('table_name')->where($link);

        $expected = 'SELECT * FROM table_name WHERE ' .
            '(`table_name`.`column_name` <> :dcValue1) AND ' .
            '(`table_name`.`column_name` < :dcValue2) AND ' .
            '(`table_name`.`column_name` <= :dcValue3) AND ' .
            '(`table_name`.`column_name` > :dcValue4) AND ' .
            '(`table_name`.`column_name` >= :dcValue5) AND ' .
            '(`table_name`.`column_name` IS NULL) AND ' .
            '(`table_name`.`column_name` IS NOT NULL) AND ' .
            '(`table_name`.`column_name` LIKE :dcValue6) AND ' .
            '(`table_name`.`column_name` NOT LIKE :dcValue7) AND ' .
            '(`table_name`.`column_name` IN (:dcValue8)) AND ' .
            '(`table_name`.`column_name` NOT IN (:dcValue9, :dcValue10))';

        $this->assertEquals($expected, $this->builder->getSQL());
        $this->assertEquals([
            'dcValue1'  => 'value1',
            'dcValue2'  => 'value2',
            'dcValue3'  => 'value3',
            'dcValue4'  => 'value4',
            'dcValue5'  => 'value5',
            'dcValue6'  => 'value6',
            'dcValue7'  => 'value7',
            'dcValue8'  => 'value8',
            'dcValue9'  => 'value9',
            'dcValue10' => 'value10',
        ], $this->builder->getParameters());
    }

    /**
     * Test invalid input.
     */
    public function testInvalidInput1()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyEquals($this->builder, $link, $errors, 'table_name', 'column_name', [['value']]);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(['parameter' => 'column_name'], $errors[0]->getSource());
    }

    /**
     * Test invalid input.
     */
    public function testInvalidInput2()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyEquals($this->builder, $link, $errors, 'table_name', 'column_name', new self);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(['parameter' => 'column_name'], $errors[0]->getSource());
    }

    /**
     * Test invalid input.
     */
    public function testInvalidInput3()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyIn($this->builder, $link, $errors, 'table_name', 'column_name', [['value']]);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(['parameter' => 'column_name'], $errors[0]->getSource());
    }

    /**
     * Test invalid input.
     */
    public function testInvalidInput4()
    {
        $link   = $this->builder->expr()->andX();
        $errors = new ErrorCollection();

        $this->filters->applyNotIn($this->builder, $link, $errors, 'table_name', 'column_name', [new self]);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(['parameter' => 'column_name'], $errors[0]->getSource());
    }
}

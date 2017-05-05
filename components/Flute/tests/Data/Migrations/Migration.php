<?php namespace Limoncello\Tests\Flute\Data\Migrations;

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

use Closure;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Tests\Flute\Data\Models\Model;
use Limoncello\Tests\Flute\Data\Models\ModelInterface;

/**
 * @package Limoncello\Tests\Flute
 */
abstract class Migration
{
    /** Model class */
    const MODEL_CLASS = null;

    /**
     * @return void
     */
    abstract public function migrate();

    /**
     * @var AbstractSchemaManager
     */
    private $schemaManager;

    /**
     * @param AbstractSchemaManager $schemaManager
     */
    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * @inheritdoc
     */
    public function rollback()
    {
        $tableName = $this->getTableName();
        if ($this->getSchemaManager()->tablesExist([$tableName]) === true) {
            $this->getSchemaManager()->dropTable($tableName);
        }
    }

    /**
     * @return AbstractSchemaManager
     */
    protected function getSchemaManager()
    {
        return $this->schemaManager;
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        $modelClass = $this->getModelClass();

        return $this->getTableNameForClass($modelClass);
    }

    /**
     * @param string    $name
     * @param Closure[] $expressions
     *
     * @return Table
     */
    protected function createTable($name, array $expressions = [])
    {
        $table = new Table($name);

        foreach ($expressions as $expression) {
            /** @var Closure $expression */
            $expression($table);
        }

        $this->getSchemaManager()->dropAndCreateTable($table);

        return $table;
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function primaryInt($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::INTEGER)->setAutoincrement(true)->setUnsigned(true)->setNotnull(true);
            $table->setPrimaryKey([$name]);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function primaryString($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::STRING)->setNotnull(true);
            $table->setPrimaryKey([$name]);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function string($name)
    {
        return function (Table $table) use ($name) {
            $modelClass = $this->getModelClass();
            /** @var ModelInterface $modelClass*/
            $lengths   = $modelClass::getAttributeLengths();
            $hasLength = array_key_exists($name, $lengths);
            assert('$hasLength === true', "String length is not specified for column '$name' in model '$modelClass'.");
            $hasLength ?: null;
            $length = $lengths[$name];
            $table->addColumn($name, Type::STRING, ['length' => $length])->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function text($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::TEXT)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function bool($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::BOOLEAN)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function datetime($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::DATETIME)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function nullableDatetime($name)
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::DATETIME)->setNotnull(false);
        };
    }

    /**
     * @param string[] $names
     *
     * @return Closure
     */
    protected function unique(array $names)
    {
        return function (Table $table) use ($names) {
            $table->addUniqueIndex($names);
        };
    }

    /**
     * @param string $name
     * @param string $referredClass
     * @param bool   $notNull
     *
     * @return Closure
     */
    protected function foreignInt($name, $referredClass, $notNull = true)
    {
        return function (Table $table) use ($name, $referredClass, $notNull) {
            $table->addColumn($name, Type::INTEGER)->setUnsigned(true)->setNotnull($notNull);
            $tableName = $this->getTableNameForClass($referredClass);
            /** @var Model $referredClass*/
            assert('$tableName !== null', "Table name is not specified for model '$referredClass'.");
            $pkName = $referredClass::FIELD_ID;
            $table->addForeignKeyConstraint($tableName, [$name], [$pkName]);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function relationship($name)
    {
        /** @var ModelInterface $modelClass */
        $modelClass    = $this->getModelClass();
        $relationships = $modelClass::getRelationships();
        $relFound      = isset($relationships[RelationshipTypes::BELONGS_TO][$name]);
        if ($relFound === false) {
            assert('$relFound === true', "Belongs-to relationship '$name' not found.");
        }
        assert('$relFound === true', "Belongs-to relationship '$name' not found.");
        list ($referencedClass, $foreignKey) = $relationships[RelationshipTypes::BELONGS_TO][$name];
        return $this->foreignInt($foreignKey, $referencedClass);
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function nullableRelationship($name)
    {
        /** @var ModelInterface $modelClass */
        $modelClass    = $this->getModelClass();
        $relationships = $modelClass::getRelationships();
        $relFound      = isset($relationships[RelationshipTypes::BELONGS_TO][$name]);
        if ($relFound === false) {
            assert('$relFound === true', "Belongs-to relationship '$name' not found.");
        }
        list ($referencedClass, $foreignKey) = $relationships[RelationshipTypes::BELONGS_TO][$name];
        return $this->foreignInt($foreignKey, $referencedClass, false);
    }

    /**
     * @param string $modelClass
     *
     * @return string
     */
    protected function getTableNameForClass($modelClass)
    {
        /** @var Model $modelClass*/
        $tableName = $modelClass::TABLE_NAME;
        assert('$tableName !== null', "Table name is not specified for model '$modelClass'.");

        return $tableName;
    }

    /**
     * @return string
     */
    private function getModelClass()
    {
        $modelClass = static::MODEL_CLASS;
        assert('$modelClass !== null', 'Model class should be set in migration');

        return $modelClass;
    }
}

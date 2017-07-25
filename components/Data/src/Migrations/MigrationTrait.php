<?php namespace Limoncello\Data\Migrations;

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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\MigrationInterface;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\Data\TimestampFields;
use Limoncello\Data\Contracts\MigrationContextInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Data
 */
trait MigrationTrait
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function init(ContainerInterface $container): MigrationInterface
    {
        $this->container = $container;

        /** @var MigrationInterface $self */
        $self = $this;

        return $self;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        assert($this->getContainer()->has(Connection::class) === true);

        return $this->getContainer()->get(Connection::class);
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        assert($this->getContainer()->has(ModelSchemeInfoInterface::class) === true);

        return $this->getContainer()->get(ModelSchemeInfoInterface::class);
    }

    /**
     * @return AbstractSchemaManager
     */
    protected function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getConnection()->getSchemaManager();
    }

    /**
     * @param string    $modelClass
     * @param Closure[] $expressions
     *
     * @return Table
     */
    protected function createTable(string $modelClass, array $expressions = []): Table
    {
        $context   = new MigrationContext($modelClass, $this->getModelSchemes());
        $tableName = $this->getModelSchemes()->getTable($modelClass);
        $table     = new Table($tableName);
        foreach ($expressions as $expression) {
            /** @var Closure $expression */
            $expression($table, $context);
        }

        $this->getSchemaManager()->dropAndCreateTable($table);

        return $table;
    }

    /**
     * @param string $modelClass
     *
     * @return void
     */
    protected function dropTableIfExists(string $modelClass): void
    {
        $tableName     = $this->getModelSchemes()->getTable($modelClass);
        $schemeManager = $this->getSchemaManager();

        if ($schemeManager->tablesExist([$tableName]) === true) {
            $schemeManager->dropTable($tableName);
        }
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function primaryInt(string $name): Closure
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
    protected function primaryString(string $name): Closure
    {
        return function (Table $table, MigrationContextInterface $context) use ($name) {
            $length = $context->getModelSchemes()->getAttributeLength($context->getModelClass(), $name);
            $table->addColumn($name, Type::STRING)->setLength($length)->setNotnull(true);
            $table->setPrimaryKey([$name]);
        };
    }

    /**
     * @param string   $name
     * @param null|int $default
     *
     * @return Closure
     */
    protected function unsignedInt(string $name, int $default = null): Closure
    {
        return $this->unsignedIntImpl($name, true, $default);
    }

    /**
     * @param string   $name
     * @param null|int $default
     *
     * @return Closure
     */
    protected function nullableUnsignedInt(string $name, int $default = null): Closure
    {
        return $this->unsignedIntImpl($name, false, $default);
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function float(string $name): Closure
    {
        // precision and scale both seems to be ignored in Doctrine so not much sense to have them as inputs

        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::FLOAT)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function string(string $name): Closure
    {
        return function (Table $table, MigrationContextInterface $context) use ($name) {
            $length = $context->getModelSchemes()->getAttributeLength($context->getModelClass(), $name);
            $table->addColumn($name, Type::STRING)->setLength($length)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function nullableString(string $name): Closure
    {
        return function (Table $table, MigrationContextInterface $context) use ($name) {
            $length = $context->getModelSchemes()->getAttributeLength($context->getModelClass(), $name);
            $table->addColumn($name, Type::STRING)->setLength($length)->setNotnull(false);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function text(string $name): Closure
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
    protected function nullableText(string $name): Closure
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::TEXT)->setNotnull(false);
        };
    }

    /**
     * @param string    $name
     * @param null|bool $default
     *
     * @return Closure
     */
    protected function bool(string $name, $default = null): Closure
    {
        return function (Table $table) use ($name, $default) {
            $column = $table->addColumn($name, Type::BOOLEAN)->setNotnull(true);
            if ($default !== null && is_bool($default) === true) {
                $column->setDefault($default);
            }
        };
    }

    /**
     * @param string $name
     * @param array  $values
     *
     * @return Closure
     */
    protected function enum(string $name, array $values): Closure
    {
        return $this->enumImpl($name, $values, true);
    }

    /**
     * @param string $name
     * @param array  $values
     *
     * @return Closure
     */
    protected function nullableEnum(string $name, array $values): Closure
    {
        return $this->enumImpl($name, $values, false);
    }

    /**
     * @return Closure
     */
    protected function timestamps(): Closure
    {
        return function (Table $table, MigrationContextInterface $context) {
            $modelClass = $context->getModelClass();

            $createdAt = TimestampFields::FIELD_CREATED_AT;
            $updatedAt = TimestampFields::FIELD_UPDATED_AT;
            $deletedAt = TimestampFields::FIELD_DELETED_AT;

            // a list of data columns and `nullable` flag
            $datesToAdd = [];
            if ($this->getModelSchemes()->hasAttributeType($modelClass, $createdAt) === true) {
                $datesToAdd[$createdAt] = true;
            }
            if ($this->getModelSchemes()->hasAttributeType($modelClass, $updatedAt) === true) {
                $datesToAdd[$updatedAt] = false;
            }
            if ($this->getModelSchemes()->hasAttributeType($modelClass, $deletedAt) === true) {
                $datesToAdd[$deletedAt] = false;
            }

            foreach ($datesToAdd as $column => $isNullable) {
                $table->addColumn($column, Type::DATETIME)->setNotnull($isNullable);
            }
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function datetime(string $name): Closure
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
    protected function nullableDatetime(string $name): Closure
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::DATETIME)->setNotnull(false);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function date(string $name): Closure
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::DATE)->setNotnull(true);
        };
    }

    /**
     * @param string $name
     *
     * @return Closure
     */
    protected function nullableDate(string $name): Closure
    {
        return function (Table $table) use ($name) {
            $table->addColumn($name, Type::DATE)->setNotnull(false);
        };
    }

    /**
     * @param string[] $names
     *
     * @return Closure
     */
    protected function unique(array $names): Closure
    {
        return function (Table $table) use ($names) {
            $table->addUniqueIndex($names);
        };
    }

    /**
     * @param string[] $names
     *
     * @return Closure
     */
    protected function searchable(array $names): Closure
    {
        return function (Table $table) use ($names) {
            $table->addIndex($names, null, ['fulltext']);
        };
    }

    /**
     * @param string $column
     * @param string $referredClass
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function foreignRelationship(
        string $column,
        string $referredClass,
        bool $cascadeDelete = false
    ): Closure {
        return function (
            Table $table,
            MigrationContextInterface $context
        ) use (
            $column,
            $referredClass,
            $cascadeDelete
        ) {
            $tableName  = $this->getTableNameForClass($referredClass);
            $pkName     = $this->getModelSchemes()->getPrimaryKey($referredClass);
            $columnType = $this->getModelSchemes()->getAttributeType($context->getModelClass(), $column);

            $closure = $this->foreignColumn($column, $tableName, $pkName, $columnType, $cascadeDelete);

            return $closure($table, $context);
        };
    }

    /**
     * @param string $column
     * @param string $referredClass
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function nullableForeignRelationship(
        string $column,
        string $referredClass,
        bool $cascadeDelete = false
    ): Closure {
        return function (
            Table $table,
            MigrationContextInterface $context
        ) use (
            $column,
            $referredClass,
            $cascadeDelete
        ) {
            $tableName  = $this->getTableNameForClass($referredClass);
            $pkName     = $this->getModelSchemes()->getPrimaryKey($referredClass);
            $columnType = $this->getModelSchemes()->getAttributeType($context->getModelClass(), $column);

            $closure = $this->nullableForeignColumn($column, $tableName, $pkName, $columnType, $cascadeDelete);

            return $closure($table, $context);
        };
    }

    /**
     * @param string $localKey
     * @param string $foreignTable
     * @param string $foreignKey
     * @param string $type
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function foreignColumn(
        string $localKey,
        string $foreignTable,
        string $foreignKey,
        string $type,
        bool $cascadeDelete = false
    ): Closure {
        return $this->foreignColumnImpl($localKey, $foreignTable, $foreignKey, $type, true, $cascadeDelete);
    }

    /**
     * @param string $localKey
     * @param string $foreignTable
     * @param string $foreignKey
     * @param string $type
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function nullableForeignColumn(
        string $localKey,
        string $foreignTable,
        string $foreignKey,
        string $type,
        bool $cascadeDelete = false
    ): Closure {
        return $this->foreignColumnImpl($localKey, $foreignTable, $foreignKey, $type, false, $cascadeDelete);
    }

    /**
     * @param string $name
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function nullableRelationship(string $name, bool $cascadeDelete = false): Closure
    {
        return $this->relationshipImpl($name, false, $cascadeDelete);
    }

    /**
     * @param string $name
     * @param bool   $cascadeDelete
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    protected function relationship(string $name, bool $cascadeDelete = false): Closure
    {
        return $this->relationshipImpl($name, true, $cascadeDelete);
    }

    /**
     * @param string $modelClass
     *
     * @return string
     */
    protected function getTableNameForClass(string $modelClass): string
    {
        assert(
            $this->getModelSchemes()->hasClass($modelClass),
            "Table name is not specified for model '$modelClass'."
        );

        $tableName = $this->getModelSchemes()->getTable($modelClass);

        return $tableName;
    }

    /**
     * @param string     $name
     * @param bool       $notNullable
     * @param null|mixed $default
     *
     * @return Closure
     */
    private function unsignedIntImpl($name, $notNullable, $default = null): Closure
    {
        return function (Table $table) use ($name, $notNullable, $default) {
            $column = $table->addColumn($name, Type::INTEGER)->setUnsigned(true)->setNotnull($notNullable);
            $default === null ?: $column->setDefault($default);
        };
    }

    /**
     * @param string $name
     * @param array  $values
     * @param bool   $notNullable
     *
     * @return Closure
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function enumImpl($name, array $values, $notNullable): Closure
    {
        return function (Table $table) use ($name, $values, $notNullable) {
            Type::hasType(EnumType::TYPE_NAME) === true ?: Type::addType(EnumType::TYPE_NAME, EnumType::class);
            EnumType::setValues($values);
            $table->addColumn($name, EnumType::TYPE_NAME)->setNotnull($notNullable);
        };
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string $localKey
     * @param string $foreignTable
     * @param string $foreignKey
     * @param string $type
     * @param bool   $notNullable
     * @param bool   $cascadeDelete
     *
     * @return Closure
     */
    private function foreignColumnImpl(
        string $localKey,
        string $foreignTable,
        string $foreignKey,
        string $type,
        bool $notNullable,
        bool $cascadeDelete
    ): Closure {
        return function (Table $table) use (
            $localKey,
            $foreignTable,
            $foreignKey,
            $notNullable,
            $cascadeDelete,
            $type
        ) {
            $options = $cascadeDelete === true ? ['onDelete' => 'CASCADE'] : [];
            $table->addColumn($localKey, $type)->setUnsigned(true)->setNotnull($notNullable);
            $table->addForeignKeyConstraint($foreignTable, [$localKey], [$foreignKey], $options);
        };
    }

    /**
     * @param string $name
     * @param bool   $notNullable
     * @param bool   $cascadeDelete
     *
     * @return Closure
     */
    private function relationshipImpl(string $name, bool $notNullable, bool $cascadeDelete): Closure
    {
        return function (
            Table $table,
            MigrationContextInterface $context
        ) use (
            $name,
            $notNullable,
            $cascadeDelete
        ) {
            $modelClass = $context->getModelClass();

            assert(
                $this->getModelSchemes()->hasRelationship($modelClass, $name),
                "Relationship `$name` not found for model `$modelClass`."
            );
            assert(
                $this->getModelSchemes()->getRelationshipType($modelClass, $name) === RelationshipTypes::BELONGS_TO,
                "Relationship `$name` for model `$modelClass` must be `belongsTo`."
            );

            $localKey   = $this->getModelSchemes()->getForeignKey($modelClass, $name);
            $columnType = $this->getModelSchemes()->getAttributeType($modelClass, $localKey);

            $otherModelClass = $this->getModelSchemes()->getReverseModelClass($modelClass, $name);
            $foreignTable    = $this->getModelSchemes()->getTable($otherModelClass);
            $foreignKey      = $this->getModelSchemes()->getPrimaryKey($otherModelClass);

            $fkClosure = $this->foreignColumnImpl(
                $localKey,
                $foreignTable,
                $foreignKey,
                $columnType,
                $notNullable,
                $cascadeDelete
            );

            return $fkClosure($table);
        };
    }
}

<?php declare (strict_types = 1);

namespace Limoncello\Flute\Adapters;

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

use Closure;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use PDO;
use function assert;
use function call_user_func;
use function is_array;
use function is_bool;
use function is_callable;
use function is_int;
use function is_iterable;
use function is_string;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ModelQueryBuilder extends QueryBuilder
{
    /**
     * Condition joining method.
     */
    public const AND = 0;

    /**
     * Condition joining method.
     */
    public const OR = self::AND + 1;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var string
     */
    private $mainTableName;

    /**
     * @var string
     */
    private $mainAlias;

    /**
     * @var Closure
     */
    private $columnMapper;

    /**
     * @var ModelSchemaInfoInterface
     */
    private $modelSchemas;

    /**
     * @var int
     */
    private $aliasIdCounter = 0;

    /**
     * @var array
     */
    private $knownAliases = [];

    /**
     * @var Type|null
     */
    private $dateTimeType;

    /**
     * @param Connection               $connection
     * @param string                   $modelClass
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(Connection $connection, string $modelClass, ModelSchemaInfoInterface $modelSchemas)
    {
        assert(!empty($modelClass));

        parent::__construct($connection);

        $this->modelSchemas = $modelSchemas;
        $this->modelClass   = $modelClass;

        $this->mainTableName = $this->getModelSchemas()->getTable($this->getModelClass());
        $this->mainAlias     = $this->createAlias($this->getTableName());

        $this->setColumnToDatabaseMapper(Closure::fromCallable([$this, 'quoteDoubleIdentifier']));
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @param string|null $tableAlias
     * @param string|null $modelClass
     *
     * @return array
     *
     * @throws DBALException
     */
    public function getModelColumns(string $tableAlias = null, string $modelClass = null): array
    {
        $modelClass = $modelClass ?? $this->getModelClass();
        $tableAlias = $tableAlias ?? $this->getAlias();

        $quotedColumns = [];

        $columnMapper    = $this->getColumnToDatabaseMapper();
        $selectedColumns = $this->getModelSchemas()->getAttributes($modelClass);
        foreach ($selectedColumns as $column) {
            $quotedColumns[] = call_user_func($columnMapper, $tableAlias, $column, $this);
        }

        $rawColumns = $this->getModelSchemas()->getRawAttributes($modelClass);
        if (empty($rawColumns) === false) {
            $platform = $this->getConnection()->getDatabasePlatform();
            foreach ($rawColumns as $columnOrCallable) {
                assert(is_string($columnOrCallable) === true || is_callable($columnOrCallable) === true);
                $quotedColumns[] = is_callable($columnOrCallable) === true ?
                    call_user_func($columnOrCallable, $tableAlias, $platform) : $columnOrCallable;
            }
        }

        return $quotedColumns;
    }

    /**
     * Select all fields associated with model.
     *
     * @param iterable|null $columns
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @throws DBALException
     */
    public function selectModelColumns(iterable $columns = null): self
    {
        if ($columns !== null) {
            $quotedColumns = [];
            foreach ($columns as $column) {
                $quotedColumns[] = $this->quoteDoubleIdentifier($this->getAlias(), $column);
            }
        } else {
            $quotedColumns = $this->getModelColumns();
        }

        $this->select($quotedColumns);

        return $this;
    }

    /**
     * @return self
     *
     * @throws DBALException
     */
    public function distinct(): self
    {
        // emulate SELECT DISTINCT with grouping by primary key
        $primaryColumn = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $this->addGroupBy($this->getQuotedMainAliasColumn($primaryColumn));

        return $this;
    }

    /**
     * @param Closure $columnMapper
     *
     * @return self
     */
    public function setColumnToDatabaseMapper(Closure $columnMapper): self
    {
        $this->columnMapper = $columnMapper;

        return $this;
    }

    /**
     * @return self
     *
     * @throws DBALException
     */
    public function fromModelTable(): self
    {
        $this->from(
            $this->quoteSingleIdentifier($this->getTableName()),
            $this->quoteSingleIdentifier($this->getAlias())
        );

        return $this;
    }

    /**
     * @param iterable $attributes
     *
     * @return self
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createModel(iterable $attributes): self
    {
        $this->insert($this->quoteSingleIdentifier($this->getTableName()));

        $valuesAsParams = [];
        foreach ($this->bindAttributes($this->getModelClass(), $attributes) as $quotedColumn => $parameterName) {
            $valuesAsParams[$quotedColumn] = $parameterName;
        }
        $this->values($valuesAsParams);

        return $this;
    }

    /**
     * @param iterable $attributes
     *
     * @return self
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function updateModels(iterable $attributes): self
    {
        $this->update($this->quoteSingleIdentifier($this->getTableName()));

        foreach ($this->bindAttributes($this->getModelClass(), $attributes) as $quotedColumn => $parameterName) {
            $this->set($quotedColumn, $parameterName);
        }

        return $this;
    }

    /**
     * @param string   $modelClass
     * @param iterable $attributes
     *
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws DBALException
     */
    public function bindAttributes(string $modelClass, iterable $attributes): iterable
    {
        $dbPlatform = $this->getConnection()->getDatabasePlatform();
        $types      = $this->getModelSchemas()->getAttributeTypes($modelClass);

        foreach ($attributes as $column => $value) {
            assert(is_string($column) && $this->getModelSchemas()->hasAttributeType($this->getModelClass(), $column));

            $quotedColumn  = $this->quoteSingleIdentifier($column);
            $type          = $this->getDbalType($types[$column]);
            $pdoValue      = $type->convertToDatabaseValue($value, $dbPlatform);
            $parameterName = $this->createNamedParameter($pdoValue, $type->getBindingType());

            yield $quotedColumn => $parameterName;
        }
    }

    /**
     * @return self
     *
     * @throws DBALException
     */
    public function deleteModels(): self
    {
        $this->delete($this->quoteSingleIdentifier($this->getTableName()));

        return $this;
    }

    /**
     * @param string $relationshipName
     * @param string $identity
     * @param string $secondaryIdBindName
     *
     * @return self
     *
     * @throws DBALException
     */
    public function prepareCreateInToManyRelationship(
        string $relationshipName,
        string $identity,
        string $secondaryIdBindName
    ): self {
        list ($intermediateTable, $primaryKey, $secondaryKey) =
            $this->getModelSchemas()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);

        $this
            ->insert($this->quoteSingleIdentifier($intermediateTable))
            ->values([
                $this->quoteSingleIdentifier($primaryKey)   => $this->createNamedParameter($identity),
                $this->quoteSingleIdentifier($secondaryKey) => $secondaryIdBindName,
            ]);

        return $this;
    }

    /**
     * @param string   $relationshipName
     * @param string   $identity
     * @param iterable $secondaryIds
     *
     * @return ModelQueryBuilder
     *
     * @throws DBALException
     */
    public function prepareDeleteInToManyRelationship(
        string $relationshipName,
        string $identity,
        iterable $secondaryIds
    ): self {
        list ($intermediateTable, $primaryKey, $secondaryKey) =
            $this->getModelSchemas()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);

        $filters = [
            $primaryKey   => [FilterParameterInterface::OPERATION_EQUALS => [$identity]],
            $secondaryKey => [FilterParameterInterface::OPERATION_IN     => $secondaryIds],
        ];

        $addWith = $this->expr()->andX();
        $this
            ->delete($this->quoteSingleIdentifier($intermediateTable))
            ->applyFilters($addWith, $intermediateTable, $filters);

        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param string $relationshipName
     * @param string $identity
     *
     * @return self
     *
     * @throws DBALException
     */
    public function clearToManyRelationship(string $relationshipName, string $identity): self
    {
        list ($intermediateTable, $primaryKey) =
            $this->getModelSchemas()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);

        $filters = [$primaryKey => [FilterParameterInterface::OPERATION_EQUALS => [$identity]]];
        $addWith = $this->expr()->andX();
        $this
            ->delete($this->quoteSingleIdentifier($intermediateTable))
            ->applyFilters($addWith, $intermediateTable, $filters);

        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param iterable $filters
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addFiltersWithAndToTable(iterable $filters): self
    {
        $addWith = $this->expr()->andX();
        $this->applyFilters($addWith, $this->getTableName(), $filters);
        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param iterable $filters
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addFiltersWithOrToTable(iterable $filters): self
    {
        $addWith = $this->expr()->orX();
        $this->applyFilters($addWith, $this->getTableName(), $filters);
        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param iterable $filters
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addFiltersWithAndToAlias(iterable $filters): self
    {
        $addWith = $this->expr()->andX();
        $this->applyFilters($addWith, $this->getAlias(), $filters);
        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param iterable $filters
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addFiltersWithOrToAlias(iterable $filters): self
    {
        $addWith = $this->expr()->orX();
        $this->applyFilters($addWith, $this->getAlias(), $filters);
        $addWith->count() <= 0 ?: $this->andWhere($addWith);

        return $this;
    }

    /**
     * @param string        $relationshipName
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     * @param int           $joinIndividuals
     * @param int           $joinRelationship
     *
     * @return self
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addRelationshipFiltersAndSorts(
        string $relationshipName,
        ?iterable $relationshipFilters,
        ?iterable $relationshipSorts,
        int $joinIndividuals = self::AND,
        int $joinRelationship = self::AND
    ): self {
        $targetAlias = null;

        if ($relationshipFilters !== null) {
            $isBelongsTo = $this->getModelSchemas()
                    ->getRelationshipType($this->getModelClass(), $relationshipName) === RelationshipTypes::BELONGS_TO;

            // it will have non-null value only in a `belongsTo` relationship
            $reversePk = $isBelongsTo === true ?
                $this->getModelSchemas()->getReversePrimaryKey($this->getModelClass(), $relationshipName)[0] : null;

            $addWith = $joinIndividuals === self::AND ? $this->expr()->andX() : $this->expr()->orX();

            foreach ($relationshipFilters as $columnName => $operationsWithArgs) {
                if ($columnName === $reversePk) {
                    // We are applying a filter to a primary key in `belongsTo` relationship
                    // It could be replaced with a filter to a value in main table. Why might we need it?
                    // Filter could be 'IS NULL' so joining a table will not work because there are no
                    // related records with 'NULL` key. For plain values it will produce shorter SQL.
                    $fkName         =
                        $this->getModelSchemas()->getForeignKey($this->getModelClass(), $relationshipName);
                    $fullColumnName = $this->getQuotedMainAliasColumn($fkName);
                } else {
                    // Will apply filters to a joined table.
                    $targetAlias    = $targetAlias ?: $this->createRelationshipAlias($relationshipName);
                    $fullColumnName = $this->quoteDoubleIdentifier($targetAlias, $columnName);
                }

                foreach ($operationsWithArgs as $operation => $arguments) {
                    assert(
                        is_iterable($arguments) === true || is_array($arguments) === true,
                        "Operation arguments are missing for `$columnName` column. " .
                        'Use an empty array as an empty argument list.'
                    );
                    $addWith->add($this->createFilterExpression($fullColumnName, $operation, $arguments));
                }

                if ($addWith->count() > 0) {
                    $joinRelationship === self::AND ? $this->andWhere($addWith) : $this->orWhere($addWith);
                }
            }
        }

        if ($relationshipSorts !== null) {
            foreach ($relationshipSorts as $columnName => $isAsc) {
                // we join the table only once and only if we have at least one 'sort' or non-belongsToPK filter.
                $targetAlias = $targetAlias ?: $this->createRelationshipAlias($relationshipName);

                assert(is_string($columnName) === true && is_bool($isAsc) === true);
                $fullColumnName = $this->quoteDoubleIdentifier($targetAlias, $columnName);
                $this->addOrderBy($fullColumnName, $isAsc === true ? 'ASC' : 'DESC');
            }
        }

        return $this;
    }

    /**
     * @param iterable $sortParameters
     *
     * @return self
     *
     * @throws DBALException
     */
    public function addSorts(iterable $sortParameters): self
    {
        return $this->applySorts($this->getAlias(), $sortParameters);
    }

    /**
     * @param string $column
     *
     * @return string
     *
     * @throws DBALException
     */
    public function getQuotedMainTableColumn(string $column): string
    {
        return $this->quoteDoubleIdentifier($this->getTableName(), $column);
    }

    /**
     * @param string $column
     *
     * @return string
     *
     * @throws DBALException
     */
    public function getQuotedMainAliasColumn(string $column): string
    {
        return $this->quoteDoubleIdentifier($this->getAlias(), $column);
    }

    /**
     * @param string $name
     *
     * @return string Table alias.
     *
     * @throws DBALException
     */
    public function createRelationshipAlias(string $name): string
    {
        $relationshipType = $this->getModelSchemas()->getRelationshipType($this->getModelClass(), $name);
        switch ($relationshipType) {
            case RelationshipTypes::BELONGS_TO:
                list($targetColumn, $targetTable) =
                    $this->getModelSchemas()->getReversePrimaryKey($this->getModelClass(), $name);
                $targetAlias = $this->innerJoinOneTable(
                    $this->getAlias(),
                    $this->getModelSchemas()->getForeignKey($this->getModelClass(), $name),
                    $targetTable,
                    $targetColumn
                );
                break;

            case RelationshipTypes::HAS_MANY:
                list($targetColumn, $targetTable) =
                    $this->getModelSchemas()->getReverseForeignKey($this->getModelClass(), $name);
                $targetAlias = $this->innerJoinOneTable(
                    $this->getAlias(),
                    $this->getModelSchemas()->getPrimaryKey($this->getModelClass()),
                    $targetTable,
                    $targetColumn
                );
                break;

            case RelationshipTypes::BELONGS_TO_MANY:
            default:
                assert($relationshipType === RelationshipTypes::BELONGS_TO_MANY);
                $primaryKey = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
                list ($intermediateTable, $intermediatePk, $intermediateFk) =
                    $this->getModelSchemas()->getBelongsToManyRelationship($this->getModelClass(), $name);
                list($targetPrimaryKey, $targetTable) =
                    $this->getModelSchemas()->getReversePrimaryKey($this->getModelClass(), $name);

                $targetAlias = $this->innerJoinTwoSequentialTables(
                    $this->getAlias(),
                    $primaryKey,
                    $intermediateTable,
                    $intermediatePk,
                    $intermediateFk,
                    $targetTable,
                    $targetPrimaryKey
                );
                break;
        }

        return $targetAlias;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->mainAlias;
    }

    /**
     * @param CompositeExpression $expression
     * @param string              $tableOrAlias
     * @param iterable            $filters
     *
     * @return self
     *
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function applyFilters(CompositeExpression $expression, string $tableOrAlias, iterable $filters): self
    {
        foreach ($filters as $columnName => $operationsWithArgs) {
            assert(
                is_string($columnName) === true && empty($columnName) === false,
                "Haven't you forgotten to specify a column name in a relationship that joins `$tableOrAlias` table?"
            );
            $fullColumnName = $this->quoteDoubleIdentifier($tableOrAlias, $columnName);
            foreach ($operationsWithArgs as $operation => $arguments) {
                assert(
                    is_iterable($arguments) === true || is_array($arguments) === true,
                    "Operation arguments are missing for `$columnName` column. " .
                    'Use an empty array as an empty argument list.'
                );
                $expression->add($this->createFilterExpression($fullColumnName, $operation, $arguments));
            }
        }

        return $this;
    }

    /**
     * @param string   $tableOrAlias
     * @param iterable $sorts
     *
     * @return self
     *
     * @throws DBALException
     */
    public function applySorts(string $tableOrAlias, iterable $sorts): self
    {
        foreach ($sorts as $columnName => $isAsc) {
            assert(is_string($columnName) === true && is_bool($isAsc) === true);
            $fullColumnName = $this->quoteDoubleIdentifier($tableOrAlias, $columnName);
            $this->addOrderBy($fullColumnName, $isAsc === true ? 'ASC' : 'DESC');
        }

        return $this;
    }

    /**
     * @param string $tableOrColumn
     *
     * @return string
     *
     * @throws DBALException
     */
    public function quoteSingleIdentifier(string $tableOrColumn): string
    {
        return $this->getConnection()->getDatabasePlatform()->quoteSingleIdentifier($tableOrColumn);
    }

    /**
     * @param string $tableOrAlias
     * @param string $column
     *
     * @return string
     *
     * @throws DBALException
     */
    public function quoteDoubleIdentifier(string $tableOrAlias, string $column): string
    {
        $platform = $this->getConnection()->getDatabasePlatform();

        return $platform->quoteSingleIdentifier($tableOrAlias) . '.' . $platform->quoteSingleIdentifier($column);
    }

    /**
     * @param $value
     *
     * @return string
     *
     * @throws DBALException
     */
    public function createSingleValueNamedParameter($value): string
    {
        $paramName = $this->createNamedParameter($this->getPdoValue($value), $this->getPdoType($value));

        return $paramName;
    }

    /**
     * @param iterable $values
     *
     * @return array
     *
     * @throws DBALException
     */
    public function createArrayValuesNamedParameter(iterable $values): array
    {
        $names = [];

        foreach ($values as $value) {
            $names[] = $this->createSingleValueNamedParameter($value);
        }

        return $names;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function createAlias(string $tableName): string
    {
        $alias                          = $tableName . (++$this->aliasIdCounter);
        $this->knownAliases[$tableName] = $alias;

        return $alias;
    }

    /**
     * @param string $fromAlias
     * @param string $fromColumn
     * @param string $targetTable
     * @param string $targetColumn
     *
     * @return string
     *
     * @throws DBALException
     */
    public function innerJoinOneTable(
        string $fromAlias,
        string $fromColumn,
        string $targetTable,
        string $targetColumn
    ): string {
        $targetAlias   = $this->createAlias($targetTable);
        $joinCondition = $this->quoteDoubleIdentifier($fromAlias, $fromColumn) . '=' .
            $this->quoteDoubleIdentifier($targetAlias, $targetColumn);

        $this->innerJoin(
            $this->quoteSingleIdentifier($fromAlias),
            $this->quoteSingleIdentifier($targetTable),
            $this->quoteSingleIdentifier($targetAlias),
            $joinCondition
        );

        return $targetAlias;
    }

    /**
     * @param string $fromAlias
     * @param string $fromColumn
     * @param string $intTable
     * @param string $intToFromColumn
     * @param string $intToTargetColumn
     * @param string $targetTable
     * @param string $targetColumn
     *
     * @return string
     *
     * @throws DBALException
     */
    public function innerJoinTwoSequentialTables(
        string $fromAlias,
        string $fromColumn,
        string $intTable,
        string $intToFromColumn,
        string $intToTargetColumn,
        string $targetTable,
        string $targetColumn
    ): string {
        $intAlias    = $this->innerJoinOneTable($fromAlias, $fromColumn, $intTable, $intToFromColumn);
        $targetAlias = $this->innerJoinOneTable($intAlias, $intToTargetColumn, $targetTable, $targetColumn);

        return $targetAlias;
    }

    /**
     * @param string $name
     *
     * @return Type
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getDbalType(string $name): Type
    {
        assert(Type::hasType($name), "Type `$name` either do not exist or registered.");
        $type = Type::getType($name);

        return $type;
    }

    /**
     * @return string
     */
    private function getTableName(): string
    {
        return $this->mainTableName;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    private function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @param string   $fullColumnName
     * @param int      $operation
     * @param iterable $arguments
     *
     * @return string
     *
     * @throws DBALException
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function createFilterExpression(string $fullColumnName, int $operation, iterable $arguments): string
    {
        switch ($operation) {
            case FilterParameterInterface::OPERATION_EQUALS:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->eq($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_NOT_EQUALS:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->neq($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_LESS_THAN:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->lt($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_LESS_OR_EQUALS:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->lte($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_GREATER_THAN:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->gt($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_GREATER_OR_EQUALS:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->gte($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_LIKE:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->like($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_NOT_LIKE:
                $parameter  = $this->createSingleValueNamedParameter($this->firstValue($arguments));
                $expression = $this->expr()->notLike($fullColumnName, $parameter);
                break;
            case FilterParameterInterface::OPERATION_IN:
                $parameters = $this->createArrayValuesNamedParameter($arguments);
                $expression = $this->expr()->in($fullColumnName, $parameters);
                break;
            case FilterParameterInterface::OPERATION_NOT_IN:
                $parameters = $this->createArrayValuesNamedParameter($arguments);
                $expression = $this->expr()->notIn($fullColumnName, $parameters);
                break;
            case FilterParameterInterface::OPERATION_IS_NULL:
                $expression = $this->expr()->isNull($fullColumnName);
                break;
            case FilterParameterInterface::OPERATION_IS_NOT_NULL:
            default:
                assert($operation === FilterParameterInterface::OPERATION_IS_NOT_NULL);
                $expression = $this->expr()->isNotNull($fullColumnName);
                break;
        }

        return $expression;
    }

    /**
     * @param iterable $arguments
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    private function firstValue(iterable $arguments)
    {
        foreach ($arguments as $argument) {
            return $argument;
        }

        // arguments are empty
        throw new InvalidArgumentException();
    }

    /**
     * @return Closure
     */
    private function getColumnToDatabaseMapper(): Closure
    {
        return $this->columnMapper;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws DBALException
     */
    private function getPdoValue($value)
    {
        return $value instanceof DateTimeInterface ? $this->convertDataTimeToDatabaseFormat($value) : $value;
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return string
     *
     * @throws DBALException
     */
    private function convertDataTimeToDatabaseFormat(DateTimeInterface $dateTime): string
    {
        return $this->getDateTimeType()->convertToDatabaseValue(
            $dateTime,
            $this->getConnection()->getDatabasePlatform()
        );
    }

    /**
     * @param mixed $value
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getPdoType($value): int
    {
        if (is_int($value) === true) {
            $type = PDO::PARAM_INT;
        } elseif (is_bool($value)) {
            $type = PDO::PARAM_BOOL;
        } elseif ($value instanceof DateTimeInterface) {
            $type = PDO::PARAM_STR;
        } else {
            assert(
                $value !== null,
                'It seems you are trying to use `null` with =, >, <, or etc operator. ' .
                'Use `is null` or `not null` instead.'
            );
            assert(is_string($value), "Only strings, booleans and integers are supported.");
            $type = PDO::PARAM_STR;
        }

        return $type;
    }

    /**
     * @return Type
     *
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getDateTimeType(): Type
    {
        if ($this->dateTimeType === null) {
            $this->dateTimeType = Type::getType(DateTimeType::DATETIME);
        }

        return $this->dateTimeType;
    }
}

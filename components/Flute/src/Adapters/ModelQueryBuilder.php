<?php namespace Limoncello\Flute\Adapters;

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
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\Type;
use Generator;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use PDO;

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
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @var int
     */
    private $aliasIdCounter = 0;

    /**
     * @var array
     */
    private $knownAliases = [];

    /**
     * @var null|Closure
     */
    private $dtToDbConverter = null;

    /**
     * @param Connection               $connection
     * @param string                   $modelClass
     * @param ModelSchemeInfoInterface $modelSchemes
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(Connection $connection, string $modelClass, ModelSchemeInfoInterface $modelSchemes)
    {
        assert(!empty($modelClass));

        parent::__construct($connection);

        $this->modelSchemes = $modelSchemes;
        $this->modelClass   = $modelClass;

        $this->mainTableName = $this->getModelSchemes()->getTable($this->getModelClass());
        $this->mainAlias     = $this->createAlias($this->getMainTableName());

        $this->setColumnToDatabaseMapper(Closure::fromCallable([$this, 'getQuotedMainAliasColumn']));
    }

    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Select all fields associated with model.
     *
     * @param iterable|null $columns
     *
     * @return self
     */
    public function selectModelColumns(iterable $columns = null): self
    {
        $selectedColumns =
            $columns === null ? $this->getModelSchemes()->getAttributes($this->getModelClass()) : $columns;

        $quotedColumns = [];
        $columnMapper  = $this->getColumnToDatabaseMapper();
        foreach ($selectedColumns as $column) {
            $quotedColumns[] = call_user_func($columnMapper, $column, $this);
        }

        $this->select($quotedColumns);

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
     */
    public function fromModelTable(): self
    {
        $this->from(
            $this->quoteTableName($this->getMainTableName()),
            $this->quoteTableName($this->getMainAlias())
        );

        return $this;
    }

    /**
     * @param iterable $attributes
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createModel(iterable $attributes): self
    {
        $this->insert($this->quoteTableName($this->getMainTableName()));

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
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function updateModels(iterable $attributes): self
    {
        $this->update($this->quoteTableName($this->getMainTableName()));

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
     */
    public function bindAttributes(string $modelClass, iterable $attributes): iterable
    {
        $dbPlatform = $this->getConnection()->getDatabasePlatform();
        $types      = $this->getModelSchemes()->getAttributeTypes($modelClass);

        foreach ($attributes as $column => $value) {
            assert(is_string($column) && $this->getModelSchemes()->hasAttributeType($this->getModelClass(), $column));

            $quotedColumn  = $this->quoteColumnName($column);

            $type          = Type::getType($types[$column]);
            $pdoValue      = $type->convertToDatabaseValue($value, $dbPlatform);
            $parameterName = $this->createNamedParameter($pdoValue, $type->getBindingType());

            yield $quotedColumn => $parameterName;
        }
    }

    /**
     * @return self
     */
    public function deleteModels(): self
    {
        $this->delete($this->quoteTableName($this->getMainTableName()));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function prepareCreateInToManyRelationship(
        string $relationshipName,
        string $identity,
        string $secondaryIdBindName
    ): self {
        list ($intermediateTable, $primaryKey, $secondaryKey) =
            $this->getModelSchemes()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);

        $this
            ->insert($this->quoteTableName($intermediateTable))
            ->values([
                $this->quoteColumnName($primaryKey)   => $this->createNamedParameter($identity),
                $this->quoteColumnName($secondaryKey) => $secondaryIdBindName,
            ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearToManyRelationship(string $relationshipName, string $identity): self
    {
        list ($intermediateTable, $primaryKey) =
            $this->getModelSchemes()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);

        $filters = [$primaryKey => [FilterParameterInterface::OPERATION_EQUALS => [$identity]]];
        $this
            ->delete($this->quoteTableName($intermediateTable))
            ->addFilters($intermediateTable, $this->expr()->andX(), $filters);

        return $this;
    }

    /**
     * @param iterable $filters
     *
     * @return self
     */
    public function addFiltersWithAndToTable(iterable $filters): self
    {
        return $this->addFilters($this->getMainTableName(), $this->expr()->andX(), $filters);
    }

    /**
     * @param iterable $filters
     *
     * @return self
     */
    public function addFiltersWithOrToTable(iterable $filters): self
    {
        return $this->addFilters($this->getMainTableName(), $this->expr()->orX(), $filters);
    }

    /**
     * @param iterable $filters
     *
     * @return self
     */
    public function addFiltersWithAndToAlias(iterable $filters): self
    {
        return $this->addFilters($this->getMainAlias(), $this->expr()->andX(), $filters);
    }

    /**
     * @param iterable $filters
     *
     * @return self
     */
    public function addFiltersWithOrToAlias(iterable $filters): self
    {
        return $this->addFilters($this->getMainAlias(), $this->expr()->orX(), $filters);
    }

    /**
     * @param string        $relationshipName
     * @param iterable      $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return self
     */
    public function addRelationshipFiltersAndSortsWithAnd(
        string $relationshipName,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $joinWith = $this->expr()->andX();

        return $this->addRelationshipFiltersAndSorts(
            $relationshipName,
            $joinWith,
            $relationshipFilters,
            $relationshipSorts
        );
    }

    /**
     * @return self
     */
    public function distinct(): self
    {
        // emulate SELECT DISTINCT with grouping by primary key
        $primaryColumn = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        $this->addGroupBy($this->getQuotedMainAliasColumn($primaryColumn));

        return $this;
    }

    /**
     * @param string        $relationshipName
     * @param iterable      $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return self
     */
    public function addRelationshipFiltersAndSortsWithOr(
        string $relationshipName,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $joinWith = $this->expr()->orX();

        return $this->addRelationshipFiltersAndSorts(
            $relationshipName,
            $joinWith,
            $relationshipFilters,
            $relationshipSorts
        );
    }

    /**
     * @param iterable $sortParameters
     *
     * @return self
     */
    public function addSorts(iterable $sortParameters): self
    {
        foreach ($sortParameters as $columnName => $isAsc) {
            assert(is_string($columnName) === true && is_bool($isAsc) === true);
            $fullColumnName = $this->getQuotedMainAliasColumn($columnName);
            assert($this->getModelSchemes()->hasAttributeType($this->getModelClass(), $columnName));
            $this->addOrderBy($fullColumnName, $isAsc === true ? 'ASC' : 'DESC');
        }

        return $this;
    }

    /**
     * @param string              $tableOrAlias
     * @param CompositeExpression $filterLink
     * @param iterable            $filters
     *
     * @return self
     */
    private function addFilters(string $tableOrAlias, CompositeExpression $filterLink, iterable $filters): self
    {
        foreach ($filters as $columnName => $operationsWithArgs) {
            $fullColumnName = $this->buildColumnName($tableOrAlias, $columnName);
            $this->applyFilter($filterLink, $fullColumnName, $operationsWithArgs);
        }
        if ($filterLink->count() > 0) {
            $this->andWhere($filterLink);
        }

        return $this;
    }

    /**
     * @param string              $relationshipName
     * @param CompositeExpression $filterLink
     * @param iterable            $relationshipFilters
     * @param iterable|null       $relationshipSorts
     *
     * @return self
     */
    private function addRelationshipFiltersAndSorts(
        string $relationshipName,
        CompositeExpression $filterLink,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $relationshipType = $this->getModelSchemes()->getRelationshipType($this->getModelClass(), $relationshipName);
        switch ($relationshipType) {
            case RelationshipTypes::BELONGS_TO:
                $builder = $this->addBelongsToFiltersAndSorts(
                    $relationshipName,
                    $filterLink,
                    $relationshipFilters,
                    $relationshipSorts
                );
                break;

            case RelationshipTypes::HAS_MANY:
                $builder = $this->addHasManyFiltersAndSorts(
                    $relationshipName,
                    $filterLink,
                    $relationshipFilters,
                    $relationshipSorts
                );
                break;

            case RelationshipTypes::BELONGS_TO_MANY:
            default:
                assert($relationshipType === RelationshipTypes::BELONGS_TO_MANY);
                $builder = $this->addBelongsToManyFiltersAndSorts(
                    $relationshipName,
                    $filterLink,
                    $relationshipFilters,
                    $relationshipSorts
                );
                break;
        }

        return $builder;
    }

    /**
     * @return string
     */
    private function getMainTableName(): string
    {
        return $this->mainTableName;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    private function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->modelSchemes;
    }

    /**
     * @return string
     */
    private function getMainAlias(): string
    {
        return $this->mainAlias;
    }

    /**
     * @param string              $relationshipName
     * @param CompositeExpression $filterLink
     * @param iterable            $relationshipFilters
     * @param iterable|null       $relationshipSorts
     *
     * @return self
     */
    private function addBelongsToFiltersAndSorts(
        string $relationshipName,
        CompositeExpression $filterLink,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $foreignKey = $this->getModelSchemes()->getForeignKey($this->getModelClass(), $relationshipName);
        list($onePrimaryKey, $oneTable) =
            $this->getModelSchemes()->getReversePrimaryKey($this->getModelClass(), $relationshipName);

        $this->innerJoinOneTable(
            $this->getMainAlias(),
            $foreignKey,
            $oneTable,
            $onePrimaryKey,
            $filterLink,
            $relationshipFilters,
            $relationshipSorts
        );
        if ($filterLink->count() > 0) {
            $this->andWhere($filterLink);
        }

        return $this;
    }

    /**
     * @param string              $relationshipName
     * @param CompositeExpression $filterLink
     * @param iterable            $relationshipFilters
     * @param iterable|null       $relationshipSorts
     *
     * @return self
     */
    private function addHasManyFiltersAndSorts(
        string $relationshipName,
        CompositeExpression $filterLink,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $primaryKey = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        list($manyForeignKey, $manyTable) =
            $this->getModelSchemes()->getReverseForeignKey($this->getModelClass(), $relationshipName);

        $this->innerJoinOneTable(
            $this->getMainAlias(),
            $primaryKey,
            $manyTable,
            $manyForeignKey,
            $filterLink,
            $relationshipFilters,
            $relationshipSorts
        );
        if ($filterLink->count() > 0) {
            $this->andWhere($filterLink);
        }

        return $this;
    }

    /**
     * @param string              $relationshipName
     * @param CompositeExpression $targetFilterLink
     * @param iterable            $relationshipFilters
     * @param iterable|null       $relationshipSorts
     *
     * @return self
     */
    private function addBelongsToManyFiltersAndSorts(
        string $relationshipName,
        CompositeExpression $targetFilterLink,
        iterable $relationshipFilters,
        ?iterable $relationshipSorts
    ): self {
        $primaryKey = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        list ($intermediateTable, $intermediatePk, $intermediateFk) =
            $this->getModelSchemes()->getBelongsToManyRelationship($this->getModelClass(), $relationshipName);
        list($targetPrimaryKey, $targetTable) =
            $this->getModelSchemes()->getReversePrimaryKey($this->getModelClass(), $relationshipName);

        // no filters for intermediate table
        $intFilterLink = null;
        $intFilters    = null;
        $this->innerJoinTwoSequentialTables(
            $this->getMainAlias(),
            $primaryKey,
            $intermediateTable,
            $intermediatePk,
            $intermediateFk,
            $targetTable,
            $targetPrimaryKey,
            $intFilterLink,
            $intFilters,
            $targetFilterLink,
            $relationshipFilters,
            $relationshipSorts
        );
        if ($targetFilterLink->count() > 0) {
            $this->andWhere($targetFilterLink);
        }

        return $this;
    }

    /**
     * @param string                   $fromAlias
     * @param string                   $fromColumn
     * @param string                   $targetTable
     * @param string                   $targetColumn
     * @param CompositeExpression|null $targetFilterLink
     * @param iterable|null            $targetFilterParams
     * @param iterable|null            $relationshipSorts
     *
     * @return string
     */
    private function innerJoinOneTable(
        string $fromAlias,
        string $fromColumn,
        string $targetTable,
        string $targetColumn,
        ?CompositeExpression $targetFilterLink,
        ?iterable $targetFilterParams,
        ?iterable $relationshipSorts
    ): string {
        $targetAlias   = $this->createAlias($targetTable);
        $joinCondition = $this->buildColumnName($fromAlias, $fromColumn) . '=' .
            $this->buildColumnName($targetAlias, $targetColumn);

        $this->innerJoin(
            $this->quoteTableName($fromAlias),
            $this->quoteTableName($targetTable),
            $this->quoteTableName($targetAlias),
            $joinCondition
        );

        if ($targetFilterLink !== null && $targetFilterParams !== null) {
            foreach ($targetFilterParams as $columnName => $operationsWithArgs) {
                assert(is_string($columnName) === true);
                $fullColumnName = $this->buildColumnName($targetAlias, $columnName);
                $this->applyFilter($targetFilterLink, $fullColumnName, $operationsWithArgs);
            }
        }
        if ($relationshipSorts !== null) {
            foreach ($relationshipSorts as $columnName => $isAsc) {
                assert(is_string($columnName) === true && is_bool($isAsc) === true);
                $fullColumnName = $this->buildColumnName($targetAlias, $columnName);
                $this->addOrderBy($fullColumnName, $isAsc === true ? 'ASC' : 'DESC');
            }
        }

        return $targetAlias;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                   $fromAlias
     * @param string                   $fromColumn
     * @param string                   $intTable
     * @param string                   $intToFromColumn
     * @param string                   $intToTargetColumn
     * @param string                   $targetTable
     * @param string                   $targetColumn
     * @param CompositeExpression|null $intFilterLink
     * @param iterable|null            $intFilterParams
     * @param CompositeExpression|null $targetFilterLink
     * @param iterable|null            $targetFilterParams
     * @param iterable|null            $targetSortParams
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function innerJoinTwoSequentialTables(
        string $fromAlias,
        string $fromColumn,
        string $intTable,
        string $intToFromColumn,
        string $intToTargetColumn,
        string $targetTable,
        string $targetColumn,
        ?CompositeExpression $intFilterLink,
        ?iterable $intFilterParams,
        ?CompositeExpression $targetFilterLink,
        ?iterable $targetFilterParams,
        ?iterable $targetSortParams
    ): string {
        $intNoSorting = null;
        $intAlias     = $this->innerJoinOneTable(
            $fromAlias,
            $fromColumn,
            $intTable,
            $intToFromColumn,
            $intFilterLink,
            $intFilterParams,
            $intNoSorting
        );
        $targetAlias  = $this->innerJoinOneTable(
            $intAlias,
            $intToTargetColumn,
            $targetTable,
            $targetColumn,
            $targetFilterLink,
            $targetFilterParams,
            $targetSortParams
        );

        return $targetAlias;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    private function createAlias(string $tableName): string
    {
        $alias                          = $tableName . (++$this->aliasIdCounter);
        $this->knownAliases[$tableName] = $alias;

        return $alias;
    }

    /**
     * @inheritdoc
     */
    private function quoteTableName(string $tableName): string
    {
        return "`$tableName`";
    }

    /**
     * @inheritdoc
     */
    private function quoteColumnName(string $columnName): string
    {
        return "`$columnName`";
    }

    /**
     * @inheritdoc
     */
    private function buildColumnName(string $table, string $column): string
    {
        return "`$table`.`$column`";
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getQuotedMainTableColumn(string $column): string
    {
        return $this->buildColumnName($this->getMainTableName(), $column);
    }

    /**
     * @param string $column
     *
     * @return string
     */
    public function getQuotedMainAliasColumn(string $column): string
    {
        return $this->buildColumnName($this->getMainAlias(), $column);
    }

    /**
     * @param CompositeExpression $filterLink
     * @param string              $fullColumnName
     * @param iterable            $operationsWithArgs
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function applyFilter(
        CompositeExpression $filterLink,
        string $fullColumnName,
        iterable $operationsWithArgs
    ): void {
        foreach ($operationsWithArgs as $operation => $arguments) {
            assert(is_int($operation));
            assert(
                is_array($arguments) || $arguments instanceof Generator,
                "Filter argument(s) for $fullColumnName must be iterable (an array or Generator)."
            );
            switch ($operation) {
                case FilterParameterInterface::OPERATION_EQUALS:
                    $expression = $this->expr()->eq($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_NOT_EQUALS:
                    $expression = $this->expr()->neq($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_LESS_THAN:
                    $expression = $this->expr()->lt($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_LESS_OR_EQUALS:
                    $expression = $this->expr()->lte($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_GREATER_THAN:
                    $expression = $this->expr()->gt($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_GREATER_OR_EQUALS:
                    $expression = $this->expr()->gte($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_LIKE:
                    $expression = $this->expr()->like($fullColumnName, $this->createSingleNamedParameter($arguments));
                    break;
                case FilterParameterInterface::OPERATION_NOT_LIKE:
                    $parameter  = $this->createSingleNamedParameter($arguments);
                    $expression = $this->expr()->notLike($fullColumnName, $parameter);
                    break;
                case FilterParameterInterface::OPERATION_IN:
                    $expression = $this->expr()->in($fullColumnName, $this->createNamedParameterArray($arguments));
                    break;
                case FilterParameterInterface::OPERATION_NOT_IN:
                    $expression = $this->expr()->notIn($fullColumnName, $this->createNamedParameterArray($arguments));
                    break;
                case FilterParameterInterface::OPERATION_IS_NULL:
                    $expression = $this->expr()->isNull($fullColumnName);
                    break;
                case FilterParameterInterface::OPERATION_IS_NOT_NULL:
                default:
                    $expression = $this->expr()->isNotNull($fullColumnName);
                    break;
            }

            $filterLink->add($expression);
        }
    }

    /**
     * @param iterable $arguments
     *
     * @return string
     */
    private function createSingleNamedParameter(iterable $arguments): string
    {
        foreach ($arguments as $argument) {
            $paramName = $this->createNamedParameter($this->getPdoValue($argument), $this->getPdoType($argument));

            return $paramName;
        }

        // arguments are empty
        throw new InvalidArgumentException();
    }

    /**
     * @param iterable $arguments
     *
     * @return string[]
     */
    private function createNamedParameterArray(iterable $arguments): array
    {
        $names = [];

        foreach ($arguments as $argument) {
            $names[] = $this->createNamedParameter($this->getPdoValue($argument), $this->getPdoType($argument));
        }

        return $names;
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
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function convertDataTimeToDatabaseFormat(DateTimeInterface $dateTime): string
    {
        if ($this->dtToDbConverter === null) {
            $type     = Type::getType(DateTimeType::DATETIME);
            $platform = $this->getConnection()->getDatabasePlatform();
            $this->dtToDbConverter = function (DateTimeInterface $dateTime) use ($type, $platform) : string {
                return $type->convertToDatabaseValue($dateTime, $platform);
            };
        }

        return call_user_func($this->dtToDbConverter, $dateTime);
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
}

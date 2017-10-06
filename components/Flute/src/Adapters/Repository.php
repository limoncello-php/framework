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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Adapters\FilterOperationsInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Http\Query\SortParameterInterface;
use Limoncello\Flute\Http\Query\FilterParameterCollection;
use Limoncello\Flute\L10n\Messages;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Repository implements RepositoryInterface
{
    /** Filer constant */
    const FILTER_OP_IS_NULL = 'is-null';

    /** Filer constant */
    const FILTER_OP_IS_NOT_NULL = 'not-null';

    /** Default filtering operation */
    const DEFAULT_FILTER_OPERATION = 'in';

    /** Default filtering operation */
    const DEFAULT_FILTER_OPERATION_SINGLE = 'eq';

    /** Default filtering operation */
    const DEFAULT_FILTER_OPERATION_EMPTY = self::FILTER_OP_IS_NULL;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @var FilterOperationsInterface
     */
    private $filterOperations;

    /**
     * @var FormatterInterface
     */
    private $fluteMsgFormatter;

    /**
     * @var int
     */
    private $aliasIdCounter = 0;

    /**
     * @param Connection                $connection
     * @param ModelSchemeInfoInterface  $modelSchemes
     * @param FilterOperationsInterface $filterOperations
     * @param FormatterInterface        $fluteMsgFormatter
     */
    public function __construct(
        Connection $connection,
        ModelSchemeInfoInterface $modelSchemes,
        FilterOperationsInterface $filterOperations,
        FormatterInterface $fluteMsgFormatter
    ) {
        $this->connection        = $connection;
        $this->modelSchemes      = $modelSchemes;
        $this->filterOperations  = $filterOperations;
        $this->fluteMsgFormatter = $fluteMsgFormatter;
    }

    /**
     * @inheritdoc
     */
    public function index(string $modelClass): QueryBuilder
    {
        $builder = $this->getConnection()->createQueryBuilder();
        $table   = $this->buildTableName($this->getTableName($modelClass));
        $builder->select($this->getColumns($modelClass))->from($table);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function count(string $modelClass): QueryBuilder
    {
        $builder = $this->getConnection()->createQueryBuilder();
        $table   = $this->buildTableName($this->getTableName($modelClass));
        $builder->select('COUNT(*)')->from($table);

        return $builder;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function create(string $modelClass, array $attributes): QueryBuilder
    {
        $connection = $this->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $builder    = $connection->createQueryBuilder();
        $types      = $this->getModelSchemes()->getAttributeTypes($modelClass);

        $valuesAsParams = [];
        foreach ($attributes as $column => $value) {
            $type                        = Type::getType($types[$column]);
            $pdoValue                    = $type->convertToDatabaseValue($value, $dbPlatform);
            $valuesAsParams["`$column`"] = $builder->createNamedParameter($pdoValue, $type->getBindingType());
        }

        $tableName = $this->getTableName($modelClass);
        $builder
            ->insert("`$tableName`")
            ->values($valuesAsParams);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function read(string $modelClass, string $indexBind): QueryBuilder
    {
        $builder = $this->getConnection()->createQueryBuilder();
        $table   = $this->getTableName($modelClass);
        $builder->select($this->getColumns($modelClass))->from($this->buildTableName($table));
        $this->addWhereBind($builder, $table, $this->getPrimaryKeyName($modelClass), $indexBind);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function readRelationship(string $modelClass, string $indexBind, string $relationshipName): array
    {
        list($builder, $resultClass, $relationshipType, $table, $column) =
            $this->createRelationshipBuilder($modelClass, $relationshipName);

        $this->addWhereBind($builder, $table, $column, $indexBind);

        return [$builder, $resultClass, $relationshipType];
    }

    /**
     * @inheritdoc
     */
    public function hasInRelationship(
        string $modelClass,
        string $parentIndexBind,
        string $relationshipName,
        string $childIndexBind
    ): array {
        list($builder, $resultClass, $relationshipType, $table, $column) =
            $this->createRelationshipBuilder($modelClass, $relationshipName);

        $this->addWhereBind($builder, $table, $column, $parentIndexBind);

        $childTable = $this->getModelSchemes()->getTable($resultClass);
        $childPk    = $this->getModelSchemes()->getPrimaryKey($resultClass);
        $this->addWhereBind($builder, $childTable, $childPk, $childIndexBind);

        return [$builder, $resultClass, $relationshipType];
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function update(string $modelClass, $index, array $attributes): QueryBuilder
    {
        $connection = $this->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();
        $builder    = $this->getConnection()->createQueryBuilder();
        $types      = $this->getModelSchemes()->getAttributeTypes($modelClass);

        $table = $this->getTableName($modelClass);
        $builder->update("`$table`");

        foreach ($attributes as $column => $value) {
            $type     = Type::getType($types[$column]);
            $pdoValue = $type->convertToDatabaseValue($value, $dbPlatform);
            $builder->set("`$column`", $builder->createNamedParameter($pdoValue, $type->getBindingType()));
        }

        $pkName   = $this->getPrimaryKeyName($modelClass);
        $pkColumn = $this->buildColumnName($table, $pkName);
        $type     = Type::getType($types[$pkName]);
        $pdoValue = $type->convertToDatabaseValue($index, $dbPlatform);
        $builder->where($pkColumn . '=' . $builder->createNamedParameter($pdoValue, $type->getBindingType()));

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $modelClass, string $indexBind): QueryBuilder
    {
        $builder = $this->getConnection()->createQueryBuilder();

        $table = $this->getTableName($modelClass);
        $builder->delete($this->buildTableName($table));
        $this->addWhereBind($builder, $table, $this->getPrimaryKeyName($modelClass), $indexBind);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function createToManyRelationship(
        string $modelClass,
        string $indexBind,
        string $name,
        string $otherIndexBind
    ): QueryBuilder {
        list ($intermediateTable, $foreignKey, $reverseForeignKey) =
            $this->getModelSchemes()->getBelongsToManyRelationship($modelClass, $name);

        $builder = $this->getConnection()->createQueryBuilder();
        $builder
            ->insert("`$intermediateTable`")
            ->values([
                "`$foreignKey`"        => $indexBind,
                "`$reverseForeignKey`" => $otherIndexBind,
            ]);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function cleanToManyRelationship(string $modelClass, string $indexBind, string $name): QueryBuilder
    {
        list ($intermediateTable, $foreignKey) =
            $this->getModelSchemes()->getBelongsToManyRelationship($modelClass, $name);

        $builder = $this->getConnection()->createQueryBuilder();
        $builder
            ->delete("`$intermediateTable`");
        $this->addWhereBind($builder, $intermediateTable, $foreignKey, $indexBind);

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function applySorting(QueryBuilder $builder, string $modelClass, array $sortParams): void
    {
        $table = $this->getTableName($modelClass);
        foreach ($sortParams as $sortParam) {
            assert($sortParam instanceof SortParameterInterface);
            /** @var SortParameterInterface $sortParam */
            $column = null;
            if ($sortParam->isRelationship() === false) {
                $column = $sortParam->getName();
            } elseif ($sortParam->getRelationshipType() === RelationshipTypes::BELONGS_TO) {
                $column = $this->getModelSchemes()->getForeignKey($modelClass, $sortParam->getName());
            }

            if ($column !== null) {
                $builder->addOrderBy(
                    $this->buildColumnName($table, $column),
                    $sortParam->isAscending() === true ? 'ASC' : 'DESC'
                );
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function applyFilters(
        ErrorCollection $errors,
        QueryBuilder $builder,
        string $modelClass,
        FilterParameterCollection $filterParams
    ): void {
        if ($filterParams->count() <= 0) {
            return;
        }

        $whereLink = $filterParams->isWithAnd() === true ? $builder->expr()->andX() : $builder->expr()->orX();

        // while joining tables we select distinct rows. this flag used to apply `distinct` no more than once.
        $hasAppliedDistinct = false;
        $table              = $this->getTableName($modelClass);
        $quotedTable        = $this->buildTableName($table);
        $modelSchemes       = $this->getModelSchemes();
        foreach ($filterParams as $filterParam) {
            /** @var FilterParameterInterface $filterParam */
            $filterValue = $filterParam->getValue();

            // if filter value is not array of 'operation' => parameters (string/array) but
            // just parameters we will apply default operation
            // for example instead of `filter[id][in]=1,2,3,8,9,10` we got `filter[id]=1,2,3,8,9,10`
            if (is_array($filterValue) === false) {
                if (empty($filterValue) === true) {
                    $operation     = static::DEFAULT_FILTER_OPERATION_EMPTY;
                    $filterIndexes = null;
                } else {
                    $filterIndexes = explode(',', $filterValue);
                    $numIndexes    = count($filterIndexes);
                    $operation     = $numIndexes === 1 ?
                        static::DEFAULT_FILTER_OPERATION_SINGLE : static::DEFAULT_FILTER_OPERATION;
                }
                $filterValue = [$operation => $filterIndexes];
            }

            foreach ($filterValue as $operation => $params) {
                $filterTable  = null;
                $filterColumn = null;
                $lcOp         = strtolower((string)$operation);

                if ($filterParam->isForRelationship() === true) {
                    switch ($filterParam->getRelationshipType()) {
                        case RelationshipTypes::BELONGS_TO:
                            if ($filterParam->isForAttributeInRelationship() === true) {
                                $foreignKey = $modelSchemes->getForeignKey(
                                    $modelClass,
                                    $filterParam->getRelationshipName()
                                );
                                list ($reverseClass) = $modelSchemes
                                    ->getReverseRelationship($modelClass, $filterParam->getRelationshipName());
                                $reversePk     = $modelSchemes->getPrimaryKey($reverseClass);
                                $filterTable   = $modelSchemes->getTable($reverseClass);
                                $filterColumn  = $filterParam->getAttributeName();
                                $aliased       = $filterTable . $this->getNewAliasId();
                                $joinCondition = $this->buildColumnName($table, $foreignKey) . '=' .
                                    $this->buildColumnName($aliased, $reversePk);
                                $builder->innerJoin(
                                    $quotedTable,
                                    $this->buildTableName($filterTable),
                                    $aliased,
                                    $joinCondition
                                );
                                if ($hasAppliedDistinct === false) {
                                    $this->distinct($builder, $modelClass);
                                    $hasAppliedDistinct = true;
                                }
                                $filterTable = $aliased;
                            } else {
                                $filterTable  = $table;
                                $filterColumn = $modelSchemes->getForeignKey(
                                    $modelClass,
                                    $filterParam->getRelationshipName()
                                );
                            }
                            break;
                        case RelationshipTypes::HAS_MANY:
                            // here we join hasMany table and apply filter on its primary key
                            $primaryKey = $modelSchemes->getPrimaryKey($modelClass);
                            list ($reverseClass, $reverseName) = $modelSchemes
                                ->getReverseRelationship($modelClass, $filterParam->getRelationshipName());
                            $filterTable   = $modelSchemes->getTable($reverseClass);
                            $reverseFk     = $modelSchemes->getForeignKey($reverseClass, $reverseName);
                            $filterColumn  = $filterParam->isForAttributeInRelationship() === true ?
                                $filterParam->getAttributeName() : $modelSchemes->getPrimaryKey($reverseClass);
                            $aliased       = $filterTable . $this->getNewAliasId();
                            $joinCondition = $this->buildColumnName($table, $primaryKey) . '=' .
                                $this->buildColumnName($aliased, $reverseFk);
                            $builder->innerJoin(
                                $quotedTable,
                                $this->buildTableName($filterTable),
                                $aliased,
                                $joinCondition
                            );
                            if ($hasAppliedDistinct === false) {
                                $this->distinct($builder, $modelClass);
                                $hasAppliedDistinct = true;
                            }
                            $filterTable = $aliased;
                            break;
                        case RelationshipTypes::BELONGS_TO_MANY:
                            // here we join intermediate belongsToMany table and apply filter on its 2nd foreign key
                            list ($intermediateTable, $intermediatePk, $intermediateFk) = $modelSchemes
                                ->getBelongsToManyRelationship($modelClass, $filterParam->getRelationshipName());
                            $primaryKey    = $modelSchemes->getPrimaryKey($modelClass);
                            $aliased       = $intermediateTable . $this->getNewAliasId();
                            $joinCondition = $this->buildColumnName($table, $primaryKey) . '=' .
                                $this->buildColumnName($aliased, $intermediatePk);
                            $builder->innerJoin(
                                $quotedTable,
                                $this->buildTableName($intermediateTable),
                                $aliased,
                                $joinCondition
                            );
                            if ($hasAppliedDistinct === false) {
                                $this->distinct($builder, $modelClass);
                                $hasAppliedDistinct = true;
                            }
                            if ($filterParam->isForAttributeInRelationship() === false) {
                                $filterColumn = $intermediateFk;
                                $filterTable  = $aliased;
                            } else {
                                // that's a condition on attribute of resources in relationship
                                // so we have to join that table
                                list ($reverseClass) = $modelSchemes
                                    ->getReverseRelationship($modelClass, $filterParam->getRelationshipName());
                                $reverseTable = $modelSchemes->getTable($reverseClass);
                                $reversePk    = $modelSchemes->getPrimaryKey($reverseClass);
                                // now join the table with intermediate
                                $aliased2      = $reverseTable . $this->getNewAliasId();
                                $joinCondition = $this->buildColumnName($aliased, $intermediateFk) .
                                    '=' . $this->buildColumnName($aliased2, $reversePk);
                                $builder->innerJoin(
                                    $aliased,
                                    $this->buildTableName($reverseTable),
                                    $aliased2,
                                    $joinCondition
                                );
                                $filterColumn = $filterParam->getAttributeName();
                                $filterTable  = $aliased2;
                            }
                            break;
                    }
                } else {
                    // param for attribute
                    $filterTable  = $table;
                    $filterColumn = $filterParam->getAttributeName();
                }

                // here $filterTable and $filterColumn should always be not null (if not it's a bug in logic)

                $this->applyFilterToQuery(
                    $errors,
                    $builder,
                    $whereLink,
                    $filterParam->getOriginalName(),
                    $filterTable,
                    $filterColumn,
                    $lcOp,
                    $params
                );
            }
        }

        $builder->andWhere($whereLink);
    }

    /**
     * @inheritdoc
     */
    public function createRelationshipBuilder(string $modelClass, string $relationshipName): array
    {
        $builder          = null;
        $resultClass      = null;
        $relationshipType = null;
        $table            = null;
        $column           = null;

        $relationshipType = $this->getModelSchemes()->getRelationshipType($modelClass, $relationshipName);
        switch ($relationshipType) {
            case RelationshipTypes::BELONGS_TO:
                list($builder, $resultClass, $table, $column) =
                    $this->createBelongsToBuilder($modelClass, $relationshipName);
                break;
            case RelationshipTypes::HAS_MANY:
                list($builder, $resultClass, $table, $column) =
                    $this->createHasManyBuilder($modelClass, $relationshipName);
                break;
            case RelationshipTypes::BELONGS_TO_MANY:
                list($builder, $resultClass, $table, $column) =
                    $this->createBelongsToManyBuilder($modelClass, $relationshipName);
                break;
        }

        return [$builder, $resultClass, $relationshipType, $table, $column];
    }

    /**
     * @inheritdoc
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @inheritdoc
     */
    public function getColumns(string $modelClass): array
    {
        $table   = $this->getModelSchemes()->getTable($modelClass);
        $columns = $this->getModelSchemes()->getAttributes($modelClass);
        $result  = [];
        foreach ($columns as $column) {
            $result[] = $this->buildColumnName($table, $column, $modelClass);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function buildTableName(string $table): string
    {
        return "`$table`";
    }

    /**
     * @inheritdoc
     */
    public function buildColumnName(string $table, string $column, string $modelClass = null): string
    {
        return "`$table`.`$column`";
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->modelSchemes;
    }

    /**
     * @return FormatterInterface
     */
    protected function getFluteMessageFormatter(): FormatterInterface
    {
        return $this->fluteMsgFormatter;
    }

    /**
     * @return FilterOperationsInterface
     */
    protected function getFilterOperations(): FilterOperationsInterface
    {
        return $this->filterOperations;
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function getTableName(string $class): string
    {
        $tableName = $this->getModelSchemes()->getTable($class);

        return $tableName;
    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function getPrimaryKeyName(string $class): string
    {
        $primaryKey = $this->getModelSchemes()->getPrimaryKey($class);

        return $primaryKey;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param ErrorCollection     $errors
     * @param QueryBuilder        $builder
     * @param CompositeExpression $link
     * @param string              $originalName
     * @param string              $table
     * @param string              $field
     * @param string              $operation
     * @param array|string|null   $params
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function applyFilterToQuery(
        ErrorCollection $errors,
        QueryBuilder $builder,
        CompositeExpression $link,
        string $originalName,
        string $table,
        string $field,
        string $operation,
        $params = null
    ): void {
        switch ($operation) {
            case '=':
            case 'eq':
            case 'equals':
                $this->getFilterOperations()
                    ->applyEquals($builder, $link, $errors, $table, $field, $params);
                break;
            case '!=':
            case 'neq':
            case 'not-equals':
                $this->getFilterOperations()
                    ->applyNotEquals($builder, $link, $errors, $table, $field, $params);
                break;
            case '<':
            case 'lt':
            case 'less-than':
                $this->getFilterOperations()
                    ->applyLessThan($builder, $link, $errors, $table, $field, $params);
                break;
            case '<=':
            case 'lte':
            case 'less-or-equals':
                $this->getFilterOperations()
                    ->applyLessOrEquals($builder, $link, $errors, $table, $field, $params);
                break;
            case '>':
            case 'gt':
            case 'greater-than':
                $this->getFilterOperations()
                    ->applyGreaterThan($builder, $link, $errors, $table, $field, $params);
                break;
            case '>=':
            case 'gte':
            case 'greater-or-equals':
                $this->getFilterOperations()
                    ->applyGreaterOrEquals($builder, $link, $errors, $table, $field, $params);
                break;
            case 'like':
                $this->getFilterOperations()
                    ->applyLike($builder, $link, $errors, $table, $field, $params);
                break;
            case 'not-like':
                $this->getFilterOperations()
                    ->applyNotLike($builder, $link, $errors, $table, $field, $params);
                break;
            case 'in':
                $this->getFilterOperations()
                    ->applyIn($builder, $link, $errors, $table, $field, (array)$params);
                break;
            case 'not-in':
                $this->getFilterOperations()
                    ->applyNotIn($builder, $link, $errors, $table, $field, (array)$params);
                break;
            case self::FILTER_OP_IS_NULL:
                $this->getFilterOperations()->applyIsNull($builder, $link, $table, $field);
                break;
            case self::FILTER_OP_IS_NOT_NULL:
                $this->getFilterOperations()->applyIsNotNull($builder, $link, $table, $field);
                break;
            default:
                $errMsg = $this->getFluteMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_OPERATION);
                $errors->addQueryParameterError($originalName, $errMsg, $operation);
                break;
        }
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     *
     * @return QueryBuilder
     */
    protected function distinct(QueryBuilder $builder, string $modelClass): QueryBuilder
    {
        // emulate SELECT DISTINCT (group by primary key)
        $primaryColumn     = $this->getModelSchemes()->getPrimaryKey($modelClass);
        $fullPrimaryColumn = $this->buildColumnName($this->getTableName($modelClass), $primaryColumn, $modelClass);
        $builder->addGroupBy($fullPrimaryColumn);

        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $table
     * @param string       $column
     * @param string       $bindName
     *
     * @return void
     */
    private function addWhereBind(QueryBuilder $builder, string $table, string $column, string $bindName): void
    {
        $builder
            ->andWhere($this->buildColumnName($table, $column) . '=' . $bindName);
    }

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return array
     */
    private function createBelongsToBuilder(string $modelClass, string $relationshipName): array
    {
        $oneClass       = $this->getModelSchemes()->getReverseModelClass($modelClass, $relationshipName);
        $oneTable       = $this->getTableName($oneClass);
        $oneTableQuoted = $this->buildTableName($oneTable);
        $onePrimaryKey  = $this->getPrimaryKeyName($oneClass);
        $table          = $this->getTableName($modelClass);
        $foreignKey     = $this->getModelSchemes()->getForeignKey($modelClass, $relationshipName);

        $builder = $this->getConnection()->createQueryBuilder();

        $aliased       = $table . $this->getNewAliasId();
        $joinCondition = $this->buildColumnName($oneTable, $onePrimaryKey) . '=' .
            $this->buildColumnName($aliased, $foreignKey);
        $builder
            ->select($this->getColumns($oneClass))
            ->from($oneTableQuoted)
            ->innerJoin($oneTableQuoted, $table, $aliased, $joinCondition);

        return [$builder, $oneClass, $aliased, $this->getPrimaryKeyName($modelClass)];
    }

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return array
     */
    private function createHasManyBuilder(string $modelClass, string $relationshipName): array
    {
        list ($reverseClass, $reverseName) = $this->getModelSchemes()
            ->getReverseRelationship($modelClass, $relationshipName);
        $reverseTable = $this->getModelSchemes()->getTable($reverseClass);
        $foreignKey   = $this->getModelSchemes()->getForeignKey($reverseClass, $reverseName);
        $builder      = $this->getConnection()->createQueryBuilder();
        $builder
            ->select($this->getColumns($reverseClass))
            ->from($this->buildTableName($reverseTable));

        return [$builder, $reverseClass, $reverseTable, $foreignKey];
    }

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return array
     */
    private function createBelongsToManyBuilder(string $modelClass, string $relationshipName): array
    {
        list ($intermediateTable, $foreignKey, $reverseForeignKey) =
            $this->getModelSchemes()->getBelongsToManyRelationship($modelClass, $relationshipName);
        $reverseClass       = $this->getModelSchemes()->getReverseModelClass($modelClass, $relationshipName);
        $reverseTable       = $this->getModelSchemes()->getTable($reverseClass);
        $reverseTableQuoted = $this->buildTableName($reverseTable);
        $reversePk          = $this->getModelSchemes()->getPrimaryKey($reverseClass);

        $aliased       = $intermediateTable . $this->getNewAliasId();
        $joinCondition = $this->buildColumnName($reverseTable, $reversePk) . '=' .
            $this->buildColumnName($aliased, $reverseForeignKey);
        $builder       = $this->getConnection()->createQueryBuilder();
        $builder
            ->select($this->getColumns($reverseClass))
            ->from($reverseTableQuoted)
            ->innerJoin($reverseTableQuoted, $intermediateTable, $aliased, $joinCondition);

        return [$builder, $reverseClass, $aliased, $foreignKey];
    }

    /**
     * @return int
     */
    private function getNewAliasId(): int
    {
        return ++$this->aliasIdCounter;
    }
}

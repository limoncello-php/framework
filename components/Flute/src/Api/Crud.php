<?php namespace Limoncello\Flute\Api;

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

use ArrayObject;
use Closure;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Generator;
use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Api\ModelsDataInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\IncludeParameterInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use Limoncello\Flute\Http\Query\FilterParameterCollection;
use Limoncello\Flute\L10n\Messages;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException as E;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Crud implements CrudInterface
{
    use HasContainerTrait;

    /** Internal constant. Query param name. */
    protected const INDEX_BIND = ':index';

    /** Internal constant. Query param name. */
    protected const CHILD_INDEX_BIND = ':childIndex';

    /** Internal constant. Path constant. */
    protected const ROOT_PATH = '';

    /** Internal constant. Path constant. */
    protected const PATH_SEPARATOR = DocumentInterface::PATH_SEPARATOR;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @var PaginationStrategyInterface
     */
    private $paginationStrategy;

    /**
     * @param ContainerInterface $container
     * @param string             $modelClass
     */
    public function __construct(ContainerInterface $container, string $modelClass)
    {
        $this->setContainer($container);

        $this->factory            = $this->getContainer()->get(FactoryInterface::class);
        $this->modelClass         = $modelClass;
        $this->repository         = $this->getContainer()->get(RepositoryInterface::class);
        $this->modelSchemes       = $this->getContainer()->get(ModelSchemeInfoInterface::class);
        $this->paginationStrategy = $this->getContainer()->get(PaginationStrategyInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function index(
        FilterParameterCollection $filterParams = null,
        array $sortParams = null,
        array $includePaths = null,
        array $pagingParams = null
    ): ModelsDataInterface {
        $modelClass = $this->getModelClass();

        $builder = $this->getRepository()->index($modelClass);

        $errors = $this->getFactory()->createErrorCollection();
        $filterParams === null ?: $this->getRepository()->applyFilters($errors, $builder, $modelClass, $filterParams);
        $this->checkErrors($errors);
        $sortParams === null ?: $this->getRepository()->applySorting($builder, $modelClass, $sortParams);

        list($offset, $limit) = $this->getPaginationStrategy()->parseParameters($pagingParams);
        $builder->setFirstResult($offset)->setMaxResults($limit);

        $data = $this->fetchCollectionData($this->builderOnIndex($builder), $modelClass, $limit, $offset);

        $relationships = null;
        if ($data->getData() !== null && $includePaths !== null) {
            $relationships = $this->readRelationships($data, $includePaths);
        }

        $result = $this->getFactory()->createModelsData($data, $relationships);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function indexResources(FilterParameterCollection $filterParams = null, array $sortParams = null): array
    {
        $modelClass = $this->getModelClass();

        $builder = $this->getRepository()->index($modelClass);

        $errors = $this->getFactory()->createErrorCollection();
        $filterParams === null ?: $this->getRepository()->applyFilters($errors, $builder, $modelClass, $filterParams);
        $this->checkErrors($errors);
        $sortParams === null ?: $this->getRepository()->applySorting($builder, $modelClass, $sortParams);

        list($models) = $this->fetchCollection($this->builderOnIndex($builder), $modelClass);

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function count(FilterParameterCollection $filterParams = null): ?int
    {
        $modelClass = $this->getModelClass();

        $builder = $this->getRepository()->count($modelClass);

        $errors = $this->getFactory()->createErrorCollection();
        $filterParams === null ?: $this->getRepository()->applyFilters($errors, $builder, $modelClass, $filterParams);
        $this->checkErrors($errors);

        $result = $this->builderOnCount($builder)->execute()->fetchColumn();

        return $result === false ? null : $result;
    }

    /**
     * @inheritdoc
     */
    public function read(
        $index,
        FilterParameterCollection $filterParams = null,
        array $includePaths = null
    ): ModelsDataInterface {
        $model = $this->readResource($index, $filterParams);
        $data  = $this->getFactory()->createPaginatedData($model);

        $relationships = null;
        if ($data->getData() !== null && $includePaths !== null) {
            $relationships = $this->readRelationships($data, $includePaths);
        }

        $result = $this->getFactory()->createModelsData($data, $relationships);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function readResource($index, FilterParameterCollection $filterParams = null)
    {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();

        $builder = $this->getRepository()
            ->read($modelClass, static::INDEX_BIND)
            ->setParameter(static::INDEX_BIND, $index);

        $errors = $this->getFactory()->createErrorCollection();
        $filterParams === null ?: $this->getRepository()->applyFilters($errors, $builder, $modelClass, $filterParams);
        $this->checkErrors($errors);

        $model = $this->fetchSingle($this->builderOnRead($builder), $modelClass);

        return $model;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function readRelationship(
        $index,
        string $name,
        FilterParameterCollection $filterParams = null,
        array $sortParams = null,
        array $pagingParams = null
    ): PaginatedDataInterface {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();

        /** @var QueryBuilder $builder */
        list ($builder, $resultClass, $relationshipType) =
            $this->getRepository()->readRelationship($modelClass, static::INDEX_BIND, $name);

        $errors = $this->getFactory()->createErrorCollection();
        $filterParams === null ?: $this->getRepository()->applyFilters($errors, $builder, $resultClass, $filterParams);
        $this->checkErrors($errors);
        $sortParams === null ?: $this->getRepository()->applySorting($builder, $resultClass, $sortParams);

        $builder->setParameter(static::INDEX_BIND, $index);

        $isCollection = $relationshipType === RelationshipTypes::HAS_MANY ||
            $relationshipType === RelationshipTypes::BELONGS_TO_MANY;

        if ($isCollection == true) {
            list($offset, $limit) = $this->getPaginationStrategy()->parseParameters($pagingParams);
            $builder->setFirstResult($offset)->setMaxResults($limit);
            $data = $this
                ->fetchCollectionData($this->builderOnReadRelationship($builder), $resultClass, $limit, $offset);
        } else {
            $data = $this->fetchSingleData($this->builderOnReadRelationship($builder), $resultClass);
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function hasInRelationship($parentId, string $name, $childId): bool
    {
        if ($parentId !== null && is_scalar($parentId) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }
        if ($childId !== null && is_scalar($childId) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();

        /** @var QueryBuilder $builder */
        list ($builder) = $this->getRepository()
            ->hasInRelationship($modelClass, static::INDEX_BIND, $name, static::CHILD_INDEX_BIND);

        $builder->setParameter(static::INDEX_BIND, $parentId);
        $builder->setParameter(static::CHILD_INDEX_BIND, $childId);

        $result = $builder->execute()->fetch();

        return $result !== false;
    }

    /**
     * @inheritdoc
     */
    public function readRow($index): ?array
    {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();
        $builder    = $this->getRepository()
            ->read($modelClass, static::INDEX_BIND)
            ->setParameter(static::INDEX_BIND, $index);
        $typedRow   = $this->fetchRow($builder, $modelClass);

        return $typedRow;
    }

    /**
     * @inheritdoc
     */
    public function delete($index): int
    {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();

        $builder = $this->builderOnDelete(
            $this->getRepository()->delete($modelClass, static::INDEX_BIND)->setParameter(static::INDEX_BIND, $index)
        );

        $deleted = $builder->execute();

        return (int)$deleted;
    }

    /**
     * @inheritdoc
     */
    public function create($index, array $attributes, array $toMany = []): string
    {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $modelClass = $this->getModelClass();

        $allowedChanges = $this->filterAttributesOnCreate($modelClass, $attributes, $index);

        $saveMain = $this->getRepository()->create($modelClass, $allowedChanges);
        $saveMain = $this->builderSaveResourceOnCreate($saveMain);
        $saveMain->getSQL(); // prepare
        $this->inTransaction(function () use ($modelClass, $saveMain, $toMany, &$index) {
            $saveMain->execute();
            // if no index given will use last insert ID as index
            $index !== null ?: $index = $saveMain->getConnection()->lastInsertId();
            foreach ($toMany as $name => $values) {
                $indexBind      = ':index';
                $otherIndexBind = ':otherIndex';
                $saveToMany     = $this->getRepository()
                    ->createToManyRelationship($modelClass, $indexBind, $name, $otherIndexBind);
                $saveToMany     = $this->builderSaveRelationshipOnCreate($name, $saveToMany);
                $saveToMany->setParameter($indexBind, $index);
                foreach ($values as $value) {
                    $saveToMany->setParameter($otherIndexBind, $value)->execute();
                }
            }
        });

        return $index;
    }

    /**
     * @inheritdoc
     */
    public function update($index, array $attributes, array $toMany = []): int
    {
        if ($index !== null && is_scalar($index) === false) {
            throw new InvalidArgumentException(
                $this->createMessageFormatter()->formatMessage(Messages::MSG_ERR_INVALID_ARGUMENT)
            );
        }

        $updated    = 0;
        $modelClass = $this->getModelClass();

        $allowedChanges = $this->filterAttributesOnUpdate($modelClass, $attributes);

        $saveMain = $this->getRepository()->update($modelClass, $index, $allowedChanges);
        $saveMain = $this->builderSaveResourceOnUpdate($saveMain);
        $saveMain->getSQL(); // prepare
        $this->inTransaction(function () use ($modelClass, $saveMain, $toMany, $index, &$updated) {
            $updated = $saveMain->execute();
            foreach ($toMany as $name => $values) {
                $indexBind      = ':index';
                $otherIndexBind = ':otherIndex';

                $cleanToMany = $this->getRepository()->cleanToManyRelationship($modelClass, $indexBind, $name);
                $cleanToMany = $this->builderCleanRelationshipOnUpdate($name, $cleanToMany);
                $cleanToMany->setParameter($indexBind, $index)->execute();

                $saveToMany = $this->getRepository()
                    ->createToManyRelationship($modelClass, $indexBind, $name, $otherIndexBind);
                $saveToMany = $this->builderSaveRelationshipOnUpdate($name, $saveToMany);
                $saveToMany->setParameter($indexBind, $index);
                foreach ($values as $value) {
                    $updated += (int)$saveToMany->setParameter($otherIndexBind, $value)->execute();
                }
            }
        });

        return (int)$updated;
    }

    /**
     * @return FactoryInterface
     */
    protected function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @return RepositoryInterface
     */
    protected function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->modelSchemes;
    }

    /**
     * @return PaginationStrategyInterface
     */
    protected function getPaginationStrategy(): PaginationStrategyInterface
    {
        return $this->paginationStrategy;
    }

    /**
     * @param Closure $closure
     *
     * @return void
     */
    protected function inTransaction(Closure $closure): void
    {
        $connection = $this->getRepository()->getConnection();
        $connection->beginTransaction();
        try {
            $isOk = ($closure() === false ? null : true);
        } finally {
            isset($isOk) === true ? $connection->commit() : $connection->rollBack();
        }
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $class
     *
     * @return PaginatedDataInterface
     */
    protected function fetchSingleData(QueryBuilder $builder, string $class): PaginatedDataInterface
    {
        $model = $this->fetchSingle($builder, $class);
        $data  = $this->getFactory()->createPaginatedData($model)->markAsSingleItem();

        return $data;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $class
     * @param int          $limit
     * @param int          $offset
     *
     * @return PaginatedDataInterface
     */
    protected function fetchCollectionData(
        QueryBuilder $builder,
        string $class,
        int $limit,
        int $offset
    ): PaginatedDataInterface {
        list($models, $hasMore, $limit, $offset) = $this->fetchCollection($builder, $class, $limit, $offset);

        $data = $this->getFactory()
            ->createPaginatedData($models)
            ->markAsCollection()
            ->setOffset($offset)
            ->setLimit($limit);
        $hasMore === true ? $data->markHasMoreItems() : $data->markHasNoMoreItems();

        return $data;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $class
     *
     * @return array|null
     */
    protected function fetchRow(QueryBuilder $builder, string $class): ?array
    {
        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
        $platform  = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($class);

        $model = null;
        if (($attributes = $statement->fetch()) !== false) {
            $model = $this->readRowFromAssoc($attributes, $typeNames, $platform);
        }

        return $model;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $class
     *
     * @return mixed|null
     */
    protected function fetchSingle(QueryBuilder $builder, string $class)
    {
        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
        $platform = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($class);

        $model = null;
        if (($attributes = $statement->fetch()) !== false) {
            $model = $this->readInstanceFromAssoc($class, $attributes, $typeNames, $platform);
        }

        return $model;
    }

    /**
     * @param QueryBuilder    $builder
     * @param string          $class
     * @param int|string|null $offset
     * @param int|string|null $limit
     *
     * @return array
     */
    protected function fetchCollection(QueryBuilder $builder, string $class, $limit = null, $offset = null): array
    {
        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
        $platform = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($class);

        $models = [];
        while (($attributes = $statement->fetch()) !== false) {
            $models[] = $this->readInstanceFromAssoc($class, $attributes, $typeNames, $platform);
        }

        return $this->normalizePagingParams($models, $limit, $offset);
    }

    /**
     * @param string      $modelClass
     * @param array       $attributes
     * @param null|string $index
     *
     * @return array
     */
    protected function filterAttributesOnCreate(string $modelClass, array $attributes, string $index = null): array
    {
        $allowedAttributes = array_flip($this->getModelSchemes()->getAttributes($modelClass));
        $allowedChanges    = array_intersect_key($attributes, $allowedAttributes);
        if ($index !== null) {
            $pkName = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
            $allowedChanges[$pkName] = $index;
        }

        return $allowedChanges;
    }

    /**
     * @param string $modelClass
     * @param array  $attributes
     *
     * @return array
     */
    protected function filterAttributesOnUpdate(string $modelClass, array $attributes): array
    {
        $allowedAttributes = array_flip($this->getModelSchemes()->getAttributes($modelClass));
        $allowedChanges    = array_intersect_key($attributes, $allowedAttributes);

        return $allowedChanges;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderOnCount(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderOnIndex(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderOnRead(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderOnReadRelationship(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderSaveResourceOnCreate(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderSaveResourceOnUpdate(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param string       $relationshipName
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderSaveRelationshipOnCreate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        QueryBuilder $builder
    ): QueryBuilder {
        return $builder;
    }

    /**
     * @param string       $relationshipName
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderSaveRelationshipOnUpdate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        QueryBuilder $builder
    ): QueryBuilder {
        return $builder;
    }

    /**
     * @param string       $relationshipName
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderCleanRelationshipOnUpdate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        QueryBuilder $builder
    ): QueryBuilder {
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     *
     * @return QueryBuilder
     */
    protected function builderOnDelete(QueryBuilder $builder): QueryBuilder
    {
        return $builder;
    }

    /**
     * @param PaginatedDataInterface      $data
     * @param IncludeParameterInterface[] $paths
     *
     * @return RelationshipStorageInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function readRelationships(PaginatedDataInterface $data, array $paths): RelationshipStorageInterface
    {
        $result = $this->getFactory()->createRelationshipStorage();

        if (empty($data->getData()) === false && empty($paths) === false) {
            $modelStorage = $this->getFactory()->createModelStorage($this->getModelSchemes());
            $modelsAtPath = $this->getFactory()->createTagStorage();

            // we gonna send this storage via function params so it is an equivalent for &array
            $classAtPath = new ArrayObject();

            $model = null;
            if ($data->isCollection() === true) {
                foreach ($data->getData() as $model) {
                    $uniqueModel = $modelStorage->register($model);
                    if ($uniqueModel !== null) {
                        $modelsAtPath->register($uniqueModel, static::ROOT_PATH);
                    }
                }
            } else {
                $model       = $data->getData();
                $uniqueModel = $modelStorage->register($model);
                if ($uniqueModel !== null) {
                    $modelsAtPath->register($uniqueModel, static::ROOT_PATH);
                }
            }
            $classAtPath[static::ROOT_PATH] = get_class($model);

            foreach ($this->getPaths($paths) as list ($parentPath, $childPaths)) {
                $this->loadRelationshipsLayer(
                    $result,
                    $modelsAtPath,
                    $classAtPath,
                    $modelStorage,
                    $parentPath,
                    $childPaths
                );
            }
        }

        return $result;
    }

    /**
     * @param array           $models
     * @param int|string|null $offset
     * @param int|string|null $limit
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function normalizePagingParams(array $models, $limit, $offset): array
    {
        if ($limit !== null) {
            $hasMore = count($models) >= $limit;
            $limit   = $hasMore === true ? $limit - 1 : null;
            $offset  = $limit === null && $hasMore === false ? null : $offset;
            $hasMore === false ?: array_pop($models);
        } else {
            $hasMore = false;
        }

        return [$models, $hasMore, $limit, $offset];
    }

    /**
     * @param ErrorCollection $errors
     *
     * @return void
     */
    private function checkErrors(ErrorCollection $errors): void
    {
        if (empty($errors->getArrayCopy()) === false) {
            throw new E($errors);
        }
    }

    /**
     * @param IncludeParameterInterface[] $paths
     *
     * @return Generator
     */
    private function getPaths(array $paths): Generator
    {
        // The idea is to normalize paths. It means build all intermediate paths.
        // e.g. if only `a.b.c` path it given it will be normalized to `a`, `a.b` and `a.b.c`.
        // Path depths store depth of each path (e.g. 0 for root, 1 for `a`, 2 for `a.b` and etc).
        // It is needed for yielding them in correct order (from top level to bottom).
        $normalizedPaths = [];
        $pathsDepths     = [];
        foreach ($paths as $path) {
            $parentDepth = 0;
            $tmpPath     = static::ROOT_PATH;
            foreach ($path->getPath() as $pathPiece) {
                $parent                    = $tmpPath;
                $tmpPath                   = empty($tmpPath) === true ?
                    $pathPiece : $tmpPath . static::PATH_SEPARATOR . $pathPiece;
                $normalizedPaths[$tmpPath] = [$parent, $pathPiece];
                $pathsDepths[$parent]      = $parentDepth++;
            }
        }

        // Here we collect paths in form of parent => [list of children]
        // e.g. '' => ['a', 'c', 'b'], 'b' => ['bb', 'aa'] and etc
        $parentWithChildren = [];
        foreach ($normalizedPaths as $path => list ($parent, $childPath)) {
            $parentWithChildren[$parent][] = $childPath;
        }

        // And finally sort by path depth and yield parent with its children. Top level paths first then deeper ones.
        asort($pathsDepths, SORT_NUMERIC);
        foreach ($pathsDepths as $parent => $depth) {
            $depth ?: null; // suppress unused
            $childPaths = $parentWithChildren[$parent];
            yield [$parent, $childPaths];
        }
    }

    /**
     * @param RelationshipStorageInterface $result
     * @param TagStorageInterface          $modelsAtPath
     * @param ArrayObject                  $classAtPath
     * @param ModelStorageInterface        $deDup
     * @param string                       $parentsPath
     * @param array                        $childRelationships
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function loadRelationshipsLayer(
        RelationshipStorageInterface $result,
        TagStorageInterface $modelsAtPath,
        ArrayObject $classAtPath,
        ModelStorageInterface $deDup,
        string $parentsPath,
        array $childRelationships
    ): void {
        $rootClass   = $classAtPath[static::ROOT_PATH];
        $parentClass = $classAtPath[$parentsPath];
        $parents     = $modelsAtPath->get($parentsPath);

        // What should we do? We have do find all child resources for $parents at paths $childRelationships (1 level
        // child paths) and add them to $relationships. While doing it we have to deduplicate resources with
        // $models.

        foreach ($childRelationships as $name) {
            $childrenPath = $parentsPath !== static::ROOT_PATH ? $parentsPath . static::PATH_SEPARATOR . $name : $name;

            /** @var QueryBuilder $builder */
            list ($builder, $class, $relationshipType) =
                $this->getRepository()->readRelationship($parentClass, static::INDEX_BIND, $name);

            $classAtPath[$childrenPath] = $class;

            switch ($relationshipType) {
                case RelationshipTypes::BELONGS_TO:
                    $pkName = $this->getModelSchemes()->getPrimaryKey($parentClass);
                    foreach ($parents as $parent) {
                        $builder->setParameter(static::INDEX_BIND, $parent->{$pkName});
                        $child = $deDup->register($this->fetchSingle($builder, $class));
                        if ($child !== null) {
                            $modelsAtPath->register($child, $childrenPath);
                        }
                        $result->addToOneRelationship($parent, $name, $child);
                    }
                    break;
                case RelationshipTypes::HAS_MANY:
                case RelationshipTypes::BELONGS_TO_MANY:
                    list ($queryOffset, $queryLimit) = $this->getPaginationStrategy()
                        ->getParameters($rootClass, $parentClass, $parentsPath, $name);
                    $builder->setFirstResult($queryOffset)->setMaxResults($queryLimit);
                    $pkName = $this->getModelSchemes()->getPrimaryKey($parentClass);
                    foreach ($parents as $parent) {
                        $builder->setParameter(static::INDEX_BIND, $parent->{$pkName});
                        list($children, $hasMore, $limit, $offset) =
                            $this->fetchCollection($builder, $class, $queryLimit, $queryOffset);
                        $deDupedChildren = [];
                        foreach ($children as $child) {
                            $child = $deDup->register($child);
                            $modelsAtPath->register($child, $childrenPath);
                            if ($child !== null) {
                                $deDupedChildren[] = $child;
                            }
                        }
                        $result->addToManyRelationship($parent, $name, $deDupedChildren, $hasMore, $offset, $limit);
                    }
                    break;
            }
        }
    }

    /**
     * @param string             $namespace
     *
     * @return FormatterInterface
     */
    protected function createMessageFormatter(string $namespace = Messages::RESOURCES_NAMESPACE): FormatterInterface
    {
        /** @var FormatterFactoryInterface $factory */
        $factory          = $this->getContainer()->get(FormatterFactoryInterface::class);
        $messageFormatter = $factory->createFormatter($namespace);

        return $messageFormatter;
    }

    /**
     * @param string           $class
     * @param array            $attributes
     * @param Type[]           $typeNames
     * @param AbstractPlatform $platform
     *
     * @return mixed|null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function readInstanceFromAssoc(
        string $class,
        array $attributes,
        array $typeNames,
        AbstractPlatform $platform
    ) {
        $instance = new $class();
        foreach ($attributes as $name => $value) {
            if (array_key_exists($name, $typeNames) === true) {
                $type  = Type::getType($typeNames[$name]);
                $value = $type->convertToPHPValue($value, $platform);
            }
            $instance->{$name} = $value;
        }

        return $instance;
    }

    /**
     * @param array            $attributes
     * @param Type[]           $typeNames
     * @param AbstractPlatform $platform
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function readRowFromAssoc(array $attributes, array $typeNames, AbstractPlatform $platform): array
    {
        $row = [];
        foreach ($attributes as $name => $value) {
            if (array_key_exists($name, $typeNames) === true) {
                $type  = Type::getType($typeNames[$name]);
                $value = $type->convertToPHPValue($value, $platform);
            }
            $row[$name] = $value;
        }

        return $row;
    }
}

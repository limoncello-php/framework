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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Adapters\ModelQueryBuilder;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Models\ModelStorageInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\TagStorageInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use Limoncello\Flute\L10n\Messages;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Psr\Container\ContainerInterface;
use Traversable;

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
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @var PaginationStrategyInterface
     */
    private $paginationStrategy;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var iterable|null
     */
    private $filterParameters = null;

    /**
     * @var bool
     */
    private $areFiltersWithAnd = true;

    /**
     * @var iterable|null
     */
    private $sortingParameters = null;

    /**
     * @var array
     */
    private $relFiltersAndSorts = [];

    /**
     * @var iterable|null
     */
    private $includePaths = null;

    /**
     * @var int|null
     */
    private $pagingOffset = null;

    /**
     * @var int|null
     */
    private $pagingLimit = null;

    /** internal constant */
    private const REL_FILTERS_AND_SORTS__FILTERS = 0;

    /** internal constant */
    private const REL_FILTERS_AND_SORTS__SORTS = 1;

    /**
     * @param ContainerInterface $container
     * @param string             $modelClass
     */
    public function __construct(ContainerInterface $container, string $modelClass)
    {
        $this->setContainer($container);

        $this->modelClass         = $modelClass;
        $this->factory            = $this->getContainer()->get(FactoryInterface::class);
        $this->modelSchemes       = $this->getContainer()->get(ModelSchemeInfoInterface::class);
        $this->paginationStrategy = $this->getContainer()->get(PaginationStrategyInterface::class);
        $this->connection         = $this->getContainer()->get(Connection::class);

        $this->clearBuilderParameters()->clearFetchParameters();
    }

    /**
     * @inheritdoc
     */
    public function withFilters(iterable $filterParameters): CrudInterface
    {
        $this->filterParameters = $filterParameters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withIndexFilter($index): CrudInterface
    {
        if (is_int($index) === false && is_string($index) === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        $pkName = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        $this->withFilters([
            $pkName => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withRelationshipFilters(string $name, iterable $filters): CrudInterface
    {
        $this->relFiltersAndSorts[$name][self::REL_FILTERS_AND_SORTS__FILTERS] = $filters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withRelationshipSorts(string $name, iterable $sorts): CrudInterface
    {
        $this->relFiltersAndSorts[$name][self::REL_FILTERS_AND_SORTS__SORTS] = $sorts;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function combineWithAnd(): CrudInterface
    {
        $this->areFiltersWithAnd = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function combineWithOr(): CrudInterface
    {
        $this->areFiltersWithAnd = false;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasFilters(): bool
    {
        return empty($this->filterParameters) === false;
    }

    /**
     * @return iterable
     */
    private function getFilters(): iterable
    {
        return $this->filterParameters;
    }

    /**
     * @return bool
     */
    private function areFiltersWithAnd(): bool
    {
        return $this->areFiltersWithAnd;
    }

    /**
     * @inheritdoc
     */
    public function withSorts(iterable $sortingParameters): CrudInterface
    {
        $this->sortingParameters = $sortingParameters;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasSorts(): bool
    {
        return empty($this->sortingParameters) === false;
    }

    /**
     * @return iterable
     */
    private function getSorts(): ?iterable
    {
        return $this->sortingParameters;
    }

    /**
     * @inheritdoc
     */
    public function withIncludes(iterable $includePaths): CrudInterface
    {
        $this->includePaths = $includePaths;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasIncludes(): bool
    {
        return empty($this->includePaths) === false;
    }

    /**
     * @return iterable
     */
    private function getIncludes(): iterable
    {
        return $this->includePaths;
    }

    /**
     * @inheritdoc
     */
    public function withPaging(int $offset, int $limit): CrudInterface
    {
        $this->pagingOffset = $offset;
        $this->pagingLimit  = $limit;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasPaging(): bool
    {
        return $this->pagingOffset !== null && $this->pagingLimit !== null;
    }

    /**
     * @return int
     */
    private function getPagingOffset(): int
    {
        return $this->pagingOffset;
    }

    /**
     * @return int
     */
    private function getPagingLimit(): int
    {
        return $this->pagingLimit;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string $modelClass
     *
     * @return ModelQueryBuilder
     */
    protected function createBuilder(string $modelClass): ModelQueryBuilder
    {
        return $this->createBuilderFromConnection($this->getConnection(), $modelClass);
    }

    /**
     * @param Connection $connection
     * @param string     $modelClass
     *
     * @return ModelQueryBuilder
     */
    private function createBuilderFromConnection(Connection $connection, string $modelClass): ModelQueryBuilder
    {
        return $this->getFactory()->createModelQueryBuilder($connection, $modelClass, $this->getModelSchemes());
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return Crud
     */
    private function applyAliasFilters(ModelQueryBuilder $builder): self
    {
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $this->areFiltersWithAnd() === true ?
                $builder->addFiltersWithAndToAlias($filters) : $builder->addFiltersWithOrToAlias($filters);
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return self
     */
    private function applyTableFilters(ModelQueryBuilder $builder): self
    {
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $this->areFiltersWithAnd() === true ?
                $builder->addFiltersWithAndToTable($filters) : $builder->addFiltersWithOrToTable($filters);
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return self
     */
    private function applyRelationshipFiltersAndSorts(ModelQueryBuilder $builder): self
    {
        // While joining tables we select distinct rows. This flag used to apply `distinct` no more than once.
        $distinctApplied = false;

        foreach ($this->relFiltersAndSorts as $relationshipName => $filtersAndSorts) {
            assert(is_string($relationshipName) === true && is_array($filtersAndSorts) === true);
            $builder->addRelationshipFiltersAndSortsWithAnd(
                $relationshipName,
                $filtersAndSorts[self::REL_FILTERS_AND_SORTS__FILTERS] ?? [],
                $filtersAndSorts[self::REL_FILTERS_AND_SORTS__SORTS] ?? []
            );

            if ($distinctApplied === false) {
                $builder->distinct();
                $distinctApplied = true;
            }
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return self
     */
    private function applySorts(ModelQueryBuilder $builder): self
    {
        if ($this->hasSorts() === true) {
            $builder->addSorts($this->getSorts());
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return self
     */
    private function applyPaging(ModelQueryBuilder $builder): self
    {
        if ($this->hasPaging() === true) {
            $builder->setFirstResult($this->getPagingOffset());
            $builder->setMaxResults($this->getPagingLimit() + 1);
        }

        return $this;
    }

    /**
     * @return self
     */
    private function clearBuilderParameters(): self
    {
        $this->filterParameters   = null;
        $this->areFiltersWithAnd  = true;
        $this->sortingParameters  = null;
        $this->pagingOffset       = null;
        $this->pagingLimit        = null;
        $this->relFiltersAndSorts = [];

        return $this;
    }

    /**
     * @return self
     */
    private function clearFetchParameters(): self
    {
        $this->includePaths = null;

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderOnCount(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderOnIndex(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderOnReadRelationship(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderSaveResourceOnCreate(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderSaveResourceOnUpdate(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param string            $relationshipName
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderSaveRelationshipOnCreate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string            $relationshipName
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderSaveRelationshipOnUpdate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string            $relationshipName
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function builderCleanRelationshipOnUpdate(/** @noinspection PhpUnusedParameterInspection */
        $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     *
     * @return ModelQueryBuilder
     */
    protected function builderOnDelete(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param PaginatedDataInterface $data
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function loadRelationships(PaginatedDataInterface $data): void
    {
        if (empty($data->getData()) === false && $this->hasIncludes() === true) {
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

            foreach ($this->getPaths($this->getIncludes()) as list ($parentPath, $childPaths)) {
                $this->loadRelationshipsLayer(
                    $modelsAtPath,
                    $classAtPath,
                    $modelStorage,
                    $parentPath,
                    $childPaths
                );
            }
        }
    }

    /**
     * @param iterable $paths (string[])
     *
     * @return iterable
     */
    private static function getPaths(iterable $paths): iterable
    {
        // The idea is to normalize paths. It means build all intermediate paths.
        // e.g. if only `a.b.c` path it given it will be normalized to `a`, `a.b` and `a.b.c`.
        // Path depths store depth of each path (e.g. 0 for root, 1 for `a`, 2 for `a.b` and etc).
        // It is needed for yielding them in correct order (from top level to bottom).
        $normalizedPaths = [];
        $pathsDepths     = [];
        foreach ($paths as $path) {
            assert(is_array($path) || $path instanceof Traversable);
            $parentDepth = 0;
            $tmpPath     = static::ROOT_PATH;
            foreach ($path as $pathPiece) {
                assert(is_string($pathPiece));
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
            assert($depth !== null); // suppress unused
            $childPaths = $parentWithChildren[$parent];
            yield [$parent, $childPaths];
        }
    }

    /**
     * @inheritdoc
     */
    public function createIndexBuilder(): QueryBuilder
    {
        return $this->createIndexModelBuilder();
    }

    /**
     * @inheritdoc
     */
    public function createDeleteBuilder(): QueryBuilder
    {
        return $this->createDeleteModelBuilder();
    }

    /**
     * @return ModelQueryBuilder
     */
    protected function createIndexModelBuilder(): ModelQueryBuilder
    {
        $builder = $this
            ->createBuilder($this->getModelClass())
            ->selectModelFields()
            ->fromModelTable();

        $this
            ->applyAliasFilters($builder)
            ->applySorts($builder)
            ->applyRelationshipFiltersAndSorts($builder)
            ->applyPaging($builder);

        $result = $this->builderOnIndex($builder);

        $this->clearBuilderParameters();

        return $result;
    }

    /**
     * @return ModelQueryBuilder
     */
    protected function createDeleteModelBuilder(): ModelQueryBuilder
    {
        $builder = $this
            ->createBuilder($this->getModelClass())
            ->deleteModels();

        $this->applyTableFilters($builder);

        $result = $this->builderOnDelete($builder);

        $this->clearBuilderParameters();

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function index(): PaginatedDataInterface
    {
        $builder = $this->createIndexModelBuilder();
        $data    = $this->fetchResources($builder, $builder->getModelClass());

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function read($index): PaginatedDataInterface
    {
        $this->withIndexFilter($index);

        $builder = $this->createIndexModelBuilder();
        $data    = $this->fetchResource($builder, $builder->getModelClass());

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function count(): ?int
    {
        $builder = $this
            ->createBuilder($this->getModelClass())
            ->select('COUNT(*)')
            ->fromModelTable();

        $this->applyAliasFilters($builder);

        $this->clearBuilderParameters()->clearFetchParameters();

        $result = $this->builderOnCount($builder)->execute()->fetchColumn();

        return $result === false ? null : $result;
    }

    /**
     * @param string        $relationshipName
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return ModelQueryBuilder
     */
    public function createReadRelationshipBuilder(
        string $relationshipName,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): ModelQueryBuilder {
        // as we read data from a relationship our main table and model would be the table/model in the relationship
        // so 'root' model(s) will be located in the reverse relationship.

        list ($targetModelClass, $reverseRelName) =
            $this->getModelSchemes()->getReverseRelationship($this->getModelClass(), $relationshipName);

        $builder = $this
            ->createBuilder($targetModelClass)
            ->selectModelFields()
            ->fromModelTable();

        // 'root' filters would be applied to the data in the reverse relationship ...
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $sorts   = $this->getSorts();
            $this->areFiltersWithAnd() ?
                $builder->addRelationshipFiltersAndSortsWithAnd($reverseRelName, $filters, $sorts) :
                $builder->addRelationshipFiltersAndSortsWithOr($reverseRelName, $filters, $sorts);
        }
        // ... and the input filters to actual data we select
        if ($relationshipFilters !== null) {
            $builder->addFiltersWithAndToAlias($relationshipFilters);
        }
        if ($relationshipSorts !== null) {
            $builder->addSorts($relationshipSorts);
        }

        $this->applyPaging($builder);

        // While joining tables we select distinct rows.
        $builder->distinct();

        return $this->builderOnReadRelationship($builder);
    }

    /**
     * @inheritdoc
     */
    public function indexRelationship(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface {
        // depending on the relationship type we expect the result to be either single resource or a collection
        $relationshipType = $this->getModelSchemes()->getRelationshipType($this->getModelClass(), $name);
        $isExpectMany     = $relationshipType === RelationshipTypes::HAS_MANY ||
            $relationshipType === RelationshipTypes::BELONGS_TO_MANY;

        $builder = $this->createReadRelationshipBuilder($name, $relationshipFilters, $relationshipSorts);

        $modelClass = $builder->getModelClass();
        $data       = $isExpectMany === true ?
            $this->fetchResources($builder, $modelClass) : $this->fetchResource($builder, $modelClass);

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function readRelationship(
        $index,
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface {
        return $this->withIndexFilter($index)->indexRelationship($name, $relationshipFilters, $relationshipSorts);
    }

    /**
     * @inheritdoc
     */
    public function hasInRelationship($parentId, string $name, $childId): bool
    {
        if ($parentId !== null && is_scalar($parentId) === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }
        if ($childId !== null && is_scalar($childId) === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        $parentPkName  = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        $parentFilters = [$parentPkName => [FilterParameterInterface::OPERATION_EQUALS => [$parentId]]];
        list($childClass) = $this->getModelSchemes()->getReverseRelationship($this->getModelClass(), $name);
        $childPkName  = $this->getModelSchemes()->getPrimaryKey($childClass);
        $childFilters = [$childPkName => [FilterParameterInterface::OPERATION_EQUALS => [$childId]]];

        $data = $this
            ->clearBuilderParameters()
            ->clearFetchParameters()
            ->withFilters($parentFilters)
            ->indexRelationship($name, $childFilters);

        $has = empty($data->getData()) === false;

        return $has;
    }

    /**
     * @inheritdoc
     */
    public function delete(): int
    {
        $deleted = $this->createDeleteModelBuilder()->execute();

        $this->clearFetchParameters();

        return (int)$deleted;
    }

    /**
     * @inheritdoc
     */
    public function remove($index): bool
    {
        return $this->withIndexFilter($index)->delete() > 0;
    }

    /**
     * @inheritdoc
     */
    public function create($index, iterable $attributes, iterable $toMany): string
    {
        if ($index !== null && is_int($index) === false && is_string($index) === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        $allowedChanges = $this->filterAttributesOnCreate($index, $attributes);
        $saveMain       = $this
            ->createBuilder($this->getModelClass())
            ->createModel($allowedChanges);
        $saveMain       = $this->builderSaveResourceOnCreate($saveMain);
        $saveMain->getSQL(); // prepare

        $this->clearBuilderParameters()->clearFetchParameters();

        $this->inTransaction(function () use ($saveMain, $toMany, &$index) {
            $saveMain->execute();

            // if no index given will use last insert ID as index
            $index !== null ?: $index = $saveMain->getConnection()->lastInsertId();

            $inserted = 0;
            foreach ($toMany as $relationshipName => $secondaryIds) {
                $secondaryIdBindName = ':secondaryId';
                $saveToMany          = $this->builderSaveRelationshipOnCreate(
                    $relationshipName,
                    $this
                        ->createBuilderFromConnection($saveMain->getConnection(), $this->getModelClass())
                        ->prepareCreateInToManyRelationship($relationshipName, $index, $secondaryIdBindName)
                );
                foreach ($secondaryIds as $secondaryId) {
                    $inserted += (int)$saveToMany->setParameter($secondaryIdBindName, $secondaryId)->execute();
                }
            }
        });

        return $index;
    }

    /**
     * @inheritdoc
     */
    public function update($index, iterable $attributes, iterable $toMany): int
    {
        if (is_int($index) === false && is_string($index) === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        $updated        = 0;
        $pkName         = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
        $filters        = [
            $pkName => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ];
        $allowedChanges = $this->filterAttributesOnUpdate($attributes);
        $saveMain       = $this
            ->createBuilder($this->getModelClass())
            ->updateModels($allowedChanges)
            ->addFiltersWithAndToTable($filters);
        $saveMain       = $this->builderSaveResourceOnUpdate($saveMain);
        $saveMain->getSQL(); // prepare

        $this->clearBuilderParameters()->clearFetchParameters();

        $this->inTransaction(function () use ($saveMain, $toMany, $index, &$updated) {
            $updated = $saveMain->execute();

            foreach ($toMany as $relationshipName => $secondaryIds) {
                $cleanToMany = $this->builderCleanRelationshipOnUpdate(
                    $relationshipName,
                    $this
                        ->createBuilderFromConnection($saveMain->getConnection(), $this->getModelClass())
                        ->clearToManyRelationship($relationshipName, $index)
                );
                $cleanToMany->execute();

                $secondaryIdBindName = ':secondaryId';
                $saveToMany          = $this->builderSaveRelationshipOnUpdate(
                    $relationshipName,
                    $this
                        ->createBuilderFromConnection($saveMain->getConnection(), $this->getModelClass())
                        ->prepareCreateInToManyRelationship($relationshipName, $index, $secondaryIdBindName)
                );
                foreach ($secondaryIds as $secondaryId) {
                    $updated += (int)$saveToMany->setParameter($secondaryIdBindName, $secondaryId)->execute();
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
    public function inTransaction(Closure $closure): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $isOk = ($closure() === false ? null : true);
        } finally {
            isset($isOk) === true ? $connection->commit() : $connection->rollBack();
        }
    }

    /**
     * @inheritdoc
     */
    public function fetchResources(
        QueryBuilder $builder = null,
        string $modelClass = null
    ): PaginatedDataInterface {
        if ($builder === null && $modelClass === null) {
            $builder    = $this->createIndexModelBuilder();
            $modelClass = $builder->getModelClass();
        }

        $data = $this->fetchPaginatedResourcesWithoutRelationships($builder, $modelClass);

        if ($this->hasIncludes() === true) {
            $this->loadRelationships($data);
            $this->clearFetchParameters();
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function fetchResource(
        QueryBuilder $builder = null,
        string $modelClass = null
    ): PaginatedDataInterface {
        if ($builder === null && $modelClass === null) {
            $builder    = $this->createIndexModelBuilder();
            $modelClass = $builder->getModelClass();
        }

        $data = $this->getFactory()->createPaginatedData(
            $this->fetchResourceWithoutRelationships($builder, $modelClass)
        )->markAsSingleItem();

        if ($this->hasIncludes() === true) {
            $this->loadRelationships($data);
            $this->clearFetchParameters();
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function fetchRow(
        QueryBuilder $builder = null,
        string $modelClass = null
    ): ?array {
        if ($builder === null && $modelClass === null) {
            $builder    = $this->createIndexModelBuilder();
            $modelClass = $builder->getModelClass();
        }

        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
        $platform  = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($modelClass);

        $model = null;
        if (($attributes = $statement->fetch()) !== false) {
            $model = $this->readRowFromAssoc($attributes, $typeNames, $platform);
        }

        $this->clearFetchParameters();

        return $model;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     *
     * @return PaginatedDataInterface
     */
    private function fetchPaginatedResourcesWithoutRelationships(
        QueryBuilder $builder,
        string $modelClass
    ): PaginatedDataInterface {
        list($models, $hasMore, $limit, $offset) = $this->fetchResourceCollection($builder, $modelClass);

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
     * @param string       $modelClass
     *
     * @return mixed|null
     */
    protected function fetchResourceWithoutRelationships(QueryBuilder $builder, string $modelClass)
    {
        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
        $platform  = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($modelClass);

        $model = null;
        if (($attributes = $statement->fetch()) !== false) {
            $model = $this->readResourceFromAssoc($modelClass, $attributes, $typeNames, $platform);
        }

        return $model;
    }

    /**
     * @param QueryBuilder $builder
     * @param string       $modelClass
     *
     * @return array
     */
    protected function fetchResourceCollection(QueryBuilder $builder, string $modelClass): array
    {
        $platform  = $builder->getConnection()->getDatabasePlatform();
        $typeNames = $this->getModelSchemes()->getAttributeTypes($modelClass);

        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);

        $models           = [];
        $counter          = 0;
        $hasMoreThanLimit = false;
        $limit            = $builder->getMaxResults() !== null ? $builder->getMaxResults() - 1 : null;
        while (($attributes = $statement->fetch()) !== false) {
            $counter++;
            if ($limit !== null && $counter > $limit) {
                $hasMoreThanLimit = true;
                break;
            }
            $models[] = $this->readResourceFromAssoc($modelClass, $attributes, $typeNames, $platform);
        }

        return [$models, $hasMoreThanLimit, $limit, $builder->getFirstResult()];
    }

    /**
     * @param null|string $index
     * @param iterable    $attributes
     *
     * @return iterable
     */
    protected function filterAttributesOnCreate(?string $index, iterable $attributes): iterable
    {
        if ($index !== null) {
            $pkName = $this->getModelSchemes()->getPrimaryKey($this->getModelClass());
            yield $pkName => $index;
        }

        $knownAttrAndTypes = $this->getModelSchemes()->getAttributeTypes($this->getModelClass());
        foreach ($attributes as $attribute => $value) {
            if (array_key_exists($attribute, $knownAttrAndTypes) === true) {
                yield $attribute => $value;
            }
        }
    }

    /**
     * @param iterable $attributes
     *
     * @return iterable
     */
    protected function filterAttributesOnUpdate(iterable $attributes): iterable
    {
        $knownAttrAndTypes = $this->getModelSchemes()->getAttributeTypes($this->getModelClass());
        foreach ($attributes as $attribute => $value) {
            if (array_key_exists($attribute, $knownAttrAndTypes) === true) {
                yield $attribute => $value;
            }
        }
    }

    /**
     * @param TagStorageInterface   $modelsAtPath
     * @param ArrayObject           $classAtPath
     * @param ModelStorageInterface $deDup
     * @param string                $parentsPath
     * @param array                 $childRelationships
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadRelationshipsLayer(
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

        $pkName = $this->getModelSchemes()->getPrimaryKey($parentClass);

        foreach ($childRelationships as $name) {
            $childrenPath = $parentsPath !== static::ROOT_PATH ? $parentsPath . static::PATH_SEPARATOR . $name : $name;

            $relationshipType = $this->getModelSchemes()->getRelationshipType($parentClass, $name);
            list ($targetModelClass, $reverseRelName) =
                $this->getModelSchemes()->getReverseRelationship($parentClass, $name);

            $builder = $this
                ->createBuilder($targetModelClass)
                ->selectModelFields()
                ->fromModelTable();

            $classAtPath[$childrenPath] = $targetModelClass;

            switch ($relationshipType) {
                case RelationshipTypes::BELONGS_TO:
                    foreach ($parents as $parent) {
                        $clonedBuilder = (clone $builder)->addRelationshipFiltersAndSortsWithAnd(
                            $reverseRelName,
                            [$pkName => [FilterParameterInterface::OPERATION_EQUALS => [$parent->{$pkName}]]],
                            []
                        );
                        $child         = $deDup->register($this->fetchResourceWithoutRelationships(
                            $clonedBuilder,
                            $clonedBuilder->getModelClass()
                        ));
                        if ($child !== null) {
                            $modelsAtPath->register($child, $childrenPath);
                        }
                        $parent->{$name} = $child;
                    }
                    break;
                case RelationshipTypes::HAS_MANY:
                case RelationshipTypes::BELONGS_TO_MANY:
                    list ($queryOffset, $queryLimit) = $this->getPaginationStrategy()
                        ->getParameters($rootClass, $parentClass, $parentsPath, $name);
                    $builder->setFirstResult($queryOffset)->setMaxResults($queryLimit + 1);
                    foreach ($parents as $parent) {
                        $clonedBuilder = (clone $builder)->addRelationshipFiltersAndSortsWithAnd(
                            $reverseRelName,
                            [$pkName => [FilterParameterInterface::OPERATION_EQUALS => [$parent->{$pkName}]]],
                            []
                        );
                        $children      = $this->fetchPaginatedResourcesWithoutRelationships(
                            $clonedBuilder,
                            $clonedBuilder->getModelClass()
                        );

                        $deDupedChildren = [];
                        foreach ($children->getData() as $child) {
                            $child = $deDup->register($child);
                            $modelsAtPath->register($child, $childrenPath);
                            if ($child !== null) {
                                $deDupedChildren[] = $child;
                            }
                        }

                        $paginated = $this->getFactory()
                            ->createPaginatedData($deDupedChildren)
                            ->markAsCollection()
                            ->setOffset($children->getOffset())
                            ->setLimit($children->getLimit());
                        $children->hasMoreItems() === true ?
                            $paginated->markHasMoreItems() : $paginated->markHasNoMoreItems();

                        $parent->{$name} = $paginated;
                    }
                    break;
            }
        }
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function getMessage(string $message): string
    {
        /** @var FormatterFactoryInterface $factory */
        $factory   = $this->getContainer()->get(FormatterFactoryInterface::class);
        $formatter = $factory->createFormatter(Messages::RESOURCES_NAMESPACE);
        $result    = $formatter->formatMessage($message);

        return $result;
    }

    /**
     * @param string           $class
     * @param array            $attributes
     * @param Type[]           $typeNames
     * @param AbstractPlatform $platform
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function readResourceFromAssoc(
        string $class,
        array $attributes,
        array $typeNames,
        AbstractPlatform $platform
    ) {
        $instance = new $class();
        foreach ($this->readTypedAttributes($attributes, $typeNames, $platform) as $name => $value) {
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
        foreach ($this->readTypedAttributes($attributes, $typeNames, $platform) as $name => $value) {
            $row[$name] = $value;
        }

        return $row;
    }

    /**
     * @param iterable         $attributes
     * @param array            $typeNames
     * @param AbstractPlatform $platform
     *
     * @return iterable
     */
    private function readTypedAttributes(iterable $attributes, array $typeNames, AbstractPlatform $platform): iterable
    {
        foreach ($attributes as $name => $value) {
            yield $name => (array_key_exists($name, $typeNames) === true ?
                Type::getType($typeNames[$name])->convertToPHPValue($value, $platform) : $value);
        }
    }
}

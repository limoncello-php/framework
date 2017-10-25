<?php namespace Limoncello\Flute\Contracts\Api;

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
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;

/**
 * @package Limoncello\Flute
 */
interface CrudInterface
{
    /**
     * @return self
     */
    public function combineWithAnd(): self;

    /**
     * @return self
     */
    public function combineWithOr(): self;

    /**
     * @param iterable $filterParameters
     *
     * @return self
     */
    public function withFilters(iterable $filterParameters): self;

    /**
     * @param string|int $index
     *
     * @return self
     */
    public function withIndexFilter($index): self;

    /**
     * @param iterable $sortingParameters
     *
     * @return self
     */
    public function withSorts(iterable $sortingParameters): self;

    /**
     * @param iterable $includePaths
     *
     * @return self
     */
    public function withIncludes(iterable $includePaths): self;

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return self
     */
    public function withPaging(int $offset, int $limit): self;

    /**
     * @param string   $name
     * @param iterable $filters
     *
     * @return CrudInterface
     */
    public function withRelationshipFilters(string $name, iterable $filters): self;

    /**
     * @param string   $name
     * @param iterable $sorts
     *
     * @return CrudInterface
     */
    public function withRelationshipSorts(string $name, iterable $sorts): self;

    /**
     * @return QueryBuilder
     */
    public function getIndexBuilder(): QueryBuilder;

    /**
     * @return QueryBuilder
     */
    public function getDeleteBuilder(): QueryBuilder;

    /**
     * @param QueryBuilder|null $builder
     * @param string|null       $modelClass
     *
     * @return PaginatedDataInterface
     */
    public function fetchResources(QueryBuilder $builder = null, string $modelClass = null): PaginatedDataInterface;

    /**
     * @param QueryBuilder|null $builder
     * @param string|null       $modelClass
     *
     * @return PaginatedDataInterface
     */
    public function fetchResource(QueryBuilder $builder = null, string $modelClass = null): PaginatedDataInterface;

    /**
     * @param QueryBuilder|null $builder
     * @param string|null       $modelClass
     *
     * @return array|null
     */
    public function fetchRow(QueryBuilder $builder = null, string $modelClass = null): ?array;

    /**
     * @return PaginatedDataInterface
     */
    public function index(): PaginatedDataInterface;

    /**
     * @param null|string $index
     *
     * @return PaginatedDataInterface
     */
    public function read($index): PaginatedDataInterface;

    /**
     * @return int|null
     */
    public function count(): ?int;

    /**
     * @return int
     */
    public function delete(): int;

    /**
     * @param null|string $index
     *
     * @return bool
     */
    public function remove($index): bool;

    /**
     * @param null|string $index
     * @param iterable    $attributes
     * @param iterable    $toMany
     *
     * @return string
     */
    public function create($index, iterable $attributes, iterable $toMany): string;

    /**
     * @param int|string $index
     * @param iterable   $attributes
     * @param iterable   $toMany
     *
     * @return int
     */
    public function update($index, iterable $attributes, iterable $toMany): int;

    /**
     * @param string        $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return PaginatedDataInterface
     */
    public function indexRelationship(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface;

    /**
     * @param int|string    $index
     * @param string        $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     *
     * @return PaginatedDataInterface
     */
    public function readRelationship(
        $index,
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): PaginatedDataInterface;

    /**
     * @param int|string $parentId
     * @param string     $name
     * @param int|string $childId
     *
     * @return bool
     */
    public function hasInRelationship($parentId, string $name, $childId): bool;
}

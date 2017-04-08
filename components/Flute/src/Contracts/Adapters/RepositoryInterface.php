<?php namespace Limoncello\Flute\Contracts\Adapters;

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
use Doctrine\DBAL\Query\QueryBuilder;
use Limoncello\Flute\Contracts\Http\Query\SortParameterInterface;
use Limoncello\Flute\Http\Query\FilterParameterCollection;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * @package Limoncello\Flute
 */
interface RepositoryInterface
{
    /**
     * @param string $modelClass
     *
     * @return QueryBuilder
     */
    public function index(string $modelClass): QueryBuilder;

    /**
     * @param string $modelClass
     * @param array  $attributes
     *
     * @return QueryBuilder
     */
    public function create(string $modelClass, array $attributes): QueryBuilder;

    /**
     * @param string $modelClass
     * @param string $indexBind
     *
     * @return QueryBuilder
     */
    public function read(string $modelClass, string $indexBind): QueryBuilder;

    /**
     * @param string $modelClass
     * @param string $indexBind
     * @param string $relationshipName
     *
     * @return array [$builder, $resultClass, $relationshipType]
     */
    public function readRelationship(string $modelClass, string $indexBind, string $relationshipName): array;

    /**
     * @param string $modelClass
     * @param string $parentIndexBind
     * @param string $relationshipName
     * @param string $childIndexBind
     *
     * @return array [$builder, $resultClass, $relationshipType]
     */
    public function hasInRelationship(
        string $modelClass,
        string $parentIndexBind,
        string $relationshipName,
        string $childIndexBind
    ): array;

    /**
     * @param string     $modelClass
     * @param int|string $index
     * @param array      $attributes
     *
     * @return QueryBuilder
     */
    public function update(string $modelClass, $index, array $attributes): QueryBuilder;

    /**
     * @param string $modelClass
     * @param string $indexBind
     *
     * @return QueryBuilder
     */
    public function delete(string $modelClass, string $indexBind): QueryBuilder;

    /**
     * @param string $modelClass
     * @param string $indexBind
     * @param string $name
     * @param string $otherIndexBind
     *
     * @return QueryBuilder
     */
    public function createToManyRelationship(
        string $modelClass,
        string $indexBind,
        string $name,
        string $otherIndexBind
    ): QueryBuilder;

    /**
     * @param string $modelClass
     * @param string $indexBind
     * @param string $name
     *
     * @return QueryBuilder
     */
    public function cleanToManyRelationship(string $modelClass, string $indexBind, string $name): QueryBuilder;

    /**
     * @param ErrorCollection           $errors
     * @param QueryBuilder              $builder
     * @param string                    $modelClass
     * @param FilterParameterCollection $filterParams
     *
     * @return void
     */
    public function applyFilters(
        ErrorCollection $errors,
        QueryBuilder $builder,
        string $modelClass,
        FilterParameterCollection $filterParams
    );

    /**
     * @param QueryBuilder             $builder
     * @param string                   $modelClass
     * @param SortParameterInterface[] $sortParams
     *
     * @return void
     */
    public function applySorting(QueryBuilder $builder, string $modelClass, array $sortParams);

    /**
     * @param string $modelClass
     * @param string $relationshipName
     *
     * @return array [$builder, $resultClass, $relationshipType, $table, $column]
     */
    public function createRelationshipBuilder(string $modelClass, string $relationshipName): array;

    /**
     * @param string $modelClass
     *
     * @return QueryBuilder
     */
    public function count(string $modelClass): QueryBuilder;

    /**
     * @return Connection
     */
    public function getConnection(): Connection;

    /**
     * @param string $modelClass
     *
     * @return array
     */
    public function getColumns(string $modelClass): array;

    /**
     * @param string $table
     *
     * @return string
     */
    public function buildTableName(string $table): string;

    /**
     * @param string      $table
     * @param string      $column
     * @param null|string $modelClass
     *
     * @return string
     */
    public function buildColumnName(string $table, string $column, string $modelClass = null): string;
}

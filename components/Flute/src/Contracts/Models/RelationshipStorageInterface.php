<?php namespace Limoncello\Flute\Contracts\Models;

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

/**
 * @package Limoncello\Flute
 */
interface RelationshipStorageInterface
{
    /** Relationship type */
    const RELATIONSHIP_TYPE_NONE = 0;

    /** Relationship type */
    const RELATIONSHIP_TYPE_TO_ONE = 1;

    /** Relationship type */
    const RELATIONSHIP_TYPE_TO_MANY = 2;

    /**
     * @param mixed  $model
     * @param string $relationship
     * @param mixed  $one
     *
     * @return bool
     */
    public function addToOneRelationship($model, string $relationship, $one): bool;

    /**
     * @param mixed    $model
     * @param string   $relationship
     * @param mixed[]  $many
     * @param bool     $hasMore
     * @param int|null $offset
     * @param int|null $size
     *
     * @return bool
     */
    public function addToManyRelationship(
        $model,
        string $relationship,
        array $many,
        bool $hasMore,
        int $offset = null,
        $size = null
    ): bool;

    /**
     * @param mixed  $model
     * @param string $relationship
     *
     * @return bool
     */
    public function hasRelationship($model, string $relationship): bool;

    /**
     * @param mixed  $model
     * @param string $relationship
     *
     * @return int
     */
    public function getRelationshipType($model, string $relationship): int;

    /**
     * @param mixed  $model
     * @param string $relationship
     *
     * @return PaginatedDataInterface|null
     */
    public function getRelationship($model, string $relationship): ?PaginatedDataInterface;
}

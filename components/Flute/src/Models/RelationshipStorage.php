<?php namespace Limoncello\Flute\Models;

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

use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Models\RelationshipStorageInterface;

/**
 * @package Limoncello\Flute
 */
class RelationshipStorage implements RelationshipStorageInterface
{
    /** Internal data index */
    const IDX_DATA = 0;

    /** Internal data index */
    const IDX_TYPE = 1;

    /**
     * @var array
     */
    private $relationships;

    /** @var FactoryInterface */
    private $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function addToOneRelationship($model, string $relationship, $one): bool
    {
        $uniqueId = spl_object_hash($model);
        if (isset($this->relationships[$uniqueId][$relationship]) === false) {
            $this->relationships[$uniqueId][$relationship] = [
                self::IDX_DATA => $this->factory->createPaginatedData($one)->markAsSingleItem(),
                self::IDX_TYPE => self::RELATIONSHIP_TYPE_TO_ONE,
            ];

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function addToManyRelationship(
        $model,
        string $relationship,
        array $many,
        bool $hasMore,
        int $offset = null,
        $size = null
    ): bool {
        $uniqueId = spl_object_hash($model);
        if (isset($this->relationships[$uniqueId][$relationship]) === false) {
            $data = $this->factory->createPaginatedData($many)
                ->markAsCollection()
                ->setOffset($offset)
                ->setLimit($size);
            $hasMore === true ? $data->markHasMoreItems() : $data->markHasNoMoreItems();
            $this->relationships[$uniqueId][$relationship] = [
                self::IDX_DATA => $data,
                self::IDX_TYPE => self::RELATIONSHIP_TYPE_TO_MANY,
            ];

            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function hasRelationship($model, string $relationship): bool
    {
        return $this->getRelationshipType($model, $relationship) !== self::RELATIONSHIP_TYPE_NONE;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType($model, string $relationship): int
    {
        $result = self::RELATIONSHIP_TYPE_NONE;

        $uniqueId = spl_object_hash($model);
        if (isset($this->relationships[$uniqueId][$relationship][self::IDX_TYPE]) === true) {
            $result = $this->relationships[$uniqueId][$relationship][self::IDX_TYPE];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRelationship($model, string $relationship): ?PaginatedDataInterface
    {
        $uniqueId = spl_object_hash($model);
        $result = $this->relationships[$uniqueId][$relationship][self::IDX_DATA];

        return $result;
    }
}

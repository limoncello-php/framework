<?php namespace Limoncello\Flute\Http\Query;

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

use Limoncello\Flute\Contracts\Http\Query\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface as JsonLibrarySortParameterInterface;

/**
 * @package Limoncello\Flute
 */
class SortParameter implements SortParameterInterface
{
    /**
     * @var JsonLibrarySortParameterInterface
     */
    private $libSortParam;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $isRelationship;

    /**
     * @var int|null
     */
    private $relationshipType = null;

    /**
     * @param JsonLibrarySortParameterInterface $sortParam
     * @param string                            $name
     * @param bool                              $isRelationship
     * @param int|null                          $relationshipType
     */
    public function __construct(
        JsonLibrarySortParameterInterface $sortParam,
        string $name,
        bool $isRelationship,
        int $relationshipType = null
    ) {
        $this->libSortParam   = $sortParam;
        $this->name           = $name;
        $this->isRelationship = $isRelationship;
        if ($isRelationship === true) {
            $this->relationshipType = $relationshipType;
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getOriginalName(): string
    {
        return $this->libSortParam->getField();
    }

    /**
     * @inheritdoc
     */
    public function isAscending(): bool
    {
        return $this->libSortParam->isAscending();
    }

    /**
     * @inheritdoc
     */
    public function isRelationship(): bool
    {
        return $this->isRelationship;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType()
    {
        return $this->relationshipType;
    }
}

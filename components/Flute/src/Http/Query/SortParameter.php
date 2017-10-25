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

use Limoncello\Flute\Contracts\Http\Query\AttributeInterface;
use Limoncello\Flute\Contracts\Http\Query\RelationshipInterface;
use Limoncello\Flute\Contracts\Http\Query\SortParameterInterface;

/**
 * @package Limoncello\Flute
 */
class SortParameter implements SortParameterInterface
{
    /**
     * @var AttributeInterface
     */
    private $attribute;

    /**
     * @var RelationshipInterface|null
     */
    private $relationship;

    /**
     * @var bool
     */
    private $isAsc;

    /**
     * @param AttributeInterface         $attribute
     * @param bool                       $isAsc
     * @param RelationshipInterface|null $relationship
     */
    public function __construct(
        AttributeInterface $attribute,
        bool $isAsc,
        RelationshipInterface $relationship = null
    ) {
        $this->attribute    = $attribute;
        $this->relationship = $relationship;
        $this->isAsc        = $isAsc;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute(): AttributeInterface
    {
        return $this->attribute;
    }

    /**
     * @inheritdoc
     */
    public function getRelationship(): ?RelationshipInterface
    {
        return $this->relationship;
    }

    /**
     * @inheritdoc
     */
    public function isAsc(): bool
    {
        return $this->isAsc;
    }

    /**
     * @inheritdoc
     */
    public function isDesc(): bool
    {
        return !$this->isAsc;
    }
}

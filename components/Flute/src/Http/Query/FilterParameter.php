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

use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;

/**
 * @package Limoncello\Flute
 */
class FilterParameter implements FilterParameterInterface
{
    /**
     * @var string
     */
    private $originalName;

    /**
     * @var string|null
     */
    private $relationshipName;

    /**
     * @var string|null
     */
    private $attributeName;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int|null
     */
    private $relationshipType = null;

    /**
     * @param string      $originalName
     * @param string|null $relationshipName
     * @param string|null $attributeName
     * @param mixed       $value
     * @param int|null    $relationshipType
     */
    public function __construct(
        $originalName,
        $relationshipName,
        $attributeName,
        $value,
        $relationshipType = null
    ) {
        $this->originalName     = $originalName;
        $this->relationshipName = $relationshipName;
        $this->attributeName    = $attributeName;
        $this->value            = $value;
        if ($relationshipName !== null) {
            $this->relationshipType = $relationshipType;
        }
    }

    /**
     * @inheritdoc
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipName()
    {
        return $this->relationshipName;
    }

    /**
     * @inheritdoc
     */
    public function getAttributeName()
    {
        return $this->attributeName;
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function isForAttribute()
    {
        return $this->getAttributeName() !== null;
    }

    /**
     * @inheritdoc
     */
    public function isForRelationship()
    {
        return $this->getRelationshipName() !== null;
    }

    /**
     * @inheritdoc
     */
    public function isForAttributeInRelationship()
    {
        return $this->isForAttribute() === true && $this->isForRelationship() === true;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType()
    {
        return $this->relationshipType;
    }
}

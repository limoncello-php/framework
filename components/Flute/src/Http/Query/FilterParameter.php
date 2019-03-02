<?php declare (strict_types = 1);

namespace Limoncello\Flute\Http\Query;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Http\Query\RelationshipInterface;

/**
 * @package Limoncello\Flute
 */
class FilterParameter implements FilterParameterInterface
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
     * @var iterable
     */
    private $operationsWithArguments;

    /**
     * @param AttributeInterface         $attribute
     * @param iterable                   $operationsWithArgs
     * @param RelationshipInterface|null $relationship
     */
    public function __construct(
        AttributeInterface $attribute,
        iterable $operationsWithArgs,
        RelationshipInterface $relationship = null
    ) {
        $this->attribute               = $attribute;
        $this->relationship            = $relationship;
        $this->operationsWithArguments = $operationsWithArgs;
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
    public function getOperationsWithArguments(): iterable
    {
        return $this->operationsWithArguments;
    }
}

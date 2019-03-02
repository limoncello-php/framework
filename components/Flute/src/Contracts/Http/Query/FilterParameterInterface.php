<?php declare (strict_types = 1);

namespace Limoncello\Flute\Contracts\Http\Query;

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

/**
 * @package Limoncello\Flute
 */
interface FilterParameterInterface
{
    /** Filter operation */
    public const OPERATION_EQUALS = 0;

    /** Filter operation */
    public const OPERATION_NOT_EQUALS = self::OPERATION_EQUALS + 1;

    /** Filter operation */
    public const OPERATION_LESS_THAN = self::OPERATION_NOT_EQUALS + 1;

    /** Filter operation */
    public const OPERATION_LESS_OR_EQUALS = self::OPERATION_LESS_THAN + 1;

    /** Filter operation */
    public const OPERATION_GREATER_THAN = self::OPERATION_LESS_OR_EQUALS + 1;

    /** Filter operation */
    public const OPERATION_GREATER_OR_EQUALS = self::OPERATION_GREATER_THAN + 1;

    /** Filter operation */
    public const OPERATION_LIKE = self::OPERATION_GREATER_OR_EQUALS + 1;

    /** Filter operation */
    public const OPERATION_NOT_LIKE = self::OPERATION_LIKE + 1;

    /** Filter operation */
    public const OPERATION_IN = self::OPERATION_NOT_LIKE + 1;

    /** Filter operation */
    public const OPERATION_NOT_IN = self::OPERATION_IN + 1;

    /** Filter operation */
    public const OPERATION_IS_NULL = self::OPERATION_NOT_IN + 1;

    /** Filter operation */
    public const OPERATION_IS_NOT_NULL = self::OPERATION_IS_NULL + 1;

    /** Filter operation */
    public const OPERATION_LAST = self::OPERATION_IS_NOT_NULL;

    /**
     * @return AttributeInterface
     */
    public function getAttribute(): AttributeInterface;

    /**
     * @return RelationshipInterface|null
     */
    public function getRelationship(): ?RelationshipInterface;

    /**
     * @return iterable
     */
    public function getOperationsWithArguments(): iterable;
}

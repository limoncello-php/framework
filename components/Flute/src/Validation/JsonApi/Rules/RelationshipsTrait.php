<?php namespace Limoncello\Flute\Validation\JsonApi\Rules;

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

use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Generic\AndOperator;

/**
 * @package Limoncello\Flute
 */
trait RelationshipsTrait
{
    /**
     * @param string             $type
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    public static function toOneRelationship(string $type, RuleInterface $rule = null): RuleInterface
    {
        $primary = new ToOneRelationshipTypeChecker($type);

        return $rule === null ? $primary : new AndOperator($primary, $rule);
    }

    /**
     * @param string             $type
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    public static function toManyRelationship(string $type, RuleInterface $rule = null): RuleInterface
    {
        $primary = new ToManyRelationshipTypeChecker($type);

        return $rule === null ? $primary : new AndOperator($primary, $rule);
    }
}

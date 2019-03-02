<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\Rules;

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

use Limoncello\Flute\Validation\JsonApi\Rules\ExistInDbTableMultipleWithDoctrineRule;
use Limoncello\Flute\Validation\JsonApi\Rules\ExistInDbTableSingleWithDoctrineRule;
use Limoncello\Flute\Validation\JsonApi\Rules\UniqueInDbTableSingleWithDoctrineRule;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Generic\AndOperator;

/**
 * @package Limoncello\Flute
 */
trait DatabaseRulesTrait
{
    /**
     * @param string             $tableName
     * @param string             $primaryName
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function exists(string $tableName, string $primaryName, RuleInterface $next = null): RuleInterface
    {
        $primary = new ExistInDbTableSingleWithDoctrineRule($tableName, $primaryName);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }

    /**
     * @param string             $tableName
     * @param string             $primaryName
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function existAll(string $tableName, string $primaryName, RuleInterface $next = null): RuleInterface
    {
        $primary = new ExistInDbTableMultipleWithDoctrineRule($tableName, $primaryName);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }

    /**
     * @param string             $tableName
     * @param string             $primaryName
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function unique(string $tableName, string $primaryName, RuleInterface $next = null): RuleInterface
    {
        $primary = new UniqueInDbTableSingleWithDoctrineRule($tableName, $primaryName);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }
}

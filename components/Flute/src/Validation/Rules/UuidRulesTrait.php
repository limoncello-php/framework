<?php declare(strict_types=1);

namespace Limoncello\Flute\Validation\Rules;

/**
 * Copyright 2020 info@lolltec.com
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
use Limoncello\Flute\Validation\JsonApi\Rules\IsValidUuidRule;

/**
 * @package Limoncello\Flute
 */
trait UuidRulesTrait
{
    /**
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function isUuid(RuleInterface $next = null): RuleInterface
    {
        $primary = new IsValidUuidRule();

        return $next === null ? $primary : new AndOperator($primary, $next);
    }
}

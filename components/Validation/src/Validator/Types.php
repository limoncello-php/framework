<?php declare(strict_types=1);

namespace Limoncello\Validation\Validator;

/**
 * Copyright 2015-2020 info@neomerx.com
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
use Limoncello\Validation\Rules\Types\IsArray;
use Limoncello\Validation\Rules\Types\IsBool;
use Limoncello\Validation\Rules\Types\IsDateTime;
use Limoncello\Validation\Rules\Types\IsFloat;
use Limoncello\Validation\Rules\Types\IsInt;
use Limoncello\Validation\Rules\Types\IsNumeric;
use Limoncello\Validation\Rules\Types\IsString;

/**
 * @package Limoncello\Validation
 */
trait Types
{
    /**
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function isArray(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsArray() : new AndOperator(new IsArray(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isString(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsString() : new AndOperator(new IsString(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isBool(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsBool() : new AndOperator(new IsBool(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isInt(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsInt() : new AndOperator(new IsInt(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isFloat(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsFloat() : new AndOperator(new IsFloat(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isNumeric(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsNumeric() : new AndOperator(new IsNumeric(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isDateTime(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsDateTime() : new AndOperator(new IsDateTime(), $next);
    }
}

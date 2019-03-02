<?php declare(strict_types=1);

namespace Limoncello\Validation\Validator;

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

use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Generic\AndOperator;
use Limoncello\Validation\Rules\Types\AsArray;
use Limoncello\Validation\Rules\Types\AsBool;
use Limoncello\Validation\Rules\Types\AsDateTime;
use Limoncello\Validation\Rules\Types\AsFloat;
use Limoncello\Validation\Rules\Types\AsInt;
use Limoncello\Validation\Rules\Types\AsNumeric;
use Limoncello\Validation\Rules\Types\AsString;

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
        return $next === null ? new AsArray() : new AndOperator(new AsArray(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isString(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsString() : new AndOperator(new AsString(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isBool(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsBool() : new AndOperator(new AsBool(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isInt(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsInt() : new AndOperator(new AsInt(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isFloat(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsFloat() : new AndOperator(new AsFloat(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isNumeric(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsNumeric() : new AndOperator(new AsNumeric(), $next);
    }

    /**
     * @param RuleInterface $next
     *
     * @return RuleInterface
     */
    protected static function isDateTime(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new AsDateTime() : new AndOperator(new AsDateTime(), $next);
    }
}

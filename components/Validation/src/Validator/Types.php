<?php namespace Limoncello\Validation\Validator;

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
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function isArray(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsArray() : new AndOperator(new AsArray(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isString(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsString() : new AndOperator(new AsString(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isBool(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsBool() : new AndOperator(new AsBool(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isInt(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsInt() : new AndOperator(new AsInt(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isFloat(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsFloat() : new AndOperator(new AsFloat(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isNumeric(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsNumeric() : new AndOperator(new AsNumeric(), $rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function isDateTime(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new AsDateTime() : new AndOperator(new AsDateTime(), $rule);
    }
}

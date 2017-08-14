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
use Limoncello\Validation\Rules\Converters\StringArrayToIntArray;
use Limoncello\Validation\Rules\Converters\StringToBool;
use Limoncello\Validation\Rules\Converters\StringToDateTime;
use Limoncello\Validation\Rules\Converters\StringToFloat;
use Limoncello\Validation\Rules\Converters\StringToInt;
use Limoncello\Validation\Rules\Generic\AndOperator;

/**
 * @package Limoncello\Validation
 */
trait Converters
{
    /**
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function stringToBool(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new StringToBool() : new AndOperator(new StringToBool(), $rule);
    }

    /**
     * @param string             $format
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function stringToDateTime(string $format, RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new StringToDateTime($format) : new AndOperator(new StringToDateTime($format), $rule);
    }

    /**
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function stringToFloat(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new StringToFloat() : new AndOperator(new StringToFloat(), $rule);
    }

    /**
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function stringToInt(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new StringToInt() : new AndOperator(new StringToInt(), $rule);
    }

    /**
     * @param RuleInterface|null $rule
     *
     * @return RuleInterface
     */
    protected static function stringArrayToIntArray(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new StringArrayToIntArray() : new AndOperator(new StringArrayToIntArray(), $rule);
    }
}

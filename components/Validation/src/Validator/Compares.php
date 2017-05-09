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

use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Rules\CallableRule;

/**
 * @package Limoncello\Validation
 */
trait Compares
{
    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function equals($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input === $value;
        }, MessageCodes::EQUALS);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function notEquals($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input !== $value;
        }, MessageCodes::NOT_EQUALS);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function lessThan($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input < $value;
        }, MessageCodes::LESS_THAN);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function lessOrEquals($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input <= $value;
        }, MessageCodes::LESS_OR_EQUALS);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function moreThan($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input > $value;
        }, MessageCodes::MORE_THAN);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function moreOrEquals($value): RuleInterface
    {
        return new CallableRule(function ($input) use ($value) {
            return $input >= $value;
        }, MessageCodes::MORE_OR_EQUALS);
    }
}

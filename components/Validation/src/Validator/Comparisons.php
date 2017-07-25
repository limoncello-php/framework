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

use DateTimeInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Comparisons\DateTimeBetween;
use Limoncello\Validation\Rules\Comparisons\DateTimeEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeLessOrEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeLessThan;
use Limoncello\Validation\Rules\Comparisons\DateTimeMoreOrEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeMoreThan;
use Limoncello\Validation\Rules\Comparisons\DateTimeNotEquals;
use Limoncello\Validation\Rules\Comparisons\NumericBetween;
use Limoncello\Validation\Rules\Comparisons\NumericLessOrEquals;
use Limoncello\Validation\Rules\Comparisons\NumericLessThan;
use Limoncello\Validation\Rules\Comparisons\NumericMoreOrEqualsThan;
use Limoncello\Validation\Rules\Comparisons\NumericMoreThan;
use Limoncello\Validation\Rules\Comparisons\ScalarEquals;
use Limoncello\Validation\Rules\Comparisons\ScalarInValues;
use Limoncello\Validation\Rules\Comparisons\ScalarNotEquals;
use Limoncello\Validation\Rules\Comparisons\StringLengthBetween;
use Limoncello\Validation\Rules\Comparisons\StringLengthMax;
use Limoncello\Validation\Rules\Comparisons\StringLengthMin;
use Limoncello\Validation\Rules\Comparisons\StringRegExp;
use Limoncello\Validation\Rules\Generic\OrOperator;

/**
 * @package Limoncello\Validation
 */
trait Comparisons
{
    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function equals($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ? new DateTimeEquals($value) : new ScalarEquals($value);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function notEquals($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ? new DateTimeNotEquals($value) : new ScalarNotEquals($value);
    }

    /**
     * @param array $scalars
     *
     * @return RuleInterface
     */
    protected static function inValues(array $scalars): RuleInterface
    {
        return new ScalarInValues($scalars);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function lessThan($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ? new DateTimeLessThan($value) : new NumericLessThan($value);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function lessOrEquals($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ? new DateTimeLessOrEquals($value) : new NumericLessOrEquals($value);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function moreThan($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ? new DateTimeMoreThan($value) : new NumericMoreThan($value);
    }

    /**
     * @param mixed $value
     *
     * @return RuleInterface
     */
    protected static function moreOrEquals($value): RuleInterface
    {
        return $value instanceof DateTimeInterface ?
            new DateTimeMoreOrEquals($value) : new NumericMoreOrEqualsThan($value);
    }

    /**
     * @param mixed $lowerLimit
     * @param mixed $upperLimit
     *
     * @return RuleInterface
     */
    protected static function between($lowerLimit, $upperLimit): RuleInterface
    {
        return ($lowerLimit instanceof DateTimeInterface && $upperLimit instanceof DateTimeInterface) ?
            new DateTimeBetween($lowerLimit, $upperLimit) : new NumericBetween($lowerLimit, $upperLimit);
    }

    /**
     * @param int $min
     * @param int $max
     *
     * @return RuleInterface
     */
    protected static function stringLengthBetween(int $min, int $max): RuleInterface
    {
        return new StringLengthBetween($min, $max);
    }

    /**
     * @param int $min
     *
     * @return RuleInterface
     */
    protected static function stringLengthMin(int $min): RuleInterface
    {
        return new StringLengthMin($min);
    }

    /**
     * @param int $max
     *
     * @return RuleInterface
     */
    protected static function stringLengthMax(int $max): RuleInterface
    {
        return new StringLengthMax($max);
    }

    /**
     * @param string $pattern
     *
     * @return RuleInterface
     */
    protected static function regexp(string $pattern): RuleInterface
    {
        return new StringRegExp($pattern);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function nullable(RuleInterface $rule): RuleInterface
    {
        return new OrOperator(static::equals(null), $rule);
    }
}

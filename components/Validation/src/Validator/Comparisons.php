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

use DateTimeInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Comparisons\DateTimeBetween;
use Limoncello\Validation\Rules\Comparisons\DateTimeEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeLessOrEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeLessThan;
use Limoncello\Validation\Rules\Comparisons\DateTimeMoreOrEquals;
use Limoncello\Validation\Rules\Comparisons\DateTimeMoreThan;
use Limoncello\Validation\Rules\Comparisons\DateTimeNotEquals;
use Limoncello\Validation\Rules\Comparisons\IsNotNull;
use Limoncello\Validation\Rules\Comparisons\IsNull;
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
use Limoncello\Validation\Rules\Generic\AndOperator;
use Limoncello\Validation\Rules\Generic\OrOperator;

/**
 * @package Limoncello\Validation
 */
trait Comparisons
{
    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected static function equals($value, RuleInterface $next = null): RuleInterface
    {
        if ($value === null) {
            $rule = new IsNull();
        } elseif ($value instanceof DateTimeInterface) {
            $rule = new DateTimeEquals($value);
        } else {
            $rule = new ScalarEquals($value);
        }

        return $next === null ? $rule : new AndOperator(static::equals($value), $next);
    }

    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected static function notEquals($value, RuleInterface $next = null): RuleInterface
    {
        if ($value === null) {
            $rule = new IsNotNull();
        } elseif ($value instanceof DateTimeInterface) {
            $rule = new DateTimeNotEquals($value);
        } else {
            $rule = new ScalarNotEquals($value);
        }

        return $next === null ? $rule : new AndOperator(static::notEquals($value), $next);
    }

    /**
     * @param array              $scalars
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function inValues(array $scalars, RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new ScalarInValues($scalars) : new AndOperator(static::inValues($scalars), $next);
    }

    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function lessThan($value, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            $value instanceof DateTimeInterface ? new DateTimeLessThan($value) : new NumericLessThan($value) :
            new AndOperator(static::lessThan($value), $next);
    }

    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function lessOrEquals($value, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            $value instanceof DateTimeInterface ? new DateTimeLessOrEquals($value) : new NumericLessOrEquals($value) :
            new AndOperator(static::lessOrEquals($value), $next);
    }

    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function moreThan($value, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            $value instanceof DateTimeInterface ? new DateTimeMoreThan($value) : new NumericMoreThan($value) :
            new AndOperator(static::moreThan($value), $next);
    }

    /**
     * @param mixed              $value
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function moreOrEquals($value, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            ($value instanceof DateTimeInterface ?
                new DateTimeMoreOrEquals($value) : new NumericMoreOrEqualsThan($value)) :
            new AndOperator(static::moreOrEquals($value), $next);
    }

    /**
     * @param mixed              $lowerLimit
     * @param mixed              $upperLimit
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function between($lowerLimit, $upperLimit, RuleInterface $next = null): RuleInterface
    {
        $areLimitsDates = $lowerLimit instanceof DateTimeInterface && $upperLimit instanceof DateTimeInterface;

        return $next === null ?
            ($areLimitsDates ?
                new DateTimeBetween($lowerLimit, $upperLimit) : new NumericBetween($lowerLimit, $upperLimit)) :
            new AndOperator(static::between($lowerLimit, $upperLimit), $next);
    }

    /**
     * @param int                $min
     * @param int                $max
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function stringLengthBetween(int $min, int $max, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            new StringLengthBetween($min, $max) :
            new AndOperator(static::stringLengthBetween($min, $max), $next);
    }

    /**
     * @param int                $min
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function stringLengthMin(int $min, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            new StringLengthMin($min) :
            new AndOperator(static::stringLengthMin($min), $next);
    }

    /**
     * @param int                $max
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function stringLengthMax(int $max, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            new StringLengthMax($max) :
            new AndOperator(static::stringLengthMax($max), $next);
    }

    /**
     * @param string             $pattern
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function regexp(string $pattern, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            new StringRegExp($pattern) :
            new AndOperator(static::regexp($pattern), $next);
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

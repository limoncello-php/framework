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

use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\Generic\AndOperator;
use Limoncello\Validation\Rules\Generic\Enum;
use Limoncello\Validation\Rules\Generic\Fail;
use Limoncello\Validation\Rules\Generic\Filter;
use Limoncello\Validation\Rules\Generic\IfOperator;
use Limoncello\Validation\Rules\Generic\OrOperator;
use Limoncello\Validation\Rules\Generic\Required;
use Limoncello\Validation\Rules\Generic\Success;

/**
 * @package Limoncello\Validation
 */
trait Generics
{
    /**
     * @param RuleInterface $first
     * @param RuleInterface $second
     *
     * @return RuleInterface
     */
    protected static function andX(RuleInterface $first, RuleInterface $second): RuleInterface
    {
        return new AndOperator($first, $second);
    }

    /**
     * @param RuleInterface $primary
     * @param RuleInterface $secondary
     *
     * @return RuleInterface
     */
    protected static function orX(RuleInterface $primary, RuleInterface $secondary): RuleInterface
    {
        return new OrOperator($primary, $secondary);
    }

    /**
     * @param callable      $condition
     * @param RuleInterface $onTrue
     * @param RuleInterface $onFalse
     * @param array         $settings
     *
     * @return RuleInterface
     */
    protected static function ifX(
        callable $condition,
        RuleInterface $onTrue,
        RuleInterface $onFalse,
        array $settings = []
    ): RuleInterface {
        return new IfOperator($condition, $onTrue, $onFalse, $settings);
    }

    /**
     * @return RuleInterface
     */
    protected static function success(): RuleInterface
    {
        return new Success();
    }

    /**
     * @param int        $errorCode
     * @param null|mixed $errorContext
     *
     * @return RuleInterface
     */
    protected static function fail(int $errorCode = ErrorCodes::INVALID_VALUE, $errorContext = null): RuleInterface
    {
        return new Fail($errorCode, $errorContext);
    }

    /**
     * @param array              $values
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function enum(array $values, RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new Enum($values) : new AndOperator(static::enum($values), $next);
    }

    /**
     * @param int                $filterId
     * @param mixed              $options
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    protected static function filter(int $filterId, $options = null, RuleInterface $next = null): RuleInterface
    {
        return $next === null ?
            new Filter($filterId, $options) : new AndOperator(static::filter($filterId, $options), $next);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function required(RuleInterface $rule = null): RuleInterface
    {
        return $rule === null ? new Required(static::success()) : new Required($rule);
    }
}

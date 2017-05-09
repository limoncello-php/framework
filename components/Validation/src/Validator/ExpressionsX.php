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

use Limoncello\Validation\Contracts\AutoNameRuleInterface;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Expressions\AndExpression;
use Limoncello\Validation\Expressions\ArrayExpression;
use Limoncello\Validation\Expressions\EachExpression;
use Limoncello\Validation\Expressions\IfExpression;
use Limoncello\Validation\Expressions\ObjectExpression;
use Limoncello\Validation\Expressions\OrExpression;
use Limoncello\Validation\Rules\CallableRule;
use Limoncello\Validation\Rules\Success;

/**
 * @package Limoncello\Validation
 */
trait ExpressionsX
{
    /**
     * @param RuleInterface $first
     * @param RuleInterface $second
     *
     * @return RuleInterface
     */
    protected static function andX(RuleInterface $first, RuleInterface $second): RuleInterface
    {
        return new AndExpression($first, $second);
    }

    /**
     * @param RuleInterface $primary
     * @param RuleInterface $secondary
     *
     * @return RuleInterface
     */
    protected static function orX(RuleInterface $primary, RuleInterface $secondary): RuleInterface
    {
        return new OrExpression($primary, $secondary);
    }

    /**
     * @param callable      $condition
     * @param RuleInterface $onTrue
     * @param RuleInterface $onFalse
     *
     * @return RuleInterface
     */
    protected static function ifX(
        callable $condition,
        RuleInterface $onTrue,
        RuleInterface $onFalse
    ): RuleInterface {
        return new IfExpression($condition, $onTrue, $onFalse);
    }

    /**
     * @param RuleInterface[]    $rules
     * @param RuleInterface|null $unlisted
     *
     * @return AutoNameRuleInterface
     */
    protected static function arrayX(array $rules, RuleInterface $unlisted = null): AutoNameRuleInterface
    {
        return new ArrayExpression($rules, $unlisted === null ? new Success() : $unlisted);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function eachX(RuleInterface $rule): RuleInterface
    {
        return new EachExpression($rule);
    }

    /**
     * @param RuleInterface[]    $rules
     * @param RuleInterface|null $unlisted
     *
     * @return AutoNameRuleInterface
     */
    protected static function objectX(array $rules, RuleInterface $unlisted = null): AutoNameRuleInterface
    {
        return new ObjectExpression($rules, $unlisted === null ? new Success() : $unlisted);
    }

    /**
     * @param callable $callable
     * @param int      $messageCode
     *
     * @return RuleInterface
     */
    protected static function callableX(
        callable $callable,
        int $messageCode = MessageCodes::INVALID_VALUE
    ): RuleInterface {
        return new CallableRule($callable, $messageCode);
    }
}

<?php namespace Limoncello\Validation\Validator;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
use Limoncello\Validation\Rules\Between;
use Limoncello\Validation\Rules\CallableRule;
use Limoncello\Validation\Rules\RegExp;
use Limoncello\Validation\Rules\Required;
use Limoncello\Validation\Rules\StringLength;

/**
 * @package Limoncello\Validation
 */
trait Values
{
    /**
     * @return RuleInterface
     */
    protected static function isRequired(): RuleInterface
    {
        return new Required();
    }

    /**
     * @return RuleInterface
     */
    protected static function isNull(): RuleInterface
    {
        return new CallableRule('is_null', MessageCodes::IS_NULL);
    }

    /**
     * @return RuleInterface
     */
    protected static function notNull(): RuleInterface
    {
        return new CallableRule(function ($input) {
            return $input !== null;
        }, MessageCodes::NOT_NULL);
    }

    /**
     * @param string $pattern
     *
     * @return RuleInterface
     */
    protected static function regExp(string $pattern): RuleInterface
    {
        return new RegExp($pattern);
    }

    /**
     * @param null|int $min
     * @param null|int $max
     *
     * @return RuleInterface
     */
    protected static function between(int $min = null, int $max = null): RuleInterface
    {
        return new Between($min, $max);
    }

    /**
     * @param null|int $min
     * @param null|int $max
     *
     * @return RuleInterface
     */
    protected static function stringLength(int $min = null, int $max = null): RuleInterface
    {
        return new StringLength($min, $max);
    }
}

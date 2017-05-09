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

use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @method static RuleInterface andX(RuleInterface $first, RuleInterface $second)
 * @method static RuleInterface orX(RuleInterface $primary, RuleInterface $secondary)
 * @method static RuleInterface isNull()
 * @method static RuleInterface isRequired()
 *
 * @package Limoncello\Validation
 */
trait Wrappers
{
    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function nullable(RuleInterface $rule): RuleInterface
    {
        return static::orX($rule, static::isNull());
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function required(RuleInterface $rule): RuleInterface
    {
        return static::andX(static::isRequired(), $rule);
    }
}

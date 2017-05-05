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

use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Converters\BoolConverter;
use Limoncello\Validation\Converters\DateTimeConverter;
use Limoncello\Validation\Converters\FloatConverter;
use Limoncello\Validation\Converters\IntConverter;

/**
 * @package Limoncello\Validation
 */
trait Converters
{
    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function toBool(RuleInterface $rule): RuleInterface
    {
        return new BoolConverter($rule);
    }

    /**
     * @param RuleInterface $rule
     * @param string        $format
     *
     * @return RuleInterface
     */
    protected static function toDateTime(RuleInterface $rule, string $format): RuleInterface
    {
        return new DateTimeConverter($rule, $format);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function toFloat(RuleInterface $rule): RuleInterface
    {
        return new FloatConverter($rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleInterface
     */
    protected static function toInt(RuleInterface $rule): RuleInterface
    {
        return new IntConverter($rule);
    }
}

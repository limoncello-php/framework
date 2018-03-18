<?php namespace Limoncello\Flute\Validation\JsonApi;

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

use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules as r;

/**
 * @package Limoncello\Flute
 */
class DefaultQueryValidationRules implements JsonApiQueryRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getFilterRules(): ?array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getFieldSetRules(): ?array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getSortsRule(): ?RuleInterface
    {
        return r::fail();
    }

    /**
     * @inheritdoc
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        return r::fail();
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getPageOffsetRule(): ?RuleInterface
    {
        // if not given (`null` as an input) then 0 otherwise input should be integer value >= 0
        return r::ifX(
            r::IS_NULL_CALLABLE,
            r::value(0),
            r::stringToInt(r::moreOrEquals(0))
        );
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getPageLimitRule(): ?RuleInterface
    {
        return static::getPageLimitRuleForDefaultAndMaxSizes(
            FluteSettings::DEFAULT_PAGE_SIZE,
            FluteSettings::DEFAULT_MAX_PAGE_SIZE
        );
    }

    /**
     * @param int $defaultSize
     * @param int $maxSize
     *
     * @return RuleInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function getPageLimitRuleForDefaultAndMaxSizes(int $defaultSize, int $maxSize): RuleInterface
    {
        assert($maxSize > 1 && $defaultSize <= $maxSize);

        // if not given (`null` as an input) then default value otherwise input should be integer 1 <= value <= max
        return r::ifX(
            r::IS_NULL_CALLABLE,
            r::value($defaultSize),
            r::stringToInt(r::between(1, $maxSize))
        );
    }
}

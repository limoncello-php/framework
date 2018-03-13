<?php namespace Limoncello\Tests\Flute\Data\Validation\JsonQueries;

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
use Limoncello\Flute\Validation\JsonApi\DefaultQueryValidationRules;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema as Schema;
use Limoncello\Tests\Flute\Data\Schemes\EmotionSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class ReadCommentsQueryRules implements JsonApiQueryRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getFilterRules(): ?array
    {
        return [
            Schema::RESOURCE_ID                                   => v::stringToInt(v::moreThan(0)),
            Schema::ATTR_TEXT                                     => v::isString(v::stringLengthMin(1)),
            Schema::REL_POST                                      => v::stringToInt(v::moreThan(0)),
            Schema::REL_POST . '.' . PostSchema::ATTR_TEXT        => v::isString(v::stringLengthMin(1)),
            Schema::REL_EMOTIONS                                  => v::stringToInt(v::moreThan(0)),
            Schema::REL_EMOTIONS . '.' . EmotionSchema::ATTR_NAME => v::isString(v::stringLengthMin(1)),
        ];
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
        return v::isString(v::inValues([
            Schema::RESOURCE_ID,
            Schema::REL_POST,
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        return v::isString(v::inValues([
            Schema::REL_USER,
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getPageOffsetRule(): ?RuleInterface
    {
        return DefaultQueryValidationRules::getPageOffsetRule();
    }

    /**
     * @inheritdoc
     */
    public static function getPageLimitRule(): ?RuleInterface
    {
        return DefaultQueryValidationRules::getPageLimitRule();
    }
}

<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Validation\JsonQueries;

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

use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Limoncello\Tests\Flute\Data\Schemas\EmotionSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class ReadEmotionsFromCommentsQueryRules implements JsonApiQueryRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getIdentityRule(): ?RuleInterface
    {
        return v::success();
    }

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
        return v::isString(v::inValues([
            Schema::ATTR_NAME,
        ]));
    }

    /**
     * @inheritdoc
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        return v::fail();
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

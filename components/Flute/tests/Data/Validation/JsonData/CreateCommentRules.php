<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Validation\JsonData;

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

use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Tests\Flute\Data\Schemas\CommentSchema as Schema;
use Limoncello\Tests\Flute\Data\Schemas\EmotionSchema;
use Limoncello\Tests\Flute\Data\Schemas\PostSchema;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class CreateCommentRules implements JsonApiDataRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getTypeRule(): RuleInterface
    {
        return v::equals(Schema::TYPE);
    }

    /**
     * @inheritdoc
     */
    public static function getIdRule(): RuleInterface
    {
        return v::equals(null);
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Schema::ATTR_TEXT => v::required(v::isString()),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getToOneRelationshipRules(): array
    {
        return [
            Schema::REL_POST => v::required(v::toOneRelationship(PostSchema::TYPE, v::stringToInt(v::postId()))),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getToManyRelationshipRules(): array
    {
        return [
            Schema::REL_EMOTIONS =>
                v::toManyRelationship(EmotionSchema::TYPE, v::stringArrayToIntArray(v::emotionIds())),
        ];
    }
}

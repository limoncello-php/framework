<?php namespace Limoncello\Tests\Flute\Data\Schemes;

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

use Limoncello\Tests\Flute\Data\Models\Emotion as Model;

/**
 * @package Limoncello\Tests\Flute
 */
class EmotionSchema extends BaseSchema
{
    /** Type */
    const TYPE = 'emotions';

    /** Model class name */
    const MODEL = Model::class;

    /** Attribute name */
    const ATTR_NAME = 'text-attribute';

    /** Relationship name */
    const REL_COMMENTS = 'comments-relationship';

    /**
     * @inheritdoc
     */
    public static function getMappings(): array
    {
        return [
            self::SCHEMA_ATTRIBUTES => [
                self::ATTR_NAME       => Model::FIELD_NAME,
                self::ATTR_CREATED_AT => Model::FIELD_CREATED_AT,
                self::ATTR_UPDATED_AT => Model::FIELD_UPDATED_AT,
            ],
            self::SCHEMA_RELATIONSHIPS => [
                self::REL_COMMENTS => Model::REL_COMMENTS,
            ],
        ];
    }
}

<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Schemas;

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

use Limoncello\Tests\Flute\Data\Models\Board as Model;

/**
 * @package Limoncello\Tests\Flute
 */
class BoardSchema extends BaseSchema
{
    /** Type */
    const TYPE = 'boards';

    /** Model class name */
    const MODEL = Model::class;

    /** Attribute name */
    const ATTR_TITLE = 'title-attribute';

    /** Relationship name */
    const REL_POSTS = 'posts-relationship';

    /**
     * @inheritdoc
     */
    public static function getMappings(): array
    {
        return [
            self::SCHEMA_ATTRIBUTES => [
                self::RESOURCE_ID     => Model::FIELD_ID,
                self::ATTR_TITLE      => Model::FIELD_TITLE,
                self::ATTR_CREATED_AT => Model::FIELD_CREATED_AT,
                self::ATTR_UPDATED_AT => Model::FIELD_UPDATED_AT,
            ],
            self::SCHEMA_RELATIONSHIPS => [
                self::REL_POSTS => Model::REL_POSTS,
            ],
        ];
    }
}

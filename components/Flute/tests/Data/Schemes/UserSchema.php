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

use Limoncello\Tests\Flute\Data\Models\User as Model;

/**
 * @package Limoncello\Tests\Flute
 */
class UserSchema extends BaseSchema
{
    /** Type */
    const TYPE = 'users';

    /** Model class name */
    const MODEL = Model::class;

    /** Attribute name */
    const ATTR_TITLE = 'title-attribute';

    /** Attribute name */
    const ATTR_FIRST_NAME = 'first-name-attribute';

    /** Attribute name */
    const ATTR_LAST_NAME = 'last-name-attribute';

    /** Attribute name */
    const ATTR_EMAIL = 'email-attribute';

    /** Attribute name */
    const ATTR_LANGUAGE = 'language-attribute';

    /** Attribute name */
    const ATTR_IS_ACTIVE = 'is-active-attribute';

    /** Relationship name */
    const REL_ROLE = 'role-relationship';

    /** Relationship name */
    const REL_POSTS = 'posts-relationship';

    /** Relationship name */
    const REL_COMMENTS = 'comments-relationship';

    /**
     * @inheritdoc
     */
    public static function getMappings(): array
    {
        return [
            self::SCHEMA_ATTRIBUTES => [
                self::RESOURCE_ID     => Model::FIELD_ID,
                self::ATTR_TITLE      => Model::FIELD_TITLE,
                self::ATTR_FIRST_NAME => Model::FIELD_FIRST_NAME,
                self::ATTR_LAST_NAME  => Model::FIELD_LAST_NAME,
                self::ATTR_EMAIL      => Model::FIELD_EMAIL,
                self::ATTR_LANGUAGE   => Model::FIELD_LANGUAGE,
                self::ATTR_IS_ACTIVE  => Model::FIELD_IS_ACTIVE,
                self::ATTR_CREATED_AT => Model::FIELD_CREATED_AT,
                self::ATTR_UPDATED_AT => Model::FIELD_UPDATED_AT,
            ],
            self::SCHEMA_RELATIONSHIPS => [
                self::REL_ROLE     => Model::REL_ROLE,
                self::REL_POSTS    => Model::REL_AUTHORED_POSTS,
                self::REL_COMMENTS => Model::REL_COMMENTS,
            ],
        ];
    }
}

<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Models;

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

use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Tests\Flute\Data\Types\SystemDateTimeType;
use LogicException;

/**
 * @package Limoncello\Tests\Flute
 */
class User extends Model
{
    /** @inheritdoc */
    const TABLE_NAME = 'users';

    /** @inheritdoc */
    const FIELD_ID = 'id_user';

    /** Relationship name */
    const REL_ROLE = 'role';

    /** Relationship name */
    const REL_AUTHORED_POSTS = 'authored_posts';

    /** Relationship name */
    const REL_EDITOR_POSTS = 'editor_posts';

    /** Relationship name */
    const REL_COMMENTS = 'comments';

    /** Field name */
    const FIELD_ID_ROLE = 'id_role_fk';

    /** Field name */
    const FIELD_TITLE = 'title';

    /** Field name */
    const FIELD_FIRST_NAME = 'first_name';

    /** Field name */
    const FIELD_LAST_NAME = 'last_name';

    /** Field name */
    const FIELD_EMAIL = 'email';

    /** Field name */
    const FIELD_IS_ACTIVE = 'is_active';

    /** Field name */
    const FIELD_PASSWORD_HASH = 'password_hash';

    /** Field name */
    const FIELD_LANGUAGE = 'language';

    /** Field name */
    const FIELD_API_TOKEN = 'api_token';

    /** Field name */
    const D_FIELD_FULL_NAME = 'd_full_name';

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes(): array
    {
        return [
            self::FIELD_ID            => Type::INTEGER,
            self::FIELD_ID_ROLE       => Type::INTEGER,
            self::FIELD_TITLE         => Type::STRING,
            self::FIELD_FIRST_NAME    => Type::STRING,
            self::FIELD_LAST_NAME     => Type::STRING,
            self::FIELD_EMAIL         => Type::STRING,
            self::FIELD_IS_ACTIVE     => Type::BOOLEAN,
            self::FIELD_PASSWORD_HASH => Type::STRING,
            self::FIELD_LANGUAGE      => Type::STRING,
            self::FIELD_API_TOKEN     => Type::STRING,
            self::FIELD_CREATED_AT    => SystemDateTimeType::NAME,
            self::FIELD_UPDATED_AT    => SystemDateTimeType::NAME,
            self::FIELD_DELETED_AT    => SystemDateTimeType::NAME,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeLengths(): array
    {
        return [
            self::FIELD_TITLE         => 255,
            self::FIELD_FIRST_NAME    => 255,
            self::FIELD_LAST_NAME     => 255,
            self::FIELD_EMAIL         => 255,
            self::FIELD_PASSWORD_HASH => 255,
            self::FIELD_LANGUAGE      => 255,
            self::FIELD_API_TOKEN     => 255,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships(): array
    {
        return [
            RelationshipTypes::BELONGS_TO => [
                self::REL_ROLE => [Role::class, self::FIELD_ID_ROLE, Role::REL_USERS],
            ],
            RelationshipTypes::HAS_MANY   => [
                self::REL_AUTHORED_POSTS => [Post::class, Post::FIELD_ID_USER, Post::REL_USER],
                self::REL_EDITOR_POSTS   => [Post::class, Post::FIELD_ID_EDITOR, Post::REL_EDITOR],
                self::REL_COMMENTS       => [Comment::class, Comment::FIELD_ID_USER, Comment::REL_USER],
            ],
        ];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case static::D_FIELD_FULL_NAME:
                return $this->{static::FIELD_FIRST_NAME} . ' ' . $this->{static::FIELD_LAST_NAME};
            default:
                throw new LogicException();
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case static::D_FIELD_FULL_NAME:
                return true;
            default:
                return false;
        }
    }
}

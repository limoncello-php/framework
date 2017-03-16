<?php namespace Limoncello\Tests\Flute\Data\Models;

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

use Doctrine\DBAL\Types\Type;
use Limoncello\Tests\Flute\Data\Types\SystemDateTimeType;

/**
 * @package Limoncello\Tests\Flute
 */
class CommentEmotion extends Model
{
    /** @inheritdoc */
    const TABLE_NAME = 'comments_emotions';

    /** @inheritdoc */
    const FIELD_ID = 'id_comment_emotion';

    /** Field name */
    const FIELD_ID_COMMENT = 'id_comment_fk';

    /** Field name */
    const FIELD_ID_EMOTION = 'id_emotion_fk';

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes()
    {
        return [
            self::FIELD_ID         => Type::INTEGER,
            self::FIELD_ID_COMMENT => Type::INTEGER,
            self::FIELD_ID_EMOTION => Type::INTEGER,
            self::FIELD_CREATED_AT => SystemDateTimeType::NAME,
            self::FIELD_UPDATED_AT => SystemDateTimeType::NAME,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeLengths()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships()
    {
        return [];
    }
}

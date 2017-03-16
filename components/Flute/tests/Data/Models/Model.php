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

/**
 * @package Limoncello\Tests\Flute
 */
abstract class Model implements ModelInterface
{
    /** Table name */
    const TABLE_NAME = null;

    /** Primary key */
    const FIELD_ID = null;

    /** Field name */
    const FIELD_CREATED_AT = 'created_at';

    /** Field name */
    const FIELD_UPDATED_AT = 'updated_at';

    /** Field name */
    const FIELD_DELETED_AT = 'deleted_at';

    /**
     * @inheritdoc
     */
    public static function getTableName()
    {
        return static::TABLE_NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getPrimaryKeyName()
    {
        return static::FIELD_ID;
    }
}

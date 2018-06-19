<?php namespace Limoncello\Data\Migrations;

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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * The type could be used for referring custom database types in table columns.
 *
 * @package Limoncello\Data
 */
class RawNameType extends Type
{
    /** Type name */
    const TYPE_NAME = 'RawName';

    /**
     * @inheritdoc
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        assert(
            array_key_exists(static::TYPE_NAME, $fieldDeclaration),
            'Raw type name is not set. Use `Column::setCustomSchemaOption` to set the name.'
        );
        $rawName = $fieldDeclaration[static::TYPE_NAME];
        assert(empty($rawName) === false);

        return $rawName;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::TYPE_NAME;
    }
}

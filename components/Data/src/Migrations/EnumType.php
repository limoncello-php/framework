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
 * @package Limoncello\Data
 */
class EnumType extends Type
{
    /** Type name */
    const TYPE_NAME = 'EnumValues';

    /**
     * @var string[]|null
     */
    private static $values = null;

    /**
     * @param array $values
     *
     * @return void
     */
    public static function setValues(array $values)
    {
        static::$values = $values;
    }

    /**
     * @return void
     */
    public static function resetValues()
    {
        static::$values = null;
    }

    /**
     * @inheritdoc
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $quotedValues = array_map(function ($value) {
            return "'$value'";
        }, static::$values);

        $valueList = implode(',', $quotedValues);

        return "ENUM($valueList)";
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::TYPE_NAME;
    }
}

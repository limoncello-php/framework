<?php namespace Limoncello\Flute\Types;

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

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * @package Limoncello\Flute
 */
class JsonApiDateTimeType extends DateTimeType
{
    /** Type name */
    const NAME = 'limoncelloDateTime';

    /** DateTime format */
    const JSON_API_FORMAT = DateBaseType::JSON_API_FORMAT;

    /**
     * @inheritdoc
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $dateTimeOrNot = parent::convertToPHPValue($value, $platform);

        return $dateTimeOrNot instanceof DateTimeInterface ? new JsonApiDateTime($dateTimeOrNot) : $dateTimeOrNot;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof DateTimeInterface || $value === null) {
            $dateTime = $value;
        } elseif ($value instanceof JsonApiDateTime) {
            $dateTime = $value->getValue();
        } elseif (is_string($value) === true) {
            if (($dateTime = DateTime::createFromFormat(self::JSON_API_FORMAT, $value)) === false) {
                throw ConversionException::conversionFailed($value, $this->getName());
            }
        } else {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                DateTimeInterface::class,
                [DateTimeInterface::class, JsonApiDateTime::class, 'string']
            );
        }

        return parent::convertToDatabaseValue($dateTime, $platform);
    }
}

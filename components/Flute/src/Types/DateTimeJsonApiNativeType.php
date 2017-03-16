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
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Receives string value from database in JSON API format and converts it to DateTime.
 * Converts DateTime to string in default database format.
 *
 * string (JSON API format) -> DateTime (read)
 * DateTime -> string (default database format) (create / update)
 *
 * @package Limoncello\Flute
 */
class DateTimeJsonApiNativeType extends DateTimeBaseType
{
    /** Type name */
    const NAME = 'jaJsonApiNativeDateTime';

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        /** @var DateTime|null $value */

        if ($value === null) {
            return null;
        }

        return $value->format($platform->getDateTimeFormatString());
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        /** @var string|null|DateTime $value */

        if ($value === null || $value instanceof DateTime) {
            return $value;
        }

        $dateTime = DateTime::createFromFormat(static::JSON_API_FORMAT, $value);

        if ($dateTime === false) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), static::JSON_API_FORMAT);
        }

        return $dateTime;
    }
}

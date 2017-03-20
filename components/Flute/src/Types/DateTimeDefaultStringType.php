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

/**
 * Receives string value from database in default database format and converts it to string in JSON API format.
 * Converts JSON API string to string in default database format.
 *
 * string (default database format) -> string (JSON API format) (read)
 * string (JSON API format) -> string (default database format) (create / update)
 *
 * @package Limoncello\Flute
 */
class DateTimeDefaultStringType extends DateTimeBaseType
{
    /** Type name */
    const NAME = 'jaDefaultStringDateTime';

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        /** @var string|null $value */

        if ($value === null) {
            return null;
        }

        return $this->convertDateTimeString($value, static::JSON_API_FORMAT, $platform->getDateTimeFormatString());
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        /** @var string|null $value */

        if ($value === null) {
            return null;
        }

        return $this->convertDateTimeString($value, $platform->getDateTimeFormatString(), static::JSON_API_FORMAT);
    }

    /**
     * @param string|DateTime $value
     * @param string          $fromFormat
     * @param string          $toFormat
     *
     * @return string
     *
     * @throws ConversionException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function convertDateTimeString($value, $fromFormat, $toFormat)
    {
        $dateTime = $value instanceof DateTimeInterface ? $value : DateTime::createFromFormat($fromFormat, $value);

        if ($dateTime === false) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $fromFormat);
        }

        $result = $dateTime->format($toFormat);

        return $result;
    }
}

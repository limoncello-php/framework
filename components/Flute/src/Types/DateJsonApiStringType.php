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
 * Receives string value from database in JSON API format and just passes it further.
 * Converts JSON API string to string in default database format.
 *
 * string (JSON API format) -> string (JSON API format) (read)
 * string (JSON API format) -> string (default database format) (create / update)
 *
 * The main benefit of this type is that it does not involve any DateTime parsing
 * on reading which is good for performance. Though database must format output
 * dates in JSON API format.
 *
 * @package Limoncello\Flute
 */
class DateJsonApiStringType extends DateBaseType
{
    /** Type name */
    const NAME = 'jaJsonApiStringDate';

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        /** @var string|null|DateTime $value */

        if ($value === null) {
            return null;
        }

        $dateTime = $value instanceof DateTimeInterface ?
            $value : DateTime::createFromFormat(static::JSON_API_FORMAT, $value);

        if ($dateTime === false) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), static::JSON_API_FORMAT);
        }

        return $dateTime->format($platform->getDateFormatString());
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        /** @var string|null $value */

        return $value;
    }
}

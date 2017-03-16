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

/**
 * Receives string value from database in JSON API format and passes it further.
 * Receives string value from client in database format and passes it further to database.
 *
 * string (JSON API format) -> string (JSON API format) (read)
 * string (default database format) -> string (default database format) (create / update)
 *
 * Works good for such `system` columns as 'created-at', 'updated-at' and similar which
 * are set from backend and never use input from client.
 *
 * @package Limoncello\Flute
 */
class DateTimeStringType extends DateTimeBaseType
{
    /** Type name */
    const NAME = 'jaStringDateTime';

    /** @noinspection PhpMissingParentCallCommonInspection
     * @inheritdoc
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        /** @var string|null|DateTime $value */

        return $value instanceof DateTime ? $value->format($platform->getDateTimeFormatString()) : $value;
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

<?php namespace Limoncello\Flute\Types;

/**
 * Copyright 2015-2018 info@neomerx.com
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
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateType as BaseDateType;
use Exception;

/**
 * @package Limoncello\Flute
 */
class DateType extends BaseDateType
{
    use TypeTrait;

    /** Type name */
    const NAME = 'limoncelloDate';

    /**
     * @inheritdoc
     *
     * @return DateTime|null
     *
     * @throws ConversionException
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $result = null;

        if ($value !== null && ($dateOrNull = parent::convertToPHPValue($value, $platform)) !== null) {
            // despite the name it's not null already
            $result = DateTime::createFromDateTime($dateOrNull);
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return parent::convertToDatabaseValue(
            $this->convertToDateTimeFromString($value, $platform->getDateTimeFormatString(), static::NAME),
            $platform
        );
    }
}

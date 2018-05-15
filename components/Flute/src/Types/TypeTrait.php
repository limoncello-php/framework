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

use DateTimeInterface;
use Doctrine\DBAL\Types\ConversionException;
use Exception;
use Limoncello\Flute\Types\DateTime as JsonApiDateTime;

/**
 * @package Limoncello\Flute
 */
trait TypeTrait
{
    /**
     * @param DateTimeInterface $dateTime
     *
     * @return JsonApiDateTime
     *
     * @throws Exception
     */
    private function convertToJsonApiDateTime(DateTimeInterface $dateTime): JsonApiDateTime
    {
        $utcTimestamp = $dateTime->getTimestamp();

        // yes, PHP DateTime constructor can only accept timestamp as a string ¯\_( ͡° ͜ʖ ͡°)_/¯
        return new JsonApiDateTime("@$utcTimestamp");
    }

    /**
     * @param string|DateTimeInterface $value
     * @param string                   $nonJsonFormat
     * @param string                   $typeName
     *
     * @return DateTimeInterface|null
     *
     * @throws ConversionException
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function convertToDateTimeFromString(
        $value,
        string $nonJsonFormat,
        string $typeName
    ): ?DateTimeInterface {
        if ($value instanceof DateTimeInterface || $value === null) {
            $result = $value;
        } elseif (is_string($value) === true) {
            $result = JsonApiDateTime::createFromFormat($nonJsonFormat, $value);
            $result = $result !== false ?
                $result : JsonApiDateTime::createFromFormat(JsonApiDateTime::JSON_API_FORMAT, $value);
            if ($result === false) {
                throw ConversionException::conversionFailed($value, $typeName);
            }
        } else {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                DateTimeInterface::class,
                [DateTimeInterface::class, JsonApiDateTime::class, 'string']
            );
        }

        return $result;
    }
}

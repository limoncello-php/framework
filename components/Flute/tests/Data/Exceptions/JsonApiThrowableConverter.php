<?php namespace Limoncello\Tests\Flute\Data\Exceptions;

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

use Limoncello\Flute\Contracts\Exceptions\JsonApiThrowableConverterInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Throwable;

/**
 * @package Limoncello\Tests\Flute
 */
class JsonApiThrowableConverter implements JsonApiThrowableConverterInterface
{
    /**
     * @var bool
     */
    private static $shouldThrow = false;

    /**
     * @inheritdoc
     */
    public static function convert(Throwable $throwable): ?JsonApiException
    {
        $exception = $throwable instanceof \Exception ?
            new JsonApiException([new Error()], JsonApiException::DEFAULT_HTTP_CODE, $throwable) : null;

        // normally it should return however for testing purposes we need a 'faulty' converter that throws
        // an exception.
        if (static::isShouldThrow() === true) {
            throw $exception;
        } else {
            return $exception;
        }
    }

    /**
     * @return bool
     */
    public static function isShouldThrow(): bool
    {
        return self::$shouldThrow;
    }

    /**
     * @param bool $shouldThrow
     *
     * @return void
     */
    public static function setShouldThrow(bool $shouldThrow): void
    {
        self::$shouldThrow = $shouldThrow;
    }
}

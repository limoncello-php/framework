<?php namespace Limoncello\Tests\Application\Packages\Cookies;

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

use Limoncello\Application\Packages\Cookies\CookieMiddleware;

/**
 * @package Limoncello\Tests\Application
 */
class TestCookieMiddleware extends CookieMiddleware
{
    /**
     * @inheritdoc
     */
    protected const SET_COOKIE_CALLABLE = [self::class, 'setCookie'];

    /**
     * @inheritdoc
     */
    protected const SET_RAW_COOKIE_CALLABLE = [self::class, 'setRawCookie'];

    /**
     * @var array
     */
    private static $inputs;

    /**
     * @param array $args
     */
    public static function setCookie(...$args): void
    {
        static::setCookieInt(false, $args);
    }

    /**
     * @param array $args
     */
    public static function setRawCookie(...$args): void
    {
        static::setCookieInt(true, $args);
    }

    /**
     * @return array
     */
    public static function getInputs(): array
    {
        return self::$inputs;
    }

    /**
     * Reset inputs from calls.
     */
    public static function reset(): void
    {
        static::$inputs = [];
    }

    /**
     * @param bool  $isRaw
     * @param array $args
     */
    private static function setCookieInt(bool $isRaw, array $args): void
    {
        static::$inputs[] = array_merge([$isRaw], $args);
    }
}

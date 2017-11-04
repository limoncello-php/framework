<?php namespace Limoncello\Application\Contracts\Cookie;

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

/**
 * Provides a separation layer for native PHP session functions.
 *
 * @package Limoncello\Contracts
 */
interface CookieFunctionsInterface
{
    /**
     * @return callable
     */
    public function getWriteCookieCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setWriteCookieCallable(callable $callable): self;

    /**
     * @return callable
     */
    public function getWriteRawCookieCallable(): callable;

    /**
     * @param callable $callable
     *
     * @return self
     */
    public function setWriteRawCookieCallable(callable $callable): self;
}

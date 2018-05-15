<?php namespace Limoncello\Contracts\Cookies;

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

/**
 * @package Limoncello\Application
 */
interface CookieJarInterface
{
    /**
     * @param string $cookieName
     *
     * @return CookieInterface
     */
    public function create(string $cookieName): CookieInterface;

    /**
     * @param string $cookieName
     *
     * @return bool
     */
    public function has(string $cookieName): bool;

    /**
     * @param string $cookieName
     *
     * @return CookieInterface
     */
    public function get(string $cookieName): CookieInterface;

    /**
     * @param string $cookieName
     *
     * @return CookieJarInterface
     */
    public function delete(string  $cookieName): self;

    /**
     * @return iterable
     */
    public function getAll(): iterable;
}

<?php namespace Limoncello\Passport\Contracts\Entities;

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

use DateTimeInterface;
use Psr\Http\Message\UriInterface;

/**
 * @package Limoncello\Passport
 */
interface RedirectUriInterface
{
    /**
     * @return int
     */
    public function getIdentifier(): int;

    /**
     * @param int $identifier
     *
     * @return RedirectUriInterface
     */
    public function setIdentifier(int $identifier): RedirectUriInterface;

    /**
     * @return string
     */
    public function getClientIdentifier(): string;

    /**
     * @param string $identifier
     *
     * @return RedirectUriInterface
     */
    public function setClientIdentifier(string $identifier): RedirectUriInterface;

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface;

    /**
     * @return string
     */
    public function getValue(): string;

    /**
     * @param string $uri
     *
     * @return RedirectUriInterface
     */
    public function setValue(string $uri): RedirectUriInterface;

    /**
     * @return RedirectUriInterface|null
     */
    public function getCreatedAt();

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return RedirectUriInterface
     */
    public function setCreatedAt(DateTimeInterface $createdAt): RedirectUriInterface;

    /**
     * @return RedirectUriInterface|null
     */
    public function getUpdatedAt();

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return RedirectUriInterface
     */
    public function setUpdatedAt(DateTimeInterface $createdAt): RedirectUriInterface;
}

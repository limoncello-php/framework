<?php namespace Limoncello\Contracts\Cookies;

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

/**
 * @package Limoncello\Application
 */
interface CookieInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getValue(): string;

    /**
     * @param string $value
     *
     * @return self
     */
    public function setValue(string $value): self;

    /**
     * @return int
     */
    public function getExpiresAtUnixTime(): int;

    /**
     * @param int $unixTimestamp
     *
     * @return self
     */
    public function setExpiresAtUnixTime(int $unixTimestamp): self;

    /**
     * @param int $seconds
     *
     * @return self
     */
    public function setExpiresInSeconds(int $seconds): self;

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return self
     */
    public function setExpiresAtDataTime(DateTimeInterface $dateTime): self;

    /**
     * @return string
     */
    public function getPath(): string;

    /**
     * @param string $path
     *
     * @return self
     */
    public function setPath(string $path): self;

    /**
     * @return string
     */
    public function getDomain(): string;

    /**
     * @param string $domain
     *
     * @return self
     */
    public function setDomain(string $domain): self;

    /**
     * @return bool
     */
    public function isSendOnlyOverSecureConnection(): bool;

    /**
     * @return self
     */
    public function setSendOnlyOverSecureConnection(): self;

    /**
     * @return bool
     */
    public function isSendOverAnyConnection(): bool;

    /**
     * @return self
     */
    public function setSendOverAnyConnection(): self;

    /**
     * @return bool
     */
    public function isAccessibleOnlyThroughHttp(): bool;

    /**
     * @return self
     */
    public function setAccessibleOnlyThroughHttp(): self;

    /**
     * @return bool
     */
    public function isAccessibleThroughHttpAndScripts(): bool;

    /**
     * @return self
     */
    public function setAccessibleThroughHttpAndScripts(): self;

    /**
     * @return bool
     */
    public function isRaw(): bool;

    /**
     * @return self
     */
    public function setAsRaw(): self;

    /**
     * @return bool
     */
    public function isNotRaw(): bool;

    /**
     * @return self
     */
    public function setAsNotRaw(): self;
}

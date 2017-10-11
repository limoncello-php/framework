<?php namespace Limoncello\Application\Cookies;

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

use Limoncello\Application\Exceptions\InvalidArgumentException;
use Limoncello\Contracts\Cookies\CookieInterface;
use Limoncello\Contracts\Cookies\CookieJarInterface;

/**
 * @package Limoncello\Application
 */
class CookieJar implements CookieJarInterface
{
    /**
     * @var CookieInterface[]
     */
    private $cookies;

    /**
     * @var string
     */
    private $defaultPath;

    /**
     * @var string
     */
    private $defaultDomain;

    /**
     * @var bool
     */
    private $defaultIsSecure;

    /**
     * @var bool
     */
    private $defaultIsHttpOnly;

    /**
     * @var bool
     */
    private $defaultIsRaw;

    /**
     * @param string $defaultPath
     * @param string $defaultDomain
     * @param bool   $defaultIsSecure
     * @param bool   $defaultIsHttpOnly
     * @param bool   $defaultIsRaw
     */
    public function __construct(
        string $defaultPath,
        string $defaultDomain,
        bool $defaultIsSecure,
        bool $defaultIsHttpOnly,
        bool $defaultIsRaw
    ) {
        $this->cookies           = [];
        $this->defaultPath       = $defaultPath;
        $this->defaultDomain     = $defaultDomain;
        $this->defaultIsSecure   = $defaultIsSecure;
        $this->defaultIsHttpOnly = $defaultIsHttpOnly;
        $this->defaultIsRaw      = $defaultIsRaw;
    }


    /**
     * @inheritdoc
     */
    public function create(string $cookieName): CookieInterface
    {
        if ($this->has($cookieName) === true) {
            throw new InvalidArgumentException($cookieName);
        }

        $cookie = new Cookie(
            $cookieName,
            '',
            0,
            $this->getDefaultPath(),
            $this->getDefaultDomain(),
            $this->getDefaultIsSecure(),
            $this->getDefaultIsHttpOnly(),
            $this->getDefaultIsRaw()
        );

        $this->cookies[$cookieName] = $cookie;

        return $cookie;
    }

    /**
     * @inheritdoc
     */
    public function has(string $cookieName): bool
    {
        return array_key_exists($cookieName, $this->cookies);
    }

    /**
     * @inheritdoc
     */
    public function get(string $cookieName): CookieInterface
    {
        return $this->cookies[$cookieName];
    }

    /**
     * @inheritdoc
     */
    public function delete(string $cookieName): CookieJarInterface
    {
        unset($this->cookies[$cookieName]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): iterable
    {
        /** @var iterable $result */
        $result = $this->cookies;

        return $result;
    }

    /**
     * @return string
     */
    protected function getDefaultPath(): string
    {
        return $this->defaultPath;
    }

    /**
     * @return string
     */
    protected function getDefaultDomain(): string
    {
        return $this->defaultDomain;
    }

    /**
     * @return bool
     */
    protected function getDefaultIsSecure(): bool
    {
        return $this->defaultIsSecure;
    }

    /**
     * @return bool
     */
    protected function getDefaultIsHttpOnly(): bool
    {
        return $this->defaultIsHttpOnly;
    }

    /**
     * @return bool
     */
    protected function getDefaultIsRaw(): bool
    {
        return $this->defaultIsRaw;
    }
}

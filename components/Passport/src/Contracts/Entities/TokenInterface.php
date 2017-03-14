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
use Limoncello\OAuthServer\Contracts\AuthorizationCodeInterface;

/**
 * @package Limoncello\Passport
 */
interface TokenInterface extends AuthorizationCodeInterface, \Limoncello\OAuthServer\Contracts\TokenInterface
{
    /**
     * @return int|null
     */
    public function getIdentifier();

    /**
     * @param int $identifier
     *
     * @return TokenInterface
     */
    public function setIdentifier(int $identifier): TokenInterface;

    /**
     * @param string $identifier
     *
     * @return TokenInterface
     */
    public function setClientIdentifier(string $identifier): TokenInterface;

    /**
     * @param int|string $identifier
     *
     * @return TokenInterface
     */
    public function setUserIdentifier($identifier): TokenInterface;

    /**
     * @param string[] $identifiers
     *
     * @return TokenInterface
     */
    public function setScopeIdentifiers(array $identifiers): TokenInterface;

    /**
     * @return string
     */
    public function getScopeList(): string;

    /**
     * @return string|null
     */
    public function getType();

    /**
     * @param string|null $uri
     *
     * @return TokenInterface
     */
    public function setRedirectUriString(string $uri = null): TokenInterface;

    /**
     * @return TokenInterface
     */
    public function setScopeModified(): TokenInterface;

    /**
     * @return TokenInterface
     */
    public function setScopeUnmodified(): TokenInterface;

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @return TokenInterface
     */
    public function setEnabled(): TokenInterface;

    /**
     * @return TokenInterface
     */
    public function setDisabled(): TokenInterface;

    /**
     * @param string|null $code
     *
     * @return TokenInterface
     */
    public function setCode(string $code = null): TokenInterface;

    /**
     * @param string|null $value
     *
     * @return TokenInterface
     */
    public function setValue(string $value = null): TokenInterface;

    /**
     * @param string|null $type
     *
     * @return TokenInterface
     */
    public function setType(string $type = null): TokenInterface;

    /**
     * @param string|null $refreshValue
     *
     * @return TokenInterface
     */
    public function setRefreshValue(string $refreshValue = null): TokenInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getCodeCreatedAt();

    /**
     * @param DateTimeInterface $codeCreatedAt
     *
     * @return TokenInterface
     */
    public function setCodeCreatedAt(DateTimeInterface $codeCreatedAt): TokenInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getValueCreatedAt();

    /**
     * @param DateTimeInterface $valueCreatedAt
     *
     * @return TokenInterface
     */
    public function setValueCreatedAt(DateTimeInterface $valueCreatedAt): TokenInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getRefreshCreatedAt();

    /**
     * @param DateTimeInterface $refreshCreatedAt
     *
     * @return TokenInterface
     */
    public function setRefreshCreatedAt(DateTimeInterface $refreshCreatedAt): TokenInterface;
}

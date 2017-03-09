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

/**
 * @package Limoncello\Passport
 */
interface ClientInterface extends \Limoncello\OAuthServer\Contracts\ClientInterface
{
    /**
     * @param string $identifier
     *
     * @return ClientInterface
     */
    public function setIdentifier(string $identifier): ClientInterface;

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return ClientInterface
     */
    public function setName(string $name): ClientInterface;

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string|null $description
     *
     * @return ClientInterface
     */
    public function setDescription(string $description = null): ClientInterface;

    /**
     * @return string|null
     */
    public function getCredentials();

    /**
     * @param string $credentials
     *
     * @return ClientInterface
     */
    public function setCredentials(string $credentials = null): ClientInterface;

    /**
     * @param string[] $redirectUriStrings
     *
     * @return ClientInterface
     */
    public function setRedirectUriStrings(array $redirectUriStrings): ClientInterface;

    /**
     * @param string[] $scopeIdentifiers
     *
     * @return ClientInterface
     */
    public function setScopeIdentifiers(array $scopeIdentifiers): ClientInterface;

    /**
     * @return bool
     */
    public function isPublic(): bool;

    /**
     * @return ClientInterface
     */
    public function setPublic(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function setConfidential(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function useDefaultScopesOnEmptyRequest(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function doNotUseDefaultScopesOnEmptyRequest(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enableScopeExcess(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disableScopeExcess(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enableCodeGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disableCodeGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enableImplicitGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disableImplicitGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enablePasswordGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disablePasswordGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enableClientGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disableClientGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function enableRefreshGrant(): ClientInterface;

    /**
     * @return ClientInterface
     */
    public function disableRefreshGrant(): ClientInterface;

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt();

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return ClientInterface
     */
    public function setCreatedAt(DateTimeInterface $createdAt): ClientInterface;

    /**
     * @return RedirectUriInterface|null
     */
    public function getUpdatedAt();

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return ClientInterface
     */
    public function setUpdatedAt(DateTimeInterface $createdAt): ClientInterface;
}

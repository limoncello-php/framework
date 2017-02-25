<?php namespace Limoncello\OAuthServer\Contracts;

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
 * @package Limoncello\OAuthServer
 */
interface ClientInterface
{
    /**
     * @return string
     *
     * @link https://tools.ietf.org/html/rfc6749#section-2.2
     */
    public function getIdentifier(): string;

    /**
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-2.1
     */
    public function isConfidential(): bool;

    /**
     * Get `true` if the client has credential associated with it.
     *
     * The credentials itself are implementation specific and not part of this interface.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-2.1
     * @link https://tools.ietf.org/html/rfc6749#section-2.3
     * @link https://tools.ietf.org/html/rfc6749#section-3.2.1
     */
    public function hasCredentials(): bool;

    /**
     * @return string[]
     *
     * @link https://tools.ietf.org/html/rfc6749#section-2
     * @link https://tools.ietf.org/html/rfc6749#section-3.1.2.2
     * @link https://tools.ietf.org/html/rfc6749#section-3.1.2.3
     */
    public function getRedirectUriStrings(): array;

    /**
     * Get a list of scope (scope identifiers) associated with client. It could be interpreted as allowed scopes or as
     * default scopes for token if no scopes are given in authentication request.
     *
     * @return string[]
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.3
     */
    public function getScopeIdentifiers(): array;

    /**
     * Get `true` if server should use client scope if no scope is given in authorization request. If `false` empty
     * input scope will lead to invalid_scope error.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.3
     */
    public function isUseDefaultScopesOnEmptyRequest(): bool;

    /**
     * Get `true` if client scopes are considered as default scopes rather than limits. In such a case if extra scopes
     * are requested they will be passed to resource owner to approve/deny. If `false` any extra scopes would lead
     * to invalid_scope error and without resource owner involvement.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-3.3
     */
    public function isScopeExcessAllowed(): bool;

    /**
     * Get `true` if authorization code grant is allowed for the client and `false` otherwise.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     */
    public function isCodeGrantEnabled(): bool;

    /**
     * Get `true` if implicit authorization is allowed for the client and `false` otherwise.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2.1
     */
    public function isImplicitGrantEnabled(): bool;

    /**
     * Get `true` if resource owner password credentials grant is allowed for the client and `false` otherwise.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.3.2
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    public function isPasswordGrantEnabled(): bool;

    /**
     * Get `true` if client credentials grant is allowed for the client and `false` otherwise.
     *
     * @return bool
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.3.2
     * @link https://tools.ietf.org/html/rfc6749#section-5.2
     */
    public function isClientGrantEnabled(): bool;
}

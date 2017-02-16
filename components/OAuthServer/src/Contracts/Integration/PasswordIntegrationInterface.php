<?php namespace Limoncello\OAuthServer\Contracts\Integration;

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

use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\OAuthServer\Exceptions\OAuthTokenBodyException;
use Psr\Http\Message\ResponseInterface;

/**
 * Resource owner password integration interface for server.
 *
 * @package Limoncello\OAuthServer
 */
interface PasswordIntegrationInterface extends IntegrationInterface
{
    /**
     * @param ClientInterface|null $client
     * @param array|null           $scopes
     *
     * @return array [bool $isScopeValid, string[]|null $scopeList, bool $isScopeModified] Scope list `null` for
     *               invalid, string[] otherwise.
     */
    public function passValidateScope(ClientInterface $client = null, array $scopes = null): array;

    /**
     * Validate resource owner credentials and create access token response. On error (e.g invalid credentials)
     * it throws OAuth exception.
     *
     * @param string               $userName
     * @param string               $password
     * @param ClientInterface|null $client
     * @param bool                 $isScopeModified
     * @param array|null           $scope
     *
     * @return ResponseInterface
     *
     * @throws OAuthTokenBodyException
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.4
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function passValidateCredentialsAndCreateAccessTokenResponse(
        $userName,
        $password,
        ClientInterface $client = null,
        bool $isScopeModified = false,
        array $scope = null
    ): ResponseInterface;

    /**
     * @return ClientInterface
     */
    public function passReadDefaultClient(): ClientInterface;
}

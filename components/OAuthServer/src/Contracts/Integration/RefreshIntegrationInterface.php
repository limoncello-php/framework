<?php namespace Limoncello\OAuthServer\Contracts\Integration;

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

use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\OAuthServer\Contracts\TokenInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Refresh token integration interface for server.
 *
 * @package Limoncello\OAuthServer
 */
interface RefreshIntegrationInterface extends IntegrationInterface
{
    /**
     * Create access token response.
     *
     * @param ClientInterface $client
     * @param TokenInterface  $token
     * @param bool            $isScopeModified
     * @param array|null      $scope
     * @param array           $extraParameters
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-6
     */
    public function refreshCreateAccessTokenResponse(
        ClientInterface $client,
        TokenInterface $token,
        bool $isScopeModified,
        array $scope = null,
        array $extraParameters = []
    ): ResponseInterface;

    /**
     * Read token by its value. If no token is found returns `null`.
     *
     * @param string $refreshValue
     *
     * @return TokenInterface|null
     *
     * @link https://tools.ietf.org/html/rfc6749#section-6
     */
    public function readTokenByRefreshValue(string $refreshValue): ?TokenInterface;

    /**
     * @param string $clientIdentifier
     *
     * @return ClientInterface|null
     */
    public function readClientByIdentifier(string $clientIdentifier): ?ClientInterface;
}

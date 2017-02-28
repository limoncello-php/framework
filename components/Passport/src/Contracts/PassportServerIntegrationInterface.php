<?php namespace Limoncello\Passport\Contracts;

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
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\Passport
 */
interface PassportServerIntegrationInterface
{
    /**
     * @return string
     */
    public function getDefaultClientIdentifier(): string;

    /**
     * @return ClientRepositoryInterface
     */
    public function getClientRepository(): ClientRepositoryInterface;

    /**
     * @return TokenRepositoryInterface
     */
    public function getTokenRepository(): TokenRepositoryInterface;

    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool;

    /**
     * @param string $userName
     * @param string $password
     *
     * @return int|null
     */
    public function validateUserId(string $userName, string $password);

    /**
     * @param string          $clientIdentifier
     * @param int             $userIdentifier
     * @param bool            $isScopeModified
     * @param string[]|null   $scope
     *
     * @return array [string $tokenValue, string $tokenType, int $tokenExpiresInSeconds, string|null $refreshValue]
     */
    public function generateTokenValues(
        string $clientIdentifier,
        int $userIdentifier = null,
        bool $isScopeModified = false,
        array $scope = null
    ): array;

    /**
     * @return int
     */
    public function getCodeExpirationPeriod(): int;

    /**
     * @return int
     */
    public function getTokenExpirationPeriod(): int;

    /**
     * @return ResponseInterface
     */
    public function createInvalidClientAndRedirectUriErrorResponse(): ResponseInterface;

    /** @noinspection PhpTooManyParametersInspection
     * @param ClientInterface $client
     * @param string|null     $redirectUri
     * @param bool            $isScopeModified
     * @param string[]|null   $scopeList
     * @param string|null     $state
     * @param array           $extraParameters
     *
     * @return ResponseInterface
     *
     * @link https://tools.ietf.org/html/rfc6749#section-4.1.2
     * @link https://tools.ietf.org/html/rfc6749#section-4.2.2
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function createAskResourceOwnerForApprovalResponse(
        ClientInterface $client,
        string $redirectUri = null,
        bool $isScopeModified = false,
        array $scopeList = null,
        string $state = null,
        array $extraParameters = []
    ): ResponseInterface;
}

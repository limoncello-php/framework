<?php declare(strict_types=1);

namespace Limoncello\Passport\Contracts\Repositories;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Closure;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;

/**
 * @package Limoncello\Passport
 */
interface TokenRepositoryInterface
{
    /**
     * @param Closure $closure
     *
     * @return void
     */
    public function inTransaction(Closure $closure): void;

    /**
     * @param TokenInterface $code
     *
     * @return TokenInterface
     */
    public function createCode(TokenInterface $code): TokenInterface;

    /**
     * @param TokenInterface $token
     * @param int            $expirationInSeconds
     *
     * @return void
     */
    public function assignValuesToCode(TokenInterface $token, int $expirationInSeconds): void;

    /**
     * @param TokenInterface $token
     *
     * @return TokenInterface
     */
    public function createToken(TokenInterface $token): TokenInterface;

    /**
     * @param int                       $identifier
     * @param iterable|ScopeInterface[] $scopes
     *
     * @return void
     */
    public function bindScopes(int $identifier, iterable $scopes): void;

    /**
     * @param int               $identifier
     * @param iterable|string[] $scopeIdentifiers
     *
     * @return void
     */
    public function bindScopeIdentifiers(int $identifier, iterable $scopeIdentifiers): void;

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function unbindScopes(int $identifier): void;

    /**
     * @param int $identifier
     *
     * @return TokenInterface|null
     */
    public function read(int $identifier): ?TokenInterface;

    /**
     * @param string $code
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByCode(string $code, int $expirationInSeconds): ?TokenInterface;

    /**
     * @param string $tokenValue
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds): ?TokenInterface;

    /**
     * @param string $refreshValue
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds): ?TokenInterface;

    /**
     * @param int      $userId
     * @param int      $expirationInSeconds
     * @param int|null $limit
     *
     * @return array
     */
    public function readByUser(int $userId, int $expirationInSeconds, int $limit = null): array;

    /**
     * @param int $identifier
     *
     * @return string[]
     */
    public function readScopeIdentifiers(int $identifier): array;

    /**
     * @param string $tokenValue
     * @param int    $expirationInSeconds
     *
     * @return array|null
     */
    public function readPassport(string $tokenValue, int $expirationInSeconds): ?array;

    /**
     * @param TokenInterface $token
     *
     * @return void
     */
    public function updateValues(TokenInterface $token): void;

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function delete(int $identifier): void;

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function disable(int $identifier): void;
}

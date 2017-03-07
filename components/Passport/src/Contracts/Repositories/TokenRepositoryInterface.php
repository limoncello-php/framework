<?php namespace Limoncello\Passport\Contracts\Repositories;

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
    public function inTransaction(Closure $closure);

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
    public function assignValuesToCode(TokenInterface $token, int $expirationInSeconds);

    /**
     * @param TokenInterface $token
     *
     * @return TokenInterface
     */
    public function createToken(TokenInterface $token): TokenInterface;

    /**
     * @param int              $identifier
     * @param ScopeInterface[] $scopes
     *
     * @return void
     */
    public function bindScopes(int $identifier, array $scopes);

    /**
     * @param int      $identifier
     * @param string[] $scopeIdentifiers
     *
     * @return void
     */
    public function bindScopeIdentifiers(int $identifier, array $scopeIdentifiers);

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function unbindScopes(int $identifier);

    /**
     * @param int $identifier
     *
     * @return TokenInterface|null
     */
    public function read(int $identifier);

    /**
     * @param string $code
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByCode(string $code, int $expirationInSeconds);

    /**
     * @param string $tokenValue
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds);

    /**
     * @param string $refreshValue
     * @param int    $expirationInSeconds
     *
     * @return TokenInterface|null
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds);

    /**
     * @param int $identifier
     *
     * @return string[]
     */
    public function readScopeIdentifiers(int $identifier): array;

    /**
     * @param TokenInterface $token
     *
     * @return void
     */
    public function updateValues(TokenInterface $token);

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function delete(int $identifier);

    /**
     * @param int $identifier
     *
     * @return void
     */
    public function disable(int $identifier);
}

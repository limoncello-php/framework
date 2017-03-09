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
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;

/**
 * @package Limoncello\Passport
 */
interface ClientRepositoryInterface
{
    /**
     * @param Closure $closure
     *
     * @return void
     */
    public function inTransaction(Closure $closure);

    /**
     * @return ClientInterface[]
     */
    public function index(): array;

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     */
    public function create(ClientInterface $client): ClientInterface;

    /**
     * @param string           $identifier
     * @param ScopeInterface[] $scopes
     *
     * @return void
     */
    public function bindScopes(string $identifier, array $scopes);

    /**
     * @param string   $identifier
     * @param string[] $scopeIdentifiers
     *
     * @return void
     */
    public function bindScopeIdentifiers(string $identifier, array $scopeIdentifiers);

    /**
     * @param string $identifier
     *
     * @return void
     */
    public function unbindScopes(string $identifier);

    /**
     * @param string $identifier
     *
     * @return ClientInterface|null
     */
    public function read(string $identifier);

    /**
     * @param string $identifier
     *
     * @return string[]
     */
    public function readScopeIdentifiers(string $identifier): array;

    /**
     * @param string $identifier
     *
     * @return string[]
     */
    public function readRedirectUriStrings(string $identifier): array;

    /**
     * @param ClientInterface $client
     *
     * @return void
     */
    public function update(ClientInterface $client);

    /**
     * @param string $identifier
     *
     * @return void
     */
    public function delete(string $identifier);
}

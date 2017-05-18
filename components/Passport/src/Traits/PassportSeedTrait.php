<?php namespace Limoncello\Passport\Traits;

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

use Doctrine\DBAL\Connection;
use Limoncello\Passport\Adaptors\Generic\RedirectUri;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\RedirectUriRepositoryInterface;
use Limoncello\Passport\Contracts\Repositories\ScopeRepositoryInterface;

/**
 * @package Limoncello\Passport
 */
trait PassportSeedTrait
{
    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $schemes
     *
     * @return ClientRepositoryInterface
     */
    abstract protected function createClientRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): ClientRepositoryInterface;

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $schemes
     *
     * @return ScopeRepositoryInterface
     */
    abstract protected function createScopeRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): ScopeRepositoryInterface;

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $schemes
     *
     * @return RedirectUriRepositoryInterface
     */
    abstract protected function createRedirectUriRepository(
        Connection $connection,
        DatabaseSchemeInterface $schemes
    ): RedirectUriRepositoryInterface;

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $schemes
     * @param ClientInterface         $client
     * @param array                   $scopeDescriptions
     * @param string[]                $redirectUris
     *
     * @return void
     */
    protected function seedClient(
        Connection $connection,
        DatabaseSchemeInterface $schemes,
        ClientInterface $client,
        array $scopeDescriptions,
        array $redirectUris = []
    ) {
        $scopeIds  = $client->getScopeIdentifiers();
        $scopeRepo = $this->createScopeRepository($connection, $schemes);
        foreach ($scopeDescriptions as $scopeId => $scopeDescription) {
            $scopeRepo->create(
                (new Scope())
                    ->setIdentifier($scopeId)
                    ->setDescription($scopeDescription)
            );
            $scopeIds[] = $scopeId;
        }

        $this->createClientRepository($connection, $schemes)->create($client->setScopeIdentifiers($scopeIds));

        $uriRepo = $this->createRedirectUriRepository($connection, $schemes);
        foreach ($redirectUris as $uri) {
            $uriRepo->create(
                (new RedirectUri())
                    ->setValue($uri)
                    ->setClientIdentifier($client->getIdentifier())
            );
        }
    }
}

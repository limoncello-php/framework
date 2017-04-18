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
use Limoncello\Passport\Adaptors\Generic\ClientRepository;
use Limoncello\Passport\Adaptors\Generic\RedirectUri;
use Limoncello\Passport\Adaptors\Generic\RedirectUriRepository;
use Limoncello\Passport\Adaptors\Generic\Scope;
use Limoncello\Passport\Adaptors\Generic\ScopeRepository;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;

/**
 * @package Limoncello\Passport
 */
trait PassportSeedTrait
{
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
        $clientRepo = new ClientRepository($connection, $schemes);
        $scopeRepo  = new ScopeRepository($connection, $schemes);
        $uriRepo    = new RedirectUriRepository($connection, $schemes);

        $scopeIds = [];
        foreach ($scopeDescriptions as $scopeId => $scopeDescription) {
            $scopeRepo->create(
                (new Scope())
                    ->setIdentifier($scopeId)
                    ->setDescription($scopeDescription)
            );
            $scopeIds[] = $scopeId;
        }

        $clientRepo->create($client->setScopeIdentifiers($scopeIds));

        foreach ($redirectUris as $uri) {
            $uriRepo->create(
                (new RedirectUri())
                    ->setValue($uri)
                    ->setClientIdentifier($client->getIdentifier())
            );
        }
    }
}

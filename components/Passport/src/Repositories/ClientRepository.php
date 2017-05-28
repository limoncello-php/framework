<?php namespace Limoncello\Passport\Repositories;

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

use DateTimeImmutable;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;

/**
 * @package Limoncello\Passport
 */
abstract class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function index(): array
    {
        return parent::indexResources();
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function create(ClientInterface $client): ClientInterface
    {
        $now    = new DateTimeImmutable();
        $scheme = $this->getDatabaseScheme();
        $values = [
            $scheme->getClientsIdentityColumn()               => $client->getIdentifier(),
            $scheme->getClientsNameColumn()                   => $client->getName(),
            $scheme->getClientsDescriptionColumn()            => $client->getDescription(),
            $scheme->getClientsCredentialsColumn()            => $client->getCredentials(),
            $scheme->getClientsIsConfidentialColumn()         => $client->isConfidential(),
            $scheme->getClientsIsScopeExcessAllowedColumn()   => $client->isScopeExcessAllowed(),
            $scheme->getClientsIsUseDefaultScopeColumn()      => $client->isUseDefaultScopesOnEmptyRequest(),
            $scheme->getClientsIsCodeGrantEnabledColumn()     => $client->isCodeGrantEnabled(),
            $scheme->getClientsIsImplicitGrantEnabledColumn() => $client->isImplicitGrantEnabled(),
            $scheme->getClientsIsPasswordGrantEnabledColumn() => $client->isPasswordGrantEnabled(),
            $scheme->getClientsIsClientGrantEnabledColumn()   => $client->isClientGrantEnabled(),
            $scheme->getClientsIsRefreshGrantEnabledColumn()  => $client->isRefreshGrantEnabled(),
            $scheme->getClientsCreatedAtColumn()              => $now,
        ];

        $identifier = $client->getIdentifier();
        if (empty($scopeIdentifiers = $client->getScopeIdentifiers()) === true) {
            $this->createResource($values);
        } else {
            $this->inTransaction(function () use ($identifier, $values, $scopeIdentifiers) {
                $this->createResource($values);
                $this->bindScopeIdentifiers($identifier, $scopeIdentifiers);
            });
        }
        $client->setCreatedAt($now);

        return $client;
    }

    /**
     * @inheritdoc
     */
    public function bindScopes(string $identifier, array $scopes)
    {
        $scopeIdentifiers = [];
        foreach ($scopes as $scope) {
            /** @var ScopeInterface $scope */
            assert($scope instanceof ScopeInterface);
            $scopeIdentifiers[] = $scope->getIdentifier();
        }

        $this->bindScopeIdentifiers($identifier, $scopeIdentifiers);
    }

    /**
     * @param string   $identifier
     * @param string[] $scopeIdentifiers
     *
     * @return void
     */
    public function bindScopeIdentifiers(string $identifier, array $scopeIdentifiers)
    {
        if (empty($scopeIdentifiers) === false) {
            $scheme = $this->getDatabaseScheme();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $scheme->getClientsScopesTable(),
                $scheme->getClientsScopesClientIdentityColumn(),
                $scheme->getClientsScopesScopeIdentityColumn()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function unbindScopes(string $identifier)
    {
        $scheme = $this->getDatabaseScheme();
        $this->deleteBelongsToManyRelationshipIdentifiers(
            $scheme->getClientsScopesTable(),
            $scheme->getClientsScopesClientIdentityColumn(),
            $identifier
        );
    }

    /**
     * @inheritdoc
     */
    public function read(string $identifier)
    {
        return $this->readResource($identifier);
    }

    /**
     * @inheritdoc
     */
    public function readScopeIdentifiers(string $identifier): array
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readBelongsToManyRelationshipIdentifiers(
            $identifier,
            $scheme->getClientsScopesTable(),
            $scheme->getClientsScopesClientIdentityColumn(),
            $scheme->getClientsScopesScopeIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function readRedirectUriStrings(string $identifier): array
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readHasManyRelationshipColumn(
            $identifier,
            $scheme->getRedirectUrisTable(),
            $scheme->getRedirectUrisValueColumn(),
            $scheme->getRedirectUrisClientIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function update(ClientInterface $client)
    {
        $now    = new DateTimeImmutable();
        $scheme = $this->getDatabaseScheme();
        $this->updateResource($client->getIdentifier(), [
            $scheme->getClientsNameColumn()                   => $client->getName(),
            $scheme->getClientsDescriptionColumn()            => $client->getDescription(),
            $scheme->getClientsCredentialsColumn()            => $client->getCredentials(),
            $scheme->getClientsIsConfidentialColumn()         => $client->isConfidential(),
            $scheme->getClientsIsScopeExcessAllowedColumn()   => $client->isScopeExcessAllowed(),
            $scheme->getClientsIsUseDefaultScopeColumn()      => $client->isUseDefaultScopesOnEmptyRequest(),
            $scheme->getClientsIsCodeGrantEnabledColumn()     => $client->isCodeGrantEnabled(),
            $scheme->getClientsIsImplicitGrantEnabledColumn() => $client->isImplicitGrantEnabled(),
            $scheme->getClientsIsPasswordGrantEnabledColumn() => $client->isPasswordGrantEnabled(),
            $scheme->getClientsIsClientGrantEnabledColumn()   => $client->isClientGrantEnabled(),
            $scheme->getClientsUpdatedAtColumn()              => $now,
        ]);
        $client->setUpdatedAt($now);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $identifier)
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForWriting(): string
    {
        return $this->getDatabaseScheme()->getClientsTable();
    }

    /**
     * @inheritdoc
     */
    protected function getPrimaryKeyName(): string
    {
        return $this->getDatabaseScheme()->getClientsIdentityColumn();
    }
}

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
        $schema = $this->getDatabaseSchema();
        $values = [
            $schema->getClientsIdentityColumn()               => $client->getIdentifier(),
            $schema->getClientsNameColumn()                   => $client->getName(),
            $schema->getClientsDescriptionColumn()            => $client->getDescription(),
            $schema->getClientsCredentialsColumn()            => $client->getCredentials(),
            $schema->getClientsIsConfidentialColumn()         => $client->isConfidential(),
            $schema->getClientsIsScopeExcessAllowedColumn()   => $client->isScopeExcessAllowed(),
            $schema->getClientsIsUseDefaultScopeColumn()      => $client->isUseDefaultScopesOnEmptyRequest(),
            $schema->getClientsIsCodeGrantEnabledColumn()     => $client->isCodeGrantEnabled(),
            $schema->getClientsIsImplicitGrantEnabledColumn() => $client->isImplicitGrantEnabled(),
            $schema->getClientsIsPasswordGrantEnabledColumn() => $client->isPasswordGrantEnabled(),
            $schema->getClientsIsClientGrantEnabledColumn()   => $client->isClientGrantEnabled(),
            $schema->getClientsIsRefreshGrantEnabledColumn()  => $client->isRefreshGrantEnabled(),
            $schema->getClientsCreatedAtColumn()              => $now,
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
    public function bindScopes(string $identifier, array $scopes): void
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
    public function bindScopeIdentifiers(string $identifier, array $scopeIdentifiers): void
    {
        if (empty($scopeIdentifiers) === false) {
            $schema = $this->getDatabaseSchema();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $schema->getClientsScopesTable(),
                $schema->getClientsScopesClientIdentityColumn(),
                $schema->getClientsScopesScopeIdentityColumn()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function unbindScopes(string $identifier): void
    {
        $schema = $this->getDatabaseSchema();
        $this->deleteBelongsToManyRelationshipIdentifiers(
            $schema->getClientsScopesTable(),
            $schema->getClientsScopesClientIdentityColumn(),
            $identifier
        );
    }

    /**
     * @inheritdoc
     */
    public function read(string $identifier): ?ClientInterface
    {
        return $this->readResource($identifier);
    }

    /**
     * @inheritdoc
     */
    public function readScopeIdentifiers(string $identifier): array
    {
        $schema = $this->getDatabaseSchema();
        return $this->readBelongsToManyRelationshipIdentifiers(
            $identifier,
            $schema->getClientsScopesTable(),
            $schema->getClientsScopesClientIdentityColumn(),
            $schema->getClientsScopesScopeIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function readRedirectUriStrings(string $identifier): array
    {
        $schema = $this->getDatabaseSchema();
        return $this->readHasManyRelationshipColumn(
            $identifier,
            $schema->getRedirectUrisTable(),
            $schema->getRedirectUrisValueColumn(),
            $schema->getRedirectUrisClientIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function update(ClientInterface $client): void
    {
        $now    = new DateTimeImmutable();
        $schema = $this->getDatabaseSchema();
        $this->updateResource($client->getIdentifier(), [
            $schema->getClientsNameColumn()                   => $client->getName(),
            $schema->getClientsDescriptionColumn()            => $client->getDescription(),
            $schema->getClientsCredentialsColumn()            => $client->getCredentials(),
            $schema->getClientsIsConfidentialColumn()         => $client->isConfidential(),
            $schema->getClientsIsScopeExcessAllowedColumn()   => $client->isScopeExcessAllowed(),
            $schema->getClientsIsUseDefaultScopeColumn()      => $client->isUseDefaultScopesOnEmptyRequest(),
            $schema->getClientsIsCodeGrantEnabledColumn()     => $client->isCodeGrantEnabled(),
            $schema->getClientsIsImplicitGrantEnabledColumn() => $client->isImplicitGrantEnabled(),
            $schema->getClientsIsPasswordGrantEnabledColumn() => $client->isPasswordGrantEnabled(),
            $schema->getClientsIsClientGrantEnabledColumn()   => $client->isClientGrantEnabled(),
            $schema->getClientsUpdatedAtColumn()              => $now,
        ]);
        $client->setUpdatedAt($now);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $identifier): void
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForWriting(): string
    {
        return $this->getDatabaseSchema()->getClientsTable();
    }

    /**
     * @inheritdoc
     */
    protected function getPrimaryKeyName(): string
    {
        return $this->getDatabaseSchema()->getClientsIdentityColumn();
    }
}

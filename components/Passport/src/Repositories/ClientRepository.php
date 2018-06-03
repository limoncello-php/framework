<?php namespace Limoncello\Passport\Repositories;

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

use DateTimeImmutable;
use Limoncello\Passport\Contracts\Entities\ClientInterface;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Repositories\ClientRepositoryInterface;
use Limoncello\Passport\Exceptions\RepositoryException;

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
     * @throws RepositoryException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function create(ClientInterface $client): ClientInterface
    {
        try {
            $now    = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
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
        } catch (RepositoryException $exception) {
            $message = 'Client creation failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function bindScopes(string $identifier, iterable $scopes): void
    {
        $getIdentifiers = function (iterable $scopes): iterable {
            foreach ($scopes as $scope) {
                /** @var ScopeInterface $scope */
                assert($scope instanceof ScopeInterface);
                yield $scope->getIdentifier();
            }
        };

        $this->bindScopeIdentifiers($identifier, $getIdentifiers($scopes));
    }

    /**
     * @param string   $identifier
     * @param iterable $scopeIdentifiers
     *
     * @return void
     *
     * @throws RepositoryException
     */
    public function bindScopeIdentifiers(string $identifier, iterable $scopeIdentifiers): void
    {
        try {
            $schema = $this->getDatabaseSchema();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $schema->getClientsScopesTable(),
                $schema->getClientsScopesClientIdentityColumn(),
                $schema->getClientsScopesScopeIdentityColumn()
            );
        } catch (RepositoryException $exception) {
            $message = 'Binding client scopes failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function unbindScopes(string $identifier): void
    {
        try {
            $schema = $this->getDatabaseSchema();
            $this->deleteBelongsToManyRelationshipIdentifiers(
                $schema->getClientsScopesTable(),
                $schema->getClientsScopesClientIdentityColumn(),
                $identifier
            );
        } catch (RepositoryException $exception) {
            $message = 'Unbinding client scopes failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function read(string $identifier): ?ClientInterface
    {
        try {
            return $this->readResource($identifier);
        } catch (RepositoryException $exception) {
            $message = 'Reading client failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function readScopeIdentifiers(string $identifier): array
    {
        try {
            $schema = $this->getDatabaseSchema();
            return $this->readBelongsToManyRelationshipIdentifiers(
                $identifier,
                $schema->getClientsScopesTable(),
                $schema->getClientsScopesClientIdentityColumn(),
                $schema->getClientsScopesScopeIdentityColumn()
            );
        } catch (RepositoryException $exception) {
            $message = 'Reading client scope identifiers failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function readRedirectUriStrings(string $identifier): array
    {
        try {
            $schema = $this->getDatabaseSchema();
            return $this->readHasManyRelationshipColumn(
                $identifier,
                $schema->getRedirectUrisTable(),
                $schema->getRedirectUrisValueColumn(),
                $schema->getRedirectUrisClientIdentityColumn()
            );
        } catch (RepositoryException $exception) {
            $message = 'Reading client redirect URIs failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function update(ClientInterface $client): void
    {
        try {
            $now    = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
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
        } catch (RepositoryException $exception) {
            $message = 'Client update failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function delete(string $identifier): void
    {
        try {
            $this->deleteResource($identifier);
        } catch (RepositoryException $exception) {
            $message = 'Client deletion failed.';
            throw new RepositoryException($message, 0, $exception);
        }
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

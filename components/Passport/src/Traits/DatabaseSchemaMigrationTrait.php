<?php namespace Limoncello\Passport\Traits;

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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;

/**
 * @package Limoncello\Passport
 */
trait DatabaseSchemaMigrationTrait
{
    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createDatabaseSchema(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        try {
            $this->createScopesTable($connection, $schema);
            $this->createClientsTable($connection, $schema);
            $this->createRedirectUrisTable($connection, $schema);
            $this->createTokensTable($connection, $schema);
            $this->createClientsScopesTable($connection, $schema);
            $this->createTokensScopesTable($connection, $schema);
        } catch (DBALException $exception) {
            if ($connection->isConnected() === true) {
                $this->removeDatabaseSchema($connection, $schema);
            }

            throw $exception;
        }
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     */
    protected function removeDatabaseSchema(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        if ($manager->tablesExist([$schema->getTokensScopesTable()]) === true) {
            $manager->dropTable($schema->getTokensScopesTable());
        }
        if ($manager->tablesExist([$schema->getClientsScopesTable()]) === true) {
            $manager->dropTable($schema->getClientsScopesTable());
        }
        if ($manager->tablesExist([$schema->getTokensTable()]) === true) {
            $manager->dropTable($schema->getTokensTable());
        }
        if ($manager->tablesExist([$schema->getRedirectUrisTable()]) === true) {
            $manager->dropTable($schema->getRedirectUrisTable());
        }
        if ($manager->tablesExist([$schema->getClientsTable()]) === true) {
            $manager->dropTable($schema->getClientsTable());
        }
        if ($manager->tablesExist([$schema->getScopesTable()]) === true) {
            $manager->dropTable($schema->getScopesTable());
        }
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createScopesTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getScopesTable());
        $table->addColumn($schema->getScopesIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getScopesDescriptionColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getScopesCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($schema->getScopesUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$schema->getScopesIdentityColumn()]);

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createClientsTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getClientsTable());
        $table->addColumn($schema->getClientsIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getClientsNameColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getClientsDescriptionColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getClientsCredentialsColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getClientsIsConfidentialColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($schema->getClientsIsScopeExcessAllowedColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsIsUseDefaultScopeColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($schema->getClientsIsCodeGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsIsImplicitGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsIsPasswordGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsIsClientGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsIsRefreshGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($schema->getClientsCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($schema->getClientsUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$schema->getClientsIdentityColumn()]);
        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createRedirectUrisTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getRedirectUrisTable());
        $table->addColumn($schema->getRedirectUrisIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($schema->getRedirectUrisClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getRedirectUrisValueColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getRedirectUrisCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($schema->getRedirectUrisUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$schema->getRedirectUrisIdentityColumn()]);

        $table->addForeignKeyConstraint(
            $schema->getClientsTable(),
            [$schema->getRedirectUrisClientIdentityColumn()],
            [$schema->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createTokensTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getTokensTable());
        $table->addColumn($schema->getTokensIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($schema->getTokensIsEnabledColumn(), Type::BOOLEAN)->setNotnull(true)->setDefault(true);
        $table->addColumn($schema->getTokensIsScopeModified(), Type::BOOLEAN)->setNotnull(true)->setDefault(false);
        $table->addColumn($schema->getTokensClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getTokensUserIdentityColumn(), Type::INTEGER)->setNotnull(false)->setUnsigned(true);
        $table->addColumn($schema->getTokensRedirectUriColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getTokensCodeColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getTokensTypeColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getTokensValueColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getTokensRefreshColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($schema->getTokensCodeCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->addColumn($schema->getTokensValueCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->addColumn($schema->getTokensRefreshCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$schema->getTokensIdentityColumn()]);

        $table->addForeignKeyConstraint(
            $schema->getClientsTable(),
            [$schema->getTokensClientIdentityColumn()],
            [$schema->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $usersTable          = $schema->getUsersTable();
        $usersIdentityColumn = $schema->getUsersIdentityColumn();
        if ($usersTable !== null && $usersIdentityColumn !== null) {
            $table->addForeignKeyConstraint(
                $usersTable,
                [$schema->getTokensUserIdentityColumn()],
                [$usersIdentityColumn],
                $this->getOnDeleteCascadeConstraint()
            );
        }

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createClientsScopesTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getClientsScopesTable());
        $table->addColumn($schema->getClientsScopesIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($schema->getClientsScopesClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($schema->getClientsScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([$schema->getClientsScopesIdentityColumn()]);
        $table->addUniqueIndex([
            $schema->getClientsScopesClientIdentityColumn(),
            $schema->getClientsScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $schema->getClientsTable(),
            [$schema->getClientsScopesClientIdentityColumn()],
            [$schema->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $table->addForeignKeyConstraint(
            $schema->getScopesTable(),
            [$schema->getClientsScopesScopeIdentityColumn()],
            [$schema->getScopesIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createTokensScopesTable(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($schema->getTokensScopesTable());
        $table->addColumn($schema->getTokensScopesIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($schema->getTokensScopesTokenIdentityColumn(), Type::INTEGER)->setNotnull(true)
            ->setUnsigned(true);
        $table->addColumn($schema->getTokensScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([$schema->getTokensScopesIdentityColumn()]);
        $table->addUniqueIndex([
            $schema->getTokensScopesTokenIdentityColumn(),
            $schema->getTokensScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $schema->getTokensTable(),
            [$schema->getTokensScopesTokenIdentityColumn()],
            [$schema->getTokensIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $table->addForeignKeyConstraint(
            $schema->getScopesTable(),
            [$schema->getTokensScopesScopeIdentityColumn()],
            [$schema->getScopesIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @return array
     */
    protected function getOnDeleteCascadeConstraint(): array
    {
        return ['onDelete' => 'CASCADE'];
    }
}

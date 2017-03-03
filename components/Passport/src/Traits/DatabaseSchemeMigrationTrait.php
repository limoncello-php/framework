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
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;

/**
 * @package Limoncello\Passport
 */
trait DatabaseSchemeMigrationTrait
{
    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $this->createScopesTable($connection, $scheme);
        $this->createClientsTable($connection, $scheme);
        $this->createRedirectUrisTable($connection, $scheme);
        $this->createTokensTable($connection, $scheme);
        $this->createClientsScopesTable($connection, $scheme);
        $this->createTokensScopesTable($connection, $scheme);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function removeDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        if ($manager->tablesExist($scheme->getTokensScopesTable()) === true) {
            $manager->dropTable($scheme->getTokensScopesTable());
        }
        if ($manager->tablesExist($scheme->getClientsScopesTable()) === true) {
            $manager->dropTable($scheme->getClientsScopesTable());
        }
        if ($manager->tablesExist($scheme->getTokensTable()) === true) {
            $manager->dropTable($scheme->getTokensTable());
        }
        if ($manager->tablesExist($scheme->getRedirectUrisTable()) === true) {
            $manager->dropTable($scheme->getRedirectUrisTable());
        }
        if ($manager->tablesExist($scheme->getClientsTable()) === true) {
            $manager->dropTable($scheme->getClientsTable());
        }
        if ($manager->tablesExist($scheme->getScopesTable()) === true) {
            $manager->dropTable($scheme->getScopesTable());
        }
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createScopesTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getScopesTable());
        $table->addColumn($scheme->getScopesIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getScopesDescriptionColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getScopesCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($scheme->getScopesUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getScopesIdentityColumn()]);

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createClientsTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getClientsTable());
        $table->addColumn($scheme->getClientsIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getClientsNameColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getClientsDescriptionColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getClientsCredentialsColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getClientsIsConfidentialColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsScopeExcessAllowedColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsUseDefaultScopeColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsCodeGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsImplicitGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsPasswordGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsClientGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsRefreshGrantEnabledColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($scheme->getClientsUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getClientsIdentityColumn()]);
        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createRedirectUrisTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getRedirectUrisTable());
        $table->addColumn($scheme->getRedirectUrisIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($scheme->getRedirectUrisClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisValueColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getRedirectUrisIdentityColumn()]);

        $table->addForeignKeyConstraint(
            $scheme->getClientsTable(),
            [$scheme->getRedirectUrisClientIdentityColumn()],
            [$scheme->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createTokensTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getTokensTable());
        $table->addColumn($scheme->getTokensIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn($scheme->getTokensIsEnabledColumn(), Type::BOOLEAN)->setNotnull(true)->setDefault(true);
        $table->addColumn($scheme->getTokensIsScopeModified(), Type::BOOLEAN)->setNotnull(true)->setDefault(false);
        $table->addColumn($scheme->getTokensClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getTokensUserIdentityColumn(), Type::INTEGER)->setNotnull(true)->setUnsigned(true);
        $table->addColumn($scheme->getTokensRedirectUriColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getTokensCodeColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getTokensTypeColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getTokensValueColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getTokensRefreshColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getTokensCodeCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->addColumn($scheme->getTokensValueCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->addColumn($scheme->getTokensRefreshCreatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getTokensIdentityColumn()]);

        $table->addForeignKeyConstraint(
            $scheme->getClientsTable(),
            [$scheme->getTokensClientIdentityColumn()],
            [$scheme->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $usersTable          = $scheme->getUsersTable();
        $usersIdentityColumn = $scheme->getUsersIdentityColumn();
        if ($usersTable !== null && $usersIdentityColumn !== null) {
            $table->addForeignKeyConstraint(
                $usersTable,
                [$scheme->getTokensUserIdentityColumn()],
                [$usersIdentityColumn],
                $this->getOnDeleteCascadeConstraint()
            );
        }

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createClientsScopesTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getClientsScopesTable());
        $table->addColumn($scheme->getClientsScopesClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getClientsScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([
            $scheme->getClientsScopesClientIdentityColumn(),
            $scheme->getClientsScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $scheme->getClientsTable(),
            [$scheme->getClientsScopesClientIdentityColumn()],
            [$scheme->getClientsIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $table->addForeignKeyConstraint(
            $scheme->getScopesTable(),
            [$scheme->getClientsScopesScopeIdentityColumn()],
            [$scheme->getScopesIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createTokensScopesTable(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getTokensScopesTable());
        $table->addColumn($scheme->getTokensScopesTokenIdentityColumn(), Type::INTEGER)->setNotnull(true)
            ->setUnsigned(true);
        $table->addColumn($scheme->getTokensScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([
            $scheme->getTokensScopesTokenIdentityColumn(),
            $scheme->getTokensScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $scheme->getTokensTable(),
            [$scheme->getTokensScopesTokenIdentityColumn()],
            [$scheme->getTokensIdentityColumn()],
            $this->getOnDeleteCascadeConstraint()
        );

        $table->addForeignKeyConstraint(
            $scheme->getScopesTable(),
            [$scheme->getTokensScopesScopeIdentityColumn()],
            [$scheme->getScopesIdentityColumn()],
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

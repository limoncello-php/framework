<?php namespace Limoncello\Passport\Adaptors\PostgreSql;

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
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;
use Limoncello\Passport\Traits\DatabaseSchemaMigrationTrait as BaseDatabaseSchemaMigrationTrait;

/**
 * @package Limoncello\Passport
 */
trait DatabaseSchemaMigrationTrait
{
    use BaseDatabaseSchemaMigrationTrait {
        BaseDatabaseSchemaMigrationTrait::createDatabaseSchema as createDatabaseTables;
        BaseDatabaseSchemaMigrationTrait::removeDatabaseSchema as removeDatabaseTables;
    }

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
            $this->createDatabaseTables($connection, $schema);
            $this->createDatabaseViews($connection, $schema);
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
     *
     * @throws DBALException
     */
    protected function removeDatabaseSchema(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $this->removeDatabaseViews($connection, $schema);
        $this->removeDatabaseTables($connection, $schema);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function createDatabaseViews(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $this->createClientsView($connection, $schema);
        $this->createTokensView($connection, $schema);
        $this->createUsersView($connection, $schema);
        $this->createPassportView($connection, $schema);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function removeDatabaseViews(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $this->removePassportView($connection, $schema);
        $this->removeUsersView($connection, $schema);
        $this->removeTokensView($connection, $schema);
        $this->removeClientsView($connection, $schema);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function createTokensView(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $view                = $schema->getTokensView();
        $tokens              = $schema->getTokensTable();
        $intermediate        = $schema->getTokensScopesTable();
        $tokensTokenId       = $schema->getTokensIdentityColumn();
        $intermediateTokenId = $schema->getTokensScopesTokenIdentityColumn();
        $intermediateScopeId = $schema->getTokensScopesScopeIdentityColumn();
        $scopes              = $schema->getTokensViewScopesColumn();

        $sql = <<< EOT
CREATE OR REPLACE VIEW {$view} AS
    SELECT
      t.*,
      array_remove(array_agg(s.{$intermediateScopeId}), NULL) AS {$scopes}
    FROM {$tokens} AS t
      LEFT JOIN {$intermediate} AS s ON t.{$tokensTokenId} = s.{$intermediateTokenId}
    GROUP BY t.{$tokensTokenId};
EOT;
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function removeTokensView(Connection $connection, DatabaseSchemaInterface $schema)
    {
        $view = $schema->getTokensView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function createPassportView(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $tokensView   = $schema->getTokensView();
        $view         = $schema->getPassportView();
        $users        = $schema->getUsersTable();
        $tokensUserFk = $schema->getTokensUserIdentityColumn();

        $sql = <<< EOT
CREATE OR REPLACE VIEW {$view} AS
    SELECT *
    FROM $tokensView
      LEFT JOIN $users USING ($tokensUserFk);
EOT;
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function removePassportView(Connection $connection, DatabaseSchemaInterface $schema): void
    {
        $view = $schema->getPassportView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function createClientsView(Connection $connection, DatabaseSchemaInterface $schema)
    {
        $view             = $schema->getClientsView();
        $scopes           = $schema->getClientsViewScopesColumn();
        $redirectUris     = $schema->getClientsViewRedirectUrisColumn();
        $clientsScopes    = $schema->getClientsScopesTable();
        $clientsUris      = $schema->getRedirectUrisTable();
        $clients          = $schema->getClientsTable();
        $clientsClientId  = $schema->getClientsIdentityColumn();
        $clScopesClientId = $schema->getClientsScopesClientIdentityColumn();
        $clUrisClientId   = $schema->getRedirectUrisClientIdentityColumn();
        $urisValue        = $schema->getRedirectUrisValueColumn();
        $scopesScopeId    = $schema->getScopesIdentityColumn();
        $sql              = <<< EOT
CREATE VIEW {$view} AS
    SELECT
      c.*,
      array_remove(array_agg(s.{$scopesScopeId}), NULL) AS {$scopes},
      array_remove(array_agg(u.{$urisValue}), NULL)     AS {$redirectUris}
    FROM {$clients} AS c
      LEFT JOIN {$clientsScopes} AS s ON c.{$clientsClientId} = s.{$clScopesClientId}
      LEFT JOIN {$clientsUris}   AS u ON c.{$clientsClientId} = u.{$clUrisClientId}
    GROUP BY c.{$clientsClientId};
EOT;
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function removeClientsView(Connection $connection, DatabaseSchemaInterface $schema)
    {
        $view = $schema->getClientsView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function createUsersView(Connection $connection, DatabaseSchemaInterface $schema)
    {
        $users = $schema->getUsersTable();
        if ($users !== null) {
            $view            = $schema->getUsersView();
            $tokensValue     = $schema->getTokensValueColumn();
            $tokensValueAt   = $schema->getTokensValueCreatedAtColumn();
            $tokensScopes    = $schema->getTokensViewScopesColumn();
            $tokensView      = $schema->getTokensView();
            $tokensUserId    = $schema->getTokensUserIdentityColumn();
            $usersUserId     = $schema->getUsersIdentityColumn();
            $tokensIsEnabled = $schema->getTokensIsEnabledColumn();

            $sql = <<< EOT
CREATE OR REPLACE VIEW {$view} AS
    SELECT
        t.$tokensValue, t.$tokensValueAt, t.$tokensScopes, u.*
    FROM {$tokensView} AS t
      LEFT JOIN {$users} AS u ON t.{$tokensUserId} = u.{$usersUserId}
    WHERE $tokensIsEnabled IS TRUE;
EOT;
            $connection->exec($sql);
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection
     * @param Connection              $connection
     * @param DatabaseSchemaInterface $schema
     *
     * @throws DBALException
     *
     * @return void
     */
    private function removeUsersView(Connection $connection, DatabaseSchemaInterface $schema)
    {
        $view = $schema->getUsersView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }
}

<?php namespace Limoncello\Passport\Adaptors\MySql;

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
use Doctrine\DBAL\DBALException;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Traits\DatabaseSchemeMigrationTrait as BaseDatabaseSchemeMigrationTrait;

/**
 * @package Limoncello\Passport
 */
trait DatabaseSchemeMigrationTrait
{
    use BaseDatabaseSchemeMigrationTrait {
        BaseDatabaseSchemeMigrationTrait::createDatabaseScheme as createDatabaseTables;
        BaseDatabaseSchemeMigrationTrait::removeDatabaseScheme as removeDatabaseTables;
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        try {
            $this->createDatabaseTables($connection, $scheme);
            $this->createDatabaseViews($connection, $scheme);
        } catch (DBALException $exception) {
            if ($connection->isConnected() === true) {
                $this->removeDatabaseScheme($connection, $scheme);
            }

            throw $exception;
        }
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function removeDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $this->removeDatabaseTables($connection, $scheme);
        $this->removeDatabaseViews($connection, $scheme);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function createDatabaseViews(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $this->createClientsView($connection, $scheme);
        $this->createTokensView($connection, $scheme);
        $this->createUsersView($connection, $scheme);
        $this->createPassportView($connection, $scheme);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    protected function removeDatabaseViews(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $this->removePassportView($connection, $scheme);
        $this->removeClientsView($connection, $scheme);
        $this->removeTokensView($connection, $scheme);
        $this->removeUsersView($connection, $scheme);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createTokensView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view                = $scheme->getTokensView();
        $tokens              = $scheme->getTokensTable();
        $intermediate        = $scheme->getTokensScopesTable();
        $tokensTokenId       = $scheme->getTokensIdentityColumn();
        $intermediateTokenId = $scheme->getTokensScopesTokenIdentityColumn();
        $intermediateScopeId = $scheme->getTokensScopesScopeIdentityColumn();
        $scopes              = $scheme->getTokensViewScopesColumn();

        $sql = <<< EOT
CREATE OR REPLACE VIEW {$view} AS
    SELECT
      t.*,
      GROUP_CONCAT(DISTINCT s.{$intermediateScopeId} SEPARATOR ' ') AS {$scopes}
    FROM {$tokens} AS t
      LEFT JOIN {$intermediate} AS s ON t.{$tokensTokenId} = s.{$intermediateTokenId}
    GROUP BY t.{$tokensTokenId};
EOT;
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function removeTokensView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view = $scheme->getTokensView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createPassportView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $tokensView   = $scheme->getTokensView();
        $view         = $scheme->getPassportView();
        $users        = $scheme->getUsersTable();
        $tokensUserFk = $scheme->getTokensUserIdentityColumn();

        $sql = <<< EOT
CREATE OR REPLACE VIEW {$view} AS
    SELECT *
    FROM $tokensView
      LEFT JOIN $users USING ($tokensUserFk);
EOT;
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function removePassportView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view = $scheme->getPassportView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createClientsView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view             = $scheme->getClientsView();
        $scopes           = $scheme->getClientsViewScopesColumn();
        $redirectUris     = $scheme->getClientsViewRedirectUrisColumn();
        $clientsScopes    = $scheme->getClientsScopesTable();
        $clientsUris      = $scheme->getRedirectUrisTable();
        $clients          = $scheme->getClientsTable();
        $clientsClientId  = $scheme->getClientsIdentityColumn();
        $clScopesClientId = $scheme->getClientsScopesClientIdentityColumn();
        $clUrisClientId   = $scheme->getRedirectUrisClientIdentityColumn();
        $urisValue        = $scheme->getRedirectUrisValueColumn();
        $scopesScopeId    = $scheme->getScopesIdentityColumn();
        $sql              = <<< EOT
CREATE VIEW {$view} AS
    SELECT
      c.*,
      GROUP_CONCAT(DISTINCT s.{$scopesScopeId} SEPARATOR ' ') AS {$scopes},
      GROUP_CONCAT(DISTINCT u.{$urisValue} SEPARATOR ' ')     AS {$redirectUris}
    FROM {$clients} AS c
      LEFT JOIN {$clientsScopes} AS s ON c.{$clientsClientId} = s.{$clScopesClientId}
      LEFT JOIN {$clientsUris}   AS u ON c.{$clientsClientId} = u.{$clUrisClientId}
    GROUP BY c.{$clientsClientId};
EOT;
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function removeClientsView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view = $scheme->getClientsView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function createUsersView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $users = $scheme->getUsersTable();
        if ($users !== null) {
            $view            = $scheme->getUsersView();
            $tokensValue     = $scheme->getTokensValueColumn();
            $tokensValueAt   = $scheme->getTokensValueCreatedAtColumn();
            $tokensScopes    = $scheme->getTokensViewScopesColumn();
            $tokensView      = $scheme->getTokensView();
            $tokensUserId    = $scheme->getTokensUserIdentityColumn();
            $usersUserId     = $scheme->getUsersIdentityColumn();
            $tokensIsEnabled = $scheme->getTokensIsEnabledColumn();

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

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @throws DBALException
     *
     * @return void
     */
    protected function removeUsersView(Connection $connection, DatabaseSchemeInterface $scheme)
    {
        $view = $scheme->getUsersView();
        $sql  = "DROP VIEW IF EXISTS {$view}";
        $connection->exec($sql);
    }
}

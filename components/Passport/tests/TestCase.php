<?php namespace Limoncello\Tests\Passport;

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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Entities\DatabaseScheme;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

/**
 * @package Limoncello\Tests\Passport
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * DBAL option.
     */
    const ON_DELETE_CASCADE = ['onDelete' => 'CASCADE'];

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    /**
     * @return Connection
     */
    protected function createSqLiteConnection(): Connection
    {
        //$env = (new \Dotenv\Dotenv(__DIR__))->load();
        $connection = DriverManager::getConnection(['url' => 'sqlite:///', 'memory' => true]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    protected function createDatabaseScheme(Connection $connection)
    {
        $this->createScopesTable($connection);
        $this->createClientsTable($connection);
        $this->createRedirectUrisTable($connection);
        $this->createTokensTable($connection);
        $this->createClientsScopesTable($connection);
        $this->createTokensScopesTable($connection);
    }

    /**
     * @return DatabaseSchemeInterface
     */
    protected function getDatabaseScheme(): DatabaseSchemeInterface
    {
        return new DatabaseScheme();
    }

    /**
     * @param array|null $postData
     * @param array|null $queryParameters
     * @param array|null $headers
     *
     * @return ServerRequestInterface
     */
    protected function createServerRequest(
        array $postData = null,
        array $queryParameters = null,
        array $headers = null
    ): ServerRequestInterface {
        $removeNulls = function (array $values) {
            return array_filter($values, function ($value) {
                return $value !== null;
            });
        };

        $server = null;
        $query  = null;
        $body   = null;

        if ($headers !== null) {
            foreach ($removeNulls($headers) as $header => $value) {
                $server['HTTP_' . $header] = $value;
            }
        }

        if ($queryParameters !== null) {
            $query = $removeNulls($queryParameters);
        }

        if ($postData !== null) {
            $body = $removeNulls($postData);
        }

        $request = ServerRequestFactory::fromGlobals($server, $query, $body);

        return $request;
    }

    /**
     * @param ResponseInterface $response
     * @param int               $httpStatus
     * @param array             $body
     * @param string[]          $headers
     *
     * @return void
     */
    protected function validateBodyResponse(
        ResponseInterface $response,
        int $httpStatus,
        array $body,
        array $headers = []
    ) {
        $headers += [
            'Content-type'  => 'application/json',
            'Cache-Control' => 'no-store',
            'Pragma'        => 'no-cache',
        ];

        $this->assertEquals($httpStatus, $response->getStatusCode());
        foreach ($headers as $header => $value) {
            $this->assertEquals([$value], $response->getHeader($header));
        }
        $this->assertNotNull(false, $encoded = json_decode((string)$response->getBody(), true));
        $this->assertEquals($body, $encoded);
    }

    /**
     * @param ResponseInterface $response
     * @param string            $redirectUri
     * @param array             $expectedFragments
     * @param int               $httpStatus
     * @param string[]          $headers
     *
     * @return void
     */
    protected function validateRedirectResponse(
        ResponseInterface $response,
        string $redirectUri,
        array $expectedFragments,
        int $httpStatus = 302,
        array $headers = []
    ) {
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Location'));
        $this->assertCount(1, $response->getHeader('Location'));
        list($location) = $response->getHeader('Location');

        $locationUri = new Uri($location);

        $this->assertEquals($redirectUri, $locationUri->withFragment(''));

        parse_str($locationUri->getFragment(), $fragments);
        $this->assertEquals($expectedFragments, $fragments);

        $this->assertEquals($httpStatus, $response->getStatusCode());
        foreach ($headers as $header => $value) {
            $this->assertEquals([$value], $response->getHeader($header));
        }
        $this->assertEmpty((string)$response->getBody());
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function createScopesTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
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
     * @param Connection $connection
     *
     * @return void
     */
    private function createClientsTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getClientsTable());
        $table->addColumn($scheme->getClientsIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getClientsNameColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getClientsDescriptionColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getClientsCredentialsColumn(), Type::STRING)->setNotnull(false);
        $table->addColumn($scheme->getClientsIsConfidentialColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsScopeExcessAllowedColumn(), Type::BOOLEAN)->setDefault(false);
        $table->addColumn($scheme->getClientsIsUseDefaultScopeColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsCodeGrantEnabledColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsImplicitGrantEnabledColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsPasswordGrantEnabledColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsIsClientGrantEnabledColumn(), Type::BOOLEAN)->setDefault(true);
        $table->addColumn($scheme->getClientsCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($scheme->getClientsUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getClientsIdentityColumn()]);
        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function createRedirectUrisTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getRedirectUrisTable());
        $table->addColumn($scheme->getRedirectUrisIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true);
        $table->addColumn($scheme->getRedirectUrisClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisValueColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisCreatedAtColumn(), Type::DATETIME)->setNotnull(true);
        $table->addColumn($scheme->getRedirectUrisUpdatedAtColumn(), Type::DATETIME)->setNotnull(false);
        $table->setPrimaryKey([$scheme->getRedirectUrisIdentityColumn()]);

        $table->addForeignKeyConstraint(
            $scheme->getClientsTable(),
            [$scheme->getRedirectUrisClientIdentityColumn()],
            [$scheme->getClientsIdentityColumn()],
            static::ON_DELETE_CASCADE
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function createTokensTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getTokensTable());
        $table->addColumn($scheme->getTokensIdentityColumn(), Type::INTEGER)
            ->setNotnull(true)->setAutoincrement(true);
        $table->addColumn($scheme->getTokensIsEnabledColumn(), Type::BOOLEAN)->setNotnull(true)->setDefault(true);
        $table->addColumn($scheme->getTokensClientIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->addColumn($scheme->getTokensUserIdentityColumn(), Type::INTEGER)->setNotnull(true);
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
            static::ON_DELETE_CASCADE
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function createClientsScopesTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getClientsScopesTable());
        $table->addColumn($scheme->getClientsScopesClientIdentityColumn(), Type::INTEGER)->setNotnull(true);
        $table->addColumn($scheme->getClientsScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([
            $scheme->getClientsScopesClientIdentityColumn(),
            $scheme->getClientsScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $scheme->getClientsTable(),
            [$scheme->getClientsScopesClientIdentityColumn()],
            [$scheme->getClientsIdentityColumn()],
            static::ON_DELETE_CASCADE
        );

        $table->addForeignKeyConstraint(
            $scheme->getScopesTable(),
            [$scheme->getClientsScopesScopeIdentityColumn()],
            [$scheme->getScopesIdentityColumn()],
            static::ON_DELETE_CASCADE
        );

        $manager->dropAndCreateTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return void
     */
    private function createTokensScopesTable(Connection $connection)
    {
        $scheme  = $this->getDatabaseScheme();
        $manager = $connection->getSchemaManager();

        $table = new Table($scheme->getTokensScopesTable());
        $table->addColumn($scheme->getTokensScopesTokenIdentityColumn(), Type::INTEGER)->setNotnull(true);
        $table->addColumn($scheme->getTokensScopesScopeIdentityColumn(), Type::STRING)->setNotnull(true);
        $table->setPrimaryKey([
            $scheme->getTokensScopesTokenIdentityColumn(),
            $scheme->getTokensScopesScopeIdentityColumn()
        ]);

        $table->addForeignKeyConstraint(
            $scheme->getTokensTable(),
            [$scheme->getTokensScopesTokenIdentityColumn()],
            [$scheme->getTokensIdentityColumn()],
            static::ON_DELETE_CASCADE
        );

        $table->addForeignKeyConstraint(
            $scheme->getScopesTable(),
            [$scheme->getTokensScopesScopeIdentityColumn()],
            [$scheme->getScopesIdentityColumn()],
            static::ON_DELETE_CASCADE
        );

        $manager->dropAndCreateTable($table);
    }
}

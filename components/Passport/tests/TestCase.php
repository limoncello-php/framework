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
use Dotenv\Dotenv;
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
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    abstract protected function createDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme);

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $scheme
     *
     * @return void
     */
    abstract protected function removeDatabaseScheme(Connection $connection, DatabaseSchemeInterface $scheme);

    /**
     * DBAL option.
     */
    const ON_DELETE_CASCADE = ['onDelete' => 'CASCADE'];

    /**
     * @var Connection
     */
    private $connection = null;

    /**
     * @var DatabaseSchemeInterface|null
     */
    private $databaseScheme = null;

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        if ($this->getConnection() !== null && $this->getDatabaseScheme() !== null) {
            $this->removeDatabaseScheme($this->connection, $this->databaseScheme);
            $this->connection->rollBack();
            $this->connection->close();
            $this->connection     = null;
            $this->databaseScheme = null;
        }

        Mockery::close();
    }

    /**
     * Init MySQL database.
     */
    protected function initMySqlDatabase()
    {
        $this->databaseScheme = new DatabaseScheme('users', 'id_user');

        $this->connection = $this->createMySqlDatabaseConnection();
        $this->connection->beginTransaction();

        // create users table
        $table = new Table('users');
        $table->addColumn('id_user', Type::INTEGER)->setNotnull(true)->setAutoincrement(true)->setUnsigned(true);
        $table->addColumn('name', Type::STRING)->setNotnull(true);
        $table->setPrimaryKey(['id_user']);
        $this->connection->getSchemaManager()->dropAndCreateTable($table);

        $this->createDatabaseScheme($this->connection, $this->databaseScheme);
    }

    /**
     * Init SQLite database.
     */
    protected function initSqliteDatabase()
    {
        $this->databaseScheme = new DatabaseScheme();

        $this->connection = $this->createSqliteDatabaseConnection();
        $this->connection->beginTransaction();

        $this->createDatabaseScheme($this->connection, $this->databaseScheme);
    }

    /**
     * @return Connection
     */
    private function createSqliteDatabaseConnection(): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///', 'memory' => true]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @return Connection
     */
    private function createMySqlDatabaseConnection(): Connection
    {
        (new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..'))->load();
        $connection = DriverManager::getConnection([
            'driver'       => getenv('DB_MY_SQL_DRIVER'),
            'host'         => getenv('DB_MY_SQL_HOST'),
            'port'         => getenv('DB_MY_SQL_PORT'),
            'dbname'       => getenv('DB_MY_SQL_DATABASE'),
            'user'         => getenv('DB_MY_SQL_USER_NAME'),
            'password'     => getenv('DB_MY_SQL_PASSWORD'),
            'charset'      => getenv('DB_MY_SQL_CHARSET'),
        ]);

        return $connection;
    }

    /**
     * @return DatabaseSchemeInterface
     */
    protected function getDatabaseScheme(): DatabaseSchemeInterface
    {
        return $this->databaseScheme;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->connection;
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
}

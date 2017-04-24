<?php namespace Limoncello\Passport\Adaptors\Generic;

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
use Doctrine\DBAL\Query\QueryBuilder;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use PDO;

/**
 * @package Limoncello\Passport
 */
class TokenRepository extends \Limoncello\Passport\Repositories\TokenRepository
{
    /**
     * @var string
     */
    private $modelClass;

    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $databaseScheme
     * @param string                  $modelClass
     */
    public function __construct(
        Connection $connection,
        DatabaseSchemeInterface $databaseScheme,
        string $modelClass = Token::class
    ) {
        $this->setConnection($connection)->setDatabaseScheme($databaseScheme);
        $this->modelClass = $modelClass;
    }

    /**
     * @inheritdoc
     */
    public function read(int $identifier)
    {
        $token = parent::read($identifier);

        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByCode(string $code, int $expirationInSeconds)
    {
        $token = parent::readByCode($code, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds)
    {
        $token = parent::readByValue($tokenValue, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds)
    {
        $token = parent::readByRefresh($refreshValue, $expirationInSeconds);
        if ($token !== null) {
            $this->addScope($token);
        }

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function readPassport(string $tokenValue, int $expirationInSeconds)
    {
        $statement = $this->createPassportDataQuery($tokenValue, $expirationInSeconds)->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $data = $statement->fetch();
        $result = null;
        if ($data !== false) {
            $scheme  = $this->getDatabaseScheme();
            $tokenId = $data[$scheme->getTokensIdentityColumn()];
            $scopes  =  $this->readScopeIdentifiers($tokenId);
            $data[$scheme->getTokensViewScopesColumn()] = $scopes;
            $result = $data;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getClassName(): string
    {
        return $this->modelClass;
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForReading(): string
    {
        return $this->getTableNameForWriting();
    }

    /**
     * @param string $tokenValue
     * @param int    $expirationInSeconds
     *
     * @return QueryBuilder
     */
    private function createPassportDataQuery(
        string $tokenValue,
        int $expirationInSeconds
    ): QueryBuilder {
        $scheme = $this->getDatabaseScheme();
        $query  = $this->createEnabledTokenByColumnWithExpirationCheckQuery(
            $tokenValue,
            $scheme->getTokensValueColumn(),
            $expirationInSeconds,
            $scheme->getTokensValueCreatedAtColumn()
        );

        $tokensTable = $this->getTableNameForReading();
        $usersTable  = $aliased = $scheme->getUsersTable();
        $usersFk     = $scheme->getTokensUserIdentityColumn();
        $usersPk     = $scheme->getUsersIdentityColumn();
        $query->innerJoin(
            $tokensTable,
            $usersTable,
            $aliased,
            "`$tokensTable`.`$usersFk` = `$aliased`.`$usersPk`"
        );

        return $query;
    }

    /**
     * @param TokenInterface $token
     *
     * @return void
     */
    private function addScope(TokenInterface $token)
    {
        $token->setScopeIdentifiers($this->readScopeIdentifiers($token->getIdentifier()));
    }
}

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
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use PDO;

/**
 * @package Limoncello\Passport
 */
class TokenRepository extends \Limoncello\Passport\Repositories\TokenRepository
{
    /**
     * @param Connection              $connection
     * @param DatabaseSchemeInterface $databaseScheme
     */
    public function __construct(Connection $connection, DatabaseSchemeInterface $databaseScheme)
    {
        $this->setConnection($connection)->setDatabaseScheme($databaseScheme);
    }

    /**
     * @param string $token
     * @param int    $expirationInSeconds
     * @param string $userClass
     *
     * @return array
     */
    public function readUserByToken(string $token, int $expirationInSeconds, string $userClass): array
    {
        $query = $this->getConnection()->createQueryBuilder();

        $scheme          = $this->getDatabaseScheme();
        $createdAtColumn = $scheme->getTokensValueCreatedAtColumn();
        $tokenValue      = $scheme->getTokensValueColumn();
        $statement       = $this->addExpirationCondition(
            $query->select(['*'])
                ->from($scheme->getUsersView())
                ->where($tokenValue . '=' . $this->createTypedParameter($query, $token)),
            $expirationInSeconds,
            $createdAtColumn
        )->execute();

        $statement->setFetchMode(PDO::FETCH_CLASS, $userClass);
        $userOrFalse = $statement->fetch();
        if ($userOrFalse === false) {
            $user   = null;
            $scopes = null;
        } else {
            $scopesColumn = $scheme->getClientsViewScopesColumn();
            $scopes       = $userOrFalse->{$scopesColumn};
            unset($userOrFalse->{$scopesColumn});
            unset($userOrFalse->{$tokenValue});
            unset($userOrFalse->{$createdAtColumn});
            $user = $userOrFalse;
        }

        return [$user, $scopes];
    }

    /**
     * @inheritdoc
     */
    protected function getClassName(): string
    {
        return Token::class;
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForReading(): string
    {
        return $this->getDatabaseScheme()->getTokensView();
    }
}

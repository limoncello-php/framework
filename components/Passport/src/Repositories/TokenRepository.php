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

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use PDO;

/**
 * @package Limoncello\Passport
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class TokenRepository extends BaseRepository implements TokenRepositoryInterface
{
    /**
     * @inheritdoc
     *
     * @throws Exception
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createCode(TokenInterface $code): TokenInterface
    {
        $now    = new DateTimeImmutable();
        $schema = $this->getDatabaseSchema();
        $values = [
            $schema->getTokensClientIdentityColumn() => $code->getClientIdentifier(),
            $schema->getTokensUserIdentityColumn()   => $code->getUserIdentifier(),
            $schema->getTokensCodeColumn()           => $code->getCode(),
            $schema->getTokensIsScopeModified()      => $code->isScopeModified(),
            $schema->getTokensCodeCreatedAtColumn()  => $now,
        ];

        $tokenIdentifier = null;
        if (empty($scopeIdentifiers = $code->getScopeIdentifiers()) === false) {
            $this->inTransaction(function () use ($values, $scopeIdentifiers, &$tokenIdentifier) {
                $tokenIdentifier = $this->createResource($values);
                $this->bindScopeIdentifiers($tokenIdentifier, $scopeIdentifiers);
            });
        } else {
            $tokenIdentifier = $this->createResource($values);
        }

        $code->setIdentifier($tokenIdentifier)->setCodeCreatedAt($now);

        return $code;
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     * @throws DBALException
     */
    public function assignValuesToCode(TokenInterface $token, int $expirationInSeconds): void
    {
        $query = $this->getConnection()->createQueryBuilder();

        $now             = new DateTimeImmutable();
        $dbNow           = $this->createTypedParameter($query, $now);
        $earliestExpired = $now->sub(new DateInterval("PT{$expirationInSeconds}S"));
        $schema          = $this->getDatabaseSchema();
        $query
            ->update($this->getTableNameForWriting())
            ->where($schema->getTokensCodeColumn() . '=' . $this->createTypedParameter($query, $token->getCode()))
            ->andWhere(
                $schema->getTokensCodeCreatedAtColumn() . '>' . $this->createTypedParameter($query, $earliestExpired)
            )
            ->set($schema->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
            ->set($schema->getTokensTypeColumn(), $this->createTypedParameter($query, $token->getType()))
            ->set($schema->getTokensValueCreatedAtColumn(), $dbNow);

        if ($token->getRefreshValue() !== null) {
            $query
                ->set($schema->getTokensRefreshColumn(), $this->createTypedParameter($query, $token->getRefreshValue()))
                ->set($schema->getTokensRefreshCreatedAtColumn(), $dbNow);
        }

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createToken(TokenInterface $token): TokenInterface
    {
        $now        = new DateTimeImmutable();
        $schema     = $this->getDatabaseSchema();
        $hasRefresh = $token->getRefreshValue() !== null;
        $values     = $hasRefresh === false ? [
            $schema->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $schema->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $schema->getTokensValueColumn()            => $token->getValue(),
            $schema->getTokensTypeColumn()             => $token->getType(),
            $schema->getTokensIsScopeModified()        => $token->isScopeModified(),
            $schema->getTokensIsEnabledColumn()        => $token->isEnabled(),
            $schema->getTokensValueCreatedAtColumn()   => $now,
        ] : [
            $schema->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $schema->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $schema->getTokensValueColumn()            => $token->getValue(),
            $schema->getTokensTypeColumn()             => $token->getType(),
            $schema->getTokensIsScopeModified()        => $token->isScopeModified(),
            $schema->getTokensIsEnabledColumn()        => $token->isEnabled(),
            $schema->getTokensValueCreatedAtColumn()   => $now,
            $schema->getTokensRefreshColumn()          => $token->getRefreshValue(),
            $schema->getTokensRefreshCreatedAtColumn() => $now,
        ];

        $tokenIdentifier = null;
        if (empty($scopeIdentifiers = $token->getScopeIdentifiers()) === false) {
            $this->inTransaction(function () use ($values, $scopeIdentifiers, &$tokenIdentifier) {
                $tokenIdentifier = $this->createResource($values);
                $this->bindScopeIdentifiers($tokenIdentifier, $scopeIdentifiers);
            });
        } else {
            $tokenIdentifier = $this->createResource($values);
        }

        $token->setIdentifier($tokenIdentifier)->setValueCreatedAt($now);
        if ($hasRefresh === true) {
            $token->setRefreshCreatedAt($now);
        }

        return $token;
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function bindScopes(int $identifier, array $scopes): void
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
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function bindScopeIdentifiers(int $identifier, array $scopeIdentifiers): void
    {
        if (empty($scopeIdentifiers) === false) {
            $schema = $this->getDatabaseSchema();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $schema->getTokensScopesTable(),
                $schema->getTokensScopesTokenIdentityColumn(),
                $schema->getTokensScopesScopeIdentityColumn()
            );
        }
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function unbindScopes(int $identifier): void
    {
        $schema = $this->getDatabaseSchema();
        $this->deleteBelongsToManyRelationshipIdentifiers(
            $schema->getTokensScopesTable(),
            $schema->getTokensScopesTokenIdentityColumn(),
            $identifier
        );
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function read(int $identifier): ?TokenInterface
    {
        return $this->readResource($identifier);
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function readByCode(string $code, int $expirationInSeconds): ?TokenInterface
    {
        $schema = $this->getDatabaseSchema();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $code,
            $schema->getTokensCodeColumn(),
            $expirationInSeconds,
            $schema->getTokensCodeCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds): ?TokenInterface
    {
        $schema = $this->getDatabaseSchema();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $tokenValue,
            $schema->getTokensValueColumn(),
            $expirationInSeconds,
            $schema->getTokensValueCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds): ?TokenInterface
    {
        $schema = $this->getDatabaseSchema();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $refreshValue,
            $schema->getTokensRefreshColumn(),
            $expirationInSeconds,
            $schema->getTokensRefreshCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function readByUser(int $userId, int $expirationInSeconds, int $limit = null): array
    {
        $schema = $this->getDatabaseSchema();
        /** @var TokenInterface[] $tokens */
        $tokens = $this->readEnabledTokensByColumnWithExpirationCheck(
            $userId,
            $schema->getTokensUserIdentityColumn(),
            $expirationInSeconds,
            $schema->getTokensValueCreatedAtColumn(),
            ['*'],
            $limit
        );

        return $tokens;
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function readScopeIdentifiers(int $identifier): array
    {
        $schema = $this->getDatabaseSchema();
        return $this->readBelongsToManyRelationshipIdentifiers(
            $identifier,
            $schema->getTokensScopesTable(),
            $schema->getTokensScopesTokenIdentityColumn(),
            $schema->getTokensScopesScopeIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     * @throws Exception
     */
    public function updateValues(TokenInterface $token): void
    {
        $query = $this->getConnection()->createQueryBuilder();

        $schema = $this->getDatabaseSchema();
        $now    = new DateTimeImmutable();
        $dbNow  = $this->createTypedParameter($query, $now);
        $query
            ->update($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $token->getIdentifier()))
            ->set($schema->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
            ->set($schema->getTokensValueCreatedAtColumn(), $dbNow);
        if ($token->getRefreshValue() !== null) {
            $query
                ->set($schema->getTokensRefreshColumn(), $this->createTypedParameter($query, $token->getRefreshValue()))
                ->set($schema->getTokensRefreshCreatedAtColumn(), $dbNow);
        }

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
        if ($numberOfUpdated > 0) {
            $token->setValueCreatedAt($now);
            if ($token->getRefreshValue() !== null) {
                $token->setRefreshCreatedAt($now);
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function delete(int $identifier): void
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     *
     * @throws DBALException
     */
    public function disable(int $identifier): void
    {
        $query = $this->getConnection()->createQueryBuilder();

        $schema = $this->getDatabaseSchema();
        $query
            ->update($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $identifier))
            ->set($schema->getTokensIsEnabledColumn(), $this->createTypedParameter($query, false));

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForWriting(): string
    {
        return $this->getDatabaseSchema()->getTokensTable();
    }

    /**
     * @inheritdoc
     */
    protected function getPrimaryKeyName(): string
    {
        return $this->getDatabaseSchema()->getTokensIdentityColumn();
    }

    /**
     * @param string $identifier
     * @param string $column
     * @param int    $expirationInSeconds
     * @param string $createdAtColumn
     * @param array  $columns
     *
     * @return TokenInterface|null
     *
     * @throws DBALException
     */
    protected function readEnabledTokenByColumnWithExpirationCheck(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*']
    ): ?TokenInterface {
        $query = $this->createEnabledTokenByColumnWithExpirationCheckQuery(
            $identifier,
            $column,
            $expirationInSeconds,
            $createdAtColumn,
            $columns
        );

        $statement = $query->execute();
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string   $identifier
     * @param string   $column
     * @param int      $expirationInSeconds
     * @param string   $createdAtColumn
     * @param array    $columns
     * @param int|null $limit
     *
     * @return array
     *
     * @throws DBALException
     */
    protected function readEnabledTokensByColumnWithExpirationCheck(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*'],
        int $limit = null
    ): array {
        $query = $this->createEnabledTokenByColumnWithExpirationCheckQuery(
            $identifier,
            $column,
            $expirationInSeconds,
            $createdAtColumn,
            $columns
        );
        $limit === null ?: $query->setMaxResults($limit);

        $statement = $query->execute();
        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());

        $result = [];
        while (($token = $statement->fetch()) !== false) {
            /** @var TokenInterface $token */
            $result[$token->getIdentifier()] = $token;
        }

        return $result;
    }

    /**
     * @param string $identifier
     * @param string $column
     * @param int    $expirationInSeconds
     * @param string $createdAtColumn
     * @param array  $columns
     *
     * @return QueryBuilder
     *
     * @throws DBALException
     */
    protected function createEnabledTokenByColumnWithExpirationCheckQuery(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*']
    ): QueryBuilder {
        $query = $this->getConnection()->createQueryBuilder();
        $query = $this->addExpirationCondition(
            $query->select($columns)
                ->from($this->getTableNameForReading())
                ->where($column . '=' . $this->createTypedParameter($query, $identifier))
                ->andWhere($query->expr()->eq($this->getDatabaseSchema()->getTokensIsEnabledColumn(), '1')),
            $expirationInSeconds,
            $createdAtColumn
        );

        return $query;
    }

    /**
     * @param QueryBuilder $query
     * @param int          $expirationInSeconds
     * @param string       $createdAtColumn
     *
     * @return QueryBuilder
     *
     * @throws DBALException
     * @throws Exception
     */
    protected function addExpirationCondition(
        QueryBuilder $query,
        int $expirationInSeconds,
        string $createdAtColumn
    ): QueryBuilder {
        $earliestExpired = (new DateTimeImmutable())->sub(new DateInterval("PT{$expirationInSeconds}S"));
        $query->andWhere($createdAtColumn . '>' . $this->createTypedParameter($query, $earliestExpired));

        return $query;
    }
}

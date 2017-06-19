<?php namespace Limoncello\Passport\Repositories;

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

use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Query\QueryBuilder;
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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createCode(TokenInterface $code): TokenInterface
    {
        $now    = new DateTimeImmutable();
        $scheme = $this->getDatabaseScheme();
        $values = [
            $scheme->getTokensClientIdentityColumn() => $code->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()   => $code->getUserIdentifier(),
            $scheme->getTokensCodeColumn()           => $code->getCode(),
            $scheme->getTokensIsScopeModified()      => $code->isScopeModified(),
            $scheme->getTokensCodeCreatedAtColumn()  => $now,
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
     */
    public function assignValuesToCode(TokenInterface $token, int $expirationInSeconds)
    {
        $query = $this->getConnection()->createQueryBuilder();

        $now             = new DateTimeImmutable();
        $dbNow           = $this->createTypedParameter($query, $now);
        $earliestExpired = $now->sub(new DateInterval("PT{$expirationInSeconds}S"));
        $scheme          = $this->getDatabaseScheme();
        $query
            ->update($this->getTableNameForWriting())
            ->where($scheme->getTokensCodeColumn() . '=' . $this->createTypedParameter($query, $token->getCode()))
            ->andWhere(
                $scheme->getTokensCodeCreatedAtColumn() . '>' . $this->createTypedParameter($query, $earliestExpired)
            )
            ->set($scheme->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
            ->set($scheme->getTokensTypeColumn(), $this->createTypedParameter($query, $token->getType()))
            ->set($scheme->getTokensValueCreatedAtColumn(), $dbNow);

        if ($token->getRefreshValue() !== null) {
            $query
                ->set($scheme->getTokensRefreshColumn(), $this->createTypedParameter($query, $token->getRefreshValue()))
                ->set($scheme->getTokensRefreshCreatedAtColumn(), $dbNow);
        }

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createToken(TokenInterface $token): TokenInterface
    {
        $now        = new DateTimeImmutable();
        $scheme     = $this->getDatabaseScheme();
        $hasRefresh = $token->getRefreshValue() !== null;
        $values     = $hasRefresh === false ? [
            $scheme->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $scheme->getTokensValueColumn()            => $token->getValue(),
            $scheme->getTokensTypeColumn()             => $token->getType(),
            $scheme->getTokensIsScopeModified()        => $token->isScopeModified(),
            $scheme->getTokensIsEnabledColumn()        => $token->isEnabled(),
            $scheme->getTokensValueCreatedAtColumn()   => $now,
        ] : [
            $scheme->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $scheme->getTokensValueColumn()            => $token->getValue(),
            $scheme->getTokensTypeColumn()             => $token->getType(),
            $scheme->getTokensIsScopeModified()        => $token->isScopeModified(),
            $scheme->getTokensIsEnabledColumn()        => $token->isEnabled(),
            $scheme->getTokensValueCreatedAtColumn()   => $now,
            $scheme->getTokensRefreshColumn()          => $token->getRefreshValue(),
            $scheme->getTokensRefreshCreatedAtColumn() => $now,
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
     */
    public function bindScopes(int $identifier, array $scopes)
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
     */
    public function bindScopeIdentifiers(int $identifier, array $scopeIdentifiers)
    {
        if (empty($scopeIdentifiers) === false) {
            $scheme = $this->getDatabaseScheme();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $scheme->getTokensScopesTable(),
                $scheme->getTokensScopesTokenIdentityColumn(),
                $scheme->getTokensScopesScopeIdentityColumn()
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function unbindScopes(int $identifier)
    {
        $scheme = $this->getDatabaseScheme();
        $this->deleteBelongsToManyRelationshipIdentifiers(
            $scheme->getTokensScopesTable(),
            $scheme->getTokensScopesTokenIdentityColumn(),
            $identifier
        );
    }

    /**
     * @inheritdoc
     */
    public function read(int $identifier)
    {
        return $this->readResource($identifier);
    }

    /**
     * @inheritdoc
     */
    public function readByCode(string $code, int $expirationInSeconds)
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $code,
            $scheme->getTokensCodeColumn(),
            $expirationInSeconds,
            $scheme->getTokensCodeCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function readByValue(string $tokenValue, int $expirationInSeconds)
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $tokenValue,
            $scheme->getTokensValueColumn(),
            $expirationInSeconds,
            $scheme->getTokensValueCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function readByRefresh(string $refreshValue, int $expirationInSeconds)
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readEnabledTokenByColumnWithExpirationCheck(
            $refreshValue,
            $scheme->getTokensRefreshColumn(),
            $expirationInSeconds,
            $scheme->getTokensRefreshCreatedAtColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function readByUser(int $userId, int $expirationInSeconds, int $limit = null): array
    {
        $scheme = $this->getDatabaseScheme();
        /** @var TokenInterface[] $tokens */
        $tokens = $this->readEnabledTokensByColumnWithExpirationCheck(
            $userId,
            $scheme->getTokensUserIdentityColumn(),
            $expirationInSeconds,
            $scheme->getTokensValueCreatedAtColumn(),
            ['*'],
            $limit
        );

        return $tokens;
    }

    /**
     * @inheritdoc
     */
    public function readScopeIdentifiers(int $identifier): array
    {
        $scheme = $this->getDatabaseScheme();
        return $this->readBelongsToManyRelationshipIdentifiers(
            $identifier,
            $scheme->getTokensScopesTable(),
            $scheme->getTokensScopesTokenIdentityColumn(),
            $scheme->getTokensScopesScopeIdentityColumn()
        );
    }

    /**
     * @inheritdoc
     */
    public function updateValues(TokenInterface $token)
    {
        $query = $this->getConnection()->createQueryBuilder();

        $scheme = $this->getDatabaseScheme();
        $now    = new DateTimeImmutable();
        $dbNow  = $this->createTypedParameter($query, $now);
        $query
            ->update($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $token->getIdentifier()))
            ->set($scheme->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
            ->set($scheme->getTokensValueCreatedAtColumn(), $dbNow);
        if ($token->getRefreshValue() !== null) {
            $query
                ->set($scheme->getTokensRefreshColumn(), $this->createTypedParameter($query, $token->getRefreshValue()))
                ->set($scheme->getTokensRefreshCreatedAtColumn(), $dbNow);
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
     */
    public function delete(int $identifier)
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     */
    public function disable(int $identifier)
    {
        $query = $this->getConnection()->createQueryBuilder();

        $scheme = $this->getDatabaseScheme();
        $query
            ->update($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $identifier))
            ->set($scheme->getTokensIsEnabledColumn(), $this->createTypedParameter($query, false));

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
    }

    /**
     * @inheritdoc
     */
    protected function getTableNameForWriting(): string
    {
        return $this->getDatabaseScheme()->getTokensTable();
    }

    /**
     * @inheritdoc
     */
    protected function getPrimaryKeyName(): string
    {
        return $this->getDatabaseScheme()->getTokensIdentityColumn();
    }

    /**
     * @param string $identifier
     * @param string $column
     * @param int    $expirationInSeconds
     * @param string $createdAtColumn
     * @param array  $columns
     *
     * @return TokenInterface|null
     */
    protected function readEnabledTokenByColumnWithExpirationCheck(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*']
    ) {
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
                ->andWhere($query->expr()->eq($this->getDatabaseScheme()->getTokensIsEnabledColumn(), '1')),
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

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
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Entities\Token;
use PDO;

/**
 * @package Limoncello\Passport
 */
abstract class TokenRepository extends BaseRepository implements TokenRepositoryInterface
{
    /**
     * @inheritdoc
     */
    public function createCode(TokenInterface $code): TokenInterface
    {
        /** @var Token $code */
        assert($code instanceof Token);

        $now    = new DateTimeImmutable();
        $scheme = $this->getDatabaseScheme();
        $values = [
            $scheme->getTokensClientIdentityColumn() => $code->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()   => $code->getUserIdentifier(),
            $scheme->getTokensCodeColumn()           => $code->getCode(),
            $scheme->getTokensCodeCreatedAtColumn()  => $now,
        ];

        $tokenIdentifier = null;
        if (is_array($scopeIdentifiers = $code->getScopeIdentifiers()) === true &&
            empty($scopeIdentifiers) === false
        ) {
            $this->inTransaction(function () use ($values, $scopeIdentifiers, &$tokenIdentifier) {
                $tokenIdentifier = $this->createResource($values);
                $this->bindScopeIdentifiers($tokenIdentifier, $scopeIdentifiers);
            });
        } else {
            $tokenIdentifier = $this->createResource($values);
        }

        $code->setIdentifier($tokenIdentifier)->setValueCreatedAt($now);

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
            ->update($this->getTableName())
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

        // TODO add error check if no actual changes made
    }

    /**
     * @inheritdoc
     */
    public function createToken(TokenInterface $token): TokenInterface
    {
        /** @var Token $token */
        assert($token instanceof Token);

        $now        = new DateTimeImmutable();
        $scheme     = $this->getDatabaseScheme();
        $hasRefresh = $token->getRefreshValue() !== null;
        $values     = $hasRefresh === false ? [
            $scheme->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $scheme->getTokensValueColumn()            => $token->getValue(),
            $scheme->getTokensTypeColumn()             => $token->getType(),
            $scheme->getTokensValueCreatedAtColumn()   => $now,
        ] : [
            $scheme->getTokensClientIdentityColumn()   => $token->getClientIdentifier(),
            $scheme->getTokensUserIdentityColumn()     => $token->getUserIdentifier(),
            $scheme->getTokensValueColumn()            => $token->getValue(),
            $scheme->getTokensTypeColumn()             => $token->getType(),
            $scheme->getTokensValueCreatedAtColumn()   => $now,
            $scheme->getTokensRefreshColumn()          => $token->getRefreshValue(),
            $scheme->getTokensRefreshCreatedAtColumn() => $now,
        ];

        $tokenIdentifier = null;
        if (is_array($scopeIdentifiers = $token->getScopeIdentifiers()) === true &&
            empty($scopeIdentifiers) === false
        ) {
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
        /** @var Token $token */
        assert($token instanceof Token);

        $query = $this->getConnection()->createQueryBuilder();

        $scheme = $this->getDatabaseScheme();
        $now    = new DateTimeImmutable();
        $dbNow  = $this->createTypedParameter($query, $now);
        $query
            ->update($this->getTableName())
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
            ->update($this->getTableName())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $identifier))
            ->set($scheme->getTokensIsEnabledColumn(), $this->createTypedParameter($query, false));

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);
    }

    /**
     * @inheritdoc
     */
    protected function getTableName(): string
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
        $earliestExpired = (new DateTimeImmutable())->sub(new DateInterval("PT{$expirationInSeconds}S"));

        $query = $this->getConnection()->createQueryBuilder();

        $isEnabledColumn = $this->getDatabaseScheme()->getTokensIsEnabledColumn();
        $statement = $query
            ->select($columns)
            ->from($this->getTableName())
            ->where($column . '=' . $this->createTypedParameter($query, $identifier))
            ->andWhere($createdAtColumn . '>' . $this->createTypedParameter($query, $earliestExpired))
            ->andWhere($query->expr()->eq($isEnabledColumn, '1'))
            ->execute();

        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }
}

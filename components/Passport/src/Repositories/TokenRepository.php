<?php declare(strict_types=1);

namespace Limoncello\Passport\Repositories;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Limoncello\Passport\Contracts\Entities\ScopeInterface;
use Limoncello\Passport\Contracts\Entities\TokenInterface;
use Limoncello\Passport\Contracts\Repositories\TokenRepositoryInterface;
use Limoncello\Passport\Exceptions\RepositoryException;
use PDO;
use function assert;
use function is_int;

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
     * @throws RepositoryException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createCode(TokenInterface $code): TokenInterface
    {
        try {
            $now    = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
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
                    $this->createResource($values);
                    $tokenIdentifier = $this->getLastInsertId();
                    $this->bindScopeIdentifiers($tokenIdentifier, $scopeIdentifiers);
                });
            } else {
                $this->createResource($values);
                $tokenIdentifier = $this->getLastInsertId();
            }

            $code->setIdentifier($tokenIdentifier)->setCodeCreatedAt($now);

            return $code;
        } catch (RepositoryException $exception) {
            $message = 'Token code creation failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function assignValuesToCode(TokenInterface $token, int $expirationInSeconds): void
    {
        try {
            $query = $this->getConnection()->createQueryBuilder();

            $now   = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
            $dbNow = $this->createTypedParameter($query, $now);

            $earliestExpired = $this->ignoreException(function () use ($now, $expirationInSeconds) : DateTimeImmutable {
                /** @var DateTimeImmutable $now */
                return $now->sub(new DateInterval("PT{$expirationInSeconds}S"));
            });

            $schema = $this->getDatabaseSchema();
            $query
                ->update($this->getTableNameForWriting())
                ->where($schema->getTokensCodeColumn() . '=' . $this->createTypedParameter($query, $token->getCode()))
                ->andWhere(
                    $schema->getTokensCodeCreatedAtColumn() . '>' .
                    $this->createTypedParameter($query, $earliestExpired)
                )
                ->set($schema->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
                ->set($schema->getTokensTypeColumn(), $this->createTypedParameter($query, $token->getType()))
                ->set($schema->getTokensValueCreatedAtColumn(), $dbNow);

            if ($token->getRefreshValue() !== null) {
                $query
                    ->set(
                        $schema->getTokensRefreshColumn(),
                        $this->createTypedParameter($query, $token->getRefreshValue())
                    )->set($schema->getTokensRefreshCreatedAtColumn(), $dbNow);
            }

            $numberOfUpdated = $query->execute();
            assert(is_int($numberOfUpdated) === true);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $exception) {
            $message = 'Assigning token values by code failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function createToken(TokenInterface $token): TokenInterface
    {
        try {
            $now        = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
            $schema     = $this->getDatabaseSchema();
            $hasRefresh = $token->getRefreshValue() !== null;
            $values     = $hasRefresh === false ? [
                $schema->getTokensClientIdentityColumn() => $token->getClientIdentifier(),
                $schema->getTokensUserIdentityColumn()   => $token->getUserIdentifier(),
                $schema->getTokensValueColumn()          => $token->getValue(),
                $schema->getTokensTypeColumn()           => $token->getType(),
                $schema->getTokensIsScopeModified()      => $token->isScopeModified(),
                $schema->getTokensIsEnabledColumn()      => $token->isEnabled(),
                $schema->getTokensValueCreatedAtColumn() => $now,
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
                    $this->createResource($values);
                    $tokenIdentifier = $this->getLastInsertId();
                    $this->bindScopeIdentifiers($tokenIdentifier, $scopeIdentifiers);
                });
            } else {
                $this->createResource($values);
                $tokenIdentifier = $this->getLastInsertId();
            }

            $token->setIdentifier($tokenIdentifier)->setValueCreatedAt($now);
            if ($hasRefresh === true) {
                $token->setRefreshCreatedAt($now);
            }

            return $token;
        } catch (RepositoryException $exception) {
            $message = 'Token creation failed';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function bindScopes(int $identifier, iterable $scopes): void
    {
        $getIdentifiers = function (iterable $scopes): iterable {
            foreach ($scopes as $scope) {
                /** @var ScopeInterface $scope */
                assert($scope instanceof ScopeInterface);
                yield $scope->getIdentifier();
            }
        };

        $this->bindScopeIdentifiers($identifier, $getIdentifiers($scopes));
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function bindScopeIdentifiers(int $identifier, iterable $scopeIdentifiers): void
    {
        try {
            $schema = $this->getDatabaseSchema();
            $this->createBelongsToManyRelationship(
                $identifier,
                $scopeIdentifiers,
                $schema->getTokensScopesTable(),
                $schema->getTokensScopesTokenIdentityColumn(),
                $schema->getTokensScopesScopeIdentityColumn()
            );
        } catch (RepositoryException $exception) {
            $message = 'Binding token scopes failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function unbindScopes(int $identifier): void
    {
        try {
            $schema = $this->getDatabaseSchema();
            $this->deleteBelongsToManyRelationshipIdentifiers(
                $schema->getTokensScopesTable(),
                $schema->getTokensScopesTokenIdentityColumn(),
                $identifier
            );
        } catch (RepositoryException $exception) {
            $message = 'Unbinding token scopes failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function read(int $identifier): ?TokenInterface
    {
        try {
            return $this->readResource($identifier);
        } catch (RepositoryException $exception) {
            $message = 'Token reading failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
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
     * @throws RepositoryException
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
     * @throws RepositoryException
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
     * @throws RepositoryException
     */
    public function readByUser(int $userId, int $expirationInSeconds, int $limit = null): array
    {
        $schema = $this->getDatabaseSchema();
        /** @var TokenInterface[] $tokens */
        $tokens = $this->readEnabledTokensByColumnWithExpirationCheck(
            (string)$userId,
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
     * @throws RepositoryException
     */
    public function readScopeIdentifiers(int $identifier): array
    {
        try {
            $schema = $this->getDatabaseSchema();
            return $this->readBelongsToManyRelationshipIdentifiers(
                $identifier,
                $schema->getTokensScopesTable(),
                $schema->getTokensScopesTokenIdentityColumn(),
                $schema->getTokensScopesScopeIdentityColumn()
            );
        } catch (RepositoryException $exception) {
            $message = 'Reading scopes for a token failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function updateValues(TokenInterface $token): void
    {
        try {
            $query = $this->getConnection()->createQueryBuilder();

            $schema = $this->getDatabaseSchema();
            $now    = $this->ignoreException(function (): DateTimeImmutable {
                return new DateTimeImmutable();
            });
            $dbNow  = $this->createTypedParameter($query, $now);
            $query
                ->update($this->getTableNameForWriting())
                ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $token->getIdentifier()))
                ->set($schema->getTokensValueColumn(), $this->createTypedParameter($query, $token->getValue()))
                ->set($schema->getTokensValueCreatedAtColumn(), $dbNow);
            if ($token->getRefreshValue() !== null) {
                $query
                    ->set(
                        $schema->getTokensRefreshColumn(),
                        $this->createTypedParameter($query, $token->getRefreshValue())
                    )->set($schema->getTokensRefreshCreatedAtColumn(), $dbNow);
            }

            $numberOfUpdated = $query->execute();
            assert(is_int($numberOfUpdated) === true);
            if ($numberOfUpdated > 0) {
                $token->setValueCreatedAt($now);
                if ($token->getRefreshValue() !== null) {
                    $token->setRefreshCreatedAt($now);
                }
            }
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $exception) {
            $message = 'Token update failed.';
            throw new RepositoryException($message, 0, $exception);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
     */
    public function delete(int $identifier): void
    {
        $this->deleteResource($identifier);
    }

    /**
     * @inheritdoc
     *
     * @throws RepositoryException
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
     * @throws RepositoryException
     */
    protected function readEnabledTokenByColumnWithExpirationCheck(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*']
    ): ?TokenInterface {
        try {
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
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $exception) {
            $message = 'Reading token failed.';
            throw new RepositoryException($message, 0, $exception);
        }
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
     * @throws RepositoryException
     */
    protected function readEnabledTokensByColumnWithExpirationCheck(
        string $identifier,
        string $column,
        int $expirationInSeconds,
        string $createdAtColumn,
        array $columns = ['*'],
        int $limit = null
    ): array {
        try {
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
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (DBALException $exception) {
            $message = 'Reading tokens failed.';
            throw new RepositoryException($message, 0, $exception);
        }
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
                // SQLite and MySQL work fine with just 1 but PostgreSQL wants it to be a string '1'
                ->andWhere($query->expr()->eq($this->getDatabaseSchema()->getTokensIsEnabledColumn(), "'1'")),
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
        $earliestExpired = $this->ignoreException(function () use ($expirationInSeconds) : DateTimeImmutable {
            return (new DateTimeImmutable())->sub(new DateInterval("PT{$expirationInSeconds}S"));
        });

        $query->andWhere($createdAtColumn . '>' . $this->createTypedParameter($query, $earliestExpired));

        return $query;
    }
}

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

use Closure;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use PDO;

/**
 * @package Limoncello\Passport
 */
abstract class BaseRepository
{
    /**
     * @return string
     */
    abstract protected function getTableNameForReading(): string;

    /**
     * @return string
     */
    abstract protected function getTableNameForWriting(): string;

    /**
     * @return string
     */
    abstract protected function getClassName(): string;

    /**
     * @return string
     */
    abstract protected function getPrimaryKeyName(): string;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DatabaseSchemeInterface
     */
    private $databaseScheme;

    /**
     * @param Closure $closure
     *
     * @return void
     */
    public function inTransaction(Closure $closure)
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $isOk = ($closure() === false ? null : true);
        } finally {
            isset($isOk) === true ? $connection->commit() : $connection->rollBack();
        }
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     *
     * @return BaseRepository
     */
    protected function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function indexResources(array $columns = ['*']): array
    {
        $query = $this->getConnection()->createQueryBuilder();

        $statement = $query
            ->select($columns)
            ->from($this->getTableNameForReading())
            ->execute();

        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $statement->fetchAll();

        return $result;
    }

    /**
     * @param array $values
     *
     * @return int
     */
    protected function createResource(array $values): int
    {
        $query = $this->getConnection()->createQueryBuilder();

        $query->insert($this->getTableNameForWriting());
        foreach ($values as $key => $value) {
            $query->setValue($key, $this->createTypedParameter($query, $value));
        }

        $numberOfAdded = $query->execute();
        assert(is_int($numberOfAdded) === true);

        $lastInsertId = $this->getConnection()->lastInsertId();

        return $lastInsertId;
    }

    /**
     * @param string|int $identifier
     * @param array      $columns
     *
     * @return mixed
     */
    protected function readResource($identifier, array $columns = ['*'])
    {
        return $this->readResourceByColumn($identifier, $this->getPrimaryKeyName(), $columns);
    }

    /**
     * @param string|int $identifier
     * @param string     $column
     * @param array      $columns
     *
     * @return mixed
     */
    protected function readResourceByColumn($identifier, string $column, array $columns = ['*'])
    {
        $query = $this->getConnection()->createQueryBuilder();

        $statement = $query
            ->select($columns)
            ->from($this->getTableNameForReading())
            ->where($column . '=' . $this->createTypedParameter($query, $identifier))
            ->execute();

        $statement->setFetchMode(PDO::FETCH_CLASS, $this->getClassName());
        $result = $statement->fetch();

        return $result === false ? null : $result;
    }

    /**
     * @param string|int $identifier
     * @param array      $values
     *
     * @return int
     */
    protected function updateResource($identifier, array $values): int
    {
        $query = $this->getConnection()->createQueryBuilder();

        $query
            ->update($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $identifier));
        foreach ($values as $key => $value) {
            $query->set($key, $this->createTypedParameter($query, $value));
        }

        $numberOfUpdated = $query->execute();
        assert(is_int($numberOfUpdated) === true);

        return $numberOfUpdated;
    }

    /**
     * @param string|int $identifier
     *
     * @return int
     */
    protected function deleteResource($identifier): int
    {
        $query = $this->getConnection()->createQueryBuilder();

        $query
            ->delete($this->getTableNameForWriting())
            ->where($this->getPrimaryKeyName() . '=' . $this->createTypedParameter($query, $identifier));

        $numberOfDeleted = $query->execute();
        assert(is_int($numberOfDeleted) === true);

        return $numberOfDeleted;
    }

    /**
     * @param string|int $primaryKey
     * @param array      $foreignKeys
     * @param string     $intTableName
     * @param string     $intPrimaryKeyName
     * @param string     $intForeignKeyName
     *
     * @return void
     */
    protected function createBelongsToManyRelationship(
        $primaryKey,
        array $foreignKeys,
        string $intTableName,
        string $intPrimaryKeyName,
        string $intForeignKeyName
    ) {
        $connection = $this->getConnection();
        $query      = $connection->createQueryBuilder();

        $query->insert($intTableName)->values([$intPrimaryKeyName => '?', $intForeignKeyName => '?']);
        $statement = $connection->prepare($query->getSQL());

        foreach ($foreignKeys as $value) {
            $statement->bindValue(1, $primaryKey);
            $statement->bindValue(2, $value);
            $statement->execute();
        }
    }

    /**
     * @param string|int $identifier
     * @param string     $intTableName
     * @param string     $intPrimaryKeyName
     * @param string     $intForeignKeyName
     *
     * @return string[]
     */
    protected function readBelongsToManyRelationshipIdentifiers(
        $identifier,
        string $intTableName,
        string $intPrimaryKeyName,
        string $intForeignKeyName
    ) {
        $connection = $this->getConnection();
        $query      = $connection->createQueryBuilder();

        $query
            ->select($intForeignKeyName)
            ->from($intTableName)
            ->where($intPrimaryKeyName . '=' . $this->createTypedParameter($query, $identifier));

        $statement = $query->execute();
        $statement->setFetchMode(PDO::FETCH_NUM);
        $result = array_column($statement->fetchAll(), 0);

        return $result;
    }

    /**
     * @param string|int $identifier
     * @param string     $hasManyTableName
     * @param string     $hasManyColumn
     * @param string     $hasManyFkName
     *
     * @return string[]
     */
    protected function readHasManyRelationshipColumn(
        $identifier,
        string $hasManyTableName,
        string $hasManyColumn,
        string $hasManyFkName
    ) {
        $connection = $this->getConnection();
        $query      = $connection->createQueryBuilder();

        $query
            ->select($hasManyColumn)
            ->from($hasManyTableName)
            ->where($hasManyFkName . '=' . $this->createTypedParameter($query, $identifier));

        $statement = $query->execute();
        $statement->setFetchMode(PDO::FETCH_NUM);
        $result = array_column($statement->fetchAll(), 0);

        return $result;
    }

    /**
     * @param string     $intTableName
     * @param string     $intPrimaryKeyName
     * @param string|int $identifier
     *
     * @return int
     */
    protected function deleteBelongsToManyRelationshipIdentifiers(
        string $intTableName,
        string $intPrimaryKeyName,
        $identifier
    ) {
        $connection = $this->getConnection();
        $query      = $connection->createQueryBuilder();

        $query
            ->delete($intTableName)
            ->where($intPrimaryKeyName . '=' . $this->createTypedParameter($query, $identifier));

        $numberOfDeleted = $query->execute();
        assert(is_int($numberOfDeleted) === true);

        return $numberOfDeleted;
    }

    /**
     * @param DateTimeInterface $dateTime
     *
     * @return string
     */
    protected function getDateTimeForDb(DateTimeInterface $dateTime): string
    {
        return Type::getType(Type::DATETIME)
            ->convertToDatabaseValue($dateTime, $this->getConnection()->getDatabasePlatform());
    }

    /**
     * @return DatabaseSchemeInterface
     */
    protected function getDatabaseScheme()
    {
        return $this->databaseScheme;
    }

    /**
     * @param DatabaseSchemeInterface $databaseScheme
     *
     * @return BaseRepository
     */
    protected function setDatabaseScheme(DatabaseSchemeInterface $databaseScheme): BaseRepository
    {
        $this->databaseScheme = $databaseScheme;

        return $this;
    }

    /**
     * @param QueryBuilder $query
     * @param mixed        $value
     *
     * @return string
     */
    protected function createTypedParameter(QueryBuilder $query, $value): string
    {
        if (is_bool($value) === true) {
            $type = PDO::PARAM_BOOL;
        } elseif (is_int($value) === true) {
            $type = PDO::PARAM_INT;
        } elseif ($value === null) {
            $type = PDO::PARAM_NULL;
        } elseif ($value instanceof DateTimeInterface) {
            $value = $this->getDateTimeForDb($value);
            $type = PDO::PARAM_STR;
        } else {
            $type = PDO::PARAM_STR;
        }

        return $query->createNamedParameter($value, $type);
    }
}

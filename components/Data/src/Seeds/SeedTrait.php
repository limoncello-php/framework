<?php declare (strict_types = 1);

namespace Limoncello\Data\Seeds;

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

use Closure;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use Exception;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\SeedInterface;
use PDO;
use Psr\Container\ContainerInterface;
use function array_key_exists;
use function assert;

/**
 * @package Limoncello\Data
 */
trait SeedTrait
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function init(ContainerInterface $container): SeedInterface
    {
        $this->container = $container;

        /** @var SeedInterface $self */
        $self = $this;

        return $self;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        assert($this->getContainer()->has(Connection::class) === true);

        return $this->getContainer()->get(Connection::class);
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    protected function getModelSchemas(): ModelSchemaInfoInterface
    {
        assert($this->getContainer()->has(ModelSchemaInfoInterface::class) === true);

        return $this->getContainer()->get(ModelSchemaInfoInterface::class);
    }

    /**
     * @return AbstractSchemaManager
     */
    protected function getSchemaManager(): AbstractSchemaManager
    {
        return $this->getConnection()->getSchemaManager();
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    protected function now(): string
    {
        $format = $this->getSchemaManager()->getDatabasePlatform()->getDateTimeFormatString();
        $now    = (new DateTimeImmutable())->format($format);

        return $now;
    }

    /**
     * @param string   $tableName
     * @param null|int $limit
     *
     * @return array
     */
    protected function readTableData(string $tableName, int $limit = null): array
    {
        assert($limit === null || $limit > 0);

        $builder = $this->getConnection()->createQueryBuilder();
        $builder
            ->select('*')
            ->from($tableName);

        $limit === null ?: $builder->setMaxResults($limit);

        $result = $builder->execute()->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * @param string   $modelClass
     * @param null|int $limit
     *
     * @return array
     */
    protected function readModelsData(string $modelClass, int $limit = null): array
    {
        return $this->readTableData($this->getModelSchemas()->getTable($modelClass), $limit);
    }

    /**
     * @param int     $records
     * @param string  $tableName
     * @param Closure $dataClosure
     * @param array   $columnTypes
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function seedTableData(int $records, $tableName, Closure $dataClosure, array $columnTypes = []): void
    {
        $attributeTypeGetter = $this->createAttributeTypeGetter($columnTypes);

        $connection = $this->getConnection();
        for ($i = 0; $i !== $records; $i++) {
            $this->insertRow($tableName, $connection, $dataClosure($this->getContainer()), $attributeTypeGetter);
        }
    }

    /**
     * @param int     $records
     * @param string  $modelClass
     * @param Closure $dataClosure
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function seedModelsData(int $records, string $modelClass, Closure $dataClosure): void
    {
        $attributeTypes = $this->getModelSchemas()->getAttributeTypes($modelClass);

        $this->seedTableData($records, $this->getModelSchemas()->getTable($modelClass), $dataClosure, $attributeTypes);
    }

    /**
     * @param string $tableName
     * @param array  $data
     * @param array  $columnTypes
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function seedRowData(string $tableName, array $data, array $columnTypes = []): void
    {
        $attributeTypeGetter = $this->createAttributeTypeGetter($columnTypes);

        $this->insertRow($tableName, $this->getConnection(), $data, $attributeTypeGetter);
    }

    /**
     * @param string $modelClass
     * @param array  $data
     *
     * @return void
     *
     * @throws DBALException
     */
    protected function seedModelData(string $modelClass, array $data): void
    {
        $attributeTypes = $this->getModelSchemas()->getAttributeTypes($modelClass);

        $this->seedRowData($this->getModelSchemas()->getTable($modelClass), $data, $attributeTypes);
    }

    /**
     * @return string
     */
    protected function getLastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * @param string     $tableName
     * @param Connection $connection
     * @param array      $data
     * @param Closure    $getColumnType
     *
     * @return void
     *
     * @throws DBALException
     */
    private function insertRow($tableName, Connection $connection, array $data, Closure $getColumnType): void
    {
        $types        = [];
        $quotedFields = [];
        foreach ($data as $column => $value) {
            $name                = $connection->quoteIdentifier($column);
            $quotedFields[$name] = $value;
            $types[$name]        = $getColumnType($column);
        }

        try {
            $result = $connection->insert($tableName, $quotedFields, $types);
            assert($result !== false, 'Insert failed');
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $e) {
            // ignore non-unique records
        }
    }

    /**
     * @param array $attributeTypes
     *
     * @return Closure
     */
    private function createAttributeTypeGetter(array $attributeTypes): Closure
    {
        return function (string $attributeType) use ($attributeTypes) : string {
            return array_key_exists($attributeType, $attributeTypes) === true ?
                $attributeTypes[$attributeType] : Type::STRING;
        };
    }
}

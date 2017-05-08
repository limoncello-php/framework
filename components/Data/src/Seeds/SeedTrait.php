<?php namespace Limoncello\Data\Seeds;

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
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\SeedInterface;
use PDO;
use Psr\Container\ContainerInterface;

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

        return $this;
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
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        assert($this->getContainer()->has(ModelSchemeInfoInterface::class) === true);

        return $this->getContainer()->get(ModelSchemeInfoInterface::class);
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
        return $this->readTableData($this->getModelSchemes()->getTable($modelClass), $limit);
    }

    /**
     * @param int     $records
     * @param string  $tableName
     * @param Closure $dataClosure
     *
     * @return void
     */
    protected function seedTableData(int $records, $tableName, Closure $dataClosure)
    {
        $connection = $this->getConnection();
        for ($i = 0; $i !== $records; $i++) {
            $this->insertRow($tableName, $connection, $dataClosure($this->getContainer()));
        }
    }

    /**
     * @param int     $records
     * @param string  $modelClass
     * @param Closure $dataClosure
     *
     * @return void
     */
    protected function seedModelsData(int $records, string $modelClass, Closure $dataClosure)
    {
        $this->seedTableData($records, $this->getModelSchemes()->getTable($modelClass), $dataClosure);
    }

    /**
     * @param string $tableName
     * @param array  $data
     *
     * @return string|null
     */
    protected function seedRowData(string $tableName, array $data)
    {
        return $this->insertRow($tableName, $this->getConnection(), $data);
    }

    /**
     * @param string $modelClass
     * @param array  $data
     *
     * @return string|null
     */
    protected function seedModelData(string $modelClass, array $data)
    {
        return $this->insertRow($this->getModelSchemes()->getTable($modelClass), $this->getConnection(), $data);
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
     *
     * @return string|null
     */
    private function insertRow($tableName, Connection $connection, array $data)
    {
        $quotedFields = [];
        foreach ($data as $column => $value) {
            $quotedFields["`$column`"] = $value;
        }

        $index = null;
        try {
            $result = $connection->insert($tableName, $quotedFields);
            assert($result !== false, 'Insert failed');
            $index  = $connection->lastInsertId();
        } catch (UniqueConstraintViolationException $e) {
            // ignore non-unique records
        }

        return $index;
    }
}

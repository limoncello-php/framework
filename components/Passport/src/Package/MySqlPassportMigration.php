<?php namespace Limoncello\Passport\Package;

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
use Limoncello\Contracts\Data\MigrationInterface;
use Limoncello\Passport\Adaptors\MySql\DatabaseSchemaMigrationTrait;
use Limoncello\Passport\Contracts\Entities\DatabaseSchemaInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Passport
 */
class MySqlPassportMigration implements MigrationInterface
{
    use DatabaseSchemaMigrationTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @inheritdoc
     */
    public function init(ContainerInterface $container): MigrationInterface
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return void
     */
    public function migrate(): void
    {
        $this->createDatabaseSchema($this->getConnection(), $this->getDatabaseSchema());
    }

    /**
     * @return void
     */
    public function rollback(): void
    {
        $this->removeDatabaseSchema($this->getConnection(), $this->getDatabaseSchema());
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        assert($this->container !== null);

        return $this->container;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->getContainer()->get(Connection::class);
    }

    /**
     * @return DatabaseSchemaInterface
     */
    protected function getDatabaseSchema(): DatabaseSchemaInterface
    {
        return $this->getContainer()->get(DatabaseSchemaInterface::class);
    }
}

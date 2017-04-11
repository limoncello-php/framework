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
use Limoncello\Passport\Contracts\Entities\DatabaseSchemeInterface;
use Limoncello\Passport\Traits\DatabaseSchemeMigrationTrait;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Passport
 */
class PassportMigration implements MigrationInterface
{
    use DatabaseSchemeMigrationTrait;

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
    public function migrate()
    {
        $this->createDatabaseScheme($this->getConnection(), $this->getDatabaseScheme());
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->removeDatabaseScheme($this->getConnection(), $this->getDatabaseScheme());
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
     * @return DatabaseSchemeInterface
     */
    protected function getDatabaseScheme(): DatabaseSchemeInterface
    {
        return $this->getContainer()->get(DatabaseSchemeInterface::class);
    }
}

<?php namespace Limoncello\Data\Migrations;

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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Generator;
use Limoncello\Contracts\Data\MigrationInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Data
 */
class MigrationRunner
{
    /** Migrations table name */
    const MIGRATIONS_TABLE = '_migrations';

    /** Migration column name */
    const MIGRATIONS_COLUMN_ID = 'id';

    /** Migration column name */
    const MIGRATIONS_COLUMN_CLASS = 'class';

    /** Migration column name */
    const MIGRATIONS_COLUMN_CREATED_AT = 'created_at';

    /** Seeds table name */
    const SEEDS_TABLE = '_seeds';

    /**
     * @var string[]
     */
    private $migrationsList;

    /**
     * @param string[] $migrationList
     */
    public function __construct(array $migrationList)
    {
        $this->migrationsList = $migrationList;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    public function migrate(ContainerInterface $container)
    {
        foreach ($this->getMigrations($container) as $class) {
            /** @var MigrationInterface $migration */
            $migration = new $class($container);
            $migration->migrate();
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    public function rollback(ContainerInterface $container)
    {
        foreach ($this->getRollbacks($container) as $class) {
            /** @var MigrationInterface $migration */
            $migration = new $class($container);
            $migration->rollback();
        }

        $manager = $this->getConnection($container)->getSchemaManager();
        if ($manager->tablesExist(static::MIGRATIONS_TABLE) === true) {
            $manager->dropTable(static::MIGRATIONS_TABLE);
        }
        if ($manager->tablesExist(static::SEEDS_TABLE) === true) {
            $manager->dropTable(static::SEEDS_TABLE);
        }
    }

    /**
     * @return string[]
     */
    protected function getMigrationsList(): array
    {
        return $this->migrationsList;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Generator
     */
    private function getMigrations(ContainerInterface $container): Generator
    {
        $connection = $this->getConnection($container);
        $manager    = $connection->getSchemaManager();

        if ($manager->tablesExist(static::MIGRATIONS_TABLE) === true) {
            $migrated = $this->readMigrated($connection);
        } else {
            $this->createMigrationsTable($manager);
            $migrated = [];
        }

        $notYetMigrated = array_diff($this->getMigrationsList(), $migrated);

        foreach ($notYetMigrated as $class) {
            yield $class;
            $this->saveMigration($connection, $class);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Generator
     */
    private function getRollbacks(ContainerInterface $container): Generator
    {
        $connection = $this->getConnection($container);
        $migrated   = $this->readMigrated($connection);

        foreach (array_reverse($migrated, true) as $index => $class) {
            yield $class;
            $this->removeMigration($connection, $index);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Connection
     */
    private function getConnection(ContainerInterface $container): Connection
    {
        return $container->get(Connection::class);
    }

    /**
     * @param AbstractSchemaManager $manager
     *
     * @return void
     */
    private function createMigrationsTable(AbstractSchemaManager $manager)
    {
        $table = new Table(static::MIGRATIONS_TABLE);

        $table
            ->addColumn(static::MIGRATIONS_COLUMN_ID, Type::INTEGER)
            ->setUnsigned(true)
            ->setAutoincrement(true);
        $table
            ->addColumn(static::MIGRATIONS_COLUMN_CLASS, Type::STRING)
            ->setLength(255);
        $table
            ->addColumn(static::MIGRATIONS_COLUMN_CREATED_AT, Type::DATETIME);

        $table->setPrimaryKey([static::MIGRATIONS_COLUMN_ID]);
        $table->addUniqueIndex([static::MIGRATIONS_COLUMN_CLASS]);

        $manager->createTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return array
     */
    private function readMigrated(Connection $connection): array
    {
        $builder  = $connection->createQueryBuilder();
        $migrated = [];

        if ($connection->getSchemaManager()->tablesExist(static::MIGRATIONS_TABLE) === true) {
            $migrations = $builder
                ->select(static::MIGRATIONS_COLUMN_ID, static::MIGRATIONS_COLUMN_CLASS)
                ->from(static::MIGRATIONS_TABLE)
                ->orderBy(static::MIGRATIONS_COLUMN_ID)
                ->execute()
                ->fetchAll();
            foreach ($migrations as $migration) {
                $index            = $migration[static::MIGRATIONS_COLUMN_ID];
                $class            = $migration[static::MIGRATIONS_COLUMN_CLASS];
                $migrated[$index] = $class;
            }
        }

        return $migrated;
    }

    /**
     * @param Connection $connection
     * @param string     $class
     *
     * @return void
     */
    private function saveMigration(Connection $connection, string $class)
    {
        $format = $connection->getSchemaManager()->getDatabasePlatform()->getDateTimeFormatString();
        $now    = (new DateTimeImmutable())->format($format);
        $connection->insert(static::MIGRATIONS_TABLE, [
            static::MIGRATIONS_COLUMN_CLASS      => $class,
            static::MIGRATIONS_COLUMN_CREATED_AT => $now,
        ]);
    }

    /**
     * @param Connection $connection
     * @param int        $index
     *
     * @return void
     */
    private function removeMigration(Connection $connection, int $index)
    {
        $connection->delete(static::MIGRATIONS_TABLE, [static::MIGRATIONS_COLUMN_ID => $index]);
    }
}

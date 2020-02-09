<?php declare(strict_types=1);

namespace Limoncello\Application\Data;

/**
 * Copyright 2015-2020 info@neomerx.com
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
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Error;
use Exception;
use Generator;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Data\MigrationInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function array_diff;
use function array_reverse;
use function assert;
use function is_string;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BaseMigrationRunner
{
    /** Migrations table name */
    const MIGRATIONS_TABLE = '_migrations';

    /** Migration column name */
    const MIGRATIONS_COLUMN_ID = 'id';

    /** Migration column name */
    const MIGRATIONS_COLUMN_CLASS = 'class';

    /** Migration column name */
    const MIGRATIONS_COLUMN_MIGRATED_AT = 'migrated_at';

    /** Seeds table name */
    const SEEDS_TABLE = '_seeds';

    /**
     * @var IoInterface
     */
    private $inOut;

    /**
     * @return string[]
     */
    abstract protected function getMigrationClasses(): array;

    /**
     * @param IoInterface $inOut
     */
    public function __construct(IoInterface $inOut)
    {
        $this->setIO($inOut);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    public function migrate(ContainerInterface $container): void
    {
        foreach ($this->getMigrations($container) as $class) {
            assert(is_string($class));
            $this->getIO()->writeInfo("Starting migration for `$class`..." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);
            if (($migration = $this->createMigration($class, $container)) !== null) {
                $migration->init($container)->migrate();
                $this->getIO()->writeInfo("Migration finished for `$class`." . PHP_EOL, IoInterface::VERBOSITY_NORMAL);
            }
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     *
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws DBALException
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    public function rollback(ContainerInterface $container): void
    {
        foreach ($this->getRollbacks($container) as $class) {
            assert(is_string($class));
            $this->getIO()->writeInfo("Starting rollback for `$class`..." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);
            if (($migration = $this->createMigration($class, $container)) !== null) {
                $migration->init($container)->rollback();
                $this->getIO()->writeInfo("Rollback finished for `$class`." . PHP_EOL, IoInterface::VERBOSITY_NORMAL);
            }
        }

        $manager = $this->getConnection($container)->getSchemaManager();
        if ($manager->tablesExist([static::MIGRATIONS_TABLE]) === true) {
            $manager->dropTable(static::MIGRATIONS_TABLE);
        }
        if ($manager->tablesExist([static::SEEDS_TABLE]) === true) {
            $manager->dropTable(static::SEEDS_TABLE);
        }
    }

    /**
     * @return IoInterface
     */
    protected function getIO(): IoInterface
    {
        return $this->inOut;
    }

    /**
     * @param IoInterface $inOut
     *
     * @return self
     */
    private function setIO(IoInterface $inOut): self
    {
        $this->inOut = $inOut;

        return $this;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Generator
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @throws DBALException
     */
    private function getMigrations(ContainerInterface $container): Generator
    {
        $connection = $this->getConnection($container);
        $manager    = $connection->getSchemaManager();

        if ($manager->tablesExist([static::MIGRATIONS_TABLE]) === true) {
            $migrated = $this->readMigrated($connection);
        } else {
            $this->createMigrationsTable($manager);
            $migrated = [];
        }

        $notYetMigrated = array_diff($this->getMigrationClasses(), $migrated);

        foreach ($notYetMigrated as $class) {
            yield $class;
            $this->saveMigration($connection, $class);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Generator
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws DBALException
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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getConnection(ContainerInterface $container): Connection
    {
        return $container->get(Connection::class);
    }

    /**
     * @param AbstractSchemaManager $manager
     *
     * @return void
     *
     * @throws DBALException
     */
    private function createMigrationsTable(AbstractSchemaManager $manager): void
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
            ->addColumn(static::MIGRATIONS_COLUMN_MIGRATED_AT, Type::DATETIME);

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

        if ($connection->getSchemaManager()->tablesExist([static::MIGRATIONS_TABLE]) === true) {
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
     * @param string $class
     *
     * @return void
     *
     * @throws DBALException
     * @throws Exception
     */
    private function saveMigration(Connection $connection, string $class): void
    {
        $format = $connection->getSchemaManager()->getDatabasePlatform()->getDateTimeFormatString();
        $now    = (new DateTimeImmutable())->format($format);
        $connection->insert(static::MIGRATIONS_TABLE, [
            static::MIGRATIONS_COLUMN_CLASS       => $class,
            static::MIGRATIONS_COLUMN_MIGRATED_AT => $now,
        ]);
    }

    /**
     * @param Connection $connection
     * @param int $index
     *
     * @return void
     *
     * @throws InvalidArgumentException
     *
     * @throws DBALException
     */
    private function removeMigration(Connection $connection, int $index): void
    {
        $connection->delete(static::MIGRATIONS_TABLE, [static::MIGRATIONS_COLUMN_ID => $index]);
    }

    /**
     * @param string             $class
     * @param ContainerInterface $container
     *
     * @return MigrationInterface|null
     */
    private function createMigration(string $class, ContainerInterface $container): ?MigrationInterface
    {
        $migration = null;

        try {
            /** @var MigrationInterface $migration */
            $migration = new $class($container);
        } catch (Error $exception) {
            $this->getIO()->writeWarning("Migration `$class` not found." . PHP_EOL);
        }

        return $migration;
    }
}

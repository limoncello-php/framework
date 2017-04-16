<?php namespace Limoncello\Application\Data;

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
use Limoncello\Contracts\Data\SeedInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
abstract class BaseSeedRunner
{
    /** Seed column name */
    const SEEDS_COLUMN_ID = 'id';

    /** Seed column name */
    const SEEDS_COLUMN_CLASS = 'class';

    /** Seed column name */
    const SEEDS_COLUMN_SEEDED_AT = 'seeded_at';

    /**
     * @var string
     */
    private $seedsTable;

    /**
     * @var null|callable
     */
    private $seedInit = null;

    /**
     * @return string[]
     */
    abstract protected function getSeedClasses(): array;

    /**
     * @param callable $seedInit
     * @param string   $seedsTable
     */
    public function __construct(callable $seedInit = null, string $seedsTable = BaseMigrationRunner::SEEDS_TABLE)
    {
        assert(empty($seedsTable) === false);

        $this->seedInit    = $seedInit;
        $this->seedsTable  = $seedsTable;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    public function run(ContainerInterface $container)
    {
        foreach ($this->getSeeds($container) as $seederClass) {
            $this->executeSeedInit($container);
            /** @var SeedInterface $seeder */
            $seeder = new $seederClass();
            $seeder->init($container)->run();
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    public function reset(ContainerInterface $container)
    {
        foreach ($this->getSeeds($container) as $seederClass) {
            /** @var SeedInterface $seeder */
            $seeder = new $seederClass();
            $seeder->init($container)->reset();
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Generator
     */
    protected function getSeeds(ContainerInterface $container): Generator
    {
        $connection = $this->getConnection($container);
        $manager    = $connection->getSchemaManager();

        if ($manager->tablesExist($this->getSeedsTable()) === true) {
            $seeded = $this->readSeeded($connection);
        } else {
            $this->createSeedsTable($manager);
            $seeded = [];
        }

        $notYetSeeded = array_diff($this->getSeedClasses(), $seeded);

        foreach ($notYetSeeded as $class) {
            yield $class;
            $this->saveSeed($connection, $class);
        }
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Connection
     */
    protected function getConnection(ContainerInterface $container): Connection
    {
        return $container->get(Connection::class);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return void
     */
    protected function executeSeedInit(ContainerInterface $container)
    {
        if (($closure = $this->seedInit) !== null) {
            call_user_func($closure, $container);
        }
    }

    /**
     * @param AbstractSchemaManager $manager
     *
     * @return void
     */
    private function createSeedsTable(AbstractSchemaManager $manager)
    {
        $table = new Table($this->getSeedsTable());

        $table
            ->addColumn(static::SEEDS_COLUMN_ID, Type::INTEGER)
            ->setUnsigned(true)
            ->setAutoincrement(true);
        $table
            ->addColumn(static::SEEDS_COLUMN_CLASS, Type::STRING)
            ->setLength(255);
        $table
            ->addColumn(static::SEEDS_COLUMN_SEEDED_AT, Type::DATETIME);

        $table->setPrimaryKey([static::SEEDS_COLUMN_ID]);
        $table->addUniqueIndex([static::SEEDS_COLUMN_CLASS]);

        $manager->createTable($table);
    }

    /**
     * @param Connection $connection
     *
     * @return array
     */
    private function readSeeded($connection)
    {
        $builder = $connection->createQueryBuilder();
        $seeded  = [];

        if ($connection->getSchemaManager()->tablesExist($this->getSeedsTable()) === true) {
            $seeds = $builder
                ->select(static::SEEDS_COLUMN_ID, static::SEEDS_COLUMN_CLASS)
                ->from($this->getSeedsTable())
                ->orderBy(static::SEEDS_COLUMN_ID)
                ->execute()
                ->fetchAll();
            foreach ($seeds as $seed) {
                $index          = $seed[static::SEEDS_COLUMN_ID];
                $class          = $seed[static::SEEDS_COLUMN_CLASS];
                $seeded[$index] = $class;
            }
        }

        return $seeded;
    }

    /**
     * @param Connection $connection
     * @param string     $class
     *
     * @return void
     */
    private function saveSeed(Connection $connection, $class)
    {
        $format = $connection->getSchemaManager()->getDatabasePlatform()->getDateTimeFormatString();
        $now    = (new DateTimeImmutable())->format($format);
        $connection->insert($this->getSeedsTable(), [
            static::SEEDS_COLUMN_CLASS     => $class,
            static::SEEDS_COLUMN_SEEDED_AT => $now,
        ]);
    }

    /**
     * @return string
     */
    private function getSeedsTable(): string
    {
        return $this->seedsTable;
    }
}

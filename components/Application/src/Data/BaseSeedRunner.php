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
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Exception;
use Generator;
use Limoncello\Contracts\Commands\IoInterface;
use Limoncello\Contracts\Data\SeedInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function array_diff;
use function assert;
use function call_user_func;

/**
 * @package Limoncello\Application
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var IoInterface
     */
    private $inOut;

    /**
     * @return string[]
     */
    abstract protected function getSeedClasses(): array;

    /**
     * @param IoInterface $inOut
     * @param callable    $seedInit
     * @param string      $seedsTable
     */
    public function __construct(
        IoInterface $inOut,
        callable $seedInit = null,
        string $seedsTable = BaseMigrationRunner::SEEDS_TABLE
    ) {
        assert(empty($seedsTable) === false);

        $this->seedInit    = $seedInit;
        $this->seedsTable  = $seedsTable;

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
     */
    public function run(ContainerInterface $container): void
    {
        foreach ($this->getSeeds($container) as $seederClass) {
            $this->getIO()->writeInfo("Starting seed for `$seederClass`..." . PHP_EOL, IoInterface::VERBOSITY_VERBOSE);
            $this->executeSeedInit($container, $seederClass);
            /** @var SeedInterface $seeder */
            $seeder = new $seederClass();
            $seeder->init($container)->run();
            $this->getIO()->writeInfo("Seed finished for `$seederClass`." . PHP_EOL, IoInterface::VERBOSITY_NORMAL);
        }
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
    protected function getSeeds(ContainerInterface $container): Generator
    {
        $connection = $this->getConnection($container);
        $manager    = $connection->getSchemaManager();

        if ($manager->tablesExist([$this->getSeedsTable()]) === true) {
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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getConnection(ContainerInterface $container): Connection
    {
        return $container->get(Connection::class);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $seedClass
     *
     * @return void
     */
    protected function executeSeedInit(ContainerInterface $container, string $seedClass): void
    {
        if ($this->seedInit !== null) {
            call_user_func($this->seedInit, $container, $seedClass);
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
     * @param AbstractSchemaManager $manager
     *
     * @return void
     *
     * @throws DBALException
     */
    private function createSeedsTable(AbstractSchemaManager $manager): void
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
    private function readSeeded(Connection $connection): array
    {
        $builder = $connection->createQueryBuilder();
        $seeded  = [];

        if ($connection->getSchemaManager()->tablesExist([$this->getSeedsTable()]) === true) {
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
     * @param string $class
     *
     * @return void
     *
     * @throws DBALException
     * @throws Exception
     */
    private function saveSeed(Connection $connection, string $class): void
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

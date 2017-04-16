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

use Limoncello\Contracts\FileSystem\FileSystemInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Application
 */
class FileMigrationRunner extends BaseMigrationRunner
{
    /**
     * @var string
     */
    private $migrationsPath;

    /**
     * @var string[]
     */
    private $migrationClasses;

    /**
     * @param string $migrationsPath
     */
    public function __construct(string $migrationsPath)
    {
        $this->migrationsPath = $migrationsPath;
    }

    /**
     * @inheritdoc
     */
    public function migrate(ContainerInterface $container)
    {
        // read & remember classes to migrate...
        assert($container->has(FileSystemInterface::class) === true);
        /** @var FileSystemInterface $fileSystem */
        $fileSystem = $container->get(FileSystemInterface::class);

        assert($fileSystem->exists($this->getMigrationsPath()) === true);

        /** @noinspection PhpIncludeInspection */
        $migrationClasses = require $this->getMigrationsPath();
        $this->setMigrationClasses($migrationClasses);

        // ... and run actual migration
        parent::migrate($container);
    }

    /**
     * @inheritdoc
     */
    protected function getMigrationClasses(): array
    {
        return $this->migrationClasses;
    }

    /**
     * @return string
     */
    protected function getMigrationsPath(): string
    {
        return $this->migrationsPath;
    }

    /**
     * @param string $migrationsPath
     *
     * @return self
     */
    protected function setMigrationsPath(string $migrationsPath): self
    {
        $this->migrationsPath = $migrationsPath;

        return $this;
    }

    /**
     * @param string[] $migrationClasses
     *
     * @return self
     */
    private function setMigrationClasses(array $migrationClasses): self
    {
        $this->migrationClasses = $migrationClasses;

        return $this;
    }
}

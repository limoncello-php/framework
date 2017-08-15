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
class FileSeedRunner extends BaseSeedRunner
{
    /**
     * @var string
     */
    private $seedsPath;

    /**
     * @var string[]
     */
    private $seedClasses;

    /**
     * @param string        $seedsPath
     * @param callable|null $seedInit
     * @param string        $seedsTable
     */
    public function __construct(
        string $seedsPath,
        callable $seedInit = null,
        string $seedsTable = BaseMigrationRunner::SEEDS_TABLE
    ) {
        $this->setSeedsPath($seedsPath);

        parent::__construct($seedInit, $seedsTable);
    }

    /**
     * @inheritdoc
     */
    public function run(ContainerInterface $container): void
    {
        // read & remember seed classes...
        assert($container->has(FileSystemInterface::class) === true);
        /** @var FileSystemInterface $fileSystem */
        $fileSystem = $container->get(FileSystemInterface::class);

        assert($fileSystem->exists($this->getSeedsPath()) === true);

        $seedClasses = $fileSystem->requireFile($this->getSeedsPath());
        $this->setSeedClasses($seedClasses);

        // ... and run actual seeding
        parent::run($container);
    }

    /**
     * @param string[] $seedClasses
     *
     * @return self
     */
    private function setSeedClasses(array $seedClasses): self
    {
        $this->seedClasses = $seedClasses;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getSeedClasses(): array
    {
        return $this->seedClasses;
    }

    /**
     * @return string
     */
    protected function getSeedsPath(): string
    {
        return $this->seedsPath;
    }

    /**
     * @param string $seedsPath
     *
     * @return self
     */
    protected function setSeedsPath(string $seedsPath): self
    {
        $this->seedsPath = $seedsPath;

        return $this;
    }
}

<?php declare(strict_types=1);

namespace Limoncello\Validation\Execution;

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

use Limoncello\Validation\Contracts\Execution\BlockPropertiesInterface;
use Limoncello\Validation\Contracts\Execution\BlockStateInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Validation
 */
class ContextStorage implements ContextStorageInterface, BlockStateInterface, BlockPropertiesInterface
{
    /**
     * @var array
     */
    private $states = [];

    /**
     * @var int
     */
    private $currentBlockId = 0;

    /**
     * @var
     */
    private $blocks;

    /**
     * @var null|ContainerInterface
     */
    private $container;

    /**
     * @param array                   $blocks
     * @param ContainerInterface|null $container
     */
    public function __construct(array $blocks, ContainerInterface $container = null)
    {
        $this->blocks    = $blocks;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getStates(): BlockStateInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProperties(): BlockPropertiesInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentBlockId(): int
    {
        return $this->currentBlockId;
    }

    /**
     * @inheritdoc
     */
    public function setCurrentBlockId(int $index): ContextStorageInterface
    {
        $this->currentBlockId = $index;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clear(): ContextStorageInterface
    {
        $this->states = [];
        $this->setCurrentBlockId(0);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getProperty(int $key, $default = null)
    {
        return $this->blocks[$this->getCurrentBlockId()][BlockSerializer::PROPERTIES][$key] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function getState(int $key, $default = null)
    {
        return $this->states[$this->getCurrentBlockId()][$key] ?? $default;
    }

    /**
     * @inheritdoc
     */
    public function setState(int $key, $value): BlockStateInterface
    {
        $this->states[$this->getCurrentBlockId()][$key] = $value;

        return $this;
    }
}

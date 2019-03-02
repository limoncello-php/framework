<?php declare(strict_types=1);

namespace Limoncello\Validation\Contracts\Execution;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;

/**
 * @package Limoncello\Validation
 */
interface BlockSerializerInterface
{
    // Serialization structure

    /**
     * Serialization structure index.
     */
    const SERIALIZATION_BLOCKS = 0;

    /**
     * Serialization structure index.
     */
    const SERIALIZATION_BLOCKS_WITH_START = self::SERIALIZATION_BLOCKS + 1;

    /**
     * Serialization structure index.
     */
    const SERIALIZATION_BLOCKS_WITH_END = self::SERIALIZATION_BLOCKS_WITH_START + 1;

    // Block types

    /**
     * Serialization property index.
     */
    const TYPE = 0;

    /**
     * Serialization property index.
     */
    const PROPERTIES = self::TYPE + 1;

    /**
     * Serialization property index.
     */
    const TYPE__PROCEDURE = 0;

    /**
     * Serialization property index.
     */
    const TYPE__IF_EXPRESSION = self::TYPE__PROCEDURE + 1;

    /**
     * Serialization property index.
     */
    const TYPE__AND_EXPRESSION = self::TYPE__IF_EXPRESSION + 1;

    /**
     * Serialization property index.
     */
    const TYPE__OR_EXPRESSION = self::TYPE__AND_EXPRESSION + 1;

    // Procedure keys

    /**
     * Serialization property index.
     */
    const PROCEDURE_EXECUTE_CALLABLE = self::PROPERTIES + 1;

    /**
     * Serialization property index.
     */
    const PROCEDURE_START_CALLABLE = self::PROCEDURE_EXECUTE_CALLABLE + 1;

    /**
     * Serialization property index.
     */
    const PROCEDURE_END_CALLABLE = self::PROCEDURE_START_CALLABLE + 1;

    /**
     * Serialization property index.
     */
    const PROCEDURE_LAST = self::PROCEDURE_END_CALLABLE;

    // IF Expression keys

    /**
     * Serialization property index.
     */
    const IF_EXPRESSION_CONDITION_CALLABLE = self::PROPERTIES + 1;

    /**
     * Serialization property index.
     */
    const IF_EXPRESSION_ON_TRUE_BLOCK = self::IF_EXPRESSION_CONDITION_CALLABLE + 1;

    /**
     * Serialization property index.
     */
    const IF_EXPRESSION_ON_FALSE_BLOCK = self::IF_EXPRESSION_ON_TRUE_BLOCK + 1;

    /**
     * Serialization property index.
     */
    const IF_EXPRESSION_LAST = self::IF_EXPRESSION_ON_FALSE_BLOCK;

    // AND Expression keys

    /**
     * Serialization property index.
     */
    const AND_EXPRESSION_PRIMARY = self::PROPERTIES + 1;

    /**
     * Serialization property index.
     */
    const AND_EXPRESSION_SECONDARY = self::AND_EXPRESSION_PRIMARY + 1;

    /**
     * Serialization property index.
     */
    const AND_EXPRESSION_LAST = self::AND_EXPRESSION_SECONDARY;

    // OR Expression keys

    /**
     * Serialization property index.
     */
    const OR_EXPRESSION_PRIMARY = self::PROPERTIES + 1;

    /**
     * Serialization property index.
     */
    const OR_EXPRESSION_SECONDARY = self::OR_EXPRESSION_PRIMARY + 1;

    /**
     * Serialization property index.
     */
    const OR_EXPRESSION_LAST = self::OR_EXPRESSION_SECONDARY;

    /**
     * @param ExecutionBlockInterface $block
     *
     * @return self
     */
    public function serialize(ExecutionBlockInterface $block): self;

    /**
     * @param ExecutionBlockInterface $block
     *
     * @return int
     */
    public function addBlock(ExecutionBlockInterface $block): int;

    /**
     * @return array
     */
    public function get(): array;

    /**
     * @return array
     */
    public function getSerializedBlocks(): array;

    /**
     * @return int[]
     */
    public function getBlocksWithStart(): array;

    /**
     * @return int[]
     */
    public function getBlocksWithEnd(): array;

    /**
     * @return self
     *
     */
    public function clearBlocks(): self;

    /**
     * @return self
     */
    public function clearBlocksWithStart(): self;

    /**
     * @return self
     */
    public function clearBlocksWithEnd(): self;

    /**
     * @param array $serialized
     *
     * @return array
     */
    public static function unserializeBlocks(array $serialized): array;

    /**
     * @param array $serialized
     *
     * @return array
     */
    public static function unserializeBlocksWithStart(array $serialized): array;

    /**
     * @param array $serialized
     *
     * @return array
     */
    public static function unserializeBlocksWithEnd(array $serialized): array;
}

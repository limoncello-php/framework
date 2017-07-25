<?php namespace Limoncello\Validation\Execution;

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

use Limoncello\Validation\Contracts\Blocks\AndExpressionInterface;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Blocks\IfExpressionInterface;
use Limoncello\Validation\Contracts\Blocks\OrExpressionInterface;
use Limoncello\Validation\Contracts\Blocks\ProcedureBlockInterface;
use Limoncello\Validation\Contracts\Execution\BlockSerializerInterface;
use Limoncello\Validation\Exceptions\UnknownExecutionBlockType;

/**
 * @package Limoncello\Validation
 */
final class BlockSerializer implements BlockSerializerInterface
{
    /**
     * Index of the first block.
     */
    const FIRST_BLOCK_INDEX = 0;

    /**
     * @var int
     */
    private $currentBlockIndex = 0;

    /**
     * @var array
     */
    private $serializedBlocks = [];

    /**
     * @var int[]
     */
    private $blocksWithStart = [];

    /**
     * @var int[]
     */
    private $blocksWithEnd = [];

    /**
     * @inheritdoc
     */
    public function serialize(ExecutionBlockInterface $block): BlockSerializerInterface
    {
        $this->reset()->addBlock($block);

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function addBlock(ExecutionBlockInterface $block): int
    {
        if ($block instanceof ProcedureBlockInterface) {
            $index = $this->serializeProcedure($block);
        } elseif ($block instanceof IfExpressionInterface) {
            $index = $this->serializeIfExpression($block);
        } elseif ($block instanceof AndExpressionInterface) {
            $index = $this->serializeAndExpression($block);
        } elseif ($block instanceof OrExpressionInterface) {
            $index = $this->serializeOrExpression($block);
        } else {
            // unknown execution block type
            throw new UnknownExecutionBlockType();
        }

        return $index;
    }

    /**
     * @return array
     */
    public function getSerializedBlocks(): array
    {
        return $this->serializedBlocks;
    }

    /**
     * @inheritdoc
     */
    public function getBlocksWithStart(): array
    {
        return $this->blocksWithStart;
    }

    /**
     * @inheritdoc
     */
    public function getBlocksWithEnd(): array
    {
        return $this->blocksWithEnd;
    }

    /**
     * @inheritdoc
     */
    public function clearBlocks(): BlockSerializerInterface
    {
        $this->currentBlockIndex = 0;
        $this->serializedBlocks  = [];


        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearBlocksWithStart(): BlockSerializerInterface
    {
        $this->blocksWithStart = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function clearBlocksWithEnd(): BlockSerializerInterface
    {
        $this->blocksWithEnd = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            static::SERIALIZATION_BLOCKS            => $this->getSerializedBlocks(),
            static::SERIALIZATION_BLOCKS_WITH_START => $this->getBlocksWithStart(),
            static::SERIALIZATION_BLOCKS_WITH_END   => $this->getBlocksWithEnd(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function unserializeBlocks(array $serialized): array
    {
        assert(count($serialized) === 3); // blocks, starts, ends

        return static::readProperty(static::SERIALIZATION_BLOCKS, $serialized);
    }

    /**
     * @inheritdoc
     */
    public static function unserializeBlocksWithStart(array $serialized): array
    {
        assert(count($serialized) === 3); // blocks, starts, ends

        return static::readProperty(static::SERIALIZATION_BLOCKS_WITH_START, $serialized);
    }

    /**
     * @inheritdoc
     */
    public static function unserializeBlocksWithEnd(array $serialized): array
    {
        assert(count($serialized) === 3); // blocks, starts, ends

        return static::readProperty(static::SERIALIZATION_BLOCKS_WITH_END, $serialized);
    }

    /**
     * @param int   $key
     * @param array $properties
     *
     * @return mixed
     */
    private static function readProperty(int $key, array $properties)
    {
        assert(array_key_exists($key, $properties));

        return $properties[$key];
    }

    /**
     * @return BlockSerializerInterface
     */
    private function reset(): BlockSerializerInterface
    {
        return $this->clearBlocks()->clearBlocksWithStart()->clearBlocksWithEnd();
    }

    /**
     * @param ProcedureBlockInterface $procedure
     *
     * @return int
     */
    private function serializeProcedure(ProcedureBlockInterface $procedure): int
    {
        $index = $this->allocateIndex();

        // TODO add signature checks for callable

        $serialized = [
            static::TYPE                       => static::TYPE__PROCEDURE,
            static::PROCEDURE_EXECUTE_CALLABLE => $procedure->getExecuteCallable(),
        ];
        if ($procedure->getStartCallable() !== null) {
            $serialized[static::PROCEDURE_START_CALLABLE] = $procedure->getStartCallable();
            $this->blocksWithStart[]                      = $index;
        }
        if ($procedure->getEndCallable() !== null) {
            $serialized[static::PROCEDURE_END_CALLABLE] = $procedure->getEndCallable();
            $this->blocksWithEnd[]                      = $index;
        }
        if (empty($procedure->getProperties()) === false) {
            $serialized[static::PROPERTIES] = $procedure->getProperties();
        }

        $this->serializedBlocks[$index] = $serialized;

        return $index;
    }

    /**
     * @param IfExpressionInterface $ifExpression
     *
     * @return int
     */
    private function serializeIfExpression(IfExpressionInterface $ifExpression): int
    {
        $index = $this->allocateIndex();

        // TODO add signature checks for callable

        $serialized = [
            static::TYPE                             => static::TYPE__IF_EXPRESSION,
            static::IF_EXPRESSION_CONDITION_CALLABLE => $ifExpression->getConditionCallable(),
        ];

        assert($ifExpression->getOnTrue() !== null || $ifExpression->getOnFalse() !== null);

        if ($ifExpression->getOnTrue() !== null) {
            $serialized[static::IF_EXPRESSION_ON_TRUE_BLOCK] = $this->addBlock($ifExpression->getOnTrue());
        }
        if ($ifExpression->getOnFalse() !== null) {
            $serialized[static::IF_EXPRESSION_ON_FALSE_BLOCK] = $this->addBlock($ifExpression->getOnFalse());
        }
        if (empty($ifExpression->getProperties()) === false) {
            $serialized[static::PROPERTIES] = $ifExpression->getProperties();
        }

        $this->serializedBlocks[$index] = $serialized;

        return $index;
    }

    /**
     * @param AndExpressionInterface $andExpression
     *
     * @return int
     */
    private function serializeAndExpression(AndExpressionInterface $andExpression): int
    {
        $index = $this->allocateIndex();

        $serialized = [
            static::TYPE                     => static::TYPE__AND_EXPRESSION,
            static::AND_EXPRESSION_PRIMARY   => $this->addBlock($andExpression->getPrimary()),
            static::AND_EXPRESSION_SECONDARY => $this->addBlock($andExpression->getSecondary()),
        ];
        if (empty($andExpression->getProperties()) === false) {
            $serialized[static::PROPERTIES] = $andExpression->getProperties();
        }

        $this->serializedBlocks[$index] = $serialized;

        return $index;
    }

    /**
     * @param OrExpressionInterface $orExpression
     *
     * @return int
     */
    private function serializeOrExpression(OrExpressionInterface $orExpression): int
    {
        $index = $this->allocateIndex();

        $serialized = [
            static::TYPE                    => static::TYPE__OR_EXPRESSION,
            static::OR_EXPRESSION_PRIMARY   => $this->addBlock($orExpression->getPrimary()),
            static::OR_EXPRESSION_SECONDARY => $this->addBlock($orExpression->getSecondary()),
        ];
        if (empty($orExpression->getProperties()) === false) {
            $serialized[static::PROPERTIES] = $orExpression->getProperties();
        }

        $this->serializedBlocks[$index] = $serialized;

        return $index;
    }

    /**
     * @return int
     */
    private function allocateIndex(): int
    {
        $index = $this->currentBlockIndex++;

        return $index;
    }
}

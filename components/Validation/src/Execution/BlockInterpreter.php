<?php declare(strict_types=1);

namespace Limoncello\Validation\Execution;

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

use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Rules\BaseRule;
use function array_key_exists;
use function assert;
use function call_user_func;
use function is_array;
use function is_bool;
use function is_callable;
use function is_int;
use function is_iterable;

/**
 * @package Limoncello\Validation
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
final class BlockInterpreter
{
    /**
     * @param mixed                      $input
     * @param array                      $serializedBlocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     * @param ErrorAggregatorInterface   $errors
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function execute(
        $input,
        array $serializedBlocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures,
        ErrorAggregatorInterface $errors
    ): bool {
        $blockIndex = BlockSerializer::FIRST_BLOCK_INDEX;

        $blocks   = static::getBlocks($serializedBlocks);
        $startsOk = static::executeStarts(static::getBlocksWithStart($serializedBlocks), $blocks, $context, $errors);
        $blockOk  = static::executeBlock($input, $blockIndex, $blocks, $context, $captures, $errors);
        $endsOk   = static::executeEnds(static::getBlocksWithEnd($serializedBlocks), $blocks, $context, $errors);

        return $startsOk && $blockOk && $endsOk;
    }

    /**
     * @param iterable|int[]           $indexes
     * @param array                    $blocks
     * @param ContextStorageInterface  $context
     * @param ErrorAggregatorInterface $errors
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function executeStarts(
        iterable $indexes,
        array $blocks,
        ContextStorageInterface $context,
        ErrorAggregatorInterface $errors
    ): bool {
        $allOk = true;

        foreach ($indexes as $index) {
            $context->setCurrentBlockId($index);
            $block      = $blocks[$index];
            $errorsInfo = static::executeProcedureStart($block, $context);
            if (empty($errorsInfo) === false) {
                static::addBlockErrors($errorsInfo, $context, $errors);
                $allOk = false;
            }
        }

        return $allOk;
    }

    /**
     * @param iterable|int[]           $indexes
     * @param array                    $blocks
     * @param ContextStorageInterface  $context
     * @param ErrorAggregatorInterface $errors
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function executeEnds(
        iterable $indexes,
        array $blocks,
        ContextStorageInterface $context,
        ErrorAggregatorInterface $errors
    ): bool {
        $allOk = true;

        foreach ($indexes as $index) {
            $context->setCurrentBlockId($index);
            $block      = $blocks[$index];
            $errorsInfo = static::executeProcedureEnd($block, $context);
            if (empty($errorsInfo) === false) {
                static::addBlockErrors($errorsInfo, $context, $errors);
                $allOk = false;
            }
        }

        return $allOk;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     * @param ErrorAggregatorInterface   $errors
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function executeBlock(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures,
        ErrorAggregatorInterface $errors
    ): bool {
        $result = static::executeBlockImpl(
            $input,
            $blockIndex,
            $blocks,
            $context,
            $captures
        );
        if (BlockReplies::isResultSuccessful($result) === false) {
            $errorsInfo = BlockReplies::extractResultErrorsInfo($result);
            static::addBlockErrors($errorsInfo, $context, $errors);

            return false;
        }

        return true;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeBlockImpl(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures
    ): array {
        assert(array_key_exists($blockIndex, $blocks));

        $blockType = static::getBlockType($blocks[$blockIndex]);
        $context->setCurrentBlockId($blockIndex);
        switch ($blockType) {
            case BlockSerializer::TYPE__PROCEDURE:
                $result = static::executeProcedureBlock($input, $blockIndex, $blocks, $context, $captures);
                break;
            case BlockSerializer::TYPE__IF_EXPRESSION:
                $result = static::executeIfBlock($input, $blockIndex, $blocks, $context, $captures);
                break;
            case BlockSerializer::TYPE__AND_EXPRESSION:
                $result = static::executeAndBlock($input, $blockIndex, $blocks, $context, $captures);
                break;
            case BlockSerializer::TYPE__OR_EXPRESSION:
            default:
                assert($blockType === BlockSerializer::TYPE__OR_EXPRESSION);
                $result = static::executeOrBlock($input, $blockIndex, $blocks, $context, $captures);
                break;
        }

        return $result;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeProcedureBlock(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures
    ): array {
        $block = $blocks[$blockIndex];
        assert(static::getBlockType($block) === BlockSerializer::TYPE__PROCEDURE);

        $procedure = $block[BlockSerializer::PROCEDURE_EXECUTE_CALLABLE];
        assert(is_callable($procedure));
        $result = call_user_func($procedure, $input, $context);

        static::captureSuccessfulBlockResultIfEnabled($result, $block, $captures);

        return $result;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeIfBlock(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures
    ): array {
        $block = $blocks[$blockIndex];
        assert(static::getBlockType($block) === BlockSerializer::TYPE__IF_EXPRESSION);

        $conditionCallable = $block[BlockSerializer::IF_EXPRESSION_CONDITION_CALLABLE];
        assert(is_callable($conditionCallable));
        $conditionResult = call_user_func($conditionCallable, $input, $context);
        assert(is_bool($conditionResult));

        $index = $block[$conditionResult === true ?
            BlockSerializer::IF_EXPRESSION_ON_TRUE_BLOCK : BlockSerializer::IF_EXPRESSION_ON_FALSE_BLOCK];

        $result = static::executeBlockImpl($input, $index, $blocks, $context, $captures);

        static::captureSuccessfulBlockResultIfEnabled($result, $block, $captures);

        return $result;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeAndBlock(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures
    ): array {
        $block = $blocks[$blockIndex];
        assert(static::getBlockType($block) === BlockSerializer::TYPE__AND_EXPRESSION);

        $primaryIndex = $block[BlockSerializer::AND_EXPRESSION_PRIMARY];
        $result       = static::executeBlockImpl($input, $primaryIndex, $blocks, $context, $captures);
        if (BlockReplies::isResultSuccessful($result) === true) {
            $nextInput      = BlockReplies::extractResultOutput($result);
            $secondaryIndex = $block[BlockSerializer::AND_EXPRESSION_SECONDARY];
            $result         = static::executeBlockImpl($nextInput, $secondaryIndex, $blocks, $context, $captures);
        }

        static::captureSuccessfulBlockResultIfEnabled($result, $block, $captures);

        return $result;
    }

    /**
     * @param mixed                      $input
     * @param int                        $blockIndex
     * @param array                      $blocks
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private static function executeOrBlock(
        $input,
        int $blockIndex,
        array $blocks,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures
    ): array {
        $block = $blocks[$blockIndex];
        assert(static::getBlockType($block) === BlockSerializer::TYPE__OR_EXPRESSION);

        $primaryIndex      = $block[BlockSerializer::OR_EXPRESSION_PRIMARY];
        $resultFromPrimary = static::executeBlockImpl($input, $primaryIndex, $blocks, $context, $captures);
        if (BlockReplies::isResultSuccessful($resultFromPrimary) === true) {
            $result = $resultFromPrimary;
        } else {
            $secondaryIndex = $block[BlockSerializer::OR_EXPRESSION_SECONDARY];
            $result         = static::executeBlockImpl($input, $secondaryIndex, $blocks, $context, $captures);
        }

        static::captureSuccessfulBlockResultIfEnabled($result, $block, $captures);

        return $result;
    }

    /**
     * @param array $serializedBlocks
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function getBlocks(array $serializedBlocks): array
    {
        $blocks = BlockSerializer::unserializeBlocks($serializedBlocks);
        assert(static::debugCheckLooksLikeBlocksArray($blocks));

        return $blocks;
    }

    /**
     * @param array $serializedBlocks
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function getBlocksWithStart(array $serializedBlocks): array
    {
        $blocksWithStart = BlockSerializer::unserializeBlocksWithStart($serializedBlocks);

        // check result contain only block indexes and the blocks are procedures
        assert(
            is_array($blocks = static::getBlocks($serializedBlocks)) &&
            static::debugCheckBlocksExist($blocksWithStart, $blocks, BlockSerializer::TYPE__PROCEDURE)
        );

        return $blocksWithStart;
    }

    /**
     * @param array $serializedBlocks
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function getBlocksWithEnd(array $serializedBlocks): array
    {
        $blocksWithEnd = BlockSerializer::unserializeBlocksWithEnd($serializedBlocks);

        // check result contain only block indexes and the blocks are procedures
        assert(
            is_array($blocks = static::getBlocks($serializedBlocks)) &&
            static::debugCheckBlocksExist($blocksWithEnd, $blocks, BlockSerializer::TYPE__PROCEDURE)
        );

        return $blocksWithEnd;
    }

    /**
     * @param array $block
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function getBlockType(array $block): int
    {
        assert(static::debugHasKnownBlockType($block));

        $type = $block[BlockSerializer::TYPE];

        return $type;
    }

    /**
     * @param array                      $result
     * @param array                      $block
     * @param CaptureAggregatorInterface $captures
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function captureSuccessfulBlockResultIfEnabled(
        array $result,
        array $block,
        CaptureAggregatorInterface $captures
    ): void {
        if (BlockReplies::isResultSuccessful($result) === true) {
            $isCaptureEnabled = $block[BlockSerializer::PROPERTIES][BaseRule::PROPERTY_IS_CAPTURE_ENABLED] ?? false;
            if ($isCaptureEnabled === true) {
                $name  = $block[BlockSerializer::PROPERTIES][BaseRule::PROPERTY_NAME];
                $value = BlockReplies::extractResultOutput($result);
                $captures->remember($name, $value);
            }
        }
    }

    /**
     * @param array            $procedureBlock
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeProcedureStart(array $procedureBlock, ContextInterface $context): array
    {
        assert(static::getBlockType($procedureBlock) === BlockSerializer::TYPE__PROCEDURE);
        $callable = $procedureBlock[BlockSerializer::PROCEDURE_START_CALLABLE];
        assert(is_callable($callable) === true);
        $errors = call_user_func($callable, $context);
        assert(is_array($errors));

        return $errors;
    }

    /**
     * @param array            $procedureBlock
     * @param ContextInterface $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function executeProcedureEnd(array $procedureBlock, ContextInterface $context): iterable
    {
        assert(static::getBlockType($procedureBlock) === BlockSerializer::TYPE__PROCEDURE);
        $callable = $procedureBlock[BlockSerializer::PROCEDURE_END_CALLABLE];
        assert(is_callable($callable) === true);
        $errors = call_user_func($callable, $context);
        assert(is_iterable($errors));

        return $errors;
    }

    /**
     * @param iterable                 $errorsInfo
     * @param ContextStorageInterface  $context
     * @param ErrorAggregatorInterface $errors
     *
     * @return void
     */
    private static function addBlockErrors(
        iterable $errorsInfo,
        ContextStorageInterface $context,
        ErrorAggregatorInterface $errors
    ): void {
        foreach ($errorsInfo as $errorInfo) {
            $index           = $errorInfo[BlockReplies::ERROR_INFO_BLOCK_INDEX];
            $value           = $errorInfo[BlockReplies::ERROR_INFO_VALUE];
            $errorCode       = $errorInfo[BlockReplies::ERROR_INFO_CODE];
            $messageTemplate = $errorInfo[BlockReplies::ERROR_INFO_MESSAGE_TEMPLATE];
            $messageParams   = $errorInfo[BlockReplies::ERROR_INFO_MESSAGE_PARAMETERS];

            $name = $context->setCurrentBlockId($index)->getProperties()->getProperty(BaseRule::PROPERTY_NAME);

            $errors->add(new Error($name, $value, $errorCode, $messageTemplate, $messageParams));
        }
    }

    /**
     * @param iterable $blocks
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function debugCheckLooksLikeBlocksArray(iterable $blocks): bool
    {
        $result = true;

        foreach ($blocks as $index => $block) {
            $result = $result &&
                is_int($index) === true &&
                is_array($block) === true &&
                static::debugHasKnownBlockType($block) === true;
        }

        return $result;
    }

    /**
     * @param iterable|int[] $blockIndexes
     * @param array          $blockList
     * @param int            $blockType
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function debugCheckBlocksExist(iterable $blockIndexes, array $blockList, int $blockType): bool
    {
        $result = true;

        foreach ($blockIndexes as $index) {
            $result = $result &&
                array_key_exists($index, $blockList) === true &&
                static::getBlockType($blockList[$index]) === $blockType;
        }

        return $result;
    }

    /**
     * @param array $block
     *
     * @return bool
     */
    private static function debugHasKnownBlockType(array $block): bool
    {
        $result = false;

        if (array_key_exists(BlockSerializer::TYPE, $block) === true) {
            $type = $block[BlockSerializer::TYPE];

            $result =
                $type === BlockSerializer::TYPE__PROCEDURE ||
                $type === BlockSerializer::TYPE__AND_EXPRESSION ||
                $type === BlockSerializer::TYPE__OR_EXPRESSION ||
                $type === BlockSerializer::TYPE__IF_EXPRESSION;
        }

        return $result;
    }
}

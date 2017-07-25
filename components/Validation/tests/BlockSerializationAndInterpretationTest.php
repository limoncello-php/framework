<?php namespace Limoncello\Tests\Validation;

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

use Limoncello\Validation\Blocks\AndBlock;
use Limoncello\Validation\Blocks\IfBlock;
use Limoncello\Validation\Blocks\OrBlock;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Execution\BlockReplies;
use Limoncello\Validation\Execution\BlockSerializer;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Rules\BaseRule;
use Limoncello\Validation\Rules\Generic\Fail;
use Limoncello\Validation\Rules\Generic\Success;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Validation
 */
class BlockSerializationAndInterpretationTest extends TestCase
{
    /**
     * Procedure callable.
     */
    const PROCEDURE_EXEC_SUCCESS = [self::class, 'procedureExecuteSuccess'];

    /**
     * Procedure callable.
     */
    const PROCEDURE_EXEC_ERROR = [self::class, 'procedureExecuteError'];

    /**
     * Procedure callable.
     */
    const PROCEDURE_START_SUCCESS = [self::class, 'procedureStartSuccess'];

    /**
     * Procedure callable.
     */
    const PROCEDURE_START_ERROR = [self::class, 'procedureStartError'];

    /**
     * Procedure callable.
     */
    const PROCEDURE_END_SUCCESS = [self::class, 'procedureEndSuccess'];

    /**
     * Procedure callable.
     */
    const PROCEDURE_END_ERROR = [self::class, 'procedureEndError'];

    /**
     * Test procedure block serialization.
     */
    public function testSerializeProcedureBlock(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_SUCCESS,
            ['any' => 'properties'],
            static::PROCEDURE_START_SUCCESS,
            static::PROCEDURE_END_SUCCESS
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $this->assertEquals([
            BlockSerializer::SERIALIZATION_BLOCKS            => [
                0 => [
                    BlockSerializer::TYPE                       => BlockSerializer::TYPE__PROCEDURE,
                    BlockSerializer::PROPERTIES                 => ['any' => 'properties'],
                    BlockSerializer::PROCEDURE_EXECUTE_CALLABLE => static::PROCEDURE_EXEC_SUCCESS,
                    BlockSerializer::PROCEDURE_START_CALLABLE   => static::PROCEDURE_START_SUCCESS,
                    BlockSerializer::PROCEDURE_END_CALLABLE     => static::PROCEDURE_END_SUCCESS,
                ],
            ],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_START => [0],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_END   => [0],
        ], $serialized);
    }

    /**
     * Test AND block serialization.
     */
    public function testSerializeAndBlock(): void
    {
        $block = new AndBlock(
            (new Success())->toBlock(),
            (new Fail())->toBlock(),
            ['any' => 'properties']
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $this->assertEquals([
            BlockSerializer::SERIALIZATION_BLOCKS            => [
                0 => [
                    BlockSerializer::TYPE                     => BlockSerializer::TYPE__AND_EXPRESSION,
                    BlockSerializer::PROPERTIES               => ['any' => 'properties'],
                    BlockSerializer::AND_EXPRESSION_PRIMARY   => 1,
                    BlockSerializer::AND_EXPRESSION_SECONDARY => 2,
                ],
                1 => $this->getSampleSerializationForSuccess(),
                2 => $this->getSampleSerializationForFail(),
            ],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_START => [],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_END   => [],
        ], $serialized);
    }

    /**
     * Test OR block serialization.
     */
    public function testSerializeOrBlock(): void
    {
        $block = new OrBlock(
            (new Success())->toBlock(),
            (new Fail())->toBlock(),
            ['any' => 'properties']
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $this->assertEquals([
            BlockSerializer::SERIALIZATION_BLOCKS            => [
                0 => [
                    BlockSerializer::TYPE                    => BlockSerializer::TYPE__OR_EXPRESSION,
                    BlockSerializer::PROPERTIES              => ['any' => 'properties'],
                    BlockSerializer::OR_EXPRESSION_PRIMARY   => 1,
                    BlockSerializer::OR_EXPRESSION_SECONDARY => 2,
                ],
                1 => $this->getSampleSerializationForSuccess(),
                2 => $this->getSampleSerializationForFail(),
            ],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_START => [],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_END   => [],
        ], $serialized);
    }

    /**
     * Test IF block serialization.
     */
    public function testSerializeIfBlock(): void
    {
        $condition = [static::class, 'ifBlockCondition'];
        $block     = new IfBlock(
            $condition,
            (new Success())->toBlock(),
            (new Fail())->toBlock(),
            ['any' => 'properties']
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $this->assertEquals([
            BlockSerializer::SERIALIZATION_BLOCKS            => [
                0 => [
                    BlockSerializer::TYPE                             => BlockSerializer::TYPE__IF_EXPRESSION,
                    BlockSerializer::PROPERTIES                       => ['any' => 'properties'],
                    BlockSerializer::IF_EXPRESSION_CONDITION_CALLABLE => $condition,
                    BlockSerializer::IF_EXPRESSION_ON_TRUE_BLOCK      => 1,
                    BlockSerializer::IF_EXPRESSION_ON_FALSE_BLOCK     => 2,
                ],
                1 => $this->getSampleSerializationForSuccess(),
                2 => $this->getSampleSerializationForFail(),
            ],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_START => [],
            BlockSerializer::SERIALIZATION_BLOCKS_WITH_END   => [],
        ], $serialized);
    }

    /**
     * Test block interpreter.
     */
    public function testInterpretProcedureSuccess(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_SUCCESS,
            [
                BaseRule::PROPERTY_NAME               => 'name',
                BaseRule::PROPERTY_IS_CAPTURE_ENABLED => true,
            ],
            static::PROCEDURE_START_SUCCESS,
            static::PROCEDURE_END_SUCCESS
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $context  = new ContextStorage(BlockSerializer::unserializeBlocks($serialized));
        $captures = new CaptureAggregator();
        $errors   = new ErrorAggregator();

        BlockInterpreter::execute('whatever', $serialized, $context, $captures, $errors);

        $this->assertEquals(['name' => 'whatever'], $captures->get());
        $this->assertEmpty($errors->get());
    }

    /**
     * Test block interpreter.
     */
    public function testInterpretProcedureError(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_ERROR,
            [
                BaseRule::PROPERTY_NAME               => 'name',
                BaseRule::PROPERTY_IS_CAPTURE_ENABLED => true,
            ],
            static::PROCEDURE_START_SUCCESS,
            static::PROCEDURE_END_SUCCESS
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $context  = new ContextStorage(BlockSerializer::unserializeBlocks($serialized));
        $captures = new CaptureAggregator();
        $errors   = new ErrorAggregator();

        BlockInterpreter::execute('whatever', $serialized, $context, $captures, $errors);

        $this->assertEmpty($captures->get());
        $this->assertCount(1, $errors->get());
        /** @var ErrorInterface $error */
        $error = $errors->get()[0];
        $this->assertEquals('name', $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(['some_value__exec'], $error->getMessageContext());
    }

    /**
     * Test block interpreter.
     */
    public function testInterpretProcedureStartError(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_SUCCESS,
            [
                BaseRule::PROPERTY_NAME               => 'name',
                BaseRule::PROPERTY_IS_CAPTURE_ENABLED => true,
            ],
            static::PROCEDURE_START_ERROR,
            static::PROCEDURE_END_SUCCESS
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $context  = new ContextStorage(BlockSerializer::unserializeBlocks($serialized));
        $captures = new CaptureAggregator();
        $errors   = new ErrorAggregator();

        BlockInterpreter::execute('whatever', $serialized, $context, $captures, $errors);

        $this->assertEquals(['name' => 'whatever'], $captures->get());
        $this->assertCount(1, $errors->get());
        /** @var ErrorInterface $error */
        $error = $errors->get()[0];
        $this->assertEquals('name', $error->getParameterName());
        $this->assertEquals(null, $error->getParameterValue());
        $this->assertEquals(['some_value__start'], $error->getMessageContext());
    }

    /**
     * Test block interpreter.
     */
    public function testInterpretProcedureEndError(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_SUCCESS,
            [
                BaseRule::PROPERTY_NAME               => 'name',
                BaseRule::PROPERTY_IS_CAPTURE_ENABLED => true,
            ],
            static::PROCEDURE_START_SUCCESS,
            static::PROCEDURE_END_ERROR
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $context  = new ContextStorage(BlockSerializer::unserializeBlocks($serialized));
        $captures = new CaptureAggregator();
        $errors   = new ErrorAggregator();

        BlockInterpreter::execute('whatever', $serialized, $context, $captures, $errors);

        $this->assertEquals(['name' => 'whatever'], $captures->get());
        $this->assertCount(1, $errors->get());
        /** @var ErrorInterface $error */
        $error = $errors->get()[0];
        $this->assertEquals('name', $error->getParameterName());
        $this->assertEquals(null, $error->getParameterValue());
        $this->assertEquals(['some_value__end'], $error->getMessageContext());
    }

    /**
     * Test block interpreter.
     */
    public function testInterpretProcedureStartExecEndError(): void
    {
        $block = new ProcedureBlock(
            static::PROCEDURE_EXEC_ERROR,
            [
                BaseRule::PROPERTY_NAME               => 'name',
                BaseRule::PROPERTY_IS_CAPTURE_ENABLED => true,
            ],
            static::PROCEDURE_START_ERROR,
            static::PROCEDURE_END_ERROR
        );

        $serialized = (new BlockSerializer())->serialize($block)->get();

        $context  = new ContextStorage(BlockSerializer::unserializeBlocks($serialized));
        $captures = new CaptureAggregator();
        $errors   = new ErrorAggregator();

        BlockInterpreter::execute('whatever', $serialized, $context, $captures, $errors);

        $this->assertEmpty($captures->get());
        $this->assertCount(3, $errors->get());

        /** @var ErrorInterface $error */
        $error = $errors->get()[0];
        $this->assertEquals('name', $error->getParameterName());
        $this->assertEquals(null, $error->getParameterValue());
        $this->assertEquals(['some_value__start'], $error->getMessageContext());
        $error = $errors->get()[1];
        $this->assertEquals('name', $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(['some_value__exec'], $error->getMessageContext());
        $error = $errors->get()[2];
        $this->assertEquals('name', $error->getParameterName());
        // if we check parameter name it would be same as in exec because getErrorInfo in this class
        // just reads it from state. Which in its turn is set by exec. As it's a specific for this particular
        // test implementation we do not check parameter value for the error.
        $this->assertEquals(['some_value__end'], $error->getMessageContext());

        // add here some testing coverage for context's cleaning
        $context->clear();
    }

    /**
     * Test block serializer.
     *
     * @expectedException \Limoncello\Validation\Exceptions\UnknownExecutionBlockType
     */
    public function testSerializeUnknownBlockType(): void
    {
        $unknownTypeBlock = new class implements ExecutionBlockInterface
        {
            /**
             * @inheritdoc
             */
            public function getProperties(): array
            {
                return ['whatever'];
            }
        };

        (new BlockSerializer())->addBlock($unknownTypeBlock);
    }

    /**
     * @param mixed            $input
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureExecuteSuccess($input, ContextInterface $context): array
    {
        assert($context);

        return BlockReplies::createSuccessReply($input);
    }

    /**
     * @param mixed            $input
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureExecuteError($input, ContextInterface $context): array
    {
        return BlockReplies::createErrorReply($context, $input, ErrorCodes::INVALID_VALUE, ['some_value__exec']);
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureStartSuccess(ContextInterface $context): array
    {
        assert($context);

        return BlockReplies::createStartSuccessReply();
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureStartError(ContextInterface $context): array
    {
        return BlockReplies::createStartErrorReply($context, ErrorCodes::INVALID_VALUE, ['some_value__start']);
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureEndSuccess(ContextInterface $context): array
    {
        assert($context);

        return BlockReplies::createEndSuccessReply();
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    public static function procedureEndError(ContextInterface $context)
    {
        return BlockReplies::createEndErrorReply($context, ErrorCodes::INVALID_VALUE, ['some_value__end']);
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function ifBlockCondition(ContextInterface $context): bool
    {
        assert($context);

        return true;
    }

    /**
     * @return array
     */
    private function getSampleSerializationForSuccess(): array
    {
        return [
            BlockSerializer::TYPE                       => BlockSerializer::TYPE__PROCEDURE,
            BlockSerializer::PROPERTIES                 => [
                Success::PROPERTY_NAME               => '',
                Success::PROPERTY_IS_CAPTURE_ENABLED => false,
            ],
            BlockSerializer::PROCEDURE_EXECUTE_CALLABLE => [Success::class, 'execute'],
        ];
    }

    /**
     * @return array
     */
    private function getSampleSerializationForFail(): array
    {
        return [
            BlockSerializer::TYPE                       => BlockSerializer::TYPE__PROCEDURE,
            BlockSerializer::PROPERTIES                 => [
                Fail::PROPERTY_NAME               => '',
                Fail::PROPERTY_IS_CAPTURE_ENABLED => false,
                Fail::PROPERTY_ERROR_CODE         => ErrorCodes::INVALID_VALUE,
                Fail::PROPERTY_ERROR_CONTEXT      => null,
            ],
            BlockSerializer::PROCEDURE_EXECUTE_CALLABLE => [Fail::class, 'execute'],
        ];
    }
}

<?php declare(strict_types=1);

namespace Limoncello\Tests\Validation;

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

use DateTime;
use DateTimeImmutable;
use Exception;
use Limoncello\Validation\Blocks\AndBlock;
use Limoncello\Validation\Blocks\IfBlock;
use Limoncello\Validation\Blocks\OrBlock;
use Limoncello\Validation\Blocks\ProcedureBlock;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\I18n\Messages;
use Limoncello\Validation\Rules\BaseRule;
use Limoncello\Validation\Rules\Comparisons\DateTimeBetween;
use Limoncello\Validation\Rules\Comparisons\DateTimeEquals;
use Limoncello\Validation\Rules\Converters\StringToBool;
use Limoncello\Validation\Rules\Converters\StringToDateTime;
use Limoncello\Validation\Rules\Converters\StringToFloat;
use Limoncello\Validation\Rules\Converters\StringToInt;
use Limoncello\Validation\Rules\Generic\AndOperator;
use Limoncello\Validation\Rules\Generic\Fail;
use Limoncello\Validation\Rules\Generic\IfOperator;
use Limoncello\Validation\Rules\Generic\OrOperator;
use Limoncello\Validation\Rules\Generic\Required;
use Limoncello\Validation\Rules\Generic\Success;
use Limoncello\Validation\Rules\Types\AsArray;
use Limoncello\Validation\Rules\Types\AsBool;
use Limoncello\Validation\Rules\Types\AsDateTime;
use Limoncello\Validation\Rules\Types\AsFloat;
use Limoncello\Validation\Rules\Types\AsInt;
use Limoncello\Validation\Rules\Types\AsNumeric;
use Limoncello\Validation\Rules\Types\AsString;
use PHPUnit\Framework\TestCase;
use function assert;
use function is_callable;

/**
 * @package Limoncello\Tests\Validation
 */
class RulesToBlocksTest extends TestCase
{
    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testOneValueComparision(): void
    {
        $date = new DateTimeImmutable('2001-03-03 04:05:06');
        $rule = new DateTimeEquals($date);

        /** @var IfBlock $block */
        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertTrue(is_callable($block->getConditionCallable()));
        $this->assertEquals([
            DateTimeEquals::PROPERTY_NAME               => '',
            DateTimeEquals::PROPERTY_IS_CAPTURE_ENABLED => false,
            DateTimeEquals::PROPERTY_VALUE              => $date->getTimestamp(),
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertEquals([
            DateTimeEquals::PROPERTY_NAME               => 'name',
            DateTimeEquals::PROPERTY_IS_CAPTURE_ENABLED => true,
            DateTimeEquals::PROPERTY_VALUE              => $date->getTimestamp(),
        ], $block->getProperties());

        $this->assertTrue($block->getOnTrue() instanceof ProcedureBlock);
        $this->assertEquals([
            Success::PROPERTY_NAME               => 'name',
            Success::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getOnTrue()->getProperties());

        $this->assertTrue($block->getOnFalse() instanceof ProcedureBlock);
        $this->assertEquals([
            Fail::PROPERTY_NAME                     => 'name',
            Fail::PROPERTY_IS_CAPTURE_ENABLED       => false,
            Fail::PROPERTY_ERROR_CODE               => ErrorCodes::DATE_TIME_EQUALS,
            Fail::PROPERTY_ERROR_MESSAGE_TEMPLATE   => Messages::DATE_TIME_EQUALS,
            Fail::PROPERTY_ERROR_MESSAGE_PARAMETERS => [$date->getTimestamp()],
        ], $block->getOnFalse()->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testTwoValueComparision(): void
    {
        $date1 = new DateTimeImmutable('2001-03-03 04:05:06');
        $date2 = new DateTime('2007-08-09 10:11:12');
        $rule  = new DateTimeBetween($date1, $date2);

        /** @var IfBlock $block */
        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertTrue(is_callable($block->getConditionCallable()));
        $this->assertEquals([
            DateTimeBetween::PROPERTY_NAME               => '',
            DateTimeBetween::PROPERTY_IS_CAPTURE_ENABLED => false,
            DateTimeBetween::PROPERTY_LOWER_VALUE        => $date1->getTimestamp(),
            DateTimeBetween::PROPERTY_UPPER_VALUE        => $date2->getTimestamp(),
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertEquals([
            DateTimeBetween::PROPERTY_NAME               => 'name',
            DateTimeBetween::PROPERTY_IS_CAPTURE_ENABLED => true,
            DateTimeBetween::PROPERTY_LOWER_VALUE        => $date1->getTimestamp(),
            DateTimeBetween::PROPERTY_UPPER_VALUE        => $date2->getTimestamp(),
        ], $block->getProperties());

        $this->assertTrue($block->getOnTrue() instanceof ProcedureBlock);
        $this->assertEquals([
            Success::PROPERTY_NAME               => 'name',
            Success::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getOnTrue()->getProperties());

        $this->assertTrue($block->getOnFalse() instanceof ProcedureBlock);
        $this->assertEquals([
            Fail::PROPERTY_NAME                     => 'name',
            Fail::PROPERTY_IS_CAPTURE_ENABLED       => false,
            Fail::PROPERTY_ERROR_CODE               => ErrorCodes::DATE_TIME_BETWEEN,
            Fail::PROPERTY_ERROR_MESSAGE_TEMPLATE   => Messages::DATE_TIME_BETWEEN,
            Fail::PROPERTY_ERROR_MESSAGE_PARAMETERS => [$date1->getTimestamp(), $date2->getTimestamp()],
        ], $block->getOnFalse()->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testStringToBoolConverter(): void
    {
        $rule = new StringToBool();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToBool::PROPERTY_NAME               => '',
            StringToBool::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToBool::PROPERTY_NAME               => 'name',
            StringToBool::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testStringToDateTimeConverter(): void
    {
        $rule = new StringToDateTime(DATE_ATOM);

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToDateTime::PROPERTY_NAME               => '',
            StringToDateTime::PROPERTY_IS_CAPTURE_ENABLED => false,
            StringToDateTime::PROPERTY_FORMAT             => DATE_ATOM,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToDateTime::PROPERTY_NAME               => 'name',
            StringToDateTime::PROPERTY_IS_CAPTURE_ENABLED => true,
            StringToDateTime::PROPERTY_FORMAT             => DATE_ATOM,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testStringToFloatConverter(): void
    {
        $rule = new StringToFloat();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToFloat::PROPERTY_NAME               => '',
            StringToFloat::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToFloat::PROPERTY_NAME               => 'name',
            StringToFloat::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testStringToIntConverter(): void
    {
        $rule = new StringToInt();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToInt::PROPERTY_NAME               => '',
            StringToInt::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            StringToInt::PROPERTY_NAME               => 'name',
            StringToInt::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testAndOperator(): void
    {
        $rule = new AndOperator(new Success(), new Fail());

        $this->assertTrue(($block = $rule->toBlock()) instanceof AndBlock);
        $this->assertEquals([
            AndOperator::PROPERTY_NAME               => '',
            AndOperator::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof AndBlock);
        $this->assertEquals([
            AndOperator::PROPERTY_NAME               => 'name',
            AndOperator::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testOrOperator(): void
    {
        $rule = new OrOperator(new Success(), new Fail());

        $this->assertTrue(($block = $rule->toBlock()) instanceof OrBlock);
        $this->assertEquals([
            OrOperator::PROPERTY_NAME               => '',
            OrOperator::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof OrBlock);
        $this->assertEquals([
            OrOperator::PROPERTY_NAME               => 'name',
            OrOperator::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testIfOperator(): void
    {
        $rule = new IfOperator([static::class, 'dummyConditionCallable'], new Success(), new Fail());

        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertEquals([
            IfOperator::PROPERTY_NAME               => '',
            IfOperator::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof IfBlock);
        $this->assertEquals([
            IfOperator::PROPERTY_NAME               => 'name',
            IfOperator::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testSuccess(): void
    {
        $rule = new Success();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            Success::PROPERTY_NAME               => '',
            Success::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            Success::PROPERTY_NAME               => 'name',
            Success::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testFail(): void
    {
        $rule = new Fail();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            Fail::PROPERTY_NAME                     => '',
            Fail::PROPERTY_IS_CAPTURE_ENABLED       => false,
            Fail::PROPERTY_ERROR_CODE               => ErrorCodes::INVALID_VALUE,
            Fail::PROPERTY_ERROR_MESSAGE_TEMPLATE   => Messages::INVALID_VALUE,
            Fail::PROPERTY_ERROR_MESSAGE_PARAMETERS => [],
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
        $this->assertEquals([
            Fail::PROPERTY_NAME                     => 'name',
            Fail::PROPERTY_IS_CAPTURE_ENABLED       => true,
            Fail::PROPERTY_ERROR_CODE               => ErrorCodes::INVALID_VALUE,
            Fail::PROPERTY_ERROR_MESSAGE_TEMPLATE   => Messages::INVALID_VALUE,
            Fail::PROPERTY_ERROR_MESSAGE_PARAMETERS => [],
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testRequired(): void
    {
        $rule = new Required(new Success());

        $this->assertTrue(($block = $rule->toBlock()) instanceof AndBlock);
        $this->assertEquals([
            Required::PROPERTY_NAME               => '',
            Required::PROPERTY_IS_CAPTURE_ENABLED => false,
        ], $block->getProperties());

        $rule->setName('name')->enableCapture();

        $this->assertTrue(($block = $rule->toBlock()) instanceof AndBlock);
        $this->assertEquals([
            Required::PROPERTY_NAME               => 'name',
            Required::PROPERTY_IS_CAPTURE_ENABLED => true,
        ], $block->getProperties());
    }

    /**
     * Test rule to blocks transformation.
     *
     * @throws Exception
     */
    public function testTypes(): void
    {
        $classes = [
            AsArray::class,
            AsBool::class,
            AsDateTime::class,
            AsFloat::class,
            AsInt::class,
            AsNumeric::class,
            AsString::class,
        ];
        foreach ($classes as $className) {
            /** @var BaseRule $rule */
            $rule = new $className();

            $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
            $this->assertEquals([
                AsArray::PROPERTY_NAME               => '',
                AsArray::PROPERTY_IS_CAPTURE_ENABLED => false,
            ], $block->getProperties());

            $rule->setName('name')->enableCapture();

            $this->assertTrue(($block = $rule->toBlock()) instanceof ProcedureBlock);
            $this->assertEquals([
                AsArray::PROPERTY_NAME               => 'name',
                AsArray::PROPERTY_IS_CAPTURE_ENABLED => true,
            ], $block->getProperties());
        }
    }

    /**
     * @param mixed            $input
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function dummyConditionCallable($input, ContextInterface $context): bool
    {
        assert($input || $context);

        return true;
    }
}

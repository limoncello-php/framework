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

use DateTimeImmutable;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Rules as v;
use Limoncello\Validation\Validator;
use Limoncello\Validation\Validator\ArrayValidation;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @package Limoncello\Tests\Validation
 */
class RulesTest extends TestCase
{
    use ArrayValidation;

    /**
     * Test validator.
     */
    public function testDates(): void
    {
        $jan1  = new DateTimeImmutable('2001-01-01');
        $feb1  = new DateTimeImmutable('2001-02-01');
        $jan20 = new DateTimeImmutable('2001-01-20');

        $rules = [
            'date1' => v::stringToDateTime(DATE_ATOM, v::between($jan1, $feb1)),
            'date2' => v::stringToDateTime('Y-d-m\TH:i:sP', v::equals($jan20)),
            'date3' => v::stringToDateTime(DATE_ATOM, v::notEquals($jan20)),
            'date4' => v::nullable(v::stringToDateTime(DATE_ATOM, v::success())),
            'date5' => v::nullable(v::stringToDateTime(DATE_ATOM, v::success())),
            'date6' => v::stringToDateTime(DATE_ATOM, v::lessThan($feb1)),
            'date7' => v::stringToDateTime(DATE_ATOM, v::lessOrEquals($feb1)),
            'date8' => v::stringToDateTime(DATE_ATOM, v::moreThan($feb1)),
            'date9' => v::stringToDateTime(DATE_ATOM, v::moreOrEquals($feb1)),
        ];

        // Check with valid input

        $input = [
            'date1' => '2001-01-02T00:00:00Z',
            'date2' => '2001-20-01T00:00:00Z',
            'date3' => '2001-01-21T00:00:00Z',
            'date4' => null,
            'date5' => '2001-02-03T00:00:00Z',
            'date6' => '2001-01-02T00:00:00Z',
            'date7' => '2001-02-01T00:00:00Z',
            'date8' => '2001-02-02T00:00:00Z',
            'date9' => '2001-02-01T00:00:00Z',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals([
            'date1' => new DateTimeImmutable('2001-01-02T00:00:00Z'),
            'date2' => $jan20,
            'date3' => new DateTimeImmutable('2001-01-21T00:00:00Z'),
            'date4' => null,
            'date5' => new DateTimeImmutable('2001-02-03T00:00:00Z'),
            'date6' => new DateTimeImmutable('2001-01-02T00:00:00Z'),
            'date7' => new DateTimeImmutable('2001-02-01T00:00:00Z'),
            'date8' => new DateTimeImmutable('2001-02-02T00:00:00Z'),
            'date9' => new DateTimeImmutable('2001-02-01T00:00:00Z'),
        ], $captures);

        // Check with invalid input

        $input = [
            'date1' => '2001-03-02T00:00:00Z',
            'date2' => '2001-21-01T00:00:00Z',
            'date3' => '2001-01-20T00:00:00Z',
            'date4' => new stdClass(),
            'date5' => 'not date',
            'date6' => '2001-02-01T00:00:00Z',
            'date7' => '2001-02-02T00:00:00Z',
            'date8' => '2001-02-01T00:00:00Z',
            'date9' => '2001-01-01T00:00:00Z',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(9, $errors);

        $this->assertEquals(
            ErrorCodes::DATE_TIME_BETWEEN,
            $this->findErrorByParamName('date1', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_EQUALS,
            $this->findErrorByParamName('date2', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_NOT_EQUALS,
            $this->findErrorByParamName('date3', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_DATE_TIME,
            $this->findErrorByParamName('date4', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_DATE_TIME,
            $this->findErrorByParamName('date5', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_LESS_THAN,
            $this->findErrorByParamName('date6', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_LESS_OR_EQUALS,
            $this->findErrorByParamName('date7', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_MORE_THAN,
            $this->findErrorByParamName('date8', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::DATE_TIME_MORE_OR_EQUALS,
            $this->findErrorByParamName('date9', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testScalars(): void
    {
        $rules = [
            'scalar1'  => v::stringToInt(v::between(5, 10)),
            'scalar2'  => v::stringToFloat(v::equals(5.5)),
            'scalar3'  => v::stringToBool(v::notEquals(false)),
            'scalar4'  => v::nullable(v::stringToInt(v::success())),
            'scalar5'  => v::nullable(v::stringToFloat(v::success())),
            'scalar6'  => v::stringToInt(v::lessThan(5)),
            'scalar7'  => v::stringToInt(v::lessOrEquals(5)),
            'scalar8'  => v::stringToInt(v::moreThan(5)),
            'scalar9'  => v::stringToInt(v::moreOrEquals(5)),
            'scalar10' => v::isString(v::inValues(['one', 'two', 'three'])),
            'scalar11' => v::notEquals(null),
        ];

        // Check with valid input

        $input = [
            'scalar1'  => '7',
            'scalar2'  => '5.5',
            'scalar3'  => 'true',
            'scalar4'  => null,
            'scalar5'  => '5.5',
            'scalar6'  => '4',
            'scalar7'  => '5',
            'scalar8'  => '6',
            'scalar9'  => '5',
            'scalar10' => 'two',
            'scalar11' => 'anything',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals([
            'scalar1'  => 7,
            'scalar2'  => 5.5,
            'scalar3'  => true,
            'scalar4'  => null,
            'scalar5'  => 5.5,
            'scalar6'  => 4,
            'scalar7'  => 5,
            'scalar8'  => 6,
            'scalar9'  => 5,
            'scalar10' => 'two',
            'scalar11' => 'anything',
        ], $captures);

        // Check with invalid input

        $input = [
            'scalar1'  => '3',
            'scalar2'  => '6.5',
            'scalar3'  => 'false',
            'scalar4'  => new stdClass(),
            'scalar5'  => new stdClass(),
            'scalar6'  => '5',
            'scalar7'  => '6',
            'scalar8'  => '5',
            'scalar9'  => '4',
            'scalar10' => 'four',
            'scalar11' => null,
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(11, $errors);

        $this->assertEquals(
            ErrorCodes::NUMERIC_BETWEEN,
            $this->findErrorByParamName('scalar1', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::SCALAR_EQUALS,
            $this->findErrorByParamName('scalar2', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::SCALAR_NOT_EQUALS,
            $this->findErrorByParamName('scalar3', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_INT,
            $this->findErrorByParamName('scalar4', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_FLOAT,
            $this->findErrorByParamName('scalar5', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::NUMERIC_LESS_THAN,
            $this->findErrorByParamName('scalar6', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::NUMERIC_LESS_OR_EQUALS,
            $this->findErrorByParamName('scalar7', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::NUMERIC_MORE_THAN,
            $this->findErrorByParamName('scalar8', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::NUMERIC_MORE_OR_EQUALS,
            $this->findErrorByParamName('scalar9', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::SCALAR_IN_VALUES,
            $this->findErrorByParamName('scalar10', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::SCALAR_NOT_EQUALS,
            $this->findErrorByParamName('scalar11', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testStrings(): void
    {
        $rules = [
            'string1' => v::isString(v::stringLengthBetween(3, 5)),
            'string2' => v::isString(v::stringLengthMin(3)),
            'string3' => v::isString(v::stringLengthMax(5)),
            'string4' => v::nullable(v::isString(v::success())),
            'string5' => v::nullable(v::isString(v::success())),
            'string6' => v::isString(v::regexp('/^def$/')),
        ];

        // Check with valid input

        $input = [
            'string1' => '1234',
            'string2' => '123',
            'string3' => '12345',
            'string4' => null,
            'string5' => 'any string',
            'string6' => 'def',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals($input, $captures);

        // Check with invalid input 1

        $input = [
            'string1' => '12',
            'string2' => '12',
            'string3' => '123456',
            'string4' => new stdClass(),
            'string5' => 123,
            'string6' => 'non matching value',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(6, $errors);

        $this->assertEquals(
            ErrorCodes::STRING_LENGTH_BETWEEN,
            $this->findErrorByParamName('string1', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::STRING_LENGTH_MIN,
            $this->findErrorByParamName('string2', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::STRING_LENGTH_MAX,
            $this->findErrorByParamName('string3', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_STRING,
            $this->findErrorByParamName('string4', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_STRING,
            $this->findErrorByParamName('string5', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::STRING_REG_EXP,
            $this->findErrorByParamName('string6', $errors)->getMessageCode()
        );

        // Check with invalid input 2

        $input = [
            'string1' => '1234567',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(1, $errors);

        $this->assertEquals(
            ErrorCodes::STRING_LENGTH_BETWEEN,
            $this->findErrorByParamName('string1', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testConverters(): void
    {
        $rules = [
            'string1'    => v::stringToBool(),
            'string2'    => v::stringToBool(),
            'string3'    => v::stringToDateTime(DATE_ATOM),
            'string4'    => v::stringToFloat(),
            'string5'    => v::stringToInt(),
            'stringArr1' => v::stringArrayToIntArray(),
            'stringArr2' => v::stringArrayToIntArray(),
        ];

        // Check with valid input

        $now   = new DateTimeImmutable();
        $input = [
            'string1'    => 'yes',
            'string2'    => true,
            'string3'    => $now,
            'string4'    => 5.5,
            'string5'    => 5,
            'stringArr1' => ['1', '2',],
            'stringArr2' => ['3tree',],
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals([
            'string1'    => true,
            'string2'    => true,
            'string3'    => $now,
            'string4'    => 5.5,
            'string5'    => 5,
            'stringArr1' => [1, 2],
            'stringArr2' => [3],
        ], $captures);

        // Check with invalid input

        $input = [
            'string1'    => 'non bool',
            'string2'    => new stdClass(),
            'string3'    => 'not date or date in wrong format',
            'string4'    => [],
            'string5'    => [],
            'stringArr1' => 123,
            'stringArr2' => [new stdClass(),],
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(7, $errors);

        $this->assertEquals(
            ErrorCodes::IS_BOOL,
            $this->findErrorByParamName('string1', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_BOOL,
            $this->findErrorByParamName('string2', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_DATE_TIME,
            $this->findErrorByParamName('string3', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_FLOAT,
            $this->findErrorByParamName('string4', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_INT,
            $this->findErrorByParamName('string5', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_ARRAY,
            $this->findErrorByParamName('stringArr1', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_STRING,
            $this->findErrorByParamName('stringArr2', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testRequired(): void
    {
        $rules = [
            'string1' => v::required(v::isString(v::success())),
            'string2' => v::isString(v::success()),
        ];

        // Check with valid input

        $input = [
            'string1' => 'whatever 1',
            'string2' => 'whatever 2',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertCount(2, $captures);

        // Check with invalid input

        $input = [];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(1, $errors);

        $this->assertEquals(
            ErrorCodes::REQUIRED,
            $this->findErrorByParamName('string1', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testEnum(): void
    {
        $values = ['one', 'two', 'three'];

        $rules = [
            'value1' => v::enum($values),
            'value2' => v::enum($values, v::equals('two')),
        ];

        // Check with valid input

        $input = [
            'value1' => 'one',
            'value2' => 'two',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertCount(2, $captures);

        // Check with invalid input

        $input = [
            'value1' => 'four',
            'value2' => 'one',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(2, $errors);

        $this->assertEquals(
            ErrorCodes::INVALID_VALUE,
            $this->findErrorByParamName('value1', $errors)->getMessageCode()
        );

        $this->assertEquals(
            ErrorCodes::SCALAR_EQUALS,
            $this->findErrorByParamName('value2', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testFilter(): void
    {
        $rules = [
            'value1' => v::filter(FILTER_VALIDATE_EMAIL),
            'value2' => v::filter(FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH),
        ];

        // Check with valid input

        $input = [
            'value1' => 'bob@example.com',
            'value2' => 'Hello WorldÆØÅ!',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals([
            'value1' => 'bob@example.com',
            'value2' => 'Hello World!',
        ], $captures);

        // Check with invalid input

        $input = [
            'value1' => 'not_an_email',
            'value2' => new stdClass(),
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(2, $errors);

        $this->assertEquals(
            ErrorCodes::INVALID_VALUE,
            $this->findErrorByParamName('value1', $errors)->getMessageCode()
        );

        $this->assertEquals(
            ErrorCodes::INVALID_VALUE,
            $this->findErrorByParamName('value2', $errors)->getMessageCode()
        );
    }

    /**
     * Test validator.
     */
    public function testTypes(): void
    {
        $rules = [
            'array'   => v::isArray(v::success()),
            'bool'    => v::isBool(v::success()),
            'date'    => v::isDateTime(v::success()),
            'float'   => v::isFloat(v::success()),
            'int'     => v::isInt(v::success()),
            'numeric' => v::isNumeric(v::success()),
            'string'  => v::isString(v::success()),
        ];

        // Check with valid input

        $now   = new DateTimeImmutable();
        $input = [
            'array'   => [],
            'bool'    => true,
            'date'    => $now,
            'float'   => 5.5,
            'int'     => 5,
            'numeric' => 5.5,
            'string'  => 'some string',
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($errors);
        $this->assertEquals($input, $captures);

        // Check with invalid input

        $input = [
            'array'   => new stdClass(),
            'bool'    => new stdClass(),
            'date'    => new stdClass(),
            'float'   => new stdClass(),
            'int'     => new stdClass(),
            'numeric' => 'ABC',
            'string'  => new stdClass(),
        ];

        list($captures, $errors) = $this->validateArray($input, $rules);

        $this->assertEmpty($captures);
        $this->assertCount(7, $errors);

        $this->assertEquals(
            ErrorCodes::IS_ARRAY,
            $this->findErrorByParamName('array', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_BOOL,
            $this->findErrorByParamName('bool', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_DATE_TIME,
            $this->findErrorByParamName('date', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_FLOAT,
            $this->findErrorByParamName('float', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_INT,
            $this->findErrorByParamName('int', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_NUMERIC,
            $this->findErrorByParamName('numeric', $errors)->getMessageCode()
        );
        $this->assertEquals(
            ErrorCodes::IS_STRING,
            $this->findErrorByParamName('string', $errors)->getMessageCode()
        );
    }

    /**
     * Test basic rule methods.
     */
    public function testBaseRule(): void
    {
        $rule = v::success();

        $this->assertEmpty($rule->getName());
        $this->assertFalse($rule->isCaptureEnabled());
        $this->assertNull($rule->getParent());

        $rule->setName('name')->enableCapture()->setParent(v::fail());

        $this->assertNotEmpty($rule->getName());
        $this->assertTrue($rule->isCaptureEnabled());
        $this->assertNotNull($rule->getParent());

        $rule->unsetName()->disableCapture()->unsetParent();

        $this->assertEmpty($rule->getName());
        $this->assertFalse($rule->isCaptureEnabled());
        $this->assertNull($rule->getParent());
    }

    /**
     * Emulate we validate data against database.
     */
    public function testEmulateValidationChecksDatabaseRecord(): void
    {
        $dbExistRule = v::isString(v::stringToInt(
            v::ifX([static::class, 'emulateDbRequest'], v::success(), v::fail())
        ));
        $validator   = Validator::validator($dbExistRule);

        // no error
        $this->assertTrue($validator->validate('5'));

        // has error
        $this->assertFalse($validator->validate('15'));
    }

    /**
     * @param mixed            $input
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function emulateDbRequest($input, ContextInterface $context): bool
    {
        assert($context);

        // emulate database request
        $recordExists = is_int($input) === true && $input < 10;

        return $recordExists;
    }

    /**
     * @param array $input
     * @param array $rules
     *
     * @return array
     */
    private function validateArray(array $input, array $rules): array
    {
        $errors   = new ErrorAggregator();
        $captures = new CaptureAggregator();

        // both aggregators are Countable. Just add some testing/coverage for that.
        $this->assertCount(0, $errors);
        $this->assertCount(0, $captures);

        $this->setRules($rules)->validateArrayImplementation($input, $captures, $errors);

        return [
            $captures->get(),
            $errors->get(),
        ];
    }

    /**
     * @param array $blocks
     *
     * @return ContextStorageInterface
     */
    protected function createContextStorageFromBlocks(array $blocks): ContextStorageInterface
    {
        $context = new ContextStorage($blocks);

        return $context;
    }

    /**
     * @param string           $name
     * @param ErrorInterface[] $errors
     *
     * @return ErrorInterface
     */
    private function findErrorByParamName(string $name, array $errors): ErrorInterface
    {
        $error = null;
        foreach ($errors as $curError) {
            if ($curError->getParameterName() === $name) {
                $error = $curError;
                break;
            }
        }

        assert($error !== null);

        return $error;
    }
}

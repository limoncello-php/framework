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

use DateTime;
use Limoncello\Tests\Validation\Data\AppValidator as v;
use Limoncello\Validation\Contracts\MessageCodes;
use Limoncello\Validation\Contracts\ValidatorInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\I18n\Locales\EnUsLocale;
use Limoncello\Validation\I18n\Translator;
use Limoncello\Validation\Rules\Between;
use Limoncello\Validation\Rules\InValues;
use Limoncello\Validation\Rules\IsDateTimeFormat;
use Limoncello\Validation\Rules\RegExp;
use Limoncello\Validation\Rules\StringLength;
use Sample\Application;

/**
 * @package Limoncello\Tests\Validation
 */
class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = new Translator(
            EnUsLocale::getLocaleCode(),
            EnUsLocale::getMessages(),
            [],
            MessageCodes::INVALID_VALUE
        );
    }

    /**
     * Test validator.
     */
    public function testIsNull()
    {
        $rule      = v::isNull();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, null));

        $invalidValue = 'not null';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_NULL, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be null.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be null.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testNotNull()
    {
        $rule      = v::notNull();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'not null'));

        $invalidValue = null;
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::NOT_NULL, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should not be null.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should not be null.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testRegExp()
    {
        $pattern   = '/^def$/';
        $rule      = v::regExp($pattern);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'def'));

        $invalidValue = 'A';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::REG_EXP, $error->getMessageCode());
        $this->assertEquals([RegExp::CONTEXT_PATTERN => $pattern], $error->getMessageContext());
        $this->assertEquals('The `` value format is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value format is invalid.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testBetween1()
    {
        $min       = 1;
        $max       = 5;
        $rule      = v::between($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, '3'));

        $invalidValue = '10';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::BETWEEN, $error->getMessageCode());
        $this->assertEquals([Between::CONTEXT_MIN => $min, Between::CONTEXT_MAX => $max], $error->getMessageContext());
        $this->assertEquals('The `` value should be between 1 and 5.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be between 1 and 5.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testBetween2()
    {
        $min       = null;
        $max       = 5;
        $rule      = v::between($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, '3'));

        $invalidValue = '10';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::BETWEEN_MAX, $error->getMessageCode());
        $this->assertEquals([Between::CONTEXT_MIN => $min, Between::CONTEXT_MAX => $max], $error->getMessageContext());
        $this->assertEquals('The `` value should not be greater than 5.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should not be greater than 5.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testBetween3()
    {
        $min       = 1;
        $max       = null;
        $rule      = v::between($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, '3'));

        $invalidValue = '-10';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::BETWEEN_MIN, $error->getMessageCode());
        $this->assertEquals([Between::CONTEXT_MIN => $min, Between::CONTEXT_MAX => $max], $error->getMessageContext());
        $this->assertEquals('The `` value should be at least 1.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be at least 1.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testStringLength1()
    {
        $min       = 2;
        $max       = 5;
        $rule      = v::stringLength($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'ABC'));

        $invalidValue = 'A';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::STRING_LENGTH, $error->getMessageCode());
        $this->assertEquals(
            [StringLength::CONTEXT_MIN => $min, StringLength::CONTEXT_MAX => $max],
            $error->getMessageContext()
        );
        $this->assertEquals('The `` value should be between 2 and 5 characters.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals(
            'The `name` value should be between 2 and 5 characters.',
            $this->translator->translate($error)
        );
    }

    /**
     * Test validator.
     */
    public function testStringLength2()
    {
        $min       = null;
        $max       = 5;
        $rule      = v::stringLength($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'ABC'));

        $invalidValue = 'Too long string';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::STRING_LENGTH_MAX, $error->getMessageCode());
        $this->assertEquals(
            [StringLength::CONTEXT_MIN => $min, StringLength::CONTEXT_MAX => $max],
            $error->getMessageContext()
        );
        $this->assertEquals(
            'The `` value should not be greater than 5 characters.',
            $this->translator->translate($error)
        );

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals(
            'The `name` value should not be greater than 5 characters.',
            $this->translator->translate($error)
        );
    }

    /**
     * Test validator.
     */
    public function testStringLength3()
    {
        $min       = 2;
        $max       = null;
        $rule      = v::stringLength($min, $max);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'ABC'));

        $invalidValue = 'A'; // too short string
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::STRING_LENGTH_MIN, $error->getMessageCode());
        $this->assertEquals(
            [StringLength::CONTEXT_MIN => $min, StringLength::CONTEXT_MAX => $max],
            $error->getMessageContext()
        );
        $this->assertEquals(
            'The `` value should be at least 2 characters.',
            $this->translator->translate($error)
        );

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals(
            'The `name` value should be at least 2 characters.',
            $this->translator->translate($error)
        );
    }

    /**
     * Test validator.
     */
    public function testIsString()
    {
        $rule      = v::isString();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'a string'));

        $invalidValue = 123;
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_STRING, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be a string.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be a string.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsBool()
    {
        $rule      = v::isBool();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, false));

        $invalidValue = 123;
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_BOOL, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be boolean.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be boolean.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsInt()
    {
        $rule      = v::isInt();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 123));

        $invalidValue = '123';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_INT, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be integer.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be integer.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsFloat()
    {
        $rule      = v::isFloat();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 1.23));

        $invalidValue = 123;
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_FLOAT, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be float.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be float.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsNumeric()
    {
        $rule      = v::isNumeric();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, '1.23'));

        $invalidValue = 'abc';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_NUMERIC, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be numeric.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be numeric.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsArray()
    {
        $rule      = v::isArray();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, []));

        $invalidValue = 'abc';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_ARRAY, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value should be an array.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value should be an array.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testInValues()
    {
        $allowed   = ['one', 'some-key' => 'two', 5 => 'three'];
        $rule      = v::inValues($allowed);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, 'one'));
        $this->assertEmpty($this->readErrors($validator, 'two'));
        $this->assertEmpty($this->readErrors($validator, 'three'));

        $invalidValue = 'abc';
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IN_VALUES, $error->getMessageCode());
        $this->assertEquals([InValues::CONTEXT_VALUES => $allowed], $error->getMessageContext());
        $this->assertEquals('The `` value is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));
    }

    /**
     * Test validator.
     */
    public function testIsDateTimeFormat()
    {
        $rule      = v::isDateTimeFormat(DateTime::W3C);
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, date(DateTime::W3C)));

        $invalidValue = date(DateTime::RFC850);
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_DATE_TIME_FORMAT, $error->getMessageCode());
        $this->assertEquals([IsDateTimeFormat::CONTEXT_FORMAT => DateTime::W3C], $error->getMessageContext());
        $this->assertEquals(
            'The `` value should be a date time in format `Y-m-d\TH:i:sP`.',
            $this->translator->translate($error)
        );

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals(
            'The `name` value should be a date time in format `Y-m-d\TH:i:sP`.',
            $this->translator->translate($error)
        );
    }

    /**
     * Test validator.
     */
    public function testIsDateTime()
    {
        $rule      = v::isDateTime();
        $validator = v::validator($rule);

        $this->assertEmpty($this->readErrors($validator, new DateTime()));

        $invalidValue = date(DateTime::RFC850);
        $this->assertCount(1, $errors = $this->readErrors($validator, $invalidValue));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals($invalidValue, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_DATE_TIME, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals(
            'The `` value should be a valid date time.',
            $this->translator->translate($error)
        );

        $rule->setParameterName('name');
        $error = $this->readErrors($validator, $invalidValue)[0];
        $this->assertEquals(
            'The `name` value should be a valid date time.',
            $this->translator->translate($error)
        );
    }

    /**
     * Test validator.
     */
    public function testAndX()
    {
        // all ok
        $this->assertEmpty($this->readErrors(
            v::validator(v::andX(v::success(), v::success())),
            'whatever'
        ));

        // first to fail
        $this->assertCount(1, $errors = $this->readErrors(
            v::validator($rule = v::andX(v::fail(123456), v::success())),
            'whatever'
        ));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(123456, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors(v::validator($rule), 'whatever')[0];
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));

        // second to fail
        $this->assertCount(1, $errors = $this->readErrors(
            v::validator($rule = v::andX(v::success(), v::fail(123456))),
            'whatever'
        ));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(123456, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors(v::validator($rule), 'whatever')[0];
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));

        $this->assertFalse($rule->isStateless());
    }

    /**
     * Test validator.
     */
    public function testIfX()
    {
        $getTrue = function () {
            return true;
        };

        // all ok
        $this->assertEmpty($this->readErrors(
            v::validator(v::ifX($getTrue, v::success(), v::success())),
            'whatever'
        ));

        // with fail
        $this->assertNotEmpty($errors = $this->readErrors(
            v::validator($rule = v::ifX($getTrue, v::fail(123456), v::success())),
            'whatever'
        ));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(123456, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors(v::validator($rule), 'whatever')[0];
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));

        $this->assertFalse($rule->isStateless());
    }

    /**
     * Test validator.
     */
    public function testOrX()
    {
        // all ok
        $this->assertEmpty($this->readErrors(v::validator(v::orX(v::success(), v::success())), 'whatever'));
        $this->assertEmpty($this->readErrors(v::validator(v::orX(v::fail(), v::success())), 'whatever'));
        $this->assertEmpty($this->readErrors(v::validator(v::orX(v::success(), v::fail())), 'whatever'));

        // to fail (note we got error(s) from primary/first rule only).
        $this->assertCount(1, $errors = $this->readErrors(
            v::validator($rule = v::orX(v::fail(123456), v::fail(654321))),
            'whatever'
        ));
        $error = $errors[0];
        $this->assertEquals(null, $error->getParameterName());
        $this->assertEquals('whatever', $error->getParameterValue());
        $this->assertEquals(123456, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `` value is invalid.', $this->translator->translate($error));

        $rule->setParameterName('name');
        $error = $this->readErrors(v::validator($rule), 'whatever')[0];
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));

        $this->assertFalse($rule->isStateless());
    }

    /**
     * Test validator.
     */
    public function testValidateArray()
    {
        $input = [
            'bad1'      => 1234,
            'bad2'      => ['sub-field4' => 1234],
            'bad3'      => str_repeat('a', 300),
            'bad4'      => '',
            'good1'     => null,
            'good2'     => 'something goes here',
            'extra-bad' => 'whatever',
        ];

        // identical rules, just different ways to get same result
        $stringRule1 = v::ifX('is_null', v::success(), v::andX(v::isString(), v::stringLength(1, 255)));
        $stringRule2 = v::nullable(v::andX(v::isString(), v::stringLength(1, 255)));
        $rule        = v::arrayX([
            'bad1'  => $stringRule1,
            'bad2'  => $stringRule2,
            'bad3'  => $stringRule1,
            'bad4'  => $stringRule2,
            'bad5'  => v::required($stringRule1),
            'good1' => $stringRule2,
            'good2' => $stringRule1,
        ], v::fail(123456));

        $this->assertCount(6, $errors = $this->readErrors(v::validator($rule), $input));
        $this->assertEquals(
            ['bad1', MessageCodes::IS_STRING],
            [$errors[0]->getParameterName(), $errors[0]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad2', MessageCodes::IS_STRING],
            [$errors[1]->getParameterName(), $errors[1]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad3', MessageCodes::STRING_LENGTH],
            [$errors[2]->getParameterName(), $errors[2]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad4', MessageCodes::STRING_LENGTH],
            [$errors[3]->getParameterName(), $errors[3]->getMessageCode()]
        );
        $this->assertEquals(
            ['extra-bad', 123456],
            [$errors[4]->getParameterName(), $errors[4]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad5', MessageCodes::REQUIRED],
            [$errors[5]->getParameterName(), $errors[5]->getMessageCode()]
        );

        $this->assertFalse($rule->isStateless());
        // second time for receiving cached value
        $this->assertFalse($rule->isStateless());
    }

    /**
     * Test validator.
     */
    public function testValidateObject()
    {
        $input = (object)[
            'bad1'      => 1234,
            'bad2'      => ['sub-field4' => 1234],
            'bad3'      => str_repeat('a', 300),
            'bad4'      => '',
            'good1'     => null,
            'good2'     => 'something goes here',
            'extra-bad' => 'whatever',
        ];

        $stringRule1  = v::ifX('is_null', v::success(), v::andX(v::isString(), v::stringLength(1, 255)));
        $stringRule2  = v::nullable(v::andX(v::isString(), v::stringLength(1, 255)));
        $requiredRule = v::required($stringRule1);
        $rule         = v::objectX([
            'bad1'  => $stringRule1,
            'bad2'  => $stringRule2,
            'bad3'  => $stringRule1,
            'bad4'  => $stringRule2,
            'bad5'  => $requiredRule,
            'good1' => $stringRule2,
            'good2' => $stringRule1,
        ], v::fail(123456));

        $this->assertFalse($requiredRule->isStateless());

        $this->assertCount(6, $errors = $this->readErrors(v::validator($rule), $input));
        $this->assertEquals(
            ['bad1', MessageCodes::IS_STRING],
            [$errors[0]->getParameterName(), $errors[0]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad2', MessageCodes::IS_STRING],
            [$errors[1]->getParameterName(), $errors[1]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad3', MessageCodes::STRING_LENGTH],
            [$errors[2]->getParameterName(), $errors[2]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad4', MessageCodes::STRING_LENGTH],
            [$errors[3]->getParameterName(), $errors[3]->getMessageCode()]
        );
        $this->assertEquals(
            ['extra-bad', 123456],
            [$errors[4]->getParameterName(), $errors[4]->getMessageCode()]
        );
        $this->assertEquals(
            ['bad5', MessageCodes::REQUIRED],
            [$errors[5]->getParameterName(), $errors[5]->getMessageCode()]
        );
    }

    /**
     * Test combination `Or` with `required`.
     */
    public function testOrWithRequired()
    {
        $input = [
        ];

        $subRule1 = v::orX(v::success(), v::isRequired());
        $subRule2 = v::orX(v::fail(123456), v::isRequired());
        $subRule3 = v::orX(v::isRequired(), v::success());
        $subRule4 = v::orX(v::isRequired(), v::fail(123456));
        $rule     = v::arrayX([
            'field1'  => $subRule1,
            'field2'  => $subRule2,
            'field3'  => $subRule3,
            'field4'  => $subRule4,
        ], v::success());

        $this->assertCount(2, $errors = $this->readErrors(v::validator($rule), $input));
    }

    /**
     * Validate nested arrays.
     */
    public function testNestedArrays()
    {
        $input = [
            'key1' => [
                'key2' => 'field1',
                'key3' => 123,
                'key4' => 'field2'
            ],
        ];

        $rules = v::arrayX([
            'key1'  => v::arrayX([
                'key2' => v::isString(),
                'key3' => v::isString(),
                'key4' => v::isString(),
            ]),
        ]);

        $this->assertCount(1, $errors = $this->readErrors(v::validator($rules), $input));
        $error = $errors[0];
        $this->assertEquals('key3', $error->getParameterName());
        $this->assertEquals(123, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_STRING, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `key3` value should be a string.', $this->translator->translate($error));

        $this->assertTrue($rules->isStateless());
    }

    /**
     * Test auto naming in arrays could be disabled in favour of parent param name.
     */
    public function testDisableAutoParamNamesInArrays()
    {
        $input = [
            'key1' => [
                'key2' => 123,
            ],
        ];

        $rules = v::arrayX([
            'key1'  => v::arrayX([
                'key2' => v::isString(),
            ])->disableAutoParameterNames(),
        ])->enableAutoParameterNames();

        $this->assertCount(1, $errors = $this->readErrors(v::validator($rules), $input));
        $error = $errors[0];
        $this->assertEquals('key1', $error->getParameterName());
        $this->assertEquals(123, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_STRING, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `key1` value should be a string.', $this->translator->translate($error));
    }

    /**
     * Validate nested arrays.
     */
    public function testEachX()
    {
        $input = [
            'key1' => ['field1', 123, 'field2'],
        ];

        $eachRule = v::eachX(v::isString());
        $rules    = v::arrayX([
            'key1' => $eachRule,
        ]);

        $this->assertCount(1, $errors = $this->readErrors(v::validator($rules), $input));
        $error = $errors[0];
        $this->assertEquals('key1', $error->getParameterName());
        $this->assertEquals(123, $error->getParameterValue());
        $this->assertEquals(MessageCodes::IS_STRING, $error->getMessageCode());
        $this->assertEquals(null, $error->getMessageContext());
        $this->assertEquals('The `key1` value should be a string.', $this->translator->translate($error));
        $this->assertTrue($eachRule->isStateless());
    }

    /**
     * Emulate we validate data against database.
     */
    public function testEmulateValidationChecksDatabaseRecord()
    {
        $customErrorCode = 123456;
        $checkDatabase = function ($recordId) {
            // emulate database request
            $recordExists = $recordId < 10;
            return $recordExists;
        };
        $existsRule = v::callableX($checkDatabase, $customErrorCode);

        // no error
        $this->assertEmpty($this->readErrors(v::validator($existsRule), 5));

        // has error
        $this->assertNotEmpty($this->readErrors(v::validator($existsRule), 15));
    }

    /**
     * Check the field under should present only if all of the other specified fields given.
     */
    public function testRequiredWithAll()
    {
        // has error
        $enoughFields = [
            'field1' => 'whatever',
            'field2' => 'whatever',
            'field3' => 'not boolean',
        ];
        $rules = [
            'field3' => v::requiredWithAll($enoughFields, ['field1', 'field2'], v::isBool())
        ];
        $this->assertNotEmpty($this->readErrors(v::validator(v::arrayX($rules)), $enoughFields));

        // no error
        $notEnoughFields = [
            'field1' => 'whatever',
            'field3' => 'not boolean',
        ];

        $rules = [
            'field3' => v::requiredWithAll($notEnoughFields, ['field1', 'field2'], v::isBool())
        ];

        // no error
        $this->assertEmpty($this->readErrors(v::validator(v::arrayX($rules)), $notEnoughFields));
    }

    /**
     * Test validation for compare functions.
     */
    public function testCompareFunctions()
    {
        $rules = [
            'field1' => v::equals('1'),
            'field2' => v::notEquals('2'),
            'field3' => v::lessThan('3'),
            'field4' => v::lessOrEquals('4'),
            'field5' => v::moreThan('5'),
            'field6' => v::moreOrEquals('6'),
        ];

        // has error
        $invalidValues = [
            'field1' => '0',
            'field2' => '2',
            'field3' => '3',
            'field4' => '5',
            'field5' => '5',
            'field6' => '5',
        ];
        $this->assertCount(6, $this->readErrors(v::validator(v::arrayX($rules)), $invalidValues));

        // no error
        $notEnoughFields = [
            'field1' => '1',
            'field2' => '1',
            'field3' => '2',
            'field4' => '3',
            'field5' => '6',
            'field6' => '6',
        ];

        // no error
        $this->assertEmpty($this->readErrors(v::validator(v::arrayX($rules)), $notEnoughFields));
    }

    /**
     * Test validator.
     *
     * @see https://github.com/limoncello-php/validation/issues/10
     */
    public function testTranslateErrorWithNonScalarParameterValue()
    {
        $error = new Error('name', [['non scalar value']], MessageCodes::INVALID_VALUE, null);
        $this->assertEquals('The `name` value is invalid.', $this->translator->translate($error));
    }

    /**
     * Test sample app not failing on execution and is up to date with the lib.
     */
    public function testSampleAppNotFail()
    {
        $outputToConsole = false;
        (new Application($outputToConsole))->run();
    }

    /**
     * @param ValidatorInterface $validator
     * @param mixed              $input
     *
     * @return Error[]
     */
    private function readErrors(ValidatorInterface $validator, $input)
    {
        $errors = [];
        foreach ($validator->validate($input) as $error) {
            /** @var Error $error */
            $errors[] = $error;
        }

        return $errors;
    }
}

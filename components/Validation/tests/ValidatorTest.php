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

use Limoncello\Validation\ArrayValidator as vv;
use Limoncello\Validation\Contracts\Execution\ContextInterface;
use Limoncello\Validation\Rules as r;
use Limoncello\Validation\SingleValidator as v;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Validation
 */
class ValidatorTest extends TestCase
{
    /**
     * Test validator.
     */
    public function testBasicValidatorMethods(): void
    {
        $validator = v::validator(r::isBool(r::success())->setName('value')->enableCapture());

        $this->assertTrue($validator->validate(false));
        $this->assertEmpty($validator->getErrors());
        $this->assertNotEmpty($validator->getCaptures());

        $this->assertFalse($validator->validate('false'));
        $this->assertNotEmpty($validator->getErrors());
        $this->assertEmpty($validator->getCaptures());
    }
    /**
     * Test validator.
     */
    public function testBasicValidatorCaptureWithoutName(): void
    {
        $validator = v::validator(r::isBool(r::success())->enableCapture());

        $this->assertTrue($validator->validate(false));
        $this->assertEmpty($validator->getErrors());
        $this->assertEquals(['' => false], $validator->getCaptures());
    }

    /**
     * Test validator.
     */
    public function testBasicValidatorRules(): void
    {
        // allows either int > 5 OR `true`
        // it could be written shorter but we need some testing/coverage for the methods.
        $rule = r::orX(
            r::ifX([static::class, 'customCondition'], r::success(), r::fail()),
            r::andX(r::isBool(r::success()), r::equals(true))
        )->setName('value')->enableCapture();

        $validator = v::validator($rule);

        $this->assertTrue($validator->validate(6));
        $this->assertTrue($validator->validate(true));
        $this->assertFalse($validator->validate(5));
        $this->assertFalse($validator->validate(false));
    }

    /**
     * Test validation for array values.
     */
    public function testArrayValidator(): void
    {
        $validator = vv::validator([
            'value1' => r::isString(),
        ]);

        $this->assertTrue($validator->validate(['value1' => 'I am a string']));
        $this->assertFalse($validator->validate(['value1' => false]));
    }

    /**
     * @param mixed            $input
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function customCondition($input, ContextInterface $context): bool
    {
        assert($context);

        return is_int($input) === true && $input > 5;
    }
}

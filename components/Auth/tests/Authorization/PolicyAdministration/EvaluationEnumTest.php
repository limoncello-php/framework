<?php namespace Limoncello\Tests\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Auth
 */
class EvaluationEnumTest extends TestCase
{
    /**
     * Test to string conversion.
     */
    public function testToString()
    {
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::PERMIT));
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::DENY));
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::INDETERMINATE));
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::INDETERMINATE_PERMIT));
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::INDETERMINATE_DENY));
        $this->assertNotEmpty(EvaluationEnum::toString(EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT));
        $this->assertEquals('UNKNOWN', EvaluationEnum::toString(-1));
    }
}

<?php namespace Limoncello\Tests\Auth\Authorization\PolicyAdministration;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetMatchEnum;

/**
 * @package Limoncello\Tests\Auth
 */
class TargetMatchEnumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test to string conversion.
     */
    public function testToString()
    {
        $this->assertNotEmpty(TargetMatchEnum::toString(TargetMatchEnum::MATCH));
        $this->assertNotEmpty(TargetMatchEnum::toString(TargetMatchEnum::NOT_MATCH));
        $this->assertNotEmpty(TargetMatchEnum::toString(TargetMatchEnum::NO_TARGET));
        $this->assertNotEmpty(TargetMatchEnum::toString(TargetMatchEnum::INDETERMINATE));
        $this->assertEquals('UNKNOWN', TargetMatchEnum::toString(-1));
    }
}

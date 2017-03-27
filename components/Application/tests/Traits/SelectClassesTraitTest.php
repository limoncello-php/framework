<?php namespace Limoncello\Tests\Application\Traits;

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

use Limoncello\Application\Traits\SelectClassesTrait;
use Limoncello\Tests\Application\TestCase;

/**
 * @package Limoncello\Tests\Core
 */
class SelectClassesTraitTest extends TestCase
{
    use SelectClassesTrait;

    /**
     * Test selecting classes from files.
     */
    public function testSelectClasses1()
    {
        $selectClasses = [];
        foreach ($this->selectClasses(__FILE__, TestCase::class) as $selectClass) {
            $selectClasses[] = $selectClass;
        }
        $this->assertEquals([self::class], $selectClasses);
    }

    /**
     * Test selecting classes from files.
     */
    public function testSelectClasses2()
    {
        $selectClasses = [];
        foreach ($this->selectClasses(__FILE__, self::class) as $selectClass) {
            $selectClasses[] = $selectClass;
        }
        $this->assertEquals([self::class], $selectClasses);
    }

    /**
     * Test selecting from non existing path.
     */
    public function testSelectFromNonExistingPath()
    {
        $selectClasses = [];
        foreach ($this->selectClasses('_NON_EXISTING_PATH_', self::class) as $selectClass) {
            $selectClasses[] = $selectClass;
        }
        $this->assertEmpty($selectClasses);
    }
}

<?php namespace Limoncello\Tests\Common\Reflection;

/**
 * Copyright 2015-2018 info@neomerx.com
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
use DateTimeInterface;
use Exception;
use Limoncello\Contracts\Routing\RouterInterface;
use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Tests\Common\TestCase;
use PHPUnit\Framework\Test;

/**
 * @package Limoncello\Tests\Common
 */
class ClassIsTraitTest extends TestCase
{
    use ClassIsTrait;

    /**
     * Test class selections.
     *
     * @throws Exception
     */
    public function testClassMethods(): void
    {
        $this->assertTrue($this->classImplements(DateTime::class, DateTimeInterface::class));
        $this->assertFalse($this->classImplements(DateTime::class, RouterInterface::class));

        $this->assertTrue($this->classExtends(self::class, TestCase::class));
        $this->assertFalse($this->classExtends(self::class, DateTime::class));

        $this->assertTrue($this->classInherits(self::class, TestCase::class));
        $this->assertFalse($this->classInherits(self::class, DateTime::class));
        $this->assertTrue($this->classInherits(self::class, Test::class));
        $this->assertFalse($this->classInherits(self::class, DateTimeInterface::class));

        // test `selectClassImplements` (interface yes/no)
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClassImplements([self::class], Test::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClassImplements([self::class], DateTimeInterface::class))
        );

        // test `selectClassExtends` (class yes/no)
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClassExtends([self::class], TestCase::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClassExtends([self::class], DateTime::class))
        );

        // test `selectClassInherits` (interface yes/no, class yes/no)
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClassInherits([self::class], Test::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClassInherits([self::class], DateTimeInterface::class))
        );
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClassInherits([self::class], TestCase::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClassInherits([self::class], DateTime::class))
        );

        // test `selectClasses` (interface yes/no, class yes/no)
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClasses(__FILE__, Test::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClasses(__FILE__, DateTimeInterface::class))
        );
        $this->assertEquals(
            [self::class],
            iterator_to_array($this->selectClasses(__FILE__, TestCase::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array($this->selectClasses(__FILE__, DateTime::class))
        );
        $this->assertEquals(
            [],
            iterator_to_array(
                $this->selectClasses(__DIR__ . DIRECTORY_SEPARATOR . 'InvalidInclude.php', DateTime::class)
            )
        );
    }
}

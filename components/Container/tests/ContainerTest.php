<?php namespace Limoncello\Tests\Container;

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

use Limoncello\Container\Container;
use Limoncello\Container\Traits\HasContainerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Container
 */
class ContainerTest extends TestCase
{
    /**
     * Test `get` and `has` methods.
     */
    public function testContainer(): void
    {
        $container = new Container();

        $this->assertFalse($container->has(self::class));

        $container[self::class] = $this;

        $this->assertTrue($container->has(self::class));
        $this->assertSame($this, $container->get(self::class));
    }

    /**
     * @expectedException \Limoncello\Container\Exceptions\NotFoundException
     */
    public function testNotFound(): void
    {
        (new Container())->get('non-existing');
    }

    /**
     * Test HasContainerTrait.
     */
    public function testHasContainerTrait(): void
    {
        $container = new Container();
        $class     = new class
        {
            use HasContainerTrait {
                getContainer as public;
                setContainer as public;
                hasContainer as public;
            }
        };

        $this->assertFalse($class->hasContainer());

        $class->setContainer($container);
        $this->assertTrue($class->hasContainer());
        $this->assertEquals($container, $class->getContainer());
    }
}

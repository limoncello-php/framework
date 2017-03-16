<?php namespace Limoncello\Tests\Flute\Http\Query;

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

use Limoncello\Flute\Http\Query\FilterParameter;
use Limoncello\Flute\Http\Query\FilterParameterCollection;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class FilterParameterCollectionTest extends TestCase
{
    /**
     * Test collection.
     */
    public function testCollection()
    {
        $collection = new FilterParameterCollection();

        $collection->withAnd();
        $this->assertTrue($collection->isWithAnd());
        $this->assertFalse($collection->isWithOr());

        $collection->withOr();
        $this->assertFalse($collection->isWithAnd());
        $this->assertTrue($collection->isWithOr());

        $parameter = new FilterParameter('original', null, 'name', 'value');

        $this->assertCount(0, $collection);
        $this->assertCount(0, $collection->getArrayCopy());
        $collection->add($parameter);
        $this->assertCount(1, $collection);
        $this->assertCount(1, $collection->getArrayCopy());

        $this->assertTrue(isset($collection[0]));
        $this->assertNotNull($collection[0]);

        $serialized = $collection->serialize();
        $collection = new FilterParameterCollection();
        $collection->unserialize($serialized);
        $this->assertEquals('original', $collection[0]->getOriginalName());

        unset($collection[0]);
        $this->assertCount(0, $collection);

        $collection[0] = $parameter;
        $this->assertCount(1, $collection);
    }
}

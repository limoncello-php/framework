<?php namespace Limoncello\Tests\Flute\Models;

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

use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;
use Limoncello\Flute\Models\ModelSchemes;
use Limoncello\Flute\Models\ModelStorage;
use Limoncello\Tests\Flute\Data\Models\Post;

/**
 * @package Limoncello\Tests\Models
 */
class ModelStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test storage.
     */
    public function testStorage()
    {
        $storage = new ModelStorage($this->createSchemaStorage());

        $post1 = new Post();
        $post1->{Post::FIELD_ID}    = 1;
        $post1->{Post::FIELD_TITLE} = 'some title';

        $post2 = clone $post1;

        $this->assertSame($post1, $storage->register($post1));
        $this->assertSame($post1, $storage->register($post1));
        $this->assertSame($post1, $storage->register($post2));

        $this->assertTrue($storage->has(Post::class, 1));
        $this->assertFalse($storage->has(Post::class, 2));

        $this->assertSame($post1, $storage->get(Post::class, 1));
    }

    /**
     * @return ModelSchemesInterface
     */
    private function createSchemaStorage()
    {
        $storage = new ModelSchemes();
        $storage->registerClass(Post::class, Post::TABLE_NAME, Post::FIELD_ID, [], []);

        return $storage;
    }
}

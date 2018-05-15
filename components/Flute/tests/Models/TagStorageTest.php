<?php namespace Limoncello\Tests\Flute\Models;

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

use Exception;
use Limoncello\Flute\Models\TagStorage;
use Limoncello\Tests\Flute\Data\Models\Post;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class TagStorageTest extends TestCase
{
    /**
     * Test storage.
     *
     * @throws Exception
     */
    public function testStorage(): void
    {
        $storage = new TagStorage();

        $post1 = new Post();
        $post1->{Post::FIELD_ID}    = 1;
        $post1->{Post::FIELD_TITLE} = 'some title 1';

        $post2 = new Post();
        $post2->{Post::FIELD_ID}    = 2;
        $post2->{Post::FIELD_TITLE} = 'some title 2';

        $storage->register($post1, '');
        $storage->registerArray($post2, ['']);
        $storage->registerArray($post1, ['one']);

        $this->assertSame([$post1, $post2], array_values($storage->get('')));
        $this->assertSame([$post1], array_values($storage->get('one')));
        $this->assertSame([], array_values($storage->get('non-existing')));
    }
}

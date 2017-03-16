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

use Limoncello\Flute\Factory;
use Limoncello\Flute\Models\RelationshipStorage;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Models\User;

/**
 * @package Limoncello\Tests\Models
 */
class RelationshipStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test storage.
     */
    public function testStorage()
    {
        $storage = new RelationshipStorage(new Factory());

        $user     = new User();
        $post1    = new Post();
        $post2    = new Post();
        $comments = [
            new Comment(),
            new Comment(),
        ];

        $this->assertEquals(null, $storage->getRelationship($post1, Post::REL_COMMENTS));
        $this->assertEquals(null, $storage->getRelationship($post2, Post::REL_COMMENTS));
        $this->assertEquals(false, $storage->hasRelationship($post1, Post::REL_COMMENTS));

        $offset = 0;
        $size   = 2;
        $this->assertTrue(
            $storage->addToManyRelationship($post1, Post::REL_COMMENTS, $comments, false, $offset, $size)
        );
        $this->assertFalse(
            $storage->addToManyRelationship($post1, Post::REL_COMMENTS, $comments, false, $offset, $size)
        );

        $rel = $storage->getRelationship($post1, Post::REL_COMMENTS);
        $this->assertEquals($comments, $rel->getData());
        $this->assertTrue($rel->isCollection());
        $this->assertFalse($rel->hasMoreItems());
        $this->assertEquals($offset, $rel->getOffset());
        $this->assertEquals($size, $rel->getLimit());
        $this->assertFalse($storage->hasRelationship($post2, Post::REL_COMMENTS));

        $this->assertEquals(false, $storage->hasRelationship($post1, Post::REL_USER));

        $this->assertTrue($storage->addToOneRelationship($post1, Post::REL_USER, $user));
        $this->assertFalse($storage->addToOneRelationship($post1, Post::REL_USER, $user));

        $this->assertEquals(true, $storage->hasRelationship($post1, Post::REL_USER));
        $this->assertEquals($user, $storage->getRelationship($post1, Post::REL_USER)->getData());
        $rel = $storage->getRelationship($post1, Post::REL_COMMENTS);
        $this->assertEquals($comments, $rel->getData());
        $this->assertTrue($rel->isCollection());
        $this->assertFalse($rel->hasMoreItems());
        $this->assertEquals($offset, $rel->getOffset());
        $this->assertEquals($size, $rel->getLimit());
        $this->assertEquals(
            RelationshipStorage::RELATIONSHIP_TYPE_TO_ONE,
            $storage->getRelationshipType($post1, Post::REL_USER)
        );
        $this->assertEquals(
            RelationshipStorage::RELATIONSHIP_TYPE_TO_MANY,
            $storage->getRelationshipType($post1, Post::REL_COMMENTS)
        );
    }

    /**
     * Test `to-one` relationship can store `null` and existence of relationship could be checked.
     */
    public function testAddRelationToNull()
    {
        $storage = new RelationshipStorage(new Factory());
        $post1   = new Post();

        $this->assertFalse($storage->hasRelationship($post1, Post::REL_USER));
        $this->assertTrue($storage->addToOneRelationship($post1, Post::REL_USER, null));
        $this->assertEquals(null, $storage->getRelationship($post1, Post::REL_USER)->getData());
        $this->assertTrue($storage->hasRelationship($post1, Post::REL_USER));
    }
}

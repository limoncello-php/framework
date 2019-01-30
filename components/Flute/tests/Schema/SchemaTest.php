<?php namespace Limoncello\Tests\Flute\Schema;

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
use Limoncello\Container\Container;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Factory;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Schemas\PostSchema;
use Limoncello\Tests\Flute\TestCase;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class SchemaTest extends TestCase
{
    /**
     * @var PostSchema
     */
    private $schema;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory(new Container());
        $this->schema  = $this
            ->getJsonSchemas($this->factory, $this->getModelSchemas())
            ->getSchemaByResourceType(PostSchema::TYPE);
    }

    /**
     * Relationship test.
     *
     * @throws Exception
     */
    public function testEncodeNullToOneRelationship(): void
    {
        $post                          = new Post();
        $post->{Post::FIELD_ID}        = '1';
        $post->{Post::FIELD_ID_USER}   = null;
        $post->{Post::FIELD_ID_EDITOR} = null;
        $post->{Post::FIELD_ID_BOARD}  = null;

        $relationships = $this->schema->getRelationships($post);
        $this->assertNull($relationships[PostSchema::REL_USER][PostSchema::RELATIONSHIP_DATA]);
        $this->assertNull($relationships[PostSchema::REL_BOARD][PostSchema::RELATIONSHIP_DATA]);

        $this->assertTrue($this->schema->hasAttributeMapping(PostSchema::ATTR_TEXT));
        $this->assertTrue($this->schema->hasRelationshipMapping(PostSchema::REL_USER));
    }

    /**
     * Test how paging links are generated in relationships.
     */
    public function testPaginationInRelationships(): void
    {
        $comment = new Comment();
        $comment->{Comment::FIELD_ID} = '1';

        $post                       = new Post();
        $post->{Post::FIELD_ID}     = '2';
        $post->{Post::REL_COMMENTS} = $data = $this->factory->createPaginatedData([
            $comment,
            $comment,
            $comment,
        ])->markAsCollection()->setLimit(3)->markHasMoreItems();

        // test with paging when it's enough place for the previous link. It starts from offset 4 with page size 3.
        $data->setOffset(4);
        $description = $this->schema->getRelationships($post);
        $prev = $this->getRelationshipLinkFromDescription($description, PostSchema::REL_COMMENTS, LinkInterface::PREV);
        $next = $this->getRelationshipLinkFromDescription($description, PostSchema::REL_COMMENTS, LinkInterface::NEXT);
        $this->assertEquals(
            '/posts/2/relationships/comments-relationship?offset=1&limit=3',
            $prev->getStringRepresentation('')
        );
        $this->assertEquals(
            '/posts/2/relationships/comments-relationship?offset=7&limit=3',
            $next->getStringRepresentation('')
        );

        // test with shifted paging when it starts from offset 1 with page size 3.
        $data->setOffset(1);
        $description = $this->schema->getRelationships($post);
        $prev = $this->getRelationshipLinkFromDescription($description, PostSchema::REL_COMMENTS, LinkInterface::PREV);
        $next = $this->getRelationshipLinkFromDescription($description, PostSchema::REL_COMMENTS, LinkInterface::NEXT);
        $this->assertEquals(
            '/posts/2/relationships/comments-relationship?offset=0&limit=1',
            $prev->getStringRepresentation('')
        );
        $this->assertEquals(
            '/posts/2/relationships/comments-relationship?offset=4&limit=3',
            $next->getStringRepresentation('')
        );
    }

    /**
     * @param array  $description
     * @param string $relationshipName
     * @param string $linkName
     *
     * @return LinkInterface
     */
    private function getRelationshipLinkFromDescription(
        array $description,
        string $relationshipName,
        string $linkName
    ): LinkInterface {
        $this->assertTrue(isset($description[$relationshipName][PostSchema::RELATIONSHIP_LINKS]));
        $links = $description[$relationshipName][PostSchema::RELATIONSHIP_LINKS];

        $this->assertArrayHasKey($linkName, $links);
        [$linkName => $link] = $links;
        $this->assertTrue($link instanceof LinkInterface);

        return $link;
    }
}

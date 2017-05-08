<?php namespace Limoncello\Tests\Flute\Schema;

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
use Limoncello\Flute\Factory as FluteFactory;
use Limoncello\Tests\Flute\Data\Models\Post;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\TestCase;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Factories\Factory;

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
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $factory      = new FluteFactory(new Container());
        $modelSchemes = $this->getModelSchemes();
        $this->schema = new PostSchema(new Factory(), $this->getJsonSchemes($factory, $modelSchemes), $modelSchemes);
    }

    /**
     * Relationship test.
     */
    public function testEncodeNullToOneRelationship()
    {
        $post = new Post();
        $post->{Post::FIELD_ID}  = '1';
        $post->{Post::FIELD_ID_USER}   = null;
        $post->{Post::FIELD_ID_EDITOR} = null;
        $post->{Post::FIELD_ID_BOARD}  = null;

        $relationships = $this->schema->getRelationships($post, true, []);
        $this->assertNull($relationships[PostSchema::REL_USER][DocumentInterface::KEYWORD_DATA]);
        $this->assertNull($relationships[PostSchema::REL_BOARD][DocumentInterface::KEYWORD_DATA]);

        $this->assertTrue($this->schema->hasAttributeMapping(PostSchema::ATTR_TEXT));
        $this->assertTrue($this->schema->hasRelationshipMapping(PostSchema::REL_USER));
    }
}

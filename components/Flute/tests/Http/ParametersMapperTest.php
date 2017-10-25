<?php namespace Limoncello\Tests\Flute\Http;

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

use Generator;
use Limoncello\Container\Container;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Http\Query\RelationshipInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Factory;
use Limoncello\Flute\Http\Query\ParametersMapper;
use Limoncello\Flute\Http\Query\SortParameter;
use Limoncello\Tests\Flute\Data\Models\Board;
use Limoncello\Tests\Flute\Data\Schemes\BoardSchema;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemes\EmotionSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class ParametersMapperTest extends TestCase
{
    /**
     * Test query.
     */
    public function testGetFilters(): void
    {
        $filterParameters = [
            BoardSchema::RESOURCE_ID                              => [
                'in' => ['10', '11'],
            ],
            BoardSchema::ATTR_TITLE                               => [
                'like' => ['like%'],
            ],
            BoardSchema::REL_POSTS                                => [
                'eq' => ['1'],
            ],
            BoardSchema::REL_POSTS . '.' . PostSchema::ATTR_TITLE => [
                'not-like' => ['not_like%'],
            ],
        ];

        $mapper = $this->createMapper(BoardSchema::class)->withFilters($filterParameters);

        /** @var FilterParameterInterface[] $filters */
        $filters = $this->iterableToArray($mapper->getMappedFilters());
        $this->assertCount(4, $filters);

        $filter = $filters[0];
        $this->assertNull($filter->getRelationship());
        $this->assertNotNull($filter->getAttribute());
        $this->assertEquals(BoardSchema::RESOURCE_ID, $filter->getAttribute()->getNameInScheme());
        $this->assertEquals(BoardSchema::TYPE, $filter->getAttribute()->getScheme()::TYPE);
        $this->assertEquals([
            FilterParameterInterface::OPERATION_IN => ['10', '11'],
        ], $this->iterableToArray($filter->getOperationsWithArguments()));

        $filter = $filters[1];
        $this->assertNull($filter->getRelationship());
        $this->assertNotNull($filter->getAttribute());
        $this->assertEquals(BoardSchema::ATTR_TITLE, $filter->getAttribute()->getNameInScheme());
        $this->assertEquals(BoardSchema::TYPE, $filter->getAttribute()->getScheme()::TYPE);
        $this->assertEquals([
            FilterParameterInterface::OPERATION_LIKE => ['like%'],
        ], $this->iterableToArray($filter->getOperationsWithArguments()));

        $filter = $filters[2];
        $this->assertNotNull($filter->getRelationship());
        $this->assertNotNull($filter->getAttribute());
        $this->assertEquals(BoardSchema::TYPE, $filter->getRelationship()->getFromSchema()::TYPE);
        $this->assertEquals(PostSchema::TYPE, $filter->getRelationship()->getToSchema()::TYPE);
        $this->assertEquals(BoardSchema::REL_POSTS, $filter->getRelationship()->getNameInScheme());
        $this->assertEquals(Board::REL_POSTS, $filter->getRelationship()->getNameInModel());
        $this->assertEquals(PostSchema::RESOURCE_ID, $filter->getAttribute()->getNameInScheme());
        $this->assertEquals(PostSchema::TYPE, $filter->getAttribute()->getScheme()::TYPE);
        $this->assertEquals([
            FilterParameterInterface::OPERATION_EQUALS => ['1'],
        ], $this->iterableToArray($filter->getOperationsWithArguments()));

        $filter = $filters[3];
        $this->assertNotNull($filter->getRelationship());
        $this->assertNotNull($filter->getAttribute());
        $this->assertEquals(BoardSchema::TYPE, $filter->getRelationship()->getFromSchema()::TYPE);
        $this->assertEquals(PostSchema::TYPE, $filter->getRelationship()->getToSchema()::TYPE);
        $this->assertEquals(BoardSchema::REL_POSTS, $filter->getRelationship()->getNameInScheme());
        $this->assertEquals(Board::REL_POSTS, $filter->getRelationship()->getNameInModel());
        $this->assertEquals(PostSchema::ATTR_TITLE, $filter->getAttribute()->getNameInScheme());
        $this->assertEquals(PostSchema::TYPE, $filter->getAttribute()->getScheme()::TYPE);
        $this->assertEquals([
            FilterParameterInterface::OPERATION_NOT_LIKE => ['not_like%'],
        ], $this->iterableToArray($filter->getOperationsWithArguments()));
    }

    /**
     * Test query.
     */
    public function testGetSorts(): void
    {
        $sortParameters = [
            BoardSchema::RESOURCE_ID                              => true,
            BoardSchema::ATTR_TITLE                               => false,
            BoardSchema::REL_POSTS                                => true,
            BoardSchema::REL_POSTS . '.' . PostSchema::ATTR_TITLE => false,
        ];

        $mapper = $this->createMapper(BoardSchema::class)->withSorts($sortParameters);
        /** @var SortParameter[] $sorts */
        $sorts = $this->iterableToArray($mapper->getMappedSorts());
        $this->assertCount(4, $sorts);

        $sort = $sorts[0];
        $this->assertNull($sort->getRelationship());
        $this->assertNotNull($sort->getAttribute());
        $this->assertEquals(BoardSchema::RESOURCE_ID, $sort->getAttribute()->getNameInScheme());
        $this->assertEquals(BoardSchema::TYPE, $sort->getAttribute()->getScheme()::TYPE);
        $this->assertTrue($sort->isAsc());

        $sort = $sorts[1];
        $this->assertNull($sort->getRelationship());
        $this->assertNotNull($sort->getAttribute());
        $this->assertEquals(BoardSchema::ATTR_TITLE, $sort->getAttribute()->getNameInScheme());
        $this->assertEquals(BoardSchema::TYPE, $sort->getAttribute()->getScheme()::TYPE);
        $this->assertTrue($sort->isDesc());

        $sort = $sorts[2];
        $this->assertNotNull($sort->getRelationship());
        $this->assertNotNull($sort->getAttribute());
        $this->assertEquals(BoardSchema::TYPE, $sort->getRelationship()->getFromSchema()::TYPE);
        $this->assertEquals(PostSchema::TYPE, $sort->getRelationship()->getToSchema()::TYPE);
        $this->assertEquals(BoardSchema::REL_POSTS, $sort->getRelationship()->getNameInScheme());
        $this->assertEquals(Board::REL_POSTS, $sort->getRelationship()->getNameInModel());
        $this->assertEquals(PostSchema::RESOURCE_ID, $sort->getAttribute()->getNameInScheme());
        $this->assertEquals(PostSchema::TYPE, $sort->getAttribute()->getScheme()::TYPE);
        $this->assertTrue($sort->isAsc());

        $sort = $sorts[3];
        $this->assertNotNull($sort->getRelationship());
        $this->assertNotNull($sort->getAttribute());
        $this->assertEquals(BoardSchema::TYPE, $sort->getRelationship()->getFromSchema()::TYPE);
        $this->assertEquals(PostSchema::TYPE, $sort->getRelationship()->getToSchema()::TYPE);
        $this->assertEquals(BoardSchema::REL_POSTS, $sort->getRelationship()->getNameInScheme());
        $this->assertEquals(Board::REL_POSTS, $sort->getRelationship()->getNameInModel());
        $this->assertEquals(PostSchema::ATTR_TITLE, $sort->getAttribute()->getNameInScheme());
        $this->assertEquals(PostSchema::TYPE, $sort->getAttribute()->getScheme()::TYPE);
        $this->assertTrue($sort->isDesc());
    }

    /**
     * Test query.
     */
    public function testIncludes(): void
    {
        $path1 = [BoardSchema::REL_POSTS];
        $path2 = [BoardSchema::REL_POSTS, PostSchema::REL_COMMENTS];
        $path3 = [BoardSchema::REL_POSTS, PostSchema::REL_COMMENTS, CommentSchema::REL_EMOTIONS];

        $includeParameters = [$path1, $path2, $path3];

        $mapper = $this->createMapper(BoardSchema::class)->withIncludes($includeParameters);

        $includes = $this->iterableToArray($mapper->getMappedIncludes());
        $this->assertCount(3, $includes);

        /** @var RelationshipInterface[] $include */
        $include = $includes[0];
        $this->assertCount(1, $include);
        $this->assertEquals(BoardSchema::REL_POSTS, $include[0]->getNameInScheme());
        $this->assertEquals(BoardSchema::TYPE, $include[0]->getFromSchema()::TYPE);
        $this->assertEquals(PostSchema::TYPE, $include[0]->getToSchema()::TYPE);

        $include = $includes[1];
        $this->assertCount(2, $include);
        $this->assertEquals(PostSchema::REL_COMMENTS, $include[1]->getNameInScheme());
        $this->assertEquals(PostSchema::TYPE, $include[1]->getFromSchema()::TYPE);
        $this->assertEquals(CommentSchema::TYPE, $include[1]->getToSchema()::TYPE);

        $include = $includes[2];
        $this->assertCount(3, $include);
        $this->assertEquals(CommentSchema::REL_EMOTIONS, $include[2]->getNameInScheme());
        $this->assertEquals(CommentSchema::TYPE, $include[2]->getFromSchema()::TYPE);
        $this->assertEquals(EmotionSchema::TYPE, $include[2]->getToSchema()::TYPE);
    }

    /**
     * @param string $schemaClass
     *
     * @return ParametersMapperInterface
     */
    private function createMapper(string $schemaClass): ParametersMapperInterface
    {
        assert(in_array(SchemaInterface::class, class_implements($schemaClass)));
        /** @var SchemaInterface $schemaClass */

        $schemes = $this->getJsonSchemes(new Factory(new Container()), $this->getModelSchemes());

        return (new ParametersMapper($schemes))->selectRootSchemeByResourceType($schemaClass::TYPE);
    }

    /**
     * @param iterable $iterable
     *
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        $result = [];

        foreach ($iterable as $key => $value) {
            $result[$key] = $value instanceof Generator ? $this->iterableToArray($value) : $value;
        }

        return $result;
    }
}

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
use Limoncello\Flute\Adapters\PaginationStrategy;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Http\Query\QueryParser;
use Limoncello\Tests\Flute\Data\Schemes\BoardSchema;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class QueryParserTest extends TestCase
{
    /**
     * Test query.
     */
    public function testEmptyQueryParams(): void
    {
        $queryParameters = [];

        $parser = $this->createParser($queryParameters);

        $this->assertTrue($parser->areFiltersWithAnd());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidEmptyFilter1(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => '',
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertTrue($parser->areFiltersWithAnd());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidEmptyFilter2(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [],
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertTrue($parser->areFiltersWithAnd());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidFilterTooManyRootItems(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'or'  => [
                    BoardSchema::RESOURCE_ID => [
                        'in' => '10,11',
                    ],
                ],
                'xxx' => 'only one top-level element is allowed if AND/OR is used',
            ],
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertTrue($parser->areFiltersWithAnd());
    }

    /**
     * Test query.
     */
    public function testTopLevelConditionWithOr(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'or' => [
                    BoardSchema::RESOURCE_ID => [
                        'in' => '10,11',
                    ],
                ],
            ],
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertFalse($parser->areFiltersWithAnd());

        $filters = $this->iterableToArray($parser->getFilters());
        $this->assertEquals([
            BoardSchema::RESOURCE_ID => [
                'in' => ['10', '11'],
            ],
        ], $filters);
    }

    /**
     * Test query.
     */
    public function testGetFilters(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                BoardSchema::RESOURCE_ID                              => [
                    'in' => '10,11',
                ],
                BoardSchema::ATTR_TITLE                               => [
                    'like' => 'like%',
                ],
                BoardSchema::REL_POSTS                                => [
                    'eq' => '1',
                ],
                BoardSchema::REL_POSTS . '.' . PostSchema::ATTR_TITLE => [
                    'not-like' => 'not_like%',
                ],
            ],
        ];

        $parser  = $this->createParser($queryParameters);
        $filters = $this->iterableToArray($parser->getFilters());
        $this->assertEquals([
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
        ], $filters);

        $this->assertTrue($parser->areFiltersWithAnd());
    }

    /**
     * Test query.
     */
    public function testGetSorts(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_SORT =>
                BoardSchema::RESOURCE_ID . ',' .
                '-' . BoardSchema::ATTR_TITLE . ',' .
                '+' . BoardSchema::REL_POSTS . ',' .
                '-' . BoardSchema::REL_POSTS . '.' . PostSchema::ATTR_TITLE,
        ];

        $parser = $this->createParser($queryParameters);
        $sorts  = $this->iterableToArray($parser->getSorts());
        $this->assertEquals([
            BoardSchema::RESOURCE_ID                              => true,
            BoardSchema::ATTR_TITLE                               => false,
            BoardSchema::REL_POSTS                                => true,
            BoardSchema::REL_POSTS . '.' . PostSchema::ATTR_TITLE => false,
        ], $sorts);
    }

    /**
     * Test query.
     */
    public function testIncludes(): void
    {
        $path1 = BoardSchema::REL_POSTS;
        $path2 = BoardSchema::REL_POSTS . '.' . PostSchema::REL_COMMENTS;
        $path3 = BoardSchema::REL_POSTS . '.' . PostSchema::REL_COMMENTS . '.' . CommentSchema::REL_EMOTIONS;

        $queryParameters = [
            QueryParserInterface::PARAM_INCLUDE => "$path1,$path2,$path3",
        ];

        $parser = $this->createParser($queryParameters);

        $includes = $this->iterableToArray($parser->getIncludes());
        $this->assertEquals([
            $path1 => [BoardSchema::REL_POSTS],
            $path2 => [BoardSchema::REL_POSTS, PostSchema::REL_COMMENTS],
            $path3 => [BoardSchema::REL_POSTS, PostSchema::REL_COMMENTS, CommentSchema::REL_EMOTIONS],
        ], $includes);
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testGetFiltersWithInvalidValues1(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                BoardSchema::RESOURCE_ID => 'cannot be string',
            ],
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getFilters());
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testGetFiltersWithInvalidValues2(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                BoardSchema::RESOURCE_ID => [
                    'in' => ['must be string but not array'],
                ],
            ],
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getFilters());
    }

    /**
     * Test create EncodingParameters.
     */
    public function testCreateEncodingParametersForInputGenerator(): void
    {
        $generator = function () {
            yield 'some_value';
        };

        $queryParameters = [
            QueryParserInterface::PARAM_FIELDS => [
                'posts' => $generator(),
            ],
        ];

        $parameters = $this->createParser($queryParameters)->createEncodingParameters();
        $this->assertEquals(['some_value'], $parameters->getFieldSet('posts'));
    }

    /**
     * @param array $queryParameters
     *
     * @return QueryParserInterface
     */
    private function createParser(array $queryParameters): QueryParserInterface
    {
        $parser = (new QueryParser(new PaginationStrategy(20, 100)))
            ->withAllAllowedFilterFields()
            ->withAllAllowedSortFields()
            ->withAllAllowedIncludePaths()
            ->parse($queryParameters);

        return $parser;
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

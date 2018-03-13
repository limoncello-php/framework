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

use Generator;
use Limoncello\Container\Container;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryValidatingParserInterface;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiQueryRulesSerializer;
use Limoncello\Flute\Validation\JsonApi\QueryParser;
use Limoncello\Tests\Flute\Data\L10n\FormatterFactory;
use Limoncello\Tests\Flute\Data\Models\Comment;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema;
use Limoncello\Tests\Flute\Data\Validation\JsonQueries\AllowEverythingRules;
use Limoncello\Tests\Flute\Data\Validation\JsonQueries\CommentsIndexRules;
use Limoncello\Tests\Flute\TestCase;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\BlockSerializer;
use Limoncello\Validation\Execution\ContextStorage;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Tests\Flute
 */
class QueryParserTest extends TestCase
{
    /**
     * Parser test.
     */
    public function testParsePaging(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        // check both in the input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_PAGE => [
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_OFFSET => '10',
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_LIMIT  => '20',
            ],
        ]);
        $this->assertSame(10, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());
        $this->assertTrue($parser->hasPaging());

        // check no offset in the input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_PAGE => [
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_LIMIT => '20',
            ],
        ]);
        $this->assertSame(0, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());

        // check no offset & limit in the input
        $parser->parse([]);
        $this->assertSame(0, $parser->getPagingOffset());
        $this->assertSame(30, $parser->getPagingLimit());
        $this->assertFalse($parser->hasPaging());
    }

    /**
     * Parser test.
     */
    public function testParseInclude(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $relUser = Comment::REL_USER;
        $relPost = Comment::REL_POST;

        // check with valid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_INCLUDE => "$relUser,$relPost",
        ]);
        $includes = $this->iterableToArray($parser->getIncludes());

        // that's the format of parsed path: 'some.long.path' => ['some', 'long', 'path']
        $this->assertEquals([
            $relUser => [$relUser],
            $relPost => [$relPost],
        ], $includes);

        // check with invalid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_INCLUDE => "$relUser,foo,$relPost,boo",
        ]);

        $exception = null;
        try {
            $this->iterableToArray($parser->getIncludes());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(2, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_INCLUDE],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_INCLUDE],
            $errors[1]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseSort(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $fieldText  = Comment::FIELD_TEXT;
        $fieldFloat = Comment::FIELD_FLOAT;

        // check with valid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_SORT => "+$fieldText,-$fieldFloat",
        ]);
        $sorts = $this->iterableToArray($parser->getSorts());
        $this->assertTrue($parser->hasSorts());

        $this->assertEquals([
            $fieldText  => true,
            $fieldFloat => false,
        ], $sorts);

        // check with invalid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_SORT => "-$fieldText,foo,$fieldFloat,boo",
        ]);

        $exception = null;
        try {
            $this->iterableToArray($parser->getSorts());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(2, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_SORT],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_SORT],
            $errors[1]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseFieldSets(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText  = CommentSchema::ATTR_TEXT;
        $commentUser  = CommentSchema::REL_USER;
        $commentPost  = CommentSchema::REL_POST;
        $postTitle    = PostSchema::ATTR_TITLE;
        $postUser     = PostSchema::REL_USER;
        $postComments = PostSchema::REL_COMMENTS;

        // check with valid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FIELDS => [
                CommentSchema::TYPE => "$commentText,$commentUser,$commentPost",
                PostSchema::TYPE    => "$postTitle,$postUser,$postComments",
            ],
        ]);
        $fieldSets = $this->iterableToArray($parser->getFields());

        $this->assertEquals([
            CommentSchema::TYPE => [$commentText, $commentUser, $commentPost],
            PostSchema::TYPE    => [$postTitle, $postUser, $postComments],
        ], $fieldSets);

        // check with invalid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FIELDS => [
                CommentSchema::TYPE => "$commentText,foo,$commentUser,$commentPost",
                PostSchema::TYPE    => "$postTitle,$postUser,boo,$postComments",
                'UnknownType'       => 'whatever',
            ],
        ]);

        $exception = null;
        try {
            $this->iterableToArray($parser->getFields());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(3, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_FIELDS],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_FIELDS],
            $errors[1]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_FIELDS],
            $errors[2]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseFilters(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText = CommentSchema::ATTR_TEXT;
        $commentInt  = CommentSchema::ATTR_INT;
        $commentBool = CommentSchema::ATTR_BOOL;

        // check with valid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                $commentText => ['like' => '%foo%', 'not_in' => 'food,foolish'],
                $commentInt  => ['gte' => '3', 'lte' => '9'],
                $commentBool => ['eq' => 'true'],
            ],
        ]);
        $this->assertTrue($parser->hasFilters());
        $filters = $this->iterableToArray($parser->getFilters());

        $this->assertSame([
            $commentText => ['like' => ['%foo%'], 'not_in' => ['food', 'foolish']],
            $commentInt  => ['gte' => [3], 'lte' => [9]],
            $commentBool => ['eq' => [true]],
        ], $filters);

        // check with invalid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                $commentText => ['like' => '%', 'not_in' => 'f,g'],
                $commentInt  => ['gte' => '0', 'lte' => '11'],
            ],
        ]);

        $exception = null;
        try {
            $this->iterableToArray($parser->getFilters());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(5, $errors = $exception->getErrors());
        foreach ($errors->getArrayCopy() as $error) {
            $this->assertEquals(
                [ErrorInterface::SOURCE_PARAMETER => JsonApiQueryValidatingParserInterface::PARAM_FILTER],
                $error->getSource()
            );
        }
    }

    /**
     * Parser test.
     */
    public function testParseEmptyFilterArguments(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText = CommentSchema::ATTR_TEXT;

        // check with valid input
        $parser->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                $commentText => ['not_in' => ''],
            ],
        ]);
        $filters = $this->iterableToArray($parser->getFilters());

        $this->assertSame([
            $commentText => ['not_in' => []],
        ], $filters);
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testGetFiltersWithInvalidValues1(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                CommentSchema::RESOURCE_ID => 'cannot be string',
            ],
        ];

        $this->iterableToArray($this->createParser(CommentsIndexRules::class)->parse($queryParameters)->getFilters());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testGetFiltersWithInvalidValues2(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                'UnknownField' => ['gte' => '0'],
            ],
        ];

        $this->iterableToArray($this->createParser(CommentsIndexRules::class)->parse($queryParameters)->getFilters());
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testGetFiltersWithInvalidValues3(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                CommentSchema::RESOURCE_ID => [
                    'in' => ['must be string but not array'],
                ],
            ],
        ];

        $this->iterableToArray($this->createParser(CommentsIndexRules::class)->parse($queryParameters)->getFilters());
    }

    /**
     * Test query.
     *
     * @return void
     */
    public function testEmptyQueryParams(): void
    {
        $queryParameters = [];

        $parser = $this->createParser(CommentsIndexRules::class);

        $this->assertTrue($parser->parse($queryParameters)->areFiltersWithAnd());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidEmptyFilter1(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => '',
        ];

        $this->createParser(CommentsIndexRules::class)->parse($queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidEmptyFilter2(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [],
        ];

        $this->createParser(CommentsIndexRules::class)->parse($queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidFilterTooManyRootItems(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                'or'  => [
                    CommentSchema::RESOURCE_ID => [
                        'in' => '3,5,7',
                    ],
                ],
                'xxx' => 'only one top-level element is allowed if AND/OR is used',
            ],
        ];

        $this->createParser(CommentsIndexRules::class)->parse($queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     *
     * @return void
     */
    public function testTopLevelConditionWithOr(): void
    {
        $queryParameters = [
            JsonApiQueryValidatingParserInterface::PARAM_FILTER => [
                'or' => [
                    CommentSchema::RESOURCE_ID => [
                        'in' => '3,5,7',
                    ],
                ],
            ],
        ];

        $parser = $this->createParser(CommentsIndexRules::class)->parse($queryParameters);

        $this->assertFalse($parser->areFiltersWithAnd());

        $filters = $this->iterableToArray($parser->getFilters());
        $this->assertSame([
            CommentSchema::RESOURCE_ID => [
                'in' => [3, 5, 7],
            ],
        ], $filters);
    }

    /**
     * Test validator that allows any input data.
     *
     * @return void
     */
    public function testAllowAnyInput(): void
    {
        $relUser    = Comment::REL_USER;
        $relPost    = Comment::REL_POST;
        $fieldText  = Comment::FIELD_TEXT;
        $fieldFloat = Comment::FIELD_FLOAT;
        $fieldInt   = Comment::FIELD_INT;

        $parser = $this->createParser(AllowEverythingRules::class)->parse([
            JsonApiQueryValidatingParserInterface::PARAM_FILTER  => [
                $fieldText => ['like' => '%foo%', 'not_in' => 'food,foolish'],
                $fieldInt  => ['gte' => '3', 'lte' => '9'],
            ],
            JsonApiQueryValidatingParserInterface::PARAM_FIELDS  => [
                CommentSchema::TYPE => "$fieldText,$relUser,$relPost",
            ],
            JsonApiQueryValidatingParserInterface::PARAM_SORT    => "+$fieldText,-$fieldFloat",
            JsonApiQueryValidatingParserInterface::PARAM_INCLUDE => "$relUser,$relPost",
            JsonApiQueryValidatingParserInterface::PARAM_PAGE    => [
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_OFFSET => '10',
                JsonApiQueryValidatingParserInterface::PARAM_PAGING_LIMIT  => '20',
            ],
        ]);

        $this->assertSame([
            $fieldText => ['like' => ['%foo%'], 'not_in' => ['food', 'foolish']],
            $fieldInt  => ['gte' => ['3'], 'lte' => ['9']],
        ], $this->iterableToArray($parser->getFilters()));
        $this->assertSame([
            CommentSchema::TYPE => [$fieldText, $relUser, $relPost],
        ], $this->iterableToArray($parser->getFields()));
        $this->assertSame([
            $fieldText  => true,
            $fieldFloat => false,
        ], $this->iterableToArray($parser->getSorts()));
        $this->assertSame([
            $relUser => [$relUser],
            $relPost => [$relPost],
        ], $this->iterableToArray($parser->getIncludes()));
        $this->assertSame(10, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());
    }

    /**
     *
     * @param string $ruleClass
     *
     * @return JsonApiQueryValidatingParserInterface
     */
    private function createParser(string $ruleClass): JsonApiQueryValidatingParserInterface
    {
        $ruleClasses = [
            CommentsIndexRules::class,
            AllowEverythingRules::class,
        ];

        $serializer = new JsonApiQueryRulesSerializer(new BlockSerializer());
        foreach ($ruleClasses as $class) {
            $serializer->addRulesFromClass($class);
        }
        $serializedData = $serializer->getData();

        $container                                   = new Container();
        $container[FormatterFactoryInterface::class] = $formatterFactory = new FormatterFactory();

        $blocks = JsonApiQueryRulesSerializer::readBlocks($serializedData);
        $parser = new QueryParser(
            $ruleClass,
            JsonApiQueryRulesSerializer::class,
            $serializedData,
            new ContextStorage($blocks, $container),
            new CaptureAggregator(),
            new ErrorAggregator(),
            new JsonApiErrorCollection($container),
            $formatterFactory
        );

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

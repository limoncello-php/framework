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
use Limoncello\Flute\Http\Query\BaseQueryParser;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class BaseQueryParserTest extends TestCase
{
    /**
     * Test query.
     */
    public function testEmptyQueryParams(): void
    {
        $queryParameters = [];

        $parser = $this->createParser($queryParameters);

        $this->assertEquals([], $this->iterableToArray($parser->getIncludes()));
        $this->assertEquals([], $this->iterableToArray($parser->getFields()));
    }

    /**
     * Test query.
     */
    public function testIncludes(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => 'comments,   comments.author',
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertEquals([
            'comments'        => ['comments'],
            'comments.author' => ['comments', 'author'],
        ], $this->iterableToArray($parser->getIncludes()));
    }

    /**
     * Test query.
     */
    public function testFields(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_FIELDS => [
                'articles' => 'title,     body      ',
                'people'   => 'name',
            ],
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertEquals([
            'articles' => ['title', 'body'],
            'people'   => ['name'],
        ], $this->iterableToArray($parser->getFields()));
    }

    /**
     * Test query.
     */
    public function testSorts(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_SORT => '-created,title',
        ];

        $parser = $this->createParser($queryParameters);

        $this->assertEquals([
            'created' => false,
            'title'   => true,
        ], $this->iterableToArray($parser->getSorts()));
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidIncludesEmptyValue(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => 'comments,      ,comments.author',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidIncludesNotString1(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => ['not string'],
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidIncludesNotString2(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => null,
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidIncludesEmptyString1(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidIncludesEmptyString2(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_INCLUDE => '  ',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getIncludes());
    }

    /**
     * Test query.
     *
     * @expectedException \Neomerx\JsonApi\Exceptions\JsonApiException
     */
    public function testInvalidFields(): void
    {
        $queryParameters = [
            BaseQueryParser::PARAM_FIELDS => 'not array',
        ];

        $this->iterableToArray($this->createParser($queryParameters)->getFields());
    }

    /**
     * @param array $queryParameters
     *
     * @return BaseQueryParser
     */
    private function createParser(array $queryParameters): BaseQueryParser
    {
        return new BaseQueryParser($queryParameters);
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

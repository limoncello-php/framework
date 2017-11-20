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

use DateTimeImmutable;
use Generator;
use Limoncello\Container\Container;
use Limoncello\Flute\Adapters\PaginationStrategy;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Http\Query\QueryValidator;
use Limoncello\Flute\Types\DateBaseType;
use Limoncello\Flute\Validation\Form\Execution\FormRuleSerializer;
use Limoncello\Tests\Flute\Data\Validation\AppRules as v;
use Limoncello\Tests\Flute\TestCase;

/**
 * @package Limoncello\Tests\Flute
 */
class QueryValidatorTest extends TestCase
{
    /**
     * Test query.
     */
    public function testQueryParams(): void
    {
        $now             = new DateTimeImmutable('2001-02-03 04:05:06');
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'bool_attr'      => [
                    'eq' => 'true',
                ],
                'date_time_attr' => [
                    'eq' => $now->format(DateBaseType::JSON_API_FORMAT),
                ],
            ],
        ];
        $rules           = [
            'bool_attr'      => v::isString(v::required(v::stringToBool())),
            'date_time_attr' => v::isString(v::stringToDateTime(DateBaseType::JSON_API_FORMAT)),
        ];

        $parser = $this->createParser($rules, $queryParameters);

        $filters = $this->iterableToArray($parser->getFilters());
        $this->assertEquals([
            'bool_attr'      => [
                'eq' => [true],
            ],
            'date_time_attr' => [
                'eq' => [$now],
            ],
        ], $filters);
    }

    /**
     * Test query.
     */
    public function testAllowedQueryParams(): void
    {
        $now             = new DateTimeImmutable('2001-02-03 04:05:06');
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'bool_attr'      => [
                    'eq' => 'true',
                ],
                'date_time_attr' => [
                    'eq' => $now->format(DateBaseType::JSON_API_FORMAT),
                ],
            ],
        ];
        $rules           = [];

        $parser = $this
            ->createParser($rules, $queryParameters)
            ->withAllowedFilterFields(['bool_attr', 'date_time_attr']);

        $filters = $this->iterableToArray($parser->getFilters());
        $this->assertEquals([
            'bool_attr'      => [
                'eq' => ['true'],
            ],
            'date_time_attr' => [
                'eq' => ['2001-02-03T04:05:06+0000'],
            ],
        ], $filters);
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testMissingRequiredQueryParam(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'int_attr' => [
                    'eq' => '1',
                ],
            ],
        ];
        $rules           = [
            'bool_attr' => v::isString(v::required(v::stringToBool())),
            'int_attr'  => v::isString(v::stringToInt()),
        ];

        $parser = $this->createParser($rules, $queryParameters);

        $this->iterableToArray($parser->getFilters());
    }

    /**
     * Test query.
     *
     * @expectedException \Limoncello\Flute\Exceptions\InvalidQueryParametersException
     */
    public function testUnknownQueryParam(): void
    {
        $queryParameters = [
            QueryParserInterface::PARAM_FILTER => [
                'unknown_attr' => [
                    'eq' => '1',
                ],
            ],
        ];
        $rules           = [
            'int_attr' => v::isString(v::stringToInt()),
        ];

        $parser = $this->createParser($rules, $queryParameters);

        $this->iterableToArray($parser->getFilters());
    }

    /**
     * @param array $attributeRules
     * @param array $queryParameters
     *
     * @return QueryParserInterface
     */
    private function createParser(array $attributeRules, array $queryParameters): QueryParserInterface
    {
        $name      = 'typically_a_class_name';
        $container = new Container();

        $data = (new FormRuleSerializer())->addResourceRules($name, $attributeRules)->getData();

        $paginationStrategy = new PaginationStrategy(20, 100);
        $parser             = (new QueryValidator($data, $container, $paginationStrategy))
            ->withValidatedFilterFields($name)
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

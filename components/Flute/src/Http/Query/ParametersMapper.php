<?php namespace Limoncello\Flute\Http\Query;

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
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Http\Query\AttributeInterface;
use Limoncello\Flute\Contracts\Http\Query\FilterParameterInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Contracts\Http\Query\RelationshipInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Exceptions\LogicException;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ParametersMapper implements ParametersMapperInterface
{
    /** Message */
    public const MSG_ERR_INVALID_OPERATION = 'Invalid Operation.';

    /** Message */
    public const MSG_ERR_INVALID_FIELD = 'Invalid field.';

    /** Message */
    public const MSG_ERR_ROOT_SCHEME_IS_NOT_SET = 'Root Scheme is not set.';

    /** Message */
    private const MSG_PARAM_INCLUDE = QueryParserInterface::PARAM_INCLUDE;

    /** Message */
    private const MSG_PARAM_FILTER = QueryParserInterface::PARAM_FILTER;

    /** internal constant */
    private const REL_FILTER_INDEX = 0;

    /** internal constant */
    private const REL_SORT_INDEX = 1;

    /**
     * @var SchemaInterface
     */
    private $rootScheme;

    /**
     * @var JsonSchemesInterface
     */
    private $jsonSchemes;

    /**
     * @var array|null
     */
    private $messages;

    /**
     * @var iterable
     */
    private $filters;

    /**
     * @var iterable
     */
    private $sorts;

    /**
     * @var iterable
     */
    private $includes;

    /**
     * @param JsonSchemesInterface $jsonSchemes
     * @param array|null           $messages
     */
    public function __construct(JsonSchemesInterface $jsonSchemes, array $messages = null)
    {
        $this->jsonSchemes = $jsonSchemes;
        $this->messages    = $messages;

        $this->withoutFilters()->withoutSorts()->withoutIncludes();
    }

    /**
     * @inheritdoc
     */
    public function selectRootSchemeByResourceType(string $resourceType): ParametersMapperInterface
    {
        $this->rootScheme = $this->getJsonSchemes()->getSchemaByResourceType($resourceType);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withFilters(iterable $filters): ParametersMapperInterface
    {
        // Sample format
        // [
        //     'attribute' => [
        //         'op1' => [arg1],
        //         'op2' => [arg1, arg1],
        //     ],
        //     'relationship' => [
        //         'op1' => [arg1],
        //         'op2' => [arg1, arg1],
        //     ],
        //     'relationship.attribute' => [
        //         'op1' => [arg1],
        //         'op2' => [arg1, arg1],
        //     ],
        // ];

        $this->filters = $filters;

        return $this;
    }

    /**
     * @return self
     */
    public function withoutFilters(): self
    {
        $this->withFilters([]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withSorts(iterable $sorts): ParametersMapperInterface
    {
        // Sample format (name => isAsc)
        // [
        //     'attribute'              => true,
        //     'relationship'           => false,
        //     'relationship.attribute' => true,
        // ];

        $this->sorts = $sorts;

        return $this;
    }

    /**
     * @return self
     */
    public function withoutSorts(): self
    {
        $this->withSorts([]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withIncludes(iterable $includes): ParametersMapperInterface
    {
        // Sample format
        // [
        //     ['relationship'],
        //     ['relationship', 'next_relationship'],
        // ];

        $this->includes = $includes;

        return $this;
    }

    /**
     * @return self
     */
    public function withoutIncludes(): self
    {
        $this->withIncludes([]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMappedFilters(): iterable
    {
        foreach ($this->getFilters() as $field => $operationsAndArgs) {
            assert(is_string($field));

            /** @var RelationshipInterface|null $relationship */
            /** @var AttributeInterface $attribute */
            list ($relationship, $attribute) = $this->mapToRelationshipAndAttribute($field);

            $filter = new FilterParameter(
                $attribute,
                $this->parseOperationsAndArguments(static::MSG_PARAM_FILTER, $operationsAndArgs),
                $relationship
            );

            yield $filter;
        }
    }

    /**
     * @inheritdoc
     */
    public function getMappedSorts(): iterable
    {
        foreach ($this->getSorts() as $field => $isAsc) {
            assert(is_string($field) === true && empty($field) === false && is_bool($isAsc) === true);

            /** @var RelationshipInterface|null $relationship */
            /** @var AttributeInterface $attribute */
            list ($relationship, $attribute) = $this->mapToRelationshipAndAttribute($field);

            $sort = new SortParameter($attribute, $isAsc, $relationship);

            yield $sort;
        }
    }

    /**
     * @inheritdoc
     */
    public function getMappedIncludes(): iterable
    {
        $fromScheme        = $this->getRootScheme();
        $getMappedRelLinks = function (iterable $links) use ($fromScheme) : iterable {
            foreach ($links as $link) {
                assert(is_string($link));
                $fromSchemaClass = get_class($fromScheme);
                if ($this->getJsonSchemes()->hasRelationshipSchema($fromSchemaClass, $link)) {
                    $toScheme     = $this->getJsonSchemes()->getRelationshipSchema($fromSchemaClass, $link);
                    $relationship = new Relationship($link, $fromScheme, $toScheme);

                    yield $relationship;

                    $fromScheme = $toScheme;
                    continue;
                }

                $error = $this->createQueryError(static::MSG_PARAM_INCLUDE, static::MSG_ERR_INVALID_FIELD);
                throw new JsonApiException($error);
            }
        };

        foreach ($this->getIncludes() as $links) {
            yield $getMappedRelLinks($links);
        }
    }

    /**
     * @inheritdoc
     */
    public function applyQueryParameters(QueryParserInterface $parser, CrudInterface $api): CrudInterface
    {
        //
        // Paging
        //
        $api->withPaging($parser->getPagingOffset(), $parser->getPagingLimit());

        //
        // Includes
        //

        // the two functions below compose a 2D array of relationship names in a form of iterable
        // [
        //     ['rel1_name1', 'rel1_name2', 'rel1_name3', ],
        //     ['rel2_name1', ],
        //     ['rel3_name1', 'rel3_name2', 'rel3_name3', ],
        // ]
        $includeAsModelNames = function (iterable $relationships): iterable {
            foreach ($relationships as $relationship) {
                assert($relationship instanceof RelationshipInterface);
                yield $relationship->getNameInModel();
            }
        };
        $mappedIncludes     = $this->getMappedIncludes();
        $getIncludes         = function () use ($mappedIncludes, $includeAsModelNames) : iterable {
            foreach ($mappedIncludes as $relationships) {
                yield $includeAsModelNames($relationships);
            }
        };

        //
        // Filters and Sorts
        //

        $parser->areFiltersWithAnd() === true ? $api->combineWithAnd() : $api->combineWithOr();

        $this
            ->withFilters($parser->getFilters())
            ->withSorts($parser->getSorts())
            ->withIncludes($parser->getIncludes());

        $attributeFilters = [];
        $attributeSorts   = [];

        // As relationship filters and sorts should be applied together (in one SQL JOIN)
        // we have to iterate through all filters and merge related to the same relationship.
        $relFiltersAndSorts = [];

        foreach ($this->getMappedFilters() as $filter) {
            /** @var FilterParameterInterface $filter */
            $attributeName = $filter->getAttribute()->getNameInModel();
            if ($filter->getRelationship() === null) {
                $attributeFilters[$attributeName] = $filter->getOperationsWithArguments();
            } else {
                $relationshipName                                                              =
                    $filter->getRelationship()->getNameInModel();
                $relFiltersAndSorts[$relationshipName][self::REL_FILTER_INDEX][$attributeName] =
                    $filter->getOperationsWithArguments();
            }
        }
        foreach ($this->getMappedSorts() as $sort) {
            /** @var SortParameter $sort */
            $attributeName = $sort->getAttribute()->getNameInModel();
            if ($sort->getRelationship() === null) {
                $attributeSorts[$attributeName] = $sort->isAsc();
            } else {
                $relationshipName                                                            =
                    $sort->getRelationship()->getNameInModel();
                $relFiltersAndSorts[$relationshipName][self::REL_SORT_INDEX][$attributeName] =
                    $sort->isAsc();
            }
        }

        $api->withFilters($attributeFilters)
            ->withSorts($attributeSorts)
            ->withIncludes($getIncludes());

        foreach ($relFiltersAndSorts as $relationshipName => $filtersAndSorts) {
            if (array_key_exists(self::REL_FILTER_INDEX, $filtersAndSorts) === true) {
                $api->withRelationshipFilters($relationshipName, $filtersAndSorts[self::REL_FILTER_INDEX]);
            }
            if (array_key_exists(self::REL_SORT_INDEX, $filtersAndSorts) === true) {
                $api->withRelationshipSorts($relationshipName, $filtersAndSorts[self::REL_SORT_INDEX]);
            }
        }

        return $api;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    private function mapToRelationshipAndAttribute(string $field): array
    {
        $rootSchema = $this->getRootScheme();
        if ($rootSchema->hasAttributeMapping($field) === true) {
            $relationship = null;
            $scheme       = $rootSchema;
            $attribute    = new Attribute($field, $scheme);

            return [$relationship, $attribute];
        } elseif ($rootSchema->hasRelationshipMapping($field) === true) {
            $fromScheme   = $rootSchema;
            $toScheme     = $this->getJsonSchemes()->getRelationshipSchema(get_class($fromScheme), $field);
            $relationship = new Relationship($field, $fromScheme, $toScheme);
            $attribute    = new Attribute($toScheme::RESOURCE_ID, $toScheme);

            return [$relationship, $attribute];
        } elseif (count($mightBeRelAndAttr = explode('.', $field, 2)) === 2) {
            // Last chance. It could be a dot ('.') separated relationship with an attribute.

            $mightBeRel  = $mightBeRelAndAttr[0];
            $mightBeAttr = $mightBeRelAndAttr[1];

            $fromScheme = $rootSchema;
            if ($fromScheme->hasRelationshipMapping($mightBeRel)) {
                $toScheme = $this->getJsonSchemes()->getRelationshipSchema(get_class($fromScheme), $mightBeRel);
                if ($toScheme::hasAttributeMapping($mightBeAttr) === true) {
                    $relationship = new Relationship($mightBeRel, $fromScheme, $toScheme);
                    $attribute    = new Attribute($mightBeAttr, $toScheme);

                    return [$relationship, $attribute];
                }
            }
        }

        $error = $this->createQueryError($field, static::MSG_ERR_INVALID_FIELD);
        throw new JsonApiException($error);
    }

    /**
     * @param string   $parameterName
     * @param iterable $value
     *
     * @return iterable
     */
    private function parseOperationsAndArguments(string $parameterName, iterable $value): iterable
    {
        // in this case we interpret it as an [operation => [arg1, arg2]]
        foreach ($value as $operationName => $arguments) {
            assert(is_array($arguments) || $arguments instanceof Generator);

            switch ($operationName) {
                case '=':
                case 'eq':
                case 'equals':
                    $operation = FilterParameterInterface::OPERATION_EQUALS;
                    break;
                case '!=':
                case 'neq':
                case 'not-equals':
                    $operation = FilterParameterInterface::OPERATION_NOT_EQUALS;
                    break;
                case '<':
                case 'lt':
                case 'less-than':
                    $operation = FilterParameterInterface::OPERATION_LESS_THAN;
                    break;
                case '<=':
                case 'lte':
                case 'less-or-equals':
                    $operation = FilterParameterInterface::OPERATION_LESS_OR_EQUALS;
                    break;
                case '>':
                case 'gt':
                case 'greater-than':
                    $operation = FilterParameterInterface::OPERATION_GREATER_THAN;
                    break;
                case '>=':
                case 'gte':
                case 'greater-or-equals':
                    $operation = FilterParameterInterface::OPERATION_GREATER_OR_EQUALS;
                    break;
                case 'like':
                    $operation = FilterParameterInterface::OPERATION_LIKE;
                    break;
                case 'not-like':
                    $operation = FilterParameterInterface::OPERATION_NOT_LIKE;
                    break;
                case 'in':
                    $operation = FilterParameterInterface::OPERATION_IN;
                    break;
                case 'not-in':
                    $operation = FilterParameterInterface::OPERATION_NOT_IN;
                    break;
                case 'is-null':
                    $operation = FilterParameterInterface::OPERATION_IS_NULL;
                    $arguments = [];
                    break;
                case 'not-null':
                    $operation = FilterParameterInterface::OPERATION_IS_NOT_NULL;
                    $arguments = [];
                    break;
                default:
                    $error = $this->createQueryError($parameterName, static::MSG_ERR_INVALID_OPERATION);
                    throw new JsonApiException($error);
            }

            yield $operation => $arguments;
        }
    }

    /**
     * @return SchemaInterface
     */
    private function getRootScheme(): SchemaInterface
    {
        if ($this->rootScheme === null) {
            throw new LogicException($this->getMessage(static::MSG_ERR_ROOT_SCHEME_IS_NOT_SET));
        }

        return $this->rootScheme;
    }

    /**
     * @return JsonSchemesInterface
     */
    private function getJsonSchemes(): JsonSchemesInterface
    {
        return $this->jsonSchemes;
    }

    /**
     * @return iterable
     */
    private function getFilters(): iterable
    {
        return $this->filters;
    }

    /**
     * @return iterable
     */
    private function getSorts(): iterable
    {
        return $this->sorts;
    }

    /**
     * @return iterable
     */
    private function getIncludes(): iterable
    {
        return $this->includes;
    }

    /**
     * @param string $name
     * @param string $title
     *
     * @return Error
     */
    private function createQueryError(string $name, string $title): Error
    {
        $title  = $this->getMessage($title);
        $source = [Error::SOURCE_PARAMETER => $name];
        $error  = new Error(null, null, null, null, $title, null, $source);

        return $error;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    private function getMessage(string $message): string
    {
        $hasTranslation = $this->messages !== null && array_key_exists($message, $this->messages) === false;

        return $hasTranslation === true ? $this->messages[$message] : $message;
    }
}

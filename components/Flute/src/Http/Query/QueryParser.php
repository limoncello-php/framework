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
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Exceptions\InvalidQueryParametersException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Http\Query\BaseQueryParser;

/**
 * @package Limoncello\Flute
 */
class QueryParser extends BaseQueryParser implements QueryParserInterface
{
    /** Message */
    public const MSG_ERR_INVALID_OPERATION_ARGUMENTS = 'Invalid Operation Arguments.';

    /**
     * @var PaginationStrategyInterface
     */
    private $paginationStrategy;

    /**
     * @var array
     */
    private $filterParameters;

    /**
     * @var bool
     */
    private $areFiltersWithAnd;

    /**
     * @var int|null
     */
    private $pagingOffset;

    /**
     * @var int|null
     */
    private $pagingLimit;

    /**
     * @var string[]|null
     */
    private $allowedFilterFields;

    /**
     * @var string[]|null
     */
    private $allowedSortFields;

    /**
     * @var string[]|null
     */
    private $allowedIncludePaths;

    /**
     * @param PaginationStrategyInterface $paginationStrategy
     * @param string[]|null               $messages
     */
    public function __construct(PaginationStrategyInterface $paginationStrategy, array $messages = null)
    {
        $parameters = [];
        parent::__construct($parameters, $messages);

        $this->paginationStrategy = $paginationStrategy;

        $this->clear();
    }

    /**
     * @inheritdoc
     */
    public function withAllowedFilterFields(array $fields): QueryParserInterface
    {
        // debug check all fields are strings
        assert(
            (function () use ($fields) {
                $allAreStrings = !empty($fields);
                foreach ($fields as $field) {
                    $allAreStrings = $allAreStrings === true && is_string($field) === true && empty($field) === false;
                }

                return $allAreStrings;
            })() === true
        );

        $this->allowedFilterFields = $fields;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAllAllowedFilterFields(): QueryParserInterface
    {
        $this->allowedFilterFields = null;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withNoAllowedFilterFields(): QueryParserInterface
    {
        $this->allowedFilterFields = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAllowedSortFields(array $fields): QueryParserInterface
    {
        // debug check all fields are strings
        assert(
            (function () use ($fields) {
                $allAreStrings = !empty($fields);
                foreach ($fields as $field) {
                    $allAreStrings = $allAreStrings === true && is_string($field) === true && empty($field) === false;
                }

                return $allAreStrings;
            })() === true
        );

        $this->allowedSortFields = $fields;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAllAllowedSortFields(): QueryParserInterface
    {
        $this->allowedSortFields = null;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withNoAllowedSortFields(): QueryParserInterface
    {
        $this->allowedSortFields = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAllowedIncludePaths(array $paths): QueryParserInterface
    {
        // debug check all fields are strings
        assert(
            (function () use ($paths) {
                $allAreStrings = !empty($paths);
                foreach ($paths as $path) {
                    $allAreStrings = $allAreStrings === true && is_string($path) === true && empty($path) === false;
                }

                return $allAreStrings;
            })() === true
        );

        $this->allowedIncludePaths = $paths;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withAllAllowedIncludePaths(): QueryParserInterface
    {
        $this->allowedIncludePaths = null;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withNoAllowedIncludePaths(): QueryParserInterface
    {
        $this->allowedIncludePaths = [];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function parse(array $parameters): QueryParserInterface
    {
        parent::setParameters($parameters);

        $this->parsePagingParameters()->parseFilterLink();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function areFiltersWithAnd(): bool
    {
        return $this->areFiltersWithAnd;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): iterable
    {
        foreach ($this->getFilterParameters() as $field => $operationsWithArgs) {
            if (is_string($field) === false || empty($field) === true ||
                is_array($operationsWithArgs) === false || empty($operationsWithArgs) === true
            ) {
                throw new InvalidQueryParametersException($this->createParameterError(static::PARAM_FILTER));
            }

            if ($this->allowedFilterFields === null || in_array($field, $this->allowedFilterFields) === true) {
                yield $field => $this->parseOperationsAndArguments(static::PARAM_FILTER, $operationsWithArgs);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): iterable
    {
        foreach (parent::getSorts() as $field => $isAsc) {
            if ($this->allowedSortFields === null || in_array($field, $this->allowedSortFields) === true) {
                yield $field => $isAsc;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getIncludes(): iterable
    {
        foreach (parent::getIncludes() as $path => $split) {
            if ($this->allowedIncludePaths === null || in_array($path, $this->allowedIncludePaths) === true) {
                yield $path => $split;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getPagingOffset(): ?int
    {
        return $this->pagingOffset;
    }

    /**
     * @inheritdoc
     */
    public function getPagingLimit(): ?int
    {
        return $this->pagingLimit;
    }

    /**
     * @inheritdoc
     */
    public function createEncodingParameters(): EncodingParametersInterface
    {
        $paths = null;
        foreach ($this->getIncludes() as $path => $links) {
            $paths[] = $path;
        }

        $fields = $this->deepReadIterable($this->getParameters()[static::PARAM_FIELDS] ?? []);

        // encoder uses only these parameters and the rest are ignored
        return new EncodingParameters($paths, empty($fields) === true ? null : $fields);
    }

    /**
     * @return self
     */
    private function parsePagingParameters(): self
    {
        $pagingParams = $this->getParameters()[static::PARAM_PAGE] ?? null;

        list ($this->pagingOffset, $this->pagingLimit) = $this->getPaginationStrategy()->parseParameters($pagingParams);
        assert(is_int($this->pagingOffset) === true && $this->pagingOffset >= 0);
        assert(is_int($this->pagingLimit) === true && $this->pagingLimit > 0);

        return $this;
    }

    /**
     * Pre-parsing for filter parameters.
     *
     * @return self
     */
    private function parseFilterLink(): self
    {
        if (array_key_exists(static::PARAM_FILTER, $this->getParameters()) === false) {
            $this->setFiltersWithAnd()->setFilterParameters([]);

            return $this;
        }

        $filterSection = $this->getParameters()[static::PARAM_FILTER];
        if (is_array($filterSection) === false || empty($filterSection) === true) {
            throw new InvalidQueryParametersException($this->createParameterError(static::PARAM_FILTER));
        }

        $isWithAnd = true;
        reset($filterSection);

        // check if top level element is `AND` or `OR`
        $firstKey   = key($filterSection);
        $firstLcKey = strtolower(trim($firstKey));
        if (($hasOr = ($firstLcKey === 'or')) || $firstLcKey === 'and') {
            if (count($filterSection) > 1 ||
                empty($filterSection = $filterSection[$firstKey]) === true ||
                is_array($filterSection) === false
            ) {
                throw new InvalidQueryParametersException($this->createParameterError(static::PARAM_FILTER));
            } else {
                $this->setFilterParameters($filterSection);
                if ($hasOr === true) {
                    $isWithAnd = false;
                }
            }
        } else {
            $this->setFilterParameters($filterSection);
        }

        $isWithAnd === true ? $this->setFiltersWithAnd() : $this->setFiltersWithOr();

        return $this;
    }

    /**
     * @param array $values
     *
     * @return self
     */
    private function setFilterParameters(array $values): self
    {
        $this->filterParameters = $values;

        return $this;
    }

    /**
     * @return array
     */
    private function getFilterParameters(): array
    {
        return $this->filterParameters;
    }

    /**
     * @return self
     */
    private function setFiltersWithAnd(): self
    {
        $this->areFiltersWithAnd = true;

        return $this;
    }

    /**
     * @return self
     */
    private function setFiltersWithOr(): self
    {
        $this->areFiltersWithAnd = false;

        return $this;
    }

    /**
     * @return PaginationStrategyInterface
     */
    private function getPaginationStrategy(): PaginationStrategyInterface
    {
        return $this->paginationStrategy;
    }

    /**
     * @return self
     */
    private function clear(): self
    {
        $this->filterParameters  = [];
        $this->areFiltersWithAnd = true;
        $this->pagingOffset      = null;
        $this->pagingLimit       = null;

        $this->withNoAllowedFilterFields()->withNoAllowedSortFields()->withNoAllowedIncludePaths();

        return $this;
    }

    /**
     * @param string $parameterName
     * @param array  $value
     *
     * @return iterable
     */
    private function parseOperationsAndArguments(string $parameterName, array $value): iterable
    {
        // in this case we interpret it as an [operation => 'comma separated argument(s)']
        foreach ($value as $operationName => $arguments) {
            if (is_string($operationName) === false || empty($operationName) === true ||
                is_string($arguments) === false
            ) {
                $title = static::MSG_ERR_INVALID_OPERATION_ARGUMENTS;
                $error = $this->createQueryError($parameterName, $title);
                throw new InvalidQueryParametersException($error);
            }

            if ($arguments === '') {
                yield $operationName => [];
            } else {
                yield $operationName => $this->splitCommaSeparatedStringAndCheckNoEmpties($parameterName, $arguments);
            }
        }
    }

    /**
     * @param iterable $input
     *
     * @return array
     */
    private function deepReadIterable(iterable $input): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            $result[$key] = $value instanceof Generator ? $this->deepReadIterable($value) : $value;
        }

        return $result;
    }
}

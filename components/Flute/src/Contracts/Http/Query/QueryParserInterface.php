<?php namespace Limoncello\Flute\Contracts\Http\Query;

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

use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * @package Limoncello\Flute
 */
interface QueryParserInterface extends BaseQueryParserInterface
{
    /** Parameter name */
    public const PARAM_PAGE = 'page';

    /** Parameter name */
    public const PARAM_FILTER = 'filter';

    /**
     * @param array $parameters
     *
     * @return self
     */
    public function parse(array $parameters): self;

    /**
     * @return bool
     */
    public function areFiltersWithAnd(): bool;

    /**
     * @return iterable
     */
    public function getFilters(): iterable;

    /**
     * @return int|null
     */
    public function getPagingOffset(): ?int;

    /**
     * @return int|null
     */
    public function getPagingLimit(): ?int;

    /**
     * @return EncodingParametersInterface
     */
    public function createEncodingParameters(): EncodingParametersInterface;

    /**
     * @param array $fields
     *
     * @return self
     */
    public function withAllowedFilterFields(array $fields): self;

    /**
     * @return self
     */
    public function withAllAllowedFilterFields(): self;

    /**
     * @return self
     */
    public function withNoAllowedFilterFields(): self;

    /**
     * @param array $fields
     *
     * @return self
     */
    public function withAllowedSortFields(array $fields): self;

    /**
     * @return self
     */
    public function withAllAllowedSortFields(): self;

    /**
     * @return self
     */
    public function withNoAllowedSortFields(): self;

    /**
     * @param array $paths
     *
     * @return self
     */
    public function withAllowedIncludePaths(array $paths): self;

    /**
     * @return self
     */
    public function withAllAllowedIncludePaths(): self;

    /**
     * @return self
     */
    public function withNoAllowedIncludePaths(): self;
}
<?php namespace Limoncello\Flute\Contracts\Validation;

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

use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;

/**
 * @package Limoncello\Application
 */
interface JsonApiQueryValidatingParserInterface extends BaseQueryParserInterface
{
    /** Query parameter */
    const PARAM_PAGING_LIMIT = 'limit';

    /** Query parameter */
    const PARAM_PAGING_OFFSET = 'offset';

    /**
     * @param array $parameters
     *
     * @return self
     */
    public function parse(array $parameters): self;

    /**
     * If filters are joined with `AND` (or with `OR` otherwise).
     *
     * @return bool
     */
    public function areFiltersWithAnd(): bool;

    /**
     * @return bool
     */
    public function hasFilters(): bool;

    /**
     * @return bool
     */
    public function hasFields(): bool;

    /**
     * @return bool
     */
    public function hasIncludes(): bool;

    /**
     * @return bool
     */
    public function hasSorts(): bool;

    /**
     * @return bool
     */
    public function hasPaging(): bool;

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
}

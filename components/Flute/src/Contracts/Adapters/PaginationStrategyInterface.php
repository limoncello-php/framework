<?php namespace Limoncello\Flute\Contracts\Adapters;

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

/**
 * @package Limoncello\Flute
 */
interface PaginationStrategyInterface
{
    /** Query parameter */
    const PARAM_PAGING_LIMIT = 'limit';

    /** Query parameter */
    const PARAM_PAGING_OFFSET = 'offset';

    /**
     * @param string $rootClass
     * @param string $class
     * @param string $path
     * @param string $relationshipName
     *
     * @return array [$offset, $limit]
     */
    public function getParameters(string $rootClass, string $class, string $path, string $relationshipName): array;

    /**
     * @param array|null $parameters
     *
     * @return array [$offset, $limit]
     */
    public function parseParameters(?array $parameters): array;
}

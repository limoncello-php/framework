<?php namespace Limoncello\Contracts\Routing;

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
 * @package Limoncello\Contracts
 */
interface DispatcherInterface
{
    /** Dispatch result code */
    const ROUTE_NOT_FOUND = 0;

    /** Dispatch result code */
    const ROUTE_FOUND = self::ROUTE_NOT_FOUND + 1;

    /** Dispatch result code */
    const ROUTE_METHOD_NOT_ALLOWED = self::ROUTE_FOUND + 1;

    /**
     * @param array $data
     *
     * @return void
     */
    public function setData(array $data);

    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * Returns array with one of the following formats:
     *
     *     [self::NOT_FOUND]
     *     [self::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
     *     [self::FOUND, $handler, ['varName' => 'value', ...]]
     *
     * @param string $method
     * @param string $uri
     *
     * @return array
     */
    public function dispatchRequest(string $method, string $uri): array;
}

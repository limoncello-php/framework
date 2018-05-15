<?php namespace Limoncello\Contracts\Routing;

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

use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Contracts
 */
interface RouterInterface
{
    /** Config key */
    const CONFIG_KEY_FAST_ROUTE_DISPATCHER = 0;

    /** Match result code */
    const MATCH_FOUND = 0;

    /** Match result code */
    const MATCH_METHOD_NOT_ALLOWED = 1;

    /** Match result code */
    const MATCH_NOT_FOUND = 2;

    /**
     * @param GroupInterface $group
     *
     * @return array
     */
    public function getCachedRoutes(GroupInterface $group): array;

    /**
     * @param array $cachedRoutes
     *
     * @return void
     */
    public function loadCachedRoutes(array $cachedRoutes): void;

    /**
     * @param string $method
     * @param string $uriPath
     *
     * @return array Array contains matching result code, allowed methods list, handler parameters list, handler,
     *               middleware list, container configurators list, custom request factory.
     */
    public function match(string $method, string $uriPath): array;

    /**
     * Get URI path for named route.
     *
     * @param string $routeName
     *
     * @return string|null
     */
    public function getUriPath(string $routeName): ?string;

    /**
     * Compose URL for named route.
     *
     * @param string $hostUri
     * @param string $routeName
     * @param array  $placeholders
     * @param array  $queryParams
     *
     * @return string
     */
    public function get(
        string $hostUri,
        string $routeName,
        array $placeholders = [],
        array $queryParams = []
    ): string;

    /**
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function getHostUri(ServerRequestInterface $request): string;
}

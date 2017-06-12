<?php namespace Limoncello\Testing;

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

use Psr\Http\Message\ResponseInterface;

/** @noinspection PhpTooManyParametersInspection
 * @package Limoncello\Testing
 *
 * @@codingStandardsIgnoreLine
 * @method ResponseInterface call(string $method, string $uri, array $queryParams = [], array $parsedBody = [], array $headers = [], array $cookies = [], array $files = [], array $server = [], string $messageBody = 'php://input')
 */
trait HttpCallsTrait
{
    /**
     * @param string $uri
     * @param array  $queryParams
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function get(
        string $uri,
        array $queryParams = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $this->call('GET', $uri, $queryParams, [], $headers, $cookies);
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function post(
        string $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        return $this->call('POST', $uri, [], $data, $headers, $cookies);
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function put(
        string $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        return $this->call('PUT', $uri, [], $data, $headers, $cookies);
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function patch(
        string $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';

        return $this->call('PATCH', $uri, [], $data, $headers, $cookies);
    }

    /**
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function delete(
        string $uri,
        array $data = [],
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        return $this->call('DELETE', $uri, [], $data, $headers, $cookies);
    }
}

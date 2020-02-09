<?php declare(strict_types=1);

namespace Limoncello\Testing;

/**
 * Copyright 2015-2020 info@neomerx.com
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
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Stream;

/** @noinspection PhpTooManyParametersInspection
 * @package Limoncello\Testing
 *
 * @@codingStandardsIgnoreLine
 * @method ResponseInterface call(string $method, string $uri, array $queryParams = [], array $parsedBody = [], array $headers = [], array $cookies = [], array $files = [], array $server = [], string|StreamInterface $messageBody = 'php://input')
 */
trait JsonApiCallsTrait
{
    /**
     * @param string $uri
     * @param string $json
     * @param array  $headers
     * @param array  $cookies
     * @param array  $files
     *
     * @return ResponseInterface
     */
    protected function postJsonApi(
        string $uri,
        string $json,
        array $headers = [],
        array $cookies = [],
        array $files = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/vnd.api+json';

        return $this->call('POST', $uri, [], [], $headers, $cookies, $files, [], $this->streamFromString($json));
    }

    /**
     * @param string $uri
     * @param string $json
     * @param array  $headers
     * @param array  $cookies
     * @param array  $files
     *
     * @return ResponseInterface
     */
    protected function putJsonApi(
        string $uri,
        string $json,
        array $headers = [],
        array $cookies = [],
        array $files = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/vnd.api+json';

        return $this->call('PUT', $uri, [], [], $headers, $cookies, $files, [], $this->streamFromString($json));
    }

    /**
     * @param string $uri
     * @param string $json
     * @param array  $headers
     * @param array  $cookies
     * @param array  $files
     *
     * @return ResponseInterface
     */
    protected function patchJsonApi(
        string $uri,
        string $json,
        array $headers = [],
        array $cookies = [],
        array $files = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/vnd.api+json';

        return $this->call('PATCH', $uri, [], [], $headers, $cookies, $files, [], $this->streamFromString($json));
    }

    /**
     * @param string $uri
     * @param string $json
     * @param array  $headers
     * @param array  $cookies
     *
     * @return ResponseInterface
     */
    protected function deleteJsonApi(
        string $uri,
        string $json,
        array $headers = [],
        array $cookies = []
    ): ResponseInterface {
        $headers['CONTENT_TYPE'] = 'application/vnd.api+json';

        return $this->call('DELETE', $uri, [], [], $headers, $cookies, [], [], $this->streamFromString($json));
    }

    /**
     * @param string $content
     *
     * @return StreamInterface
     */
    protected function streamFromString(string $content): StreamInterface
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($content);

        return $stream;
    }
}

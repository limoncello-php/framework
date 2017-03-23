<?php namespace Limoncello\Core\Contracts\Application;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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
use Psr\Http\Message\UriInterface;

/**
 * @package Limoncello\Core
 */
interface SapiInterface
{
    /**
     * @return array
     */
    public function getServer(): array;

    /**
     * @return string|resource|StreamInterface
     */
    public function getRequestBody();

    /**
     * @return array|object
     */
    public function getParsedBody();

    /**
     * @return array
     */
    public function getQueryParams(): array;

    /**
     * @return array
     */
    public function getCookies(): array;

    /**
     * @return array
     */
    public function getFiles(): array;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string
     */
    public function getProtocolVersion(): string;

    /**
     * @param ResponseInterface $response
     *
     * @return void
     */
    public function handleResponse(ResponseInterface $response);
}

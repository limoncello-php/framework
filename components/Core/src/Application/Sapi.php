<?php namespace Limoncello\Core\Application;

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

use Limoncello\Core\Contracts\Application\SapiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequestFactory;

/**
 * @package Limoncello\Core
 */
class Sapi implements SapiInterface
{
    /**
     * @var EmitterInterface
     */
    private $sapiEmitter;

    /**
     * @var array
     */
    private $server;

    /**
     * @var array
     */
    private $files;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $cookies;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var array|object
     */
    private $parsedBody;

    /**
     * @var string|resource|StreamInterface
     */
    private $messageBody;

    /**
     * Sapi constructor.
     *
     * @param EmitterInterface                $sapiEmitter
     * @param array|null                      $server
     * @param array|null                      $queryParams
     * @param array|object|null               $parsedBody
     * @param array|null                      $cookies
     * @param array|null                      $files
     * @param string|resource|StreamInterface $messageBody
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function __construct(
        EmitterInterface $sapiEmitter,
        array $server = null,
        array $queryParams = null,
        array $parsedBody = null,
        array $cookies = null,
        array $files = null,
        $messageBody = 'php://input'
    ) {
        $this->sapiEmitter = $sapiEmitter;

        // returns value if not null or $fallback otherwise
        $get = function ($nullable, $fallback) {
            return $nullable !== null ? $nullable : $fallback;
        };

        // Code below based on ServerRequestFactory::fromGlobals
        $this->server      = ServerRequestFactory::normalizeServer($get($server, $_SERVER));
        $this->files       = ServerRequestFactory::normalizeFiles($get($files, $_FILES));
        $this->headers     = ServerRequestFactory::marshalHeaders($this->server);
        $this->uri         = ServerRequestFactory::marshalUriFromServer($this->server, $this->headers);
        $this->method      = ServerRequestFactory::get('REQUEST_METHOD', $this->server, 'GET');
        $this->cookies     = $get($cookies, $_COOKIE);
        $this->queryParams = $get($queryParams, $_GET);
        $this->parsedBody  = $get($parsedBody, $_POST);
        $this->messageBody = $messageBody;
    }

    /**
     * @inheritdoc
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @inheritdoc
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritdoc
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @inheritdoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritdoc
     */
    public function getRequestBody()
    {
        return $this->messageBody;
    }

    /**
     * @inheritdoc
     */
    public function handleResponse(ResponseInterface $response)
    {
        $this->sapiEmitter->emit($response);
    }
}

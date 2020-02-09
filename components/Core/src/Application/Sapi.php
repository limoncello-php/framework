<?php declare(strict_types=1);

namespace Limoncello\Core\Application;

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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Limoncello\Contracts\Core\SapiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

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
     * @var string
     */
    private $protocolVersion;

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
     * @param string                          $protocolVersion
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
        $messageBody = 'php://input',
        string $protocolVersion = '1.1'
    ) {
        $this->sapiEmitter = $sapiEmitter;

        // Code below based on ServerRequestFactory::fromGlobals
        $this->server          = \Zend\Diactoros\normalizeServer($server ?? $_SERVER);
        $this->files           = \Zend\Diactoros\normalizeUploadedFiles($files ?? $_FILES);
        $this->headers         = \Zend\Diactoros\marshalHeadersFromSapi($this->server);
        $this->uri             = \Zend\Diactoros\marshalUriFromSapi($this->server, $this->headers);
        $this->method          = \Zend\Diactoros\marshalMethodFromSapi($this->server);
        $this->cookies         = $cookies ?? $_COOKIE;
        $this->queryParams     = $queryParams ?? $_GET;
        $this->parsedBody      = $parsedBody ?? $_POST;
        $this->messageBody     = $messageBody;
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * @inheritdoc
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritdoc
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * @inheritdoc
     */
    public function getQueryParams(): array
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
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function handleResponse(ResponseInterface $response): void
    {
        $this->sapiEmitter->emit($response);
    }
}

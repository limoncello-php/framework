<?php namespace Limoncello\Testing;

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

use Closure;
use Limoncello\Contracts\Core\ApplicationInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @package Limoncello\Testing
 */
trait TestCaseTrait
{
    /**
     * @var array
     */
    private $eventHandlers = [];

    /**
     * @param array|null                      $server
     * @param array|null                      $queryParams
     * @param array|object|null               $parsedBody
     * @param array|null                      $cookies
     * @param array|null                      $files
     * @param string|resource|StreamInterface $messageBody
     * @param string                          $protocolVersion
     *
     * @return Sapi
     */
    abstract protected function createSapi(
        array $server = null,
        array $queryParams = null,
        array $parsedBody = null,
        array $cookies = null,
        array $files = null,
        $messageBody = 'php://input',
        string $protocolVersion = '1.1'
    ): Sapi;

    /**
     * @return ApplicationInterface
     */
    abstract protected function createApplication(): ApplicationInterface;

    /** @noinspection PhpTooManyParametersInspection
     * @param string                          $method
     * @param string                          $uri
     * @param array                           $queryParams
     * @param array                           $parsedBody
     * @param array                           $headers
     * @param array                           $cookies
     * @param array                           $files
     * @param array                           $server
     * @param string|resource|StreamInterface $content
     * @param string                          $host
     * @param string                          $protocolVersion
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function call(
        string $method,
        string $uri,
        array $queryParams = [],
        array $parsedBody = [],
        array $headers = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = 'php://input',
        string $host = 'localhost',
        string $protocolVersion = '1.1'
    ): ResponseInterface {
        $headers['host'] = $host;

        $prefix = 'HTTP_';
        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');
            if ($name !== 'CONTENT_TYPE' && strpos($name, $prefix) !== 0) {
                $name = $prefix . $name;
            }
            $server[$name] = $value;
        }

        $server['REQUEST_URI']    = $uri;
        $server['REQUEST_METHOD'] = $method;

        $app  = $this->createApplication();
        $sapi = $this->createSapi($server, $queryParams, $parsedBody, $cookies, $files, $content, $protocolVersion);
        $app->setSapi($sapi)->run();
        unset($app);

        $response = $sapi->getResponse();
        unset($sapi);

        return $response;
    }

    /**
     * @return void
     */
    protected function resetEventHandlers(): void
    {
        $this->eventHandlers = [];
    }

    /**
     * @param Closure $handler
     *
     * @return void
     */
    protected function addOnHandleRequestEvent(Closure $handler): void
    {
        $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_HANDLE_REQUEST][] = $handler;
    }

    /**
     * @param Closure $handler
     *
     * @return void
     */
    protected function addOnHandleResponseEvent(Closure $handler): void
    {
        $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_HANDLE_RESPONSE][] = $handler;
    }

    /**
     * @param Closure $handler
     *
     * @return void
     */
    protected function addOnContainerCreatedEvent(Closure $handler): void
    {
        $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_CONTAINER_CREATED][] = $handler;
    }

    /**
     * @param Closure $handler
     *
     * @return void
     */
    protected function addOnContainerConfiguredEvent(Closure $handler): void
    {
        $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_CONTAINER_LAST_CONFIGURATOR][] = $handler;
    }

    /**
     * @return array
     */
    protected function getHandleRequestEvents(): array
    {
        return $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_HANDLE_REQUEST] ?? [];
    }

    /**
     * @return array
     */
    protected function getHandleResponseEvents(): array
    {
        return $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_HANDLE_RESPONSE] ?? [];
    }

    /**
     * @return array
     */
    protected function getContainerCreatedEvents(): array
    {
        return $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_CONTAINER_CREATED] ?? [];
    }

    /**
     * @return array
     */
    protected function getContainerConfiguredEvents(): array
    {
        return $this->eventHandlers[ApplicationWrapperInterface::EVENT_ON_CONTAINER_LAST_CONFIGURATOR] ?? [];
    }
}

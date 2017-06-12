<?php namespace Limoncello\Tests\Testing\Data;

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

use Closure;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Core\ApplicationInterface;
use Limoncello\Contracts\Core\SapiInterface;
use Mockery;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Limoncello\Tests\Testing
 */
class ApplicationStub implements ApplicationInterface
{
    /**
     * @inheritdoc
     */
    public function setSapi(SapiInterface $sapi): ApplicationInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createContainer(string $method = null, string $path = null): LimoncelloContainerInterface
    {
        $container = $this->createContainerInstance();

        $this->configureContainer($container);

        return $container;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
    }

    /**
     * @return LimoncelloContainerInterface
     */
    protected function createContainerInstance(): LimoncelloContainerInterface
    {
        /** @var LimoncelloContainerInterface $mock */
        $mock = Mockery::mock(LimoncelloContainerInterface::class);

        return $mock;
    }


    /**
     * @param LimoncelloContainerInterface $container
     * @param array|null                   $globalConfigurators
     * @param array|null                   $routeConfigurators
     *
     * @return void
     */
    protected function configureContainer(
        LimoncelloContainerInterface $container,
        array $globalConfigurators = null,
        array $routeConfigurators = null
    ) {
        $container && $globalConfigurators && $routeConfigurators ?: null;
    }

    /**
     * @param Closure               $handler
     * @param RequestInterface|null $request
     *
     * @return ResponseInterface
     */
    protected function handleRequest(Closure $handler, RequestInterface $request = null): ResponseInterface
    {
        $handler && $request ?: null;

        /** @var ResponseInterface $mock */
        $mock = Mockery::mock(ResponseInterface::class);

        return $mock;
    }
}

<?php namespace Limoncello\Tests\Application\Data\CoreSettings\Routes;

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

use Limoncello\Contracts\Application\RoutesConfiguratorInterface;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Middleware\ApplicationMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package Limoncello\Tests\Application
 */
class Routes1 implements RoutesConfiguratorInterface
{
    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [
            ApplicationMiddleware::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function configureRoutes(GroupInterface $routes): void
    {
        $routes->get('/', [static::class, 'home']);
    }

    /**
     * @param array                       $parameters
     * @param ContainerInterface          $container
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    public static function home(
        array $parameters,
        ContainerInterface $container,
        ServerRequestInterface $request = null
    ): ResponseInterface {
        assert(($parameters && $container && $request) || true);

        return new EmptyResponse();
    }
}

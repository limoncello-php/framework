<?php namespace Limoncello\Tests\Application\Data\CoreSettings\Providers;

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

use Closure;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface as CCI;
use Limoncello\Contracts\Provider\ProvidesMiddlewareInterface as MI;
use Limoncello\Contracts\Provider\ProvidesRouteConfiguratorsInterface as RCI;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface as SI;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Core\Contracts\Routing\GroupInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Middleware\PluginMiddleware;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package Limoncello\Tests\Application
 */
class Provider1 implements CCI, MI, RCI, SI
{
    /**
     * Get container configurators.
     *
     * @return callable[]
     */
    public static function getContainerConfigurators(): array
    {
        return [
            [static::class, 'containerConfigure'],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function containerConfigure(LimoncelloContainerInterface $container)
    {
        $container[static::class] = 'Hello container';
    }

    /**
     * Get middleware.
     *
     * @return callable[]
     */
    public static function getMiddleware(): array
    {
        return [
            PluginMiddleware::ENTRY,
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param Closure                $next
     * @param PsrContainerInterface  $container
     *
     * @return ResponseInterface
     */
    public static function middlewareHandle(
        ServerRequestInterface $request,
        Closure $next,
        PsrContainerInterface $container
    ): ResponseInterface {
        return $next($request);
    }

    /**
     * Get provider default settings.
     *
     * @return SettingsInterface[]
     */
    public static function getSettings(): array
    {
        return [new class implements SettingsInterface {
            /**
             * @return array
             */
            public function get(): array
            {
                return ['Provider1_Settings' => 'some value'];
            }
        }];
    }

    /**
     * Get route configurators.
     *
     * @return callable[]
     */
    public static function getRouteConfigurators(): array
    {
        return [
            [static::class, 'routeConfigurator']
        ];
    }

    /**
     * @param GroupInterface $routes
     */
    public static function routeConfigurator(GroupInterface $routes)
    {
        $routes->get('/plugin1', [static::class, 'onIndex']);
    }

    /**
     * @param array                       $parameters
     * @param PsrContainerInterface       $container
     * @param ServerRequestInterface|null $request
     *
     * @return ResponseInterface
     */
    public static function onIndex(
        array $parameters,
        PsrContainerInterface $container,
        ServerRequestInterface $request = null
    ): ResponseInterface {
        assert(($parameters && $container && $request) || true);

        return new EmptyResponse();
    }
}

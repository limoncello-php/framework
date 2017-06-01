<?php namespace Limoncello\Tests\Application\Data\CoreSettings\Providers;

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

use Limoncello\Application\Commands\DataCommand;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface as CCI;
use Limoncello\Contracts\Application\RoutesConfiguratorInterface as RCI;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Provider\ProvidesCommandsInterface as PrCmdI;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface as PrCCI;
use Limoncello\Contracts\Provider\ProvidesMiddlewareInterface as PrMI;
use Limoncello\Contracts\Provider\ProvidesRouteConfiguratorsInterface as PrRCI;
use Limoncello\Contracts\Provider\ProvidesSettingsInterface as PrSI;
use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Tests\Application\Data\CoreSettings\Middleware\PluginMiddleware;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * @package Limoncello\Tests\Application
 */
class Provider1 implements PrCCI, PrMI, PrRCI, PrSI, CCI, RCI, PrCmdI
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            static::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getMiddleware(): array
    {
        return [
            PluginMiddleware::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRouteConfigurators(): array
    {
        return [
            static::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getCommands(): array
    {
        return [
            DataCommand::class,
        ];
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container)
    {
        $container[static::class] = 'Hello container';
    }

    /**
     * @inheritdoc
     */
    public static function configureRoutes(GroupInterface $routes)
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

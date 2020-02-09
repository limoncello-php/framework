<?php declare(strict_types=1);

namespace Limoncello\Tests\Core\Application\Data;

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

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use Limoncello\Core\Application\BaseCoreData;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;

/**
 * @package Limoncello\Core
 */
class CoreData extends BaseCoreData
{
    /**
     * @var array
     */
    private $routerParameters = [
        self::KEY_ROUTER_PARAMS__GENERATOR  => GroupCountBasedGenerator::class,
        self::KEY_ROUTER_PARAMS__DISPATCHER => GroupCountBasedDispatcher::class,
    ];

    /**
     * @var array
     */
    private $routesData = [];

    /**
     * @var array
     */
    private $globalConfigurators = [];

    /**
     * @var array
     */
    private $globalMiddleware = [];

    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            self::KEY_ROUTER_PARAMS                  => $this->getRouterParameters(),
            self::KEY_ROUTES_DATA                    => $this->getRoutesData(),
            self::KEY_GLOBAL_CONTAINER_CONFIGURATORS => $this->getGlobalConfigurators(),
            self::KEY_GLOBAL_MIDDLEWARE              => $this->getGlobalMiddleware(),
        ];
    }

    /**
     * @return array
     */
    public function getRouterParameters(): array
    {
        return $this->routerParameters;
    }

    /**
     * @param array $routerParameters
     *
     * @return CoreData
     */
    public function setRouterParameters(array $routerParameters): CoreData
    {
        $this->routerParameters = $routerParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoutesData(): array
    {
        return $this->routesData;
    }

    /**
     * @param array $routesData
     *
     * @return CoreData
     */
    public function setRoutesData(array $routesData): CoreData
    {
        $this->routesData = $routesData;

        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalConfigurators(): array
    {
        return $this->globalConfigurators;
    }

    /**
     * @param array $globalConfigurators
     *
     * @return CoreData
     */
    public function setGlobalConfigurators(array $globalConfigurators): CoreData
    {
        $this->globalConfigurators = $globalConfigurators;

        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * @param array $globalMiddleware
     *
     * @return CoreData
     */
    public function setGlobalMiddleware(array $globalMiddleware): CoreData
    {
        $this->globalMiddleware = $globalMiddleware;

        return $this;
    }
}

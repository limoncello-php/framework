<?php namespace Limoncello\Core\Application;

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

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use Limoncello\Core\Contracts\Application\CoreSettingsInterface;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;

/**
 * @package Limoncello\Core
 */
class CoreSettings implements CoreSettingsInterface
{
    /**
     * @var array
     */
    private $routerParameters = [
        CoreSettings::KEY_ROUTER_PARAMS__GENERATOR  => GroupCountBasedGenerator::class,
        CoreSettings::KEY_ROUTER_PARAMS__DISPATCHER => GroupCountBasedDispatcher::class,
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
            CoreSettings::KEY_ROUTER_PARAMS                  => $this->getRouterParameters(),
            CoreSettings::KEY_ROUTES_DATA                    => $this->getRoutesData(),
            CoreSettings::KEY_GLOBAL_CONTAINER_CONFIGURATORS => $this->getGlobalConfigurators(),
            CoreSettings::KEY_GLOBAL_MIDDLEWARE              => $this->getGlobalMiddleware(),
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
     * @return CoreSettings
     */
    public function setRouterParameters(array $routerParameters): CoreSettings
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
     * @return CoreSettings
     */
    public function setRoutesData(array $routesData): CoreSettings
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
     * @return CoreSettings
     */
    public function setGlobalConfigurators(array $globalConfigurators): CoreSettings
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
     * @return CoreSettings
     */
    public function setGlobalMiddleware(array $globalMiddleware): CoreSettings
    {
        $this->globalMiddleware = $globalMiddleware;

        return $this;
    }
}

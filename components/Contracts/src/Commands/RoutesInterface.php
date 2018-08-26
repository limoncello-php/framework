<?php namespace Limoncello\Contracts\Commands;

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

/**
 * @package Limoncello\Contracts
 */
interface RoutesInterface
{
    /**
     * @param callable[] $middleware Middleware handlers.
     *
     * @return self
     */
    public function addGlobalMiddleware(array $middleware): self;

    /**
     * @param callable[] $configurators Container configurator handlers.
     *
     * @return self
     */
    public function addGlobalContainerConfigurators(array $configurators): self;

    /**
     * @param string     $name       Command name.
     * @param callable[] $middleware Middleware handlers.
     *
     * @return self
     */
    public function addCommandMiddleware(string $name, array $middleware): self;

    /**
     * @param string     $name          Command name.
     * @param callable[] $configurators Container configurator handlers.
     *
     * @return self
     */
    public function addCommandContainerConfigurators(string $name, array $configurators): self;
}

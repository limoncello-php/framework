<?php namespace Limoncello\Flute\Http\Traits;

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

use Interop\Container\ContainerInterface;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Adapters\RepositoryInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Models\ModelSchemesInterface;

/**
 * @package Limoncello\Flute
 */
trait CreateApiTrait
{
    /**
     * @param ContainerInterface $container
     * @param string             $apiClass
     *
     * @return CrudInterface
     */
    protected static function createApiByClass(ContainerInterface $container, $apiClass)
    {
        $factory            = $container->get(FactoryInterface::class);
        $repository         = $container->get(RepositoryInterface::class);
        $modelSchemes       = $container->get(ModelSchemesInterface::class);
        $paginationStrategy = $container->get(PaginationStrategyInterface::class);

        $api = new $apiClass($factory, $repository, $modelSchemes, $paginationStrategy, $container);

        return $api;
    }
}

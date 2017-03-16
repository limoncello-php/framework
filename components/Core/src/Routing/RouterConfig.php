<?php namespace Limoncello\Core\Routing;

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

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use Limoncello\Core\Config\ArrayConfig;
use Limoncello\Core\Contracts\Routing\RouterConfigInterface;
use Limoncello\Core\Routing\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;

/**
 * @package Limoncello\Core
 */
class RouterConfig extends ArrayConfig implements RouterConfigInterface
{
    const DEFAULTS = [
        RouterConfigInterface::KEY_GENERATOR  => GroupCountBasedGenerator::class,
        RouterConfigInterface::KEY_DISPATCHER => GroupCountBasedDispatcher::class,
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct([
            RouterConfigInterface::class => static::DEFAULTS,
        ]);
    }
}

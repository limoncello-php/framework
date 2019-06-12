<?php declare(strict_types=1);

namespace Limoncello\Core\Contracts;

/**
 * Copyright 2015-2019 info@neomerx.com
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
 * @package Limoncello\Core
 */
interface CoreDataInterface
{
    /** Settings key for router parameters */
    const KEY_ROUTER_PARAMS = 0;

    /** Settings key for router internal data generator */
    const KEY_ROUTER_PARAMS__GENERATOR = 0;

    /** Settings key for router dispatcher */
    const KEY_ROUTER_PARAMS__DISPATCHER = self::KEY_ROUTER_PARAMS__GENERATOR + 1;

    /** Settings key for routing data */
    const KEY_ROUTES_DATA = self::KEY_ROUTER_PARAMS + 1;

    /** Settings key for routing data */
    const KEY_GLOBAL_CONTAINER_CONFIGURATORS = self::KEY_ROUTES_DATA + 1;

    /** Settings key for routing data */
    const KEY_GLOBAL_MIDDLEWARE = self::KEY_GLOBAL_CONTAINER_CONFIGURATORS + 1;

    /** Special key which could be used by developers to safely add their own keys */
    const KEY_LAST = self::KEY_GLOBAL_MIDDLEWARE;
}

<?php declare(strict_types=1);

namespace Limoncello\Core\Application;

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

use FastRoute\DataGenerator;
use FastRoute\Dispatcher;
use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Core\Contracts\CoreDataInterface;
use function assert;
use function array_key_exists;

/**
 * @package Limoncello\Core
 */
abstract class BaseCoreData implements CoreDataInterface
{
    use ClassIsTrait;

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getRouterParametersFromData(array $data): array
    {
        assert(array_key_exists(self::KEY_ROUTER_PARAMS, $data));
        $result = $data[self::KEY_ROUTER_PARAMS];
        assert(empty($result) === false);

        return $result;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public static function getGeneratorFromParametersData(array $data): string
    {
        assert(array_key_exists(self::KEY_ROUTER_PARAMS__GENERATOR, $data));
        $result = $data[self::KEY_ROUTER_PARAMS__GENERATOR];
        assert(empty($result) === false);
        assert(static::classImplements($result, DataGenerator::class));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public static function getDispatcherFromParametersData(array $data): string
    {
        assert(array_key_exists(self::KEY_ROUTER_PARAMS__DISPATCHER, $data));
        $result = $data[self::KEY_ROUTER_PARAMS__DISPATCHER];
        assert(empty($result) === false);
        assert(static::classImplements($result, Dispatcher::class));

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getRoutesDataFromData(array $data): array
    {
        assert(array_key_exists(self::KEY_ROUTES_DATA, $data));
        $result = $data[self::KEY_ROUTES_DATA];

        return $result;
    }

    /**
     * @param array $data
     *
     * @return callable[]
     */
    public static function getGlobalConfiguratorsFromData(array $data): array
    {
        assert(array_key_exists(self::KEY_GLOBAL_CONTAINER_CONFIGURATORS, $data));
        $result = $data[self::KEY_GLOBAL_CONTAINER_CONFIGURATORS];

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function getGlobalMiddlewareFromData(array $data): array
    {
        assert(array_key_exists(self::KEY_GLOBAL_MIDDLEWARE, $data));
        $result = $data[self::KEY_GLOBAL_MIDDLEWARE];

        return $result;
    }
}

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

use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerCreateInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerDeleteInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerIndexInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerReadInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerUpdateInterface;
use Limoncello\Flute\Contracts\Http\ControllerInterface as CI;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * @package Limoncello\Flute
 */
trait FluteRoutesTrait
{
    /**
     * @param GroupInterface $group
     * @param string         $resourceName
     * @param string         $controllerClass
     *
     * @return GroupInterface
     */
    protected static function resource(
        GroupInterface $group,
        string $resourceName,
        string $controllerClass
    ): GroupInterface {
        assert(class_exists($controllerClass) === true);

        $groupPrefix = $group->getUriPrefix();
        $indexSlug   = '/{' . CI::ROUTE_KEY_INDEX . '}';
        $params      = function (string $method) use ($groupPrefix, $resourceName): array {
            return [RouteInterface::PARAM_NAME => static::routeName($groupPrefix, $resourceName, $method)];
        };
        $handler     = function (string $method) use ($controllerClass): array {
            return [$controllerClass, $method];
        };

        // if the class implements any of CRUD methods a corresponding route will be added
        $classInterfaces = class_implements($controllerClass);
        if (in_array(ControllerIndexInterface::class, $classInterfaces) === true) {
            $group->get($resourceName, $handler(CI::METHOD_INDEX), $params(CI::METHOD_INDEX));
        }
        if (in_array(ControllerCreateInterface::class, $classInterfaces) === true) {
            $group->post($resourceName, $handler(CI::METHOD_CREATE), $params(CI::METHOD_CREATE));
        }
        if (in_array(ControllerReadInterface::class, $classInterfaces) === true) {
            $group->get($resourceName . $indexSlug, $handler(CI::METHOD_READ), $params(CI::METHOD_READ));
        }
        if (in_array(ControllerUpdateInterface::class, $classInterfaces) === true) {
            $group->patch($resourceName . $indexSlug, $handler(CI::METHOD_UPDATE), $params(CI::METHOD_UPDATE));
        }
        if (in_array(ControllerDeleteInterface::class, $classInterfaces) === true) {
            $group->delete($resourceName . $indexSlug, $handler(CI::METHOD_DELETE), $params(CI::METHOD_DELETE));
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param string         $subUri
     * @param string         $controllerClass
     *
     * @return GroupInterface
     */
    protected static function controller(GroupInterface $group, string $subUri, string $controllerClass): GroupInterface
    {
        $groupPrefix = $group->getUriPrefix();
        $slugged     = $subUri . '/{' . CI::ROUTE_KEY_INDEX . '}';
        $params      = function (string $method) use ($groupPrefix, $subUri) : array {
            return [RouteInterface::PARAM_NAME => static::routeName($groupPrefix, $subUri, $method)];
        };
        $handler     = function (string $method) use ($controllerClass): array {
            return [$controllerClass, $method];
        };

        // if the class implements any of CRUD methods a corresponding route will be added
        // as HTML forms do not support methods other than GET/POST we use POST and special URI for update and delete.
        $classInterfaces = class_implements($controllerClass);
        if (in_array(ControllerIndexInterface::class, $classInterfaces) === true) {
            $group->get($subUri, $handler(CI::METHOD_INDEX), $params(CI::METHOD_INDEX));
        }
        if (in_array(ControllerCreateInterface::class, $classInterfaces) === true) {
            $group->post($subUri, $handler(CI::METHOD_CREATE), $params(CI::METHOD_CREATE));
        }
        if (in_array(ControllerReadInterface::class, $classInterfaces) === true) {
            $group->get($slugged, $handler(CI::METHOD_READ), $params(CI::METHOD_READ));
        }
        if (in_array(ControllerUpdateInterface::class, $classInterfaces) === true) {
            $updateUri = $slugged . '/' . CI::METHOD_UPDATE;
            $group->post($updateUri, $handler(CI::METHOD_UPDATE), $params(CI::METHOD_UPDATE));
        }
        if (in_array(ControllerDeleteInterface::class, $classInterfaces) === true) {
            $deleteUri = $slugged . '/' . CI::METHOD_DELETE;
            $group->post($deleteUri, $handler(CI::METHOD_DELETE), $params(CI::METHOD_DELETE));
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param string         $resourceName
     * @param string         $relationshipName
     * @param string         $controllerClass
     * @param string         $selfGetMethod
     *
     * @return GroupInterface
     */
    protected static function relationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $selfGetMethod
    ): GroupInterface {
        /** @var string $controllerClass */
        /** @var string $schemaClass */

        $resourceIdUri = $resourceName . '/{' . CI::ROUTE_KEY_INDEX . '}/';
        $selfUri       = $resourceIdUri . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group
            // `self`
            ->get($selfUri, [$controllerClass, $selfGetMethod])
            // `related`
            ->get($resourceIdUri . $relationshipName, [$controllerClass, $selfGetMethod]);
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param string $method
     *
     * @return string
     */
    protected static function routeName(string $prefix, string $name, string $method): string
    {
        assert(empty($name) === false && empty($method) === false);

        return $prefix . '/' . $name . '::' . $method;
    }
}

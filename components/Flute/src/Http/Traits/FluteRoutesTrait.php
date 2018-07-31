<?php namespace Limoncello\Flute\Http\Traits;

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

use Limoncello\Contracts\Routing\GroupInterface;
use Limoncello\Contracts\Routing\RouteInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerCreateInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerDeleteInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerIndexInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerInstanceInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerReadInterface;
use Limoncello\Flute\Contracts\Http\Controller\ControllerUpdateInterface;
use Limoncello\Flute\Contracts\Http\JsonApiControllerInterface as JCI;
use Limoncello\Flute\Contracts\Http\WebControllerInterface as FCI;
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
    protected static function apiController(
        GroupInterface $group,
        string $resourceName,
        string $controllerClass
    ): GroupInterface {
        assert(class_exists($controllerClass) === true);

        $groupPrefix = $group->getUriPrefix();
        $indexSlug   = '/{' . JCI::ROUTE_KEY_INDEX . '}';
        $params      = function (string $method) use ($groupPrefix, $resourceName): array {
            return [RouteInterface::PARAM_NAME => static::routeName($groupPrefix, $resourceName, $method)];
        };
        $handler     = function (string $method) use ($controllerClass): array {
            return [$controllerClass, $method];
        };

        // if the class implements any of CRUD methods a corresponding route will be added
        $classInterfaces = class_implements($controllerClass);
        if (in_array(ControllerIndexInterface::class, $classInterfaces) === true) {
            $group->get($resourceName, $handler(JCI::METHOD_INDEX), $params(JCI::METHOD_INDEX));
        }
        if (in_array(ControllerCreateInterface::class, $classInterfaces) === true) {
            $group->post($resourceName, $handler(JCI::METHOD_CREATE), $params(JCI::METHOD_CREATE));
        }
        if (in_array(ControllerReadInterface::class, $classInterfaces) === true) {
            $group->get($resourceName . $indexSlug, $handler(JCI::METHOD_READ), $params(JCI::METHOD_READ));
        }
        if (in_array(ControllerUpdateInterface::class, $classInterfaces) === true) {
            $group->patch($resourceName . $indexSlug, $handler(JCI::METHOD_UPDATE), $params(JCI::METHOD_UPDATE));
        }
        if (in_array(ControllerDeleteInterface::class, $classInterfaces) === true) {
            $group->delete($resourceName . $indexSlug, $handler(JCI::METHOD_DELETE), $params(JCI::METHOD_DELETE));
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param string         $subUri
     * @param string         $controllerClass
     * @param string         $createSubUrl
     *
     * @return GroupInterface
     */
    protected static function webController(
        GroupInterface $group,
        string $subUri,
        string $controllerClass,
        string $createSubUrl = '/create'
    ): GroupInterface {
        // normalize url to have predictable URLs and their names
        if ($subUri[-1] === '/') {
            $subUri = substr($subUri, 0, -1);
        }

        $groupPrefix = $group->getUriPrefix();
        $slugged     = $subUri . '/{' . FCI::ROUTE_KEY_INDEX . '}';
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
            $group->get($subUri, $handler(FCI::METHOD_INDEX), $params(FCI::METHOD_INDEX));
        }
        if (in_array(ControllerInstanceInterface::class, $classInterfaces) === true) {
            $group->get($subUri . $createSubUrl, $handler(FCI::METHOD_INSTANCE), $params(FCI::METHOD_INSTANCE));
        }
        if (in_array(ControllerCreateInterface::class, $classInterfaces) === true) {
            $group->post($subUri . $createSubUrl, $handler(FCI::METHOD_CREATE), $params(FCI::METHOD_CREATE));
        }
        if (in_array(ControllerReadInterface::class, $classInterfaces) === true) {
            $group->get($slugged, $handler(FCI::METHOD_READ), $params(FCI::METHOD_READ));
        }
        if (in_array(ControllerUpdateInterface::class, $classInterfaces) === true) {
            $group->post($slugged, $handler(FCI::METHOD_UPDATE), $params(FCI::METHOD_UPDATE));
        }
        if (in_array(ControllerDeleteInterface::class, $classInterfaces) === true) {
            $deleteUri = $slugged . '/' . FCI::METHOD_DELETE;
            $group->post($deleteUri, $handler(FCI::METHOD_DELETE), $params(FCI::METHOD_DELETE));
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
        $resourceIdUri = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/';
        $selfUri       = $resourceIdUri . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group
            // `self`
            ->get($selfUri, [$controllerClass, $selfGetMethod])
            // `related`
            ->get($resourceIdUri . $relationshipName, [$controllerClass, $selfGetMethod]);
    }

    /**
     * @param GroupInterface $group
     * @param string         $resourceName
     * @param string         $relationshipName
     * @param string         $controllerClass
     * @param string         $addMethod
     *
     * @return GroupInterface
     */
    protected static function addInRelationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $addMethod
    ): GroupInterface {
        $url = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/' .
            DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group->post($url, [$controllerClass, $addMethod]);
    }

    /**
     * @param GroupInterface $group
     * @param string         $resourceName
     * @param string         $relationshipName
     * @param string         $controllerClass
     * @param string         $deleteMethod
     *
     * @return GroupInterface
     */
    protected static function removeInRelationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $deleteMethod
    ): GroupInterface {
        $url = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/' .
            DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group->delete($url, [$controllerClass, $deleteMethod]);
    }

    /**
     * @param string $prefix
     * @param string $subUri
     * @param string $method
     *
     * @return string
     */
    protected static function routeName(string $prefix, string $subUri, string $method): string
    {
        assert(empty($method) === false);

        // normalize prefix and url to have predictable name

        if (empty($prefix) === true || $prefix[-1] !== '/') {
            $prefix .= '/';
        }

        if (empty($subUri) === false && $subUri[-1] === '/') {
            $subUri = substr($subUri, 0, -1);
        }

        return $prefix . $subUri . '::' . $method;
    }
}

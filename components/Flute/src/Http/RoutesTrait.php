<?php namespace Limoncello\Flute\Http;

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
use Limoncello\Flute\Contracts\Http\ControllerInterface as CI;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * @package Limoncello\Flute
 */
trait RoutesTrait
{
    /**
     * @param GroupInterface $group
     * @param string         $controllerClass
     *
     * @return void
     */
    protected function resource(GroupInterface $group, string $controllerClass)
    {
        /** @var BaseController $controllerClass */
        assert(array_key_exists(BaseController::class, class_parents($controllerClass)) === true);
        $schemaClass = $controllerClass::SCHEMA_CLASS;

        /** @var SchemaInterface $schemaClass */
        assert(array_key_exists(SchemaInterface::class, class_implements($schemaClass)) === true);
        $subUri = $type = $schemaClass::TYPE;

        $indexSlug = '/{' . BaseController::ROUTE_KEY_INDEX . '}';
        $params    = function ($method) use ($type) {
            return [RouteInterface::PARAM_NAME => $type . '_' . $method];
        };
        $handler   = function ($method) use ($controllerClass) {
            return [$controllerClass, $method];
        };

        $group
            ->get($subUri, $handler(CI::METHOD_INDEX), $params(CI::METHOD_INDEX))
            ->post($subUri, $handler(CI::METHOD_CREATE), $params(CI::METHOD_CREATE))
            ->get($subUri . $indexSlug, $handler(CI::METHOD_READ), $params(CI::METHOD_READ))
            ->patch($subUri . $indexSlug, $handler(CI::METHOD_UPDATE), $params(CI::METHOD_UPDATE))
            ->delete($subUri . $indexSlug, $handler(CI::METHOD_DELETE), $params(CI::METHOD_DELETE));
    }

    /**
     * @param GroupInterface $group
     * @param string         $relationshipName
     * @param string         $controllerClass
     * @param string         $selfGetMethod
     *
     * @return void
     */
    protected function relationship(
        GroupInterface $group,
        string $relationshipName,
        string $controllerClass,
        string $selfGetMethod
    ) {
        /** @var BaseController $controllerClass */
        assert(array_key_exists(BaseController::class, class_parents($controllerClass)) === true);
        $schemaClass = $controllerClass::SCHEMA_CLASS;

        /** @var SchemaInterface $schemaClass */
        assert(array_key_exists(SchemaInterface::class, class_implements($schemaClass)) === true);
        $subUri      = $schemaClass::TYPE;

        /** @var string $controllerClass */
        /** @var string $schemaClass */

        $resourceIdUri = $subUri . '/{' . BaseController::ROUTE_KEY_INDEX . '}/';

        // `self`
        $selfUri = $resourceIdUri . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;
        $group->get($selfUri, [$controllerClass, $selfGetMethod]);

        // `related`
        $group->get($resourceIdUri . $relationshipName, [$controllerClass, $selfGetMethod]);
    }
}

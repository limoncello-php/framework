<?php namespace Limoncello\Tests\Flute\Data\Http;

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
use Limoncello\Tests\Flute\Data\Api\CategoriesApi as Api;
use Limoncello\Tests\Flute\Data\Schemes\CategorySchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class CategoriesController extends BaseController
{
    /** @inheritdoc */
    const API_CLASS = Api::class;

    /** @inheritdoc */
    const SCHEMA_CLASS = Schema::class;

    /**
     * @inheritdoc
     */
    public static function parseInputOnCreate(ContainerInterface $container, ServerRequestInterface $request)
    {
        $json   = static::parseJson($container, $request);
        $schema = static::getSchema($container);

        /** @var AppValidator $validator */
        $validator = $container->get(AppValidator::class);

        $idRule         = $validator->absentOrNull();
        $attributeRules = [
            Schema::ATTR_NAME => $validator->requiredText(),
        ];
        $toOneRules     = [
            Schema::REL_PARENT => $validator->optionalCategoryId(),
        ];
        $toManyRules    = [];

        list ($idCapture, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $json, $idRule, $attributeRules, $toOneRules, $toManyRules);

        return [$idCapture, $attrCaptures, $toManyCaptures];
    }

    /**
     * @inheritdoc
     */
    public static function parseInputOnUpdate($index, ContainerInterface $container, ServerRequestInterface $request)
    {
        $json   = static::parseJson($container, $request);
        $schema = static::getSchema($container);

        /** @var AppValidator $validator */
        $validator = $container->get(AppValidator::class);

        $idRule         = $validator->idEquals($index);
        $attributeRules = [
            Schema::ATTR_NAME => $validator->optionalText(),
        ];
        $toOneRules     = [
            Schema::REL_PARENT => $validator->optionalCategoryId(),
        ];
        $toManyRules    = [];

        list (, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $json, $idRule, $attributeRules, $toOneRules, $toManyRules);

        return [$attrCaptures, $toManyCaptures];
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readChildren(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ) {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_CHILDREN, $container, $request);
    }
}

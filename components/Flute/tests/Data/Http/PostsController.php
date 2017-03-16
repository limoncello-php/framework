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
use Limoncello\Tests\Flute\Data\Api\CommentsApi;
use Limoncello\Tests\Flute\Data\Api\PostsApi as Api;
use Limoncello\Tests\Flute\Data\Models\Post as Model;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class PostsController extends BaseController
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
            Schema::ATTR_TITLE => $validator->requiredText(Model::getAttributeLengths()[Model::FIELD_TITLE]),
            Schema::ATTR_TEXT  => $validator->requiredText(Model::getAttributeLengths()[Model::FIELD_TEXT]),
        ];

        list ($idCapture, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $json, $idRule, $attributeRules);

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
            Schema::ATTR_TITLE => $validator->optionalText(Model::getAttributeLengths()[Model::FIELD_TITLE]),
            Schema::ATTR_TEXT  => $validator->optionalText(Model::getAttributeLengths()[Model::FIELD_TEXT]),
        ];

        list (, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $json, $idRule, $attributeRules);

        return [$attrCaptures, $toManyCaptures];
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readComments(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ) {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_COMMENTS, $container, $request);
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function updateComment(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ) {
        $index        = $routeParams[static::ROUTE_KEY_INDEX];
        $commentIndex = $routeParams[static::ROUTE_KEY_CHILD_INDEX];
        list ($attributes, $toMany) = CommentsController::parseInputOnUpdate($commentIndex, $container, $request);

        $response = static::updateInRelationship(
            $index,
            Schema::REL_COMMENTS,
            $commentIndex,
            $attributes,
            $toMany,
            CommentsApi::class,
            $container,
            $request
        );

        return $response;
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function deleteComment(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ) {
        $index        = $routeParams[static::ROUTE_KEY_INDEX];
        $commentIndex = $routeParams[static::ROUTE_KEY_CHILD_INDEX];

        $response = static::deleteInRelationship(
            $index,
            Schema::REL_COMMENTS,
            $commentIndex,
            CommentsApi::class,
            $container,
            $request
        );

        return $response;
    }
}

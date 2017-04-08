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
use Limoncello\Tests\Flute\Data\Api\CommentsApi as Api;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class CommentsController extends BaseController
{
    /** @inheritdoc */
    const API_CLASS = Api::class;

    /** @inheritdoc */
    const SCHEMA_CLASS = Schema::class;

    /**
     * @inheritdoc
     */
    public static function parseInputOnCreate(
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $json   = static::parseJson($container, $request);
        $schema = static::getSchema($container);

        /** @var AppValidator $validator */
        $validator = $container->get(AppValidator::class);

        $idRule         = $validator->absentOrNull();
        $attributeRules = [
            Schema::ATTR_TEXT => $validator->requiredText(),
        ];
        $toOneRules     = [
            Schema::REL_POST => $validator->requiredPostId(),
        ];
        $toManyRules    = [
            Schema::REL_EMOTIONS => $validator->optionalEmotionId(),
        ];

        list ($idCapture, $attrCaptures, $toManyCaptures) =
            $validator->assert($schema, $json, $idRule, $attributeRules, $toOneRules, $toManyRules);

        return [$idCapture, $attrCaptures, $toManyCaptures];
    }

    /**
     * @inheritdoc
     */
    public static function parseInputOnUpdate(
        $index,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $json   = static::parseJson($container, $request);
        $schema = static::getSchema($container);

        /** @var AppValidator $validator */
        $validator = $container->get(AppValidator::class);

        $idRule         = $validator->idEquals($index);
        $attributeRules = [
            Schema::ATTR_TEXT => $validator->optionalText(),
        ];
        $toOneRules     = [
            Schema::REL_POST => $validator->optionalPostId(),
        ];
        $toManyRules    = [
            Schema::REL_EMOTIONS => $validator->optionalEmotionId(),
        ];

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
    public static function readEmotions(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_EMOTIONS, $container, $request);
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readEmotionsIdentifiers(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationshipIdentifiers($index, Schema::REL_EMOTIONS, $container, $request);
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readUser(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_USER, $container, $request);
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readPost(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_POST, $container, $request);
    }
}

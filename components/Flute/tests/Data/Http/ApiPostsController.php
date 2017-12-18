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

use Limoncello\Tests\Flute\Data\Api\CommentsApi;
use Limoncello\Tests\Flute\Data\Api\PostsApi as Api;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema;
use Limoncello\Tests\Flute\Data\Schemes\PostSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\JsonRuleSets\UpdateCommentRuleSet;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class ApiPostsController extends ApiBaseController
{
    /** @inheritdoc */
    const API_CLASS = Api::class;

    /** @inheritdoc */
    const SCHEMA_CLASS = Schema::class;

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function readComments(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_COMMENTS, $container, $request);
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function updateComment(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index        = $routeParams[static::ROUTE_KEY_INDEX];
        $commentIndex = $routeParams[static::ROUTE_KEY_CHILD_INDEX];

        $captures = static::createJsonApiValidator($container, UpdateCommentRuleSet::class)
            ->assert(static::readJsonFromRequest($container, $request))
            ->getJsonApiCaptures();

        list (, $attributes, $toMany) = static::mapSchemeDataToModelData($container, $captures, CommentSchema::class);

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
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function deleteComment(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
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

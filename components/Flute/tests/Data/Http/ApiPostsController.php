<?php declare (strict_types = 1);

namespace Limoncello\Tests\Flute\Data\Http;

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

use Limoncello\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Limoncello\Tests\Flute\Data\Api\PostsApi as Api;
use Limoncello\Tests\Flute\Data\Models\Post as Model;
use Limoncello\Tests\Flute\Data\Schemas\PostSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\JsonData\UpdatePostRules;
use Limoncello\Tests\Flute\Data\Validation\JsonQueries\ReadPostsQueryRules;
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

    /** @inheritdoc */
    const ON_READ_QUERY_VALIDATION_RULES_CLASS = ReadPostsQueryRules::class;

    /** @inheritdoc */
    const ON_UPDATE_DATA_VALIDATION_RULES_CLASS = UpdatePostRules::class;

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
        return static::readRelationship(
            (string)$routeParams[static::ROUTE_KEY_INDEX],
            Model::REL_COMMENTS,
            DefaultQueryValidationRules::class,
            $container,
            $request
        );
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function replaceEditor(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $response = static::replaceInRelationship(
            (string)$routeParams[static::ROUTE_KEY_INDEX],
            Schema::REL_EDITOR,
            Model::REL_EDITOR,
            $container,
            $request
        );

        return $response;
    }
}

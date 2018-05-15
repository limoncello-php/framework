<?php namespace Limoncello\Tests\Flute\Data\Http;

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

use Limoncello\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Limoncello\Tests\Flute\Data\Api\CategoriesApi as Api;
use Limoncello\Tests\Flute\Data\Models\Category as Model;
use Limoncello\Tests\Flute\Data\Schemas\CategorySchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\JsonQueries\ReadCategoriesQueryRules;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class ApiCategoriesController extends ApiBaseController
{
    /** @inheritdoc */
    const API_CLASS = Api::class;

    /** @inheritdoc */
    const SCHEMA_CLASS = Schema::class;

    /** @inheritdoc */
    const ON_INDEX_QUERY_VALIDATION_RULES_CLASS = ReadCategoriesQueryRules::class;

    /** @inheritdoc */
    const ON_READ_QUERY_VALIDATION_RULES_CLASS = ReadCategoriesQueryRules::class;

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
    public static function readChildren(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        return static::readRelationship(
            $routeParams[static::ROUTE_KEY_INDEX],
            Model::REL_CHILDREN,
            DefaultQueryValidationRules::class,
            $container,
            $request
        );
    }
}

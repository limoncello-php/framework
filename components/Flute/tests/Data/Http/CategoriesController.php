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

use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Tests\Flute\Data\Api\CategoriesApi as Api;
use Limoncello\Tests\Flute\Data\Schemes\CategorySchema as Schema;
use Psr\Container\ContainerInterface;
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
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_CHILDREN, $container, $request);
    }

    /**
     * @inheritdoc
     */
    protected static function configureOnIndexParser(QueryParserInterface $parser): QueryParserInterface
    {
        $parser = parent::configureOnIndexParser($parser);

        self::configureParser($parser);

        return $parser;
    }

    /**
     * @inheritdoc
     */
    protected static function configureOnReadParser(QueryParserInterface $parser): QueryParserInterface
    {
        $parser = parent::configureOnReadParser($parser);

        self::configureParser($parser);

        return $parser;
    }

    /**
     * @param QueryParserInterface $parser
     */
    private static function configureParser(QueryParserInterface $parser): void
    {
        $parser
            ->withAllowedIncludePaths([
                Schema::REL_PARENT,
                Schema::REL_CHILDREN,
            ]);
    }
}

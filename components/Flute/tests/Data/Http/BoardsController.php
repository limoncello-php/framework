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

use Limoncello\Tests\Flute\Data\Api\BoardsApi as Api;
use Limoncello\Tests\Flute\Data\Models\Board as Model;
use Limoncello\Tests\Flute\Data\Schemes\BoardSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class BoardsController extends BaseController
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
        $validator = new class ($container) extends AppValidator
        {
            /**
             * @inheritdoc
             */
            public function __construct(ContainerInterface $container)
            {
                parent::__construct($container, Schema::TYPE, [
                    self::RULE_INDEX      => $this->absentOrNull(),
                    self::RULE_ATTRIBUTES => [
                        Schema::ATTR_TITLE => $this->requiredText(Model::getAttributeLengths()[Model::FIELD_TITLE]),
                    ]
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID,
            [Model::FIELD_TITLE]
        );
    }

    /**
     * @inheritdoc
     */
    public static function parseInputOnUpdate(
        $index,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $validator = new class ($container, $index) extends AppValidator
        {
            /**
             * @inheritdoc
             */
            public function __construct(ContainerInterface $container, $index)
            {
                parent::__construct($container, Schema::TYPE, [
                    AppValidator::RULE_INDEX      => $this->idEquals($index),
                    AppValidator::RULE_ATTRIBUTES => [
                        Schema::ATTR_TITLE => $this->optionalText(Model::getAttributeLengths()[Model::FIELD_TITLE]),
                    ]
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID,
            [Model::FIELD_TITLE]
        );
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public static function readPosts(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        return static::readRelationship($routeParams[static::ROUTE_KEY_INDEX], Schema::REL_POSTS, $container, $request);
    }
}

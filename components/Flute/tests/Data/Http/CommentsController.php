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

use Limoncello\Tests\Flute\Data\Api\CommentsApi as Api;
use Limoncello\Tests\Flute\Data\Models\Comment as Model;
use Limoncello\Tests\Flute\Data\Schemes\CommentSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Container\ContainerInterface;
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
                        Schema::ATTR_TEXT => $this->requiredText(),
                    ],
                    self::RULE_TO_ONE     => [
                        Schema::REL_POST => $this->requiredPostId(),
                    ],
                    self::RULE_TO_MANY    => [
                        Schema::REL_EMOTIONS => $this->optionalEmotionId(),
                    ],
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID,
            [Model::FIELD_TEXT, Model::FIELD_ID_POST],
            [Model::REL_EMOTIONS]
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
                        Schema::ATTR_TEXT => $this->optionalText(),
                    ],
                    AppValidator::RULE_TO_ONE     => [
                        Schema::REL_POST => $this->optionalPostId(),
                    ],
                    AppValidator::RULE_TO_MANY    => [
                        Schema::REL_EMOTIONS => $this->optionalEmotionId(),
                    ],
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID,
            [Model::FIELD_TEXT, Model::FIELD_ID_POST],
            [Model::REL_EMOTIONS]
        );
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

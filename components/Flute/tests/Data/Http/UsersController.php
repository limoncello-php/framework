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

use Limoncello\Tests\Flute\Data\Api\UsersApi as Api;
use Limoncello\Tests\Flute\Data\Models\User as Model;
use Limoncello\Tests\Flute\Data\Schemes\UserSchema as Schema;
use Limoncello\Tests\Flute\Data\Validation\AppValidator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Tests\Flute
 */
class UsersController extends BaseController
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
                $lengths = Model::getAttributeLengths();
                parent::__construct($container, Schema::TYPE, [
                    self::RULE_INDEX      => $this->absentOrNull(),
                    self::RULE_ATTRIBUTES => [
                        Schema::ATTR_TITLE      => $this->requiredText($lengths[Model::FIELD_TITLE]),
                        Schema::ATTR_FIRST_NAME => $this->requiredText($lengths[Model::FIELD_FIRST_NAME]),
                        Schema::ATTR_LAST_NAME  => $this->requiredText($lengths[Model::FIELD_LAST_NAME]),
                        Schema::ATTR_EMAIL      => $this->requiredText($lengths[Model::FIELD_EMAIL]),
                        Schema::ATTR_LANGUAGE   => $this->requiredText($lengths[Model::FIELD_LANGUAGE]),
                    ],
                    self::RULE_TO_ONE     => [
                        Schema::REL_ROLE => $this->requiredRoleId(),
                    ],
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID, [
                Model::FIELD_TITLE,
                Model::FIELD_FIRST_NAME,
                Model::FIELD_LAST_NAME,
                Model::FIELD_EMAIL,
                Model::FIELD_LANGUAGE,
            ],
            [Model::REL_ROLE]
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
        $validator = new class ($container) extends AppValidator
        {
            /**
             * @inheritdoc
             */
            public function __construct(ContainerInterface $container)
            {
                $lengths = Model::getAttributeLengths();
                parent::__construct($container, Schema::TYPE, [
                    self::RULE_INDEX      => $this->absentOrNull(),
                    self::RULE_ATTRIBUTES => [
                        Schema::ATTR_TITLE      => $this->optionalText($lengths[Model::FIELD_TITLE]),
                        Schema::ATTR_FIRST_NAME => $this->optionalText($lengths[Model::FIELD_FIRST_NAME]),
                        Schema::ATTR_LAST_NAME  => $this->optionalText($lengths[Model::FIELD_LAST_NAME]),
                        Schema::ATTR_EMAIL      => $this->optionalText($lengths[Model::FIELD_EMAIL]),
                        Schema::ATTR_LANGUAGE   => $this->optionalText($lengths[Model::FIELD_LANGUAGE]),
                    ],
                    self::RULE_TO_ONE     => [
                        Schema::REL_ROLE => $this->requiredRoleId(),
                    ],
                ]);
            }
        };

        return static::prepareCaptures(
            $validator->assert(static::parseJson($container, $request))->getCaptures(),
            Model::FIELD_ID, [
            Model::FIELD_TITLE,
            Model::FIELD_FIRST_NAME,
            Model::FIELD_LAST_NAME,
            Model::FIELD_EMAIL,
            Model::FIELD_LANGUAGE,
        ],
            [Model::REL_ROLE]
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
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::readRelationship($index, Schema::REL_COMMENTS, $container, $request);
    }
}

<?php namespace Limoncello\Flute\Http;

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

use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\JsonApiControllerInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Http\Traits\DefaultControllerMethodsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 */
abstract class JsonApiBaseController implements JsonApiControllerInterface
{
    use DefaultControllerMethodsTrait;

    /** API class name */
    const API_CLASS = null;

    /** JSON API Schema class name */
    const SCHEMA_CLASS = null;

    /** JSON API query validation rules class */
    const ON_INDEX_QUERY_VALIDATION_RULES_CLASS = null;

    /** JSON API query validation rules class */
    const ON_READ_QUERY_VALIDATION_RULES_CLASS = null;

    /** JSON API data validation rules class */
    const ON_CREATE_DATA_VALIDATION_RULES_CLASS = null;

    /** JSON API data validation rules class */
    const ON_UPDATE_DATA_VALIDATION_RULES_CLASS = null;

    /**
     * @inheritdoc
     */
    public static function index(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_INDEX_QUERY_VALIDATION_RULES_CLASS);

        return static::defaultIndexHandler(
            $request->getQueryParams(),
            $request->getUri(),
            static::defaultCreateQueryParser($container, static::ON_INDEX_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateParameterMapper($container, static::SCHEMA_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class)
        );
    }

    /**
     * @inheritdoc
     */
    public static function create(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_CREATE_DATA_VALIDATION_RULES_CLASS);

        $response = static::defaultCreateHandler(
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemaInfoInterface::class),
            static::defaultCreateDataParser($container, static::ON_CREATE_DATA_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );

        return $response;
    }

    /**
     * @inheritdoc
     */
    public static function read(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_READ_QUERY_VALIDATION_RULES_CLASS);

        return static::defaultReadHandler(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getQueryParams(),
            $request->getUri(),
            static::defaultCreateQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateParameterMapper($container, static::SCHEMA_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class)
        );
    }

    /**
     * @inheritdoc
     */
    public static function update(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS);

        $response = static::defaultUpdateHandler(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemaInfoInterface::class),
            static::defaultCreateDataParser($container, static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );

        return $response;
    }

    /**
     * @inheritdoc
     */
    public static function delete(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);

        $response = static::defaultDeleteHandler(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getUri(),
            static::defaultCreateQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class)
        );

        return $response;
    }

    /**
     * @param string                 $index
     * @param string                 $modelRelName
     * @param string                 $queryValRulesClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function readRelationship(
        string $index,
        string $modelRelName,
        string $queryValRulesClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);

        $api     = static::defaultCreateApi($container, static::API_CLASS);
        $handler = function () use ($api, $index, $modelRelName) {
            return $api->readRelationship($index, $modelRelName);
        };

        return static::defaultReadRelationshipWithClosureHandler(
            $index,
            $handler,
            $request->getQueryParams(),
            $request->getUri(),
            static::defaultCreateQueryParser($container, $queryValRulesClass),
            static::defaultCreateParameterMapper($container, static::SCHEMA_CLASS),
            $api,
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class)
        );
    }

    /**
     * @param string                 $index
     * @param string                 $modelRelName
     * @param string                 $queryValRulesClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function readRelationshipIdentifiers(
        string $index,
        string $modelRelName,
        string $queryValRulesClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);

        $api     = static::defaultCreateApi($container, static::API_CLASS);
        $handler = function () use ($api, $index, $modelRelName) {
            return $api->readRelationship($index, $modelRelName);
        };

        return static::defaultReadRelationshipIdentifiersWithClosureHandler(
            $index,
            $handler,
            $request->getQueryParams(),
            $request->getUri(),
            static::defaultCreateQueryParser($container, $queryValRulesClass),
            static::defaultCreateParameterMapper($container, static::SCHEMA_CLASS),
            $api,
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class)
        );
    }

    /**
     * @param string                 $parentIndex
     * @param string                 $jsonRelName
     * @param string                 $modelRelName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected static function addInRelationship(
        string $parentIndex,
        string $jsonRelName,
        string $modelRelName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_READ_QUERY_VALIDATION_RULES_CLASS);
        static::assertClassValueDefined(static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS);

        return static::defaultAddInRelationshipHandler(
            $parentIndex,
            $jsonRelName,
            $modelRelName,
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemaInfoInterface::class),
            static::defaultCreateQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateDataParser($container, static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );
    }

    /**
     * @param string                 $parentIndex
     * @param string                 $jsonRelName
     * @param string                 $modelRelName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected static function deleteInRelationship(
        string $parentIndex,
        string $jsonRelName,
        string $modelRelName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_READ_QUERY_VALIDATION_RULES_CLASS);
        static::assertClassValueDefined(static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS);

        return static::defaultDeleteInRelationshipHandler(
            $parentIndex,
            $jsonRelName,
            $modelRelName,
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemaInfoInterface::class),
            static::defaultCreateQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateDataParser($container, static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );
    }

    /**
     * @param string                 $parentIndex
     * @param string                 $jsonRelName
     * @param string                 $modelRelName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected static function replaceInRelationship(
        string $parentIndex,
        string $jsonRelName,
        string $modelRelName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);
        static::assertClassValueDefined(static::SCHEMA_CLASS);
        static::assertClassValueDefined(static::ON_READ_QUERY_VALIDATION_RULES_CLASS);
        static::assertClassValueDefined(static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS);

        return static::defaultReplaceInRelationship(
            $parentIndex,
            $jsonRelName,
            $modelRelName,
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemaInfoInterface::class),
            static::defaultCreateQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::defaultCreateDataParser($container, static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS),
            static::defaultCreateApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemasInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );
    }
}

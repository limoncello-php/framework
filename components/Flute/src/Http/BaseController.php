<?php namespace Limoncello\Flute\Http;

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

use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\ControllerInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Http\Traits\DefaultControllerMethodsTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 */
abstract class BaseController implements ControllerInterface
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

        return static::defaultIndex(
            $request->getQueryParams(),
            $request->getUri(),
            static::createQueryParser($container, static::ON_INDEX_QUERY_VALIDATION_RULES_CLASS),
            static::createParameterMapper($container, static::SCHEMA_CLASS),
            static::createApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        $response = static::defaultCreate(
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemeInfoInterface::class),
            static::createDataParser($container, static::ON_CREATE_DATA_VALIDATION_RULES_CLASS),
            static::createApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        return static::defaultRead(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getQueryParams(),
            $request->getUri(),
            static::createQueryParser($container, static::ON_READ_QUERY_VALIDATION_RULES_CLASS),
            static::createParameterMapper($container, static::SCHEMA_CLASS),
            static::createApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        $response = static::defaultUpdate(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getUri(),
            $request->getBody(),
            static::SCHEMA_CLASS,
            $container->get(ModelSchemeInfoInterface::class),
            static::createDataParser($container, static::ON_UPDATE_DATA_VALIDATION_RULES_CLASS),
            static::createApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        $response = static::defaultDelete(
            $routeParams[static::ROUTE_KEY_INDEX],
            $request->getUri(),
            static::createApi($container, static::API_CLASS),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        $api     = static::createApi($container, static::API_CLASS);
        $handler = function () use ($api, $index, $modelRelName) {
            return $api->readRelationship($index, $modelRelName);
        };

        return static::defaultReadRelationshipWithClosure(
            $handler,
            $request->getQueryParams(),
            $request->getUri(),
            static::createQueryParser($container, $queryValRulesClass),
            static::createParameterMapper($container, static::SCHEMA_CLASS),
            $api,
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
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

        $api     = static::createApi($container, static::API_CLASS);
        $handler = function () use ($api, $index, $modelRelName) {
            return $api->readRelationship($index, $modelRelName);
        };

        return static::defaultReadRelationshipIdentifiersWithClosure(
            $handler,
            $request->getQueryParams(),
            $request->getUri(),
            static::createQueryParser($container, $queryValRulesClass),
            static::createParameterMapper($container, static::SCHEMA_CLASS),
            $api,
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
            $container->get(EncoderInterface::class)
        );
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param                        $parentIndex
     * @param string                 $modelRelName
     * @param                        $childIndex
     * @param string                 $childSchemaClass
     * @param string                 $childValidatorClass
     * @param string                 $childApiClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function updateInRelationship(
        $parentIndex,
        string $modelRelName,
        string $childIndex,
        string $childSchemaClass,
        string $childValidatorClass,
        string $childApiClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);

        $response = static::defaultUpdateInRelationship(
            $parentIndex,
            $modelRelName,
            $childIndex,
            $request->getUri(),
            $request->getBody(),
            $childSchemaClass,
            $container->get(ModelSchemeInfoInterface::class),
            static::createDataParser($container, $childValidatorClass),
            static::createApi($container, static::API_CLASS),
            static::createApi($container, $childApiClass),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
            $container->get(EncoderInterface::class),
            $container->get(FactoryInterface::class),
            $container->get(FormatterFactoryInterface::class)
        );

        return $response;
    }

    /**
     * @param string                 $parentIndex
     * @param string                 $modelRelName
     * @param string                 $childIndex
     * @param string                 $childApiClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function deleteInRelationship(
        string $parentIndex,
        string $modelRelName,
        string $childIndex,
        string $childApiClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::assertClassValueDefined(static::API_CLASS);

        return static::defaultDeleteInRelationship(
            $parentIndex,
            $modelRelName,
            $childIndex,
            $request->getUri(),
            static::createApi($container, static::API_CLASS),
            static::createApi($container, $childApiClass),
            $container->get(SettingsProviderInterface::class),
            $container->get(JsonSchemesInterface::class),
            $container->get(EncoderInterface::class)
        );
    }
}

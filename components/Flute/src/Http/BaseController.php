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

use Closure;
use Limoncello\Contracts\Application\ModelInterface;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\ControllerInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorInterface;
use Limoncello\Flute\Http\Traits\CreateResponsesTrait;
use Limoncello\Flute\L10n\Messages;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as DI;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class BaseController implements ControllerInterface
{
    use CreateResponsesTrait;

    /** API class name */
    const API_CLASS = null;

    /** JSON API Schema class name */
    const SCHEMA_CLASS = null;

    /** JSON API validation rules set class */
    const ON_CREATE_VALIDATION_RULES_SET_CLASS = null;

    /** JSON API validation rules set class */
    const ON_UPDATE_VALIDATION_RULES_SET_CLASS = null;

    /**
     * @inheritdoc
     */
    public static function index(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        // By default no filters, sorts or includes are allowed from query. You can override this method to change it.
        $parser = static::configureOnIndexParser(
            static::createQueryParser($container)
        )->parse($request->getQueryParams());
        $mapper = static::createParameterMapper($container);
        $api    = static::createApi($container);

        $models = $mapper->applyQueryParameters($parser, $api)->index();

        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = ($models->getData()) === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($models);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public static function create(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        list ($index, $api) = static::createImpl($container, $request);

        $data = $api->read($index);
        assert(!($data instanceof PaginatedDataInterface));

        $response = static::createResponses($container, $request)->getCreatedResponse($data);

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
        // By default no filters, sorts or includes are allowed from query. You can override this method to change it.
        $parser = static::configureOnReadParser(
            static::createQueryParser($container)
        )->parse($request->getQueryParams());
        $mapper = static::createParameterMapper($container);

        $index     = $routeParams[static::ROUTE_KEY_INDEX];
        $modelData = $mapper->applyQueryParameters($parser, static::createApi($container))->read($index);
        assert(!($modelData instanceof PaginatedDataInterface));

        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = $modelData === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($modelData);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public static function update(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        list ($updated, $index, $api) = static::updateImpl($routeParams, $container, $request);

        $responses = static::createResponses($container, $request);
        if ($updated > 0) {
            $modelData = $api->read($index);
            assert(!($modelData instanceof PaginatedDataInterface));

            return $responses->getContentResponse($modelData);
        }

        return $responses->getCodeResponse(404);
    }

    /**
     * @inheritdoc
     */
    public static function delete(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        static::createApi($container)->remove($routeParams[static::ROUTE_KEY_INDEX]);

        $response = static::createResponses($container, $request)->getCodeResponse(204);

        return $response;
    }

    /** @deprecated Use `readRelationshipWithClosure` instead
     * @param string                 $index
     * @param string                 $relationshipName
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
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $apiHandler = function (CrudInterface $crud) use ($index, $relationshipName) {
            return $crud->readRelationship($index, $relationshipName);
        };

        return static::readRelationshipWithClosure($apiHandler, $relationshipName, $container, $request);
    }

    /** @deprecated Use `readRelationshipIdentifiersWithClosure` instead
     * @param string                 $index
     * @param string                 $relationshipName
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
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $apiHandler = function (CrudInterface $crud) use ($index, $relationshipName) {
            return $crud->readRelationship($index, $relationshipName);
        };

        return static::readRelationshipIdentifiersWithClosure($apiHandler, $relationshipName, $container, $request);
    }

    /**
     * @param Closure                $apiHandler
     * @param string                 $relationshipName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function readRelationshipWithClosure(
        Closure $apiHandler,
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        // By default no filters, sorts or includes are allowed from query. You can override this method to change it.
        $parser = static::configureOnReadRelationshipParser(
            $relationshipName,
            static::createQueryParser($container)
        )->parse($request->getQueryParams());

        $mapper  = static::createParameterMapper($container);
        $api     = $mapper->applyQueryParameters($parser, static::createApi($container));
        $relData = call_user_func($apiHandler, $api, $container);

        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null) ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($relData);

        return $response;
    }

    /**
     * @param Closure                $apiHandler
     * @param string                 $relationshipName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function readRelationshipIdentifiersWithClosure(
        Closure $apiHandler,
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        // By default no filters, sorts or includes are allowed from query. You can override this method to change it.
        $parser = static::configureOnReadRelationshipIdentifiersParser(
            $relationshipName,
            static::createQueryParser($container)
        )->parse($request->getQueryParams());

        $mapper  = static::createParameterMapper($container);
        $api     = $mapper->applyQueryParameters($parser, static::createApi($container));
        $relData = call_user_func($apiHandler, $api, $container);

        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null) ?
            $responses->getCodeResponse(404) : $responses->getIdentifiersResponse($relData);

        return $response;
    }

    /**
     * @param ContainerInterface $container
     * @param string|null        $class
     *
     * @return CrudInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createApi(ContainerInterface $container, string $class = null): CrudInterface
    {
        /** @var FactoryInterface $factory */
        $factory = $container->get(FactoryInterface::class);
        $api     = $factory->createApi($class ?? static::API_CLASS);

        return $api;
    }

    /**
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function readJsonFromRequest(ContainerInterface $container, ServerRequestInterface $request): array
    {
        $body = (string)$request->getBody();
        if (empty($body) === true || ($json = json_decode($body, true)) === null) {
            /** @var FactoryInterface $factory */
            $factory = $container->get(FactoryInterface::class);
            $errors  = $factory->createErrorCollection();
            $errors->addDataError(
                static::createMessageFormatter($container)->formatMessage(Messages::MSG_ERR_INVALID_ELEMENT)
            );

            throw new JsonApiException($errors);
        }

        return $json;
    }

    /**
     * @param array              $routeParams
     * @param ContainerInterface $container
     * @param array              $jsonData
     *
     * @return array
     * @SuppressWarnings(PHPMD.ElseExpression)
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function normalizeIndexValueOnUpdate(
        array $routeParams,
        ContainerInterface $container,
        array $jsonData
    ): array {
        // check that index in data and URL are identical
        $index         = $routeParams[static::ROUTE_KEY_INDEX];
        $dataSection   = null;
        $hasIndexValue =
            array_key_exists(DI::KEYWORD_DATA, $jsonData) &&
            array_key_exists(DI::KEYWORD_ID, ($dataSection = $jsonData[DI::KEYWORD_DATA]));
        if ($hasIndexValue === true) {
            assert($dataSection !== null);
            if ($dataSection[DI::KEYWORD_ID] !== $index) {
                /** @var FactoryInterface $factory */
                $factory = $container->get(FactoryInterface::class);
                $errors  = $factory->createErrorCollection();
                $errors->addDataIdError(
                    static::createMessageFormatter($container)->formatMessage(Messages::MSG_ERR_INVALID_ELEMENT)
                );

                throw new JsonApiException($errors);
            }
        } else {
            // put the index to data for our convenience
            $jsonData[DI::KEYWORD_DATA][DI::KEYWORD_ID] = $index;
        }

        return $jsonData;
    }

    /**
     * @param int|string             $parentIndex
     * @param string                 $relationshipName
     * @param int|string             $childIndex
     * @param string                 $childApiClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function deleteInRelationship(
        $parentIndex,
        string $relationshipName,
        $childIndex,
        string $childApiClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        /** @var SchemaInterface $schemaClass */
        $schemaClass  = static::SCHEMA_CLASS;
        $modelRelName = $schemaClass::getRelationshipMapping($relationshipName);
        $hasChild     = static::createApi($container)->hasInRelationship($parentIndex, $modelRelName, $childIndex);
        if ($hasChild === false) {
            return static::createResponses($container, $request)->getCodeResponse(404);
        }

        $childApi = static::createApi($container, $childApiClass);

        $childApi->remove($childIndex);
        $response = static::createResponses($container, $request)->getCodeResponse(204);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param int|string             $parentIndex
     * @param string                 $relationshipName
     * @param int|string             $childIndex
     * @param array                  $attributes
     * @param array                  $toMany
     * @param string                 $childApiClass
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function updateInRelationship(
        $parentIndex,
        string $relationshipName,
        $childIndex,
        array $attributes,
        array $toMany,
        string $childApiClass,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        /** @var CrudInterface $childApi */
        list ($updated, $childApi) = static::updateInRelationshipImpl(
            $parentIndex,
            $relationshipName,
            $childIndex,
            $attributes,
            $toMany,
            $childApiClass,
            $container
        );

        $responses = static::createResponses($container, $request);
        if ($updated > 0) {
            $modelData = $childApi->read($childIndex);
            assert(!($modelData instanceof PaginatedDataInterface));

            return $responses->getContentResponse($modelData);
        }

        return $responses->getCodeResponse(404);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return JsonApiValidatorInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createOnCreateValidator(ContainerInterface $container): JsonApiValidatorInterface
    {
        assert(
            empty(static::ON_CREATE_VALIDATION_RULES_SET_CLASS) === false,
            'Validation rules set should be defined for class ' . static::class . '.'
        );

        return static::createJsonApiValidator($container, static::ON_CREATE_VALIDATION_RULES_SET_CLASS);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return JsonApiValidatorInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createOnUpdateValidator(ContainerInterface $container): JsonApiValidatorInterface
    {
        assert(
            empty(static::ON_UPDATE_VALIDATION_RULES_SET_CLASS) === false,
            'Validation rules set should be defined for class ' . static::class . '.'
        );

        return static::createJsonApiValidator($container, static::ON_UPDATE_VALIDATION_RULES_SET_CLASS);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesSetClass
     *
     * @return JsonApiValidatorInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createJsonApiValidator(
        ContainerInterface $container,
        string $rulesSetClass
    ): JsonApiValidatorInterface {
        /** @var JsonApiValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $container->get(JsonApiValidatorFactoryInterface::class);
        $validator        = $validatorFactory->createValidator($rulesSetClass);

        return $validator;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return QueryParserInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createQueryParser(ContainerInterface $container): QueryParserInterface
    {
        return $container->get(QueryParserInterface::class);
    }

    /**
     * @param QueryParserInterface $parser
     *
     * @return QueryParserInterface
     */
    protected static function configureOnIndexParser(QueryParserInterface $parser): QueryParserInterface
    {
        return $parser;
    }

    /**
     * @param QueryParserInterface $parser
     *
     * @return QueryParserInterface
     */
    protected static function configureOnReadParser(QueryParserInterface $parser): QueryParserInterface
    {
        return $parser;
    }

    /**
     * @param string               $name
     * @param QueryParserInterface $parser
     *
     * @return QueryParserInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function configureOnReadRelationshipParser(
        /** @noinspection PhpUnusedParameterInspection */ string $name,
        QueryParserInterface $parser
    ): QueryParserInterface {
        return $parser;
    }

    /**
     * @param string               $name
     * @param QueryParserInterface $parser
     *
     * @return QueryParserInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected static function configureOnReadRelationshipIdentifiersParser(
        /** @noinspection PhpUnusedParameterInspection */ string $name,
        QueryParserInterface $parser
    ): QueryParserInterface {
        return $parser;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ParametersMapperInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createParameterMapper(ContainerInterface $container): ParametersMapperInterface
    {
        /** @var SchemaInterface $schemaClass */
        $schemaClass = static::SCHEMA_CLASS;

        /** @var ParametersMapperInterface $mapper */
        $mapper = $container->get(ParametersMapperInterface::class);
        $mapper->selectRootSchemeByResourceType($schemaClass::TYPE);

        return $mapper;
    }

    /**
     * @param ContainerInterface $container
     * @param array              $captures
     * @param string             $schemeClass
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function mapSchemeDataToModelData(
        ContainerInterface $container,
        array $captures,
        string $schemeClass
    ): array {
        assert(in_array(SchemaInterface::class, class_implements($schemeClass)));
        /** @var SchemaInterface $schemeClass */

        $modelClass = $schemeClass::MODEL;
        assert(in_array(ModelInterface::class, class_implements($modelClass)));
        /** @var ModelInterface $modelClass */

        /** @var ModelSchemeInfoInterface $schemeInfo */
        $schemeInfo = $container->get(ModelSchemeInfoInterface::class);

        $index         = null;
        $fields        = [];
        $toManyIndexes = [];
        foreach ($captures as $name => $value) {
            if ($name === DI::KEYWORD_ID) {
                $index = $value;
            } elseif ($schemeClass::hasAttributeMapping($name) === true) {
                $fieldName          = $schemeClass::getAttributeMapping($name);
                $fields[$fieldName] = $value;
            } elseif ($schemeClass::hasRelationshipMapping($name) === true) {
                $modelRelName = $schemeClass::getRelationshipMapping($name);
                $relType      = $schemeInfo->getRelationshipType($modelClass, $modelRelName);
                if ($relType === RelationshipTypes::BELONGS_TO) {
                    $fkName          = $schemeInfo->getForeignKey($modelClass, $modelRelName);
                    $fields[$fkName] = $value;
                } elseif ($relType === RelationshipTypes::BELONGS_TO_MANY) {
                    $toManyIndexes[$modelRelName] = $value;
                }
            }
        }

        $result = [$index, $fields, $toManyIndexes];

        return $result;
    }

    /**
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createImpl(
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $jsonData  = static::readJsonFromRequest($container, $request);
        $validator = static::createOnCreateValidator($container);
        $captures  = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) =
            static::mapSchemeDataToModelData($container, $captures, static::SCHEMA_CLASS);

        $api   = static::createApi($container);
        $index = $api->create($index, $attributes, $toMany);

        return [$index, $api];
    }

    /**
     * @param array                  $routeParams
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array [int $updated, string $index, CrudInterface $api]
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function updateImpl(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        $jsonData  = static::normalizeIndexValueOnUpdate(
            $routeParams,
            $container,
            static::readJsonFromRequest($container, $request)
        );
        $validator = static::createOnUpdateValidator($container);
        $captures  = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) =
            static::mapSchemeDataToModelData($container, $captures, static::SCHEMA_CLASS);
        $api = static::createApi($container);

        $updated = $api->update($index, $attributes, $toMany);

        return [$updated, $index, $api];
    }

    /**
     * @param                        $parentIndex
     * @param string                 $relationshipName
     * @param                        $childIndex
     * @param array                  $attributes
     * @param array                  $toMany
     * @param string                 $childApiClass
     * @param ContainerInterface     $container
     *
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private static function updateInRelationshipImpl(
        $parentIndex,
        string $relationshipName,
        $childIndex,
        array $attributes,
        array $toMany,
        string $childApiClass,
        ContainerInterface $container
    ): array {
        /** @var SchemaInterface $schemaClass */
        $schemaClass  = static::SCHEMA_CLASS;
        $modelRelName = $schemaClass::getRelationshipMapping($relationshipName);
        $hasChild     = static::createApi($container)->hasInRelationship($parentIndex, $modelRelName, $childIndex);
        if ($hasChild === false) {
            return [0, null];
        }

        $childApi = static::createApi($container, $childApiClass);

        $updated = $childApi->update($childIndex, $attributes, $toMany);

        return [$updated, $childApi];
    }

    /**
     * @param ContainerInterface $container
     * @param string             $namespace
     *
     * @return FormatterInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function createMessageFormatter(
        ContainerInterface $container,
        string $namespace = Messages::RESOURCES_NAMESPACE
    ): FormatterInterface {
        /** @var FormatterFactoryInterface $factory */
        $factory          = $container->get(FormatterFactoryInterface::class);
        $messageFormatter = $factory->createFormatter($namespace);

        return $messageFormatter;
    }
}

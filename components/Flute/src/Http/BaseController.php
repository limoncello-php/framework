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
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Limoncello\Flute
 *
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
        $parser = static::createQueryParser($container)->parse($request->getQueryParams());
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
        $jsonData  = static::readJsonFromRequest($container, $request);
        $validator = static::createOnCreateValidator($container);
        $captures  = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) =
            static::mapSchemeDataToModelData($container, $captures, static::SCHEMA_CLASS);

        $api   = static::createApi($container);
        $index = $api->create($index, $attributes, $toMany);
        $data  = $api->read($index)->getData();

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
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        $parser = static::createQueryParser($container)->parse($request->getQueryParams());
        $mapper = static::createParameterMapper($container);
        $api    = static::createApi($container);

        $response = static::readImpl(
            $mapper->applyQueryParameters($parser, $api),
            static::createResponses($container, $request, $parser->createEncodingParameters()),
            $index
        );

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

        return static::updateImpl($index, $attributes, $toMany, $container, $request, $api);
    }

    /**
     * @inheritdoc
     */
    public static function delete(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $index = $routeParams[static::ROUTE_KEY_INDEX];

        return static::deleteImpl($index, $container, $request, static::createApi($container));
    }

    /**
     * @param string                 $index
     * @param string                 $relationshipName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected static function readRelationship(
        string $index,
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $parser    = static::createQueryParser($container)->parse($request->getQueryParams());
        $relData   = static::readRelationshipData($index, $relationshipName, $container, $parser);
        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = $relData->getData() === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($relData);

        return $response;
    }

    /**
     * @param string                 $index
     * @param string                 $relationshipName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    protected static function readRelationshipIdentifiers(
        string $index,
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        $parser    = static::createQueryParser($container)->parse($request->getQueryParams());
        $relData   = static::readRelationshipData($index, $relationshipName, $container, $parser);
        $responses = static::createResponses($container, $request, $parser->createEncodingParameters());
        $response  = $relData->getData() === null ?
            $responses->getCodeResponse(404) : $responses->getIdentifiersResponse($relData);

        return $response;
    }

    /**
     * @param ContainerInterface $container
     * @param string|null        $class
     *
     * @return CrudInterface
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
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
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

        return static::deleteImpl($childIndex, $container, $request, $childApi);
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
        /** @var SchemaInterface $schemaClass */
        $schemaClass  = static::SCHEMA_CLASS;
        $modelRelName = $schemaClass::getRelationshipMapping($relationshipName);
        $hasChild     = static::createApi($container)->hasInRelationship($parentIndex, $modelRelName, $childIndex);
        if ($hasChild === false) {
            return static::createResponses($container, $request)->getCodeResponse(404);
        }

        $childApi = static::createApi($container, $childApiClass);

        return static::updateImpl($childIndex, $attributes, $toMany, $container, $request, $childApi);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return JsonApiValidatorInterface
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
     */
    protected static function createQueryParser(ContainerInterface $container): QueryParserInterface
    {
        return $container->get(QueryParserInterface::class);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ParametersMapperInterface
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
     * @param CrudInterface      $api
     * @param ResponsesInterface $responses
     * @param string|int         $index
     *
     * @return mixed
     */
    private static function readImpl(
        CrudInterface $api,
        ResponsesInterface $responses,
        $index
    ) {
        $modelData = $api->read($index)->getData();
        $response  = $modelData === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($modelData);

        return $response;
    }

    /**
     * @param string               $index
     * @param string               $relationshipName
     * @param ContainerInterface   $container
     * @param QueryParserInterface $parser
     *
     * @return PaginatedDataInterface
     */
    private static function readRelationshipData(
        string $index,
        string $relationshipName,
        ContainerInterface $container,
        QueryParserInterface $parser
    ): PaginatedDataInterface {
        $mapper = static::createParameterMapper($container);
        $api    = static::createApi($container);

        $relData = $mapper
            ->applyQueryParameters($parser, $api)
            ->readRelationship($index, $relationshipName);

        return $relData;
    }

    /**
     * @param string|int             $index
     * @param array                  $attributes
     * @param array                  $toMany
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     * @param CrudInterface          $api
     *
     * @return ResponseInterface
     */
    private static function updateImpl(
        $index,
        array $attributes,
        array $toMany,
        ContainerInterface $container,
        ServerRequestInterface $request,
        CrudInterface $api
    ): ResponseInterface {
        $api->update($index, $attributes, $toMany);

        $response = static::readImpl($api, static::createResponses($container, $request), $index);

        return $response;
    }

    /**
     * @param string|int             $index
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     * @param CrudInterface          $api
     *
     * @return ResponseInterface
     */
    private static function deleteImpl(
        $index,
        ContainerInterface $container,
        ServerRequestInterface $request,
        CrudInterface $api
    ): ResponseInterface {
        $api->remove($index);
        $response = static::createResponses($container, $request)->getCodeResponse(204);

        return $response;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $namespace
     *
     * @return FormatterInterface
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

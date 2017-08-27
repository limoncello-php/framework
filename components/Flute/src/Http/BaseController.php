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
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiValidatorInterface;
use Limoncello\Flute\Http\Query\FilterParameterCollection;
use Limoncello\Flute\Http\Traits\CreateResponsesTrait;
use Limoncello\Flute\L10n\Messages;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as DI;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
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

    /** URI key used in routing table */
    const ROUTE_KEY_INDEX = 'idx';

    /**
     * @inheritdoc
     */
    public static function index(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        /** @var QueryParametersParserInterface $queryParser */
        $queryParser    = $container->get(QueryParametersParserInterface::class);
        $encodingParams = $queryParser->parse($request);

        list ($filters, $sorts, $includes, $paging) =
            static::mapQueryParameters($container, $encodingParams, static::SCHEMA_CLASS);

        $modelData = static::createApi($container)->index($filters, $sorts, $includes, $paging);
        $responses = static::createResponses($container, $request, $encodingParams);
        $response  = $modelData->getPaginatedData()->getData() === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($modelData);

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
        $validator = static::createOnCreateValidator($container);
        $jsonData  = static::readJsonFromRequest($container, $request);
        $captures  = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) =
            static::mapSchemeDataToModelData($container, $captures, static::SCHEMA_CLASS);

        $api   = self::createApi($container);
        $index = $api->create($index, $attributes, $toMany);
        $data  = $api->read($index);

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
        /** @var QueryParametersParserInterface $queryParser */
        $queryParser    = $container->get(QueryParametersParserInterface::class);
        $encodingParams = $queryParser->parse($request);

        list ($filters, , $includes) = static::mapQueryParameters($container, $encodingParams, static::SCHEMA_CLASS);

        $index    = $routeParams[static::ROUTE_KEY_INDEX];
        $response = static::readImpl(
            static::createApi($container),
            static::createResponses($container, $request, $encodingParams),
            $index,
            $filters,
            $includes
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
        $validator = static::createOnUpdateValidator($container);
        $jsonData  = static::readJsonFromRequest($container, $request);
        $jsonData  = static::normalizeIndexValueOnUpdate($routeParams, $container, $jsonData);
        $captures  = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) =
            static::mapSchemeDataToModelData($container, $captures, static::SCHEMA_CLASS);
        $api = self::createApi($container);

        return self::updateImpl($index, $attributes, $toMany, $container, $request, $api);
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

        return static::deleteImpl($index, $container, $request, self::createApi($container));
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
        /** @var PaginatedDataInterface $relData */
        /** @var EncodingParametersInterface $encodingParams */
        list ($relData, $encodingParams) = self::readRelationshipData($index, $relationshipName, $container, $request);

        $responses = static::createResponses($container, $request, $encodingParams);
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
        /** @var PaginatedDataInterface $relData */
        /** @var EncodingParametersInterface $encodingParams */
        list ($relData, $encodingParams) = self::readRelationshipData($index, $relationshipName, $container, $request);

        $responses = static::createResponses($container, $request, $encodingParams);
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
     * @param ContainerInterface          $container
     * @param EncodingParametersInterface $parameters
     * @param string                      $schemaClass
     *
     * @return array
     */
    protected static function mapQueryParameters(
        ContainerInterface $container,
        EncodingParametersInterface $parameters,
        string $schemaClass
    ): array {
        /** @var FactoryInterface $factory */
        $factory          = $container->get(FactoryInterface::class);
        $errors           = $factory->createErrorCollection();
        $queryTransformer = new QueryTransformer(
            $container->get(ModelSchemeInfoInterface::class),
            $container->get(JsonSchemesInterface::class),
            static::createMessageFormatter($container),
            $schemaClass
        );

        $result = $queryTransformer->mapParameters($errors, $parameters);
        if ($errors->count() > 0) {
            throw new JsonApiException($errors);
        }

        return $result;
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
        $hasChild     = self::createApi($container)->hasInRelationship($parentIndex, $modelRelName, $childIndex);
        if ($hasChild === false) {
            return static::createResponses($container, $request)->getCodeResponse(404);
        }

        $childApi = self::createApi($container, $childApiClass);

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
        $hasChild     = self::createApi($container)->hasInRelationship($parentIndex, $modelRelName, $childIndex);
        if ($hasChild === false) {
            return static::createResponses($container, $request)->getCodeResponse(404);
        }

        $childApi = self::createApi($container, $childApiClass);

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
     * @param CrudInterface                  $api
     * @param ResponsesInterface             $responses
     * @param string|int                     $index
     * @param FilterParameterCollection|null $filters
     * @param array|null                     $includes
     *
     * @return mixed
     */
    private static function readImpl(
        CrudInterface $api,
        ResponsesInterface $responses,
        $index,
        FilterParameterCollection $filters = null,
        array $includes = null
    ) {
        $modelData = $api->read($index, $filters, $includes);
        $response  = $modelData->getPaginatedData()->getData() === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($modelData);

        return $response;
    }

    /**
     * @param string                 $index
     * @param string                 $relationshipName
     * @param ContainerInterface     $container
     * @param ServerRequestInterface $request
     *
     * @return array [PaginatedDataInterface, EncodingParametersInterface]
     */
    private static function readRelationshipData(
        string $index,
        string $relationshipName,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): array {
        /** @var QueryParametersParserInterface $queryParser */
        $queryParser    = $container->get(QueryParametersParserInterface::class);
        $encodingParams = $queryParser->parse($request);

        /** @var JsonSchemesInterface $jsonSchemes */
        $jsonSchemes  = $container->get(JsonSchemesInterface::class);
        $targetSchema = $jsonSchemes->getRelationshipSchema(static::SCHEMA_CLASS, $relationshipName);
        list ($filters, $sorts, , $paging) =
            static::mapQueryParameters($container, $encodingParams, get_class($targetSchema));

        /** @var SchemaInterface $schemaClass */
        $schemaClass  = static::SCHEMA_CLASS;
        $modelRelName = $schemaClass::getRelationshipMapping($relationshipName);
        $relData      = self::createApi($container)->readRelationship($index, $modelRelName, $filters, $sorts, $paging);

        return [$relData, $encodingParams];
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
        $api->delete($index);
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

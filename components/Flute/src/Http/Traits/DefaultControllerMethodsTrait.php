<?php namespace Limoncello\Flute\Http\Traits;

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
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataValidatingParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryValidatingParserInterface;
use Limoncello\Flute\Http\Responses;
use Limoncello\Flute\Package\FluteSettings as S;
use Limoncello\Flute\Resources\Messages\En\Generic;
use Limoncello\Flute\Validation\JsonApi\DefaultQueryValidationRules;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as DI;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * @package Limoncello\Flute
 */
trait DefaultControllerMethodsTrait
{
    /** @noinspection PhpTooManyParametersInspection
     * @param array                                 $queryParams
     * @param UriInterface                          $requestUri
     * @param JsonApiQueryValidatingParserInterface $queryParser
     * @param ParametersMapperInterface             $mapper
     * @param CrudInterface                         $crud
     * @param SettingsProviderInterface             $provider
     * @param JsonSchemesInterface                  $jsonSchemes
     * @param EncoderInterface                      $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultIndexHandler(
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryValidatingParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($queryParams);

        $models = $mapper->applyQueryParameters($queryParser, $crud)->index();

        $encParams = self::defaultCreateEncodingParameters($queryParser);
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);
        $response  = ($models->getData()) === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($models);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                                $index
     * @param array                                 $queryParams
     * @param UriInterface                          $requestUri
     * @param JsonApiQueryValidatingParserInterface $queryParser
     * @param ParametersMapperInterface             $mapper
     * @param CrudInterface                         $crud
     * @param SettingsProviderInterface             $provider
     * @param JsonSchemesInterface                  $jsonSchemes
     * @param EncoderInterface                      $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultReadHandler(
        string $index,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryValidatingParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($queryParams);

        $model = $mapper->applyQueryParameters($queryParser, $crud)->read($index);
        assert(!($model instanceof PaginatedDataInterface));

        $encParams = self::defaultCreateEncodingParameters($queryParser);
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);
        $response  = $model === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($model);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Closure                               $apiHandler
     * @param array                                 $queryParams
     * @param UriInterface                          $requestUri
     * @param JsonApiQueryValidatingParserInterface $queryParser
     * @param ParametersMapperInterface             $mapper
     * @param CrudInterface                         $crud
     * @param SettingsProviderInterface             $provider
     * @param JsonSchemesInterface                  $jsonSchemes
     * @param EncoderInterface                      $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultReadRelationshipWithClosureHandler(
        Closure $apiHandler,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryValidatingParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($queryParams);
        $mapper->applyQueryParameters($queryParser, $crud);

        $relData = call_user_func($apiHandler);

        $encParams = self::defaultCreateEncodingParameters($queryParser);
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);

        $noData   = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null);
        $response = $noData === true ? $responses->getCodeResponse(404) : $responses->getContentResponse($relData);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param Closure                               $apiHandler
     * @param array                                 $queryParams
     * @param UriInterface                          $requestUri
     * @param JsonApiQueryValidatingParserInterface $queryParser
     * @param ParametersMapperInterface             $mapper
     * @param CrudInterface                         $crud
     * @param SettingsProviderInterface             $provider
     * @param JsonSchemesInterface                  $jsonSchemes
     * @param EncoderInterface                      $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultReadRelationshipIdentifiersWithClosureHandler(
        Closure $apiHandler,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryValidatingParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($queryParams);
        $mapper->applyQueryParameters($queryParser, $crud);

        $relData = call_user_func($apiHandler);

        $encParams = self::defaultCreateEncodingParameters($queryParser);
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);

        $noData   = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null);
        $response = $noData === true ? $responses->getCodeResponse(404) : $responses->getIdentifiersResponse($relData);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param UriInterface                         $requestUri
     * @param string                               $requestBody
     * @param string                               $schemeClass
     * @param ModelSchemeInfoInterface             $schemeInfo
     * @param JsonApiDataValidatingParserInterface $validator
     * @param CrudInterface                        $crud
     * @param SettingsProviderInterface            $provider
     * @param JsonSchemesInterface                 $jsonSchemes
     * @param EncoderInterface                     $encoder
     * @param FactoryInterface                     $errorFactory
     * @param FormatterFactoryInterface            $formatterFactory
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultCreateHandler(
        UriInterface $requestUri,
        string $requestBody,
        string $schemeClass,
        ModelSchemeInfoInterface $schemeInfo,
        JsonApiDataValidatingParserInterface $validator,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory
    ): ResponseInterface {
        // some of the users want to reuse default `create` but have a custom part for responses
        // to meet this requirement it is split into two parts.
        $index = static::defaultCreate(
            $requestBody,
            $schemeClass,
            $schemeInfo,
            $validator,
            $crud,
            $errorFactory,
            $formatterFactory
        );

        return static::defaultCreateResponse($index, $requestUri, $crud, $provider, $jsonSchemes, $encoder);
    }

    /**
     * @param string                               $requestBody
     * @param string                               $schemeClass
     * @param ModelSchemeInfoInterface             $schemeInfo
     * @param JsonApiDataValidatingParserInterface $validator
     * @param CrudInterface                        $crud
     * @param FactoryInterface                     $errorFactory
     * @param FormatterFactoryInterface            $formatterFactory
     *
     * @return ResponseInterface
     */
    protected static function defaultCreate(
        string $requestBody,
        string $schemeClass,
        ModelSchemeInfoInterface $schemeInfo,
        JsonApiDataValidatingParserInterface $validator,
        CrudInterface $crud,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory
    ): string {
        $jsonData = static::readJsonFromRequest($requestBody, $errorFactory, $formatterFactory);
        $captures = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) = static::mapSchemeDataToModelData($captures, $schemeClass, $schemeInfo);

        $index = $crud->create($index, $attributes, $toMany);

        return $index;
    }

    /**
     * @param string                    $index
     * @param UriInterface              $requestUri
     * @param CrudInterface             $crud
     * @param SettingsProviderInterface $provider
     * @param JsonSchemesInterface      $jsonSchemes
     * @param EncoderInterface          $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultCreateResponse(
        string $index,
        UriInterface $requestUri,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $model = $crud->read($index);
        assert($model !== null && !($model instanceof PaginatedDataInterface));

        $encParams = null;
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);
        $response  = $responses->getCreatedResponse($model);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                               $index
     * @param UriInterface                         $requestUri
     * @param string                               $requestBody
     * @param string                               $schemeClass
     * @param ModelSchemeInfoInterface             $schemeInfo
     * @param JsonApiDataValidatingParserInterface $validator
     * @param CrudInterface                        $crud
     * @param SettingsProviderInterface            $provider
     * @param JsonSchemesInterface                 $jsonSchemes
     * @param EncoderInterface                     $encoder
     * @param FactoryInterface                     $errorFactory
     * @param FormatterFactoryInterface            $formatterFactory
     * @param string                               $messagesNamespace
     * @param string                               $errorMessage
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultUpdateHandler(
        string $index,
        UriInterface $requestUri,
        string $requestBody,
        string $schemeClass,
        ModelSchemeInfoInterface $schemeInfo,
        JsonApiDataValidatingParserInterface $validator,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): ResponseInterface {
        // some of the users want to reuse default `update` but have a custom part for responses
        // to meet this requirement it is split into two parts.
        $updated = static::defaultUpdate(
            $index,
            $requestBody,
            $schemeClass,
            $schemeInfo,
            $validator,
            $crud,
            $errorFactory,
            $formatterFactory,
            $messagesNamespace,
            $errorMessage
        );

        return static::defaultUpdateResponse($updated, $index, $requestUri, $crud, $provider, $jsonSchemes, $encoder);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                               $index
     * @param string                               $requestBody
     * @param string                               $schemeClass
     * @param ModelSchemeInfoInterface             $schemeInfo
     * @param JsonApiDataValidatingParserInterface $validator
     * @param CrudInterface                        $crud
     * @param FactoryInterface                     $errorFactory
     * @param FormatterFactoryInterface            $formatterFactory
     * @param string                               $messagesNamespace
     * @param string                               $errorMessage
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultUpdate(
        string $index,
        string $requestBody,
        string $schemeClass,
        ModelSchemeInfoInterface $schemeInfo,
        JsonApiDataValidatingParserInterface $validator,
        CrudInterface $crud,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): int {
        $jsonData = static::readJsonFromRequest($requestBody, $errorFactory, $formatterFactory);
        // check that index in data and URL are identical
        $indexValue = $jsonData[DI::KEYWORD_DATA][DI::KEYWORD_ID] ?? null;
        if (empty($indexValue) === false) {
            if ($indexValue !== $index) {
                $errors    = $errorFactory->createErrorCollection();
                $formatter = $formatterFactory->createFormatter($messagesNamespace);
                $errors->addDataIdError($formatter->formatMessage($errorMessage));

                throw new JsonApiException($errors);
            }
        } else {
            // put the index to data for our convenience
            $jsonData[DI::KEYWORD_DATA][DI::KEYWORD_ID] = $index;
        }
        // validate the data
        $captures = $validator->assert($jsonData)->getJsonApiCaptures();

        list ($index, $attributes, $toMany) = static::mapSchemeDataToModelData($captures, $schemeClass, $schemeInfo);

        $updated = $crud->update($index, $attributes, $toMany);

        return $updated;
    }

    /**
     * @param int                       $updated
     * @param string                    $index
     * @param UriInterface              $requestUri
     * @param CrudInterface             $crud
     * @param SettingsProviderInterface $provider
     * @param JsonSchemesInterface      $jsonSchemes
     * @param EncoderInterface          $encoder
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected static function defaultUpdateResponse(
        int $updated,
        string $index,
        UriInterface $requestUri,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $encParams = null;
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);
        if ($updated > 0) {
            $model = $crud->read($index);
            assert($model !== null && !($model instanceof PaginatedDataInterface));
            $response = $responses->getContentResponse($model);
        } else {
            $response = $responses->getCodeResponse(404);
        }

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                               $parentIndex
     * @param string                               $modelRelName
     * @param string                               $childIndex
     * @param UriInterface                         $requestUri
     * @param string                               $requestBody
     * @param string                               $childSchemeClass
     * @param ModelSchemeInfoInterface             $schemeInfo
     * @param JsonApiDataValidatingParserInterface $childValidator
     * @param CrudInterface                        $parentCrud
     * @param CrudInterface                        $childCrud
     * @param SettingsProviderInterface            $provider
     * @param JsonSchemesInterface                 $jsonSchemes
     * @param EncoderInterface                     $encoder
     * @param FactoryInterface                     $errorFactory
     * @param FormatterFactoryInterface            $formatterFactory
     * @param string                               $messagesNamespace
     * @param string                               $errorMessage
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultUpdateInRelationshipHandler(
        string $parentIndex,
        string $modelRelName,
        string $childIndex,
        UriInterface $requestUri,
        string $requestBody,
        string $childSchemeClass,
        ModelSchemeInfoInterface $schemeInfo,
        JsonApiDataValidatingParserInterface $childValidator,
        CrudInterface $parentCrud,
        CrudInterface $childCrud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): ResponseInterface {
        if ($parentCrud->hasInRelationship($parentIndex, $modelRelName, $childIndex) === true) {
            return static::defaultUpdateHandler(
                $childIndex,
                $requestUri,
                $requestBody,
                $childSchemeClass,
                $schemeInfo,
                $childValidator,
                $childCrud,
                $provider,
                $jsonSchemes,
                $encoder,
                $errorFactory,
                $formatterFactory,
                $messagesNamespace,
                $errorMessage
            );
        }

        $encParams = null;
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);

        return $responses->getCodeResponse(404);
    }

    /**
     * @param string                    $index
     * @param UriInterface              $requestUri
     * @param CrudInterface             $crud
     * @param SettingsProviderInterface $provider
     * @param JsonSchemesInterface      $jsonSchemes
     * @param EncoderInterface          $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultDeleteHandler(
        string $index,
        UriInterface $requestUri,
        CrudInterface $crud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        $crud->remove($index);

        $encParams = null;
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);
        $response  = $responses->getCodeResponse(204);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                    $parentIndex
     * @param string                    $modelRelName
     * @param string                    $childIndex
     * @param UriInterface              $requestUri
     * @param CrudInterface             $parentCrud
     * @param CrudInterface             $childCrud
     * @param SettingsProviderInterface $provider
     * @param JsonSchemesInterface      $jsonSchemes
     * @param EncoderInterface          $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultDeleteInRelationshipHandler(
        string $parentIndex,
        string $modelRelName,
        string $childIndex,
        UriInterface $requestUri,
        CrudInterface $parentCrud,
        CrudInterface $childCrud,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder
    ): ResponseInterface {
        if ($parentCrud->hasInRelationship($parentIndex, $modelRelName, $childIndex) === true) {
            return static::defaultDeleteHandler(
                $childIndex,
                $requestUri,
                $childCrud,
                $provider,
                $jsonSchemes,
                $encoder
            );
        }

        $encParams = null;
        $responses = static::defaultCreateResponses($requestUri, $provider, $jsonSchemes, $encoder, $encParams);

        return $responses->getCodeResponse(404);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesClass
     *
     * @return JsonApiQueryValidatingParserInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateQueryParser(
        ContainerInterface $container,
        string $rulesClass = DefaultQueryValidationRules::class
    ): JsonApiQueryValidatingParserInterface {
        static::assertClassImplements($rulesClass, JsonApiQueryRulesInterface::class);

        /** @var JsonApiParserFactoryInterface $factory */
        $factory   = $container->get(JsonApiParserFactoryInterface::class);
        $validator = $factory->createQueryParser($rulesClass);

        return $validator;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesClass
     *
     * @return JsonApiDataValidatingParserInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateDataParser(
        ContainerInterface $container,
        string $rulesClass
    ): JsonApiDataValidatingParserInterface {
        static::assertClassImplements($rulesClass, JsonApiDataRulesInterface::class);

        /** @var JsonApiParserFactoryInterface $factory */
        $factory   = $container->get(JsonApiParserFactoryInterface::class);
        $validator = $factory->createDataParser($rulesClass);

        return $validator;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $schemaClass
     *
     * @return ParametersMapperInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateParameterMapper(
        ContainerInterface $container,
        string $schemaClass
    ): ParametersMapperInterface {
        static::assertClassImplements($schemaClass, SchemaInterface::class);

        /** @var SchemaInterface $schemaClass */
        $jsonResourceType = $schemaClass::TYPE;

        /** @var ParametersMapperInterface $mapper */
        $mapper = $container->get(ParametersMapperInterface::class);
        $mapper->selectRootSchemeByResourceType($jsonResourceType);

        return $mapper;
    }

    /**
     * @param JsonApiQueryValidatingParserInterface $queryParser
     *
     * @return EncodingParametersInterface
     */
    protected static function defaultCreateEncodingParameters(
        JsonApiQueryValidatingParserInterface $queryParser
    ): EncodingParametersInterface {
        return new EncodingParameters(
            $queryParser->hasIncludes() === true ? array_keys($queryParser->getIncludes()) : null,
            $queryParser->hasFields() === true ? $queryParser->getFields() : null
        );
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
    protected static function defaultCreateApi(ContainerInterface $container, string $class): CrudInterface
    {
        static::assertClassImplements($class, CrudInterface::class);

        /** @var FactoryInterface $factory */
        $factory = $container->get(FactoryInterface::class);
        $api     = $factory->createApi($class);

        return $api;
    }

    /**
     * @param UriInterface                     $requestUri
     * @param SettingsProviderInterface        $provider
     * @param JsonSchemesInterface             $jsonSchemes
     * @param EncoderInterface                 $encoder
     * @param EncodingParametersInterface|null $parameters
     *
     * @return ResponsesInterface
     */
    protected static function defaultCreateResponses(
        UriInterface $requestUri,
        SettingsProviderInterface $provider,
        JsonSchemesInterface $jsonSchemes,
        EncoderInterface $encoder,
        ?EncodingParametersInterface $parameters
    ): ResponsesInterface {
        $encoder->forOriginalUri($requestUri);
        $settings  = $provider->get(S::class);
        $responses = new Responses(
            new MediaType(MediaTypeInterface::JSON_API_TYPE, MediaTypeInterface::JSON_API_SUB_TYPE),
            $encoder,
            $jsonSchemes,
            $parameters,
            $settings[S::KEY_URI_PREFIX],
            $settings[S::KEY_META]
        );

        return $responses;
    }

    /**
     * Developers can override the method in order to add/remove some data for `create`/`update` inputs.
     *
     * @param string                    $requestBody
     * @param FactoryInterface          $errorFactory
     * @param FormatterFactoryInterface $formatterFactory
     * @param string                    $messagesNamespace
     * @param string                    $errorMessage
     *
     * @return array
     */
    protected static function readJsonFromRequest(
        string $requestBody,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): array {
        if (empty($requestBody) === true || ($json = json_decode($requestBody, true)) === null) {
            $formatter = $formatterFactory->createFormatter($messagesNamespace);
            $errors    = $errorFactory->createErrorCollection();
            $errors->addDataError($formatter->formatMessage($errorMessage));

            throw new JsonApiException($errors);
        }

        return $json;
    }

    /**
     * Developers can override the method in order to use custom data mapping from a Schema to Model.
     *
     * @param array                    $captures
     * @param string                   $schemeClass
     * @param ModelSchemeInfoInterface $schemeInfo
     *
     * @return array
     */
    protected static function mapSchemeDataToModelData(
        array $captures,
        string $schemeClass,
        ModelSchemeInfoInterface $schemeInfo
    ): array {
        static::assertClassImplements($schemeClass, SchemaInterface::class);

        /** @var SchemaInterface $schemeClass */
        static::assertClassImplements($modelClass = $schemeClass::MODEL, ModelInterface::class);
        /** @var ModelInterface $modelClass */

        $index         = null;
        $fields        = [];
        $toManyIndexes = [];
        foreach ($captures as $name => $value) {
            assert(is_string($name) === true);
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
     * @param null|string $value
     *
     * @return void
     */
    private static function assertClassValueDefined(?string $value): void
    {
        assert(empty($value) === false, 'Value should be defined in `' . static::class . '`.');
    }

    /**
     * @param string $class
     * @param string $interface
     *
     * @return void
     */
    private static function assertClassImplements(string $class, string $interface): void
    {
        assert(
            array_key_exists($interface, class_implements($class)) === true,
            "Class `$class` should implement `" . $interface . '` interface.'
        );
    }
}

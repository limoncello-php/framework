<?php namespace Limoncello\Flute\Http\Traits;

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

use Closure;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Limoncello\Contracts\Application\ModelInterface;
use Limoncello\Contracts\Data\ModelSchemaInfoInterface;
use Limoncello\Contracts\Data\RelationshipTypes;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Flute\Contracts\Api\CrudInterface;
use Limoncello\Flute\Contracts\Encoder\EncoderInterface;
use Limoncello\Flute\Contracts\FactoryInterface;
use Limoncello\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Limoncello\Flute\Contracts\Models\PaginatedDataInterface;
use Limoncello\Flute\Contracts\Schema\JsonSchemasInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\FormRulesInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\Http\Responses;
use Limoncello\Flute\Package\FluteSettings as S;
use Limoncello\Flute\Resources\Messages\En\Generic;
use Limoncello\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface as DI;
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
     * @param array                       $queryParams
     * @param UriInterface                $requestUri
     * @param JsonApiQueryParserInterface $queryParser
     * @param ParametersMapperInterface   $mapper
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultIndexHandler(
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse(null, $queryParams);

        $models = $mapper->applyQueryParameters($queryParser, $crud)->index();

        self::defaultApplyIncludesAndFieldSetsToEncoder($queryParser, $encoder);
        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = ($models->getData()) === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($models);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param array                       $queryParams
     * @param UriInterface                $requestUri
     * @param JsonApiQueryParserInterface $queryParser
     * @param ParametersMapperInterface   $mapper
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultReadHandler(
        string $index,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($index, $queryParams);
        $validatedIndex = $queryParser->getIdentity();

        $model = $mapper->applyQueryParameters($queryParser, $crud)->read($validatedIndex);
        assert(!($model instanceof PaginatedDataInterface));

        self::defaultApplyIncludesAndFieldSetsToEncoder($queryParser, $encoder);
        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = $model === null ?
            $responses->getCodeResponse(404) : $responses->getContentResponse($model);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param Closure                     $apiHandler
     * @param array                       $queryParams
     * @param UriInterface                $requestUri
     * @param JsonApiQueryParserInterface $queryParser
     * @param ParametersMapperInterface   $mapper
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultReadRelationshipWithClosureHandler(
        string $index,
        Closure $apiHandler,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($index, $queryParams);
        $mapper->applyQueryParameters($queryParser, $crud);

        $relData = call_user_func($apiHandler);

        self::defaultApplyIncludesAndFieldSetsToEncoder($queryParser, $encoder);
        $responses = static::defaultCreateResponses($requestUri, $encoder);

        $noData   = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null);
        $response = $noData === true ? $responses->getCodeResponse(404) : $responses->getContentResponse($relData);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param Closure                     $apiHandler
     * @param array                       $queryParams
     * @param UriInterface                $requestUri
     * @param JsonApiQueryParserInterface $queryParser
     * @param ParametersMapperInterface   $mapper
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultReadRelationshipIdentifiersWithClosureHandler(
        string $index,
        Closure $apiHandler,
        array $queryParams,
        UriInterface $requestUri,
        JsonApiQueryParserInterface $queryParser,
        ParametersMapperInterface $mapper,
        CrudInterface $crud,
        EncoderInterface $encoder
    ): ResponseInterface {
        $queryParser->parse($index, $queryParams);
        $mapper->applyQueryParameters($queryParser, $crud);

        $relData = call_user_func($apiHandler);

        self::defaultApplyIncludesAndFieldSetsToEncoder($queryParser, $encoder);
        $responses = static::defaultCreateResponses($requestUri, $encoder);

        $noData   = $relData === null || ($relData instanceof PaginatedDataInterface && $relData->getData() === null);
        $response = $noData === true ? $responses->getCodeResponse(404) : $responses->getIdentifiersResponse($relData);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param UriInterface               $requestUri
     * @param string                     $requestBody
     * @param string                     $schemaClass
     * @param ModelSchemaInfoInterface   $schemaInfo
     * @param JsonApiDataParserInterface $parser
     * @param CrudInterface              $crud
     * @param JsonSchemasInterface       $jsonSchemas
     * @param EncoderInterface           $encoder
     * @param FactoryInterface           $errorFactory
     * @param FormatterFactoryInterface  $formatterFactory
     * @param string                     $messagesNamespace
     * @param string                     $errorMessage
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultCreateHandler(
        UriInterface $requestUri,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiDataParserInterface $parser,
        CrudInterface $crud,
        JsonSchemasInterface $jsonSchemas,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): ResponseInterface {
        // some of the users want to reuse default `create` but have a custom part for responses
        // to meet this requirement it is split into two parts.
        $index = static::defaultCreate(
            $requestBody,
            $schemaClass,
            $schemaInfo,
            $parser,
            $crud,
            $errorFactory,
            $formatterFactory,
            $messagesNamespace,
            $errorMessage
        );

        return static::defaultCreateResponse($index, $requestUri, $crud, $jsonSchemas, $encoder);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                     $requestBody
     * @param string                     $schemaClass
     * @param ModelSchemaInfoInterface   $schemaInfo
     * @param JsonApiDataParserInterface $parser
     * @param CrudInterface              $crud
     * @param FactoryInterface           $errorFactory
     * @param FormatterFactoryInterface  $formatterFactory
     * @param string                     $messagesNamespace
     * @param string                     $errorMessage
     *
     * @return ResponseInterface
     */
    protected static function defaultCreate(
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiDataParserInterface $parser,
        CrudInterface $crud,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): string {
        $jsonData = static::readJsonFromRequest(
            $requestBody,
            $errorFactory,
            $formatterFactory,
            $messagesNamespace,
            $errorMessage
        );

        $captures = $parser->assert($jsonData)->getCaptures();

        list ($index, $attributes, $toMany) = static::mapSchemaDataToModelData($captures, $schemaClass, $schemaInfo);

        try {
            $index = $crud->create($index, $attributes, $toMany);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $exception) {
            $errors    = $errorFactory->createErrorCollection();
            $formatter = $formatterFactory->createFormatter($messagesNamespace);
            $title     = $formatter->formatMessage($errorMessage);
            $details   = null;
            $errorCode = JsonApiResponse::HTTP_CONFLICT;
            $errors->addDataError($title, $details, $errorCode);

            throw new JsonApiException($errors);
        }

        return $index;
    }

    /**
     * @param string               $index
     * @param UriInterface         $requestUri
     * @param CrudInterface        $crud
     * @param JsonSchemasInterface $jsonSchemas
     * @param EncoderInterface     $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultCreateResponse(
        string $index,
        UriInterface $requestUri,
        CrudInterface $crud,
        JsonSchemasInterface $jsonSchemas,
        EncoderInterface $encoder
    ): ResponseInterface {
        $model = $crud->read($index);
        assert($model !== null && !($model instanceof PaginatedDataInterface));

        $schema    = $jsonSchemas->getSchema($model);
        $selfLink  = $schema->getSelfLink($model);
        $urlPrefix = (string)$requestUri->withPath('')->withQuery('')->withFragment('');
        $fullUrl   = $selfLink->getStringRepresentation($urlPrefix);

        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = $responses->getCreatedResponse($model, $fullUrl);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                     $index
     * @param UriInterface               $requestUri
     * @param string                     $requestBody
     * @param string                     $schemaClass
     * @param ModelSchemaInfoInterface   $schemaInfo
     * @param JsonApiDataParserInterface $parser
     * @param CrudInterface              $crud
     * @param EncoderInterface           $encoder
     * @param FactoryInterface           $errorFactory
     * @param FormatterFactoryInterface  $formatterFactory
     * @param string                     $messagesNamespace
     * @param string                     $errorMessage
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultUpdateHandler(
        string $index,
        UriInterface $requestUri,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiDataParserInterface $parser,
        CrudInterface $crud,
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
            $schemaClass,
            $schemaInfo,
            $parser,
            $crud,
            $errorFactory,
            $formatterFactory,
            $messagesNamespace,
            $errorMessage
        );

        return static::defaultUpdateResponse($updated, $index, $requestUri, $crud, $encoder);
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                     $index
     * @param string                     $requestBody
     * @param string                     $schemaClass
     * @param ModelSchemaInfoInterface   $schemaInfo
     * @param JsonApiDataParserInterface $parser
     * @param CrudInterface              $crud
     * @param FactoryInterface           $errorFactory
     * @param FormatterFactoryInterface  $formatterFactory
     * @param string                     $messagesNamespace
     * @param string                     $errorMessage
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultUpdate(
        string $index,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiDataParserInterface $parser,
        CrudInterface $crud,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory,
        string $messagesNamespace = S::GENERIC_NAMESPACE,
        string $errorMessage = Generic::MSG_ERR_INVALID_ELEMENT
    ): int {
        $jsonData = static::readJsonFromRequest(
            $requestBody,
            $errorFactory,
            $formatterFactory,
            $messagesNamespace,
            $errorMessage
        );

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
        $captures = $parser->assert($jsonData)->getCaptures();

        list ($index, $attributes, $toMany) = static::mapSchemaDataToModelData($captures, $schemaClass, $schemaInfo);

        try {
            $updated = $crud->update($index, $attributes, $toMany);
        } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UniqueConstraintViolationException $exception) {
            $errors    = $errorFactory->createErrorCollection();
            $formatter = $formatterFactory->createFormatter($messagesNamespace);
            $title     = $formatter->formatMessage($errorMessage);
            $details   = null;
            $errorCode = JsonApiResponse::HTTP_CONFLICT;
            $errors->addDataError($title, $details, $errorCode);

            throw new JsonApiException($errors);
        }

        return $updated;
    }

    /**
     * @param int              $updated
     * @param string           $index
     * @param UriInterface     $requestUri
     * @param CrudInterface    $crud
     * @param EncoderInterface $encoder
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
        EncoderInterface $encoder
    ): ResponseInterface {
        $responses = static::defaultCreateResponses($requestUri, $encoder);
        if ($updated > 0 && ($model = $crud->read($index)) !== null) {
            assert(!($model instanceof PaginatedDataInterface));
            $response = $responses->getContentResponse($model);
        } else {
            $response = $responses->getCodeResponse(404);
        }

        return $response;
    }

    /**
     * @param string                      $index
     * @param UriInterface                $requestUri
     * @param JsonApiQueryParserInterface $queryParser
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     *
     * @return ResponseInterface
     */
    protected static function defaultDeleteHandler(
        string $index,
        UriInterface $requestUri,
        JsonApiQueryParserInterface $queryParser,
        CrudInterface $crud,
        EncoderInterface $encoder
    ): ResponseInterface {
        $validatedIndex = $queryParser->parse($index)->getIdentity();
        $crud->remove($validatedIndex);

        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = $responses->getCodeResponse(204);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param string                      $jsonRelName
     * @param string                      $modelRelName
     * @param UriInterface                $requestUri
     * @param string                      $requestBody
     * @param string                      $schemaClass
     * @param ModelSchemaInfoInterface    $schemaInfo
     * @param JsonApiQueryParserInterface $queryParser
     * @param JsonApiDataParserInterface  $dataValidator
     * @param CrudInterface               $parentCrud
     * @param EncoderInterface            $encoder
     * @param FactoryInterface            $errorFactory
     * @param FormatterFactoryInterface   $formatterFactory
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultAddInRelationshipHandler(
        string $index,
        string $jsonRelName,
        string $modelRelName,
        UriInterface $requestUri,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiQueryParserInterface $queryParser,
        JsonApiDataParserInterface $dataValidator,
        CrudInterface $parentCrud,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory
    ): ResponseInterface {
        /** @var SchemaInterface $schemaClass */
        assert(array_key_exists(SchemaInterface::class, class_implements($schemaClass)) === true);
        $modelClass = $schemaClass::MODEL;
        assert($schemaInfo->hasRelationship($modelClass, $modelRelName));
        assert($schemaInfo->getRelationshipType($modelClass, $modelRelName) === RelationshipTypes::BELONGS_TO_MANY);

        $jsonData = static::readJsonFromRequest($requestBody, $errorFactory, $formatterFactory);
        $captures = $dataValidator->assertRelationship($index, $jsonRelName, $jsonData)->getCaptures();
        $relIds   = $captures[$jsonRelName];

        $validatedIndex = $queryParser->parse($index)->getIdentity();
        $parentCrud->createInBelongsToManyRelationship($validatedIndex, $modelRelName, $relIds);

        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = $responses->getCodeResponse(204);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param string                      $jsonRelName
     * @param string                      $modelRelName
     * @param UriInterface                $requestUri
     * @param string                      $requestBody
     * @param string                      $schemaClass
     * @param ModelSchemaInfoInterface    $schemaInfo
     * @param JsonApiQueryParserInterface $queryParser
     * @param JsonApiDataParserInterface  $dataValidator
     * @param CrudInterface               $parentCrud
     * @param EncoderInterface            $encoder
     * @param FactoryInterface            $errorFactory
     * @param FormatterFactoryInterface   $formatterFactory
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultDeleteInRelationshipHandler(
        string $index,
        string $jsonRelName,
        string $modelRelName,
        UriInterface $requestUri,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiQueryParserInterface $queryParser,
        JsonApiDataParserInterface $dataValidator,
        CrudInterface $parentCrud,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory
    ): ResponseInterface {
        /** @var SchemaInterface $schemaClass */
        assert(array_key_exists(SchemaInterface::class, class_implements($schemaClass)) === true);
        $modelClass = $schemaClass::MODEL;
        assert($schemaInfo->hasRelationship($modelClass, $modelRelName));
        assert($schemaInfo->getRelationshipType($modelClass, $modelRelName) === RelationshipTypes::BELONGS_TO_MANY);

        $jsonData = static::readJsonFromRequest($requestBody, $errorFactory, $formatterFactory);
        $captures = $dataValidator->assertRelationship($index, $jsonRelName, $jsonData)->getCaptures();
        $relIds   = $captures[$jsonRelName];

        $validatedIndex = $queryParser->parse($index)->getIdentity();
        $parentCrud->removeInBelongsToManyRelationship($validatedIndex, $modelRelName, $relIds);

        $responses = static::defaultCreateResponses($requestUri, $encoder);
        $response  = $responses->getCodeResponse(204);

        return $response;
    }

    /** @noinspection PhpTooManyParametersInspection
     * @param string                      $index
     * @param string                      $jsonRelName
     * @param string                      $modelRelName
     * @param UriInterface                $requestUri
     * @param string                      $requestBody
     * @param string                      $schemaClass
     * @param ModelSchemaInfoInterface    $schemaInfo
     * @param JsonApiQueryParserInterface $queryParser
     * @param JsonApiDataParserInterface  $dataValidator
     * @param CrudInterface               $crud
     * @param EncoderInterface            $encoder
     * @param FactoryInterface            $errorFactory
     * @param FormatterFactoryInterface   $formatterFactory
     *
     * @return ResponseInterface
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected static function defaultReplaceInRelationship(
        string $index,
        string $jsonRelName,
        string $modelRelName,
        UriInterface $requestUri,
        string $requestBody,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo,
        JsonApiQueryParserInterface $queryParser,
        JsonApiDataParserInterface $dataValidator,
        CrudInterface $crud,
        EncoderInterface $encoder,
        FactoryInterface $errorFactory,
        FormatterFactoryInterface $formatterFactory
    ): ResponseInterface {
        /** @var SchemaInterface $schemaClass */
        assert(array_key_exists(SchemaInterface::class, class_implements($schemaClass)) === true);
        $modelClass = $schemaClass::MODEL;
        assert($schemaInfo->hasRelationship($modelClass, $modelRelName));
        assert(
            ($type =$schemaInfo->getRelationshipType($modelClass, $modelRelName)) === RelationshipTypes::BELONGS_TO ||
            $type === RelationshipTypes::BELONGS_TO_MANY
        );

        $jsonData = static::readJsonFromRequest($requestBody, $errorFactory, $formatterFactory);
        $captures = $dataValidator->assertRelationship($index, $jsonRelName, $jsonData)->getCaptures();

        // If we are here then we have something in 'data' section.

        $validatedIndex = $queryParser->parse($index)->getIdentity();
        list (, $attributes, $toMany) = static::mapSchemaDataToModelData($captures, $schemaClass, $schemaInfo);

        $updated = $crud->update($validatedIndex, $attributes, $toMany);

        return static::defaultUpdateResponse($updated, $index, $requestUri, $crud, $encoder);
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesClass
     *
     * @return JsonApiQueryParserInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateQueryParser(
        ContainerInterface $container,
        string $rulesClass = DefaultQueryValidationRules::class
    ): JsonApiQueryParserInterface {
        static::assertClassImplements($rulesClass, JsonApiQueryRulesInterface::class);

        /** @var JsonApiParserFactoryInterface $factory */
        $factory = $container->get(JsonApiParserFactoryInterface::class);
        $parser  = $factory->createQueryParser($rulesClass);

        return $parser;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesClass
     *
     * @return JsonApiDataParserInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateDataParser(
        ContainerInterface $container,
        string $rulesClass
    ): JsonApiDataParserInterface {
        static::assertClassImplements($rulesClass, JsonApiDataRulesInterface::class);

        /** @var JsonApiParserFactoryInterface $factory */
        $factory = $container->get(JsonApiParserFactoryInterface::class);
        $parser  = $factory->createDataParser($rulesClass);

        return $parser;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $rulesClass
     *
     * @return FormValidatorInterface
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected static function defaultCreateFormValidator(
        ContainerInterface $container,
        string $rulesClass
    ): FormValidatorInterface {
        static::assertClassImplements($rulesClass, FormRulesInterface::class);

        /** @var FormValidatorFactoryInterface $factory */
        $factory   = $container->get(FormValidatorFactoryInterface::class);
        $validator = $factory->createValidator($rulesClass);

        return $validator;
    }

    /**
     * @param ContainerInterface $container
     * @param string             $schemaClass
     *
     * @return ParametersMapperInterface
     *
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
        $mapper->selectRootSchemaByResourceType($jsonResourceType);

        return $mapper;
    }

    /**
     * @param JsonApiQueryParserInterface $queryParser
     * @param EncoderInterface            $encoder
     *
     * @return void
     */
    protected static function defaultApplyIncludesAndFieldSetsToEncoder(
        JsonApiQueryParserInterface $queryParser,
        EncoderInterface $encoder
    ): void {
        if ($queryParser->hasIncludes() === true) {
            $paths = array_keys($queryParser->getIncludes());
            $encoder->withIncludedPaths($paths);
        }
        if ($queryParser->hasFields() === true) {
            $encoder->withFieldSets($queryParser->getFields());
        }
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
     * @param UriInterface     $requestUri
     * @param EncoderInterface $encoder
     *
     * @return ResponsesInterface
     */
    protected static function defaultCreateResponses(
        UriInterface $requestUri,
        EncoderInterface $encoder
    ): ResponsesInterface {
        $encoder->forOriginalUri($requestUri);
        $responses = new Responses(
            new MediaType(MediaTypeInterface::JSON_API_TYPE, MediaTypeInterface::JSON_API_SUB_TYPE),
            $encoder
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
     * @param iterable                 $captures
     * @param string                   $schemaClass
     * @param ModelSchemaInfoInterface $schemaInfo
     *
     * @return array
     */
    protected static function mapSchemaDataToModelData(
        iterable $captures,
        string $schemaClass,
        ModelSchemaInfoInterface $schemaInfo
    ): array {
        static::assertClassImplements($schemaClass, SchemaInterface::class);

        /** @var SchemaInterface $schemaClass */
        static::assertClassImplements($modelClass = $schemaClass::MODEL, ModelInterface::class);
        /** @var ModelInterface $modelClass */

        $index         = null;
        $fields        = [];
        $toManyIndexes = [];
        foreach ($captures as $name => $value) {
            assert(is_string($name) === true);
            if ($name === DI::KEYWORD_ID) {
                $index = $value;
            } elseif ($schemaClass::hasAttributeMapping($name) === true) {
                $fieldName          = $schemaClass::getAttributeMapping($name);
                $fields[$fieldName] = $value;
            } elseif ($schemaClass::hasRelationshipMapping($name) === true) {
                $modelRelName = $schemaClass::getRelationshipMapping($name);
                $relType      = $schemaInfo->getRelationshipType($modelClass, $modelRelName);
                if ($relType === RelationshipTypes::BELONGS_TO) {
                    $fkName          = $schemaInfo->getForeignKey($modelClass, $modelRelName);
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

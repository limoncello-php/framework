<?php namespace Limoncello\Flute\Validation;

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

use Generator;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface as T;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\ValidatorInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Contracts\TranslatorInterface as ValidationTranslatorInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Validator\Captures;
use Limoncello\Validation\Validator\Compares;
use Limoncello\Validation\Validator\ExpressionsX;
use Limoncello\Validation\Validator\Generics;
use Limoncello\Validation\Validator\Types;
use Limoncello\Validation\Validator\ValidatorTrait;
use Limoncello\Validation\Validator\Values;
use Limoncello\Validation\Validator\Wrappers;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Validator implements ValidatorInterface
{
    use Captures, Compares, ExpressionsX, Generics, Types, Values, Wrappers, ValidatorTrait;

    /**
     * @return CaptureAggregatorInterface
     */
    abstract protected function createIdCaptureAggregator();

    /**
     * @return CaptureAggregatorInterface
     */
    abstract protected function createAttributesAndToOneCaptureAggregator();

    /**
     * @return CaptureAggregatorInterface
     */
    abstract protected function createToManyCaptureAggregator();

    /**
     * @var T
     */
    private $jsonApiTranslator;

    /**
     * @var ValidationTranslatorInterface
     */
    private $validationTranslator;

    /**
     * @var JsonSchemesInterface
     */
    private $jsonSchemes;

    /**
     * @var ModelSchemeInfoInterface
     */
    private $modelSchemes;

    /**
     * @var int
     */
    private $errorStatus;

    /**
     * @var RuleInterface
     */
    private $unlistedAttributeRule;

    /**
     * @var RuleInterface
     */
    private $unlistedRelationshipRule;

    /**
     * @param T                             $jsonApiTranslator
     * @param ValidationTranslatorInterface $validationTranslator
     * @param JsonSchemesInterface          $jsonSchemes
     * @param ModelSchemeInfoInterface      $modelSchemes
     * @param int                           $errorStatus
     * @param RuleInterface                 $unlistedAttrRule
     * @param RuleInterface                 $unlistedRelationRule
     */
    public function __construct(
        T $jsonApiTranslator,
        ValidationTranslatorInterface $validationTranslator,
        JsonSchemesInterface $jsonSchemes,
        ModelSchemeInfoInterface $modelSchemes,
        $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY,
        RuleInterface $unlistedAttrRule = null,
        RuleInterface $unlistedRelationRule = null
    ) {
        $this->jsonApiTranslator        = $jsonApiTranslator;
        $this->validationTranslator     = $validationTranslator;
        $this->jsonSchemes              = $jsonSchemes;
        $this->modelSchemes             = $modelSchemes;
        $this->errorStatus              = $errorStatus;
        $this->unlistedAttributeRule    = $unlistedAttrRule;
        $this->unlistedRelationshipRule = $unlistedRelationRule;
    }

    /**
     * @inheritdoc
     */
    public function assert(
        SchemaInterface $schema,
        array $jsonData,
        RuleInterface $idRule,
        array $attributeRules,
        array $toOneRules = [],
        array $toManyRules = []
    ): array {
        /** @var ErrorCollection $errors */
        /** @var CaptureAggregatorInterface $idAggregator */
        /** @var CaptureAggregatorInterface $attrTo1Aggregator */
        /** @var CaptureAggregatorInterface $toManyAggregator */
        list ($errors, $idAggregator, $attrTo1Aggregator, $toManyAggregator) =
            $this->check($schema, $jsonData, $idRule, $attributeRules, $toOneRules, $toManyRules);

        if ($errors->count() > 0) {
            throw new JsonApiException($errors, $this->getErrorStatus());
        }

        $idCaptureName = $this->getModelSchemes()->getPrimaryKey($schema::MODEL);
        $idValue       = array_key_exists($idCaptureName, $idAggregator->getCaptures()) === true ?
            $idAggregator->getCaptures()[$idCaptureName] : null;


        return [$idValue, $attrTo1Aggregator->getCaptures(), $toManyAggregator->getCaptures()];
    }

    /**
     * @inheritdoc
     */
    public function check(
        SchemaInterface $schema,
        array $jsonData,
        RuleInterface $idRule,
        array $attributeRules,
        array $toOneRules = [],
        array $toManyRules = []
    ): array {
        $errors            = $this->createErrorCollection();
        $idAggregator      = $this->createIdCaptureAggregator();
        $attrTo1Aggregator = $this->createAttributesAndToOneCaptureAggregator();
        $toManyAggregator  = $this->createToManyCaptureAggregator();

        $this->validateType($errors, $jsonData, $schema::TYPE);
        $this->validateId($errors, $schema, $jsonData, $idRule, $idAggregator);
        $this->validateAttributes($errors, $schema, $jsonData, $attributeRules, $attrTo1Aggregator);
        $relationshipCaptures = $this
            ->createRelationshipCaptures($schema, $toOneRules, $attrTo1Aggregator, $toManyRules, $toManyAggregator);
        $this->validateCaptures($errors, $jsonData, $relationshipCaptures);

        return [$errors, $idAggregator, $attrTo1Aggregator, $toManyAggregator];
    }

    /**
     * @return ErrorCollection
     */
    protected function createErrorCollection(): ErrorCollection
    {
        return new ErrorCollection(
            $this->getJsonApiTranslator(),
            $this->getValidationTranslator(),
            $this->getErrorStatus()
        );
    }

    /**
     * @return T
     */
    protected function getJsonApiTranslator(): T
    {
        return $this->jsonApiTranslator;
    }

    /**
     * @return ValidationTranslatorInterface
     */
    protected function getValidationTranslator(): ValidationTranslatorInterface
    {
        return $this->validationTranslator;
    }

    /**
     * @return JsonSchemesInterface
     */
    protected function getJsonSchemes(): JsonSchemesInterface
    {
        return $this->jsonSchemes;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->modelSchemes;
    }

    /**
     * @return int
     */
    protected function getErrorStatus(): int
    {
        return $this->errorStatus;
    }

    /**
     * @return RuleInterface
     */
    protected function getUnlistedRelationshipRule(): RuleInterface
    {
        return $this->unlistedRelationshipRule;
    }

    /**
     * @return RuleInterface
     */
    protected function getUnlistedAttributeRule(): RuleInterface
    {
        return $this->unlistedAttributeRule;
    }

    /**
     * @param ErrorCollection $errors
     * @param array           $jsonData
     * @param string          $expectedType
     *
     * @return void
     */
    private function validateType(ErrorCollection $errors, array $jsonData, string $expectedType)
    {
        $ignoreOthers = static::success();
        $rule         = static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::arrayX([
                DocumentInterface::KEYWORD_TYPE => static::required(static::equals($expectedType)),
            ], $ignoreOthers),
        ], $ignoreOthers);
        foreach ($this->validateRule($rule, $jsonData) as $error) {
            $errors->addValidationTypeError($error);
        }
    }

    /**
     * @param ErrorCollection            $errors
     * @param SchemaInterface            $schema
     * @param array                      $jsonData
     * @param RuleInterface              $idRule
     * @param CaptureAggregatorInterface $aggregator
     *
     * @return void
     */
    private function validateId(
        ErrorCollection $errors,
        SchemaInterface $schema,
        array $jsonData,
        RuleInterface $idRule,
        CaptureAggregatorInterface $aggregator
    ) {
        // will use primary column name as a capture name for `id`
        $captureName  = $this->getModelSchemes()->getPrimaryKey($schema::MODEL);
        $idRule       = static::singleCapture($captureName, $idRule, $aggregator);
        $ignoreOthers = static::success();
        $rule         = static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::arrayX([
                DocumentInterface::KEYWORD_ID => $idRule,
            ], $ignoreOthers)
        ], $ignoreOthers);
        foreach ($this->validateRule($rule, $jsonData) as $error) {
            $errors->addValidationIdError($error);
        }
    }

    /**
     * @param ErrorCollection            $errors
     * @param SchemaInterface            $schema
     * @param array                      $jsonData
     * @param RuleInterface[]            $attributeRules
     * @param CaptureAggregatorInterface $aggregator
     *
     * @return void
     */
    private function validateAttributes(
        ErrorCollection $errors,
        SchemaInterface $schema,
        array $jsonData,
        array $attributeRules,
        CaptureAggregatorInterface $aggregator
    ) {
        $attributes        =
            isset($jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_ATTRIBUTES]) === true ?
                $jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_ATTRIBUTES] : [];
        $attributeCaptures = [];
        foreach ($attributeRules as $name => $rule) {
            $captureName              = $schema->getAttributeMapping($name);
            $attributeCaptures[$name] = static::singleCapture($captureName, $rule, $aggregator);
        }
        $dataErrors = $this
            ->validateRule(static::arrayX($attributeCaptures, $this->getUnlistedAttributeRule()), $attributes);
        foreach ($dataErrors as $error) {
            $errors->addValidationAttributeError($error);
        }
    }

    /**
     * @param SchemaInterface            $schema
     * @param RuleInterface[]            $toOneRules
     * @param CaptureAggregatorInterface $toOneAggregator
     * @param RuleInterface[]            $toManyRules
     * @param CaptureAggregatorInterface $toManyAggregator
     *
     * @return array
     */
    private function createRelationshipCaptures(
        SchemaInterface $schema,
        array $toOneRules,
        CaptureAggregatorInterface $toOneAggregator,
        array $toManyRules,
        CaptureAggregatorInterface $toManyAggregator
    ): array {
        $modelClass           = $schema::MODEL;
        $relationshipCaptures = [];
        foreach ($toOneRules as $name => $rule) {
            $modelRelName   = $schema->getRelationshipMapping($name);
            $captureName    = $this->getModelSchemes()->getForeignKey($modelClass, $modelRelName);
            $expectedSchema = $this->getJsonSchemes()->getModelRelationshipSchema($modelClass, $modelRelName);
            $relationshipCaptures[$name] = $this->createSingleData(
                $name,
                static::equals($expectedSchema::TYPE),
                static::singleCapture($captureName, $rule, $toOneAggregator)
            );
        }
        foreach ($toManyRules as $name => $rule) {
            $modelRelName   = $schema->getRelationshipMapping($name);
            $expectedSchema = $this->getJsonSchemes()->getModelRelationshipSchema($modelClass, $modelRelName);
            $captureName    = $modelRelName;
            $relationshipCaptures[$name] = $this->createMultiData(
                $name,
                static::equals($expectedSchema::TYPE),
                static::multiCapture($captureName, $rule, $toManyAggregator)
            );
        }

        return $relationshipCaptures;
    }

    /**
     * @param ErrorCollection $errors
     * @param array           $jsonData
     * @param array           $relationshipCaptures
     *
     * @return void
     */
    private function validateCaptures(ErrorCollection $errors, array $jsonData, array $relationshipCaptures)
    {
        $relationships =
            isset($jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_RELATIONSHIPS]) === true ?
                $jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_RELATIONSHIPS] : [];
        $dataErrors    = $this->validateRule(
            static::arrayX($relationshipCaptures, $this->getUnlistedRelationshipRule()),
            $relationships
        );
        foreach ($dataErrors as $error) {
            $errors->addValidationRelationshipError($error);
        }
    }

    /**
     * @param RuleInterface $typeRule
     * @param RuleInterface $idRule
     *
     * @return RuleInterface
     */
    private function createOptionalIdentity(RuleInterface $typeRule, RuleInterface $idRule): RuleInterface
    {
        return self::andX(self::isArray(), self::arrayX([
            DocumentInterface::KEYWORD_TYPE => $typeRule,
            DocumentInterface::KEYWORD_ID   => $idRule,
        ])->disableAutoParameterNames());
    }

    /**
     * @param string        $name
     * @param RuleInterface $typeRule
     * @param RuleInterface $idRule
     *
     * @return RuleInterface
     */
    private function createSingleData($name, RuleInterface $typeRule, RuleInterface $idRule): RuleInterface
    {
        $identityRule  = $this->createOptionalIdentity($typeRule, $idRule);
        $nullValueRule = static::andX($idRule, static::isNull());

        return static::andX(static::isArray(), static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::orX($identityRule, $nullValueRule),
        ])->disableAutoParameterNames()->setParameterName($name));
    }

    /**
     * @param string        $name
     * @param RuleInterface $typeRule
     * @param RuleInterface $idRule
     *
     * @return RuleInterface
     */
    private function createMultiData(string $name, RuleInterface $typeRule, RuleInterface $idRule): RuleInterface
    {
        $identityRule = $this->createOptionalIdentity($typeRule, $idRule);

        return static::andX(static::isArray(), static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::andX(static::isArray(), static::eachX($identityRule)),
        ])->disableAutoParameterNames()->setParameterName($name));
    }

    /**
     * @param RuleInterface $rule
     * @param mixed         $input
     *
     * @return Generator
     */
    private function validateRule(RuleInterface $rule, $input): Generator
    {
        return static::validateData($rule, $input, new ErrorAggregator());
    }
}

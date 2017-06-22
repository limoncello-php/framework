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

use Doctrine\DBAL\Types\Type;
use Generator;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Flute\Contracts\I18n\TranslatorInterface as T;
use Limoncello\Flute\Contracts\Schema\JsonSchemesInterface;
use Limoncello\Flute\Contracts\Schema\SchemaInterface;
use Limoncello\Flute\Contracts\Validation\ValidatorInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\Types\DateBaseType;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Contracts\TranslatorInterface as ValidationTranslatorInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Validator\Captures;
use Limoncello\Validation\Validator\Compares;
use Limoncello\Validation\Validator\Converters;
use Limoncello\Validation\Validator\ExpressionsX;
use Limoncello\Validation\Validator\Generics;
use Limoncello\Validation\Validator\Types;
use Limoncello\Validation\Validator\Values;
use Limoncello\Validation\Validator\Wrappers;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Validator implements ValidatorInterface
{
    use Captures, Compares, Converters, ExpressionsX, Generics, Types, Values, Wrappers;

    /** Rule description index */
    const RULE_INDEX = 0;

    /** Rule description index */
    const RULE_ATTRIBUTES = self::RULE_INDEX + 1;

    /** Rule description index */
    const RULE_TO_ONE = self::RULE_ATTRIBUTES + 1;

    /** Rule description index */
    const RULE_TO_MANY = self::RULE_TO_ONE + 1;

    /** Rule description index */
    const RULE_UNLISTED_ATTRIBUTE = self::RULE_TO_MANY + 1;

    /** Rule description index */
    const RULE_UNLISTED_RELATIONSHIP = self::RULE_UNLISTED_ATTRIBUTE + 1;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var SchemaInterface|null
     */
    private $schema = null;

    /**
     * @var string
     */
    private $jsonType;

    /**
     * @var RuleInterface[]
     */
    private $rules;

    /**
     * @var int
     */
    private $errorStatus;

    /**
     * @var null|ErrorCollection
     */
    private $errorCollection = null;

    /**
     * @var null|CaptureAggregatorInterface
     */
    private $captureAggregator = null;

    /**
     * @param ContainerInterface $container
     * @param string             $jsonType
     * @param RuleInterface[]    $rules
     * @param int                $errorStatus
     */
    public function __construct(
        ContainerInterface $container,
        string $jsonType,
        array $rules,
        $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ) {
        if (array_key_exists(static::RULE_UNLISTED_ATTRIBUTE, $rules) === false) {
            $rules[static::RULE_UNLISTED_ATTRIBUTE] = static::fail();
        }
        if (array_key_exists(static::RULE_UNLISTED_RELATIONSHIP, $rules) === false) {
            $rules[static::RULE_UNLISTED_RELATIONSHIP] = static::fail();
        }

        $this->container   = $container;
        $this->jsonType    = $jsonType;
        $this->rules       = $rules;
        $this->errorStatus = $errorStatus;
    }

    /**
     * @inheritdoc
     */
    public function assert(array $jsonData): ValidatorInterface
    {
        if ($this->check($jsonData) === false) {
            throw new JsonApiException($this->getErrors(), $this->getErrorStatus());
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function check(array $jsonData): bool
    {
        $this->resetErrors();
        $this->resetCaptureAggregator();

        $this->validateType($jsonData);
        $this->validateId($jsonData);
        $this->validateAttributes($jsonData);
        $this->validateRelationshipCaptures($jsonData, $this->createRelationshipCaptures());

        $hasNoErrors = $this->getErrors()->count() <= 0;

        return $hasNoErrors;
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): ErrorCollection
    {
        $this->errorCollection !== null ?: $this->resetErrors();

        return $this->errorCollection;
    }

    /**
     * @inheritdoc
     */
    public function getCaptures(): array
    {
        $captures = $this->getCaptureAggregator()->getCaptures();

        return $captures;
    }

    /**
     * @return void
     */
    protected function resetErrors()
    {
        $this->errorCollection = $this->createErrorCollection();
    }

    /**
     * @return ErrorCollection
     */
    protected function createErrorCollection(): ErrorCollection
    {
        return new ErrorCollection(
            $this->getContainer()->get(T::class),
            $this->getContainer()->get(ValidationTranslatorInterface::class),
            $this->getErrorStatus()
        );
    }

    /**
     * @return string
     */
    protected function getJsonType(): string
    {
        return $this->jsonType;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return ModelSchemeInfoInterface
     */
    protected function getModelSchemes(): ModelSchemeInfoInterface
    {
        return $this->getContainer()->get(ModelSchemeInfoInterface::class);
    }

    /**
     * @return SchemaInterface
     */
    protected function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            /** @var JsonSchemesInterface $jsonSchemes */
            $jsonSchemes  = $this->getContainer()->get(JsonSchemesInterface::class);
            $this->schema = $jsonSchemes->getSchemaByResourceType($this->getJsonType());
        }

        return $this->schema;
    }

    /**
     * @return RuleInterface[]
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return int
     */
    protected function getErrorStatus(): int
    {
        return $this->errorStatus;
    }

    /**
     * @return CaptureAggregatorInterface
     */
    protected function createCaptureAggregator(): CaptureAggregatorInterface
    {
        return new CaptureAggregator();
    }

    /**
     * @return ErrorAggregatorInterface
     */
    protected function createErrorAggregator(): ErrorAggregatorInterface
    {
        return new ErrorAggregator();
    }

    /**
     * @return CaptureAggregatorInterface
     */
    public function getCaptureAggregator(): CaptureAggregatorInterface
    {
        $this->captureAggregator !== null ?: $this->resetCaptureAggregator();

        return $this->captureAggregator;
    }

    /**
     * @return void
     */
    protected function resetCaptureAggregator()
    {
        $this->captureAggregator = $this->createCaptureAggregator();
    }

    /**
     * @param array $jsonData
     *
     * @return void
     */
    private function validateType(array $jsonData)
    {
        $expectedType = $this->getSchema()::TYPE;
        $ignoreOthers = static::success();
        $rule         = static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::arrayX([
                DocumentInterface::KEYWORD_TYPE => static::required(static::equals($expectedType)),
            ], $ignoreOthers),
        ], $ignoreOthers);
        foreach ($this->validateRule($rule, $jsonData) as $error) {
            $this->getErrors()->addValidationTypeError($error);
        }
    }

    /**
     * @param array $jsonData
     *
     * @return void
     */
    private function validateId(array $jsonData)
    {
        $idRule = $this->getRules()[static::RULE_INDEX] ?? static::success();
        assert($idRule instanceof RuleInterface);

        // will use primary column name as a capture name for `id`
        $captureName  = $this->getModelSchemes()->getPrimaryKey($this->getSchema()::MODEL);
        $idRule       = static::singleCapture($captureName, $idRule, $this->getCaptureAggregator());
        $ignoreOthers = static::success();
        $rule         = static::arrayX([
            DocumentInterface::KEYWORD_DATA => static::arrayX([
                DocumentInterface::KEYWORD_ID => $idRule,
            ], $ignoreOthers)
        ], $ignoreOthers);
        foreach ($this->validateRule($rule, $jsonData) as $error) {
            $this->getErrors()->addValidationIdError($error);
        }
    }

    /**
     * @param array $jsonData
     *
     * @return void
     */
    private function validateAttributes(array $jsonData)
    {
        $attributeRules     = $this->getRules()[static::RULE_ATTRIBUTES] ?? [];
        $schema             = $this->getSchema();
        $attributeTypes     = $this->getModelSchemes()->getAttributeTypes($schema::MODEL);
        $createTypedCapture = function (string $name, RuleInterface $rule) use ($attributeTypes, $schema) {
            $captureName    = $schema->getAttributeMapping($name);
            $attributeType  = $attributeTypes[$captureName] ?? Type::STRING;
            $untypedCapture = static::singleCapture($captureName, $rule, $this->getCaptureAggregator());
            switch ($attributeType) {
                case Type::INTEGER:
                    $capture = static::toInt($untypedCapture);
                    break;
                case Type::FLOAT:
                    $capture = static::toFloat($untypedCapture);
                    break;
                case Type::BOOLEAN:
                    $capture = static::toBool($untypedCapture);
                    break;
                case Type::DATE:
                case Type::DATETIME:
                    $capture = static::toDateTime($untypedCapture, DateBaseType::JSON_API_FORMAT);
                    break;
                default:
                    $capture = $untypedCapture;
                    break;
            }

            return $capture;
        };

        $attributeCaptures = [];
        foreach ($attributeRules as $name => $rule) {
            assert(is_string($name) === true && empty($name) === false && $rule instanceof RuleInterface);
            $attributeCaptures[$name] = $createTypedCapture($name, $rule);
        }

        $attributes   = $jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_ATTRIBUTES] ?? [];
        $unlistedRule = $this->getRules()[static::RULE_UNLISTED_ATTRIBUTE] ?? null;
        $dataErrors   = $this->validateRule(static::arrayX($attributeCaptures, $unlistedRule), $attributes);

        foreach ($dataErrors as $error) {
            $this->getErrors()->addValidationAttributeError($error);
        }
    }

    /**
     * @return array
     */
    private function createRelationshipCaptures(): array
    {
        $toOneRules   = $this->getRules()[static::RULE_TO_ONE] ?? [];
        $toManyRules  = $this->getRules()[static::RULE_TO_MANY] ?? [];
        $aggregator   = $this->getCaptureAggregator();
        $jsonSchemes  = $this->getContainer()->get(JsonSchemesInterface::class);
        $schema       = $this->getSchema();
        $modelSchemes = $this->getModelSchemes();
        $modelClass   = $schema::MODEL;

        $relationshipCaptures = [];
        foreach ($toOneRules as $name => $rule) {
            assert(is_string($name) === true && empty($name) === false && $rule instanceof RuleInterface);
            $modelRelName   = $schema->getRelationshipMapping($name);
            $captureName    = $modelSchemes->getForeignKey($modelClass, $modelRelName);
            $expectedSchema = $jsonSchemes->getModelRelationshipSchema($modelClass, $modelRelName);
            $relationshipCaptures[$name] = $this->createSingleData(
                $name,
                static::equals($expectedSchema::TYPE),
                static::singleCapture($captureName, $rule, $aggregator)
            );
        }
        foreach ($toManyRules as $name => $rule) {
            assert(is_string($name) === true && empty($name) === false && $rule instanceof RuleInterface);
            $modelRelName   = $schema->getRelationshipMapping($name);
            $expectedSchema = $jsonSchemes->getModelRelationshipSchema($modelClass, $modelRelName);
            $captureName    = $modelRelName;
            $relationshipCaptures[$name] = $this->createMultiData(
                $name,
                static::equals($expectedSchema::TYPE),
                static::multiCapture($captureName, $rule, $aggregator)
            );
        }

        return $relationshipCaptures;
    }

    /**
     * @param array $jsonData
     * @param array $relationshipCaptures
     *
     * @return void
     */
    private function validateRelationshipCaptures(array $jsonData, array $relationshipCaptures)
    {
        $relationships = $jsonData[DocumentInterface::KEYWORD_DATA][DocumentInterface::KEYWORD_RELATIONSHIPS] ?? [];
        $unlistedRule  = $this->getRules()[static::RULE_UNLISTED_RELATIONSHIP] ?? null;
        $dataErrors    = $this->validateRule(static::arrayX($relationshipCaptures, $unlistedRule), $relationships);
        foreach ($dataErrors as $error) {
            $this->getErrors()->addValidationRelationshipError($error);
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
        foreach ($rule->validate($input) as $error) {
            yield $error;
        };

        $aggregator = $this->createErrorAggregator();
        $rule->onFinish($aggregator);

        foreach ($aggregator->get() as $error) {
            yield $error;
        }
    }
}

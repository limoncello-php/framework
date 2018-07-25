<?php namespace Limoncello\Flute\Validation\JsonApi;

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

use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesSerializerInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataValidatingParserInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Flute\Validation\Rules\RelationshipRulesTrait;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Validator\BaseValidator;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as DI;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DataParser extends BaseValidator implements JsonApiDataValidatingParserInterface
{
    use RelationshipRulesTrait;

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
     * NOTE: Despite the type it is just a string so only static methods can be called from the interface.
     *
     * @var JsonApiDataRulesSerializerInterface|string
     */
    private $serializerClass;

    /**
     * @var int
     */
    private $errorStatus;

    /**
     * @var ContextStorageInterface
     */
    private $context;

    /**
     * @var JsonApiErrorCollection
     */
    private $jsonApiErrors;

    /**
     * @var array
     */
    private $blocks;

    /**
     * @var array
     */
    private $idRule;

    /**
     * @var array
     */
    private $typeRule;

    /**
     * @var int[]
     */
    private $attributeRules;

    /**
     * @var int[]
     */
    private $toOneRules;

    /**
     * @var int[]
     */
    private $toManyRules;

    /**
     * @var bool
     */
    private $isIgnoreUnknowns;

    /**
     * @var FormatterInterface|null
     */
    private $formatter;

    /**
     * @var FormatterFactoryInterface
     */
    private $formatterFactory;

    /**
     * @param string                    $rulesClass
     * @param string                    $serializerClass
     * @param array                     $serializedData
     * @param ContextStorageInterface   $context
     * @param JsonApiErrorCollection    $jsonErrors
     * @param FormatterFactoryInterface $formatterFactory
     * @param int                       $errorStatus
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        string $rulesClass,
        string $serializerClass,
        array $serializedData,
        ContextStorageInterface $context,
        JsonApiErrorCollection $jsonErrors,
        FormatterFactoryInterface $formatterFactory,
        int $errorStatus = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY
    ) {
        $this
            ->setSerializerClass($serializerClass)
            ->setContext($context)
            ->setJsonApiErrors($jsonErrors)
            ->setFormatterFactory($formatterFactory);

        $this->blocks      = $this->getSerializer()::readBlocks($serializedData);
        $ruleSet           = $this->getSerializer()::readRules($rulesClass, $serializedData);
        $this->idRule      = $this->getSerializer()::readIdRuleIndexes($ruleSet);
        $this->typeRule    = $this->getSerializer()::readTypeRuleIndexes($ruleSet);
        $this->errorStatus = $errorStatus;

        $this
            ->setAttributeRules($this->getSerializer()::readAttributeRulesIndexes($ruleSet))
            ->setToOneIndexes($this->getSerializer()::readToOneRulesIndexes($ruleSet))
            ->setToManyIndexes($this->getSerializer()::readToManyRulesIndexes($ruleSet))
            ->disableIgnoreUnknowns();

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function assert($jsonData): JsonApiDataValidatingParserInterface
    {
        if ($this->validate($jsonData) === false) {
            throw new JsonApiException($this->getJsonApiErrorCollection(), $this->getErrorStatus());
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): bool
    {
        if (is_array($input) === true) {
            return $this->parse($input);
        }

        $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
        $details = $this->formatMessage(ErrorCodes::INVALID_VALUE);
        $this->getJsonApiErrorCollection()->addDataError($title, $details, $this->getErrorStatus());

        return false;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function parse(array $input): bool
    {
        $this->reInitAggregatorsIfNeeded();

        $this
            ->validateType($input)
            ->validateId($input)
            ->validateAttributes($input)
            ->validateRelationships($input);

        $hasNoErrors = $this->getJsonApiErrorCollection()->count() <= 0;

        return $hasNoErrors;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function parseRelationship(string $name, array $jsonData): bool
    {
        $this->reInitAggregatorsIfNeeded();

        $isFoundInToOne  = array_key_exists($name, $this->getSerializer()::readRulesIndexes($this->getToOneRules()));
        $isFoundInToMany = $isFoundInToOne === false &&
            array_key_exists($name, $this->getSerializer()::readRulesIndexes($this->getToManyRules()));

        if ($isFoundInToOne === false && $isFoundInToMany === false) {
            $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            $details = $this->formatMessage(ErrorCodes::UNKNOWN_RELATIONSHIP);
            $status  = $this->getErrorStatus();
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, $status);
        } else {
            assert($isFoundInToOne xor $isFoundInToMany);
            $ruleIndexes = $this->getSerializer()::readSingleRuleIndexes(
                $isFoundInToOne === true ? $this->getToOneRules() : $this->getToManyRules(),
                $name
            );

            // now execute validation rules
            $this->executeStarts($this->getSerializer()::readRuleStartIndexes($ruleIndexes));
            $ruleIndex = $this->getSerializer()::readRuleIndex($ruleIndexes);
            $isFoundInToOne === true ?
                $this->validateAsToOneRelationship($ruleIndex, $name, $jsonData) :
                $this->validateAsToManyRelationship($ruleIndex, $name, $jsonData);
            $this->executeEnds($this->getSerializer()::readRuleEndIndexes($ruleIndexes));

            if (count($this->getErrorAggregator()) > 0) {
                foreach ($this->getErrorAggregator()->get() as $error) {
                    $this->getJsonApiErrorCollection()->addValidationRelationshipError($error);
                }
                $this->getErrorAggregator()->clear();
            }
        }

        $hasNoErrors = count($this->getJsonApiErrorCollection()) <= 0;

        return $hasNoErrors;
    }

    /**
     * @inheritdoc
     */
    public function assertRelationship(string $name, array $jsonData): JsonApiDataValidatingParserInterface
    {
        if ($this->parseRelationship($name, $jsonData) === false) {
            throw new JsonApiException($this->getJsonApiErrorCollection(), $this->getErrorStatus());
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getJsonApiErrors(): array
    {
        return $this->getJsonApiErrorCollection()->getArrayCopy();
    }

    /**
     * @inheritdoc
     */
    public function getJsonApiCaptures(): array
    {
        return $this->getCaptures();
    }

    /**
     * @return BaseValidator
     */
    protected function resetAggregators(): BaseValidator
    {
        $self = parent::resetAggregators();

        $this->getContext()->clear();

        return $self;
    }

    /**
     * @param string $serializerClass
     *
     * @return self
     */
    protected function setSerializerClass(string $serializerClass): self
    {
        assert(
            class_exists($serializerClass) === true &&
            in_array(JsonApiDataRulesSerializerInterface::class, class_implements($serializerClass)) === true
        );

        $this->serializerClass = $serializerClass;

        return $this;
    }

    /**
     * @return JsonApiDataRulesSerializerInterface|string
     */
    protected function getSerializer()
    {
        return $this->serializerClass;
    }

    /**
     * @param array $jsonData
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateType(array $jsonData): self
    {
        // execute start(s)
        $starts = $this->getSerializer()::readRuleStartIndexes($this->getTypeRule());
        $this->executeStarts($starts);

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_TYPE, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            // execute main validation block(s)
            $index = $this->getSerializer()::readRuleIndex($this->getTypeRule());
            $this->executeBlock($data[DI::KEYWORD_TYPE], $index);
        } else {
            $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            $details = $this->formatMessage(ErrorCodes::TYPE_MISSING);
            $this->getJsonApiErrorCollection()->addDataTypeError($title, $details, $this->getErrorStatus());
        }

        // execute end(s)
        $ends = $this->getSerializer()::readRuleEndIndexes($this->getTypeRule());
        $this->executeEnds($ends);

        if (count($this->getErrorAggregator()) > 0) {
            $title = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()
                    ->addDataTypeError($title, $this->getMessage($error), $this->getErrorStatus());
            }
            $this->getErrorAggregator()->clear();
        }

        return $this;
    }

    /**
     * @param array $jsonData
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateId(array $jsonData): self
    {
        // execute start(s)
        $starts = $this->getSerializer()::readRuleStartIndexes($this->getIdRule());
        $this->executeStarts($starts);

        // execute main validation block(s)
        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_ID, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            $index = $this->getSerializer()::readRuleIndex($this->getIdRule());
            $this->executeBlock($data[DI::KEYWORD_ID], $index);
        }

        // execute end(s)
        $ends = $this->getSerializer()::readRuleEndIndexes($this->getIdRule());
        $this->executeEnds($ends);

        if (count($this->getErrorAggregator()) > 0) {
            $title = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()
                    ->addDataIdError($title, $this->getMessage($error), $this->getErrorStatus());
            }
            $this->getErrorAggregator()->clear();
        }

        return $this;
    }

    /**
     * @param array $jsonData
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateAttributes(array $jsonData): self
    {
        // execute start(s)
        $starts = $this->getSerializer()::readRulesStartIndexes($this->getAttributeRules());
        $this->executeStarts($starts);

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_ATTRIBUTES, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            if (is_array($attributes = $data[DI::KEYWORD_ATTRIBUTES]) === false) {
                $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
                $details = $this->formatMessage(ErrorCodes::INVALID_ATTRIBUTES);
                $this->getJsonApiErrorCollection()->addAttributesError($title, $details, $this->getErrorStatus());
            } else {
                // execute main validation block(s)
                foreach ($attributes as $name => $value) {
                    if (($index = $this->getAttributeIndex($name)) !== null) {
                        $this->executeBlock($value, $index);
                    } elseif ($this->isIgnoreUnknowns() === false) {
                        $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
                        $details = $this->formatMessage(ErrorCodes::UNKNOWN_ATTRIBUTE);
                        $status  = $this->getErrorStatus();
                        $this->getJsonApiErrorCollection()->addDataAttributeError($name, $title, $details, $status);
                    }
                }
            }
        }

        // execute end(s)
        $ends = $this->getSerializer()::readRulesEndIndexes($this->getAttributeRules());
        $this->executeEnds($ends);

        if (count($this->getErrorAggregator()) > 0) {
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()->addValidationAttributeError($error);
            }
            $this->getErrorAggregator()->clear();
        }

        return $this;
    }

    /**
     * @param array $jsonData
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateRelationships(array $jsonData): self
    {
        // execute start(s)
        $starts = array_merge(
            $this->getSerializer()::readRulesStartIndexes($this->getToOneRules()),
            $this->getSerializer()::readRulesStartIndexes($this->getToManyRules())
        );
        $this->executeStarts($starts);

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_RELATIONSHIPS, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            if (is_array($relationships = $data[DI::KEYWORD_RELATIONSHIPS]) === false) {
                $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
                $details = $this->formatMessage(ErrorCodes::INVALID_RELATIONSHIP_TYPE);
                $this->getJsonApiErrorCollection()->addRelationshipsError($title, $details, $this->getErrorStatus());
            } else {
                // ok we got to something that could be null or a valid relationship
                $toOneIndexes  = $this->getSerializer()::readRulesIndexes($this->getToOneRules());
                $toManyIndexes = $this->getSerializer()::readRulesIndexes($this->getToManyRules());

                foreach ($relationships as $name => $relationship) {
                    if (array_key_exists($name, $toOneIndexes) === true) {
                        // it might be to1 relationship
                        $this->validateAsToOneRelationship($toOneIndexes[$name], $name, $relationship);
                    } elseif (array_key_exists($name, $toManyIndexes) === true) {
                        // it might be toMany relationship
                        $this->validateAsToManyRelationship($toManyIndexes[$name], $name, $relationship);
                    } else {
                        // unknown relationship
                        $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
                        $details = $this->formatMessage(ErrorCodes::UNKNOWN_RELATIONSHIP);
                        $status  = $this->getErrorStatus();
                        $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, $status);
                    }
                }
            }
        }

        // execute end(s)
        $ends = array_merge(
            $this->getSerializer()::readRulesEndIndexes($this->getToOneRules()),
            $this->getSerializer()::readRulesEndIndexes($this->getToManyRules())
        );
        $this->executeEnds($ends);

        if (count($this->getErrorAggregator()) > 0) {
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()->addValidationRelationshipError($error);
            }
            $this->getErrorAggregator()->clear();
        }

        return $this;
    }

    /**
     * @param int    $index
     * @param string $name
     * @param mixed  $mightBeRelationship
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateAsToOneRelationship(int $index, string $name, $mightBeRelationship): void
    {
        if (is_array($mightBeRelationship) === true &&
            array_key_exists(DI::KEYWORD_DATA, $mightBeRelationship) === true &&
            ($parsed = $this->parseSingleRelationship($mightBeRelationship[DI::KEYWORD_DATA])) !== false
        ) {
            // All right we got something. Now pass it to a validation rule.
            $this->executeBlock($parsed, $index);
        } else {
            $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            $details = $this->formatMessage(ErrorCodes::INVALID_RELATIONSHIP);
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, $this->getErrorStatus());
        }
    }

    /**
     * @param int    $index
     * @param string $name
     * @param mixed  $mightBeRelationship
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateAsToManyRelationship(int $index, string $name, $mightBeRelationship): void
    {
        $isParsed       = true;
        $collectedPairs = [];
        if (is_array($mightBeRelationship) === true &&
            array_key_exists(DI::KEYWORD_DATA, $mightBeRelationship) === true &&
            is_array($data = $mightBeRelationship[DI::KEYWORD_DATA]) === true
        ) {
            foreach ($data as $mightTypeAndId) {
                // we accept only pairs of type and id (no `null`s are accepted).
                if (is_array($parsed = $this->parseSingleRelationship($mightTypeAndId)) === true) {
                    $collectedPairs[] = $parsed;
                } else {
                    $isParsed = false;
                    break;
                }
            }
        } else {
            $isParsed = false;
        }

        if ($isParsed === true) {
            // All right we got something. Now pass it to a validation rule.
            $this->executeBlock($collectedPairs, $index);
        } else {
            $title   = $this->formatMessage(ErrorCodes::INVALID_VALUE);
            $details = $this->formatMessage(ErrorCodes::INVALID_RELATIONSHIP);
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, $this->getErrorStatus());
        }
    }

    /**
     * @param mixed $data
     *
     * @return array|null|false Either `array` ($type => $id), or `null`, or `false` on error.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function parseSingleRelationship($data)
    {
        if ($data === null) {
            $result = null;
        } elseif (is_array($data) === true &&
            array_key_exists(DI::KEYWORD_TYPE, $data) === true &&
            array_key_exists(DI::KEYWORD_ID, $data) === true &&
            is_scalar($type = $data[DI::KEYWORD_TYPE]) === true &&
            is_scalar($index = $data[DI::KEYWORD_ID]) === true
        ) {
            $result = [$type => $index];
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Re-initializes internal aggregators for captures, errors, etc.
     */
    private function reInitAggregatorsIfNeeded(): void
    {
        $this->areAggregatorsDirty() === false ?: $this->resetAggregators();
    }

    /**
     * @param mixed $input
     * @param int   $index
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeBlock($input, int $index): void
    {
        BlockInterpreter::executeBlock(
            $input,
            $index,
            $this->getBlocks(),
            $this->getContext(),
            $this->getCaptureAggregator(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param array $indexes
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeStarts(array $indexes): void
    {
        BlockInterpreter::executeStarts(
            $indexes,
            $this->getBlocks(),
            $this->getContext(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param array $indexes
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeEnds(array $indexes): void
    {
        BlockInterpreter::executeEnds(
            $indexes,
            $this->getBlocks(),
            $this->getContext(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param ErrorInterface $error
     *
     * @return string
     */
    private function getMessage(ErrorInterface $error): string
    {
        $context = $error->getMessageContext();
        $args    = $context === null ? [] : $context;
        $message = $this->formatMessage($error->getMessageCode(), $args);

        return $message;
    }

    /**
     * @return array
     */
    protected function getIdRule(): array
    {
        return $this->idRule;
    }

    /**
     * @return array
     */
    protected function getTypeRule(): array
    {
        return $this->typeRule;
    }

    /**
     * @return ContextStorageInterface
     */
    protected function getContext(): ContextStorageInterface
    {
        return $this->context;
    }

    /**
     * @param ContextStorageInterface $context
     *
     * @return self
     */
    protected function setContext(ContextStorageInterface $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @return JsonApiErrorCollection
     */
    protected function getJsonApiErrorCollection(): JsonApiErrorCollection
    {
        return $this->jsonApiErrors;
    }

    /**
     * @param JsonApiErrorCollection $errors
     *
     * @return self
     */
    protected function setJsonApiErrors(JsonApiErrorCollection $errors): self
    {
        $this->jsonApiErrors = $errors;

        return $this;
    }

    /**
     * @return int
     */
    protected function getErrorStatus(): int
    {
        return $this->errorStatus;
    }

    /**
     * @return bool
     */
    protected function isIgnoreUnknowns(): bool
    {
        return $this->isIgnoreUnknowns;
    }

    /**
     * @return self
     */
    protected function enableIgnoreUnknowns(): self
    {
        $this->isIgnoreUnknowns = true;

        return $this;
    }

    /**
     * @return self
     */
    protected function disableIgnoreUnknowns(): self
    {
        $this->isIgnoreUnknowns = false;

        return $this;
    }

    /**
     * @param array $rules
     *
     * @return self
     */
    private function setAttributeRules(array $rules): self
    {
        assert($this->debugCheckIndexesExist($rules));

        $this->attributeRules = $rules;

        return $this;
    }

    /**
     * @param array $rules
     *
     * @return self
     */
    private function setToOneIndexes(array $rules): self
    {
        assert($this->debugCheckIndexesExist($rules));

        $this->toOneRules = $rules;

        return $this;
    }

    /**
     * @param array $rules
     *
     * @return self
     */
    private function setToManyIndexes(array $rules): self
    {
        assert($this->debugCheckIndexesExist($rules));

        $this->toManyRules = $rules;

        return $this;
    }

    /**
     * @return int[]
     */
    protected function getAttributeRules(): array
    {
        return $this->attributeRules;
    }

    /**
     * @return int[]
     */
    protected function getToOneRules(): array
    {
        return $this->toOneRules;
    }

    /**
     * @return int[]
     */
    protected function getToManyRules(): array
    {
        return $this->toManyRules;
    }

    /**
     * @return array
     */
    private function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @return FormatterInterface
     */
    protected function getFormatter(): FormatterInterface
    {
        if ($this->formatter === null) {
            $this->formatter = $this->formatterFactory->createFormatter(FluteSettings::VALIDATION_NAMESPACE);
        }

        return $this->formatter;
    }

    /**
     * @param FormatterFactoryInterface $formatterFactory
     *
     * @return self
     */
    protected function setFormatterFactory(FormatterFactoryInterface $formatterFactory): self
    {
        $this->formatterFactory = $formatterFactory;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return int|null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getAttributeIndex(string $name): ?int
    {
        $indexes = $this->getSerializer()::readRulesIndexes($this->getAttributeRules());
        $index   = $indexes[$name] ?? null;

        return $index;
    }

    /**
     * @param int   $messageId
     * @param array $args
     *
     * @return string
     */
    private function formatMessage(int $messageId, array $args = []): string
    {
        $message = $this->getFormatter()->formatMessage($messageId, $args);

        return $message;
    }

    /**
     * @param array $rules
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function debugCheckIndexesExist(array $rules): bool
    {
        $allOk = true;

        $indexes = array_merge(
            $this->getSerializer()::readRulesIndexes($rules),
            $this->getSerializer()::readRulesStartIndexes($rules),
            $this->getSerializer()::readRulesEndIndexes($rules)
        );

        foreach ($indexes as $index) {
            $allOk = $allOk && is_int($index) && $this->getSerializer()::hasRule($index, $this->getBlocks());
        }

        return $allOk;
    }
}

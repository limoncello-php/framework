<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\JsonApi;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Common\Reflection\ClassIsTrait;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiDataRulesSerializerInterface;
use Limoncello\Flute\Http\JsonApiResponse;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Flute\Validation\Rules\RelationshipRulesTrait;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\BlockInterpreter;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface as DI;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use function array_key_exists;
use function array_merge;
use function assert;
use function count;
use function is_array;
use function is_int;
use function is_scalar;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DataParser implements JsonApiDataParserInterface
{
    use RelationshipRulesTrait, ClassIsTrait;

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
     * @var int|null
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
     * @var ErrorAggregatorInterface
     */
    private $errorAggregator;

    /**
     * @var CaptureAggregatorInterface
     */
    private $captureAggregator;

    /**
     * @param string                    $rulesClass
     * @param string                    $serializerClass
     * @param array                     $serializedData
     * @param ContextStorageInterface   $context
     * @param JsonApiErrorCollection    $jsonErrors
     * @param FormatterFactoryInterface $formatterFactory
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        string $rulesClass,
        string $serializerClass,
        array $serializedData,
        ContextStorageInterface $context,
        JsonApiErrorCollection $jsonErrors,
        FormatterFactoryInterface $formatterFactory
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
        $this->errorStatus = null;

        $this
            ->setAttributeRules($this->getSerializer()::readAttributeRulesIndexes($ruleSet))
            ->setToOneIndexes($this->getSerializer()::readToOneRulesIndexes($ruleSet))
            ->setToManyIndexes($this->getSerializer()::readToManyRulesIndexes($ruleSet))
            ->disableIgnoreUnknowns();

        $this->errorAggregator   = new ErrorAggregator();
        $this->captureAggregator = new CaptureAggregator();
    }

    /**
     * @inheritdoc
     */
    public function assert(array $jsonData): JsonApiDataParserInterface
    {
        if ($this->parse($jsonData) === false) {
            throw new JsonApiException($this->getJsonApiErrorCollection(), $this->getErrorStatus());
        }

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function parse(array $input): bool
    {
        $this->resetAggregators();

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
    public function parseRelationship(string $index, string $name, array $jsonData): bool
    {
        $this->resetAggregators();

        $isFoundInToOne  = array_key_exists($name, $this->getSerializer()::readRulesIndexes($this->getToOneRules()));
        $isFoundInToMany = $isFoundInToOne === false &&
            array_key_exists($name, $this->getSerializer()::readRulesIndexes($this->getToManyRules()));

        if ($isFoundInToOne === false && $isFoundInToMany === false) {
            $title   = $this->formatMessage(Messages::INVALID_VALUE);
            $details = $this->formatMessage(Messages::UNKNOWN_RELATIONSHIP);
            $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, (string)$status);
            $this->addErrorStatus($status);
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
                $status  = JsonApiResponse::HTTP_CONFLICT;
                foreach ($this->getErrorAggregator()->get() as $error) {
                    $this->getJsonApiErrorCollection()->addValidationRelationshipError($error, $status);
                    $this->addErrorStatus($status);
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
    public function assertRelationship(
        string $index,
        string $name,
        array $jsonData
    ): JsonApiDataParserInterface {
        if ($this->parseRelationship($index, $name, $jsonData) === false) {
            $status = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            throw new JsonApiException($this->getJsonApiErrorCollection(), $status);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return $this->getJsonApiErrorCollection()->getArrayCopy();
    }

    /**
     * @inheritdoc
     */
    public function getCaptures(): array
    {
        return $this->getCaptureAggregator()->get();
    }

    /**
     * @return self
     */
    protected function resetAggregators(): self
    {
        $this->getCaptureAggregator()->clear();
        $this->getErrorAggregator()->clear();
        $this->getContext()->clear();

        return $this;
    }

    /**
     * @param string $serializerClass
     *
     * @return self
     */
    protected function setSerializerClass(string $serializerClass): self
    {
        assert(static::classImplements($serializerClass, JsonApiDataRulesSerializerInterface::class));

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
        $this->executeStarts($this->getSerializer()::readRuleStartIndexes($this->getTypeRule()));

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_TYPE, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            // execute main validation block(s)
            $index = $this->getSerializer()::readRuleIndex($this->getTypeRule());
            $this->executeBlock($data[DI::KEYWORD_TYPE], $index);
        } else {
            $title   = $this->formatMessage(Messages::INVALID_VALUE);
            $details = $this->formatMessage(Messages::TYPE_MISSING);
            $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            $this->getJsonApiErrorCollection()->addDataTypeError($title, $details, (string)$status);
            $this->addErrorStatus($status);
        }

        // execute end(s)
        $this->executeEnds($this->getSerializer()::readRuleEndIndexes($this->getTypeRule()));

        if (count($this->getErrorAggregator()) > 0) {
            $title  = $this->formatMessage(Messages::INVALID_VALUE);
            $status = JsonApiResponse::HTTP_CONFLICT;
            foreach ($this->getErrorAggregator()->get() as $error) {
                $details = $this->getMessage($error);
                $this->getJsonApiErrorCollection()->addDataTypeError($title, $details, (string)$status);
            }
            $this->addErrorStatus($status);
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
        $this->executeStarts($this->getSerializer()::readRuleStartIndexes($this->getIdRule()));

        // execute main validation block(s)
        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_ID, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            $index = $this->getSerializer()::readRuleIndex($this->getIdRule());
            $this->executeBlock($data[DI::KEYWORD_ID], $index);
        }

        // execute end(s)
        $this->executeEnds($this->getSerializer()::readRuleEndIndexes($this->getIdRule()));

        if (count($this->getErrorAggregator()) > 0) {
            $title  = $this->formatMessage(Messages::INVALID_VALUE);
            $status = JsonApiResponse::HTTP_CONFLICT;
            foreach ($this->getErrorAggregator()->get() as $error) {
                $details = $this->getMessage($error);
                $this->getJsonApiErrorCollection()->addDataIdError($title, $details, (string)$status);
            }
            $this->addErrorStatus($status);
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
        $this->executeStarts($this->getSerializer()::readRulesStartIndexes($this->getAttributeRules()));

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_ATTRIBUTES, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            if (is_array($attributes = $data[DI::KEYWORD_ATTRIBUTES]) === false) {
                $title   = $this->formatMessage(Messages::INVALID_VALUE);
                $details = $this->formatMessage(Messages::INVALID_ATTRIBUTES);
                $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
                $this->getJsonApiErrorCollection()->addAttributesError($title, $details, (string)$status);
                $this->addErrorStatus($status);
            } else {
                // execute main validation block(s)
                foreach ($attributes as $name => $value) {
                    if (($index = $this->getAttributeIndex($name)) !== null) {
                        if (array_key_exists(DI::KEYWORD_ID, $data = $jsonData[DI::KEYWORD_DATA]) === true) {
                            $this->executeBlock($value, $index, (int)$data[DI::KEYWORD_ID]);
                        } else {
                            $this->executeBlock($value, $index);
                        }
                    } elseif ($this->isIgnoreUnknowns() === false) {
                        $title   = $this->formatMessage(Messages::INVALID_VALUE);
                        $details = $this->formatMessage(Messages::UNKNOWN_ATTRIBUTE);
                        $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
                        $this->getJsonApiErrorCollection()
                            ->addDataAttributeError($name, $title, $details, (string)$status);
                        $this->addErrorStatus($status);
                    }
                }
            }
        }

        // execute end(s)
        $this->executeEnds($this->getSerializer()::readRulesEndIndexes($this->getAttributeRules()));

        if (count($this->getErrorAggregator()) > 0) {
            $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()->addValidationAttributeError($error, $status);
            }
            $this->addErrorStatus($status);
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
        $this->executeStarts(array_merge(
            $this->getSerializer()::readRulesStartIndexes($this->getToOneRules()),
            $this->getSerializer()::readRulesStartIndexes($this->getToManyRules())
        ));

        if (array_key_exists(DI::KEYWORD_DATA, $jsonData) === true &&
            array_key_exists(DI::KEYWORD_RELATIONSHIPS, $data = $jsonData[DI::KEYWORD_DATA]) === true
        ) {
            if (is_array($relationships = $data[DI::KEYWORD_RELATIONSHIPS]) === false) {
                $title   = $this->formatMessage(Messages::INVALID_VALUE);
                $details = $this->formatMessage(Messages::INVALID_RELATIONSHIP_TYPE);
                $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
                $this->getJsonApiErrorCollection()->addRelationshipsError($title, $details, (string)$status);
                $this->addErrorStatus($status);
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
                        $title   = $this->formatMessage(Messages::INVALID_VALUE);
                        $details = $this->formatMessage(Messages::UNKNOWN_RELATIONSHIP);
                        $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
                        $this->getJsonApiErrorCollection()
                            ->addRelationshipError($name, $title, $details, (string)$status);
                        $this->addErrorStatus($status);
                    }
                }
            }
        }

        // execute end(s)
        $this->executeEnds(array_merge(
            $this->getSerializer()::readRulesEndIndexes($this->getToOneRules()),
            $this->getSerializer()::readRulesEndIndexes($this->getToManyRules())
        ));

        if (count($this->getErrorAggregator()) > 0) {
            $status  = JsonApiResponse::HTTP_CONFLICT;
            foreach ($this->getErrorAggregator()->get() as $error) {
                $this->getJsonApiErrorCollection()->addValidationRelationshipError($error, $status);
            }
            $this->addErrorStatus($status);
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
            $title   = $this->formatMessage(Messages::INVALID_VALUE);
            $details = $this->formatMessage(Messages::INVALID_RELATIONSHIP);
            $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, (string)$status);
            $this->addErrorStatus($status);
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
            $title   = $this->formatMessage(Messages::INVALID_VALUE);
            $details = $this->formatMessage(Messages::INVALID_RELATIONSHIP);
            $status  = JsonApiResponse::HTTP_UNPROCESSABLE_ENTITY;
            $this->getJsonApiErrorCollection()->addRelationshipError($name, $title, $details, (string)$status);
            $this->addErrorStatus($status);
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
     * @param mixed    $input
     * @param int      $index
     * @param int|null $primaryKeyValue
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeBlock($input, int $index, ?int $primaryKeyValue = null): void
    {
        BlockInterpreter::executeBlock(
            $input,
            $index,
            $this->getBlocks(),
            $this->getContext(),
            $this->getCaptureAggregator(),
            $this->getErrorAggregator(),
            $primaryKeyValue
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
        if (empty($indexes) === false) {
            BlockInterpreter::executeStarts(
                $indexes,
                $this->getBlocks(),
                $this->getContext(),
                $this->getErrorAggregator()
            );
        }
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
        if (empty($indexes) === false) {
            BlockInterpreter::executeEnds(
                $indexes,
                $this->getBlocks(),
                $this->getContext(),
                $this->getErrorAggregator()
            );
        }
    }

    /**
     * @param ErrorInterface $error
     *
     * @return string
     */
    private function getMessage(ErrorInterface $error): string
    {
        $message = $this->formatMessage($error->getMessageTemplate(), $error->getMessageParameters());

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
     * @return int
     */
    private function getErrorStatus(): int
    {
        assert($this->errorStatus !== null, 'Check error code was set');

        return $this->errorStatus;
    }

    /**
     * @param int $status
     */
    private function addErrorStatus(int $status): void
    {
        // Currently (at the moment of writing) the spec is vague about how error status should be set.
        // On the one side it says, for example, 'A server MUST return 409 Conflict when processing a POST
        // request to create a resource with a client-generated ID that already exists.'
        // So you might think 'simple, that should be HTTP status, right?'
        // But on the other
        // - 'it [server] MAY continue processing and encounter multiple problems.'
        // - 'When a server encounters multiple problems for a single request, the most generally applicable
        //    HTTP error code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate
        //    for multiple 4xx errors'

        // So, as we might return multiple errors, we have to figure out what is the best status for response.

        // The strategy is the following: for the first error its error code becomes the Response's status.
        // If any following error code do not match the previous the status becomes generic 400.
        if ($this->errorStatus === null) {
            $this->errorStatus = $status;
        } elseif ($this->errorStatus !== JsonApiResponse::HTTP_BAD_REQUEST && $this->errorStatus !== $status) {
            $this->errorStatus = JsonApiResponse::HTTP_BAD_REQUEST;
        }
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
            $this->formatter = $this->formatterFactory->createFormatter(Messages::NAMESPACE_NAME);
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
     * @param string $defaultMessage
     * @param array  $args
     *
     * @return string
     */
    private function formatMessage(string $defaultMessage, array $args = []): string
    {
        $message = $this->getFormatter()->formatMessage($defaultMessage, $args);

        return $message;
    }

    /**
     * @return ErrorAggregatorInterface
     */
    private function getErrorAggregator(): ErrorAggregatorInterface
    {
        return $this->errorAggregator;
    }

    /**
     * @return CaptureAggregatorInterface
     */
    private function getCaptureAggregator(): CaptureAggregatorInterface
    {
        return $this->captureAggregator;
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

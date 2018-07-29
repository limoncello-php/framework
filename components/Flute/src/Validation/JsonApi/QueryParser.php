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

use Generator;
use Limoncello\Contracts\L10n\FormatterFactoryInterface;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Limoncello\Flute\Contracts\Validation\JsonApiQueryRulesSerializerInterface;
use Limoncello\Flute\Exceptions\InvalidQueryParametersException;
use Limoncello\Flute\Package\FluteSettings;
use Limoncello\Flute\Resources\Messages\En\Validation;
use Limoncello\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Execution\BlockInterpreter;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error as JsonApiError;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Http\Query\BaseQueryParserTrait;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class QueryParser implements JsonApiQueryParserInterface
{
    use BaseQueryParserTrait {
        BaseQueryParserTrait::getFields as getFieldsImpl;
        BaseQueryParserTrait::getIncludes as getIncludesImpl;
        BaseQueryParserTrait::getSorts as getSortsImpl;
    }

    /** Message */
    public const MSG_ERR_INVALID_PARAMETER = 'Invalid Parameter.';

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var string[]|null
     */
    private $messages;

    /**
     * @var null|string
     */
    private $identityParameter;

    /**
     * @var array
     */
    private $filterParameters;

    /**
     * @var bool
     */
    private $areFiltersWithAnd;

    /**
     * @var int|null
     */
    private $pagingOffset;

    /**
     * @var int|null
     */
    private $pagingLimit;

    /**
     * NOTE: Despite the type it is just a string so only static methods can be called from the interface.
     *
     * @var JsonApiQueryRulesSerializerInterface|string
     */
    private $serializerClass;

    /**
     * @var array
     */
    private $serializedRuleSet;

    /**
     * @var array
     */
    private $validationBlocks;

    /**
     * @var ContextStorageInterface
     */
    private $context;

    /**
     * @var CaptureAggregatorInterface
     */
    private $captures;

    /**
     * @var ErrorAggregatorInterface
     */
    private $validationErrors;

    /**
     * @var JsonApiErrorCollection
     */
    private $jsonErrors;

    /**
     * @var null|mixed
     */
    private $cachedIdentity = null;

    /**
     * @var null|array
     */
    private $cachedFilters = null;

    /**
     * @var null|array
     */
    private $cachedFields = null;

    /**
     * @var null|array
     */
    private $cachedSorts = null;

    /**
     * @var null|array
     */
    private $cachedIncludes = null;

    /**
     * @var FormatterFactoryInterface
     */
    private $formatterFactory;

    /**
     * @var FormatterInterface|null
     */
    private $formatter;

    /**
     * @param string                     $rulesClass
     * @param string                     $serializerClass
     * @param array                      $serializedData
     * @param ContextStorageInterface    $context
     * @param CaptureAggregatorInterface $captures
     * @param ErrorAggregatorInterface   $validationErrors
     * @param JsonApiErrorCollection     $jsonErrors
     * @param FormatterFactoryInterface  $formatterFactory
     * @param string[]|null              $messages
     */
    public function __construct(
        string $rulesClass,
        string $serializerClass,
        array $serializedData,
        ContextStorageInterface $context,
        CaptureAggregatorInterface $captures,
        ErrorAggregatorInterface $validationErrors,
        JsonApiErrorCollection $jsonErrors,
        FormatterFactoryInterface $formatterFactory,
        array $messages = null
    ) {
        assert(
            in_array(JsonApiQueryRulesSerializerInterface::class, class_implements($serializerClass)),
            "`$serializerClass` should implement interface `" . JsonApiQueryRulesSerializerInterface::class . '`.'
        );

        $parameters = [];
        $this->setParameters($parameters)->setMessages($messages);
        $this->serializerClass  = $serializerClass;
        $this->context          = $context;
        $this->captures         = $captures;
        $this->validationErrors = $validationErrors;
        $this->jsonErrors       = $jsonErrors;
        $this->formatterFactory = $formatterFactory;

        assert($this->serializerClass::hasRules($rulesClass, $serializedData));
        $this->serializedRuleSet = $this->serializerClass::readRules($rulesClass, $serializedData);
        $this->validationBlocks  = $this->serializerClass::readBlocks($serializedData);

        $this->clear();
    }

    /**
     * @inheritdoc
     */
    public function parse(?string $identity, array $parameters = []): JsonApiQueryParserInterface
    {
        $this->clear();

        $this->setIdentityParameter($identity)->setParameters($parameters);

        $this->parsePagingParameters()->parseFilterLink();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function areFiltersWithAnd(): bool
    {
        return $this->areFiltersWithAnd;
    }

    /**
     * @inheritdoc
     */
    public function hasFilters(): bool
    {
        return $this->hasParameter(static::PARAM_FILTER);
    }

    /**
     * @inheritdoc
     */
    public function hasFields(): bool
    {
        return $this->hasParameter(static::PARAM_FIELDS);
    }

    /**
     * @inheritdoc
     */
    public function hasIncludes(): bool
    {
        return $this->hasParameter(static::PARAM_INCLUDE);
    }

    /**
     * @inheritdoc
     */
    public function hasSorts(): bool
    {
        return $this->hasParameter(static::PARAM_SORT);
    }

    /**
     * @inheritdoc
     */
    public function hasPaging(): bool
    {
        return $this->hasParameter(static::PARAM_PAGE);
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->cachedIdentity === null) {
            $this->cachedIdentity = $this->getValidatedIdentity();
        }

        return $this->cachedIdentity;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        if ($this->cachedFilters === null) {
            $this->cachedFilters = $this->iterableToArray($this->getValidatedFilters());
        }

        return $this->cachedFilters;
    }

    /**
     * @inheritdoc
     */
    public function getFields(): array
    {
        if ($this->cachedFields === null) {
            $fields = $this->getFieldsImpl($this->getParameters(), $this->getInvalidParamMessage());
            $this->cachedFields = $this->iterableToArray($this->getValidatedFields($fields));
        }

        return $this->cachedFields;
    }

    /**
     * @inheritdoc
     */
    public function getSorts(): array
    {
        if ($this->cachedSorts === null) {
            $sorts = $this->getSortsImpl($this->getParameters(), $this->getInvalidParamMessage());
            $this->cachedSorts = $this->iterableToArray($this->getValidatedSorts($sorts));
        }

        return $this->cachedSorts;
    }

    /**
     * @inheritdoc
     */
    public function getIncludes(): iterable
    {
        if ($this->cachedIncludes === null) {
            $includes = $this->getIncludesImpl($this->getParameters(), $this->getInvalidParamMessage());
            $this->cachedIncludes = $this->iterableToArray($this->getValidatedIncludes($includes));
        }

        return $this->cachedIncludes;
    }

    /**
     * @inheritdoc
     */
    public function getPagingOffset(): ?int
    {
        return $this->pagingOffset;
    }

    /**
     * @inheritdoc
     */
    public function getPagingLimit(): ?int
    {
        return $this->pagingLimit;
    }

    /**
     * @param array $parameters
     *
     * @return self
     */
    protected function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return array
     */
    protected function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $messages
     *
     * @return self
     */
    protected function setMessages(?array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    protected function getMessage(string $message): string
    {
        $hasTranslation = $this->messages !== null && array_key_exists($message, $this->messages) === false;

        return $hasTranslation === true ? $this->messages[$message] : $message;
    }

    /**
     * @return JsonApiErrorCollection
     */
    protected function getJsonErrors(): JsonApiErrorCollection
    {
        return $this->jsonErrors;
    }

    /**
     * @return ErrorAggregatorInterface
     */
    protected function getValidationErrors(): ErrorAggregatorInterface
    {
        return $this->validationErrors;
    }

    /**
     * @return CaptureAggregatorInterface
     */
    protected function getCaptures(): CaptureAggregatorInterface
    {
        return $this->captures;
    }

    /**
     * @return ContextStorageInterface
     */
    protected function getContext(): ContextStorageInterface
    {
        return $this->context;
    }

    /**
     * @return array
     */
    protected function getValidationBlocks(): array
    {
        return $this->validationBlocks;
    }

    /**
     * @return array
     */
    protected function getSerializedRuleSet(): array
    {
        return $this->serializedRuleSet;
    }

    /**
     * @return FormatterFactoryInterface
     */
    protected function getFormatterFactory(): FormatterFactoryInterface
    {
        return $this->formatterFactory;
    }

    /**
     * @return FormatterInterface
     */
    protected function getFormatter(): FormatterInterface
    {
        if ($this->formatter === null) {
            $this->formatter = $this->getFormatterFactory()->createFormatter(FluteSettings::VALIDATION_NAMESPACE);
        }

        return $this->formatter;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->getParameters());
    }

    /**
     * @return mixed
     */
    private function getValidatedIdentity()
    {
        // without validation
        $result = $this->getIdentityParameter();

        $ruleIndexes = $this->serializerClass::readIdentityRuleIndexes($this->getSerializedRuleSet());
        if ($ruleIndexes !== null) {
            // with validation
            $ruleIndex = $this->serializerClass::readRuleMainIndex($ruleIndexes);


            $this->validationStarts(static::PARAM_IDENTITY, $ruleIndexes);
            $this->validateAndThrowOnError(static::PARAM_IDENTITY, $result, $ruleIndex);
            $this->validateEnds(static::PARAM_IDENTITY, $ruleIndexes);

            $result = $this->readSingleCapturedValue();
        }

        return $result;
    }

    /**
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getValidatedFilters(): iterable
    {
        $ruleIndexes = $this->serializerClass::readFilterRulesIndexes($this->getSerializedRuleSet());

        if ($ruleIndexes === null) {
            // without validation
            foreach ($this->getFilterParameters() as $field => $operationsWithArgs) {
                yield $field => $this->parseOperationsAndArguments(static::PARAM_FILTER, $operationsWithArgs);
            }
        } else {
            // with validation
            $mainIndexes = $this->serializerClass::readRuleMainIndexes($ruleIndexes);
            $this->validationStarts(static::PARAM_FILTER, $ruleIndexes);
            foreach ($this->getFilterParameters() as $field => $operationsWithArgs) {
                if (is_string($field) === false || empty($field) === true ||
                    is_array($operationsWithArgs) === false || empty($operationsWithArgs) === true
                ) {
                    throw new InvalidQueryParametersException($this->createParameterError(
                        static::PARAM_FILTER,
                        $this->getInvalidParamMessage()
                    ));
                }

                if (array_key_exists($field, $mainIndexes) === false) {
                    // unknown field set type
                    $this->getValidationErrors()->add(
                        new Error(static::PARAM_FILTER, $field, ErrorCodes::INVALID_VALUE, null)
                    );
                } else {
                    // for field a validation rule is defined so input value will be validated
                    $ruleIndex = $mainIndexes[$field];
                    $parsed    = $this->parseOperationsAndArguments(static::PARAM_FILTER, $operationsWithArgs);

                    yield $field => $this->validateFilterArguments($ruleIndex, $parsed);
                }
            }
            $this->validateEnds(static::PARAM_FILTER, $ruleIndexes);
        }
    }

    /**
     * @param iterable $fieldsFromParent
     *
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getValidatedFields(iterable $fieldsFromParent): iterable
    {
        $ruleIndexes = $this->serializerClass::readFieldSetRulesIndexes($this->getSerializedRuleSet());

        if ($ruleIndexes === null) {
            // without validation
            foreach ($fieldsFromParent as $type => $fieldList) {
                yield $type => $fieldList;
            }
        } else {
            // with validation
            $mainIndexes = $this->serializerClass::readRuleMainIndexes($ruleIndexes);
            $this->validationStarts(static::PARAM_FIELDS, $ruleIndexes);
            foreach ($fieldsFromParent as $type => $fieldList) {
                if (array_key_exists($type, $mainIndexes) === true) {
                    yield $type => $this->validateValues($mainIndexes[$type], $fieldList);
                } else {
                    // unknown field set type
                    $this->getValidationErrors()->add(
                        new Error(static::PARAM_FIELDS, $type, ErrorCodes::INVALID_VALUE, null)
                    );
                }
            }
            $this->validateEnds(static::PARAM_FIELDS, $ruleIndexes);
        }
    }

    /**
     * @param iterable $sortsFromParent
     *
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getValidatedSorts(iterable $sortsFromParent): iterable
    {
        $ruleIndexes = $this->serializerClass::readSortsRuleIndexes($this->getSerializedRuleSet());

        if ($ruleIndexes === null) {
            // without validation
            foreach ($sortsFromParent as $field => $isAsc) {
                yield $field => $isAsc;
            }
        } else {
            // with validation
            $ruleIndex = $this->serializerClass::readRuleMainIndex($ruleIndexes);
            $this->validationStarts(static::PARAM_SORT, $ruleIndexes);
            foreach ($sortsFromParent as $field => $isAsc) {
                $this->getCaptures()->clear();
                $this->validateAndAccumulateError($field, $ruleIndex);
                if ($this->getCaptures()->count() > 0) {
                    yield $this->readSingleCapturedValue() => $isAsc;
                }
            }
            $this->validateEnds(static::PARAM_SORT, $ruleIndexes);
        }
    }

    /**
     * @param iterable $includesFromParent
     *
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getValidatedIncludes(iterable $includesFromParent): iterable
    {
        $ruleIndexes = $this->serializerClass::readIncludesRuleIndexes($this->getSerializedRuleSet());

        if ($ruleIndexes === null) {
            // without validation
            foreach ($includesFromParent as $path => $split) {
                yield $path => $split;
            }
        } else {
            // with validation
            $ruleIndex = $this->serializerClass::readRuleMainIndex($ruleIndexes);
            $this->validationStarts(static::PARAM_INCLUDE, $ruleIndexes);
            foreach ($includesFromParent as $path => $split) {
                $this->getCaptures()->clear();
                $this->validateAndAccumulateError($path, $ruleIndex);
                if ($this->getCaptures()->count() > 0) {
                    yield $this->readSingleCapturedValue() => $split;
                }
            }
            $this->validateEnds(static::PARAM_INCLUDE, $ruleIndexes);
        }
    }

    /**
     * @param iterable $iterable
     *
     * @return array
     */
    private function iterableToArray(iterable $iterable): array
    {
        $result = [];

        foreach ($iterable as $key => $value) {
            $result[$key] = $value instanceof Generator ? $this->iterableToArray($value) : $value;
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    private function validatePageOffset($value): int
    {
        $ruleIndexes    = $this->serializerClass::readPageOffsetRuleIndexes($this->getSerializedRuleSet());
        $validatedValue = $this->validatePaginationValue($value, $ruleIndexes);

        return $validatedValue;
    }

    /**
     * @param mixed $value
     *
     * @return int
     */
    private function validatePageLimit($value): int
    {
        $ruleIndexes    = $this->serializerClass::readPageLimitRuleIndexes($this->getSerializedRuleSet());
        $validatedValue = $this->validatePaginationValue($value, $ruleIndexes);

        return $validatedValue;
    }

    /**
     * @param mixed $value
     * @param array $ruleIndexes
     *
     * @return int
     */
    private function validatePaginationValue($value, ?array $ruleIndexes): int
    {
        // no validation rule means we should accept any input value
        if ($ruleIndexes === null) {
            return is_numeric($value) === true ? (int)$value : 0;
        }

        $ruleIndex = $this->serializerClass::readRuleMainIndex($ruleIndexes);

        $this->validationStarts(static::PARAM_PAGE, $ruleIndexes);
        $this->validateAndThrowOnError(static::PARAM_PAGE, $value, $ruleIndex);
        $this->validateEnds(static::PARAM_PAGE, $ruleIndexes);

        $validatedValue = $this->readSingleCapturedValue();

        return (int)$validatedValue;
    }

    /**
     * @param string $paramName
     * @param array  $ruleIndexes
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validationStarts(string $paramName, array $ruleIndexes): void
    {
        $this->getCaptures()->clear();
        $this->getValidationErrors()->clear();

        BlockInterpreter::executeStarts(
            $this->serializerClass::readRuleStartIndexes($ruleIndexes),
            $this->getValidationBlocks(),
            $this->getContext(),
            $this->getValidationErrors()
        );
        $this->checkValidationQueueErrors($paramName);
    }

    /**
     * @param string $paramName
     * @param mixed  $value
     * @param int    $ruleIndex
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateAndThrowOnError(string $paramName, $value, int $ruleIndex): void
    {
        BlockInterpreter::executeBlock(
            $value,
            $ruleIndex,
            $this->getValidationBlocks(),
            $this->getContext(),
            $this->getCaptures(),
            $this->getValidationErrors()
        );
        $this->checkValidationQueueErrors($paramName);
    }

    /**
     * @param mixed $value
     * @param int   $ruleIndex
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateAndAccumulateError($value, int $ruleIndex): bool
    {
        return BlockInterpreter::executeBlock(
            $value,
            $ruleIndex,
            $this->getValidationBlocks(),
            $this->getContext(),
            $this->getCaptures(),
            $this->getValidationErrors()
        );
    }

    /**
     * @param string $paramName
     * @param array  $ruleIndexes
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateEnds(string $paramName, array $ruleIndexes): void
    {
        BlockInterpreter::executeEnds(
            $this->serializerClass::readRuleEndIndexes($ruleIndexes),
            $this->getValidationBlocks(),
            $this->getContext(),
            $this->getValidationErrors()
        );
        $this->checkValidationQueueErrors($paramName);
    }

    /**
     * @return mixed
     */
    private function readSingleCapturedValue()
    {
        assert(count($this->getCaptures()->get()) === 1, 'Expected that only one value would be captured.');
        $value = current($this->getCaptures()->get());

        return $value;
    }

    /**
     * @param int      $ruleIndex
     * @param iterable $values
     *
     * @return iterable
     */
    private function validateValues(int $ruleIndex, iterable $values): iterable
    {
        foreach ($values as $key => $value) {
            $this->getCaptures()->clear();
            $this->validateAndAccumulateError($value, $ruleIndex);
            if ($this->getCaptures()->count() > 0) {
                yield $key => $this->readSingleCapturedValue();
            }
        }
    }

    /**
     * @param int      $ruleIndex
     * @param iterable $opsAndArgs
     *
     * @return iterable
     */
    private function validateFilterArguments(int $ruleIndex, iterable $opsAndArgs): iterable
    {
        foreach ($opsAndArgs as $operation => $arguments) {
            yield $operation => $this->validateValues($ruleIndex, $arguments);
        }
    }

    /**
     * @return self
     */
    private function parsePagingParameters(): self
    {
        $parameters    = $this->getParameters();
        $mightBeOffset = $parameters[static::PARAM_PAGE][static::PARAM_PAGING_OFFSET] ?? null;
        $mightBeLimit  = $parameters[static::PARAM_PAGE][static::PARAM_PAGING_LIMIT] ?? null;

        $this->pagingOffset = $this->validatePageOffset($mightBeOffset);
        $this->pagingLimit  = $this->validatePageLimit($mightBeLimit);

        assert(is_int($this->pagingOffset) === true && $this->pagingOffset >= 0);
        assert(is_int($this->pagingLimit) === true && $this->pagingLimit > 0);

        return $this;
    }

    /**
     * @param string $paramName
     *
     * @return void
     *
     * @throws JsonApiException
     */
    private function checkValidationQueueErrors(string $paramName): void
    {
        if ($this->getValidationErrors()->count() > 0) {
            foreach ($this->getValidationErrors()->get() as $error) {
                $this->getJsonErrors()->addValidationQueryError($paramName, $error);
            }

            throw new JsonApiException($this->getJsonErrors());
        }
    }

    /**
     * @param string|null $value
     *
     * @return self
     */
    private function setIdentityParameter(?string $value): self
    {
        $this->identityParameter = $value;

        return $this;
    }

    /**
     * @return null|string
     */
    private function getIdentityParameter(): ?string
    {
        return $this->identityParameter;
    }

    /**
     * @param array $values
     *
     * @return self
     */
    private function setFilterParameters(array $values): self
    {
        $this->filterParameters = $values;

        return $this;
    }

    /**
     * @return array
     */
    private function getFilterParameters(): array
    {
        return $this->filterParameters;
    }

    /**
     * @return self
     */
    private function setFiltersWithAnd(): self
    {
        $this->areFiltersWithAnd = true;

        return $this;
    }

    /**
     * @return self
     */
    private function setFiltersWithOr(): self
    {
        $this->areFiltersWithAnd = false;

        return $this;
    }

    /**
     * @return self
     */
    private function clear(): self
    {
        $this->identityParameter = null;
        $this->filterParameters  = [];
        $this->areFiltersWithAnd = true;
        $this->pagingOffset      = null;
        $this->pagingLimit       = null;

        $this->cachedIdentity = null;
        $this->cachedFilters  = null;
        $this->cachedFields   = null;
        $this->cachedIncludes = null;
        $this->cachedSorts    = null;

        $this->getCaptures()->clear();
        $this->getValidationErrors()->clear();

        return $this;
    }

    /**
     * Pre-parsing for filter parameters.
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function parseFilterLink(): self
    {
        if (array_key_exists(static::PARAM_FILTER, $this->getParameters()) === false) {
            $this->setFiltersWithAnd()->setFilterParameters([]);

            return $this;
        }

        $filterSection = $this->getParameters()[static::PARAM_FILTER];
        if (is_array($filterSection) === false || empty($filterSection) === true) {
            throw new InvalidQueryParametersException($this->createParameterError(
                static::PARAM_FILTER,
                $this->getInvalidParamMessage()
            ));
        }

        $isWithAnd = true;
        reset($filterSection);

        // check if top level element is `AND` or `OR`
        $firstKey   = key($filterSection);
        $firstLcKey = strtolower(trim($firstKey));
        if (($hasOr = ($firstLcKey === 'or')) || $firstLcKey === 'and') {
            if (count($filterSection) > 1 ||
                empty($filterSection = $filterSection[$firstKey]) === true ||
                is_array($filterSection) === false
            ) {
                throw new InvalidQueryParametersException($this->createParameterError(
                    static::PARAM_FILTER,
                    $this->getInvalidParamMessage()
                ));
            } else {
                $this->setFilterParameters($filterSection);
                if ($hasOr === true) {
                    $isWithAnd = false;
                }
            }
        } else {
            $this->setFilterParameters($filterSection);
        }

        $isWithAnd === true ? $this->setFiltersWithAnd() : $this->setFiltersWithOr();

        return $this;
    }

    /**
     * @param string $parameterName
     * @param array  $value
     *
     * @return iterable
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function parseOperationsAndArguments(string $parameterName, array $value): iterable
    {
        // in this case we interpret it as an [operation => 'comma separated argument(s)']
        foreach ($value as $operationName => $arguments) {
            if (is_string($operationName) === false || empty($operationName) === true ||
                is_string($arguments) === false
            ) {
                $title = $this->getFormatter()->formatMessage(Validation::INVALID_OPERATION_ARGUMENTS);
                $error = $this->createQueryError($parameterName, $title);
                throw new InvalidQueryParametersException($error);
            }

            if ($arguments === '') {
                yield $operationName => [];
            } else {
                yield $operationName => $this->splitCommaSeparatedStringAndCheckNoEmpties(
                    $parameterName,
                    $arguments,
                    $this->getInvalidParamMessage()
                );
            }
        }
    }

    /**
     * @param string $paramName
     * @param string $errorTitle
     *
     * @return ErrorInterface
     */
    private function createQueryError(string $paramName, string $errorTitle): ErrorInterface
    {
        $source = [ErrorInterface::SOURCE_PARAMETER => $paramName];
        $error  = new JsonApiError(null, null, null, null, $errorTitle, null, $source);

        return $error;
    }

    /**
     *
     * @return string
     */
    private function getInvalidParamMessage(): string
    {
        return $this->getMessage(static::MSG_ERR_INVALID_PARAMETER);
    }
}

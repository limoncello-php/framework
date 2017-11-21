<?php namespace Limoncello\Flute\Http\Query;

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

use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Flute\Contracts\Adapters\PaginationStrategyInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryParserInterface;
use Limoncello\Flute\Contracts\Http\Query\QueryValidatorInterface;
use Limoncello\Flute\Contracts\Validation\ErrorCodes;
use Limoncello\Flute\Exceptions\InvalidQueryParametersException;
use Limoncello\Flute\Validation\Form\Execution\AttributeRulesSerializer;
use Limoncello\Validation\Captures\CaptureAggregator;
use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Errors\ErrorAggregator;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Execution\ContextStorage;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueryValidator extends QueryParser implements QueryValidatorInterface
{
    use HasContainerTrait;

    /**
     * @var ContextStorageInterface
     */
    private $contextStorage;

    /**
     * @var CaptureAggregatorInterface
     */
    private $captureAggregator;

    /**
     * @var ErrorAggregatorInterface
     */
    private $errorAggregator;

    /**
     * @var array
     */
    private $rulesData;

    /**
     * @var array
     */
    private $blocks;

    /**
     * @var int[]
     */
    private $attributeRules;

    /**
     * @var array
     */
    private $attributeRulesIdx;

    /**
     * @param array                       $data
     * @param ContainerInterface          $container
     * @param PaginationStrategyInterface $paginationStrategy
     * @param string[]|null               $messages
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        array $data,
        ContainerInterface $container,
        PaginationStrategyInterface $paginationStrategy,
        array $messages = null
    ) {
        $this
            ->setContainer($container)
            ->setRulesData($data)
            ->setBlocks(AttributeRulesSerializer::extractBlocks($this->getRulesData()))
            ->setContextStorage($this->createContextStorage())
            ->setCaptureAggregator($this->createCaptureAggregator())
            ->setErrorAggregator($this->createErrorAggregator());

        parent::__construct($paginationStrategy, $messages);
    }

    /**
     * @inheritdoc
     */
    public function withAllAllowedFilterFields(): QueryParserInterface
    {
        $self = parent::withAllAllowedFilterFields();

        $this->unsetAttributeRules();

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function withNoAllowedFilterFields(): QueryParserInterface
    {
        $self = parent::withNoAllowedFilterFields();

        $this->unsetAttributeRules();

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function withAllowedFilterFields(array $fields): QueryParserInterface
    {
        $self = parent::withAllowedFilterFields($fields);

        $this->unsetAttributeRules();

        return $self;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function withValidatedFilterFields(string $rulesSetClass): QueryValidatorInterface
    {
        $this->withAllAllowedFilterFields();

        return $this->setAttributeRules(
            AttributeRulesSerializer::getAttributeRules($rulesSetClass, $this->getRulesData())
        );
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function getFilters(): iterable
    {
        $filters = parent::getFilters();

        $serializedRules = $this->getAttributeRules();

        // if validation rules were actually set
        if ($serializedRules !== null) {
            $this->executeStarts(AttributeRulesSerializer::getRulesStartIndexes($serializedRules));

            foreach ($filters as $field => $operationsAndArgs) {
                if (($index = $this->getAttributeIndex($field)) !== null) {
                    yield $field => $this->getValidatedOperationsAndArguments($index, $field, $operationsAndArgs);
                } else {
                    // unknown field
                    $value   = null;
                    $context = null;
                    $this->getErrorAggregator()->add(new Error($field, $value, ErrorCodes::INVALID_VALUE, $context));
                }
            }

            $this->executeEnds(AttributeRulesSerializer::getRulesEndIndexes($this->getAttributeRules()));

            if ($this->getErrorAggregator()->count() > 0) {
                throw new InvalidQueryParametersException($this->createParameterError(static::PARAM_FILTER));
            }
        } else {
            foreach ($filters as $field => $operationsAndArgs) {
                yield $field => $operationsAndArgs;
            }
        }
    }

    /**
     * @return ContextStorageInterface
     */
    protected function createContextStorage(): ContextStorageInterface
    {
        return new ContextStorage($this->getBlocks(), $this->getContainer());
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
     * @param ContextStorageInterface $contextStorage
     *
     * @return self
     */
    private function setContextStorage(ContextStorageInterface $contextStorage): self
    {
        $this->contextStorage = $contextStorage;

        return $this;
    }

    /**
     * @return CaptureAggregatorInterface
     */
    private function getCaptureAggregator(): CaptureAggregatorInterface
    {
        return $this->captureAggregator;
    }

    /**
     * @param CaptureAggregatorInterface $captureAggregator
     *
     * @return self
     */
    private function setCaptureAggregator(CaptureAggregatorInterface $captureAggregator): self
    {
        $this->captureAggregator = $captureAggregator;

        return $this;
    }

    /**
     * @return ErrorAggregatorInterface
     */
    private function getErrorAggregator(): ErrorAggregatorInterface
    {
        return $this->errorAggregator;
    }

    /**
     * @param ErrorAggregatorInterface $errorAggregator
     *
     * @return self
     */
    private function setErrorAggregator(ErrorAggregatorInterface $errorAggregator): self
    {
        $this->errorAggregator = $errorAggregator;

        return $this;
    }

    /**
     * @param int      $blockIndex
     * @param string   $name
     * @param iterable $operationsAndArgs
     *
     * @return iterable
     */
    private function getValidatedOperationsAndArguments(
        int $blockIndex,
        string $name,
        iterable $operationsAndArgs
    ): iterable {
        foreach ($operationsAndArgs as $operation => $args) {
            yield $operation => $this->getValidatedArguments($blockIndex, $name, $args);
        }
    }

    /**
     * @param int      $blockIndex
     * @param string   $name
     * @param iterable $arguments
     *
     * @return iterable
     */
    private function getValidatedArguments(int $blockIndex, string $name, iterable $arguments): iterable
    {
        foreach ($arguments as $argument) {
            if ($this->executeBlock($argument, $blockIndex) === true) {
                $validated = $this->getCaptureAggregator()->get()[$name];
                yield $validated;
            }
        }
    }

    /**
     * @return ContextStorageInterface
     */
    private function getContextStorage(): ContextStorageInterface
    {
        return $this->contextStorage;
    }

    /**
     * @return array
     */
    private function getRulesData(): array
    {
        return $this->rulesData;
    }

    /**
     * @param array $rulesData
     *
     * @return self
     */
    private function setRulesData(array $rulesData): self
    {
        $this->rulesData = $rulesData;

        return $this;
    }

    /**
     * @return array
     */
    private function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @param array $blocks
     *
     * @return self
     */
    private function setBlocks(array $blocks): self
    {
        $this->blocks = $blocks;

        return $this;
    }

    /**
     * @param array $rules
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function setAttributeRules(array $rules): self
    {
        assert($this->debugCheckIndexesExist($rules));

        $this->attributeRules    = $rules;
        $this->attributeRulesIdx = AttributeRulesSerializer::getRulesIndexes($rules);

        return $this;
    }

    /**
     * @return self
     */
    private function unsetAttributeRules(): self
    {
        $this->attributeRules    = null;
        $this->attributeRulesIdx = null;

        return $this;
    }

    /**
     * @return int[]|null
     */
    private function getAttributeRules(): ?array
    {
        return $this->attributeRules;
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
        $index = $this->attributeRulesIdx[$name] ?? null;

        return $index;
    }

    /**
     * @param array $indexes
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeStarts(array $indexes): bool
    {
        return BlockInterpreter::executeStarts(
            $indexes,
            $this->getBlocks(),
            $this->getContextStorage(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param array $indexes
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeEnds(array $indexes): bool
    {
        return BlockInterpreter::executeEnds(
            $indexes,
            $this->getBlocks(),
            $this->getContextStorage(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param mixed $input
     * @param int   $index
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function executeBlock($input, int $index): bool
    {
        return BlockInterpreter::executeBlock(
            $input,
            $index,
            $this->getBlocks(),
            $this->getContextStorage(),
            $this->getCaptureAggregator(),
            $this->getErrorAggregator()
        );
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
            AttributeRulesSerializer::getRulesIndexes($rules),
            AttributeRulesSerializer::getRulesStartIndexes($rules),
            AttributeRulesSerializer::getRulesEndIndexes($rules)
        );

        foreach ($indexes as $index) {
            $allOk = $allOk && is_int($index) && AttributeRulesSerializer::isRuleExist($index, $this->getBlocks());
        }

        return $allOk;
    }
}

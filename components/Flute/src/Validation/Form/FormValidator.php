<?php declare (strict_types = 1);

namespace Limoncello\Flute\Validation\Form;

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

use Generator;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\FormRulesSerializerInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use Limoncello\Flute\L10n\Messages;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Validator\BaseValidator;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class FormValidator extends BaseValidator implements FormValidatorInterface
{
    /**
     * It is string though it can be used to access static methods of the interface.
     *
     * @var FormRulesSerializerInterface|string
     */
    private $serializerClass;

    /**
     * @var ContextStorageInterface
     */
    private $contextStorage;

    /**
     * @var FormatterInterface
     */
    private $messageFormatter;

    /**
     * @var array
     */
    private $blocks;

    /**
     * @var int[]
     */
    private $ruleIndexes;

    /**
     * @var array
     */
    private $ruleMainIndexes;

    /**
     * @param string                  $rulesClass
     * @param string                  $serializerClass
     * @param array                   $serializedData
     * @param ContextStorageInterface $context
     * @param FormatterInterface      $messageFormatter
     */
    public function __construct(
        string $rulesClass,
        string $serializerClass,
        array $serializedData,
        ContextStorageInterface $context,
        FormatterInterface $messageFormatter
    ) {
        $this
            ->setSerializer($serializerClass)
            ->setContext($context)
            ->setMessageFormatter($messageFormatter);

        $this
            ->setBlocks($this->getSerializer()::readBlocks($serializedData))
            ->setRuleIndexes($this->getSerializer()::readRules($rulesClass, $serializedData));

        parent::__construct();
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function validate($input): bool
    {
        if (is_array($input) === false && ($input instanceof Generator) === false) {
            throw new InvalidArgumentException();
        }

        if ($this->areAggregatorsDirty() === true) {
            $this->resetAggregators();
        }

        $this->validateAttributes($input)->markAggregatorsAsDirty();

        $hasNoErrors = $this->getErrorAggregator()->count() <= 0;

        return $hasNoErrors;
    }

    /**
     * @inheritdoc
     */
    public function getMessages(): iterable
    {
        $formatter = $this->getMessageFormatter();
        foreach ($this->getErrors() as $error) {
            /** @var ErrorInterface $error */
            $message = $formatter->formatMessage($error->getMessageTemplate(), $error->getMessageParameters());

            yield $error->getParameterName() => $message;
        }
    }

    /**
     * @return BaseValidator
     */
    protected function resetAggregators(): BaseValidator
    {
        parent::resetAggregators();

        $this->contextStorage->clear();

        return $this;
    }

    /**
     * @return FormRulesSerializerInterface|string
     */
    protected function getSerializer()
    {
        return $this->serializerClass;
    }

    /**
     * @param string $serializerClass
     *
     * @return self
     */
    protected function setSerializer(string $serializerClass): self
    {
        assert(in_array(FormRulesSerializerInterface::class, class_implements($serializerClass)));

        $this->serializerClass = $serializerClass;

        return $this;
    }

    /**
     * @return ContextStorageInterface
     */
    protected function getContext(): ContextStorageInterface
    {
        return $this->contextStorage;
    }

    /**
     * @param ContextStorageInterface $context
     *
     * @return self
     */
    protected function setContext(ContextStorageInterface $context): self
    {
        $this->contextStorage = $context;

        return $this;
    }

    /**
     * @return FormatterInterface
     */
    protected function getMessageFormatter(): FormatterInterface
    {
        return $this->messageFormatter;
    }

    /**
     * @param FormatterInterface $messageFormatter
     *
     * @return self
     */
    private function setMessageFormatter(FormatterInterface $messageFormatter): self
    {
        $this->messageFormatter = $messageFormatter;

        return $this;
    }

    /**
     * @param iterable $attributes
     *
     * @return self
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function validateAttributes(iterable $attributes): self
    {
        // execute start(s)
        $this->executeStarts($this->getSerializer()::readRuleStartIndexes($this->getRuleIndexes()));

        foreach ($attributes as $name => $value) {
            if (($index = $this->getAttributeIndex($name)) !== null) {
                $this->executeBlock($value, $index);
            } else {
                $this->getErrorAggregator()->add(
                    new Error($name, $value, ErrorCodes::INVALID_VALUE, Messages::INVALID_VALUE, [])
                );
            }
        }

        // execute end(s)
        $this->executeEnds($this->getSerializer()::readRuleEndIndexes($this->getRuleIndexes()));

        return $this;
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
     * @param array $ruleIndexes
     *
     * @return self
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function setRuleIndexes(array $ruleIndexes): self
    {
        assert($this->debugCheckIndexesExist($ruleIndexes));

        $this->ruleIndexes     = $ruleIndexes;
        $this->ruleMainIndexes = $this->getSerializer()::readRuleMainIndexes($ruleIndexes);

        return $this;
    }

    /**
     * @return int[]
     */
    private function getRuleIndexes(): array
    {
        return $this->ruleIndexes;
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
     * @param string $name
     *
     * @return int|null
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function getAttributeIndex(string $name): ?int
    {
        $index = $this->ruleMainIndexes[$name] ?? null;

        return $index;
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
            $this->getSerializer()::readRuleMainIndexes($rules),
            $this->getSerializer()::readRuleStartIndexes($rules),
            $this->getSerializer()::readRuleEndIndexes($rules)
        );

        foreach ($indexes as $index) {
            $allOk = $allOk && is_int($index) && array_key_exists($index, $this->getBlocks());
        }

        return $allOk;
    }
}

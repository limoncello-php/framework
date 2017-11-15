<?php namespace Limoncello\Flute\Validation\Form;

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
use Limoncello\Container\Traits\HasContainerTrait;
use Limoncello\Contracts\L10n\FormatterInterface;
use Limoncello\Flute\Contracts\Validation\FormValidatorInterface;
use Limoncello\Flute\Exceptions\InvalidArgumentException;
use Limoncello\Flute\Validation\Form\Execution\FormRuleSerializer;
use Limoncello\Validation\Contracts\Errors\ErrorCodes;
use Limoncello\Validation\Contracts\Errors\ErrorInterface;
use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Errors\Error;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Validator\BaseValidator;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Validator extends BaseValidator implements FormValidatorInterface
{
    use HasContainerTrait;

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
    private $attributeRules;

    /**
     * @var array
     */
    private $attributeRulesIdx;

    /**
     * @param string             $name
     * @param array              $data
     * @param ContainerInterface $container
     * @param FormatterInterface $messageFormatter
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        string $name,
        array $data,
        ContainerInterface $container,
        FormatterInterface $messageFormatter
    ) {
        $this
            ->setContainer($container)
            ->setMessageFormatter($messageFormatter)
            ->setBlocks(FormRuleSerializer::extractBlocks($data))
            ->setAttributeRules(FormRuleSerializer::getAttributeRules($name, $data));

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
            $message = $formatter->formatMessage($error->getMessageCode(), $error->getMessageContext() ?? []);

            yield $error->getParameterName() => $message;
        }
    }

    /**
     * @return BaseValidator
     */
    protected function resetAggregators(): BaseValidator
    {
        $self = parent::resetAggregators();

        $this->contextStorage = $this->createContextStorage();

        return $self;
    }

    /**
     * @return ContextStorageInterface
     */
    protected function getContextStorage(): ContextStorageInterface
    {
        return $this->contextStorage;
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
     * @return ContextStorageInterface
     */
    protected function createContextStorage(): ContextStorageInterface
    {
        return new ContextStorage($this->getBlocks(), $this->getContainer());
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
        $this->executeStarts(FormRuleSerializer::getRulesStartIndexes($this->getAttributeRules()));

        foreach ($attributes as $name => $value) {
            if (($index = $this->getAttributeIndex($name)) !== null) {
                $this->executeBlock($value, $index);
            } else {
                $this->getErrorAggregator()->add(new Error($name, $value, ErrorCodes::INVALID_VALUE, null));
            }
        }

        // execute end(s)
        $this->executeEnds(FormRuleSerializer::getRulesEndIndexes($this->getAttributeRules()));

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
            $this->getContextStorage(),
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
            $this->getContextStorage(),
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
            $this->getContextStorage(),
            $this->getErrorAggregator()
        );
    }

    /**
     * @param array $rules
     *
     * @return self
     */
    private function setAttributeRules(array $rules): self
    {
        assert($this->debugCheckIndexesExist($rules));

        $this->attributeRules    = $rules;
        $this->attributeRulesIdx = FormRuleSerializer::getRulesIndexes($rules);

        return $this;
    }

    /**
     * @return int[]
     */
    private function getAttributeRules(): array
    {
        return $this->attributeRules;
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
        $index = $this->attributeRulesIdx[$name] ?? null;

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
            FormRuleSerializer::getRulesIndexes($rules),
            FormRuleSerializer::getRulesStartIndexes($rules),
            FormRuleSerializer::getRulesEndIndexes($rules)
        );

        foreach ($indexes as $index) {
            $allOk = $allOk && is_int($index) && FormRuleSerializer::isRuleExist($index, $this->getBlocks());
        }

        return $allOk;
    }
}

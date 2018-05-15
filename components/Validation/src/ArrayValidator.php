<?php namespace Limoncello\Validation;

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

use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Validator\ArrayValidation;
use Limoncello\Validation\Validator\BaseValidator;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Validation
 */
class ArrayValidator extends BaseValidator
{
    use ArrayValidation;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @param RuleInterface[]         $rules
     * @param ContainerInterface|null $container
     */
    public function __construct(array $rules, ContainerInterface $container = null)
    {
        parent::__construct();

        if (empty($rules) === false) {
            $this->setRules($rules);
        }

        $this->container = $container;
    }

    /**
     * @param RuleInterface[]    $rules
     * @param ContainerInterface $container
     *
     * @return self
     */
    public static function validator(array $rules = [], ContainerInterface $container = null): self
    {
        $validator = new static($rules, $container);

        return $validator;
    }

    /**
     * @inheritdoc
     */
    public function validate($input): bool
    {
        if ($this->areAggregatorsDirty() === true) {
            $this->resetAggregators();
        }

        $this->validateArrayImplementation($input, $this->getCaptureAggregator(), $this->getErrorAggregator());
        $this->markAggregatorsAsDirty();

        $isOk = $this->getErrorAggregator()->count() <= 0;

        return $isOk;
    }

    /**
     * @return ContainerInterface|null
     */
    protected function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * During validation you can pass to rules your custom context which might have any additional
     * resources needed by your rules (extra properties, database connection settings, container, and etc).
     *
     * @param array $blocks
     *
     * @return ContextStorageInterface
     */
    protected function createContextStorageFromBlocks(array $blocks): ContextStorageInterface
    {
        return new ContextStorage($blocks, $this->getContainer());
    }
}

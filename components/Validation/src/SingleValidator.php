<?php namespace Limoncello\Validation;

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

use Limoncello\Validation\Contracts\Execution\ContextStorageInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Contracts\ValidatorInterface;
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Validator\BaseValidator;
use Limoncello\Validation\Validator\SingleValidation;

/**
 * @package Limoncello\Validation
 */
class SingleValidator extends BaseValidator
{
    use SingleValidation;

    /**
     * @param RuleInterface $rule
     */
    public function __construct(RuleInterface $rule)
    {
        parent::__construct();

        $this->setRule($rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return ValidatorInterface
     */
    public static function validator(RuleInterface $rule): ValidatorInterface
    {
        $validator = new static ($rule);

        return $validator;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function validate($input): bool
    {
        if ($this->areAggregatorsDirty() === true) {
            $this->resetAggregators();
        }

        $this->validateSingleImplementation($input, $this->getCaptureAggregator(), $this->getErrorAggregator());
        $this->markAggregatorsAsDirty();

        $noErrors = $this->getErrorAggregator()->count() <= 0;

        return $noErrors;
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
        return new ContextStorage($blocks);
    }
}

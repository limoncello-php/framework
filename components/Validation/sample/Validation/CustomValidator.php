<?php namespace Sample\Validation;

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
use Limoncello\Validation\Execution\ContextStorage;
use Limoncello\Validation\Validator\ArrayValidation;
use Limoncello\Validation\Validator\BaseValidator;

/**
 * @package Sample
 */
class CustomValidator extends BaseValidator
{
    use ArrayValidation;

    /**
     * @param RuleInterface[] $rules
     */
    public function __construct(array $rules)
    {
        parent::__construct();

        $this->setRules($rules);
    }

    /**
     * @param RuleInterface[] $rules
     *
     * @return self
     */
    public static function validator(array $rules): self
    {
        $validator = new static ($rules);

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

        $this->validateArrayImplementation($input, $this->getCaptures(), $this->getErrors());
        $this->markAggregatorsAsDirty();

        $isOk = $this->getErrors()->count() <= 0;

        return $isOk;
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
        $context = new ContextStorage($blocks);

        return $context;
    }
}

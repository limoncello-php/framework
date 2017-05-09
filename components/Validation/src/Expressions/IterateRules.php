<?php namespace Limoncello\Validation\Expressions;

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

use Limoncello\Validation\Contracts\AutoNameRuleInterface;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Rules;

/**
 * @package Limoncello\Validation
 */
abstract class IterateRules extends BaseExpression implements AutoNameRuleInterface
{
    /**
     * @var RuleInterface[]
     */
    private $rules;

    /**
     * @var null|bool
     */
    private $isStateless = null;

    /**
     * @var bool
     */
    private $isAutoParamNames = true;

    /**
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @inheritdoc
     */
    public function enableAutoParameterNames(): AutoNameRuleInterface
    {
        $this->isAutoParamNames = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function disableAutoParameterNames(): AutoNameRuleInterface
    {
        $this->isAutoParamNames = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        if ($this->isStateless !== null) {
            return $this->isStateless;
        }

        foreach ($this->getRules() as $rule) {
            if ($rule->isStateless() === false) {
                return $this->isStateless = false;
            }
        }

        return $this->isStateless = true;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        foreach ($this->getRules() as $key => $rule) {
            $this->setParameterName($key);
            $rule->setParentRule($this);
            $rule->onFinish($aggregator);
        }
    }

    /**
     * @inheritdoc
     */
    public function setParameterName(string $parameterName = null): RuleInterface
    {
        if ($this->isAutoNames() === true) {
            parent::setParameterName($parameterName);
        }

        return $this;
    }

    /**
     * @return RuleInterface[]
     */
    protected function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return bool
     */
    protected function isAutoNames(): bool
    {
        return $this->isAutoParamNames;
    }
}

<?php namespace Limoncello\Validation\Expressions;

/**
 * Copyright 2015-2016 info@neomerx.com (www.neomerx.com)
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

use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;

/**
 * @package Limoncello\Validation
 */
class IfExpression extends BaseExpression
{
    /**
     * @var callable
     */
    private $condition;

    /**
     * @var RuleInterface
     */
    private $onTrue;

    /**
     * @var RuleInterface
     */
    private $onFalse;

    /**
     * @var RuleInterface|null
     */
    private $selectedRule = null;

    /**
     * @param callable      $condition
     * @param RuleInterface $onTrue
     * @param RuleInterface $onFalse
     */
    public function __construct(
        callable $condition,
        RuleInterface $onTrue,
        RuleInterface $onFalse
    ) {
        $this->condition  = $condition;
        $this->onTrue     = $onTrue;
        $this->onFalse    = $onFalse;

        $onTrue->setParentRule($this);
        $onFalse->setParentRule($this);
    }

    /**
     * @inheritdoc
     */
    public function validate($input)
    {
        $conditionResult    = call_user_func($this->condition, $input);
        $this->selectedRule = $conditionResult === true ? $this->onTrue : $this->onFalse;

        foreach ($this->selectedRule->validate($input) as $error) {
            yield $error;
        }
    }

    /**
     * @inheritdoc
     */
    public function isStateless()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        if ($this->selectedRule !== null) {
            $this->selectedRule->onFinish($aggregator);
        }
    }
}

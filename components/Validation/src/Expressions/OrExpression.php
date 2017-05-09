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

use Generator;
use Limoncello\Validation\Contracts\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\RuleInterface;
use Limoncello\Validation\Errors\ErrorAggregator;

/**
 * @package Limoncello\Validation
 */
class OrExpression extends BaseExpression
{
    /**
     * @var RuleInterface
     */
    private $secondary;

    /**
     * @var RuleInterface
     */
    private $primary;

    /**
     * The reason why we call it 'primary' is that we issue errors only from this rule (if both rules are failed).
     *
     * @param RuleInterface $primary
     * @param RuleInterface $secondary
     */
    public function __construct(RuleInterface $primary, RuleInterface $secondary)
    {
        $this->primary   = $primary;
        $this->secondary = $secondary;

        $secondary->setParentRule($this);
        $primary->setParentRule($this);
    }

    /**
     * @inheritdoc
     */
    public function validate($input): Generator
    {
        $secondaryHasErrors = false;
        foreach ($this->secondary->validate($input) as $error) {
            $error ?: null; // suppress unused
            $secondaryHasErrors = true;
            break;
        }

        // if secondary condition failed then rule will become 'failed' only if primary condition fails too.

        if ($secondaryHasErrors === true) {
            foreach ($this->primary->validate($input) as $error) {
                yield $error;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isStateless(): bool
    {
        return $this->secondary->isStateless() === true && $this->primary->isStateless() === true;
    }

    /**
     * @inheritdoc
     */
    public function onFinish(ErrorAggregatorInterface $aggregator)
    {
        // same logic here. We can think of the rule as failed only if both rules fail.
        // If both rules fail we issue errors only from 'primary' rule.

        $emptyAggregator = new ErrorAggregator();
        $this->secondary->onFinish($emptyAggregator);

        if (empty($emptyAggregator->get()) === false) {
            $this->primary->onFinish($aggregator);
        }
    }
}

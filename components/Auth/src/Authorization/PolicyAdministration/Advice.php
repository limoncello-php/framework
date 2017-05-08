<?php namespace Limoncello\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\AdviceInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;

/**
 * @package Limoncello\Auth
 */
class Advice extends Method implements AdviceInterface
{
    /**
     * @var int
     */
    private $appliesTo;

    /**
     * @param int      $appliesTo
     * @param callable $callable
     */
    public function __construct(int $appliesTo, callable $callable)
    {
        // Reminder on obligations and advice (from 7.18)
        //---------------------------------------------------------------------
        // no obligations or advice SHALL be returned to the PEP if the rule,
        // policies, or policy sets from which they are drawn are not evaluated,
        // or if their evaluated result is "Indeterminate" or "NotApplicable",
        // or if the decision resulting from evaluating the rule, policy,
        // or policy set does not match the decision resulting from evaluating
        // an enclosing policy set.
        assert($appliesTo === EvaluationEnum::PERMIT || $appliesTo === EvaluationEnum::DENY);

        $this->appliesTo = $appliesTo;
        parent::__construct($callable);
    }

    /**
     * @inheritdoc
     */
    public function getAppliesTo(): int
    {
        return $this->appliesTo;
    }
}

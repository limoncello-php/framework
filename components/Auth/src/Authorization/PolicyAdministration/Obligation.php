<?php namespace Limoncello\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\ObligationInterface;

/**
 * @package Limoncello\Auth
 */
class Obligation extends Method implements ObligationInterface
{
    /**
     * @var int
     */
    private $fulfillOn;

    /**
     * @param int      $fulfillOn
     * @param callable $callable
     */
    public function __construct($fulfillOn, callable $callable)
    {
        // Reminder on obligations and advice (from 7.18)
        //---------------------------------------------------------------------
        // no obligations or advice SHALL be returned to the PEP if the rule,
        // policies, or policy sets from which they are drawn are not evaluated,
        // or if their evaluated result is "Indeterminate" or "NotApplicable",
        // or if the decision resulting from evaluating the rule, policy,
        // or policy set does not match the decision resulting from evaluating
        // an enclosing policy set.
        assert($fulfillOn === EvaluationEnum::PERMIT || $fulfillOn === EvaluationEnum::DENY);

        $this->fulfillOn = $fulfillOn;
        parent::__construct($callable);
    }

    /**
     * @inheritdoc
     */
    public function getFulfillOn()
    {
        return $this->fulfillOn;
    }
}

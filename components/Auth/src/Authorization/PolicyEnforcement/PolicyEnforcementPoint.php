<?php namespace Limoncello\Auth\Authorization\PolicyEnforcement;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyDecision\PolicyDecisionPointInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\PolicyEnforcementPointInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyEnforcement\RequestInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\PolicyInformationPointInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @package Limoncello\Auth
 */
class PolicyEnforcementPoint implements PolicyEnforcementPointInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PolicyInformationPointInterface
     */
    private $pip;

    /**
     * @var PolicyDecisionPointInterface
     */
    private $pdp;

    /**
     * @var bool
     */
    private $isExecuteAdvice = true;

    /**
     * @param PolicyInformationPointInterface $pip
     * @param PolicyDecisionPointInterface    $pdp
     */
    public function __construct(PolicyInformationPointInterface $pip, PolicyDecisionPointInterface $pdp)
    {
        $this->pip = $pip;
        $this->pdp = $pdp;
    }

    /**
     * @inheritdoc
     */
    public function authorize(RequestInterface $request): bool
    {
        $context = $this->getPip()->createContext($request);

        /** @var int $evaluation */
        /** @var callable[] $obligations */
        /** @var callable[] $advice */
        list ($evaluation, $obligations, $advice) = $this->getPdp()->evaluate($context);

        $isAuthorized = $this->interpretEvaluation($evaluation);

        foreach ($obligations as $obligation) {
            call_user_func($obligation, $context);
        }

        if ($this->isExecuteAdvice() === true) {
            foreach ($advice as $callable) {
                call_user_func($callable, $context);
            }
        }

        return $isAuthorized;
    }

    /**
     * @return bool
     */
    public function isExecuteAdvice(): bool
    {
        return $this->isExecuteAdvice;
    }

    /**
     * @return self
     */
    public function enableExecuteAdvice(): self
    {
        $this->isExecuteAdvice = true;

        return $this;
    }

    /**
     * @return self
     */
    public function disableExecuteAdvice(): self
    {
        $this->isExecuteAdvice = false;

        return $this;
    }

    /**
     * @return PolicyInformationPointInterface
     */
    protected function getPip(): PolicyInformationPointInterface
    {
        return $this->pip;
    }

    /**
     * @return PolicyDecisionPointInterface
     */
    protected function getPdp(): PolicyDecisionPointInterface
    {
        return $this->pdp;
    }

    /**
     * @param int $evaluation
     *
     * @return bool
     */
    protected function interpretEvaluation(int $evaluation): bool
    {
        return $evaluation === EvaluationEnum::PERMIT;
    }
}

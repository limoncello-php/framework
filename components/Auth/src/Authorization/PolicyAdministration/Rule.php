<?php namespace Limoncello\Auth\Authorization\PolicyAdministration;

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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\AdviceInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\MethodInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\ObligationInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;

/**
 * @package Limoncello\Auth
 */
class Rule implements RuleInterface
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var TargetInterface|null
     */
    private $target;

    /**
     * @var MethodInterface|null
     */
    private $condition;

    /**
     * @var MethodInterface|null
     */
    private $effect;

    /**
     * @var ObligationInterface[]
     */
    private $obligations;

    /**
     * @var AdviceInterface[]
     */
    private $advice;

    /**
     * @param null|string           $name
     * @param TargetInterface|null  $target
     * @param MethodInterface|null  $condition
     * @param MethodInterface|null  $effect
     * @param ObligationInterface[] $obligations
     * @param AdviceInterface[]     $advice
     */
    public function __construct(
        string $name = null,
        TargetInterface $target = null,
        MethodInterface $condition = null,
        MethodInterface $effect = null,
        array $obligations = [],
        array $advice = []
    ) {
        $this->setName($name)->setTarget($target)->setCondition($condition)->setEffect($effect)
            ->setObligations($obligations)->setAdvice($advice);
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param null|string $name
     *
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTarget(): ?TargetInterface
    {
        return $this->target;
    }

    /**
     * @param TargetInterface|null $target
     *
     * @return self
     */
    public function setTarget(?TargetInterface $target): self
    {
        $this->target = $target;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCondition(): ?MethodInterface
    {
        return $this->condition;
    }

    /**
     * @param MethodInterface|null $condition
     *
     * @return self
     */
    public function setCondition(?MethodInterface $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function effect(): ?MethodInterface
    {
        return $this->effect;
    }

    /**
     * @param MethodInterface $effect
     *
     * @return self
     */
    public function setEffect(?MethodInterface $effect): self
    {
        $this->effect = $effect;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getObligations(): array
    {
        return $this->obligations;
    }

    /**
     * @param ObligationInterface[] $obligations
     *
     * @return self
     */
    public function setObligations(array $obligations): self
    {
        // check every item is Obligation (debug mode only)
        assert(call_user_func(
            function () use ($obligations) {
                foreach ($obligations as $item) {
                    assert($item instanceof ObligationInterface);
                }
                return true;
            }
        ) === true);

        $this->obligations = $obligations;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAdvice(): array
    {
        return $this->advice;
    }

    /**
     * @param AdviceInterface[] $advice
     *
     * @return self
     */
    public function setAdvice(array $advice): self
    {
        // check every item is Obligation (debug mode only)
        assert(call_user_func(
            function () use ($advice) {
                foreach ($advice as $item) {
                    assert($item instanceof AdviceInterface);
                }
                return true;
            }
        ) === true);

        $this->advice = $advice;

        return $this;
    }
}

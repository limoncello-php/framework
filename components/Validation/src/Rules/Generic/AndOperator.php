<?php declare(strict_types=1);

namespace Limoncello\Validation\Rules\Generic;

/**
 * Copyright 2015-2020 info@neomerx.com
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

use Limoncello\Validation\Blocks\AndBlock;
use Limoncello\Validation\Contracts\Blocks\ExecutionBlockInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Rules\BaseRule;

/**
 * @package Limoncello\Validation
 */
final class AndOperator extends BaseRule
{
    /**
     * @var RuleInterface
     */
    private $primary;

    /**
     * @var RuleInterface
     */
    private $secondary;

    /**
     * @param RuleInterface $primary
     * @param RuleInterface $secondary
     */
    public function __construct(RuleInterface $primary, RuleInterface $secondary)
    {
        $this->primary   = $primary;
        $this->secondary = $secondary;
    }

    /**
     * @inheritdoc
     */
    public function toBlock(): ExecutionBlockInterface
    {
        return new AndBlock(
            $this->getPrimary()->setParent($this)->toBlock(),
            $this->getSecondary()->setParent($this)->toBlock(),
            $this->getStandardProperties()
        );
    }

    /**
     * @return RuleInterface
     */
    public function getPrimary(): RuleInterface
    {
        return $this->primary;
    }

    /**
     * @return RuleInterface
     */
    public function getSecondary(): RuleInterface
    {
        return $this->secondary;
    }
}

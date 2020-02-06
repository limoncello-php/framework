<?php declare(strict_types=1);

namespace Limoncello\Validation\Validator;

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

use Limoncello\Validation\Contracts\Captures\CaptureAggregatorInterface;
use Limoncello\Validation\Contracts\Errors\ErrorAggregatorInterface;
use Limoncello\Validation\Contracts\Rules\RuleInterface;
use Limoncello\Validation\Execution\BlockInterpreter;
use Limoncello\Validation\Execution\BlockSerializer;

/**
 * @package Limoncello\Validation
 *
 * The trait expects the following method to be implemented by a class that uses this trait.
 * - createContextStorageFromBlocks(array $blocks): ContextStorageInterface
 */
trait SingleValidation
{
    /**
     * @var RuleInterface
     */
    private $rule;

    /**
     * @return RuleInterface
     */
    protected function getRule(): RuleInterface
    {
        return $this->rule;
    }

    /**
     * @param RuleInterface $rule
     *
     * @return self
     */
    private function setRule(RuleInterface $rule): self
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @param mixed                      $input
     * @param CaptureAggregatorInterface $captures
     * @param ErrorAggregatorInterface   $errors
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateSingleImplementation(
        $input,
        CaptureAggregatorInterface $captures,
        ErrorAggregatorInterface $errors
    ): void {
        $serialized = (new BlockSerializer())->serialize($this->getRule()->toBlock())->get();
        $blocks     = BlockSerializer::unserializeBlocks($serialized);

        // the method is expected to be implemented by a class that uses this trait
        $context = $this->createContextStorageFromBlocks($blocks);

        BlockInterpreter::execute($input, $serialized, $context, $captures, $errors);
    }
}

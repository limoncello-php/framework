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
trait ArrayValidation
{
    /**
     * @var array
     */
    private $serializedRules = [];

    /**
     * @return array
     */
    public function getSerializedRules(): array
    {
        return $this->serializedRules;
    }

    /**
     * @param array $serialized
     *
     * @return self
     */
    public function setSerializedRules(array $serialized): self
    {
        $this->serializedRules = $serialized;

        return $this;
    }

    /**
     * @param RuleInterface[]|iterable $rules
     *
     * @return self
     */
    private function setRules(iterable $rules): self
    {
        return $this->setSerializedRules($this->serializeRules($rules));
    }

    /**
     * @param array                      $input
     * @param CaptureAggregatorInterface $captures
     * @param ErrorAggregatorInterface   $errors
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function validateArrayImplementation(
        array $input,
        CaptureAggregatorInterface $captures,
        ErrorAggregatorInterface $errors
    ): void {
        list($indexMap, $serialized) = $this->getSerializedRules();

        $blocks = BlockSerializer::unserializeBlocks($serialized);

        // the method is expected to be implemented by a class that uses this trait
        $context = $this->createContextStorageFromBlocks($blocks);

        BlockInterpreter::executeStarts(
            BlockSerializer::unserializeBlocksWithStart($serialized),
            $blocks,
            $context,
            $errors
        );
        foreach ($input as $key => $value) {
            $blockIndex = $indexMap[$key];
            BlockInterpreter::executeBlock($value, $blockIndex, $blocks, $context, $captures, $errors);
        }
        BlockInterpreter::executeEnds(
            BlockSerializer::unserializeBlocksWithEnd($serialized),
            $blocks,
            $context,
            $errors
        );
    }

    /**
     * @var RuleInterface[]|iterable $rules
     *
     * @return array
     */
    private function serializeRules(iterable $rules): array
    {
        $serializer = new BlockSerializer();

        $indexMap = [];
        foreach ($rules as $name => $rule) {
            $indexMap[$name] = $serializer->addBlock($rule->setName($name)->enableCapture()->toBlock());
        }

        return [$indexMap, $serializer->get()];
    }
}

<?php namespace Limoncello\Auth\Authorization\PolicyDecision\Algorithms;

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
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetMatchEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @package Limoncello\Auth
 */
trait DefaultTargetSerializeTrait
{
    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param LoggerInterface|null $logger
     *
     * @return Generator
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function evaluateTargets(
        ContextInterface $context,
        array $optimizedTargets,
        ?LoggerInterface $logger
    ): Generator {
        list($isOptimizedForSwitch, $data) = $optimizedTargets;
        if ($isOptimizedForSwitch === true) {
            assert(count($data) === 2);
            list($contextKey, $valueRuleIdMap) = $data;
            if ($context->has($contextKey) === true &&
                array_key_exists($targetValue = $context->get($contextKey), $valueRuleIdMap) === true
            ) {
                $matchFound    = true;
                $matchedRuleId = $valueRuleIdMap[$targetValue];
            } else {
                $matchFound = false;
            }

            // when we are here we already know if the targets has match.
            // if match found we firstly yield matched rule ID and then the rest
            // otherwise (no match) we just yield 'no match' for every rule ID.
            if ($matchFound === true) {
                assert(isset($matchedRuleId));
                yield TargetMatchEnum::MATCH => $matchedRuleId;
                foreach ($valueRuleIdMap as $value => $ruleId) {
                    if ($ruleId !== $matchedRuleId) {
                        yield TargetMatchEnum::NOT_MATCH => $ruleId;
                    }
                }
            } else {
                foreach ($valueRuleIdMap as $value => $ruleId) {
                    yield TargetMatchEnum::NOT_MATCH => $ruleId;
                }
            }
        } else {
            foreach ($data as $ruleId => $anyOf) {
                $match = static::evaluateTarget($context, $anyOf, $logger);

                yield $match => $ruleId;
            }
        }
    }

    /**
     * @param ContextInterface     $context
     * @param array                $target
     * @param LoggerInterface|null $logger
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected static function evaluateTarget(
        ContextInterface $context,
        array $target,
        ?LoggerInterface $logger
    ): int {
        /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html #7.11 (table 4) */

        assert(Encoder::isTarget($target) === true);

        $anyOfs = Encoder::targetAnyOfs($target);
        $name   = $logger === null ? null : Encoder::targetName($target);

        if ($anyOfs === null) {
            $logger === null ?: $logger->info("Target '$name' matches anything.");

            return TargetMatchEnum::NO_TARGET;
        }

        $result = TargetMatchEnum::NOT_MATCH;
        foreach ($anyOfs as $allOfs) {
            try {
                $isAllOfApplicable = true;
                foreach ($allOfs as $key => $value) {
                    if ($context->has($key) === false || $context->get($key) !== $value) {
                        $isAllOfApplicable = false;
                        break;
                    }
                }
                if ($isAllOfApplicable === true) {
                    $logger === null ?: $logger->info("Target '$name' matches.");

                    return TargetMatchEnum::MATCH;
                }
            } catch (RuntimeException $exception) {
                $logger === null ?: $logger->warning("Target '$name' got exception from context for its properties.");
                $result = TargetMatchEnum::INDETERMINATE;
            }
        }

        $logger === null ?: $logger->debug("Target '$name' has no match.");

        return $result;
    }

    /**
     * @param TargetInterface[] $targets
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    protected function optimizeTargets(array $targets): array
    {
        if (($data = $this->tryToEncodeTargetsAsSwitch($targets)) !== null) {
            $isOptimizedForSwitch = true;
            assert(count($data) === 2); // context key and value => rule ID pairs.
        } else {
            $isOptimizedForSwitch = false;
            $data = [];
            foreach ($targets as $ruleId => $target) {
                $data[$ruleId] = $this->encodeTarget($target);
            }
        }

        return [$isOptimizedForSwitch, $data];
    }

    /**
     * @param TargetInterface|null $target
     *
     * @return array
     */
    protected static function encodeTarget(?TargetInterface $target): array
    {
        $name   = null;
        $anyOfs = null;

        if ($target !== null) {
            $name = $target->getName();
            foreach ($target->getAnyOf()->getAllOfs() as $allOf) {
                $anyOfs[] = $allOf->getPairs();
            }
        }

        return [
            Encoder::TYPE           => Encoder::TYPE_TARGET,
            Encoder::TARGET_NAME    => $name,
            Encoder::TARGET_ANY_OFS => $anyOfs,
        ];
    }

    /**
     * @param TargetInterface[] $targets
     *
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function tryToEncodeTargetsAsSwitch(array $targets): ?array
    {
        $result = count($targets) > 1;

        $contextKey  = null;
        $valueRuleIdMap = [];

        foreach ($targets as $ruleId => $nullableTarget) {
            if ($result === true &&
                $nullableTarget !== null &&
                count($allOfs = $nullableTarget->getAnyOf()->getAllOfs()) === 1 &&
                count($pairs = reset($allOfs)->getPairs()) === 1
            ) {
                $value = reset($pairs);
                $key   = key($pairs);
                assert($key !== null);
                if ($contextKey === null) {
                    $contextKey = $key;
                }
                if ($key !== $contextKey) {
                    $result = false;
                    break;
                }
                $valueRuleIdMap[$value] = $ruleId;
            } else {
                $result = false;
                break;
            }
        }

        $result = $result === true && count($valueRuleIdMap) === count($targets);

        // if result === true we know the following
        // - we have more than one target
        // - every target is a really one key, value pair
        // - in every such pair key is identical and every value is unique
        //
        // if so
        // $contextKey - will have that identical key and $valueRuleIdMap will be an array of [unique_value => true]
        // so later we can check if target matched by simply taking value from context by $contextKey and
        // checking that value in $valueRuleIdMap (key existence).

        return $result === true ? [$contextKey, $valueRuleIdMap] : null;
    }
}

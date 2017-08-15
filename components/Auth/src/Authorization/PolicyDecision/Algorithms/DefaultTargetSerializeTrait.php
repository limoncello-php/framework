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
     */
    public static function evaluateTargets(
        ContextInterface $context,
        array $optimizedTargets,
        ?LoggerInterface $logger
    ): Generator {
        foreach ($optimizedTargets as $ruleId => $anyOf) {
            $match = static::evaluateTarget($context, $anyOf, $logger);

            yield $match => $ruleId;
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
     * @param array $targets
     *
     * @return array
     */
    protected function optimizeTargets(array $targets): array
    {
        $result = [];
        foreach ($targets as $ruleId => $target) {
            $result[$ruleId] = $this->encodeTarget($target);
        }

        return $result;
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
}

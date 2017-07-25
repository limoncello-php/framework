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

use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Auth
 */
class PoliciesOrSetsPermitUnlessDeny extends BasePolicyOrSetAlgorithm
{
    use DefaultTargetSerializeTrait;

    /** @inheritdoc */
    const METHOD = [self::class, 'evaluate'];

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encPoliciesOrSets
     * @param LoggerInterface|null $logger
     *
     * @return array
     */
    public static function evaluate(
        ContextInterface $context,
        array $optimizedTargets,
        array $encPoliciesOrSets,
        LoggerInterface $logger = null
    ): array {
        return self::evaluatePermitUnlessDeny($context, $optimizedTargets, $encPoliciesOrSets, $logger);
    }
}

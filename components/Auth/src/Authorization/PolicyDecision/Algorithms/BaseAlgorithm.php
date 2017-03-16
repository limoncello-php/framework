<?php namespace Limoncello\Auth\Authorization\PolicyDecision\Algorithms;

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
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Auth
 */
abstract class BaseAlgorithm implements BaseAlgorithmInterface
{
    /** @var callable */
    const METHOD = null;

    /** Evaluation result index */
    const EVALUATION_VALUE = 0;

    /** Evaluation result index */
    const EVALUATION_OBLIGATIONS = self::EVALUATION_VALUE + 1;

    /** Evaluation result index */
    const EVALUATION_ADVICE = self::EVALUATION_OBLIGATIONS + 1;

    /**
     * @param callable             $method
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     */
    protected static function callAlgorithm(
        callable $method,
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        return call_user_func($method, $context, $optimizedTargets, $encodedItems, $logger);
    }

    /**
     * @param ContextInterface $context
     * @param array|null       $serializedLogical
     *
     * @return bool
     */
    protected static function evaluateLogical(ContextInterface $context, array $serializedLogical = null)
    {
        if ($serializedLogical === null) {
            return true;
        }

        $evaluation = call_user_func($serializedLogical, $context);
        $result     = $evaluation === true;

        return $result;
    }

    /**
     * @param int   $evaluation
     * @param array $obligations
     * @param array $advice
     *
     * @return array
     */
    protected static function packEvaluationResult($evaluation, array $obligations = [], array $advice = [])
    {
        return [
            self::EVALUATION_VALUE       => $evaluation,
            self::EVALUATION_OBLIGATIONS => $obligations,
            self::EVALUATION_ADVICE      => $advice,
        ];
    }

    /**
     * @param array $packedEvaluation
     *
     * @return int
     */
    protected static function unpackEvaluationValue(array $packedEvaluation)
    {
        return $packedEvaluation[self::EVALUATION_VALUE];
    }

    /**
     * @param array $packedEvaluation
     *
     * @return array
     */
    protected static function unpackEvaluationObligations(array $packedEvaluation)
    {
        return $packedEvaluation[self::EVALUATION_OBLIGATIONS];
    }

    /**
     * @param array $packedEvaluation
     *
     * @return array
     */
    protected static function unpackEvaluationAdvice(array $packedEvaluation)
    {
        return $packedEvaluation[self::EVALUATION_ADVICE];
    }

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     */
    protected static function evaluateFirstApplicable(
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        foreach (static::evaluateTargets($context, $optimizedTargets, $logger) as $match => $itemId) {
            $encodedItem      = $encodedItems[$itemId];
            $packedEvaluation = static::evaluateItem($context, $match, $encodedItem, $logger);
            $evaluation       = static::unpackEvaluationValue($packedEvaluation);
            if ($evaluation === EvaluationEnum::PERMIT || $evaluation === EvaluationEnum::DENY) {
                $obligations = static::unpackEvaluationObligations($packedEvaluation);
                $advice      = static::unpackEvaluationAdvice($packedEvaluation);

                return static::packEvaluationResult($evaluation, $obligations, $advice);
            }
        }

        return static::packEvaluationResult(EvaluationEnum::NOT_APPLICABLE);
    }

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     */
    protected static function evaluateDenyUnlessPermit(
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        foreach (static::evaluateTargets($context, $optimizedTargets, $logger) as $match => $itemId) {
            $encodedItem      = $encodedItems[$itemId];
            $packedEvaluation = static::evaluateItem($context, $match, $encodedItem, $logger);
            $evaluation       = static::unpackEvaluationValue($packedEvaluation);
            if ($evaluation === EvaluationEnum::PERMIT) {
                $obligations = static::unpackEvaluationObligations($packedEvaluation);
                $advice      = static::unpackEvaluationAdvice($packedEvaluation);

                return static::packEvaluationResult($evaluation, $obligations, $advice);
            }
        }

        return static::packEvaluationResult(EvaluationEnum::DENY);
    }

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     */
    protected static function evaluatePermitUnlessDeny(
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        foreach (static::evaluateTargets($context, $optimizedTargets, $logger) as $match => $itemId) {
            $encodedItem      = $encodedItems[$itemId];
            $packedEvaluation = static::evaluateItem($context, $match, $encodedItem, $logger);
            $evaluation       = static::unpackEvaluationValue($packedEvaluation);
            if ($evaluation === EvaluationEnum::DENY) {
                $obligations = static::unpackEvaluationObligations($packedEvaluation);
                $advice      = static::unpackEvaluationAdvice($packedEvaluation);

                return static::packEvaluationResult($evaluation, $obligations, $advice);
            }
        }

        return static::packEvaluationResult(EvaluationEnum::PERMIT);
    }

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function evaluateDenyOverrides(
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        $foundDeny          = false;
        $foundPermit        = false;
        $foundIntDeny       = false;
        $foundIntPermit     = false;
        $foundIntDenyPermit = false;

        $obligationsCollector = [];
        $adviceCollector      = [];

        foreach (static::evaluateTargets($context, $optimizedTargets, $logger) as $match => $itemId) {
            $encodedItem      = $encodedItems[$itemId];
            $packedEvaluation = static::evaluateItem($context, $match, $encodedItem, $logger);
            $evaluation       = static::unpackEvaluationValue($packedEvaluation);
            switch ($evaluation) {
                case EvaluationEnum::DENY:
                    $foundDeny = true;
                    break;
                case EvaluationEnum::PERMIT:
                    $foundPermit = true;
                    break;
                case EvaluationEnum::INDETERMINATE_DENY:
                    $foundIntDeny = true;
                    break;
                case EvaluationEnum::INDETERMINATE_PERMIT:
                    $foundIntPermit = true;
                    break;
                case EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT:
                    $foundIntDenyPermit = true;
                    break;
            }

            $itemObligations = static::unpackEvaluationObligations($packedEvaluation);
            $itemAdvice      = static::unpackEvaluationAdvice($packedEvaluation);

            $obligationsCollector = static::mergeToStorage($obligationsCollector, $evaluation, $itemObligations);
            $adviceCollector      = static::mergeToStorage($adviceCollector, $evaluation, $itemAdvice);
        }

        if ($foundDeny === true) {
            $evaluation = EvaluationEnum::DENY;
        } elseif ($foundIntDenyPermit === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
        } elseif ($foundIntDeny === true && ($foundIntPermit === true || $foundPermit === true)) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
        } elseif ($foundIntDeny === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY;
        } elseif ($foundPermit === true) {
            $evaluation = EvaluationEnum::PERMIT;
        } elseif ($foundIntPermit === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_PERMIT;
        } else {
            $evaluation = EvaluationEnum::NOT_APPLICABLE;
        }

        $obligations = Encoder::getFulfillObligations($evaluation, $obligationsCollector);
        $advice      = Encoder::getAppliedAdvice($evaluation, $adviceCollector);

        return static::packEvaluationResult($evaluation, $obligations, $advice);
    }

    /**
     * @param ContextInterface     $context
     * @param array                $optimizedTargets
     * @param array                $encodedItems
     * @param LoggerInterface|null $logger
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function evaluatePermitOverrides(
        ContextInterface $context,
        array $optimizedTargets,
        array $encodedItems,
        LoggerInterface $logger = null
    ) {
        $foundDeny          = false;
        $foundPermit        = false;
        $foundIntDeny       = false;
        $foundIntPermit     = false;
        $foundIntDenyPermit = false;

        $obligationsCollector = [];
        $adviceCollector      = [];

        foreach (static::evaluateTargets($context, $optimizedTargets, $logger) as $match => $itemId) {
            $encodedItem      = $encodedItems[$itemId];
            $packedEvaluation = static::evaluateItem($context, $match, $encodedItem, $logger);
            $evaluation       = static::unpackEvaluationValue($packedEvaluation);
            switch ($evaluation) {
                case EvaluationEnum::DENY:
                    $foundDeny = true;
                    break;
                case EvaluationEnum::PERMIT:
                    $foundPermit = true;
                    break;
                case EvaluationEnum::INDETERMINATE_DENY:
                    $foundIntDeny = true;
                    break;
                case EvaluationEnum::INDETERMINATE_PERMIT:
                    $foundIntPermit = true;
                    break;
                case EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT:
                    $foundIntDenyPermit = true;
                    break;
            }

            $itemObligations = static::unpackEvaluationObligations($packedEvaluation);
            $itemAdvice      = static::unpackEvaluationAdvice($packedEvaluation);

            $obligationsCollector = static::mergeToStorage($obligationsCollector, $evaluation, $itemObligations);
            $adviceCollector      = static::mergeToStorage($adviceCollector, $evaluation, $itemAdvice);
        }

        if ($foundPermit === true) {
            $evaluation = EvaluationEnum::PERMIT;
        } elseif ($foundIntDenyPermit === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
        } elseif ($foundIntPermit === true && ($foundIntDeny === true || $foundDeny === true)) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
        } elseif ($foundIntPermit === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_PERMIT;
        } elseif ($foundDeny === true) {
            $evaluation = EvaluationEnum::DENY;
        } elseif ($foundIntDeny === true) {
            $evaluation = EvaluationEnum::INDETERMINATE_DENY;
        } else {
            $evaluation = EvaluationEnum::NOT_APPLICABLE;
        }

        $obligations = Encoder::getFulfillObligations($evaluation, $obligationsCollector);
        $advice      = Encoder::getAppliedAdvice($evaluation, $adviceCollector);

        return static::packEvaluationResult($evaluation, $obligations, $advice);
    }

    /**
     * @param array      $storage
     * @param string|int $key
     * @param array      $list
     *
     * @return array
     */
    private static function mergeToStorage(array $storage, $key, array $list)
    {
        $storage[$key] = array_key_exists($key, $storage) === true ? array_merge($storage[$key], $list) : $list;

        return $storage;
    }
}

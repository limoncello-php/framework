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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyCombiningAlgorithmInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicySetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetMatchEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Limoncello\Auth
 */
abstract class BasePolicyOrSetAlgorithm extends BaseAlgorithm implements PolicyCombiningAlgorithmInterface
{
    /**
     * @param array $targets
     *
     * @return array
     */
    abstract protected function optimizeTargets(array $targets): array;

    /**
     * @param ContextInterface     $context
     * @param array                $policiesAndSetsData
     * @param LoggerInterface|null $logger
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function callPolicyAlgorithm(
        ContextInterface $context,
        array $policiesAndSetsData,
        LoggerInterface $logger = null
    ): array {
        return static::callAlgorithm(
            static::getCallable($policiesAndSetsData),
            $context,
            static::getTargets($policiesAndSetsData),
            static::getPoliciesAndSets($policiesAndSetsData),
            $logger
        );
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function optimize(array $policiesAndSets): array
    {
        assert(empty($policiesAndSets) === false);

        $index              = 0;
        $rawTargets         = [];
        $serializedPolicies = [];
        foreach ($policiesAndSets as $policyOrSet) {
            /** @var PolicyInterface|PolicySetInterface $policyOrSet */
            $rawTargets[$index]         = $policyOrSet->getTarget();
            $serializedPolicies[$index] = $policyOrSet instanceof PolicyInterface ?
                Encoder::encodePolicy($policyOrSet) : Encoder::encodePolicySet($policyOrSet);
            $index++;
        }

        $callable         = static::METHOD;
        $optimizedTargets = $this->optimizeTargets($rawTargets);

        /** @var callable|array $callable */
        assert($callable !== null && is_array($callable) === true &&
            is_callable($callable) === true && count($callable) === 2 &&
            is_string($callable[0]) === true && is_string($callable[1]) === true);

        return [
            static::INDEX_TARGETS           => $optimizedTargets,
            static::INDEX_POLICIES_AND_SETS => $serializedPolicies,
            static::INDEX_CALLABLE          => $callable,
        ];
    }

    /**
     * @param ContextInterface     $context
     * @param int                  $match
     * @param array                $encodedPolicy
     * @param LoggerInterface|null $logger
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function evaluatePolicy(
        ContextInterface $context,
        int $match,
        array $encodedPolicy,
        LoggerInterface $logger = null
    ): array {
        assert(Encoder::isPolicy($encodedPolicy));
        assert($match !== TargetMatchEnum::NO_TARGET);

        /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html #7.12 (table 5) */

        // Reminder on obligations and advice (from 7.18)
        //---------------------------------------------------------------------
        // no obligations or advice SHALL be returned to the PEP if the rule,
        // policies, or policy sets from which they are drawn are not evaluated,
        // or if their evaluated result is "Indeterminate" or "NotApplicable",
        // or if the decision resulting from evaluating the rule, policy,
        // or policy set does not match the decision resulting from evaluating
        // an enclosing policy set.

        $policyName = null;
        if ($logger !== null) {
            $policyName = Encoder::policyName($encodedPolicy);
            $matchName  = TargetMatchEnum::toString($match);
            $logger->debug("Policy '$policyName' evaluation started for match '$matchName'.");
        }

        if ($match === TargetMatchEnum::MATCH || $match === TargetMatchEnum::INDETERMINATE) {
            list ($evaluation, $obligations, $advice) = BaseRuleAlgorithm::callRuleAlgorithm(
                $context,
                Encoder::rulesData(Encoder::policyRules($encodedPolicy)),
                $logger
            );

            if ($match === TargetMatchEnum::INDETERMINATE) {
                // evaluate final result in accordance to table 7
                $correctedEvaluation = static::correctEvaluationOnIntermediateTarget($evaluation, $logger);
                $result              = static::packEvaluationResult($correctedEvaluation);
            } else {
                $obligationsMap = Encoder::policyObligations($encodedPolicy);
                $adviceMap      = Encoder::policyAdvice($encodedPolicy);
                $result         = static::packEvaluationResult(
                    $evaluation,
                    static::mergeFulfilledObligations($obligations, $evaluation, $obligationsMap),
                    static::mergeAppliedAdvice($advice, $evaluation, $adviceMap)
                );
            }
        } else {
            assert($match === TargetMatchEnum::NOT_MATCH);
            $result = static::packEvaluationResult(EvaluationEnum::NOT_APPLICABLE);
        }

        $logger === null ?: $logger->debug("Policy '$policyName' evaluation ended.");

        return $result;
    }

    /**
     * @param ContextInterface     $context
     * @param int                  $match
     * @param array                $encodedPolicySet
     * @param LoggerInterface|null $logger
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public static function evaluatePolicySet(
        ContextInterface $context,
        int $match,
        array $encodedPolicySet,
        LoggerInterface $logger = null
    ) {
        assert(Encoder::isPolicySet($encodedPolicySet));

        /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html #7.13 (table 6) */

        // Reminder on obligations and advice (from 7.18)
        //---------------------------------------------------------------------
        // no obligations or advice SHALL be returned to the PEP if the rule,
        // policies, or policy sets from which they are drawn are not evaluated,
        // or if their evaluated result is "Indeterminate" or "NotApplicable",
        // or if the decision resulting from evaluating the rule, policy,
        // or policy set does not match the decision resulting from evaluating
        // an enclosing policy set.

        $policySetName = null;
        if ($logger !== null) {
            $policySetName = Encoder::policySetName($encodedPolicySet);
            $matchName     = TargetMatchEnum::toString($match);
            $logger->debug("Policy set '$policySetName' evaluation started for match '$matchName'.");
        }

        if ($match === TargetMatchEnum::MATCH ||
            $match === TargetMatchEnum::NO_TARGET ||
            $match === TargetMatchEnum::INDETERMINATE
        ) {
            list ($evaluation, $obligations, $advice) = static::callPolicyAlgorithm(
                $context,
                Encoder::policiesAndSetsData(Encoder::policySetChildren($encodedPolicySet)),
                $logger
            );

            if ($match === TargetMatchEnum::INDETERMINATE) {
                // evaluate final result in accordance to table 7
                $correctedEvaluation = static::correctEvaluationOnIntermediateTarget($evaluation, $logger);
                $result = static::packEvaluationResult($correctedEvaluation);
            } else {
                $obligationsMap = Encoder::policySetObligations($encodedPolicySet);
                $adviceMap      = Encoder::policySetAdvice($encodedPolicySet);
                $result         = static::packEvaluationResult(
                    $evaluation,
                    static::mergeFulfilledObligations($obligations, $evaluation, $obligationsMap),
                    static::mergeAppliedAdvice($advice, $evaluation, $adviceMap)
                );
            }
        } else {
            assert($match === TargetMatchEnum::NOT_MATCH);
            $result = static::packEvaluationResult(EvaluationEnum::NOT_APPLICABLE);
        }

        $logger === null ?: $logger->debug("Policy set '$policySetName' evaluation ended.");

        return $result;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public static function evaluateItem(
        ContextInterface $context,
        int $match,
        array $encodedItem,
        LoggerInterface $logger = null
    ): array {
        $isSet = Encoder::isPolicySet($encodedItem);

        return $isSet === true ?
            static::evaluatePolicySet($context, $match, $encodedItem, $logger) :
            static::evaluatePolicy($context, $match, $encodedItem, $logger);
    }

    /**
     * @param int                  $evaluation
     * @param LoggerInterface|null $logger
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function correctEvaluationOnIntermediateTarget(int $evaluation, LoggerInterface $logger = null): int
    {
        /** @see http://docs.oasis-open.org/xacml/3.0/xacml-3.0-core-spec-os-en.html #7.14 (table 7) */

        switch ($evaluation) {
            case EvaluationEnum::NOT_APPLICABLE:
                $result = EvaluationEnum::NOT_APPLICABLE;
                break;
            case EvaluationEnum::PERMIT:
                $result = EvaluationEnum::INDETERMINATE_PERMIT;
                break;
            case EvaluationEnum::DENY:
                $result = EvaluationEnum::INDETERMINATE_DENY;
                break;
            case EvaluationEnum::INDETERMINATE:
                $result = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
                break;
            case EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT:
                $result = EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT;
                break;
            case EvaluationEnum::INDETERMINATE_PERMIT:
                $result = EvaluationEnum::INDETERMINATE_PERMIT;
                break;
            default:
                assert($evaluation === EvaluationEnum::INDETERMINATE_DENY);
                $result = EvaluationEnum::INDETERMINATE_DENY;
                break;
        }

        if ($logger !== null) {
            $fromEval = EvaluationEnum::toString($evaluation);
            $toEval   = EvaluationEnum::toString($result);
            $logger->info("Due to error while checking target the evaluation changed from '$fromEval' to '$toEval'.");
        }

        return $result;
    }

    /**
     * @param array $policiesAndSetsData
     *
     * @return array
     */
    private static function getTargets(array $policiesAndSetsData): array
    {
        return $policiesAndSetsData[self::INDEX_TARGETS];
    }

    /**
     * @param array $policiesAndSetsData
     *
     * @return array
     */
    private static function getPoliciesAndSets(array $policiesAndSetsData): array
    {
        return $policiesAndSetsData[self::INDEX_POLICIES_AND_SETS];
    }

    /**
     * @param array $policiesAndSetsData
     *
     * @return callable
     */
    private static function getCallable(array $policiesAndSetsData)
    {
        return $policiesAndSetsData[self::INDEX_CALLABLE];
    }

    /**
     * @param array $obligations
     * @param int   $evaluation
     * @param array $obligationsMap
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function mergeFulfilledObligations(array $obligations, $evaluation, array $obligationsMap): array
    {
        return array_merge($obligations, Encoder::getFulfillObligations($evaluation, $obligationsMap));
    }

    /**
     * @param array $advice
     * @param int   $evaluation
     * @param array $adviceMap
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private static function mergeAppliedAdvice(array $advice, $evaluation, array $adviceMap): array
    {
        return array_merge($advice, Encoder::getAppliedAdvice($evaluation, $adviceMap));
    }
}

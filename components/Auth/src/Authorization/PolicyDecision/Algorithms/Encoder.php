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

use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\AdviceInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\MethodInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\ObligationInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyCombiningAlgorithmInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicySetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleCombiningAlgorithmInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;

/**
 * @package Limoncello\Auth
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Encoder
{
    use DefaultTargetSerializeTrait;

    /** Type index */
    const TYPE = 0;

    /** Encoded type */
    const TYPE_TARGET = 0;

    /** Encoded type */
    const TYPE_RULE = self::TYPE_TARGET + 1;

    /** Encoded type */
    const TYPE_POLICY = self::TYPE_RULE + 1;

    /** Encoded type */
    const TYPE_POLICY_SET = self::TYPE_POLICY + 1;

    /** Encoded type */
    const TYPE_RULES = self::TYPE_POLICY_SET + 1;

    /** Encoded type */
    const TYPE_POLICIES_AND_SETS = self::TYPE_RULES + 1;

    /** Rule index */
    const TARGET_NAME = self::TYPE + 1;

    /** Rule index */
    const TARGET_ANY_OFS = self::TARGET_NAME + 1;

    /** Rule index */
    const RULE_NAME = self::TYPE + 1;

    /** Rule index */
    const RULE_TARGET = self::RULE_NAME + 1;

    /** Rule index */
    const RULE_CONDITION = self::RULE_TARGET + 1;

    /** Rule index */
    const RULE_EFFECT = self::RULE_CONDITION + 1;

    /** Rule index */
    const RULE_OBLIGATIONS = self::RULE_EFFECT + 1;

    /** Rule index */
    const RULE_ADVICE = self::RULE_OBLIGATIONS + 1;

    /** Policy index */
    const POLICY_NAME = self::TYPE + 1;

    /** Policy index */
    const POLICY_TARGET = self::POLICY_NAME + 1;

    /** Policy index */
    const POLICY_OBLIGATIONS = self::POLICY_TARGET + 1;

    /** Policy index */
    const POLICY_ADVICE = self::POLICY_OBLIGATIONS + 1;

    /** Policy index */
    const POLICY_RULES = self::POLICY_ADVICE + 1;

    /** Policy index */
    const POLICY_SET_NAME = self::TYPE + 1;

    /** Policy index */
    const POLICY_SET_TARGET = self::POLICY_SET_NAME + 1;

    /** Policy index */
    const POLICY_SET_OBLIGATIONS = self::POLICY_SET_TARGET + 1;

    /** Policy index */
    const POLICY_SET_ADVICE = self::POLICY_SET_OBLIGATIONS + 1;

    /** Policy index */
    const POLICY_SET_CHILDREN = self::POLICY_SET_ADVICE + 1;

    /** Rules index */
    const RULES_DATA = self::TYPE + 1;

    /** Rules index */
    const POLICIES_AND_SETS_DATA = self::TYPE + 1;

    /**
     * @param RuleInterface $rule
     *
     * @return array
     */
    public static function encodeRule(RuleInterface $rule)
    {
        $result = [
            static::TYPE             => static::TYPE_RULE,
            static::RULE_NAME        => $rule->getName(),
            static::RULE_TARGET      => static::encodeTarget($rule->getTarget()),
            static::RULE_CONDITION   => static::serializeMethod($rule->getCondition()),
            static::RULE_EFFECT      => static::serializeMethod($rule->effect()),
            static::RULE_OBLIGATIONS => static::encodeObligations($rule->getObligations()),
            static::RULE_ADVICE      => static::encodeAdvice($rule->getAdvice()),
        ];

        return $result;
    }

    /**
     * @param PolicyInterface $policy
     *
     * @return array
     */
    public static function encodePolicy(PolicyInterface $policy)
    {
        $result = [
            static::TYPE               => static::TYPE_POLICY,
            static::POLICY_NAME        => $policy->getName(),
            static::POLICY_TARGET      => static::encodeTarget($policy->getTarget()),
            static::POLICY_OBLIGATIONS => static::encodeObligations($policy->getObligations()),
            static::POLICY_ADVICE      => static::encodeAdvice($policy->getAdvice()),
            static::POLICY_RULES       => static::encodeRules($policy->getCombiningAlgorithm(), $policy->getRules()),
        ];

        return $result;
    }

    /**
     * @param PolicySetInterface $set
     *
     * @return array
     */
    public static function encodePolicySet(PolicySetInterface $set)
    {
        $algorithm       = $set->getCombiningAlgorithm();
        $policiesAndSets = $set->getPoliciesAndSets();
        $result          = [
            static::TYPE                   => static::TYPE_POLICY_SET,
            static::POLICY_SET_NAME        => $set->getName(),
            static::POLICY_SET_TARGET      => static::encodeTarget($set->getTarget()),
            static::POLICY_SET_OBLIGATIONS => static::encodeObligations($set->getObligations()),
            static::POLICY_SET_ADVICE      => static::encodeAdvice($set->getAdvice()),
            static::POLICY_SET_CHILDREN    => static::serializePoliciesAndSets($algorithm, $policiesAndSets),
        ];

        return $result;
    }

    /**
     * @param array $encoded
     *
     * @return int
     */
    public static function getType(array $encoded)
    {
        assert(array_key_exists(static::TYPE, $encoded) === true);

        return $encoded[static::TYPE];
    }

    /**
     * @param array $encoded
     *
     * @return bool
     */
    public static function isTarget(array $encoded)
    {
        return static::getType($encoded) === static::TYPE_TARGET;
    }

    /**
     * @param array $encoded
     *
     * @return bool
     */
    public static function isRule(array $encoded)
    {
        return static::getType($encoded) === static::TYPE_RULE;
    }

    /**
     * @param array $encoded
     *
     * @return bool
     */
    public static function isPolicy(array $encoded)
    {
        return static::getType($encoded) === static::TYPE_POLICY;
    }

    /**
     * @param array $encoded
     *
     * @return bool
     */
    public static function isPolicySet(array $encoded)
    {
        $type = static::getType($encoded);

        assert($type === static::TYPE_POLICY || $type === static::TYPE_POLICY_SET);

        return $type === static::TYPE_POLICY_SET;
    }

    /**
     * @param array $encodedTarget
     *
     * @return string|null
     */
    public static function targetName(array $encodedTarget)
    {
        assert(static::isTarget($encodedTarget));

        return $encodedTarget[self::TARGET_NAME];
    }

    /**
     * @param array $encodedTarget
     *
     * @return array|null
     */
    public static function targetAnyOfs(array $encodedTarget)
    {
        assert(static::isTarget($encodedTarget));

        return $encodedTarget[self::TARGET_ANY_OFS];
    }

    /**
     * @param array $encodedRule
     *
     * @return array
     */
    public static function ruleName(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_NAME];
    }

    /**
     * @param array $encodedRule
     *
     * @return array
     */
    public static function ruleTarget(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_TARGET];
    }

    /**
     * @param array $encodedRule
     *
     * @return array
     */
    public static function ruleEffect(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_EFFECT];
    }

    /**
     * @param array $encodedRule
     *
     * @return callable|null
     */
    public static function ruleCondition(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_CONDITION];
    }

    /**
     * @param array $encodedRule
     *
     * @return callable[]
     */
    public static function ruleObligations(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_OBLIGATIONS];
    }

    /**
     * @param array $encodedRule
     *
     * @return callable[]
     */
    public static function ruleAdvice(array $encodedRule)
    {
        assert(static::isRule($encodedRule));

        return $encodedRule[static::RULE_ADVICE];
    }

    /**
     * @param array $encodedPolicy
     *
     * @return array
     */
    public static function policyName(array $encodedPolicy)
    {
        assert(static::isPolicy($encodedPolicy));

        return $encodedPolicy[static::POLICY_NAME];
    }

    /**
     * @param array $encodedPolicy
     *
     * @return array
     */
    public static function policyTarget(array $encodedPolicy)
    {
        assert(static::isPolicy($encodedPolicy));

        return $encodedPolicy[static::POLICY_TARGET];
    }

    /**
     * @param array $encodedPolicy
     *
     * @return callable[]
     */
    public static function policyObligations(array $encodedPolicy)
    {
        assert(static::isPolicy($encodedPolicy));

        return $encodedPolicy[static::POLICY_OBLIGATIONS];
    }

    /**
     * @param array $encodedPolicy
     *
     * @return callable[]
     */
    public static function policyAdvice(array $encodedPolicy)
    {
        assert(static::isPolicy($encodedPolicy));

        return $encodedPolicy[static::POLICY_ADVICE];
    }

    /**
     * @param array $encodedPolicy
     *
     * @return array
     */
    public static function policyRules(array $encodedPolicy)
    {
        assert(static::isPolicy($encodedPolicy));

        return $encodedPolicy[static::POLICY_RULES];
    }

    /**
     * @param array $encodedPolicySet
     *
     * @return array
     */
    public static function policySetName(array $encodedPolicySet)
    {
        assert(static::isPolicySet($encodedPolicySet));

        return $encodedPolicySet[static::POLICY_SET_NAME];
    }

    /**
     * @param array $encodedPolicySet
     *
     * @return array
     */
    public static function policySetTarget(array $encodedPolicySet)
    {
        assert(static::isPolicySet($encodedPolicySet));

        return $encodedPolicySet[static::POLICY_SET_TARGET];
    }

    /**
     * @param array $encodedPolicySet
     *
     * @return callable[]
     */
    public static function policySetObligations(array $encodedPolicySet)
    {
        assert(static::isPolicySet($encodedPolicySet));

        return $encodedPolicySet[static::POLICY_SET_OBLIGATIONS];
    }

    /**
     * @param array $encodedPolicySet
     *
     * @return callable[]
     */
    public static function policySetAdvice(array $encodedPolicySet)
    {
        assert(static::isPolicySet($encodedPolicySet));

        return $encodedPolicySet[static::POLICY_SET_ADVICE];
    }

    /**
     * @param array $encodedPolicySet
     *
     * @return array
     */
    public static function policySetChildren(array $encodedPolicySet)
    {
        assert(static::isPolicySet($encodedPolicySet));

        return $encodedPolicySet[static::POLICY_SET_CHILDREN];
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    public static function rulesData(array $rules)
    {
        assert(static::getType($rules) === static::TYPE_RULES);

        return $rules[static::RULES_DATA];
    }

    /**
     * @param array $policesAndSets
     *
     * @return array
     */
    public static function policiesAndSetsData(array $policesAndSets)
    {
        assert(static::getType($policesAndSets) === static::TYPE_POLICIES_AND_SETS);

        return $policesAndSets[static::POLICIES_AND_SETS_DATA];
    }

    /**
     * @param int   $evaluation
     * @param array $obligations
     *
     * @return array
     */
    public static function getFulfillObligations($evaluation, array $obligations)
    {
        $result = array_key_exists($evaluation, $obligations) === true ? $obligations[$evaluation] : [];

        return $result;
    }

    /**
     * @param int   $evaluation
     * @param array $advice
     *
     * @return array
     */
    public static function getAppliedAdvice($evaluation, array $advice)
    {
        $result = array_key_exists($evaluation, $advice) === true ? $advice[$evaluation] : [];

        return $result;
    }

    /**
     * @param MethodInterface|null $method
     *
     * @return callable|null
     */
    private static function serializeMethod(MethodInterface $method = null)
    {
        return $method === null ? null : $method->getCallable();
    }

    /**
     * @param ObligationInterface[] $obligations
     *
     * @return array
     */
    private static function encodeObligations(array $obligations)
    {
        $result = [];
        foreach ($obligations as $item) {
            /** @var ObligationInterface $item*/
            $result[$item->getFulfillOn()][] = $item->getCallable();
        }

        return $result;
    }

    /**
     * @param AdviceInterface[] $advice
     *
     * @return array
     */
    private static function encodeAdvice(array $advice)
    {
        $result = [];
        foreach ($advice as $item) {
            /** @var AdviceInterface $item*/
            $result[$item->getAppliesTo()][] = $item->getCallable();
        }

        return $result;
    }

    /**
     * @param RuleCombiningAlgorithmInterface $algorithm
     * @param array                           $rules
     *
     * @return array
     */
    private static function encodeRules(RuleCombiningAlgorithmInterface $algorithm, array $rules)
    {
        return [
            static::TYPE       => static::TYPE_RULES,
            static::RULES_DATA => $algorithm->optimize($rules)
        ];
    }

    /**
     * @param PolicyCombiningAlgorithmInterface $algorithm
     * @param array                             $policiesAndSets
     *
     * @return array
     */
    private static function serializePoliciesAndSets(
        PolicyCombiningAlgorithmInterface $algorithm,
        array $policiesAndSets
    ) {
        return [
            static::TYPE                   => static::TYPE_POLICIES_AND_SETS,
            static::POLICIES_AND_SETS_DATA => $algorithm->optimize($policiesAndSets)
        ];
    }
}

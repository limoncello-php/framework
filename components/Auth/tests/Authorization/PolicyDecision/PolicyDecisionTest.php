<?php namespace Limoncello\Tests\Auth\Authorization\PolicyDecision;

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

use Limoncello\Auth\Authorization\PolicyAdministration\Advice;
use Limoncello\Auth\Authorization\PolicyAdministration\AllOf;
use Limoncello\Auth\Authorization\PolicyAdministration\AnyOf;
use Limoncello\Auth\Authorization\PolicyAdministration\Logical;
use Limoncello\Auth\Authorization\PolicyAdministration\Obligation;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyAdministration\Target;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Authorization\PolicyEnforcement\Request;
use Limoncello\Auth\Authorization\PolicyInformation\Context;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleCombiningAlgorithmInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\RuleInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyInformation\ContextInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Limoncello\Tests\Auth
 */
class PolicyDecisionTest extends TestCase
{
    /**
     * @var bool
     */
    private static $wasConditionCalled = false;

    /**
     * @var bool
     */
    private static $wasObligationCalled = false;

    /**
     * @var bool
     */
    private static $wasAdviceCalled = false;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        static::$wasConditionCalled  = false;
        static::$wasObligationCalled = false;
        static::$wasAdviceCalled     = false;
    }

    /**
     * @return array
     */
    public function testOptimize()
    {
        $algorithm = RuleAlgorithm::denyUnlessPermit();

        list($callable, $targets, $rules) = $this->optimizedRules($algorithm, $this->createRules());

        $this->assertTrue(is_callable($callable));
        $this->assertTrue(is_array($callable));
        $this->assertCount(4, $targets);
        $this->assertCount(4, $rules);

        return [$callable, $targets, $rules];
    }

    /**
     * Test 'Deny' result.
     */
    public function testDeny()
    {
        $algorithm = RuleAlgorithm::denyUnlessPermit();
        list($callable, $targets, $rules) = $this->optimizedRules($algorithm, $this->createRules(false));

        $logger  = null;
        $context = new Context(new Request([]), []);
        $this->assertEquals(
            [EvaluationEnum::DENY, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test 'Permit' for 'no-target'.
     */
    public function testNoTargetPermit()
    {
        list($callable, $targets, $rules) = $this->testOptimize();

        $logger  = null;
        $context = new Context(new Request([]), []);
        $this->assertEquals(
            [EvaluationEnum::PERMIT, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test 'Permit'.
     */
    public function testPermit11()
    {
        list($callable, $targets, $rules) = $this->testOptimize();

        $logger  = null;
        $context = new Context(new Request([
            'key11_1' => 'value11_1',
            'key11_2' => 'value11_2',
            'key11_3' => 'value11_3',
        ]), []);
        $this->assertEquals(
            [EvaluationEnum::PERMIT, [[self::class, 'ruleObligation1']], [[self::class, 'ruleAdvice1']]],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test 'Permit'.
     */
    public function testPermit12()
    {
        list($callable, $targets, $rules) = $this->testOptimize();

        // value pairs could be split (should be no difference)
        $logger  = null;
        $context = new Context(new Request([
            'key12_1' => 'value12_1',
        ]), [
            'key12_2' => 'value12_2',
        ]);
        $this->assertEquals(
            [EvaluationEnum::PERMIT, [[self::class, 'ruleObligation1']], [[self::class, 'ruleAdvice1']]],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test 'Deny Overrides'.
     */
    public function testDenyOverrides()
    {
        $algorithm = RuleAlgorithm::denyOverrides();
        list($callable, $targets, $rules) = $this->optimizedRules($algorithm, $this->createRules());

        $logger  = null;
        $context = new Context(new Request([
            'key21_1' => 'value21_1',
            'key21_2' => 'value21_2',
            'key21_3' => 'value21_3',
        ]), []);
        $this->assertEquals(
            [EvaluationEnum::DENY, [[self::class, 'ruleObligation2']], [[self::class, 'ruleAdvice2']]],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test exception in condition.
     */
    public function testExceptionInCondition()
    {
        $algorithm = RuleAlgorithm::denyUnlessPermit();
        list($callable, $targets, $rules) = $this->optimizedRules($algorithm, $this->createRules(false, true));

        $logger  = null;
        $context = new Context(new Request([
            'key11_1' => 'value11_1',
            'key11_2' => 'value11_2',
            'key11_3' => 'value11_3',
        ]), []);
        $this->assertEquals(
            [EvaluationEnum::DENY, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
    }

    /**
     * Test exception in target.
     */
    public function testExceptionInTarget()
    {
        $algorithm = RuleAlgorithm::denyUnlessPermit();
        list($callable, $targets, $rules) = $this->optimizedRules($algorithm, $this->createRules(false));

        $logger          = null;
        $exceptionThrown = false;
        $context         = new Context(new Request([]), [
            'key11_1' => function () use (&$exceptionThrown) {
                $exceptionThrown = true;
                throw new RuntimeException();
            },
        ]);
        $this->assertEquals(
            [EvaluationEnum::DENY, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
        $this->assertTrue($exceptionThrown);
    }

    /**
     * Test 'false' in condition.
     */
    public function testFalseInCondition()
    {
        $rule = (new Rule())
            ->setTarget($this->target('key', 'value'))
            ->setCondition(new Logical([self::class, 'ruleConditionFalse']));
        list($callable, $targets, $rules) = $this->optimizedRules(RuleAlgorithm::denyUnlessPermit(), [$rule]);

        $logger  = null;
        $context = new Context(new Request([]), ['key' => 'value']);
        $this->assertEquals(
            [EvaluationEnum::DENY, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
        $this->assertTrue(static::$wasConditionCalled);
    }

    /**
     * Test effect fails.
     */
    public function testEffectFails()
    {
        $rule = (new Rule())
            ->setTarget($this->target('key', 'value'))
            ->setEffect(new Logical([self::class, 'logicalThrowsException']));
        list($callable, $targets, $rules) = $this->optimizedRules(RuleAlgorithm::permitOverrides(), [$rule]);

        $logger  = null;
        $context = new Context(new Request([]), ['key' => 'value']);
        $this->assertEquals(
            [EvaluationEnum::DENY, [], []],
            call_user_func($callable, $context, $targets, $rules, $logger)
        );
        $this->assertTrue(static::$wasConditionCalled);
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function ruleCondition(ContextInterface $context)
    {
        static::assertNotNull($context);

        static::$wasConditionCalled = true;

        return true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function ruleConditionFalse(ContextInterface $context)
    {
        static::assertNotNull($context);

        static::$wasConditionCalled = true;

        return false;
    }

    /**
     * @param ContextInterface $context
     *
     * @throws RuntimeException
     */
    public static function logicalThrowsException(ContextInterface $context)
    {
        static::assertNotNull($context);

        static::$wasConditionCalled = true;

        throw new RuntimeException();
    }

    /**
     * @param ContextInterface $context
     */
    public static function ruleObligation1(ContextInterface $context)
    {
        static::assertNotNull($context);
        static::$wasObligationCalled = true;
    }

    /**
     * @param ContextInterface $context
     */
    public static function ruleAdvice1(ContextInterface $context)
    {
        static::assertNotNull($context);
        static::$wasAdviceCalled = true;
    }

    /**
     * @param ContextInterface $context
     */
    public static function ruleObligation2(ContextInterface $context)
    {
        static::assertNotNull($context);
        static::$wasObligationCalled = true;
    }

    /**
     * @param ContextInterface $context
     */
    public static function ruleAdvice2(ContextInterface $context)
    {
        static::assertNotNull($context);
        static::$wasAdviceCalled = true;
    }

    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public static function effectDeny(ContextInterface $context)
    {
        static::assertNotNull($context);

        return false;
    }

    /**
     * @param RuleCombiningAlgorithmInterface $algorithm
     * @param RuleInterface[]                 $rules
     *
     * @return array
     */
    private function optimizedRules(RuleCombiningAlgorithmInterface $algorithm, array $rules)
    {
        $this->assertNotEmpty($optimized = $algorithm->optimize($rules));
        $this->assertNotEmpty($targets = $optimized[RuleCombiningAlgorithmInterface::INDEX_TARGETS]);
        $this->assertNotEmpty($rules = $optimized[RuleCombiningAlgorithmInterface::INDEX_RULES]);
        $this->assertNotEmpty($callable = $optimized[RuleCombiningAlgorithmInterface::INDEX_CALLABLE]);

        return [$callable, $targets, $rules];
    }

    /**
     * @param bool $addTargetAll
     * @param bool $exInCondition
     *
     * @return RuleInterface[]
     */
    private function createRules($addTargetAll = true, $exInCondition = false)
    {
        $allOf11 = new AllOf([
            'key11_1' => 'value11_1',
            'key11_2' => 'value11_2',
            'key11_3' => 'value11_3',
        ]);
        $allOf12 = new AllOf([
            'key12_1' => 'value12_1',
            'key12_2' => 'value12_2',
        ]);
        $allOf21 = new AllOf([
            'key21_1' => 'value21_1',
            'key21_2' => 'value21_2',
            'key21_3' => 'value21_3',
        ]);
        $allOf22 = new AllOf([
            'key22_1' => 'value22_1',
            'key22_2' => 'value22_2',
        ]);
        $allOf3  = new AllOf([
            'key31' => 'value31',
        ]);

        $anyOf1 = new AnyOf([$allOf11, $allOf12]);
        $anyOf2 = new AnyOf([$allOf21, $allOf22]);
        $anyOf3 = new AnyOf([$allOf3]);

        $target1 = new Target($anyOf1);
        $target2 = new Target($anyOf2);
        $target3 = new Target($anyOf3);

        $methodName = $exInCondition === true ? 'logicalThrowsException' : 'ruleCondition';
        $rule1      = (new Rule())
            ->setTarget($target1)
            ->setCondition(new Logical([self::class, $methodName]))
            ->setObligations([new Obligation(EvaluationEnum::PERMIT, [self::class, 'ruleObligation1'])])
            ->setAdvice([new Advice(EvaluationEnum::PERMIT, [self::class, 'ruleAdvice1'])]);
        $rule2      = (new Rule())
            ->setEffect(new Logical([self::class, 'effectDeny']))
            ->setTarget($target2)
            ->setCondition(new Logical([self::class, $methodName]))
            ->setObligations([new Obligation(EvaluationEnum::DENY, [self::class, 'ruleObligation2'])])
            ->setAdvice([new Advice(EvaluationEnum::DENY, [self::class, 'ruleAdvice2'])]);
        $rule3      = (new Rule())->setTarget($target3);
        $rule4      = new Rule();

        return $addTargetAll === true ? [$rule1, $rule2, $rule3, $rule4] : [$rule1, $rule2, $rule3];
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return TargetInterface
     */
    private function target($key, $value)
    {
        return new Target(new AnyOf([new AllOf([$key => $value])]));
    }
}

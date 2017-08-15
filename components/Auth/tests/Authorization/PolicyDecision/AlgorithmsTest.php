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
use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\PolicySet;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyAdministration\Target;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\PoliciesOrSetsDenyOverrides;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\PoliciesOrSetsDenyUnlessPermit;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\PoliciesOrSetsFirstApplicable;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\PoliciesOrSetsPermitOverrides;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\PoliciesOrSetsPermitUnlessDeny;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\RulesDenyOverrides;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\RulesFirstApplicable;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\RulesPermitOverrides;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\RulesPermitUnlessDeny;
use Limoncello\Auth\Authorization\PolicyEnforcement\Request;
use Limoncello\Auth\Authorization\PolicyInformation\Context;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\EvaluationEnum;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @package Limoncello\Tests\Auth
 */
class AlgorithmsTest extends TestCase
{
    const CALLBACK_11 = [self::class, 'callback11'];
    const CALLBACK_12 = [self::class, 'callback12'];
    const CALLBACK_21 = [self::class, 'callback21'];
    const CALLBACK_22 = [self::class, 'callback22'];

    /**
     * @var int
     */
    private static $callback11Counter;

    /**
     * @var int
     */
    private static $callback12Counter;

    /**
     * @var int
     */
    private static $callback21Counter;

    /**
     * @var int
     */
    private static $callback22Counter;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        static::$callback11Counter = 0;
        static::$callback12Counter = 0;
        static::$callback21Counter = 0;
        static::$callback22Counter = 0;
    }

    /**
     * Test first applicable algorithm.
     */
    public function testRuleFirstApplicable()
    {
        $algorithm    = new RulesFirstApplicable();
        $advice11     = new Advice(EvaluationEnum::PERMIT, self::CALLBACK_11);
        $advice12     = new Advice(EvaluationEnum::DENY, self::CALLBACK_12);
        $advice21     = new Advice(EvaluationEnum::PERMIT, self::CALLBACK_21);
        $advice22     = new Advice(EvaluationEnum::DENY, self::CALLBACK_22);
        $logicalFalse = new Logical([static::class, 'logicalFalse']);

        $rulesData = $algorithm->optimize([
            // permit
            (new Rule())
                ->setTarget($this->target('key1', 'value1'))
                ->setAdvice([$advice11, $advice12]),
            // deny
            (new Rule())
                ->setTarget($this->target('key2', 'value2'))
                ->setAdvice([$advice21, $advice22])
                ->setEffect($logicalFalse),
        ]);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key1' => 'value1'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value  = $result[RulesFirstApplicable::EVALUATION_VALUE];
        $advice = $result[RulesFirstApplicable::EVALUATION_ADVICE];

        $this->assertEquals(EvaluationEnum::PERMIT, $value);
        $this->assertCount(1, $advice);
        $this->assertEquals(self::CALLBACK_11, $advice[0]);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key2' => 'value2'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value  = $result[RulesFirstApplicable::EVALUATION_VALUE];
        $advice = $result[RulesFirstApplicable::EVALUATION_ADVICE];

        $this->assertEquals(EvaluationEnum::DENY, $value);
        $this->assertCount(1, $advice);
        $this->assertEquals(self::CALLBACK_22, $advice[0]);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key3' => 'non-existing'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[RulesFirstApplicable::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::NOT_APPLICABLE, $value);
    }

    /**
     * Test permit unless deny algorithm.
     */
    public function testRulePermitUnlessDeny()
    {
        $algorithm    = new RulesPermitUnlessDeny();
        $advice11     = new Advice(EvaluationEnum::PERMIT, self::CALLBACK_11);
        $advice12     = new Advice(EvaluationEnum::DENY, self::CALLBACK_12);
        $advice21     = new Advice(EvaluationEnum::PERMIT, self::CALLBACK_21);
        $advice22     = new Advice(EvaluationEnum::DENY, self::CALLBACK_22);
        $logicalFalse = new Logical([static::class, 'logicalFalse']);

        $rulesData = $algorithm->optimize([
            // permit
            (new Rule())
                ->setTarget($this->target('key1', 'value1'))
                ->setAdvice([$advice11, $advice12]),
            // deny
            (new Rule())
                ->setTarget($this->target('key2', 'value2'))
                ->setAdvice([$advice21, $advice22])
                ->setEffect($logicalFalse),
        ]);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key1' => 'value1'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value  = $result[RulesPermitUnlessDeny::EVALUATION_VALUE];
        $advice = $result[RulesPermitUnlessDeny::EVALUATION_ADVICE];

        $this->assertEquals(EvaluationEnum::PERMIT, $value);
        $this->assertEmpty($advice);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key2' => 'value2'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value  = $result[RulesPermitUnlessDeny::EVALUATION_VALUE];
        $advice = $result[RulesPermitUnlessDeny::EVALUATION_ADVICE];

        $this->assertEquals(EvaluationEnum::DENY, $value);
        $this->assertCount(1, $advice);
        $this->assertEquals(self::CALLBACK_22, $advice[0]);

        $result = $algorithm->callRuleAlgorithm(new Context(new Request(['key3' => 'non-existing'])), $rulesData);
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[RulesPermitUnlessDeny::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::PERMIT, $value);
    }

    /**
     * Test deny unless permit for policies.
     */
    public function testPoliciesOrSetsDenyUnlessPermit()
    {
        $algorithm = new PoliciesOrSetsDenyUnlessPermit();

        // permit rule
        $rule         = new Rule();
        $policiesData = $algorithm->optimize([
            (new Policy([$rule], new RulesFirstApplicable()))->setTarget($this->target('key1', 'value1')),
        ]);

        $logger = null;

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsDenyUnlessPermit::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::PERMIT, $value);

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'non-existing'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsDenyUnlessPermit::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::DENY, $value);
    }

    /**
     * Test permit unless deny for policies.
     */
    public function testPoliciesOrSetsPermitUnlessDeny()
    {
        $algorithm = new PoliciesOrSetsPermitUnlessDeny();

        $logicalFalse = new Logical([static::class, 'logicalFalse']);
        // deny rule
        $rule         = (new Rule())->setEffect($logicalFalse);
        $policiesData = $algorithm->optimize([
            (new Policy([$rule], new RulesFirstApplicable()))
                ->setTarget($this->target('key1', 'value1')),
        ]);

        $logger = null;

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsPermitUnlessDeny::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::DENY, $value);

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'non-existing'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsPermitUnlessDeny::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::PERMIT, $value);
    }

    /**
     * Test first applicable policies.
     */
    public function testPoliciesOrSetsFirstApplicable()
    {
        $algorithm = new PoliciesOrSetsFirstApplicable();

        // deny rule
        $logicalFalse = new Logical([static::class, 'logicalFalse']);
        $rule1        = (new Rule())->setEffect($logicalFalse);
        // permit rule
        $rule2        = new Rule();
        $policiesData = $algorithm->optimize([
            (new Policy([$rule1], new RulesFirstApplicable()))->setTarget($this->target('key1', 'value1')),
            (new Policy([$rule2], new RulesFirstApplicable()))->setTarget($this->target('key2', 'value2')),
        ]);

        $logger = null;

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $this->assertEquals(EvaluationEnum::DENY, $result[PoliciesOrSetsFirstApplicable::EVALUATION_VALUE]);

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key2' => 'value2'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $this->assertEquals(EvaluationEnum::PERMIT, $result[PoliciesOrSetsFirstApplicable::EVALUATION_VALUE]);
    }

    /**
     * Test permit overrides for policies.
     */
    public function testPoliciesOrSetsPermitOverrides()
    {
        $algorithm = new PoliciesOrSetsPermitOverrides();

        // deny rule
        $logicalFalse = new Logical([static::class, 'logicalFalse']);
        $rule1        = (new Rule())->setEffect($logicalFalse);
        // permit rule
        $rule2        = new Rule();
        $policiesData = $algorithm->optimize([
            (new Policy([$rule1], new RulesFirstApplicable()))->setTarget($this->target('key1', 'value1')),
            (new Policy([$rule2], new RulesFirstApplicable()))->setTarget($this->target('key1', 'value1')),
        ]);

        $logger = null;

        $result = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsPermitOverrides::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::PERMIT, $value);
    }

    /**
     * Test permit overrides with intermediate permit and intermediate deny.
     *
     * Sorry this test is very much about specifics of the algorithm and difficult to understand.
     */
    public function testPermitOverridesWithIntermediates()
    {
        $algorithm = new PoliciesOrSetsPermitOverrides();

        // intermediate deny rule
        $logicalEx    = new Logical([static::class, 'throwsException']);
        $logicalFalse = new Logical([static::class, 'logicalFalse']);
        $rule1        = (new Rule())->setCondition($logicalEx)->setEffect($logicalFalse);
        // intermediate permit rule
        $rule2   = (new Rule())->setCondition($logicalEx);
        $policy1 = (new Policy([$rule1], new RulesPermitOverrides()))->setTarget($this->target('key1', 'value1'));
        $policy2 = (new Policy([$rule2], new RulesPermitOverrides()))->setTarget($this->target('key1', 'value1'));

        $logger = null;

        $policiesData = $algorithm->optimize([$policy1, $policy2]);
        $result       = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsPermitOverrides::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT, $value);

        // now we'll try to cover those policies in set
        $set          = new PolicySet([$policy1, $policy2], $algorithm);
        $policiesData = $algorithm->optimize([$set]);
        $result       = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsPermitOverrides::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT, $value);
    }

    /**
     * Test deny overrides with intermediate permit and intermediate deny.
     *
     * Sorry this test is very much about specifics of the algorithm and difficult to understand.
     */
    public function testDenyOverridesWithIntermediates()
    {
        $algorithm = new PoliciesOrSetsDenyOverrides();

        // intermediate deny rule
        $logicalEx    = new Logical([static::class, 'throwsException']);
        $logicalFalse = new Logical([static::class, 'logicalFalse']);
        $rule1        = (new Rule())->setCondition($logicalEx)->setEffect($logicalFalse);
        // intermediate permit rule
        $rule2   = (new Rule())->setCondition($logicalEx);
        $policy1 = (new Policy([$rule1], new RulesDenyOverrides()))->setTarget($this->target('key1', 'value1'));
        $policy2 = (new Policy([$rule2], new RulesDenyOverrides()))->setTarget($this->target('key1', 'value1'));

        $logger = null;

        $policiesData = $algorithm->optimize([$policy1, $policy2]);
        $result       = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsDenyOverrides::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT, $value);

        // now we'll try to cover those policies in set
        $set          = new PolicySet([$policy1, $policy2], $algorithm);
        $policiesData = $algorithm->optimize([$set]);
        $result       = $algorithm->callPolicyAlgorithm(
            new Context(new Request(['key1' => 'value1'])),
            $policiesData,
            $logger
        );
        // here we rely on knowledge if internal structure of the result (it's not intended for direct usage)
        $value = $result[PoliciesOrSetsDenyOverrides::EVALUATION_VALUE];
        $this->assertEquals(EvaluationEnum::INDETERMINATE_DENY_OR_PERMIT, $value);
    }

    /**
     * @return void
     */
    public static function callback11()
    {
        static::$callback11Counter++;
    }

    /**
     * @return void
     */
    public static function callback12()
    {
        static::$callback12Counter++;
    }

    /**
     * @return void
     */
    public static function callback21()
    {
        static::$callback21Counter++;
    }

    /**
     * @return void
     */
    public static function callback22()
    {
        static::$callback22Counter++;
    }

    /**
     * @return bool
     */
    public static function logicalFalse()
    {
        return false;
    }

    /**
     * @throws RuntimeException
     */
    public static function throwsException()
    {
        throw new RuntimeException();
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

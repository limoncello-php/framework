<?php declare(strict_types=1);

namespace Limoncello\Tests\Auth\Authorization\PolicyDecision;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Limoncello\Auth\Authorization\PolicyAdministration\AllOf;
use Limoncello\Auth\Authorization\PolicyAdministration\AnyOf;
use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyAdministration\PolicySet;
use Limoncello\Auth\Authorization\PolicyAdministration\Rule;
use Limoncello\Auth\Authorization\PolicyAdministration\Target;
use Limoncello\Auth\Authorization\PolicyDecision\Algorithms\Encoder;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyAlgorithm;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use PHPUnit\Framework\TestCase;

/**
 * @package Limoncello\Tests\Auth
 */
class EncoderTest extends TestCase
{
    public function testEncodePolicy()
    {
        $ruleIsAdmin  = (new Rule())->setTarget($this->target('role', 'admin'));
        $createPolicy =
            (new Policy([$ruleIsAdmin], RuleAlgorithm::denyUnlessPermit()))->setTarget($this->target('op', 'create'));

        // Returned value represents policy in internal format. We won't check
        // that it has some specific structure but will test its validity later
        // in functional tests.
        $this->assertNotEmpty($encodedPolicy = Encoder::encodePolicy($createPolicy));
        $this->assertNotEmpty(Encoder::policyTarget($encodedPolicy));

        $this->assertNotEmpty($encodedRule = Encoder::encodeRule($ruleIsAdmin));
        $this->assertNotEmpty(Encoder::ruleTarget($encodedRule));
    }

    public function testEncodePolicySet()
    {
        $ruleIsAdmin  = (new Rule())->setTarget($this->target('role', 'admin'));
        $ruleIsEditor = (new Rule())->setTarget($this->target('role', 'editor'));
        $createPolicy = (new Policy([$ruleIsAdmin], RuleAlgorithm::denyUnlessPermit()))
            ->setTarget($this->target('op', 'create'));
        $updatePolicy = (new Policy([$ruleIsEditor], RuleAlgorithm::denyUnlessPermit()))
            ->setTarget($this->target('op', 'update'));
        $policySet    = (new PolicySet([$createPolicy, $updatePolicy], PolicyAlgorithm::denyUnlessPermit()))
            ->setTarget($this->target('type', 'some_resource'));

        // Returned value represents set in internal format. We won't check
        // that it has some specific structure but will test its validity later
        // in functional tests.
        $result = Encoder::encodePolicySet($policySet);
        $this->assertNotEmpty($result);
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

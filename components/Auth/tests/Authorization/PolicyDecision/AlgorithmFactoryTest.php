<?php namespace Limoncello\Tests\Auth\Authorization\PolicyDecision;

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

use Limoncello\Auth\Authorization\PolicyDecision\PolicyAlgorithm;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;

/**
 * @package Limoncello\Tests\Auth
 */
class AlgorithmFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test algorithm creation.
     */
    public function testPolicyAlgorithms()
    {
        $this->assertNotNull(PolicyAlgorithm::denyOverrides());
        $this->assertNotNull(PolicyAlgorithm::denyUnlessPermit());
        $this->assertNotNull(PolicyAlgorithm::permitOverrides());
        $this->assertNotNull(PolicyAlgorithm::permitUnlessDeny());
        $this->assertNotNull(PolicyAlgorithm::firstApplicable());
    }

    /**
     * Test algorithm creation.
     */
    public function testRuleAlgorithms()
    {
        $this->assertNotNull(RuleAlgorithm::denyOverrides());
        $this->assertNotNull(RuleAlgorithm::denyUnlessPermit());
        $this->assertNotNull(RuleAlgorithm::permitOverrides());
        $this->assertNotNull(RuleAlgorithm::permitUnlessDeny());
        $this->assertNotNull(RuleAlgorithm::firstApplicable());
    }
}

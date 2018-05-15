<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies;

/**
 * Copyright 2015-2018 info@neomerx.com
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

use Limoncello\Auth\Authorization\PolicyAdministration\PolicySet;
use Limoncello\Auth\Authorization\PolicyDecision\PolicyAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicySetInterface;

/**
 * @package Limoncello\Tests\Auth
 */
abstract class Application
{
    /**
     * @return PolicySetInterface
     */
    public static function getApplicationPolicy()
    {
        return (new PolicySet([

            Comments::getPolicies(),
            Messaging::policyCanSendMessage(),

        ], PolicyAlgorithm::denyOverrides()))->setName('Application');
    }

    /**
     * @return PolicySetInterface
     */
    public static function getApplicationPolicyThatCouldBeOptimizedAsSwitch()
    {
        $postPolicies     = Posts::getPolicies();
        $commentsPolicies = Comments::getPolicies();

        // all post rules have targets that could be combined and replaced with switch
        // comments rules cannot be replaced with switch

        // both post and comments polices have targets that could be combined and replaced with switch
        assert(
            count($allOfs = $postPolicies->getTarget()->getAnyOf()->getAllOfs()) === 1 &&
            count($allOfs[0]->getPairs()) === 1
        );
        assert(
            count($allOfs = $commentsPolicies->getTarget()->getAnyOf()->getAllOfs()) === 1 &&
            count($allOfs[0]->getPairs()) === 1
        );

        // thus we can check how optimization algorithm works with targets for Rules and Policies.

        return (new PolicySet([

            $postPolicies,
            $commentsPolicies,

        ], PolicyAlgorithm::firstApplicable()))->setName('Application');
    }
}

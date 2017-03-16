<?php namespace Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\Policies;

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

use Limoncello\Auth\Authorization\PolicyAdministration\Policy;
use Limoncello\Auth\Authorization\PolicyDecision\RuleAlgorithm;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\PolicyInterface;
use Limoncello\Auth\Contracts\Authorization\PolicyAdministration\TargetInterface;
use Limoncello\Tests\Auth\Authorization\PolicyEnforcement\Data\ContextProperties;

/**
 * @package Limoncello\Tests\Auth
 */
abstract class Messaging extends General
{
    /** Operation identity */
    const OPERATION_SEND = 'send_message';

    /**
     * @return PolicyInterface
     */
    public static function policyCanSendMessage()
    {
        return (new Policy([static::rulePermit()], RuleAlgorithm::denyUnlessPermit()))
            ->setTarget(static::targetSendMessage())
            ->setName('Messaging');
    }

    /**
     * @return TargetInterface
     */
    protected static function targetSendMessage()
    {
        return static::targetMulti([
            ContextProperties::PARAM_OPERATION    => static::OPERATION_SEND,
            ContextProperties::PARAM_IS_WORK_TIME => true,
        ]);
    }
}
